// Builds public/css/nexora.css and public/css/guest.css with Tailwind v4.
//   node resources/css/build.mjs           build
//   node resources/css/build.mjs --check   fail if a built file is out of date (CI)
//
// Only tw:-prefixed classes are generated. They are collected from the Blade
// views and the JS files below.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(path.join(root, 'package.json'));
const twDir = path.dirname(require.resolve('tailwindcss/package.json'));
const { compile } = await import(path.join(twDir, 'dist/lib.mjs'));

// source -> built file
const entries = {
    'resources/css/app.css': 'public/css/nexora.css', // app layout (with Bootstrap, no preflight)
    'resources/css/guest.css': 'public/css/guest.css', // login and other pages without Bootstrap
    'resources/css/page.css': 'public/css/page.css', // stand-alone pages (forgot password ...): ui-kit + Tailwind
};

const sources = ['resources/views', 'public/js/nx-ui.js', 'app'];

function files(p) {
    const full = path.join(root, p);
    if (!fs.existsSync(full)) return [];
    if (fs.statSync(full).isFile()) return [full];
    return fs.readdirSync(full, { withFileTypes: true }).flatMap((d) =>
        d.isDirectory() ? files(path.join(p, d.name)) : /\.(php|js|html)$/.test(d.name) ? [path.join(full, d.name)] : []
    );
}

const candidates = new Set();
for (const file of sources.flatMap(files)) {
    // tw:… up to a space or quote; [arbitrary values] may contain quotes
    for (const m of fs.readFileSync(file, 'utf8').matchAll(/tw:(?:\[[^\]\s]*\]|[^\s"'`<>{}\[])+/g)) {
        candidates.add(m[0].replace(/[,;]+$/, ''));
    }
}

let failed = false;

for (const [source, target] of Object.entries(entries)) {
    const entry = path.join(root, source);
    const output = path.join(root, target);

    const compiler = await compile(fs.readFileSync(entry, 'utf8'), {
        base: path.dirname(entry),
        async loadStylesheet(id, base) {
            const file = id.startsWith('tailwindcss/') ? path.join(twDir, id.slice('tailwindcss/'.length)) : path.resolve(base, id);
            return { path: file, base: path.dirname(file), content: fs.readFileSync(file, 'utf8') };
        },
    });

    const css = compiler.build([...candidates].sort());

    if (process.argv.includes('--check')) {
        const current = fs.existsSync(output) ? fs.readFileSync(output, 'utf8') : '';
        if (current !== css) {
            console.error(`${target} is out of date: run \`npm run css\` and commit it.`);
            failed = true;
        } else {
            console.log(`${target} is up to date`);
        }
    } else {
        fs.writeFileSync(output, css);
        console.log(`${target}: ${candidates.size} classes, ${(css.length / 1024).toFixed(1)} KB`);
    }
}

process.exit(failed ? 1 : 0);
