import { readdir, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import phpParser from 'php-parser';

const parser = new phpParser.Engine({ parser: { phpVersion: '8.4', suppressErrors: false }, ast: { withPositions: true } });
const roots = ['app', 'config', 'database/migrations', 'routes', 'tests'];
let count = 0;
let errors = 0;
async function walk(folder) {
  for (const entry of await readdir(folder, { withFileTypes: true })) {
    const path = join(folder, entry.name);
    if (entry.isDirectory()) await walk(path);
    else if (entry.name.endsWith('.php')) {
      count++;
      try { parser.parseCode(await readFile(path, 'utf8'), path); }
      catch (error) { errors++; console.error(`${path}: ${error.message}`); }
    }
  }
}
for (const root of roots) await walk(root);
console.log(`Parsed ${count} PHP files; errors: ${errors}`);
if (errors) process.exitCode = 1;
