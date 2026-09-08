import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Self-contained anonymous shell and synthetic API only; never use a live site.
const root = fileURLToPath(new URL('../../', import.meta.url));
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const shell = execFileSync('php', ['-r',
  "define('ABSPATH','/fixture/'); function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } function esc_html__($s,$domain='') { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } require $argv[1]; echo hs_manacost_reader_account_shell();",
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
  // The account shell owns the only page title, just as the dedicated template does.
  response.end(`<!doctype html><html lang="ru"><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/theme.css"><link rel="stylesheet" href="/reader.css"><title>Local reader test</title><main class="td-main-content-wrap mc-reader-page"><div class="td-container"><div class="td-page-content">${shell}</div></div></main><script src="/reader.js"></script></html>`);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const screenshotDir = process.env.READER_UI_SCREENSHOTS;
if (screenshotDir) mkdirSync(screenshotDir, { recursive: true });
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
  const capture = async name => {
    if (screenshotDir) await page.screenshot({ path: `${screenshotDir}/${name}.png`, fullPage: true });
  };
  const layout = () => page.evaluate(() => ({
    overflow: document.documentElement.scrollWidth - innerWidth,
    title: parseFloat(getComputedStyle(document.querySelector('.mc-reader__title')).fontSize),
    viewport: innerWidth,
    sections: [...document.querySelectorAll('[aria-labelledby]')].map(element => {
      const rect = element.getBoundingClientRect();
      return { bottom: rect.bottom, left: rect.left, right: rect.right, top: rect.top, width: rect.width };
    }),
  }));
  const assertFits = async () => {
    const result = await layout();
    assert.equal(result.overflow, 0, 'the account page must not horizontally scroll');
    assert.ok(result.title >= 28, 'the page title must retain readable hierarchy');
    assert.ok(result.sections.every(section => section.left >= 0 && section.right <= result.viewport && section.width > 0), 'each account section must fit the viewport');
    return result;
  };

  for (const width of [320, 390, 560, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertFits();
    if ([390, 1440].includes(width)) await capture(`guest-${width}`);
  }
  await capture('guest');
  assert.equal(await page.getByRole('heading', { name: 'Кабинет читателя', level: 1 }).count(), 1);
  assert.equal(await page.getByRole('heading', { name: 'Профиль', level: 2 }).count(), 1);
  assert.equal(await page.getByRole('heading', { name: 'Сохранённые статьи', level: 2 }).count(), 1);
  assert.equal(await status.getAttribute('role'), 'status');
  assert.equal(await status.getAttribute('aria-live'), 'polite');
  const guestLogin = page.getByRole('link', { name: 'Войти через HearthPulse', exact: true });
  await page.keyboard.press('Tab');
  assert.equal(await guestLogin.evaluate(element => document.activeElement === element), true);
  assert.ok(await guestLogin.evaluate(element => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return rect.height >= 44 && style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
  }), 'keyboard focus must be visible on a 44px login link');

  const luminance = rgb => rgb.match(/[\d.]+/g).slice(0, 3).map(Number)
    .map(value => value / 255).map(value => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4)
    .reduce((sum, value, index) => sum + value * [0.2126, 0.7152, 0.0722][index], 0);
  for (const selector of ['.mc-reader__intro', '.mc-reader__status', '.mc-reader__note', '.mc-reader__availability', '.mc-reader__empty', '.mc-reader__button--primary']) {
    const colors = await page.locator(selector).evaluate(element => {
      let surface = element;
      while (getComputedStyle(surface).backgroundColor === 'rgba(0, 0, 0, 0)' && surface.parentElement) surface = surface.parentElement;
      return { text: getComputedStyle(element).color, background: getComputedStyle(surface).backgroundColor };
    });
    const values = [luminance(colors.text), luminance(colors.background)].sort((a, b) => b - a);
    assert.ok((values[0] + 0.05) / (values[1] + 0.05) >= 4.5, `${selector} must meet normal-text contrast`);
  }

  profileStatus = 200;
  profile = { user: { displayName: 'Читатель Манакоста' }, csrfToken: 'synthetic-only', profileUrl: 'https://hearthpulse.net/profile/synthetic' };
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertFits();
    await capture(`authenticated-${width}`);
  }
  profile = {
    user: { displayName: `${'ОченьДлинноеИмяЧитателя'.repeat(12)}${'UnbrokenLatinIdentity'.repeat(14)}` },
    csrfToken: 'synthetic-only', profileUrl: 'https://hearthpulse.net/profile/synthetic',
  };
  for (const width of [320, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertFits();
    assert.equal(await page.locator('[data-reader-identity]').textContent(), profile.user.displayName);
  }
  const profileLink = page.getByRole('link', { name: 'Профиль HearthPulse', exact: true });
  await page.keyboard.press('Tab');
  assert.equal(await profileLink.evaluate(element => document.activeElement === element), true);
  assert.ok(await profileLink.evaluate(element => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return rect.height >= 44 && style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
  }), 'keyboard focus must be visible on the 44px profile link');
  await page.setViewportSize({ width: 1024, height: 900 });
  await ready();
  let sections = await assertFits();
  assert.ok(Math.abs(sections.sections[0].top - sections.sections[1].top) < 2, 'desktop account sections should form one coherent row');
  await page.setViewportSize({ width: 560, height: 900 });
  await ready();
  sections = await assertFits();
  assert.ok(sections.sections[1].top >= sections.sections[0].bottom, 'narrow account sections should stack without overlap');
  const identitySize = () => page.locator('[data-reader-identity]').evaluate(element => parseFloat(getComputedStyle(element).fontSize));
  const originalIdentitySize = await identitySize();
  await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
  assert.ok(await identitySize() >= originalIdentitySize * 2, '200% text enlargement must actually resize the identity');
  await assertFits();
  await capture('authenticated-long-name');

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
  await assertFits();
  await capture('error');
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
  console.log('Reader browser regression: responsive account states, semantic headings, keyboard targets, safe DTO, logout retry, deadlines, private-state clearing: PASS');
} finally {
  if (browser) await browser.close();
  await new Promise(resolve => server.close(resolve));
}
