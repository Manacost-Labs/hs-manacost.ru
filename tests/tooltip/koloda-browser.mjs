import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { spawnSync } from 'node:child_process';
import { chromium } from 'playwright';
import { expect } from '@playwright/test';

const root = new URL('../../', import.meta.url);
const cards = JSON.parse(readFileSync(new URL('../fixtures/tooltip-koloda-cards.json', import.meta.url)));
const fixture = new URL('../fixtures/tooltip-dictionary.php', import.meta.url).pathname;
const script = readFileSync(new URL('../../wordpress/plugins/hs-tooltip/assets/hs-tooltip-v115.js', import.meta.url));
const css = readFileSync(new URL('../../wordpress/plugins/hs-tooltip/assets/hs-tooltip.css', import.meta.url));
let html;
const server = createServer((request, response) => {
  const query = new URL(request.url, 'http://127.0.0.1').searchParams;
  if (query.has('hs_tooltip_img')) {
    // Exercise the real PHP proxy; only its outbound HTTP/cache boundary is fake.
    const result = spawnSync('php', [fixture, 'proxy', query.get('hs_tooltip_img')], { cwd: root, timeout: 20000 });
    response.writeHead(result.status === 0 ? 200 : 502, { 'Content-Type': 'image/png' });
    response.end(result.stdout);
  } else if (request.url === '/tooltip.js') {
    response.writeHead(200, { 'Content-Type': 'text/javascript' }); response.end(script);
  } else if (request.url === '/tooltip.css') {
    response.writeHead(200, { 'Content-Type': 'text/css' }); response.end(css);
  } else {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' }); response.end(html);
  }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const url = `http://127.0.0.1:${server.address().port}`;
// The real rebuild/search/shortcode fixture renders these labels and proxy URLs.
const rendered = spawnSync('php', [fixture, 'render'], {
  cwd: root, env: { ...process.env, HS_TOOLTIP_TEST_HOME: url }, encoding: 'utf8', timeout: 20000,
});
assert.equal(rendered.status, 0, rendered.stderr);
html = `<!doctype html><html lang="ru"><head><meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/tooltip.css"></head><body><h1>Карты Hearthstone</h1>
  ${rendered.stdout}<script src="/tooltip.js"></script></body></html>`;
const browser = await chromium.launch({
  headless: true,
  ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}),
});
try {
  for (const width of [320, 390, 768, 1024, 1440]) {
    const context = await browser.newContext({ viewport: { width, height: 852 }, hasTouch: width < 768 });
    const page = await context.newPage();
    const failures = [];
    const imageRequests = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('request', request => { if (request.url().includes('hs_tooltip_img=')) imageRequests.push(request.url()); });
    await page.goto(url);
    assert.equal(imageRequests.length, 0, 'No image fetch before interaction');
    for (const card of cards) {
      const label = page.getByText(card.name.ru, { exact: true });
      await expect(label).toHaveAttribute('data-image-raw', card.images.card);
      if (width < 768) await label.tap();
      else await label.hover();
      const image = page.getByAltText('Hearthstone card');
      await expect(image).toBeVisible();
      await expect(image).toHaveAttribute('src', `${url}/?hs_tooltip_img=${encodeURIComponent(card.images.card)}`);
      await expect.poll(() => image.evaluate(element => element.naturalWidth)).toBeGreaterThan(0);
    }
    assert.equal(imageRequests.length, cards.length, 'Each card loads once through the PHP proxy');
    assert.deepEqual(failures, [], 'No browser errors');
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    console.log(`${width}px: four Koloda cards and ${width < 768 ? 'tap' : 'hover'} PASS`);
    await context.close();
  }
} finally {
  await browser.close();
  await new Promise(resolve => server.close(resolve));
}
