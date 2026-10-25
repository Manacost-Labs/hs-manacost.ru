import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium, expect } from '@playwright/test';

assert.equal(process.env.HS_EDITOR_AI_TEST_DISPOSABLE, '1');
const port = process.env.WP_TEST_PORT ?? '8888';
assert.match(port, /^\d+$/);
const base = `http://127.0.0.1:${port}`;
const post = Number(process.env.HS_EDITOR_AI_TEST_POST);
assert.ok(post > 0);
const report = { environment: 'disposable-wordpress', checks: [], widths: [] };
const browser = await chromium.launch();
const errors = [];
try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  await context.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
  const page = await context.newPage();
  page.setDefaultTimeout(30000);
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(`${base}/wp-login.php`);
  await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
  await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Log In', exact: true }).click();
  await page.waitForURL('**/wp-admin/');
  await page.goto(`${base}/wp-admin/post.php?post=${post}&action=edit`);
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  const dialog = page.locator('#hs-editor-ai-dialog');
  const getContent = () => page.evaluate(() => window.tinymce.get('content').getContent());
  const setContent = source => page.evaluate(value => {
    const editor = window.tinymce.get('content');
    editor.setContent(value);
    editor.selection.setCursorLocation(editor.getBody().firstChild, 0);
    editor.save(); editor.setDirty(true); editor.fire('change');
  }, source);
  const select = text => page.evaluate(value => {
    const editor = window.tinymce.get('content');
    const node = editor.getBody().firstChild.firstChild;
    const start = node.textContent.indexOf(value);
    if (start < 0) throw new Error('Selection text absent');
    const range = editor.getDoc().createRange();
    range.setStart(node, start); range.setEnd(node, start + value.length);
    editor.selection.setRng(range);
  }, text);
  const open = mode => page.locator(`.hs-editor-ai-open[data-mode="${mode}"]`).click();
  const run = async () => {
    await dialog.getByRole('button', { name: 'Отправить текст', exact: true }).click();
    await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Предложение готово');
  };
  const apply = () => dialog.getByRole('button', { name: 'Применить изменения' }).click();
  const close = () => dialog.getByRole('button', { name: 'Закрыть', exact: true }).click();
  await setContent(''); await open('proofread');
  await expect(dialog.getByRole('button', { name: 'Отправить текст', exact: true })).toBeDisabled();
  await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Добавьте текст');
  await close();
  const payload = { post_id: post, mode: 'proofread', source: 'Тест.', context: 'Тест.' };
  const denied = await context.request.post(`${base}/wp-json/hs-editor-ai/v1/jobs`, { headers: { 'X-WP-Nonce': 'invalid' }, data: payload });
  assert.equal(denied.status(), 403);
  const emptyId = '0'.repeat(32);
  const deniedRun = await context.request.post(`${base}/wp-json/hs-editor-ai/v1/jobs/${emptyId}/run`, { headers: { 'X-WP-Nonce': 'invalid' }, data: {} });
  assert.equal(deniedRun.status(), 403);
  const anonymous = await browser.newContext();
  const unauth = await anonymous.request.post(`${base}/wp-json/hs-editor-ai/v1/jobs`, { data: payload });
  assert.equal(unauth.status(), 401);
  assert.equal((await anonymous.request.post(`${base}/wp-json/hs-editor-ai/v1/jobs/${emptyId}/run`, { data: {} })).status(), 401);
  await anonymous.close();
  report.checks.push('real cookie authentication and invalid nonce rejection');

  const original = '<p>Эта карта стоит 3 мнаны. Лирой Дженкинс — финишер.</p>';
  await setContent(original);
  const stable = await getContent();
  await select('Лирой Дженкинс');
  await open('cards');
  await expect(dialog.locator('#hs-editor-ai-input')).toHaveText('Лирой Дженкинс');
  const dispatch = page.waitForResponse(response => response.request().method() === 'POST' && /\/jobs\/[a-f0-9]{32}\/run$/.test(response.url()));
  await run();
  assert.equal((await dispatch).status(), 200);
  await expect(dialog.locator('#hs-editor-ai-after mark')).not.toHaveCount(0);
  await expect(dialog.locator('#hs-editor-ai-after')).toContainText('[hs_card');
  await close(); await open('cards');
  await expect(dialog.getByRole('button', { name: 'Применить изменения' })).toBeVisible();
  await apply();
  assert.equal(await getContent(), stable.replace('Лирой Дженкинс', '[hs_card id="EX1_116"]Лирой Дженкинс[/hs_card]'));
  await page.evaluate(() => window.tinymce.get('content').undoManager.undo());
  assert.equal(await getContent(), stable);
  report.checks.push('selected card insertion and TinyMCE undo');
  await setContent('<p>На Полях сражений Лирой Дженкинс завершает бой.</p>');
  await select('Лирой Дженкинс'); await open('cards'); await run(); await apply();
  assert.match(await getContent(), /\[hs_bg id="BG_001"\]/);
  report.checks.push('BG context selects hs_bg');
  await setContent(original);
  await select('3 мнаны'); await open('proofread'); await run(); await apply();
  assert.equal(await getContent(), stable.replace('3 мнаны', '3 маны'));
  report.checks.push('punctuation mode preserves other words');
  await setContent(original); await open('editor'); await run();
  await expect(dialog.locator('#hs-editor-ai-before')).not.toContainText('<p>');
  await dialog.getByLabel('Показать HTML-разметку').check();
  await expect(dialog.locator('#hs-editor-ai-before')).toContainText('<p>');
  await dialog.getByLabel('Показать HTML-разметку').uncheck();
  await apply();
  assert.equal(await getContent(), stable.replace('3 мнаны', '3 маны'));
  report.checks.push('whole article editing with separate semantic review');
  report.checks.push('immediate authenticated dispatch, safe highlighted preview, markup toggle, retained proposal');

  await setContent(original); await open('proofread'); await run();
  await setContent('<p>Автор уже изменил текст.</p>'); await apply();
  await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Статья изменилась');
  assert.equal(await getContent(), '<p>Автор уже изменил текст.</p>');
  await close(); report.checks.push('stale suggestion cannot overwrite changes');

  await setContent('<p>Текст при временной ошибке: 3 мнаны.</p>');
  const retained = await getContent();
  await open('proofread');
  const temporaryFailure = route => route.request().method() === 'POST'
    ? route.fulfill({ status: 503, contentType: 'application/json', body: JSON.stringify({ message: 'Сервис временно недоступен.' }) })
    : route.continue();
  await page.route('**/wp-json/hs-editor-ai/v1/jobs', temporaryFailure);
  await dialog.getByRole('button', { name: 'Отправить текст', exact: true }).click();
  await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Сервис временно недоступен');
  assert.equal(await getContent(), retained);
  await page.unroute('**/wp-json/hs-editor-ai/v1/jobs', temporaryFailure);
  await dialog.getByRole('button', { name: 'Повторить отправку', exact: true }).click();
  await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Предложение готово');
  await apply();
  report.checks.push('empty text, visible service failure and successful retry without lost text');

  await setContent('<p>Фоновая обработка: 3 мнаны. &lt;img src=x onerror=alert(1)&gt;</p>');
  await open('proofread');
  await dialog.getByRole('button', { name: 'Отправить текст', exact: true }).click();
  await expect(dialog.getByRole('button', { name: 'Продолжить писать', exact: true })).toBeVisible();
  await close(); await open('proofread');
  await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Предложение готово');
  await expect(dialog.locator('.hs-editor-ai-compare img, .hs-editor-ai-compare script')).toHaveCount(0);
  await expect(dialog.locator('#hs-editor-ai-after')).toContainText('<img src=x onerror=alert(1)>');
  await apply();
  report.checks.push('background close/reopen and HTML entities remain inert text in preview');

  for (const width of [320, 390, 600, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await setContent(original); await open('proofread');
    await expect(dialog).toBeVisible();
    const geometry = await dialog.evaluate(element => ({ scroll: element.scrollWidth, client: element.clientWidth, width: element.getBoundingClientRect().width, window: innerWidth }));
    assert.ok(geometry.scroll <= geometry.client + 1 && geometry.width <= geometry.window, JSON.stringify(geometry));
    await page.keyboard.press('Tab');
    assert.ok(await dialog.evaluate(element => element.contains(document.activeElement)));
    await page.keyboard.press('Escape');
    await expect(page.locator('.hs-editor-ai-open[data-mode="proofread"]')).toBeFocused();
    report.widths.push(width);
  }
  await page.setViewportSize({ width: 720, height: 450 });
  await open('proofread');
  await dialog.evaluate(element => element.style.zoom = '2');
  await expect(dialog.getByRole('button', { name: 'Закрыть', exact: true })).toBeVisible();
  await dialog.evaluate(element => element.style.zoom = '');
  await close();
  report.checks.push('responsive dialog, keyboard trap, Escape and restored focus');

  const touchContext = await browser.newContext({
    storageState: await context.storageState(),
    viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true,
  });
  await touchContext.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
  const touchPage = await touchContext.newPage();
  await touchPage.goto(`${base}/wp-admin/post.php?post=${post}&action=edit`);
  await touchPage.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  await touchPage.evaluate(() => {
    const editor = window.tinymce.get('content');
    editor.setContent('<p>На телефоне карта стоит 3 мнаны.</p>');
    editor.selection.select(editor.getBody().firstChild);
    editor.save();
  });
  await touchPage.locator('.hs-editor-ai-open[data-mode="proofread"]').tap();
  const touchDialog = touchPage.locator('#hs-editor-ai-dialog');
  await touchDialog.getByRole('button', { name: 'Отправить текст', exact: true }).tap();
  await expect(touchDialog.locator('#hs-editor-ai-status')).toContainText('Предложение готово');
  await touchDialog.getByRole('button', { name: 'Применить изменения' }).tap();
  assert.match(await touchPage.evaluate(() => window.tinymce.get('content').getContent()), /3 маны/);
  await touchPage.setViewportSize({ width: 844, height: 390 });
  await touchPage.locator('.hs-editor-ai-open[data-mode="proofread"]').tap();
  await expect(touchDialog.getByRole('button', { name: 'Закрыть', exact: true })).toBeVisible();
  await touchPage.setViewportSize({ width: 390, height: 844 });
  // Isolate dialog geometry when a legacy panel widens the phone layout.
  await touchPage.evaluate(() => { document.body.style.minWidth = '609px'; });
  const touchBounds = await touchDialog.evaluate(element => ({right:element.getBoundingClientRect().right, width:visualViewport.width, top:element.getBoundingClientRect().top}));
  assert.ok(touchBounds.right <= touchBounds.width && touchBounds.top >= 0, JSON.stringify(touchBounds));
  await touchDialog.getByRole('button', { name: 'Закрыть', exact: true }).tap();
  await touchContext.close();
  report.checks.push('phone touch selection/apply and landscape dialog');

  // Actual HTML/text mode uses the native textarea and preserves selection.
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.locator('#content-html').click();
  await page.locator('#content').fill('Эта карта стоит 3 мнаны. Лирой Дженкинс.');
  await page.locator('#content').evaluate(element => { const start = element.value.indexOf('3 мнаны'); element.setSelectionRange(start, start + 7); });
  await open('proofread'); await run(); await apply();
  assert.equal(await page.locator('#content').inputValue(), 'Эта карта стоит 3 маны. Лирой Дженкинс.');
  report.checks.push('text mode selection');
  await page.locator('#content-tmce').click();
  await setContent('<p>Эта карта стоит 3 мнаны. Лирой Дженкинс.</p>');
  await open('proofread'); await run();
  await mkdir('.artifacts/editor-ai', { recursive: true });
  await dialog.screenshot({ path: '.artifacts/editor-ai/preview-desktop.png' });
  await page.setViewportSize({ width: 390, height: 844 });
  await dialog.screenshot({ path: '.artifacts/editor-ai/preview-mobile.png' });
  await page.setViewportSize({ width: 1440, height: 900 });
  await apply();
  const saved = await getContent();
  await Promise.all([page.waitForNavigation(), page.locator('#save-post').click()]);
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  assert.equal(await getContent(), saved);
  const headers = { 'X-WP-Nonce': await page.evaluate(() => window.hsEditorAi.nonce) };
  const stored = await context.request.get(`${base}/wp-json/wp/v2/posts/${post}?context=edit`, { headers });
  const nativeSaved = await page.evaluate(() => window.switchEditors.pre_wpautop(window.tinymce.get('content').getContent()));
  assert.equal((await stored.json()).content.raw, nativeSaved);
  report.checks.push('native draft save and stored post_content');

  await setContent('<p>Черновик для autosave: 3 маны.</p>');
  const heartbeat = page.waitForResponse(response => new URL(response.url()).pathname === '/wp-admin/admin-ajax.php' && (response.request().postData() ?? '').includes('wp_autosave'));
  await page.evaluate(() => window.wp.autosave.server.triggerSave());
  const autosave = await heartbeat;
  const autosaveBody = await autosave.json();
  assert.ok(autosave.ok() && autosaveBody.wp_autosave && !autosaveBody.wp_autosave.errors);
  const revisions = await context.request.get(`${base}/wp-json/wp/v2/posts/${post}/revisions?context=edit`, { headers });
  assert.ok((await revisions.json()).length > 0);
  report.checks.push('native editor heartbeat autosave and revision retained');
  await setContent(saved);
  const popupWait = page.waitForEvent('popup');
  await page.locator('#post-preview').click();
  const preview = await popupWait;
  await preview.waitForLoadState('domcontentloaded');
  await expect(preview.locator('body')).toContainText('Эта карта стоит 3 маны');
  await preview.close();
  report.checks.push('native article preview');
  const publish = await context.request.post(`${base}/wp-json/wp/v2/posts/${post}`, { headers, data: { content: saved, status: 'publish' } });
  assert.ok(publish.ok());
  const rendered = await publish.json();
  await page.goto(rendered.link);
  await expect(page.locator('body')).toContainText('Эта карта стоит 3 маны');
  report.checks.push('publication and frontend rendered text');
  assert.equal(errors.length, 0, errors.join('\n'));
  await writeFile('.artifacts/editor-ai/browser-report.json', JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report));
} finally { await browser.close(); }
