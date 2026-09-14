import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

// Exercise shipped PHP, CSS, community helper, and comments JS in Chromium. The
// synthetic HTTP boundary records browser requests; it does not replace UI code.
const ownRoot = fileURLToPath(new URL('../../', import.meta.url));
const root = process.env.READER_UI_SOURCE_ROOT || ownRoot;
const { chromium } = createRequire(`${root}/package.json`)('playwright');
const plugin = `${root}/wordpress/mu-plugins/hs-manacost-reader`;
const ownId = '123e4567-e89b-42d3-a456-426614174000';
const authorId = '223e4567-e89b-42d3-a456-426614174000';
const commentId = '323e4567-e89b-42d3-a456-426614174000';
const firstBanId = '423e4567-e89b-42d3-a456-426614174000';
const nextCursor = '523e4567-e89b-42d3-a456-426614174000';
const json = (response, status, value) => {
  if (response.destroyed) return;
  response.writeHead(status, { 'content-type': 'application/json', 'cache-control': 'no-store' });
  response.end(JSON.stringify(value));
};
const php = "define('ABSPATH','/fixture/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function esc_html__($v){return $v;} function get_the_ID(){return 7;} function get_permalink(){return 'https://example.test/article/';} function wp_parse_url($v,$part){return '/article/';} require $argv[1]; echo hs_reader_comments_shell();";
const shell = execFileSync('php', ['-r', php, `${plugin}/comments.php`], { encoding: 'utf8' });
const assets = new Map([
  ['/bootstrap.js', ['text/javascript', readFileSync(`${plugin}/bootstrap.js`)]],
  ['/community-ui.js', ['text/javascript', readFileSync(`${plugin}/community-ui.js`)]],
  ['/comments.js', ['text/javascript', readFileSync(`${plugin}/comments.js`)]],
  ['/comments.css', ['text/css', readFileSync(`${plugin}/comments.css`)]],
  ['/ui.css', ['text/css', readFileSync(`${plugin}/ui.css`)]],
]);
const kinds = ['like', 'thanks', 'fire'];
const reactions = (selected = null, counts = [5, 2, 1]) => kinds.map((kind, index) => ({ kind, count: counts[index], selected: kind === selected }));
const author = () => ({
  id: authorId, name: 'Автор', bio: 'Колоды', favoriteClass: 'mage', profileUrl: `/account/?reader=${authorId}`,
  avatarVersion: 'A'.repeat(32), avatarUrl: `/reader-api/v1/readers/${authorId}/avatar?v=${'A'.repeat(32)}`,
  paidSubscriber: false, hasTwitch: true, hasYoutube: true,
});
const comment = (overrides = {}) => ({
  id: commentId, postId: 7, parentId: null, status: 'published', version: 1, createdAt: 1700000000000,
  body: 'Комментарий для модерации', author: author(), reactions: reactions(), ...overrides,
});
const me = () => ({ profile: { id: ownId, displayName: 'Читатель', bio: '', favoriteClass: 'mage', version: 1, avatarUrl: null }, csrfToken: 'synthetic-csrf' });
const ban = (id, version) => ({ id, blocked: true, version, profile: { name: `Читатель ${version}` } });
const banId = index => `${(0x623e4567 + index).toString(16).padStart(8, '0')}-e89b-42d3-a456-426614174000`;
const requestBody = async request => {
  let text = ''; for await (const chunk of request) text += chunk;
  return text ? JSON.parse(text) : null;
};

let rows, community, reactionStatus, holdPermission, holdReaction, holdHydration, heldPermissions, heldReactions, heldHydrations;
let reactionWrites, hydrationCalls, deleteWrites, banWrites, unbanWrites, banListCalls, commentPosts, bans;
function reset({ moderator = false, blocked = false } = {}) {
  rows = [comment()]; community = { canModerateComments: moderator, commentingBlocked: blocked };
  reactionStatus = 200; holdPermission = holdReaction = holdHydration = false;
  heldPermissions = []; heldReactions = []; heldHydrations = [];
  reactionWrites = []; hydrationCalls = 0; deleteWrites = []; banWrites = []; unbanWrites = []; banListCalls = []; commentPosts = 0; bans = [];
}
function reactionReply(value) {
  const item = rows.find(row => row.id === commentId), old = item.reactions;
  const selected = old.find(reaction => reaction.selected)?.kind ?? null, counts = old.map(reaction => reaction.count);
  if (value && value !== selected) { if (selected) counts[kinds.indexOf(selected)]--; counts[kinds.indexOf(value)]++; }
  if (value === null && selected) counts[kinds.indexOf(selected)]--;
  item.reactions = reactions(value, counts); return { reactions: item.reactions };
}
function release(queue, status, value) {
  const response = queue.shift(); assert.ok(response, 'expected delayed browser request'); json(response, status, value);
}

reset();
const server = createServer(async (request, response) => {
  const asset = assets.get(request.url);
  if (asset) { response.writeHead(200, { 'content-type': asset[0] }); response.end(asset[1]); return; }
  if (request.url === '/') {
    response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
    response.end(`<!doctype html><meta charset=utf-8><meta name=viewport content="width=device-width"><link rel=stylesheet href=/ui.css><link rel=stylesheet href=/comments.css><body>${shell}<script src=/bootstrap.js></script><script src=/community-ui.js></script><script src=/comments.js></script>`);
    return;
  }
  if (request.url === '/reader-api/v1/bootstrap') { json(response, 200, me()); return; }
  if (request.url.startsWith('/reader-api/v1/threads/7/comments') && request.method === 'GET') { json(response, 200, { items: rows, nextCursor: null }); return; }
  if (request.url === '/reader-api/v1/community/me') {
    if (holdPermission) { request.resume(); heldPermissions.push(response); return; }
    json(response, 200, community); return;
  }
  if (request.url.startsWith('/reader-api/v1/community/reactions?')) {
    hydrationCalls++;
    const ids = new URL(request.url, 'http://fixture').searchParams.getAll('comment');
    if (holdHydration) { request.resume(); heldHydrations.push(response); return; }
    json(response, 200, { items: ids.map(id => ({
      commentId: id,
      reactions: rows.find(row => row.id === id)?.reactions ?? reactions(),
    })) });
    return;
  }
  if (request.url === `/reader-api/v1/comments/${commentId}/reaction` && request.method === 'PUT') {
    const write = await requestBody(request); reactionWrites.push(write);
    if (holdReaction) { heldReactions.push({ response, write }); return; }
    if (reactionStatus !== 200) { json(response, reactionStatus, { error: reactionStatus === 401 ? 'not_authenticated' : 'comments_unavailable' }); return; }
    json(response, 200, reactionReply(write.reaction)); return;
  }
  if (request.url === `/reader-api/v1/moderation/comments/${commentId}` && request.method === 'DELETE') {
    deleteWrites.push(await requestBody(request));
    rows = rows.map(row => row.id === commentId ? { ...row, status: 'deleted', body: null, author: null, version: 2 } : row);
    json(response, 200, { id: commentId, status: 'deleted', version: 2 }); return;
  }
  if (request.url === `/reader-api/v1/moderation/readers/${authorId}/ban`) {
    if (request.method === 'GET') { json(response, 200, { ban: { id: null, blocked: false, version: 0 } }); return; }
    banWrites.push(await requestBody(request)); json(response, 200, { ban: { id: firstBanId, blocked: true, version: 1 } }); return;
  }
  if (request.url.startsWith('/reader-api/v1/moderation/bans') && request.method === 'GET') {
    const cursor = new URL(request.url, 'http://fixture').searchParams.get('cursor'); banListCalls.push(cursor);
    json(response, 200, cursor ? { items: bans.slice(20), nextCursor: null } : { items: bans.slice(0, 20), nextCursor: bans.length > 20 ? nextCursor : null }); return;
  }
  if (request.url.startsWith('/reader-api/v1/moderation/bans/') && request.method === 'PUT') { unbanWrites.push(await requestBody(request)); json(response, 200, { ban: { id: firstBanId, blocked: false, version: 2 } }); return; }
  if (request.url === '/reader-api/v1/threads/7/comments' && request.method === 'POST') { commentPosts++; await requestBody(request); json(response, 201, { comment: comment() }); return; }
  if (request.url.startsWith(`/reader-api/v1/readers/${authorId}/avatar`)) { response.writeHead(200, { 'content-type': 'image/svg+xml' }); response.end('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20"/></svg>'); return; }
  response.writeHead(404); response.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;

let browser;
try {
  browser = await chromium.launch({ headless: true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const page = await browser.newPage(); page.setDefaultTimeout(4000);
  const draft = () => page.getByRole('textbox', { name: 'Комментарий', exact: true });
  const reaction = kind => page.locator(`[data-comment-id="${commentId}"] [data-reaction="${kind}"]`);
  const moderation = () => page.getByText('Модерация', { exact: true });
  const load = async () => { await page.goto(`${origin}/`); await page.getByRole('button', { name: /Нравится/ }).waitFor(); await draft().waitFor(); };
  const confirm = () => page.once('dialog', dialog => dialog.accept());

  reset(); await page.setViewportSize({ width: 320, height: 760 }); await load();
  assert.equal(await page.locator(`[data-comment-id="${commentId}"] [data-reaction]`).count(), 3, 'all three reaction kinds render');
  assert.equal(await page.locator('.mc-comments__author-badge--twitch svg').count(), 1, 'Twitch mark is SVG');
  assert.equal(await page.locator('.mc-comments__author-badge--youtube svg').count(), 1, 'YouTube mark is SVG');
  assert.match(await page.locator('.mc-comments__avatar').first().getAttribute('src'), new RegExp(`/readers/${authorId}/avatar`), 'public author avatar is used');
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, '320px shell has no overflow');
  assert.ok((await reaction('like').boundingBox()).height >= 44, 'reaction touch target is at least 44px');
  assert.equal(await moderation().count(), 0, 'regular readers have no moderator UI');

  reset(); holdHydration = true; await load();
  await new Promise(resolve => {
    const ready = () => heldHydrations.length ? resolve() : setImmediate(ready);
    ready();
  });
  await reaction('like').click();
  await page.waitForFunction(() => {
    const button = document.querySelector('[data-reaction="like"]');
    return button?.getAttribute('aria-pressed') === 'true' && !button.disabled;
  });
  holdHydration = false;
  release(heldHydrations, 200, { items: [{ commentId, reactions: reactions() }] });
  await page.waitForTimeout(20);
  assert.equal(await reaction('like').getAttribute('aria-pressed'), 'true', 'stale hydration cannot overwrite a newer reaction');

  reset(); holdHydration = holdReaction = true; await load();
  await new Promise(resolve => {
    const ready = () => heldHydrations.length ? resolve() : setImmediate(ready);
    ready();
  });
  await reaction('like').click();
  await page.waitForFunction(() => document.querySelector('[data-reaction="like"]').disabled);
  const failedReaction = heldReactions.shift();
  json(failedReaction.response, 503, { error: 'comments_unavailable' });
  await page.getByText('Не удалось сохранить реакцию. Попробуйте ещё раз.').waitFor();
  holdReaction = holdHydration = false;
  release(heldHydrations, 200, { items: [{ commentId, reactions: reactions() }] });
  await Promise.race([
    new Promise(resolve => {
      const retried = () => hydrationCalls >= 2 ? resolve() : setImmediate(retried);
      retried();
    }),
    new Promise((_, reject) => setTimeout(() => reject(new Error('reaction hydration did not retry')), 500)),
  ]);
  assert.equal(hydrationCalls, 2, 'failed PUT causes a retry after stale hydration is released');
  assert.equal(await reaction('like').getAttribute('aria-pressed'), 'false', 'retry restores the authoritative reaction state');

  reset(); await load();
  holdReaction = true; await reaction('like').click();
  await page.waitForFunction(() => document.querySelector('[data-reaction="like"]').disabled);
  assert.equal(await reaction('like').getAttribute('aria-pressed'), 'true', 'a reaction changes locally before the server response returns');
  await reaction('like').dispatchEvent('click');
  assert.equal(reactionWrites.length, 1, 'pending reaction cannot duplicate request');
  holdReaction = false; const pendingLike = heldReactions.shift(); json(pendingLike.response, 200, reactionReply(pendingLike.write.reaction));
  await page.waitForFunction(() => document.querySelector('[data-reaction="like"]').getAttribute('aria-pressed') === 'true');
  await reaction('thanks').click(); await page.waitForFunction(() => document.querySelector('[data-reaction="thanks"]').getAttribute('aria-pressed') === 'true');
  await reaction('thanks').click(); await page.waitForFunction(() => document.querySelector('[data-reaction="thanks"]').getAttribute('aria-pressed') === 'false');
  assert.deepEqual(reactionWrites.map(write => write.reaction), ['like', 'thanks', null], 'toggle, switch, and withdrawal are explicit API states');

  await draft().fill('Черновик переживает 503'); reactionStatus = 503; await reaction('fire').click();
  await page.getByText('Не удалось сохранить реакцию. Попробуйте ещё раз.').waitFor();
  assert.equal(await draft().inputValue(), 'Черновик переживает 503', '503 preserves draft');
  assert.equal(await reaction('fire').getAttribute('aria-pressed'), 'false', '503 preserves reactions');
  reactionStatus = 401; await reaction('like').click(); await page.getByRole('link', { name: 'Войти через HearthPulse' }).waitFor();
  assert.equal(await page.locator('[data-comments-form]').isHidden(), true, '401 clears authenticated composer');
  assert.equal(await page.locator('[data-comments-body]').inputValue(), '', '401 clears private draft');

  reset({ moderator: true }); await load(); await moderation().click(); await page.getByRole('button', { name: 'Удалить комментарий' }).waitFor();
  await draft().fill('Черновик при понижении роли'); community.canModerateComments = false;
  await page.getByRole('button', { name: 'Удалить комментарий' }).click(); await page.waitForFunction(() => !document.querySelector('[data-community-admin]'));
  assert.equal(deleteWrites.length, 0, 'role demotion before action sends no DELETE');
  assert.equal(await draft().inputValue(), 'Черновик при понижении роли', 'denied moderation keeps draft');

  reset({ moderator: true }); await load(); await draft().fill('Черновик настоящего администратора'); await moderation().click(); confirm();
  await page.getByRole('button', { name: 'Удалить комментарий' }).click(); await page.waitForFunction(() => document.querySelector('[data-comments-status]').textContent === 'Комментарий удалён.');
  assert.deepEqual(deleteWrites.at(-1), { version: 1 }, 'genuine admin DELETE sends version only');
  assert.equal(await draft().inputValue(), 'Черновик настоящего администратора', 'admin deletion keeps unrelated draft');

  reset({ moderator: true }); bans = Array.from({ length: 21 }, (_, index) => ban(index === 0 ? firstBanId : banId(index), index + 1)); await load();
  assert.deepEqual(banListCalls, [], 'ban pagination is not automatic');
  await page.getByText('Заблокированные читатели', { exact: true }).click(); await page.getByRole('button', { name: 'Показать ещё блокировки' }).waitFor();
  assert.deepEqual(banListCalls, [null], 'first ban page is user initiated');
  assert.equal(await page.locator('.mc-comments__bans li').count(), 20, 'first ban page is limited to 20');
  await page.getByRole('button', { name: 'Показать ещё блокировки' }).click(); await page.waitForFunction(() => document.querySelectorAll('.mc-comments__bans li').length === 21);
  assert.deepEqual(banListCalls, [null, nextCursor], 'next ban page is lazy and cursor based');
  await page.getByRole('button', { name: 'Разрешить комментировать' }).first().click(); await page.getByText('Комментирование снова разрешено.').waitFor();
  assert.deepEqual(unbanWrites.at(-1), { version: 1 }, 'unban sends version');

  reset({ moderator: true }); await load(); await moderation().click(); await page.getByRole('button', { name: 'Запретить комментировать' }).waitFor(); confirm();
  await page.getByRole('button', { name: 'Запретить комментировать' }).click(); await page.getByText('Комментирование запрещено.').waitFor();
  assert.deepEqual(banWrites.at(-1), { version: 0 }, 'ban uses fresh server version');

  reset({ blocked: true }); await load(); await draft().fill('Черновик заблокированного читателя');
  assert.equal(await page.getByRole('button', { name: 'Опубликовать' }).isDisabled(), true, 'blocked user cannot submit');
  await page.getByRole('button', { name: 'Ответить' }).click();
  assert.equal(await page.getByRole('button', { name: 'Отменить ответ' }).isHidden(), true, 'blocked user cannot reply');
  assert.equal(await draft().inputValue(), 'Черновик заблокированного читателя');
  await page.locator('[data-comments-data] summary').click();
  assert.equal(await page.getByRole('button', { name: 'Скачать мои комментарии' }).isDisabled(), false, 'export remains available');
  assert.equal(await page.getByRole('button', { name: 'Удалить мои комментарии и публичный профиль' }).isDisabled(), false, 'erasure remains available');
  assert.equal(commentPosts, 0);

  reset({ moderator: true }); holdPermission = true;
  await page.goto(`${origin}/`, { waitUntil: 'domcontentloaded' }); await page.getByRole('button', { name: /Нравится/ }).waitFor();
  await new Promise(resolve => setImmediate(resolve)); assert.equal(heldPermissions.length, 1, 'permission body is genuinely delayed');
  holdReaction = true; await reaction('like').click(); await new Promise(resolve => setImmediate(resolve)); assert.equal(heldReactions.length, 1, 'reaction body is genuinely delayed');
  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide', { persisted: true })));
  release(heldPermissions, 200, { canModerateComments: true, commentingBlocked: false });
  const staleReaction = heldReactions.shift(); json(staleReaction.response, 200, reactionReply(staleReaction.write.reaction));
  await page.waitForFunction(() => document.querySelector('[data-comments-form]').hidden === true);
  assert.equal(await page.locator('[data-community-admin]').count(), 0, 'stale permission cannot restore admin UI after pagehide');
  assert.equal(await reaction('like').count(), 0, 'stale reaction cannot restore cleared pagehide rows');
  console.log('community-browser: pass');
} finally {
  await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
}
