import assert from 'node:assert/strict';
import { expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Synthetic HTTP only: exercise the shipped shells and browser code without a WP runtime,
// browser storage, a real session, or reader data. READER_UI_SOURCE_ROOT lets integration
// tests point this fixture at the candidate implementation instead of this test worktree.
const ownRoot = fileURLToPath(new URL('../../', import.meta.url));
const root = process.env.READER_UI_SOURCE_ROOT || ownRoot;
const plugin = `${root}/wordpress/mu-plugins/hs-manacost-reader`;
const sharedUi = `${plugin}/ui.css`;
const id = '123e4567-e89b-42d3-a456-426614174000';
const otherId = '223e4567-e89b-42d3-a456-426614174000';
const commentId = '323e4567-e89b-42d3-a456-426614174000';
const avatarVersion = 'A'.repeat(32);
const json = (response, status, value) => {
  response.writeHead(status, { 'content-type': 'application/json', 'cache-control': 'no-store' });
  response.end(JSON.stringify(value));
};
const shell = (file, call, extra = '') => execFileSync('php', ['-r', `define('ABSPATH','/fixture/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function esc_html__($v){return $v;} function get_the_ID(){return 7;} function get_permalink(){return 'https://example.test/article/';} function wp_parse_url($v,$p){return '/article/';} require $argv[1]; ${extra} echo ${call};`, file], { encoding: 'utf8' });
const commentShell = shell(`${plugin}/comments.php`, 'hs_reader_comments_shell()');
const profileShell = shell(`${plugin}/public-profile.php`, `hs_reader_public_profile_shell('${id}')`);
const assets = new Map([
  ['/comments.js', ['text/javascript', readFileSync(`${plugin}/comments.js`)]],
  ['/public-profile.js', ['text/javascript', readFileSync(`${plugin}/public-profile.js`)]],
  ['/comments.css', ['text/css', readFileSync(`${plugin}/comments.css`)]],
  ['/ui.css', ['text/css', readFileSync(sharedUi)]],
]);

const author = (overrides = {}) => ({
  id, name: 'Я <script>window.injected=1</script>', bio: 'Люблю колоды', favoriteClass: 'mage',
  avatarVersion, avatarUrl: `/reader-api/v1/readers/${id}/avatar?v=${avatarVersion}`,
  profileUrl: `/account/?reader=${id}`, paidSubscriber: true, twitchUrl: null, youtubeUrl: null, ...overrides,
});
const me = (version = 1) => ({ profile: { id, displayName: 'Я', bio: '', favoriteClass: 'mage', version, avatarUrl: null }, csrfToken: `csrf-${version}` });
const row = (overrides = {}) => ({ id: commentId, postId: 7, parentId: null, status: 'published', version: 1, createdAt: 1700000000000, body: 'Серверный текст', author: author(), ...overrides });

let meVersion = 1;
let comments = [];
let postStatus = 201;
let writes = [];
let postHeaders = [];
let postPartial = false;
let exportPages = [];
let exportCalls = [];
let eraseStatus = 200;
let eraseCalls = [];
let held = { me: [], comments: [], profile: [], post: [] };
let hold = { me: false, comments: false, profile: false };
let publicStatus = 200;
let publicProfile = author();
const release = (kind, status, value) => {
  const request = held[kind].shift();
  assert.ok(request, `expected delayed ${kind} request`);
  json(request.response, status, value);
};
const server = createServer(async (request, response) => {
  const asset = assets.get(request.url);
  if (asset) { response.writeHead(200, { 'content-type': asset[0] }); response.end(asset[1]); return; }
  if (request.url === '/') {
    response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
    response.end(`<!doctype html><meta charset=utf-8><meta name=viewport content="width=device-width"><link rel=stylesheet href=/ui.css><link rel=stylesheet href=/comments.css><body>${commentShell}<script src=/comments.js></script>`); return;
  }
  if (request.url === '/profile') {
    response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
    response.end(`<!doctype html><meta charset=utf-8><link rel=stylesheet href=/ui.css><link rel=stylesheet href=/comments.css><body>${profileShell}<script src=/public-profile.js></script>`); return;
  }
  if (request.url === '/reader-api/v1/me') {
    if (hold.me) { request.resume(); held.me.push({ response }); return; }
    json(response, 200, me(meVersion)); return;
  }
  if (request.url.startsWith('/reader-api/v1/threads/7/comments') && request.method === 'GET') {
    if (hold.comments) { request.resume(); held.comments.push({ response }); return; }
    json(response, 200, { items: comments, nextCursor: null }); return;
  }
  if (request.url === '/reader-api/v1/threads/7/comments' && request.method === 'POST') {
    let text = ''; for await (const chunk of request) text += chunk;
    writes.push(JSON.parse(text));
    postHeaders.push(request.headers);
    if (postPartial) {
      response.writeHead(201, { 'content-type': 'application/json', 'cache-control': 'no-store' });
      response.write('{"comment":'); held.post.push({ response }); return;
    }
    if (postStatus === 401) { json(response, 401, { error: 'not_authenticated' }); return; }
    if (postStatus === 409) { json(response, 409, { error: 'profile_conflict' }); return; }
    json(response, postStatus, { comment: row({ status: 'published', body: writes.at(-1).body }) }); return;
  }
  if (request.url.startsWith('/reader-api/v1/community/export') && request.method === 'GET') {
    const cursor = new URL(request.url, 'http://fixture').searchParams.get('cursor');
    exportCalls.push(cursor);
    const page = exportPages.find(candidate => candidate.cursor === cursor);
    if (!page) { json(response, 503, { error: 'comments_unavailable' }); return; }
    json(response, page.status ?? 200, page.value); return;
  }
  if (request.url === '/reader-api/v1/community/profile' && request.method === 'DELETE') {
    let text = ''; for await (const chunk of request) text += chunk;
    eraseCalls.push({ body: JSON.parse(text), headers: request.headers });
    if (eraseStatus !== 200) { json(response, eraseStatus, { error: 'erase_failed' }); return; }
    comments = []; json(response, 200, { erased: true }); return;
  }
  if (request.url === `/reader-api/v1/readers/${id}`) {
    if (hold.profile) { request.resume(); held.profile.push({ response }); return; }
    json(response, publicStatus, publicStatus === 200 ? { profile: publicProfile } : { error: 'not_found' }); return;
  }
  if (request.url.startsWith(`/reader-api/v1/readers/${id}/avatar`)) { response.writeHead(200, { 'content-type': 'image/svg+xml' }); response.end('<svg xmlns="http://www.w3.org/2000/svg"/>'); return; }
  response.writeHead(404); response.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
let browser;
try {
  browser = await chromium.launch({ headless: true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const page = await browser.newPage();
  page.setDefaultTimeout(4000);
  const loadComments = async () => { await page.goto(`${origin}/`); await page.getByLabel('Комментарий').waitFor(); };
  const submit = async body => { await page.getByLabel('Комментарий').fill(body); await page.getByRole('checkbox', { name: /Согласен/ }).check(); await page.getByRole('button', { name: 'Опубликовать' }).click(); };
  const openCommunityData = async () => { if (!await page.locator('[data-comments-data]').evaluate(element => element.open)) await page.locator('[data-comments-data] summary').click(); };

  // Reading starts alongside identity verification, but private pending rows wait for identity.
  hold.me = hold.comments = true;
  await page.goto(`${origin}/`, { waitUntil: 'domcontentloaded' });
  await expect.poll(() => held.me.length).toBe(1);
  await expect.poll(() => held.comments.length, { message: 'comments request starts while identity is held' }).toBe(1);
  release('comments', 200, { items: [row({ status: 'pending', body: 'Только после проверки входа', author: author({ profileUrl: null, avatarUrl: null, avatarVersion: null, paidSubscriber: false }) })], nextCursor: null });
  assert.equal(await page.getByText('Только после проверки входа').count(), 0);
  release('me', 200, me());
  await page.getByText('Только после проверки входа').waitFor();
  hold.me = hold.comments = false;

  // 1. Only the viewer's pending DTO is valid/rendered; it intentionally has no public identity.
  comments = [
    row({ status: 'pending', body: 'Мой ожидающий', author: author({ profileUrl: null, avatarUrl: null, avatarVersion: null, paidSubscriber: false }) }),
    row({ id: '423e4567-e89b-42d3-a456-426614174000', status: 'pending', body: 'Чужой ожидающий', author: author({ id: otherId, profileUrl: null, avatarUrl: null, avatarVersion: null, paidSubscriber: false }) }),
  ];
  await loadComments();
  assert.deepEqual(await page.locator('[data-mc-comments]').evaluate(root => ({
    background: getComputedStyle(root).backgroundColor,
    color: getComputedStyle(root).color,
    colorScheme: getComputedStyle(root).colorScheme,
    composerBackground: getComputedStyle(root.querySelector('[data-comments-form]')).backgroundColor,
  })), {
    background: 'rgba(0, 0, 0, 0)', color: 'rgb(21, 45, 58)', colorScheme: 'light', composerBackground: 'rgba(0, 0, 0, 0)',
  });
  assert.equal(await page.getByText('Мой ожидающий').count(), 1);
  assert.equal(await page.getByText('Чужой ожидающий').count(), 0);
  assert.equal(await page.locator('.mc-comments__pending').count(), 1);
  assert.equal(await page.locator('.mc-comments__pending').locator('..').getByRole('link').count(), 0);

  // 2. Authentication loss wipes private draft/retry state and returns to the login affordance.
  postStatus = 401; await submit('Потерянный черновик');
  await page.getByRole('link', { name: 'Войти через HearthPulse' }).waitFor();
  assert.equal(await page.locator('[data-comments-body]').evaluate(element => element.value), '');
  assert.equal(await page.locator('[data-comments-consent]').evaluate(element => element.checked), false);
  assert.equal(await page.getByRole('button', { name: 'Повторить отправку' }).isHidden(), true);
  assert.equal(await page.locator('[data-comments-form]').isHidden(), true);
  assert.equal(await page.getByRole('link', { name: 'Войти через HearthPulse' }).isVisible(), true);

  // 3. A 409 preserves text but fetches a newer profile version; the next fresh submit gets a fresh operation ID.
  postStatus = 409; meVersion = 1; comments = [];
  await loadComments(); meVersion = 2; await submit('Сохранённый после конфликта');
  await page.getByText(/Профиль или обсуждение изменились/).waitFor();
  assert.equal(await page.getByLabel('Комментарий').inputValue(), 'Сохранённый после конфликта');
  assert.equal(await page.getByRole('checkbox', { name: /Согласен/ }).isChecked(), false);
  postStatus = 201; await submit('После новой версии');
  await page.getByText('Комментарий опубликован.').waitFor();
  assert.equal(writes.at(-1).profileVersion, 2);
  assert.notEqual(writes.at(-1).operationId, writes.at(-2).operationId);

  // 4. Real delayed HTTP bodies completing after bfcache lifecycle events cannot restore rows or private state.
  hold = { me: false, comments: true, profile: false }; comments = [row({ body: 'Старое приватное' })];
  await page.goto(`${origin}/`, { waitUntil: 'domcontentloaded' });
  await page.waitForFunction(() => document.querySelector('[data-comments-form]').hidden === false);
  assert.equal(held.comments.length, 1, 'initial comments body must remain genuinely in flight');
  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide', { persisted: true })));
  assert.equal(await page.locator('[data-comments-form]').isHidden(), true);
  release('comments', 200, { items: [row({ body: 'Старое приватное' })], nextCursor: null }); // Deliberately complete the stale body after pagehide.
  hold.me = true;
  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
  await page.waitForFunction(() => document.querySelector('[data-comments-form]').hidden === true);
  release('me', 200, me(10)); // The restored generation gets its own delayed /me body.
  await page.getByLabel('Комментарий').waitFor();
  assert.ok(held.comments.length >= 1, 'the restored page must request its own comments');
  release('comments', 200, { items: [row({ body: 'Старое приватное' })], nextCursor: null });
  await page.waitForFunction(() => document.querySelector('[data-comments-list]').textContent.includes('Старое приватное'));
  hold.comments = hold.me = false;

  // 5. The public shell uses textContent for Russian/XSS input, accepts only an exact self-avatar, clears failures, and ignores stale completion.
  publicStatus = 200; publicProfile = author({ name: 'Жрец <img src=x onerror=window.injected=1>', bio: 'Русский текст <b>не HTML</b>', favoriteClass: 'priest', twitchUrl: 'https://www.twitch.tv/mana_cost', youtubeUrl: 'https://www.youtube.com/@Manacost' });
  await page.goto(`${origin}/profile`); await page.getByRole('heading', { name: /Жрец/ }).waitFor();
  assert.equal(await page.evaluate(() => window.injected), undefined);
  assert.equal(await page.locator('[data-public-profile-class]').textContent(), 'Любимый класс: Жрец');
  assert.equal(await page.locator('[data-public-profile-avatar]').getAttribute('src'), publicProfile.avatarUrl);
  assert.equal(await page.locator('[data-public-profile-twitch]').getAttribute('href'), 'https://www.twitch.tv/mana_cost');
  assert.equal(await page.locator('[data-public-profile-youtube]').getAttribute('href'), 'https://www.youtube.com/@Manacost');
  publicProfile = author({ twitchUrl: 'https://evil.test/channel', youtubeUrl: 'https://youtube.com/watch?v=not-a-channel' });
  await page.reload(); await page.locator('[data-public-profile-content]').waitFor();
  assert.equal(await page.locator('[data-public-profile-socials]').isHidden(), true, 'unrecognised public URLs must never become outbound links');
  publicProfile = author({ avatarUrl: `/reader-api/v1/readers/${otherId}/avatar?v=${avatarVersion}` });
  await page.reload(); await page.locator('[data-public-profile-content]').waitFor();
  assert.equal(await page.locator('[data-public-profile-avatar]').isHidden(), true);
  for (const status of [404, 503]) {
    publicStatus = status; await page.reload(); await page.getByText(status === 503 ? /Сервис профилей/ : 'Профиль недоступен.').waitFor();
    assert.equal(await page.locator('[data-public-profile-content]').isHidden(), true);
    assert.equal(await page.locator('[data-public-profile-name]').textContent(), '');
  }
  publicStatus = 200; publicProfile = author({ name: 'Старый ответ' }); hold.profile = true;
  await page.goto(`${origin}/profile`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide', { persisted: true })));
  release('profile', 200, { profile: publicProfile });
  await page.waitForFunction(() => document.querySelector('[data-public-profile-content]').hidden === true);
  hold.profile = false;

  // 6. Export follows cursors to completion before creating one JSON download; malformed cursors and 503 never leak a partial file.
  await loadComments();
  const cursor = '523e4567-e89b-42d3-a456-426614174000';
  exportPages = [
    { cursor: null, value: { items: Array.from({ length: 100 }, (_, n) => ({ id: n })), nextCursor: cursor } },
    { cursor, value: { items: [{ id: 100 }], nextCursor: null } },
  ];
  await openCommunityData();
  let successfulDownload = null;
  page.once('download', download => { successfulDownload = download; });
  await page.getByRole('button', { name: 'Скачать мои комментарии' }).click();
  await page.getByText('Выгрузка подготовлена.').waitFor();
  assert.ok(successfulDownload, 'a complete cursor chain must create one download');
  const stream = await successfulDownload.createReadStream(); let downloadedText = '';
  for await (const part of stream) downloadedText += part;
  assert.equal(JSON.parse(downloadedText).items.length, 101);
  assert.deepEqual(exportCalls.slice(-2), [null, cursor]);
  let downloads = 0; page.on('download', () => { downloads++; });
  exportPages = [
    { cursor: null, value: { items: [{ id: 'first' }], nextCursor: cursor } },
    { cursor, value: { items: [{ id: 'second' }], nextCursor: cursor } },
  ];
  await page.getByRole('button', { name: 'Скачать мои комментарии' }).click();
  await page.getByText('Не удалось подготовить полную выгрузку. Ничего не скачано.').waitFor();
  assert.equal(downloads, 0, 'a repeated cursor must not create a partial download');
  exportPages = [{ cursor: null, value: { items: [{ id: 'first' }], nextCursor: 'not-a-cursor' } }];
  await page.getByRole('button', { name: 'Скачать мои комментарии' }).click();
  await page.getByText('Не удалось подготовить полную выгрузку. Ничего не скачано.').waitFor();
  assert.equal(downloads, 0, 'a malformed cursor must not create a partial download');
  exportPages = [{ cursor: null, status: 503, value: { error: 'comments_unavailable' } }];
  await page.getByRole('button', { name: 'Скачать мои комментарии' }).click();
  await page.getByText('Не удалось подготовить полную выгрузку. Ничего не скачано.').waitFor();
  assert.equal(downloads, 0, 'a 503 must not create a partial download');

  // 7. Community erasure sends the exact authenticated request, clears only community state on success, and leaves data intact on failure.
  comments = [row({ body: 'Мой публичный след' })]; meVersion = 12; eraseStatus = 200;
  await loadComments(); await page.getByLabel('Комментарий').fill('Черновик перед удалением'); await page.getByRole('checkbox', { name: /Согласен/ }).check();
  await openCommunityData();
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Удалить мои комментарии и публичный профиль' }).click();
  await page.getByText('Комментарии и публичный профиль удалены. Кабинет сохранён.').waitFor();
  assert.deepEqual(eraseCalls.at(-1).body, { profileId: id, confirm: 'erase-community' });
  assert.equal(eraseCalls.at(-1).headers['x-reader-csrf'], 'csrf-12');
  assert.equal(await page.getByLabel('Комментарий').inputValue(), '');
  assert.equal(await page.locator('[data-comments-list]').textContent(), '');
  assert.equal(await page.locator('[data-comments-form]').isVisible(), true, 'the private account composer remains available');
  comments = [row({ body: 'Данные не потеряны' })]; eraseStatus = 503;
  await loadComments(); await page.getByLabel('Комментарий').fill('Сохранить при ошибке'); await page.getByRole('checkbox', { name: /Согласен/ }).check();
  await openCommunityData();
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Удалить мои комментарии и публичный профиль' }).click();
  await page.getByText('Не удалось удалить данные. Повторите попытку позже.').waitFor();
  assert.equal(await page.getByLabel('Комментарий').inputValue(), 'Сохранить при ошибке');
  assert.equal(await page.getByText('Данные не потеряны').count(), 1);

  // 8. A real 201 whose JSON body never finishes reaches the request deadline, locks retry state, then retries the exact operation once.
  postPartial = true; postStatus = 201;
  await loadComments(); await submit('Неполный ответ сервера');
  await page.getByText('Результат отправки неизвестен. Повторите тот же комментарий — повторная попытка не создаст дубликат.').waitFor({ timeout: 9000 });
  assert.equal(held.post.length, 1, 'the 201 response must have sent headers but hold its JSON body');
  assert.equal(await page.getByLabel('Комментарий').isDisabled(), true);
  assert.equal(await page.getByRole('checkbox', { name: /Согласен/ }).isDisabled(), true);
  const unknown = writes.at(-1);
  assert.equal(postHeaders.at(-1)['x-reader-csrf'], 'csrf-12');
  postPartial = false;
  await page.getByRole('button', { name: 'Повторить отправку' }).click();
  await page.getByText('Комментарий опубликован.').waitFor();
  assert.deepEqual(writes.at(-1), unknown);
  assert.equal(writes.filter(payload => payload.operationId === unknown.operationId).length, 2);
  console.log('comments-flows: pass (8 focused flows)');
} finally {
  for (const values of Object.values(held)) for (const request of values) request.response.destroy();
  await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
}
