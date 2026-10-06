import { readFile, stat, readdir } from 'node:fs/promises';
import { join } from 'node:path';
import sharp from 'sharp';

const project = new URL('../', import.meta.url).pathname.replace(/^\/([A-Z]:\/)/i, '$1');
const manifest = JSON.parse(await readFile(join(project, 'storage/app/webp-manifest.json'), 'utf8'));
let checked = 0;
let saved = 0;
const failures = [];
for (const row of manifest.files) {
  if (!['ready', 'already-exists', 'refresh'].includes(row.status)) continue;
  const path = join(project, 'public', row.to.slice(1));
  try {
    const info = await sharp(path, { failOn: 'error' }).metadata();
    if (info.format !== 'webp' || info.width !== row.width || info.height !== row.height) throw new Error('format or dimensions mismatch');
    const size = (await stat(path)).size;
    if (size >= row.sourceBytes) throw new Error('WebP is not smaller');
    checked++;
    saved += row.sourceBytes - size;
  } catch (error) { failures.push({ path: row.to, error: error.message }); }
}
let checkedReferences = 0;
async function checkReferences(folder) {
  for (const entry of await readdir(folder, { withFileTypes: true })) {
    const path = join(folder, entry.name);
    if (entry.isDirectory()) await checkReferences(path);
    else if (/\.(blade\.php|css|js|vue)$/i.test(entry.name)) {
      const source = await readFile(path, 'utf8');
      for (const match of source.matchAll(/\/(?:media|site-media|assets|files)\/[^"'\s)]+\.webp/gi)) {
        const target = join(project, 'public', match[0].slice(1));
        if (!(await stat(target).catch(() => null))) failures.push({ file: path, reference: match[0], error: 'missing file' });
        checkedReferences++;
      }
    }
  }
}
await checkReferences(join(project, 'resources/views'));
await checkReferences(join(project, 'public/assets/css'));
console.log(JSON.stringify({ checked, checkedReferences, savedBytes: saved, failures }, null, 2));
if (failures.length) process.exitCode = 1;
