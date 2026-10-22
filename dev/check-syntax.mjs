import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
function files(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => entry.isDirectory() ? files(join(directory, entry.name)) : [join(directory, entry.name)]);
}
let checked = 0;
for (const file of [...files('listnest'), ...files('dev')]) {
  const command = file.endsWith('.php') ? 'php' : /\.(?:js|mjs)$/.test(file) ? process.execPath : null;
  if (!command) continue;
  const result = spawnSync(command, [command === 'php' ? '-l' : '--check', file], { encoding: 'utf8' });
  if (result.error || result.status !== 0) {
    process.stderr.write(`${file}: ${result.error || result.stdout + result.stderr}\n`);
    process.exit(1);
  }
  checked++;
}
console.log(`PASS: syntax checked in ${checked} PHP/JavaScript files`);
