#!/usr/bin/env node

import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = resolve(__dirname, '..', 'docs/images');
const baseUrl = process.env.WORKBENCH_URL ?? 'http://127.0.0.1:8766';
const page_path = '/admin/deliveries';

// Each target opens the picker in one data-demo section; `then` moves it to the state worth showing.
// Stages are switched through Alpine rather than by clicking a digit, which would leave its text selected.
const stage = (name) => (picker) => picker.evaluate((el, s) => { window.Alpine.$data(el).stage = s }, name);

const targets = [
    { file: 'twelve_hour', demo: 'twelve-hour' },
    { file: 'minutes', demo: 'twelve-hour', then: stage('minute') },
    { file: 'twenty_four_hour', demo: 'twenty-four-hour' },
    { file: 'bounds', demo: 'bounds' },
    { file: 'seconds', demo: 'seconds', then: stage('second') },
    { file: 'date_time_calendar', demo: 'date-time' },
    { file: 'date_time_clock', demo: 'date-time', then: (picker) => picker.locator('.fi-dtp-view').nth(1).click() },
    { file: 'layout', demo: 'twelve-hour', viewport: true },
];

const modes = ['light', 'dark'];
const padding = 25;
// Crops get a tall viewport so a panel never runs off the bottom; the full-page shot stays short.
const viewport = { width: 1280, height: 1400 };
const layoutViewport = { width: 1280, height: 860 };

async function main() {
    await mkdir(outDir, { recursive: true });

    const browser = await browserWithSession();

    for (const target of targets) {
        for (const mode of modes) {
            const page = await browser.newPage();
            await page.setViewportSize(target.viewport ? layoutViewport : viewport);

            // Filament follows the system scheme by default, so emulating it themes the panel from first paint.
            await page.emulateMedia({ colorScheme: mode });
            await page.addInitScript(() => localStorage.setItem('theme', 'system'));
            await page.goto(`${baseUrl}${page_path}`);
            await page.waitForLoadState('networkidle');
            await page.addStyleTag({ content: 'html { scrollbar-width: none; } ::-webkit-scrollbar { display: none; }' });

            const section = page.locator(`[data-demo="${target.demo}"]`);
            const picker = section.locator('.fi-dtp');

            await picker.locator('.fi-dtp-trigger').click();
            await picker.locator('.fi-dtp-panel').waitFor({ state: 'visible' });

            if (target.then) {
                await target.then(picker);
            }

            await page.waitForTimeout(400);

            const outPath = resolve(outDir, `${target.file}_${mode}.png`);

            if (target.viewport) {
                await page.screenshot({ path: outPath });
            } else {
                const a = await section.boundingBox();
                const b = await picker.locator('.fi-dtp-panel').boundingBox();
                const x = Math.min(a.x, b.x) - padding;
                const y = Math.min(a.y, b.y) - padding;

                await page.screenshot({
                    path: outPath,
                    clip: {
                        x: Math.max(0, x),
                        y: Math.max(0, y),
                        width: Math.max(a.x + a.width, b.x + b.width) + padding - x,
                        height: Math.max(a.y + a.height, b.y + b.height) + padding - y,
                    },
                });
            }

            console.log(`saved ${outPath}`);
            await page.close();
        }
    }

    await browser.close();
}

// Signs in once through the workbench's local-only route and shares the session across pages.
async function browserWithSession() {
    const browser = await chromium.launch();
    const context = await browser.newContext({ viewport, deviceScaleFactor: 2 });
    const page = await context.newPage();

    await page.goto(`${baseUrl}/dev-login/${encodeURIComponent('salman@example.com')}`);
    await page.waitForURL((url) => url.toString().includes('/admin'));
    await page.close();

    return { newPage: () => context.newPage(), close: () => browser.close() };
}

main().catch((err) => {
    console.error(err);
    process.exit(1);
});
