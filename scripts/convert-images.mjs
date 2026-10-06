import { readdir, readFile, writeFile, rename, stat, unlink } from 'node:fs/promises';
import { join, relative, extname } from 'node:path';
import { createHash } from 'node:crypto';
import sharp from 'sharp';

const root = new URL('../public/', import.meta.url).pathname.replace(/^\/([A-Z]:\/)/i, '$1');
const write = process.argv.includes('--write');
const refreshIcons = process.argv.includes('--refresh-icons');
const limitArg = process.argv.find(arg => arg.startsWith('--limit='));
const limit = limitArg ? Number(limitArg.split('=')[1]) : Infinity;
const startAfter = process.argv.find(arg => arg.startsWith('--start-after='))?.slice('--start-after='.length) || '';
if ((!Number.isFinite(limit) && limit !== Infinity) || limit <= 0) throw new Error('Invalid --limit');
const folders = ['media', 'site-media', 'files', 'assets'];
const skip = path => path.includes('/favicon/') || path.includes('/vendor/') || path.includes('/dependencies/');
const candidates = [];

async function walk(folder) {
  for (const entry of await readdir(folder, { withFileTypes: true })) {
    const path = join(folder, entry.name);
    if (entry.isDirectory()) await walk(path);
    else if (/\.(jpe?g|png)$/i.test(entry.name) && !skip(path.replaceAll('\\', '/'))) candidates.push(path);
  }
}

for (const folder of folders) {
  try { await walk(join(root, folder)); } catch (error) { if (error.code !== 'ENOENT') throw error; }
}
candidates.sort();
const preferredByTarget = new Map();
for (const source of candidates) {
  const target = source.slice(0, -extname(source).length) + '.webp';
  const preferred = preferredByTarget.get(target);
  if (!preferred || (extname(source).toLowerCase() === '.png' && extname(preferred).toLowerCase() !== '.png')) {
    preferredByTarget.set(target, source);
  }
}

const manifest = [];
let processed = 0;
for (const source of candidates) {
  const from = '/' + relative(root, source).replaceAll('\\', '/');
  if (from <= startAfter) continue;
  if (processed >= limit) break;
  const target = source.slice(0, -extname(source).length) + '.webp';
  const to = '/' + relative(root, target).replaceAll('\\', '/');
  if (preferredByTarget.get(target) !== source) {
    manifest.push({ from, to, status: 'target-collision', preferredSource: '/' + relative(root, preferredByTarget.get(target)).replaceAll('\\', '/') });
    processed++;
    continue;
  }
  try {
    const input = await readFile(source);
    const metadata = await sharp(input, { failOn: 'error' }).metadata();
    const lossless = extname(source).toLowerCase() === '.png' && metadata.width <= 512 && metadata.height <= 512;
    const output = await sharp(input, { failOn: 'error' }).rotate().webp(lossless
      ? { lossless: true, effort: 6 }
      : { quality: 82, alphaQuality: 100, effort: 6 }).toBuffer();
    const smaller = output.length < input.length;
    const existing = await stat(target).catch(() => null);
    const existingBytes = existing ? await readFile(target) : null;
    let status = !smaller ? 'larger-than-original' : existingBytes
      ? (existingBytes.equals(output) ? 'already-exists' : 'conflict') : 'ready';
    if (status === 'conflict' && refreshIcons && lossless) {
      const legacy = await sharp(input, { failOn: 'error' }).rotate().webp({ quality: 82, alphaQuality: 100, effort: 6 }).toBuffer();
      if (existingBytes.equals(legacy)) status = 'refresh';
    }
    manifest.push({ from, to, status, sourceBytes: input.length, webpBytes: output.length, width: metadata.width, height: metadata.height, sourceSha256: createHash('sha256').update(input).digest('hex') });
    if (write && ['ready', 'refresh'].includes(status)) {
      const decoded = await sharp(output).metadata();
      if (decoded.format !== 'webp' || decoded.width !== metadata.width || decoded.height !== metadata.height) throw new Error(`Invalid WebP: ${to}`);
      const temp = target + `.${process.pid}.tmp`;
      try {
        await writeFile(temp, output, { flag: 'wx' });
        if (status === 'refresh') {
          await writeFile(target, output);
        } else {
          for (let attempt = 0; attempt < 5; attempt++) {
            try { await rename(temp, target); break; }
            catch (error) {
              if (error.code !== 'EBUSY' || attempt === 4) throw error;
              await new Promise(resolve => setTimeout(resolve, 100 * (attempt + 1)));
            }
          }
        }
      } finally { await unlink(temp).catch(() => {}); }
    }
  } catch (error) {
    manifest.push({ from, status: 'error', error: error.message });
  }
  processed++;
  if (processed % 50 === 0) console.log(`Processed ${processed} images`);
}

const summary = {
  mode: write ? 'write' : 'dry-run',
  processed,
  ready: manifest.filter(row => row.status === 'ready').length,
  refreshed: manifest.filter(row => row.status === 'refresh').length,
  alreadyExists: manifest.filter(row => row.status === 'already-exists').length,
  conflicts: manifest.filter(row => row.status === 'conflict').length,
  collisions: manifest.filter(row => row.status === 'target-collision').length,
  larger: manifest.filter(row => row.status === 'larger-than-original').length,
  errors: manifest.filter(row => row.status === 'error').length,
  estimatedSavedBytes: manifest.filter(row => ['ready', 'already-exists', 'refresh'].includes(row.status)).reduce((sum, row) => sum + row.sourceBytes - row.webpBytes, 0),
};
const report = new URL('../storage/app/webp-manifest.json', import.meta.url);
await writeFile(report, JSON.stringify({ summary, files: manifest }, null, 2));
console.log(JSON.stringify({ summary, report: report.pathname }, null, 2));
if (summary.errors || summary.conflicts) process.exitCode = 1;
