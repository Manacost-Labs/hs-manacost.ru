import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Self-contained fixture: a cached public page whose header shows the signed-in reader.
const root = fileURLToPath(new URL('../../', import.meta.url));
const script = readFileSync(`${root}wordpress/mu-plugins/hs-manacost-reader/header-session.js`);
const navigationCss = readFileSync(`${root}wordpress/mu-plugins/manacost-site-navigation.css`);
let meStatus = 200;
let meCalls = 0;
const page = shared => `<!doctype html><html lang="ru"><head><meta charset="utf-8"><link rel="stylesheet" href="/nav.css">
${shared ? `<script>window.hsManacostReaderBootstrap = () => fetch('/reader-api/v1/bootstrap').then(async response => ({ response, data: response.ok ? await response.json() : null }));</script>` : ''}
<script defer src="/header-session.js"></script></head><body><div class="td-header-wrap"><div class="td-header-menu-wrap"><ul class="sf-menu">
<li class="menu-item"><a href="/">Главная</a></li><li class="menu-item mc-reader-entry"><a data-mc-reader-entry href="/account/">Кабинет</a></li></ul></div></div>
<nav class="td-mobile-content"><ul><li class="menu-item mc-reader-entry"><a data-mc-reader-entry href="/account/">Кабинет</a></li></ul></nav></body></html>`;
const server = createServer((request, response) => {
  if (request.url === '/header-session.js') { response.writeHead(200, { 'Content-Type': 'text/javascript' }); response.end(script); return; }
  if (request.url === '/nav.css') { response.writeHead(200, { 'Content-Type': 'text/css' }); response.end(navigationCss); return; }
  if (request.url === '/reader-api/v1/me' || request.url === '/reader-api/v1/bootstrap') {
    meCalls += 1;
    response.writeHead(meStatus, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
    response.end(JSON.stringify(meStatus === 200
      ? { user: { displayName: 'Читатель <b>Манакоста</b>' }, profile: { avatarUrl: '/reader-api/v1/profile/avatar?v=3' } }
      : { error: 'not_authenticated' }));
    return;
  }
  if (request.url.startsWith('/reader-api/v1/profile/avatar')) { response.writeHead(404); response.end(); return; }
  if (request.url === '/' || request.url === '/article') {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    response.end(page(request.url === '/article'));
    return;
  }
  response.writeHead(404); response.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
// localhost is a secure context, so the hint's Secure attribute is honoured over plain HTTP.
const origin = `http://localhost:${server.address().port}`;
const browser = await chromium.launch({ headless: true,
  ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
try {
  const context = await browser.newContext();
  const tab = await context.newPage();
  const entry = tab.locator('.sf-menu a[data-mc-reader-entry]');
  const hint = async () => (await context.cookies()).find(cookie => cookie.name === 'mc_reader_signed_in')?.value;

  // 1. A guest without the hint never calls the Reader API from an ordinary page.
  await tab.goto(`${origin}/`); await tab.waitForLoadState('load');
  assert.equal(meCalls, 0, 'guests must not create Reader traffic on every page view');
  assert.equal(await entry.textContent(), 'Кабинет');

  // 2. The first cabinet/article page after login uses the shared bootstrap and sets the hint.
  await tab.goto(`${origin}/article`);
  await tab.locator('.sf-menu .mc-reader-entry__label').waitFor();
  assert.equal(await hint(), '1');
  assert.equal(meCalls, 1, 'the header must reuse the page bootstrap instead of a second request');

  // 3. Every other page now shows the reader, escaped, in both menus.
  await tab.goto(`${origin}/`);
  await tab.locator('.sf-menu .mc-reader-entry__label').waitFor();
  assert.equal(await entry.locator('.mc-reader-entry__name').textContent(), 'Читатель <b>Манакоста</b>');
  assert.equal(await tab.locator('.td-mobile-content .mc-reader-entry__name').isVisible(), true, 'the mobile menu shows the name');
  assert.equal(await entry.locator('.mc-reader-entry__name').isVisible(), false, 'the desktop bar keeps «Кабинет» to avoid overflow');
  assert.equal(await entry.locator('.mc-reader-entry__label').isVisible(), true);
  assert.equal(await entry.getAttribute('aria-label'), 'Личный кабинет: Читатель <b>Манакоста</b>');
  assert.equal(await tab.locator('.mc-reader-entry img, .mc-reader-entry__avatar').count(), 0, 'the menu entry shows no avatar');
  assert.equal(await entry.getAttribute('href'), '/account/');
  assert.equal(await tab.evaluate(() => document.querySelector('.sf-menu b')), null, 'names are text, never HTML');

  // 4. An expired session clears the hint and leaves the plain entry.
  meStatus = 401;
  await tab.goto(`${origin}/`); await tab.waitForLoadState('load');
  await tab.waitForFunction(() => !document.cookie.includes('mc_reader_signed_in=1'));
  assert.equal(await entry.textContent(), 'Кабинет');
  const before = meCalls;
  await tab.goto(`${origin}/`); await tab.waitForLoadState('load');
  assert.equal(meCalls, before, 'after sign-out ordinary pages stop asking the Reader API');
  console.log('header-session: pass (guest silent, shared bootstrap, escaped name in both menus, expiry clears hint)');
} finally {
  await browser.close();
  server.close();
}
