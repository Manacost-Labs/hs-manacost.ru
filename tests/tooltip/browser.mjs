import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { chromium } from 'playwright';
import { expect } from '@playwright/test';

const script = readFileSync(new URL('../../wordpress/plugins/hs-tooltip/assets/hs-tooltip-v115.js', import.meta.url));
const card = index => `<span class="hs-card-tooltip" data-image="/cards/${index}.svg" data-image-raw="/raw/${index}.svg">Карта ${index}</span>`;
const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="300"><rect width="200" height="300" fill="gold"/></svg>';
const server = createServer((request, response) => {
  if (request.url === '/tooltip.js') {
    response.writeHead(200, { 'Content-Type': 'text/javascript' });
    response.end(script);
  } else if (request.url.endsWith('.svg')) {
    response.writeHead(request.url === '/cards/error.svg' ? 503 : 200, {
      'Content-Type': 'image/svg+xml', 'Cache-Control': 'public, max-age=3600',
    });
    response.end(request.url === '/cards/error.svg' ? 'upstream unavailable' : svg);
  } else {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    response.end(`<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1">
      <style>.hs-card-tooltip { display:block; margin-bottom:600px; }
      .hs-tooltip-box { position:fixed; width:200px; pointer-events:none; }
      .hs-tooltip-box img { width:100%; }</style></head><body>
      <h1>Статья</h1><div style="height:3000px"></div>
      ${Array.from({ length: 30 }, (_, i) => card(i)).join('')}
      <script src="/tooltip.js"></script></body></html>`);
  }
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const url = `http://127.0.0.1:${server.address().port}`;
const browser = await chromium.launch({
  headless: true,
  ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}),
});

try {
  for (const width of [320, 390, 768, 1024, 1440]) {
    const context = await browser.newContext({ viewport: { width, height: 852 }, hasTouch: width < 768 });
    const page = await context.newPage();
    const requests = [];
    page.on('request', request => { if (request.url().includes('/cards/')) requests.push(request.url()); });
    await page.goto(url);
    assert.equal(requests.length, 0, `${width}px: no offscreen card downloads on initial load`);
    const first = page.getByText('Карта 0', { exact: true });
    await first.scrollIntoViewIfNeeded();
    await page.waitForTimeout(700);
    assert.equal(requests.length, 0, `${width}px: scrolling alone does not download tooltip images`);
    if (width < 768) await first.tap();
    else await first.hover();
    await expect.poll(() => requests.length).toBeGreaterThan(0);
    await expect(page.getByAltText('Hearthstone card')).toBeVisible();
    await expect.poll(() => page.getByAltText('Hearthstone card').evaluate(img => img.naturalWidth)).toBe(200);
    assert.equal(requests.length, 1, `${width}px: one tooltip interaction makes one image request`);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    console.log(`${width}px: deferred loading and ${width < 768 ? 'tap' : 'hover'} PASS`);
    await context.close();
  }

  // Without IntersectionObserver, hovering must still fetch the card on demand.
  const page = await browser.newPage();
  await page.addInitScript(() => { window.IntersectionObserver = undefined; });
  await page.goto(url);
  await page.getByText('Карта 0', { exact: true }).hover();
  await expect.poll(() => page.getByAltText('Hearthstone card').evaluate(img => img.naturalWidth)).toBe(200);
  // Newly inserted card + failed proxy retain the existing raw-image fallback.
  await page.evaluate(() => {
    const target = document.createElement('span');
    target.className = 'hs-card-tooltip';
    target.dataset.image = '/cards/error.svg';
    target.dataset.imageRaw = '/raw/error.svg';
    target.textContent = 'Карта с fallback';
    document.body.append(target);
  });
  await page.getByText('Карта с fallback', { exact: true }).hover();
  await expect(page.getByAltText('Hearthstone card')).toHaveAttribute('src', '/raw/error.svg');
  await expect.poll(() => page.getByAltText('Hearthstone card').evaluate(img => img.naturalWidth)).toBe(200);
  console.log('No observer, dynamic card and proxy fallback: PASS');
} finally {
  await browser.close();
  await new Promise(resolve => server.close(resolve));
}
