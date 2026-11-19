import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';
import { finishAdminLogin } from '../../ops/performance/browser-admin-login.mjs';
import { navigateWithNetworkRecovery } from '../../ops/performance/browser-fixture-request.mjs';

// Staging smoke for «Изменения карт»: editor button, search by Russian name, a 2-per-row block in a disposable draft,
// its rendering and the lightbox. Staging only; the draft is removed even when a step fails.
const base = process.env.WP_TEST_BASE_URL;
if (base !== 'https://test.hs-manacost.ru') throw new Error('Card changes smoke is restricted to protected staging');
for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
  if (!process.env[key]) throw new Error('Protected staging credentials are required');
}
// "required": the card must be found by its Russian name (main, after the archive search is deployed).
// "optional": a branch run against staging without that deployment may add it by identifier instead.
const nameSearch = process.env.CARD_CHANGES_NAME_SEARCH === 'optional' ? 'optional' : 'required';
const NAME = 'Жертвенный страж гнева';
const marker = `Card changes staging probe ${randomUUID()}`;
const report = { ok: false, stage: 'login', name_search: nameSearch, search_found: null, saved: false, rendered: false, lightbox: false, cleaned: false, navigation_retries: 0, plugin_errors: [] };
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
page.on('pageerror', error => { if (/hs-card-changes/.test(error.stack || '')) report.plugin_errors.push(String(error.message).slice(0, 200)); });
let nonce;
let fixture;
let createSent = false;
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
try {
  const nonceURL = `${base}/wp-admin/admin-ajax.php?action=rest-nonce`;
  // The runner's network occasionally flaps (net::ERR_NETWORK_CHANGED) and can interrupt the login POST itself, so the
  // whole sign-in is retried (at most three times) until the REST nonce proves a session; a wrong password still fails.
  for (let attempt = 1; attempt <= 3 && !/^[a-z0-9]{10}$/i.test(nonce ?? ''); attempt++) {
    report.stage = 'login-navigation';
    report.login_attempts = attempt;
    try {
      await navigate(page, `${base}/wp-login.php?redirect_to=${encodeURIComponent(nonceURL)}`);
      await page.waitForFunction(() => document.activeElement?.id === 'user_login');
      await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
      await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
      report.stage = 'login-redirect';
      await page.locator('#wp-submit').click();
      await finishAdminLogin(page, base);
      report.stage = 'rest-nonce';
      nonce = (await (await navigate(apiPage, nonceURL)).text()).trim();
    } catch (error) {
      if (!navigationFailures.some(failure => failure.code === 'net::ERR_NETWORK_CHANGED') || attempt === 3) throw error;
    }
    if (!/^[a-z0-9]{10}$/i.test(nonce ?? '')) report.navigation_retries++;
  }
  assert.match(nonce ?? '', /^[a-z0-9]{10}$/i, 'Signed in');
  report.stage = 'create-draft';
  createSent = true;
  fixture = await api('posts', 'POST', { title: marker, content: `<p>${marker}</p>`, status: 'draft' });
  assert.ok(Number.isSafeInteger(fixture.id) && fixture.id > 0);

  report.stage = 'editor-open';
  await navigate(page, `${base}/wp-admin/post.php?post=${fixture.id}&action=edit`);
  await editorReady();
  assert.equal(await page.evaluate(() => typeof window.hsCardChanges), 'object', 'The plugin loads its editor configuration');
  const opener = page.getByRole('button', { name: 'Изменения карт', exact: true });
  assert.equal(await opener.count(), 1, 'The «Изменения карт» button is present');
  await page.evaluate(() => { const editor = tinymce.get('content'); editor.selection.select(editor.getBody().querySelector('p')); editor.selection.collapse(false); });
  report.stage = 'dialog';
  await opener.click();
  await page.locator('#hs-card-changes-dialog[open]').waitFor();
  await page.locator('#hs-cc-date').fill('2026-11-03T15:55');

  report.stage = 'search';
  await page.locator('#hs-cc-search').fill(NAME);
  const wanted = page.locator('.hs-ccd-result', { hasText: 'BG36_362' });
  report.search_found = await wanted.first().waitFor({ timeout: 30000 }).then(() => true, () => false);
  if (report.search_found) {
    assert.match(await wanted.first().innerText(), /Поля сражений/);
    await wanted.first().click();
  } else {
    assert.equal(nameSearch, 'optional', 'BG36_362 must be found by its Russian name');
    await page.locator('#hs-cc-id').fill('BG36_362');
    await page.getByRole('button', { name: 'Добавить по идентификатору', exact: true }).click();
  }
  await page.locator('#hs-cc-search').fill('Матриарх');
  const matriarch = page.locator('.hs-ccd-result', { hasText: 'BE_036' });
  await matriarch.first().waitFor({ timeout: 30000 });
  await matriarch.first().click();
  await page.waitForFunction(() => document.querySelectorAll('#hs-cc-items .hs-ccd-row').length === 2 && !document.querySelector('#hs-cc-items .hs-ccd-state--loading'));
  const states = await page.locator('.hs-ccd-state').allInnerTexts();
  assert.ok(states.every(text => text.startsWith('До — ')), `Both cards resolved a before/after pair: ${states.join(' | ')}`);
  await page.locator('#hs-cc-columns').selectOption('2');
  await page.waitForFunction(() => document.querySelectorAll('#hs-cc-preview .hs-cc__item').length === 2 && !document.querySelector('#hs-cc-insert').disabled);
  report.stage = 'insert';
  await page.locator('#hs-cc-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-card-changes-dialog').open);
  const inserted = await page.evaluate(() => tinymce.get('content').getContent({ format: 'text' }));
  assert.match(inserted, /\[hs_card_changes columns="2"/);
  assert.equal((inserted.match(/\[hs_card_change /g) || []).length, 2);

  report.stage = 'save';
  await page.evaluate(() => window.scrollTo(0, 0));
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#save-post:not(.disabled)').click()]);
  const stored = await api(`posts/${fixture.id}?context=edit`);
  assert.equal(stored.status, 'draft');
  assert.equal((stored.content.raw.match(/\[hs_card_change /g) || []).length, 2, 'Stored content keeps both shortcodes');
  assert.ok(stored.content.raw.includes(marker));
  report.saved = true;

  report.stage = 'render';
  // The site lightbox is not loaded on drafts and previews by design, so the disposable post becomes private (never public)
  // and is viewed at its plain address by the signed-in author.
  assert.equal((await api(`posts/${fixture.id}`, 'POST', { status: 'private' })).status, 'private');
  const view = await context.newPage();
  await navigate(view, `${base}/?p=${fixture.id}`);
  await view.locator('.hs-cc .hs-cc__card').first().waitFor({ timeout: 30000 });
  assert.equal(await view.locator('.hs-cc__item').count(), 2);
  assert.equal(await view.locator('.hs-cc').first().evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 2, 'Two per row at 1440 px');
  await view.waitForFunction(() => [...document.images].filter(image => image.closest('.hs-cc')).every(image => image.complete && image.naturalWidth > 0), null, { timeout: 30000 });
  assert.equal(await view.locator('.hs-cc__card').count(), 4, 'Every change shows before and after');
  assert.equal(await view.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
  report.rendered = true;
  report.stage = 'lightbox';
  await view.locator('.hs-cc__open').first().click();
  await view.locator('dialog.hs-lightbox[open]').waitFor();
  assert.equal(await view.locator('.hs-lightbox__pair-image').count(), 2, 'The lightbox shows before and after side by side');
  await view.waitForFunction(() => [...document.querySelectorAll('.hs-lightbox__pair-image')].every(image => image.complete && image.naturalWidth > 0));
  await view.keyboard.press('Escape');
  await view.waitForFunction(() => !document.querySelector('dialog.hs-lightbox[open]'));
  report.lightbox = true;
  assert.deepEqual(report.plugin_errors, [], 'No script errors from the plugin');
  report.ok = true;
} catch (error) {
  let message = String(error?.message ?? 'Unknown failure');
  for (const key of ['WP_TEST_ADMIN_USER', 'WP_TEST_ADMIN_PASSWORD', 'STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD']) {
    message = message.replaceAll(process.env[key], '[redacted]');
  }
  report.navigation_failures = navigationFailures;
  report.failure = message.replace(/https?:\/\/\S+/g, '[url]').slice(0, 1600);
  report.final_path = new URL(page.url()).pathname;
  await mkdir('.artifacts/card-changes', { recursive: true });
  await page.screenshot({ path: '.artifacts/card-changes/staging-failure.png', timeout: 10000 }).catch(() => {});
  report.ok = false;
} finally {
  // Remove only our own disposable draft, identified by its unique marker and author, even after a failure.
  // A lost create response may still have created it, so then the draft is looked up by marker instead of by ID.
  if (createSent && nonce) {
    try {
      const owner = await api('users/me');
      const ours = post => post.author === owner.id && post.title.raw === marker && post.content.raw.includes(marker);
      const lookup = () => api(`posts?context=edit&author=${owner.id}&status=draft,private&per_page=10&search=${encodeURIComponent(marker)}`);
      let found = fixture?.id ? [await api(`posts/${fixture.id}?context=edit`)] : await lookup();
      // A create whose response was lost may still be committing: look again before declaring nothing to clean.
      for (let attempt = 0; !fixture?.id && !found.some(ours) && attempt < 3; attempt++) {
        await new Promise(resolve => setTimeout(resolve, 5000));
        found = await lookup();
      }
      let cleaned = true;
      for (const post of found.filter(ours)) cleaned = (await api(`posts/${post.id}?force=true`, 'DELETE')).deleted === true && cleaned;
      if (!fixture?.id && cleaned) {
        // One more delayed pass: a draft committed after the last lookup is deleted too, never reported clean.
        await new Promise(resolve => setTimeout(resolve, 5000));
        const late = (await lookup()).filter(ours);
        for (const post of late) cleaned = (await api(`posts/${post.id}?force=true`, 'DELETE')).deleted === true && cleaned;
        if (late.length) cleaned = cleaned && !(await lookup()).some(ours);
      }
      report.cleaned = cleaned && (fixture?.id ? found.some(ours) : true);
    } catch { report.cleaned = false; }
  } else report.cleaned = !createSent;
  report.ok &&= report.cleaned;
  await browser.close();
  await mkdir('.artifacts/card-changes', { recursive: true });
  await writeFile('.artifacts/card-changes/staging-report.json', JSON.stringify(report, null, 2));
  process.stdout.write(`${JSON.stringify(report)}\n`);
  if (!report.ok) process.exitCode = 1;
}
