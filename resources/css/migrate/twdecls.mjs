// Prints the CSS declarations Tailwind makes for each candidate class.
//   echo '["tw:mb-4!","tw:md:flex"]' | node resources/css/migrate/twdecls.mjs
// -> {"tw:mb-4!": [{"media": "", "sel": "...", "decls": [["margin-bottom", "...", true]]}], ...}
// Used by bs2tw.py to check that a Tailwind class does exactly what the
// Bootstrap class did.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
const require = createRequire(path.join(root, 'package.json'));
const twDir = path.dirname(require.resolve('tailwindcss/package.json'));
const { compile } = await import(path.join(twDir, 'dist/lib.mjs'));

// app.css without our own CSS files: only the theme and the utilities
const entry = path.join(root, 'resources/css/app.css');
const source = fs.readFileSync(entry, 'utf8').replace(/@import ["']\.\/nexora\/[^;]+;/g, '');
const load = async (id, base) => {
    const file = id.startsWith('tailwindcss/') ? path.join(twDir, id.slice('tailwindcss/'.length)) : path.resolve(base, id);
    return { path: file, base: path.dirname(file), content: fs.readFileSync(file, 'utf8') };
};
const empty = (await (await compile(source, { base: path.dirname(entry), loadStylesheet: load })).build([]));

function parse(css) {
    const out = [];
    const re = /(@media[^{]+)\{|([^{}]+)\{([^{}]*)\}|\}/g;
    let media = '';
    let m;
    while ((m = re.exec(css))) {
        if (m[1]) media = m[1].trim();
        else if (m[2]) {
            const decls = m[3].split(';').map((d) => d.trim()).filter(Boolean).map((d) => {
                const i = d.indexOf(':');
                let v = d.slice(i + 1).trim();
                const imp = /!important$/.test(v);
                return [d.slice(0, i).trim(), v.replace(/\s*!important$/, ''), imp];
            });
            out.push({ media, sel: m[2].trim(), decls });
        } else media = '';
    }
    return out;
}

const candidates = JSON.parse(fs.readFileSync(0, 'utf8'));
const result = {};
for (const c of candidates) {
    const compiler = await compile(source, { base: path.dirname(entry), loadStylesheet: load });
    const css = compiler.build([c]);
    const extra = css.startsWith(empty.slice(0, empty.lastIndexOf('}'))) ? css : css;
    // keep only the rules that are not in the empty build (theme, @property ...)
    const rules = parse(css.replace(/@layer[^{]*\{/g, '').replace(/@property[^{]+\{[^}]*\}/g, '').replace(/@supports[^{]+\{/g, ''));
    result[c] = rules.filter((r) => r.sel.includes('tw\\:') || r.sel.startsWith(':is(') || r.sel.startsWith(':where('));
}
process.stdout.write(JSON.stringify(result));
