import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Self-contained anonymous shell and synthetic API only; never use a live site.
const root = fileURLToPath(new URL('../../', import.meta.url));
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const shell = execFileSync('php', ['-r',
  "define('ABSPATH','/fixture/'); function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } require $argv[1]; echo hs_manacost_reader_account_shell();",
  `${plugin}account.php`], { encoding: 'utf8' });
const assets = new Map([
  ['/reader.css', ['text/css', readFileSync(`${plugin}reader.css`)]],
  ['/reader.js', ['text/javascript', readFileSync(`${plugin}reader.js`)]],
  ['/theme.css', ['text/css', readFileSync(`${root}wordpress/themes/Newspaper_new/style.css`)]],
]);
const server = createServer((request, response) => {
  const asset = assets.get(request.url);
  if (asset) { response.writeHead(200, { 'Content-Type': asset[0] }); response.end(asset[1]); return; }
  if (request.url !== '/') { response.writeHead(404); response.end(); return; }
  response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  response.end(`<!doctype html><html lang="ru"><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/theme.css"><link rel="stylesheet" href="/reader.css"><title>Local reader test</title><main class="td-page-content"><h1>Кабинет</h1>${shell}</main><script src="/reader.js"></script></html>`);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
let browser;
try {
  browser = await chromium.launch({ headless: true,
    ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const page = await browser.newPage();
  let profileStatus = 401;
  let profile = {};
  let logoutStatus = 204;
  let logoutCalls = 0;
  let hangProfile = false;
  let hangLogout = false;
  await page.route('**/reader-api/v1/me', route => hangProfile ? undefined
    : route.fulfill({ status: profileStatus, json: profile }));
  await page.route('**/reader-auth/logout', route => {
    logoutCalls += 1;
    return hangLogout ? undefined : route.fulfill({ status: logoutStatus });
  });
  const status = page.locator('[data-reader-status]');
  const retry = page.getByRole('button', { name: 'Повторить', exact: true });
  const ready = async () => {
    await page.goto(origin, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => document.querySelector('[data-reader-status]').textContent !== 'Проверяем вход…');
  };
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    const layout = await page.evaluate(() => ({
      overflow: document.documentElement.scrollWidth - innerWidth,
      title: parseFloat(getComputedStyle(document.querySelector('.mc-reader__title')).fontSize),
      sections: [...document.querySelectorAll('.mc-reader h3')].map(element => ({
        size: getComputedStyle(element).fontSize, color: getComputedStyle(element).color,
      })),
      paragraphs: [...document.querySelectorAll('.mc-reader p')].map(element => getComputedStyle(element).color),
      target: document.querySelector('.mc-reader__button').getBoundingClientRect().height,
    }));
    assert.equal(layout.overflow, 0);
    assert.ok(layout.title >= 28);
    assert.ok(layout.target >= 44);
    assert.deepEqual(layout.sections, Array(2).fill({ size: '21px', color: 'rgb(244, 248, 250)' }));
    assert.ok(layout.paragraphs.every(color => ['rgb(109, 185, 232)', 'rgb(189, 209, 219)'].includes(color)));
  }
  profileStatus = 200;
  profile = { user: { displayName: '<img src=x onerror=alert(1)> Читатель' }, csrfToken: 'synthetic-only', profileUrl: null };
  await ready();
  assert.equal(await page.locator('[data-reader-identity]').textContent(), profile.user.displayName);
  assert.equal(await page.locator('.mc-reader img').count(), 0);
  assert.equal(await page.getByRole('link', { name: 'Профиль HearthPulse' }).count(), 0);
  logoutStatus = 503;
  await page.getByRole('button', { name: 'Выйти', exact: true }).click();
  await retry.waitFor();
  assert.equal(await page.locator('[data-reader-identity]').textContent(), '');
  logoutStatus = 204;
  await retry.click();
  await page.waitForFunction(() => document.querySelector('[data-reader-status]').textContent.includes('вышли'));
  assert.equal(logoutCalls, 2, 'retry must retry logout, not reauthenticate');
  profile = { user: { displayName: 'Missing CSRF' } };
  await ready();
  await retry.waitFor();
  profileStatus = 503;
  await ready();
  await retry.waitFor();
  hangProfile = true;
  await page.goto(origin, { waitUntil: 'domcontentloaded' });
  await retry.waitFor({ timeout: 9000 });
  assert.match(await status.textContent(), /слишком много времени/);
  hangProfile = false;
  profileStatus = 200;
  profile = { user: { displayName: 'Synthetic reader' }, csrfToken: 'synthetic-only', profileUrl: null };
  await ready();
  hangLogout = true;
  await page.getByRole('button', { name: 'Выйти', exact: true }).click();
  await retry.waitFor({ timeout: 9000 });
  assert.match(await status.textContent(), /Не удалось выйти/);
  hangLogout = false;
  await ready();
  await page.evaluate(() => window.dispatchEvent(new Event('pagehide')));
  assert.equal(await page.locator('[data-reader-identity]').textContent(), '');
  assert.equal(await page.locator('[data-reader-actions]').textContent(), '');
  console.log('Reader browser regression: Newspaper typography, responsive targets, safe DTO, logout retry, deadlines, private-state clearing: PASS');
} finally {
  if (browser) await browser.close();
  await new Promise(resolve => server.close(resolve));
}
