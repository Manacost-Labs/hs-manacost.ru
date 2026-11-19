import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { chromium } from 'playwright';

// Synthetic Newspaper cover and reader API only; never use a live site.
const root = new URL('../../', import.meta.url).pathname;
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const favoritePhp = "define('ABSPATH','/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function hs_reader_favorite_article($id){return ['allowed'=>true,'path'=>'/article/'];} require $argv[1]; echo hs_reader_article_favorite_shell(7);";
const favoriteShell = execFileSync('php', ['-r', favoritePhp, `${plugin}article-favorite.php`], { encoding: 'utf8' });
const assets = new Map([
  ['/bootstrap.js', ['text/javascript', readFileSync(`${plugin}bootstrap.js`)]],
  ['/article-favorite.js', ['text/javascript', readFileSync(`${plugin}article-favorite.js`)]],
  ['/article-favorite.css', ['text/css', readFileSync(`${plugin}article-favorite.css`)]],
  ['/ui.css', ['text/css', readFileSync(`${plugin}ui.css`)]],
]);
const csrf = 'c'.repeat(43);
let signedIn = false;
let saved = false;
const writes = [];
const cover = `<header class="td-post-title" style="padding:24px;background:#1b1020;color:#fff;font:11px/11px sans-serif">
<h1 class="entry-title">Лучший первый ход на Полях сражений</h1>
<div class="td-module-meta-info"><span class="td-post-date" style="float:left">Ноя 17, 2026</span>
<div class="td-post-views" style="display:inline-block;margin-left:22px;vertical-align:top;line-height:17px">28807</div></div></header>`;
const server = createServer(async (req, res) => {
  const asset = assets.get(req.url);
  if (asset) { res.writeHead(200, { 'content-type': asset[0] }); return res.end(asset[1]); }
  if (req.url === '/article/') {
    res.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
    return res.end(`<!doctype html><meta charset="utf-8"><meta name=viewport content="width=device-width"><link rel=stylesheet href=/ui.css><link rel=stylesheet href=/article-favorite.css><body style="margin:0">${cover}<div class="td-post-content">${favoriteShell}<p data-article-first>Текст статьи.</p></div><script defer src=/bootstrap.js></script><script defer src=/article-favorite.js></script>`);
  }
  if (req.url.startsWith('/reader-auth/start?')) { res.writeHead(200, { 'content-type': 'text/html; charset=utf-8' }); return res.end('<!doctype html><title>Вход</title>'); }
  if (req.url === '/reader-api/v1/bootstrap?postId=7') {
    if (!signedIn) { res.writeHead(401, { 'content-type': 'application/json' }); return res.end('{"error":"unauthenticated"}'); }
    res.writeHead(200, { 'content-type': 'application/json' });
    return res.end(JSON.stringify({ profile: { id: 'r1', displayName: 'Я' }, csrfToken: csrf, favorite: { postId: 7, saved } }));
  }
  if (req.url === '/reader-api/v1/favorites/7' && ['PUT', 'DELETE'].includes(req.method)) {
    for await (const _chunk of req) { /* drain */ }
    if (req.headers['x-reader-csrf'] !== csrf) { res.writeHead(403); return res.end(); }
    saved = req.method === 'PUT'; writes.push(req.method);
    res.writeHead(200, { 'content-type': 'application/json' });
    return res.end(JSON.stringify({ postId: 7, saved }));
  }
  res.writeHead(404); res.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
let browser;
try {
  browser = await chromium.launch({ headless: true,
    ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const page = await browser.newPage();
  const toggle = page.locator('[data-favorite-toggle]');
  for (const width of [1440, 320]) {
    await page.setViewportSize({ width, height: 700 });
    signedIn = false; saved = false; writes.length = 0;
    await page.goto(`${origin}/article/`, { waitUntil: 'load' });
    await page.waitForFunction(() => document.querySelector('[data-mc-article-favorite]')?.classList.contains('mc-article-favorite--meta'));
    const placement = await page.evaluate(() => {
      const shell = document.querySelector('[data-mc-article-favorite]');
      const button = shell.querySelector('button').getBoundingClientRect();
      const views = document.querySelector('.td-post-views').getBoundingClientRect();
      return { parent: shell.parentElement.className, centerDelta: Math.abs((button.top + button.height / 2) - (views.top + views.height / 2)),
        rowHeight: document.querySelector('.td-module-meta-info').getBoundingClientRect().height, overflow: document.documentElement.scrollWidth - innerWidth,
        statusText: shell.querySelector('[data-favorite-status]').textContent };
    });
    assert.equal(placement.parent, 'td-module-meta-info', 'the save action joins the cover meta row');
    assert.ok(placement.centerDelta <= 2, `the save chip is centred on the view counter: ${JSON.stringify(placement)}`);
    assert.ok(placement.rowHeight <= 18, `the chip must not grow the cover meta row: ${JSON.stringify(placement)}`);
    assert.equal(placement.overflow, 0, `no horizontal overflow at ${width}px`);
    assert.equal(placement.statusText, '', 'guests are not nagged before they press the button');
    assert.equal(await page.getByRole('button', { name: 'Сохранить', exact: true }).isVisible(), true);
    assert.equal(await page.locator('[data-favorite-login]').isVisible(), false, 'the cover keeps one compact control for guests');

    await toggle.click();
    await page.waitForURL(url => url.pathname === '/reader-auth/start' && url.searchParams.get('returnTo') === '/article/');
    signedIn = true;
    await page.goto(`${origin}/article/`, { waitUntil: 'load' });
    await page.waitForFunction(() => document.querySelector('[data-favorite-toggle]')?.getAttribute('aria-pressed') === 'true');
    assert.deepEqual(writes, ['PUT'], 'the article is saved once after returning from login');
    assert.equal(await toggle.textContent(), 'Сохранено');
    assert.equal(await page.locator('[data-favorite-status]').textContent(), 'Статья сохранена в избранное.');
    assert.equal(await page.evaluate(() => sessionStorage.getItem('mc-article-favorite-pending')), null, 'the login intent is one-shot');

    await page.reload({ waitUntil: 'load' });
    await page.waitForFunction(() => document.querySelector('[data-favorite-toggle]')?.getAttribute('aria-pressed') === 'true');
    assert.deepEqual(writes, ['PUT'], 'a reload never repeats the saved intent');
    await toggle.click();
    await page.waitForFunction(() => document.querySelector('[data-favorite-toggle]')?.getAttribute('aria-pressed') === 'false' && !document.querySelector('[data-favorite-toggle]').disabled);
    assert.deepEqual(writes, ['PUT', 'DELETE']);
    assert.equal(await toggle.textContent(), 'Сохранить');
    await page.evaluate(() => document.activeElement?.blur());
    for (let step = 0; step < 4 && !await toggle.evaluate(element => document.activeElement === element); step++) await page.keyboard.press('Tab');
    const focus = await toggle.evaluate(element => ({ active: document.activeElement === element, activeTag: document.activeElement?.outerHTML.slice(0, 80), outline: getComputedStyle(element).outlineStyle, width: getComputedStyle(element).outlineWidth }));
    assert.ok(focus.active && focus.outline !== 'none', `keyboard focus stays visible on the cover: ${JSON.stringify(focus)}`);
  }
  console.log('Reader favorite cover regression: meta placement, guest login intent, one-shot auto-save and toggle: PASS');
} finally {
  await browser?.close();
  server.close();
}
