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

// the first element (document order) whose box or style is not the same
const PROPS = ['display', 'position', 'box-sizing', 'width', 'height', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'border-top-width', 'border-bottom-width',
    'font-size', 'font-weight', 'line-height', 'color', 'background-color', 'gap', 'flex', 'text-align'];
function firstMoved(file) {
    const ja = path.join(beforeDir, file.replace('.png', '.json')), jb = path.join(afterDir, file.replace('.png', '.json'));
    if (!fs.existsSync(ja) || !fs.existsSync(jb)) return '';
    const A = JSON.parse(fs.readFileSync(ja, 'utf8')), B = JSON.parse(fs.readFileSync(jb, 'utf8'));
    const n = Math.min(A.length, B.length);
    const desc = (i, extra) => {
        const a = A[i], b = B[i];
        return `\`${b[0]}\` ${a[3]}×${a[4]}@${a[1]},${a[2]} → ${b[3]}×${b[4]}@${b[1]},${b[2]} ${extra || ''}`.slice(0, 300);
    };
    // 1) an element whose own styles changed
    for (let i = 0; i < n; i++) {
        if (A[i][0] !== B[i][0]) return `element ${i}: \`${A[i][0]}\` → \`${B[i][0]}\``;
        const sa = A[i][5].split('|'), sb = B[i][5].split('|');
        const styles = PROPS.map((p, k) => (sa[k] !== sb[k] && p !== 'width' && p !== 'height' ? `${p}: ${sa[k]} → ${sb[k]}` : '')).filter(Boolean);
        if (styles.length) return desc(i, styles.join('; '));
    }
    // 2) the first element that moved, and the last one before it that changed size (the cause)
    for (let i = 0; i < n; i++) {
        const a = A[i], b = B[i];
        if (a[1] !== b[1] || a[2] !== b[2]) {
            let cause = '';
            for (let j = i - 1; j >= 0; j--) {
                if (A[j][3] !== B[j][3] || A[j][4] !== B[j][4]) {
                    // the deepest one: skip ancestors (they contain i)
                    if (A[j][2] + A[j][4] >= a[2] && A[j][2] <= a[2]) continue;
                    cause = ' after ' + desc(j);
                    break;
                }
            }
            return 'moved ' + desc(i) + cause;
        }
    }
    for (let i = n - 1; i >= 0; i--) {
        if (A[i][3] !== B[i][3] || A[i][4] !== B[i][4]) return 'size ' + desc(i);
    }
    return A.length !== B.length ? `elements ${A.length} → ${B.length}` : '';
}

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
        rows[rows.length - 1].push(firstMoved(file));
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
    '| Page | Height | Changed | First element that differs |',
    '|---|---|---|---|',
    ...rows.map(([f, h, p, m]) => `| ${f.replace('.png', '')} | ${h} | ${p}% | ${(m || '').replace(/\|/g, '/')} |`),
].join('\n');
fs.writeFileSync(path.join(outDir, 'summary.md'), md + '\n');
console.log(md);
