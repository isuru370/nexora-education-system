// Screenshots of every page for the UI (Tailwind) migration.
// usage: node shoot.mjs <base url> <urls.json> <out dir>
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const [base, urlsFile, outDir] = process.argv.slice(2);
const urls = JSON.parse(fs.readFileSync(urlsFile, 'utf8'));
fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch();

async function session(viewport) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 1 });
    // Same clock for both runs: no blinking caret / animation differences
    await context.addInitScript(() => {
        const style = document.createElement('style');
        style.textContent = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';
        document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
    });
    const page = await context.newPage();
    for (let attempt = 1; ; attempt++) {
        try {
            await page.goto(base + '/login');
            await page.fill('input[name=email]', 'admin@test.lk');
            await page.fill('input[name=password]', 'password123');
            await Promise.all([page.waitForNavigation(), page.click('button[type=submit]')]);
            if (!page.url().includes('/login')) break;
        } catch (e) {
            if (attempt >= 3) throw e;
        }
        if (attempt >= 3) throw new Error('login failed');
        await page.waitForTimeout(1000);
    }
    return { context, page };
}

const name = (url) => (url.replace(/^\//, '').replace(/[^a-zA-Z0-9]+/g, '_') || 'home');

// The login page itself (logged out)
{
    const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
    const page = await context.newPage();
    await page.goto(base + '/login');
    await page.waitForTimeout(300);
    await page.screenshot({ path: path.join(outDir, 'desktop__login.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: path.join(outDir, 'mobile__login.png'), fullPage: true });
    await context.close();
}

function layout() {
    const props = ['display', 'position', 'box-sizing', 'width', 'height', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'border-top-width', 'border-bottom-width',
        'font-size', 'font-weight', 'line-height', 'color', 'background-color', 'gap', 'flex', 'text-align'];
    const out = [];
    for (const el of document.body.querySelectorAll('*')) {
        if (el.closest('#sidebar')) continue;
        const r = el.getBoundingClientRect();
        const cs = getComputedStyle(el);
        const id = el.id ? '#' + el.id : '';
        const cls = typeof el.className === 'string' ? el.className.trim().split(/\s+/).filter((c) => c && !c.startsWith('tw:')).slice(0, 3).map((c) => '.' + c).join('') : '';
        out.push([el.tagName.toLowerCase() + id + cls, Math.round(r.x), Math.round(r.y + scrollY), Math.round(r.width), Math.round(r.height),
            props.map((p) => cs.getPropertyValue(p)).join('|')]);
    }
    return out;
}

for (const [label, viewport] of [['desktop', { width: 1366, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
    const { context, page } = await session(viewport);
    const list = label === 'mobile' ? urls.slice(0, 25) : urls;

    for (const url of list) {
        try {
            await page.goto(base + url, { waitUntil: 'networkidle', timeout: 20000 });
            await page.waitForTimeout(200);
            await page.screenshot({ path: path.join(outDir, `${label}__${name(url)}.png`), fullPage: true });
            // element boxes + a few styles, to find what moved when the picture changes
            fs.writeFileSync(path.join(outDir, `${label}__${name(url)}.json`), JSON.stringify(await page.evaluate(layout)));
        } catch (e) {
            console.log(`skip ${url}: ${e.message.split('\n')[0]}`);
        }
    }
    await context.close();
}

await browser.close();
console.log('done');
