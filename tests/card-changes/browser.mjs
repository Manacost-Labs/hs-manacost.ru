import { chromium } from 'playwright';
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import assert from 'node:assert/strict';

const env = Object.fromEntries(readFileSync('.artifacts/integration/runtime.env', 'utf8').split('\n').filter(line => line.includes('=')).map(line => [line.slice(0, line.indexOf('=')), line.slice(line.indexOf('=') + 1)]));
const fixture = JSON.parse(readFileSync('.artifacts/card-changes/fixture.json', 'utf8'));
const base = `http://127.0.0.1:${env.WP_TEST_PORT}`;
const shots = '.artifacts/card-changes/screenshots';
const images = process.env.CARD_CHANGES_IMAGES || '';
const PATCH = '2026-11-03T15:55';
const browser = await chromium.launch({ headless: true });
const report = { errors: [], checks: [] };
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
report.console = [];
context.setDefaultTimeout(90000);
const page = await context.newPage();
page.on('pageerror', error => report.errors.push({ message: error.message, stack: error.stack?.slice(0, 600) }));
report.responses = [];
page.on('console', message => { if (message.type() === 'error' && report.console.length < 12) report.console.push(`${message.text()} ${message.location().url}`.slice(0, 300)); });
page.on('response', response => { if (response.url().includes('load-scripts') || response.status() >= 400) report.responses.push([response.status(), response.url().slice(0, 240)]); });
page.on('requestfailed', request => report.responses.push(['failed', request.url().slice(0, 240), request.failure()?.errorText]));
const placeholder = hash => `<svg xmlns="http://www.w3.org/2000/svg" width="404" height="558"><rect width="404" height="558" rx="28" fill="#${hash.slice(0, 6)}"/><text x="202" y="290" font-size="28" text-anchor="middle" fill="#fff">${hash.slice(0, 6)}</text></svg>`;
// Card art is served from the real archive in production; tests use recorded art when provided, otherwise a placeholder.
await context.route('https://api.kolodahearthstone.com/uploads/card-history-media/*.png', route => {
  const hash = new URL(route.request().url()).pathname.split('/').pop().replace('.png', '');
  const file = `${images}/${hash}.png`;
  if (images && existsSync(file)) return route.fulfill({ contentType: 'image/png', body: readFileSync(file) });
  return route.fulfill({ contentType: 'image/svg+xml', body: placeholder(hash) });
});
const check = (name, value) => { report.checks.push(name); return value; };

async function login(target, user, password) {
  await target.goto(`${base}/wp-login.php?redirect_to=${encodeURIComponent(base + '/wp-admin/')}`);
  await target.locator('#user_login').fill(user);
  await target.locator('#user_pass').fill(password);
  await target.locator('#wp-submit').click();
  await target.waitForURL(/\/wp-admin\//);
}
async function ajax(target, values) {
  return target.evaluate(async fields => {
    const config = window.hsCardChanges;
    const response = await fetch(config.url, { method: 'POST', body: new URLSearchParams({ action: 'hs_card_changes', nonce: config.nonce, postId: config.postId, ...fields }) });
    return { status: response.status, body: await response.text() };
  }, values);
}
async function rowsReady(target, count) {
  await target.waitForFunction(expected => document.querySelectorAll('#hs-cc-items .hs-ccd-row').length === expected && !document.querySelector('#hs-cc-items .hs-ccd-state--loading'), count);
}
async function previewReady(target, count) {
  await target.waitForFunction(expected => document.querySelectorAll('#hs-cc-preview .hs-cc__item').length === expected, count);
  await target.waitForFunction(() => !document.querySelector('#hs-cc-insert').disabled);
}

try {
  await login(page, env.WP_TEST_ADMIN_USER, env.WP_TEST_ADMIN_PASSWORD);

  // Classic Editor: dialog, search, pairs, plate editing, insert, publish.
  await page.goto(`${base}/wp-admin/post.php?post=${fixture.draft}&action=edit`);
  // WordPress remembers the last editor tab per user; always start in Visual.
  if (await page.locator('#content-tmce').count()) await page.locator('#content-tmce').click();
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  const untouched = await page.evaluate(() => tinymce.get('content').getContent());
  await page.getByRole('button', { name: 'Изменения карт', exact: true }).click();
  await page.locator('#hs-card-changes-dialog[open]').waitFor();
  assert.equal(await page.locator('#hs-cc-insert').isDisabled(), true, 'Nothing to insert yet');
  assert.match(await page.locator('#hs-cc-date').inputValue(), /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/, 'Date defaults to the post date');
  check('default date', true);
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => !document.querySelector('#hs-card-changes-dialog').open);
  assert.equal(await page.evaluate(() => tinymce.get('content').getContent()), untouched, 'Cancel never changes content');
  assert.equal(await page.getByRole('button', { name: 'Изменения карт', exact: true }).evaluate(el => el === document.activeElement), true, 'Focus returns to the opener');

  await page.evaluate(() => { const editor = tinymce.get('content'); editor.selection.select(editor.getBody().querySelector('p')); editor.selection.collapse(false); });
  await page.getByRole('button', { name: 'Изменения карт', exact: true }).click();
  await page.locator('#hs-cc-date').fill(PATCH);
  await page.locator('#hs-cc-search').fill('М');
  await page.getByText('Введите хотя бы две буквы названия.').first().waitFor();
  for (const [query, id] of [['Матриарх', 'BE_036'], ['Костоправ', 'JAIL_444'], ['Рыцарь-гибрид', 'BE_285']]) {
    await page.locator('#hs-cc-search').fill(query);
    const choice = page.locator('.hs-ccd-result', { hasText: id });
    await choice.first().waitFor();
    await choice.first().click();
  }
  // Battlegrounds names resolve through the same catalog; cards newer than the bundled dictionary are added by identifier.
  await page.locator('#hs-cc-search').fill('Злобный страж гнева');
  await page.locator('.hs-ccd-result', { hasText: 'BG35_155' }).first().waitFor();
  assert.match(await page.locator('.hs-ccd-result', { hasText: 'BG35_155' }).first().innerText(), /Поля сражений/);
  await page.locator('#hs-cc-id').fill('BG36_362');
  await page.getByRole('button', { name: 'Добавить по идентификатору', exact: true }).click();
  await rowsReady(page, 4);
  const states = await page.locator('.hs-ccd-state').allInnerTexts();
  assert.ok(states.every(text => text.startsWith('До — 2026-10-')), `Every card resolved a before/after pair: ${states.join(' | ')}`);
  await previewReady(page, 4);
  const plates = await page.locator('#hs-cc-preview .hs-cc__plate').allInnerTexts();
  assert.equal(plates.length, 5, `Automatic plates: ${plates.join(' | ')}`);
  assert.equal(await page.locator('#hs-cc-preview .hs-cc__plate--bad').count(), 2, 'Higher mana is bad');
  assert.equal(await page.locator('#hs-cc-preview .hs-cc__plate--good').count(), 3, 'Higher attack/health is good');
  check('resolved pairs and plates', true);
  // Edit plates: remove Рыцарь' health plate, add a free label to the Battlegrounds card.
  await page.locator('.hs-ccd-row').nth(2).getByRole('button', { name: /^Убрать плашку: Здоровье/ }).click();
  const bgRow = page.locator('.hs-ccd-row').nth(3);
  await bgRow.locator('.hs-ccd-plate-add select').selectOption('custom');
  await bgRow.getByRole('button', { name: 'Добавить плашку', exact: true }).click();
  await bgRow.getByLabel('Название плашки').fill('Новый эффект');
  await page.waitForFunction(() => document.querySelector('#hs-cc-preview').innerText.toLowerCase().includes('новый эффект'));
  // Reorder and remove through keyboard-accessible buttons.
  await page.locator('.hs-ccd-row').nth(1).getByRole('button', { name: /^Выше:/ }).click();
  assert.match(await page.locator('.hs-ccd-row').first().innerText(), /Костоправ/, 'Reordering moves the card up');
  await page.locator('#hs-cc-columns').selectOption('2');
  await page.locator('#hs-cc-captions').check();
  await page.waitForFunction(() => document.querySelector('#hs-cc-preview .hs-cc--captions'));
  await page.evaluate(() => document.querySelectorAll('.hs-ccd-body > section').forEach(section => { section.scrollTop = 0; }));
  await page.screenshot({ path: `${shots}/editor-dialog-1440.png` });
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    const bounds = await page.locator('#hs-card-changes-dialog').evaluate(el => ({ client: el.clientWidth, scroll: el.scrollWidth, left: el.getBoundingClientRect().left, right: el.getBoundingClientRect().right }));
    assert.ok(bounds.scroll <= bounds.client + 1 && bounds.left >= 0 && bounds.right <= width, `No dialog overflow at ${width}`);
    if (width === 390) await page.screenshot({ path: `${shots}/editor-dialog-390.png` });
  }
  await page.locator('#hs-cc-captions').uncheck();
  for (let index = 0; index < 25; index++) {
    await page.keyboard.press('Tab');
    assert.equal(await page.evaluate(() => document.querySelector('#hs-card-changes-dialog').contains(document.activeElement)), true, 'Native dialog traps focus: ' + await page.evaluate(() => document.activeElement.tagName + '#' + document.activeElement.id + '.' + document.activeElement.className));
  }
  await page.locator('#hs-cc-insert').click();
  await page.waitForFunction(() => !document.querySelector('#hs-card-changes-dialog').open);
  const inserted = await page.evaluate(() => tinymce.get('content').getContent({ format: 'text' }));
  assert.ok(inserted.indexOf('Первый абзац') < inserted.indexOf('[hs_card_changes') && inserted.indexOf('[/hs_card_changes]') < inserted.indexOf('Последний абзац'), 'Inserted at the saved cursor position');
  assert.equal((inserted.match(/\[hs_card_change /g) || []).length, 4);
  assert.match(inserted, /\[hs_card_changes columns="2" size="m"\]/);
  assert.match(inserted, /plates="[^"]*~Новый эффект/, 'Free plate is stored');
  assert.doesNotMatch(inserted, /health:3:4/, 'Removed plate is gone');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.locator('#content-html').click();
  assert.match(await page.locator('#content').inputValue(), /\[hs_card_changes columns="2"/);
  await page.locator('#content-tmce').click();
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized && !window.tinymce.get('content').isHidden());
  await page.locator('#publish').click();
  await page.waitForSelector('#message.updated, .notice-success');

  // Public article: columns, transparency, accessibility, lightbox.
  async function layout(url, columns, width) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto(url);
    await page.locator('.hs-cc .hs-cc__card').first().waitFor();
    await page.waitForFunction(() => [...document.images].filter(image => image.closest('.hs-cc')).every(image => image.complete && image.naturalWidth > 0));
    const grid = page.locator('.hs-cc').first();
    assert.equal(await grid.evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), columns, `Columns at ${width}`);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, `No horizontal scroll at ${width}`);
    const painted = await page.evaluate(() => [...document.querySelectorAll('.hs-cc, .hs-cc__item, .hs-cc__open, .hs-cc__side')].map(el => { const style = getComputedStyle(el); return [style.backgroundColor, style.borderTopWidth, style.boxShadow]; }));
    assert.ok(painted.every(([background, border, shadow]) => background === 'rgba(0, 0, 0, 0)' && border === '0px' && shadow === 'none'), 'Transparent: no panel, border or shadow around a change');
    return grid;
  }
  await layout(`${base}/?p=${fixture.draft}`, 2, 1440);
  assert.equal(await page.locator('.hs-cc .hs-cc__item').count(), 4, 'Editor-inserted block renders four changes');
  assert.equal(await page.locator('.hs-cc__card[alt$="до изменения"]').count(), 4);
  assert.equal(await page.locator('.hs-cc__card[alt$="после изменения"]').count(), 4);
  assert.equal(await page.locator('.hs-cc__card[loading="lazy"]').count(), 8);
  assert.equal(await page.locator('.hs-cc__plate').count(), 5, 'Edited plates survive saving and TinyMCE round trip');
  assert.match(await page.locator('.hs-cc').first().innerText(), /НОВЫЙ ЭФФЕКТ/i);
  await page.locator('.hs-cc').first().screenshot({ path: `${shots}/published-from-editor-1440.png` });
  await layout(`${base}/?p=${fixture.article}`, 2, 1440);
  await page.locator('.hs-cc').first().scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${shots}/article-2-per-row-1440.png` });
  const second = page.locator('.hs-cc').nth(1);
  assert.equal(await second.evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 1, 'Owner option: one per row');
  await second.scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${shots}/article-1-per-row-1440.png` });
  assert.equal(await page.locator('.hs-cc--captions .hs-cc__cap').count(), 4, 'Optional captions');
  assert.equal(await page.locator('.hs-cc:not(.hs-cc--captions) .hs-cc__cap').count(), 0, 'Captions are off by default');
  await layout(`${base}/?p=${fixture.article}`, 1, 390);
  await page.screenshot({ path: `${shots}/article-390.png` });
  await page.locator('.hs-cc').first().scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${shots}/article-2-per-row-390.png` });
  await page.locator('.hs-cc').nth(1).scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${shots}/article-1-per-row-390.png` });
  await layout(`${base}/?p=${fixture.article}`, 1, 720);
  await layout(`${base}/?p=${fixture.article}`, 2, 721);
  // The site's article background is parchment: nothing is painted behind the cards, so it shows through.
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.addStyleTag({ content: '.td-post-content, .td-ss-main-content { background: #f3e9d4 !important; }' });
  await page.locator('.hs-cc').first().scrollIntoViewIfNeeded();
  await page.screenshot({ path: `${shots}/article-2-per-row-1440-parchment.png` });
  check('layouts', true);

  // Lightbox: before and after side by side, keyboard and screen-reader behaviour.
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(`${base}/?p=${fixture.article}`);
  const trigger = page.locator('.hs-cc__open').first();
  assert.match(await trigger.getAttribute('aria-label'), /^МАТРИАРХ: Мана 9 → 10\. Открыть карты до и после/, 'Accessible name carries the change');
  await trigger.focus();
  await page.keyboard.press('Enter');
  const box = page.locator('dialog.hs-lightbox[open]');
  await box.waitFor();
  assert.equal(await page.locator('.hs-lightbox__pair-image').count(), 2);
  assert.equal(await page.locator('.hs-lightbox__nav:visible').count(), 0, 'No gallery navigation in pair mode');
  await page.waitForFunction(() => [...document.querySelectorAll('.hs-lightbox__pair-image')].every(image => image.complete && image.naturalWidth > 0));
  const [left, right] = await page.locator('.hs-lightbox__pair-image').evaluateAll(list => list.map(image => { const r = image.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }));
  assert.ok(right.x >= left.x + left.w - 1 && Math.abs(left.y - right.y) < 4, `Side by side: ${JSON.stringify([left, right])}`);
  assert.ok(left.h > 300, 'Large render');
  assert.deepEqual(await page.locator('.hs-lightbox__pair-caption').allTextContents(), ['До', 'После']);
  assert.equal(await page.locator('.hs-lightbox__pair-image').first().getAttribute('alt'), 'МАТРИАРХ до изменения');
  assert.equal(await box.getAttribute('aria-modal'), 'true');
  assert.equal(await page.locator('#hs-lightbox-title').innerText(), 'МАТРИАРХ', 'Dialog is named after the card');
  await page.screenshot({ path: `${shots}/lightbox-1440.png` });
  await page.keyboard.press('Escape');
  await page.waitForFunction(() => !document.querySelector('dialog.hs-lightbox[open]'));
  assert.equal(await trigger.evaluate(el => el === document.activeElement), true, 'Focus returns to the change');
  await trigger.click();
  await box.waitFor();
  await page.locator('.hs-lightbox__close').click();
  await page.waitForFunction(() => !document.querySelector('dialog.hs-lightbox[open]'));
  await page.locator('.hs-cc__open').nth(4).click();
  await box.waitFor();
  assert.equal(await page.locator('.hs-lightbox__pair-image').count(), 2, 'Second block uses the same dialog');
  await page.keyboard.press('Escape');
  await page.locator('.hs-cc__open').last().click();
  await box.waitFor();
  assert.equal(await page.locator('.hs-lightbox__pair-image').count(), 1, 'Single card opens alone');
  assert.equal(await page.locator('.hs-lightbox__pair-image').getAttribute('alt'), 'МАТРИАРХ');
  await page.keyboard.press('Escape');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.locator('.hs-cc__open').first().click();
  await box.waitFor();
  await page.waitForFunction(() => [...document.querySelectorAll('.hs-lightbox__pair-image')].every(image => image.complete && image.naturalWidth > 0));
  const [top, bottom] = await page.locator('.hs-lightbox__pair-image').evaluateAll(list => list.map(image => image.getBoundingClientRect()));
  assert.ok(bottom.y >= top.y + top.height - 1 && top.right <= 390 && bottom.right <= 390, 'Stacked on a phone without overflow');
  await page.screenshot({ path: `${shots}/lightbox-390.png` });
  await page.keyboard.press('Escape');
  const reduced = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
  const motion = await reduced.newPage();
  await reduced.route('https://api.kolodahearthstone.com/**', route => route.fulfill({ contentType: 'image/svg+xml', body: placeholder('abcdef') }));
  await motion.goto(`${base}/?p=${fixture.article}`);
  await motion.locator('.hs-cc__open').first().click();
  await motion.locator('dialog.hs-lightbox[open]').waitFor();
  assert.equal(await motion.locator('.hs-lightbox__pair-image').first().evaluate(el => getComputedStyle(el).animationName), 'none', 'No motion under prefers-reduced-motion');
  await reduced.close();
  check('lightbox', true);

  // Gutenberg: the same dialog inserts a Shortcode block.
  const blockPage = await context.newPage();
  blockPage.on('pageerror', error => report.errors.push({ message: error.message, stack: error.stack?.slice(0, 600) }));
  await blockPage.goto(`${base}/wp-admin/post.php?post=${fixture.block_post}&action=edit`);
  await blockPage.waitForFunction(() => window.wp?.data?.select('core/editor')?.getCurrentPostId());
  // A fresh site shows the welcome guide a moment after the editor is ready.
  await blockPage.getByRole('button', { name: 'Close', exact: true }).click({ timeout: 8000 }).catch(() => {});
  await blockPage.locator('.hs-card-changes-open').first().click();
  await blockPage.locator('#hs-card-changes-dialog[open]').waitFor();
  await blockPage.locator('#hs-cc-date').fill(PATCH);
  await blockPage.locator('#hs-cc-search').fill('Матриарх');
  await blockPage.locator('.hs-ccd-result', { hasText: 'BE_036' }).first().click();
  await rowsReady(blockPage, 1);
  await previewReady(blockPage, 1);
  await blockPage.locator('#hs-cc-insert').click();
  await blockPage.waitForFunction(() => window.wp.data.select('core/block-editor').getBlocks().some(block => block.name === 'core/shortcode' && block.attributes.text.startsWith('[hs_card_change card="BE_036"')));
  check('gutenberg', true);
  await blockPage.close();

  // Permissions: authors edit their own posts only; subscribers and bad nonces are refused.
  const authorContext = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const author = await authorContext.newPage();
  await login(author, 'cc-author', process.env.WP_CC_AUTHOR_PASSWORD);
  await author.goto(`${base}/wp-admin/post.php?post=${fixture.author_post}&action=edit`);
  await author.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  assert.equal(await author.locator('.hs-card-changes-open').count(), 1, 'Author sees the button on their own post');
  const own = await ajax(author, { mode: 'search', term: 'Матриарх', kind: 'all' });
  assert.equal(own.status, 200);
  assert.match(own.body, /BE_036/);
  const foreign = await ajax(author, { mode: 'search', term: 'Матриарх', kind: 'all', postId: String(fixture.draft) });
  assert.equal(foreign.status, 403, 'Author cannot use the endpoint for someone else’s post');
  for (const bad of [{ mode: 'resolve', card: '../x', cardMode: 'constructed', date: PATCH }, { mode: 'resolve', card: 'BE_036', cardMode: 'evil', date: PATCH }, { mode: 'resolve', card: 'BE_036', cardMode: 'constructed', date: 'tomorrow' }, { mode: 'compose', items: '[{"card":"BE_036","date":"nope","plates":[]}]' }, { mode: 'compose', items: '{}' }, { mode: 'search', term: 'ма', kind: 'everything' }, { mode: 'nope' }]) {
    const refused = await ajax(author, bad);
    assert.ok([400].includes(refused.status), `${JSON.stringify(bad)} → ${refused.status}`);
  }
  let limited = 0;
  for (let index = 0; index < 70; index++) {
    const response = await ajax(author, { mode: 'resolve', card: 'BE_285', cardMode: 'constructed', date: `2026-11-03T15:${String(index % 60).padStart(2, '0')}` });
    if (response.status === 429) limited++;
  }
  assert.ok(limited >= 1, 'Resolve requests are rate limited per editor');
  const markup = await ajax(author, { mode: 'compose', items: JSON.stringify([{ card: 'BE_036', mode: 'constructed', date: PATCH, name: '<img src=x onerror=alert(1)>', before: '', after: '', plates: [{ key: 'custom', label: '<b>x</b>', old: '', new: '' }] }]) });
  assert.equal(markup.status, 400, 'Markup in a plate label is refused');
  const clean = JSON.parse((await ajax(author, { mode: 'compose', items: JSON.stringify([{ card: 'BE_036', mode: 'constructed', date: PATCH, name: '<img src=x onerror=alert(1)>', before: '', after: '', plates: [] }]) })).body);
  assert.ok(!clean.data.shortcode.includes('<') && !clean.data.html.includes('<img src=x') && !/name="[^"]*[<>=]/.test(clean.data.shortcode), 'Names lose markup');
  const noNonce = await author.evaluate(async () => (await fetch(window.hsCardChanges.url, { method: 'POST', body: new URLSearchParams({ action: 'hs_card_changes', nonce: '0000000000', postId: window.hsCardChanges.postId, mode: 'search', term: 'Матриарх', kind: 'all' }) })).status);
  assert.equal(noNonce, 403, 'Bad nonce is refused');
  await authorContext.close();
  const subscriberContext = await browser.newContext();
  const subscriber = await subscriberContext.newPage();
  await login(subscriber, 'cc-subscriber', process.env.WP_CC_SUBSCRIBER_PASSWORD).catch(() => {});
  const anonymous = await subscriber.evaluate(async url => (await fetch(url, { method: 'POST', body: new URLSearchParams({ action: 'hs_card_changes', nonce: '0000000000', postId: '1', mode: 'search', term: 'Матриарх', kind: 'all' }) })).status, `${base}/wp-admin/admin-ajax.php`);
  assert.ok([400, 403].includes(anonymous), 'Subscriber and anonymous requests are refused');
  await subscriberContext.close();
  check('permissions', true);
  // Errors raised by other first-party editors (e.g. hs-api-gallery in Gutenberg) predate this feature and are reported, not hidden.
  report.preexisting = report.errors.filter(error => !/hs-card-changes/.test(error.stack || ''));
  assert.deepEqual(report.errors.filter(error => /hs-card-changes/.test(error.stack || '')), [], 'No page errors from the card changes plugin');
} catch (error) {
  await page.screenshot({ path: `${shots}/failure.png`, fullPage: true }).catch(() => {});
  report.failure = String(error.stack || error);
  throw error;
} finally {
  writeFileSync('.artifacts/card-changes/browser-report.json', JSON.stringify(report, null, 2));
  await browser.close();
}
