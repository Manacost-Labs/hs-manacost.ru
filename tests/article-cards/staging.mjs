import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';
import { finishAdminLogin } from '../../ops/performance/browser-admin-login.mjs';

const base = process.env.WP_TEST_BASE_URL;
if (base !== 'https://test.hs-manacost.ru') throw new Error('Article cards smoke is restricted to protected staging');
for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
  if (!process.env[key]) throw new Error('Protected staging credentials are required');
}
const marker = `Article cards staging probe ${randomUUID()}`;
const report = { ok: false, sources: [], saved: false, preview: false, cleaned: false, stage: 'login' };
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
let nonce;
let fixture;
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
  await page.goto(`${base}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  report.stage = 'login-form';
  await page.waitForFunction(() => document.activeElement?.id === 'user_login');
  await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
  await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
  await page.locator('#wp-submit').click();
  report.stage = 'login-redirect';
  await finishAdminLogin(page, base);
  report.stage = 'rest-nonce';
  const nonceResponse = await apiPage.goto(`${base}/wp-admin/admin-ajax.php?action=rest-nonce`);
  nonce = (await nonceResponse.text()).trim();
  assert.match(nonce, /^[a-z0-9]{10}$/i);
  report.stage = 'create-draft';
  fixture = await api('posts', 'POST', { title: marker, content: `<p>${marker}</p>`, status: 'draft' });
  assert.ok(Number.isSafeInteger(fixture.id) && fixture.id > 0);
  report.stage = 'editor-open';
  await page.goto(`${base}/wp-admin/post.php?post=${fixture.id}&action=edit`);
  await editorReady();
  for (const source of ['manacost', 'koloda']) {
    report.stage = `picker-${source}`;
    await resultsAfter(() => page.getByRole('button', { name: 'Вставить статью', exact: true }).click());
    if (await page.locator('#hs-ac-source').inputValue() !== source) {
      await resultsAfter(() => page.locator('#hs-ac-source').selectOption(source));
    }
    await page.waitForFunction(() => document.querySelectorAll('#hs-ac-category option').length > 1);
    if (source === 'koloda') assert.ok(await page.locator('.hs-ac-choice img').count(), 'Live Koloda catalog must include covers');
    const title = (await page.locator('.hs-ac-choice strong').first().innerText()).trim();
    await resultsAfter(() => page.locator('#hs-ac-search').fill(title.slice(0, 80)));
    await page.locator('.hs-ac-choice').first().click();
    await page.locator('#hs-ac-preview .hs-article-card').waitFor();
    const category = await page.locator('#hs-ac-category option').nth(1).getAttribute('value');
    await resultsAfter(() => page.locator('#hs-ac-search').fill(''));
    await resultsAfter(() => page.locator('#hs-ac-category').selectOption(category));
    await page.locator('.hs-ac-choice').first().click();
    await page.locator('#hs-ac-layout').selectOption(source === 'koloda' ? 'cover' : 'compact');
    await page.setViewportSize({ width: 390, height: 1000 });
    assert.equal(await page.locator('#hs-article-cards-dialog').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
    await page.locator('#hs-ac-insert').click();
    await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
    await page.setViewportSize({ width: 1440, height: 1000 });
    report.sources.push(source);
  }
  report.stage = 'save';
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#save-post').click(),
  ]);
  await editorReady();
  const stored = await api(`posts/${fixture.id}?context=edit`);
  assert.equal(stored.status, 'draft');
  assert.equal((stored.content.raw.match(/class="hs-article-card hs-article-card--/g) || []).length, 2);
  assert.ok(stored.content.raw.includes(marker));
  report.saved = true;
  report.stage = 'preview';
  const preview = await context.newPage();
  await preview.goto(`${base}/?p=${fixture.id}&preview=true`);
  assert.equal(await preview.locator('.hs-article-card').count(), 2);
  for (const card of await preview.locator('.hs-article-card').all()) {
    assert.equal(await card.locator('a').getAttribute('target'), '_blank');
    assert.match(await card.locator('a').getAttribute('rel'), /noopener/);
    assert.ok((await card.locator('.hs-article-card__date').innerText()).trim());
    assert.equal(await card.evaluate(el => getComputedStyle(el).backgroundColor), 'rgba(0, 0, 0, 0)');
  }
  report.preview = true;
  report.ok = true;
} catch (error) {
  let message = String(error?.message ?? 'Unknown failure').split('\n')[0];
  for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
    message = message.replaceAll(process.env[key], '[redacted]');
  }
  report.failure = message.replace(/https?:\/\/\S+/g, '[url]').slice(0, 240);
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
