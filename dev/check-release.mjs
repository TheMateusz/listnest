import { spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync, readdirSync, cpSync, existsSync, mkdirSync } from 'node:fs';
import { resolve, relative, isAbsolute } from 'node:path';
const root = process.cwd();
const generated = resolve('.test-env');
const home = resolve(generated, 'home');
const configFile = resolve(generated, 'release.json');
const current = readdirSync(home).find(name => name.includes('-current83-'));
if (!current) throw new Error('Run the current83 matrix profile first');
const cache = resolve(home, current);
const config = {
  ...JSON.parse(readFileSync('.wp-env.json')), phpVersion: '8.3', port: 8894,
  plugins: ['woocommerce', 'contact-form-7', 'plugin-check'].map(name => resolve(cache, name)),
  themes: [resolve(cache, 'storefront')],
};
config.mappings = Object.fromEntries(Object.entries(config.mappings).map(([target, source]) => [target, resolve(source)]));
config.mappings['wp-content/listnest-release'] = resolve('releases');
writeFileSync(configFile, JSON.stringify(config, null, 2));
mkdirSync('test-results', { recursive: true });
const report = { started: new Date().toISOString(), checks: {}, passed: false };
function run(command, args) {
  const result = spawnSync(command, args, { cwd: root, shell: process.platform === 'win32', encoding: 'utf8', timeout: 15 * 60 * 1000, maxBuffer: 16 * 1024 * 1024, env: { ...process.env, WP_ENV_HOME: home } });
  const output = (result.stdout || '') + (result.stderr || '');
  process.stdout.write(output);
  if (result.error || result.status !== 0) throw new Error(`${command}: ${result.error || output}`);
  return output;
}
const wp = (...args) => run('wp-env', [...args, process.platform === 'win32' ? `--config="${configFile}"` : `--config=${configFile}`]);
try {
  for (let attempt = 0; ; attempt++) {
    try { wp('start'); break; } catch (error) {
      const match = String(error).match(/EPERM: operation not permitted, rename '([^']+)' -> '([^']+)'/);
      const inside = path => { const rel = relative(generated, resolve(path)); return rel && !rel.startsWith('..') && !isAbsolute(rel); };
      if (!match || attempt >= 5 || !inside(match[1]) || !inside(match[2]) || existsSync(match[2])) throw error;
      cpSync(match[1], match[2], { recursive: true, errorOnExist: true, force: false });
    }
  }
  // This environment never maps the repository's listnest directory.
  report.checks.install = wp('run', 'cli', 'wp', 'plugin', 'install', 'wp-content/listnest-release/listnest-1.0.0.zip', '--activate', '--force');
  report.checks.version = wp('run', 'cli', 'wp', 'plugin', 'get', 'listnest', '--field=version');
  if (!report.checks.version.includes('1.0.0')) throw new Error('Wrong installed version');
  report.checks.integrity = wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/package-integrity.php');
  wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/seed.php');
  report.checks.regression = wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/regression.php');
  report.checks.releaseRegression = wp('run', 'cli', 'wp', 'eval-file', 'wp-content/listnest-dev/release-regression.php');
  report.checks.form = run('node', ['dev/test-http.mjs', 'http://localhost:8894']);
  report.checks.cf7 = run('node', ['dev/test-cf7.mjs', 'http://localhost:8894']);
  report.checks.variation = run('node', ['dev/test-variation.mjs', 'http://localhost:8894']);
  report.checks.pluginCheck = wp('run', 'cli', 'wp', 'plugin', 'check', 'listnest', '--require=wp-content/plugins/plugin-check/cli.php', '--format=json');
  if (/"type"\s*:\s*"ERROR"/i.test(report.checks.pluginCheck)) throw new Error('Plugin Check reported errors');
  report.passed = true;
} catch (error) {
  report.error = String(error);
  process.exitCode = 1;
} finally {
  report.finished = new Date().toISOString();
  writeFileSync('test-results/release-package.json', JSON.stringify(report, null, 2));
}
