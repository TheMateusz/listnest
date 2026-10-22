import { spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync, cpSync, existsSync } from 'node:fs';
import { resolve, relative, isAbsolute } from 'node:path';

// Run from the repository root. Each profile owns its containers, database and port.
const profiles = {
  minimum: { core: 'https://wordpress.org/wordpress-6.5.zip', phpVersion: '7.4', wc: '9.0.2', cf7: '5.9.8', port: 8891 },
  current82: { core: 'https://wordpress.org/wordpress-7.1.3.zip', phpVersion: '8.2', wc: '11.2.1', cf7: '5.9.8', port: 8892 },
  current83: { core: 'https://wordpress.org/wordpress-7.1.3.zip', phpVersion: '8.3', wc: '11.2.1', cf7: '6.2.3', port: 8893 },
};
const args = process.argv.slice(2);
const requested = args.filter(x => x.startsWith('--profile=')).map(x => x.split('=')[1]);
const selected = args.includes('--all') ? Object.keys(profiles) : requested.length ? requested : ['current82'];
const planOnly = args.includes('--plan');
const root = process.cwd();
const generated = resolve(root, '.test-env');
mkdirSync(generated, { recursive: true });
mkdirSync(resolve(root, 'test-results'), { recursive: true });
const base = JSON.parse(readFileSync('.wp-env.json', 'utf8'));
let failed = false;
function run(command, commandArgs, env = {}) {
  const result = spawnSync(command, commandArgs, {
    cwd: root, encoding: 'utf8', env: { ...process.env, ...env },
    shell: process.platform === 'win32', maxBuffer: 16 * 1024 * 1024, timeout: 15 * 60 * 1000,
  });
  const output = `${result.stdout || ''}${result.stderr || ''}`;
  process.stdout.write(output);
  if (result.error || result.status !== 0) throw new Error(`${command} failed (${result.status}): ${result.error || output}`);
  return output;
}
if (!planOnly) run('docker', ['info', '--format', '{{.ServerVersion}}']);
for (const name of selected) {
  if (!Object.hasOwn(profiles, name)) throw new Error(`Unknown profile: ${name}`);
  const p = profiles[name];
  const file = resolve(generated, `${name}.json`);
  const config = {
    ...base, core: p.core, phpVersion: p.phpVersion, port: p.port,
    plugins: [
      `https://downloads.wordpress.org/plugin/woocommerce.${p.wc}.zip`,
      `https://downloads.wordpress.org/plugin/contact-form-7.${p.cf7}.zip`,
      'https://downloads.wordpress.org/plugin/plugin-check.2.1.0.zip', resolve(root, 'listnest'),
    ],
    mappings: Object.fromEntries(Object.entries(base.mappings).map(([target, source]) => [target, resolve(root, source)])),
    themes: ['https://downloads.wordpress.org/theme/storefront.4.6.2.zip'],
  };
  writeFileSync(file, JSON.stringify(config, null, 2));
  console.log(`\n${name}: PHP ${p.phpVersion}, WP ${p.core || 'latest stable'}, WC ${p.wc}, http://localhost:${p.port}`);
  if (planOnly) continue;
  const report = { profile: name, started: new Date().toISOString(), config, checks: {}, passed: false };
  const env = { WP_ENV_HOME: resolve(generated, 'home') };
  const configArg = process.platform === 'win32' ? `--config="${file}"` : `--config=${file}`;
  const wp = (...commandArgs) => run('wp-env', [...commandArgs, configArg], env);
  try {
    for (let attempt = 0; ; attempt++) {
      try { wp('start'); break; } catch (error) {
        // Windows can reject renaming an extracted directory. Preserve its files
        // by copying only inside our generated cache, then let wp-env retry.
        const match = String(error).match(/EPERM: operation not permitted, rename '([^']+)' -> '([^']+)'/);
        const inside = path => {
          const rel = relative(generated, resolve(path));
          return rel && !rel.startsWith('..') && !isAbsolute(rel);
        };
        if (process.platform !== 'win32' || attempt >= 5 || !match || !inside(match[1]) || !inside(match[2]) || existsSync(match[2])) throw error;
        cpSync(match[1], match[2], { recursive: true, errorOnExist: true, force: false });
        report.windowsCopyRecoveries = (report.windowsCopyRecoveries || 0) + 1;
      }
    }
    report.checks.versions = wp('run', 'cli', 'wp', 'core', 'version') + wp('run', 'cli', 'wp', 'plugin', 'list', '--format=json') + wp('run', 'cli', 'php', '-v');
    wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/seed.php');
    report.checks.regression = wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/regression.php');
    report.checks.releaseRegression = wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/release-regression.php');
    report.checks.form = run('node', ['dev/test-http.mjs', `http://localhost:${p.port}`]);
    report.checks.cf7 = run('node', ['dev/test-cf7.mjs', `http://localhost:${p.port}`]);
    report.checks.variation = run('node', ['dev/test-variation.mjs', `http://localhost:${p.port}`]);
    report.checks.pluginCheck = wp('run', 'cli', 'wp', 'plugin', 'check', 'listnest', '--require=wp-content/plugins/plugin-check/cli.php', '--format=json');
    if (/"type"\s*:\s*"ERROR"/i.test(report.checks.pluginCheck)) throw new Error('Plugin Check reported errors; inspect checks.pluginCheck');
    report.passed = true;
  } catch (error) {
    report.error = String(error);
    failed = true;
  } finally {
    if (args.includes('--keep-running')) {
      report.keptRunning = true;
    } else {
      try { wp('stop'); } catch (error) { report.stopError = String(error); failed = true; }
    }
    report.finished = new Date().toISOString();
    writeFileSync(resolve(root, 'test-results', `${name}.json`), JSON.stringify(report, null, 2));
  }
}
process.exitCode = failed ? 1 : 0;
