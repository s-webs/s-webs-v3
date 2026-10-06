import { readFile, readdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';

const project = new URL('../', import.meta.url).pathname.replace(/^\/([A-Z]:\/)/i, '$1');
const manifest = JSON.parse(await readFile(join(project, 'storage/app/webp-manifest.json'), 'utf8'));
const replacements = manifest.files
  .filter(row => ['ready', 'already-exists', 'refresh'].includes(row.status))
  .map(row => [row.from.slice(1), row.to.slice(1)])
  .sort((a, b) => b[0].length - a[0].length);
const roots = ['resources/views', 'resources/css', 'resources/js', 'public/assets/css', 'public/assets/js'];
const write = process.argv.includes('--write');
const changed = [];

async function walk(folder) {
  for (const entry of await readdir(folder, { withFileTypes: true })) {
    const path = join(folder, entry.name);
    if (entry.isDirectory()) await walk(path);
    else if (/\.(blade\.php|css|js|vue)$/i.test(entry.name)) {
      const before = await readFile(path, 'utf8');
      let after = before;
      const matches = [];
      for (const [from, to] of replacements) {
        if (!after.includes(from)) continue;
        after = after.replaceAll(from, to);
        matches.push(from);
      }
      if (after !== before) {
        changed.push({ file: path.replace(project, '').replaceAll('\\', '/'), replacements: matches.length });
        if (write) await writeFile(path, after);
      }
    }
  }
}

for (const root of roots) await walk(join(project, root));
console.log(JSON.stringify({ mode: write ? 'write' : 'dry-run', changed }, null, 2));
