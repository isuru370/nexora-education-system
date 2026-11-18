// Compares before/ and after/ screenshots; writes changed pages (as JPEG,
// side by side) to <out> and a markdown summary to <out>/summary.md
// usage: node compare.mjs <before dir> <after dir> <out dir>
import fs from 'node:fs';
import path from 'node:path';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';
import sharp from 'sharp';

const [beforeDir, afterDir, outDir] = process.argv.slice(2);
fs.mkdirSync(outDir, { recursive: true });

const files = [...new Set([...fs.readdirSync(beforeDir), ...fs.readdirSync(afterDir)])].filter(f => f.endsWith('.png')).sort();
const rows = [];

for (const file of files) {
    const a = path.join(beforeDir, file), b = path.join(afterDir, file);
    if (!fs.existsSync(a) || !fs.existsSync(b)) { rows.push([file, fs.existsSync(a) ? 'missing after' : 'new page', 100]); continue; }

    const imgA = PNG.sync.read(fs.readFileSync(a)), imgB = PNG.sync.read(fs.readFileSync(b));
    const width = Math.max(imgA.width, imgB.width), height = Math.max(imgA.height, imgB.height);
    const pad = (img) => { const out = new PNG({ width, height }); out.data.fill(255); PNG.bitblt(img, out, 0, 0, img.width, img.height, 0, 0); return out; };
    const pa = pad(imgA), pb = pad(imgB);
    const diff = pixelmatch(pa.data, pb.data, null, width, height, { threshold: 0.1 });
    const pct = +(diff / (width * height) * 100).toFixed(2);

    if (pct > 0.05) {
        rows.push([file, `${imgA.height}px → ${imgB.height}px`, pct]);
        const scale = Math.min(1, 700 / width);
        const left = await sharp(a).resize({ width: Math.round(imgA.width * scale) }).toBuffer();
        const right = await sharp(b).resize({ width: Math.round(imgB.width * scale) }).toBuffer();
        const w = Math.round(width * scale), h = Math.round(height * scale);
        await sharp({ create: { width: w * 2 + 20, height: h, channels: 3, background: '#ff00ff' } })
            .composite([{ input: left, left: 0, top: 0 }, { input: right, left: w + 20, top: 0 }])
            .jpeg({ quality: 70 })
            .toFile(path.join(outDir, file.replace('.png', '.jpg')));
    }
}

rows.sort((x, y) => y[2] - x[2]);
const md = [
    `${files.length} screenshots, **${rows.length} changed** (left: before, right: after)`,
    '',
    '| Page | Height | Changed |',
    '|---|---|---|',
    ...rows.map(([f, h, p]) => `| ${f.replace('.png', '')} | ${h} | ${p}% |`),
].join('\n');
fs.writeFileSync(path.join(outDir, 'summary.md'), md + '\n');
console.log(md);
