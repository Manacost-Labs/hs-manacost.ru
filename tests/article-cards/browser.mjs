import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const env = Object.fromEntries(readFileSync('.artifacts/integration/runtime.env', 'utf8').split('\n').filter(line => line.includes('=')).map(line => [line.slice(0, line.indexOf('=')), line.slice(line.indexOf('=') + 1)]));
const fixture = JSON.parse(readFileSync('.artifacts/article-cards/fixture.json', 'utf8'));
const base = `http://127.0.0.1:${env.WP_TEST_PORT}`;
const browser = await chromium.launch({ headless: true });
const report = { errors: [], widths: [], checks: [] };
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
page.on('pageerror', error => report.errors.push(error.message));
await context.route('https://kolodahearthstone.com/wp-content/uploads/card-fixture.png', route => route.fulfill({ contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="640" height="360" fill="#204873"/><path d="M220 290L320 60L420 290Z" fill="#a3dafa"/><circle cx="320" cy="160" r="40" fill="#fff"/></svg>' }));

async function login(target, user, password) {
  await target.goto(`${base}/wp-login.php`);
  await target.waitForFunction(() => document.activeElement?.id === 'user_login');
  await target.locator('#user_login').fill(user);
  await target.locator('#user_pass').fill(password);
  await target.locator('#wp-submit').click();
  await target.waitForURL(/\/wp-admin\//);
}
async function editorReady() {
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
}
async function loaded() {
  await page.waitForFunction(() => document.querySelector('#hs-ac-results')?.getAttribute('aria-busy') === 'false');
}
async function searchFor(value) {
  const response = page.waitForResponse(response => response.url().includes('admin-ajax.php') && response.request().postData()?.includes('mode=posts'));
  await page.locator('#hs-ac-search').fill(value);
  await response;
  await loaded();
}
async function source(value) {
  const response = page.waitForResponse(response => response.url().includes('admin-ajax.php') && response.request().postData()?.includes(`source=${value}`) && response.request().postData()?.includes('mode=posts'));
  await page.locator('#hs-ac-source').selectOption(value);
  await response;
  await loaded();
}
async function open() {
  await page.getByRole('button', { name: 'Вставить статью', exact: true }).click();
  await loaded();
}
async function ajax(target, values) {
  return target.evaluate(async (fields) => {
    const config = window.hsArticleCards;
    const response = await fetch(config.url, { method: 'POST', body: new URLSearchParams({ action: 'hs_article_cards', nonce: config.nonce, postId: config.postId, source: 'manacost', mode: 'posts', ...fields }) });
    return { status: response.status, body: await response.text() };
  }, values);
}

try {
  await login(page, env.WP_TEST_ADMIN_USER, env.WP_TEST_ADMIN_PASSWORD);
  await page.goto(`${base}/wp-admin/post.php?post=${fixture.draft}&action=edit`);
  await editorReady();
  const before = await page.evaluate(() => tinymce.get('content').getContent());
  await open();
  assert.equal(await page.locator('#hs-ac-insert').isDisabled(), true);
  await searchFor('НетТакойСтатьи');
  await page.getByText('Ничего не найдено. Измените запрос или выберите другую категорию.', { exact: true }).waitFor();
  await searchFor('карточек');
  await page.locator('#hs-ac-category').selectOption(String(fixture.category_id));
  await page.locator('.hs-ac-choice').first().waitFor();
  assert.equal(await page.locator('.hs-ac-choice').count(), 1);
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  assert.equal(await page.getByRole('button', { name: 'Вставить статью', exact: true }).evaluate(el => el === document.activeElement), true);
  assert.equal(await page.evaluate(() => tinymce.get('content').getContent()), before, 'Cancel never changes editor content');
  await page.evaluate(() => { const editor = tinymce.get('content'); editor.selection.select(editor.getBody().querySelector('p')); editor.selection.collapse(false); });
  await open();
  await searchFor('');
  await source('koloda');
  await page.locator('#hs-ac-more-categories').waitFor({ state: 'visible' });
  await page.locator('#hs-ac-more-categories').click();
  await page.locator('#hs-ac-category option[value="16"]').waitFor({ state: 'attached' });
  await page.locator('#hs-ac-category').selectOption('16');
  await page.getByText('Ничего не найдено. Измените запрос или выберите другую категорию.', { exact: true }).waitFor();
  await page.locator('#hs-ac-category').selectOption('14');
  await page.locator('.hs-ac-choice').first().waitFor();
  await page.locator('#hs-ac-next').click();
  await page.getByRole('button', { name: /Колода воина:/ }).waitFor();
  await page.locator('#hs-ac-prev').click();
  await page.getByRole('button', { name: /Колода мага:/ }).click();
  await page.locator('#hs-ac-preview .hs-article-card').waitFor();
  assert.equal(await page.locator('#hs-ac-preview').innerText().then(text => text.includes('kolodahearthstone.com')), false);
  for (const width of [320, 390, 480, 768, 900, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    const bounds = await page.locator('#hs-article-cards-dialog').evaluate(el => ({ client: el.clientWidth, scroll: el.scrollWidth, left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right }));
    assert.ok(bounds.scroll <= bounds.client + 1 && bounds.left >= 0 && bounds.right <= width, `No modal overflow at ${width}`);
    report.widths.push(width);
    await page.screenshot({ path: `.artifacts/article-cards/screenshots/picker-${width}.png` });
  }
  for (let index = 0; index < 20; index++) {
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.querySelector('#hs-article-cards-dialog').contains(document.activeElement)), true, 'Native dialog traps focus');
  }
  await page.locator('#hs-ac-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  await page.waitForFunction(() => tinymce.get('content').getContent().includes('hs-article-card--compact'));
  const inserted = await page.evaluate(() => tinymce.get('content').getContent());
  assert.ok(inserted.indexOf('Первый абзац') < inserted.indexOf('hs-article-card--compact') && inserted.indexOf('hs-article-card--compact') < inserted.indexOf('Последний абзац'), 'Inserts at the saved cursor position');
  await page.locator('#content-html').click();
  assert.match(await page.locator('#content').inputValue(), /hs-article-card--compact/);
  await page.locator('#content-tmce').click();
  await open();
  await source('manacost');
  await searchFor('карточек');
  await page.locator('.hs-ac-choice').first().click();
  await page.locator('#hs-ac-layout').selectOption('cover');
  assert.equal(await page.locator('#hs-ac-preview .hs-article-card__placeholder').count(), 1, 'Missing-cover preview matches frontend placeholder');
  await page.locator('#hs-ac-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-article-cards-dialog').open);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#save-post').click()]);
  await editorReady();
  const saved = await page.evaluate(() => tinymce.get('content').getContent());
  assert.match(saved, /hs-article-card--cover/);
  assert.match(saved, /Первый абзац/);
  assert.match(saved, /Последний абзац/);
  assert.equal((saved.match(/class="hs-article-card /g) || []).length, 2);
  // Native preview of the saved draft must render both cards.
  const previewPage = await context.newPage();
  await previewPage.goto(`${base}/?p=${fixture.draft}&preview=true`);
  assert.equal(await previewPage.locator('.hs-article-card').count(), 2);
  await previewPage.close();
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#publish').click()]);
  await editorReady();
  const reader = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const publicPage = await reader.newPage();
  await publicPage.route('https://kolodahearthstone.com/wp-content/uploads/card-fixture.png', route => route.abort());
  await publicPage.goto(`${base}/?p=${fixture.draft}`);
  assert.equal(await publicPage.locator('.hs-article-card').count(), 2);
  await reader.route('https://kolodahearthstone.com/guide-*', route => route.fulfill({ contentType: 'text/html', body: '<title>Article target fixture</title>' }));
  for (const selector of ['.hs-article-card__image', '.hs-article-card__title']) {
    const opened = publicPage.waitForEvent('popup');
    await publicPage.locator(selector).first().click();
    const target = await opened;
    await target.waitForLoadState();
    assert.match(target.url(), /kolodahearthstone\.com\/guide-101\//);
    await target.close();
  }
  for (const card of await publicPage.locator('.hs-article-card__link').all()) {
    assert.equal(await card.getAttribute('target'), '_blank');
    assert.match(await card.getAttribute('rel'), /noopener/);
    assert.equal(await card.evaluate(el => getComputedStyle(el).backgroundColor), 'rgba(0, 0, 0, 0)');
  }
  await publicPage.locator('.hs-article-card__link').first().focus();
  await publicPage.keyboard.press('Tab');
  await publicPage.keyboard.press('Shift+Tab');
  assert.notEqual(await publicPage.locator('.hs-article-card__link').first().evaluate(el => getComputedStyle(el).outlineStyle), 'none');
  for (const width of [320, 390, 768, 1024, 1440]) {
    await publicPage.setViewportSize({ width, height: 1000 });
    for (const card of await publicPage.locator('.hs-article-card').all()) {
      assert.equal(await card.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true, `Card fits ${width}`);
    }
  }
  await publicPage.setViewportSize({ width: 720, height: 500 });
  await publicPage.evaluate(() => { document.body.style.zoom = '2'; });
  assert.equal(await publicPage.locator('.hs-article-card').first().evaluate(el => el.scrollWidth <= el.clientWidth + 1), true, '200% zoom preserves card');
  await reader.close();
  assert.equal((await ajax(page, { nonce: 'invalid' })).status, 403);
  assert.equal((await ajax(page, { source: 'https://localhost/' })).status, 400);
  assert.equal((await ajax(page, { page: '-1' })).status, 400);
  await open();
  await source('koloda');
  await searchFor('__fail__');
  await page.locator('#hs-ac-retry').waitFor({ state: 'visible' });
  assert.equal(await page.locator('#hs-ac-insert').isDisabled(), true);
  await source('manacost');
  await searchFor('карточек');
  await page.locator('.hs-ac-choice').first().waitFor();
  await page.keyboard.press('Escape');
  const blockPage = await context.newPage();
  let blockPhase = 'baseline';
  const blockErrors = { baseline: [], withPlugin: [] };
  blockPage.on('pageerror', error => blockErrors[blockPhase].push({ message: error.message, stack: error.stack?.slice(0, 1000) }));
  await blockPage.goto(`${base}/wp-admin/post.php?post=${fixture.block_post}&action=edit&hs_cards_baseline=1`);
  await blockPage.waitForFunction(() => window.wp?.data?.select('core/editor')?.getCurrentPostId());
  assert.equal(await blockPage.locator('.hs-article-cards-open').count(), 0, 'Baseline excludes the new editor integration');
  blockPhase = 'withPlugin';
  await blockPage.goto(`${base}/wp-admin/post.php?post=${fixture.block_post}&action=edit`);
  await blockPage.waitForFunction(() => window.wp?.data?.select('core/editor')?.getCurrentPostId());
  const welcome = blockPage.getByRole('button', { name: 'Close', exact: true });
  if (await welcome.isVisible()) await welcome.click();
  await blockPage.locator('.hs-article-cards-open').click();
  await blockPage.locator('.hs-ac-choice').first().waitFor();
  await blockPage.locator('.hs-ac-choice').first().click();
  await blockPage.locator('#hs-ac-insert').click();
  await blockPage.waitForFunction(() => wp.data.select('core/block-editor').getBlocks().some(block => block.name === 'core/html' && block.attributes.content.includes('hs-article-card')));
  await blockPage.evaluate(() => wp.data.dispatch('core/editor').savePost());
  await blockPage.reload();
  await blockPage.waitForFunction(() => wp.data.select('core/block-editor').getBlocks().some(block => block.name === 'core/html' && block.attributes.content.includes('hs-article-card')));
  const errorOrigin = error => error.stack?.replace(/&hs_cards_baseline=1/g, '').replace(/:\d+:\d+/g, ':line:column');
  assert.ok(blockErrors.withPlugin.every(error => blockErrors.baseline.some(original => original.message === error.message && errorOrigin(original) === errorOrigin(error))), 'The picker introduces no block-editor errors');
  report.blockEditorErrors = blockErrors;
  await blockPage.close();
  report.checks.push('block-editor-insert-save-reload');
  for (const [role, user, password, postId] of [
    ['author', 'cards-author', process.env.WP_CARDS_AUTHOR_PASSWORD, fixture.author_post],
    ['subscriber', 'cards-subscriber', process.env.WP_CARDS_SUBSCRIBER_PASSWORD, fixture.draft],
  ]) {
    const roleContext = await browser.newContext();
    const rolePage = await roleContext.newPage();
    await login(rolePage, user, password);
    await rolePage.goto(`${base}/wp-admin/post.php?post=${postId}&action=edit`);
    if (role === 'author') {
      await rolePage.getByRole('button', { name: 'Вставить статью', exact: true }).waitFor();
      assert.equal((await ajax(rolePage, {})).status, 200);
      assert.equal((await ajax(rolePage, { postId: fixture.draft })).status, 403);
    } else {
      assert.equal(await rolePage.locator('.hs-article-cards-open').count(), 0);
    }
    await roleContext.close();
  }
  report.checks.push('local-search-category', 'remote-search-category-pagination', 'category-pagination', 'keyboard-escape-focus', 'visual-text-roundtrip', 'save-draft-preview-publish', 'public-render', 'missing-image', '200%-zoom', 'nonce-permission-validation', 'provider-failure-recovery');
  assert.deepEqual(report.errors, []);
  console.log('Article cards browser: PASS');
} finally {
  await page.screenshot({ path: '.artifacts/article-cards/screenshots/last-state.png' }).catch(() => {});
  writeFileSync('.artifacts/article-cards/browser-report.json', JSON.stringify(report, null, 2));
  await browser.close();
}
