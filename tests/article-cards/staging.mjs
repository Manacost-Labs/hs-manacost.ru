import { insertComponents, verifyComponents } from './components.mjs';
import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';
import { finishAdminLogin } from '../../ops/performance/browser-admin-login.mjs';
import { navigateWithNetworkRecovery } from '../../ops/performance/browser-fixture-request.mjs';

const base = process.env.WP_TEST_BASE_URL;
if (base !== 'https://test.hs-manacost.ru') throw new Error('Article cards smoke is restricted to protected staging');
for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
  if (!process.env[key]) throw new Error('Protected staging credentials are required');
}
const marker = `Article cards staging probe ${randomUUID()}`;
const report = { ok: false, sources: [], saved: false, preview: false, cleaned: false, stage: 'login', navigation_retries: 0 };
const browser = await chromium.launch({
  headless: true,
  executablePath: process.env.PLAYWRIGHT_EXECUTABLE_PATH || undefined,
  args: process.env.PLAYWRIGHT_HOST_RESOLVER_RULES ? [`--host-resolver-rules=${process.env.PLAYWRIGHT_HOST_RESOLVER_RULES}`] : [],
});
const context = await browser.newContext({
  viewport: { width: 1440, height: 1000 }, locale: 'ru-RU',
  httpCredentials: { origin: base, username: process.env.STAGING_HTTP_USER, password: process.env.STAGING_HTTP_PASSWORD },
});
const page = await context.newPage();
const apiPage = await context.newPage();
const navigationFailures = [];
page.on('requestfailed', request => {
  const code = request.failure()?.errorText;
  if (request.isNavigationRequest() && /^net::ERR_[A-Z_]+$/.test(code ?? '') && navigationFailures.length < 4) {
    navigationFailures.push({ path: new URL(request.url()).pathname, code });
  }
});
let nonce;
let fixture;
async function navigate(target, url) {
  const { response, retries } = await navigateWithNetworkRecovery(target, url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  report.navigation_retries += retries;
  return response;
}
async function api(route, method = 'GET', body) {
  return apiPage.evaluate(async ({ route, method, body, nonce }) => {
    const response = await fetch(`/wp-json/wp/v2/${route}`, {
      method, headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' },
      ...(body ? { body: JSON.stringify(body) } : {}),
      signal: AbortSignal.timeout(30000), redirect: 'error', cache: 'no-store',
    });
    if (!response.ok) throw new Error('Staging fixture request failed');
    return response.json();
  }, { route, method, body, nonce });
}
async function editorReady() {
  await page.locator('#content-tmce').click();
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
}
async function resultsAfter(action) {
  await Promise.all([
    page.waitForResponse(response => response.url().includes('/admin-ajax.php')
      && response.request().postData()?.includes('action=hs_article_cards')
      && response.request().postData()?.includes('mode=posts')),
    action(),
  ]);
  await page.waitForFunction(() => document.querySelector('#hs-ac-results')?.getAttribute('aria-busy') === 'false');
  await page.locator('.hs-ac-choice').first().waitFor();
}
try {
  report.stage = 'login-navigation';
  const nonceURL = `${base}/wp-admin/admin-ajax.php?action=rest-nonce`;
  // Use core's local login redirect; the unrelated dashboard need not initialize.
  await navigate(page, `${base}/wp-login.php?redirect_to=${encodeURIComponent(nonceURL)}`);
  report.stage = 'login-form';
  await page.waitForFunction(() => document.activeElement?.id === 'user_login');
  await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
  await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
  report.stage = 'login-redirect';
  try {
    await page.locator('#wp-submit').click();
    await finishAdminLogin(page, base);
  } catch (error) {
    const interruptedRead = navigationFailures.some(failure => failure.code === 'net::ERR_NETWORK_CHANGED'
      && failure.path === '/wp-admin/admin-ajax.php');
    if (!interruptedRead) throw error;
    // The login POST already redirected; recover only its interrupted GET.
    report.navigation_retries++;
    await navigate(page, nonceURL);
  }
  report.stage = 'rest-nonce';
  const nonceResponse = await navigate(apiPage, nonceURL);
  nonce = (await nonceResponse.text()).trim();
  assert.match(nonce, /^[a-z0-9]{10}$/i);
  report.stage = 'create-draft';
  fixture = await api('posts', 'POST', { title: marker, content: `<p>${marker}</p>`, status: 'draft' });
  assert.ok(Number.isSafeInteger(fixture.id) && fixture.id > 0);
  report.stage = 'editor-open';
  await navigate(page, `${base}/wp-admin/post.php?post=${fixture.id}&action=edit`);
  await editorReady();
  for (const source of ['manacost', 'koloda']) {
    report.stage = `picker-${source}`;
    if (source === 'manacost') await resultsAfter(() => page.getByRole('button', { name: 'Вставить статью', exact: true }).click());
    if (await page.locator('#hs-ac-source').inputValue() !== source) {
      await resultsAfter(() => page.locator('#hs-ac-source').selectOption(source));
    }
    await page.waitForFunction(() => document.querySelectorAll('#hs-ac-category option').length > 1);
    if (source === 'koloda') assert.ok(await page.locator('.hs-ac-choice img').count(), 'Live Koloda catalog must include covers');
    const title = (await page.locator('.hs-ac-choice strong').first().innerText()).trim();
    await resultsAfter(() => page.locator('#hs-ac-search').fill(title.slice(0, 80)));
    await page.locator('.hs-ac-choice').first().click();
    await page.locator('#hs-ac-preview .hs-article-card').first().waitFor();
    await page.locator('#hs-ac-selected li').last().getByRole('button', { name: /^Убрать:/ }).click();
    const category = await page.locator('#hs-ac-category option').nth(1).getAttribute('value');
    await resultsAfter(() => page.locator('#hs-ac-search').fill(''));
    await resultsAfter(() => page.locator('#hs-ac-category').selectOption(category));
    await page.locator('.hs-ac-choice').first().click();
    report.sources.push(source);
  }
  assert.equal(await page.locator('#hs-ac-selected li').count(), 2, 'Mixed live catalogs stay selected');
  await page.locator('#hs-ac-layout').selectOption('grid');
  await page.locator('#hs-ac-setting-date').selectOption('hide');
  await page.locator('#hs-ac-setting-radius').selectOption('round');
  await page.locator('#hs-ac-setting-image').selectOption('large');
  await page.locator('#hs-ac-setting-spacing').selectOption('tight');
  await page.locator('#hs-ac-selected li').last().getByRole('button', { name: /^Выше:/ }).click();
  await page.setViewportSize({ width: 390, height: 1000 });
  assert.equal(await page.locator('#hs-article-cards-dialog').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
  await page.locator('#hs-ac-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  await resultsAfter(() => page.getByRole('button', { name: 'Вставить статью', exact: true }).click());
  await page.locator('#hs-ac-recent summary').click();
  await page.locator('.hs-ac-recent-choice').first().click();
  await page.locator('#hs-ac-reset-settings').click();
  await page.locator('#hs-ac-layout').selectOption('list');
  await page.locator('#hs-ac-setting-image').selectOption('small');
  await page.locator('#hs-ac-setting-width').selectOption('narrow');
  await page.locator('#hs-ac-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  await page.setViewportSize({ width: 1440, height: 1000 });
  await resultsAfter(() => page.getByRole('button', { name: 'Вставить статью', exact: true }).click());
  if (await page.locator('#hs-ac-source').inputValue() !== 'manacost') await resultsAfter(() => page.locator('#hs-ac-source').selectOption('manacost'));
  await page.locator('.hs-ac-choice').nth(0).click();
  await page.locator('.hs-ac-choice').nth(1).click();
  await page.locator('#hs-ac-layout').selectOption('folded');
  await page.locator('#hs-ac-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  report.layouts = ['grid', 'list', 'folded'];
  await insertComponents(page);
  report.components = ['alert', 'spoiler', 'quote', 'tabs', 'table', 'author'];
  report.stage = 'inline-image-open';
  await page.evaluate(() => { const editor = tinymce.get('content'); const p = editor.getBody().querySelector('p'); editor.selection.setCursorLocation(p.firstChild, 0); });
  await page.getByRole('button', { name: 'Картинка в строке', exact: true }).click();
  const media = page.locator('.media-modal');
  report.stage = 'inline-image-library';
  await media.getByRole('tab', { name: /Media Library|Медиафайлы/ }).click();
  await media.locator('.attachments .attachment').first().click();
  report.stage = 'inline-image-insert';
  await media.getByRole('button', { name: 'Вставить в строку', exact: true }).click();
  await page.frameLocator('#content_ifr').locator('p > img.hs-inline-image').waitFor();
  report.stage = 'save';
  await page.evaluate(() => window.scrollTo(0, 0));
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#save-post:not(.disabled)').click(),
  ]);
  await editorReady();
  const stored = await api(`posts/${fixture.id}?context=edit`);
  assert.equal(stored.status, 'draft');
  assert.equal((stored.content.raw.match(/class="hs-article-card hs-article-card--/g) || []).length, 5);
  assert.ok(stored.content.raw.includes(marker));
  report.saved = true;
  report.stage = 'preview';
  const preview = await context.newPage();
  await navigate(preview, `${base}/?p=${fixture.id}&preview=true`);
  assert.equal(await preview.locator('.hs-article-card').count(), 5);
  for (const card of await preview.locator('.hs-article-card').all()) {
    assert.equal(await card.locator('a').getAttribute('target'), '_blank');
    assert.match(await card.locator('a').getAttribute('rel'), /noopener/);
    if (await card.locator('.hs-article-card__date').count()) assert.ok((await card.locator('.hs-article-card__date').innerText()).trim());
    if (!(await card.getAttribute('class')).includes('hs-article-card--folded')) assert.equal(await card.evaluate(el => getComputedStyle(el).backgroundColor), 'rgba(0, 0, 0, 0)');
  }
  assert.equal(await preview.locator('.hs-article-cards--grid .hs-article-card').count(), 2);
  assert.equal(await preview.locator('.hs-article-cards--grid .hs-article-card__date').count(), 0);
  assert.equal(await preview.locator('.hs-article-cards--list .hs-article-card__date').count(), 1);
  for (const width of [390, 1440]) {
    await preview.setViewportSize({ width, height: 1000 });
    assert.equal(await preview.locator('.hs-article-cards--grid').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
  }
  await verifyComponents(preview);
  const folded = preview.locator('.hs-article-cards--folded');
  await folded.getByRole('button', { name: 'Следующая статья' }).click();
  assert.equal(await folded.locator('.hs-article-card').nth(1).getAttribute('data-front'), 'true');
  assert.equal(await preview.locator('img.hs-inline-image').evaluate(el => Math.abs(el.getBoundingClientRect().height - parseFloat(getComputedStyle(el).fontSize)) < 1), true);
  report.inline_image = true;
  report.preview = true;
  report.ok = true;
} catch (error) {
  let message = String(error?.message ?? 'Unknown failure').split('\n')[0];
  for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
    message = message.replaceAll(process.env[key], '[redacted]');
  }
  report.failure = message.replace(/https?:\/\/\S+/g, '[url]').slice(0, 240);
  report.final_path = new URL(page.url()).pathname;
  report.navigation_failures = navigationFailures;
  report.ok = false;
} finally {
  if (fixture?.id && nonce) {
    try {
      const owner = await api('users/me');
      const stored = await api(`posts/${fixture.id}?context=edit`);
      if (stored.author === owner.id && stored.title.raw === marker && stored.status === 'draft'
          && stored.content.raw.includes(marker)) {
        const trashed = await api(`posts/${fixture.id}`, 'DELETE');
        report.cleaned = trashed.status === 'trash';
      }
    } catch { report.cleaned = false; }
  } else report.cleaned = true;
  report.ok &&= report.cleaned;
  await browser.close();
  await mkdir('.artifacts/article-cards', { recursive: true });
  await writeFile('.artifacts/article-cards/staging-report.json', JSON.stringify(report, null, 2));
  process.stdout.write(`${JSON.stringify(report)}\n`);
  if (!report.ok) process.exitCode = 1;
}
