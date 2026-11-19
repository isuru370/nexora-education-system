// Tailwind CSS (theme + utilities, no nexora/*.css) for the candidates on stdin (JSON array).
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const twDir = path.join(root, 'node_modules/tailwindcss');
const { compile } = await import(path.join(twDir, 'dist/lib.mjs'));
const src = fs.readFileSync(path.join(root, 'resources/css/app.css'), 'utf8').replace(/@import ["']\.\/nexora\/[^;]+;/g, '');
const c = await compile(src, {
    base: path.join(root, 'resources/css'),
    async loadStylesheet(id, base) {
        const f = id.startsWith('tailwindcss/') ? path.join(twDir, id.slice(12)) : path.resolve(base, id);
        return { path: f, base: path.dirname(f), content: fs.readFileSync(f, 'utf8') };
    },
});
process.stdout.write(c.build(JSON.parse(fs.readFileSync(0, 'utf8'))));
