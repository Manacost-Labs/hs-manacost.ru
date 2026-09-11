import assert from 'node:assert/strict';
import { randomBytes, randomUUID } from 'node:crypto';
import test from 'node:test';
import sharp from 'sharp';
import { ReaderArticleFavorites } from '../article-favorites.js';
import { ReaderCommentAttachments } from '../comment-attachments.js';
import { ReaderComments } from '../comments-store.js';
import { ReaderStore } from '../core.js';
import { createReaderHandler } from '../http.js';
import { ReaderProfiles } from '../profiles.js';

const origin = 'https://test.hs-manacost.ru';

function fixture(t) {
  const issuer = 'https://hearthpulse.net/identity';
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  t.after(() => store.close());
  const profiles = new ReaderProfiles({ db: store.db, issuer });
  const attachments = new ReaderCommentAttachments({ db: store.db, issuer });
  const comments = new ReaderComments({ db: store.db, issuer, attachments });
  const favorites = new ReaderArticleFavorites({ db: store.db, issuer });
  const commentAllowed = new Set([17]);
  const favoriteAllowed = new Set([17]);
  const article = (postId, allowed) => ({ postId, allowed, ...(allowed ? { title: 'Гайд по Полям сражений', path: '/guides/battlegrounds/' } : {}) });
  const community = {
    comments,
    attachments,
    favorites,
    editorial: { get: async ids => new Map(ids.map(id => [id, article(id, commentAllowed.has(id))])) },
    favoriteEditorial: { get: async ids => new Map(ids.map(id => [id, article(id, favoriteAllowed.has(id))])) },
    entitlements: { get: async () => new Map() },
  };
  const identity = { verify: async () => true, profile: async () => ({ displayName: 'Читатель' }) };
  const handle = createReaderHandler({ origin, store, profiles, identity, community, csrfKey: randomBytes(32) });
  const call = (path, { method = 'GET', headers = {}, body, raw = false } = {}) => handle(new Request(origin + path, {
    method,
    headers,
    ...(body === undefined ? {} : { body: raw ? body : JSON.stringify(body) }),
  }));
  async function reader(subject) {
    const session = store.createSession({ userId: subject, upstreamToken: `synthetic-${subject}`, ttlMs: 300000 });
    const cookie = `__Host-manacost_reader=${session.id}`;
    const me = await (await call('/reader-api/v1/me', { headers: { cookie } })).json();
    return { me, headers: { cookie, origin, 'x-reader-csrf': me.csrfToken } };
  }
  return { call, reader, denyComment: postId => commentAllowed.delete(postId), denyFavorite: postId => favoriteAllowed.delete(postId) };
}

async function sourcePng() {
  return sharp({ create: { width: 96, height: 48, channels: 3, background: '#224466' } }).png().toBuffer();
}

test('a pasted-comment image is authenticated, private before publication, and disappears on removal', async t => {
  const f = fixture(t); const reader = await f.reader('image-reader'); const image = await sourcePng();
  const upload = await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { ...reader.headers, 'content-type': 'image/png' }, body: image, raw: true,
  });
  assert.equal(upload.status, 201);
  const staged = (await upload.json()).attachment;
  assert.match(staged.id, /^[0-9a-f-]{36}$/i); assert.deepEqual(Object.keys(staged).sort(), ['height', 'id', 'width']);
  assert.equal((await f.call(`/reader-api/v1/comments/${staged.id}/attachment`)).status, 404, 'staged bytes have no public route');
  const post = await f.call('/reader-api/v1/threads/17/comments', {
    method: 'POST', headers: { ...reader.headers, 'content-type': 'application/json' }, body: {
      body: 'Добавляю скриншот.', parentId: null, operationId: randomUUID(), profileVersion: reader.me.profile.version, attachmentId: staged.id,
    },
  });
  assert.equal(post.status, 201);
  const comment = (await post.json()).comment;
  assert.deepEqual(comment.attachment, { ...staged, url: `/reader-api/v1/comments/${comment.id}/attachment` });
  const publicImage = await f.call(comment.attachment.url);
  assert.equal(publicImage.status, 200); assert.equal(publicImage.headers.get('content-type'), 'image/webp');
  const metadata = await sharp(Buffer.from(await publicImage.arrayBuffer())).metadata();
  assert.deepEqual([metadata.width, metadata.height], [96, 48]);
  f.denyComment(17);
  assert.equal((await f.call(comment.attachment.url)).status, 404, 'an image URL follows the host article visibility decision');
  assert.equal((await f.call(`/reader-api/v1/comments/${comment.id}`, {
    method: 'DELETE', headers: { ...reader.headers, 'content-type': 'application/json' }, body: { version: comment.version },
  })).status, 200);
  assert.equal((await f.call(comment.attachment.url)).status, 404);
});

test('attachment uploads reject a guest, foreign origin, invalid bytes, and an oversized body', async t => {
  const f = fixture(t); const reader = await f.reader('image-reader'); const image = await sourcePng();
  assert.equal((await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { 'content-type': 'image/png' }, body: image, raw: true,
  })).status, 401);
  assert.equal((await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { ...reader.headers, origin: 'https://evil.example', 'content-type': 'image/png' }, body: image, raw: true,
  })).status, 403);
  assert.equal((await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { ...reader.headers, 'content-type': 'image/png' }, body: Buffer.from('not an image'), raw: true,
  })).status, 400);
  assert.equal((await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { ...reader.headers, 'content-type': 'image/png', 'content-length': String(4 * 1024 * 1024 + 1) }, body: image, raw: true,
  })).status, 413);
});

test('a reader can discard an unshared staged image without consuming the private attachment quota', async t => {
  const f = fixture(t); const reader = await f.reader('discard-reader'); const image = await sourcePng();
  const upload = await f.call('/reader-api/v1/comment-attachments', {
    method: 'PUT', headers: { ...reader.headers, 'content-type': 'image/png' }, body: image, raw: true,
  });
  assert.equal(upload.status, 201);
  const staged = (await upload.json()).attachment;
  const discarded = await f.call(`/reader-api/v1/comment-attachments/${staged.id}`, { method: 'DELETE', headers: reader.headers });
  assert.deepEqual(await discarded.json(), { id: staged.id, discarded: true });
  const again = await f.call(`/reader-api/v1/comment-attachments/${staged.id}`, { method: 'DELETE', headers: reader.headers });
  assert.deepEqual(await again.json(), { id: staged.id, discarded: false });
  assert.equal((await f.call('/reader-api/v1/threads/17/comments', {
    method: 'POST', headers: { ...reader.headers, 'content-type': 'application/json' }, body: {
      body: 'Этот файл уже убран.', parentId: null, operationId: randomUUID(), profileVersion: reader.me.profile.version, attachmentId: staged.id,
    },
  })).status, 409);
});

test('favorites use signed article data, are private, and accept no browser-controlled metadata', async t => {
  const f = fixture(t); const reader = await f.reader('favorites-reader'); const other = await f.reader('other-reader');
  const status = await f.call('/reader-api/v1/favorites/17', { headers: reader.headers });
  assert.equal(status.status, 200); const initial = await status.json(); assert.equal(initial.saved, false);
  assert.equal((await f.call('/reader-api/v1/favorites/17', {
    method: 'PUT', headers: { ...reader.headers, 'content-type': 'application/json' }, body: { title: 'browser title' },
  })).status, 400, 'a client cannot choose saved article metadata');
  const saved = await f.call('/reader-api/v1/favorites/17', { method: 'PUT', headers: reader.headers });
  assert.equal(saved.status, 201);
  const favorite = (await saved.json()).favorite;
  assert.equal(favorite.postId, 17); assert.equal(favorite.title, 'Гайд по Полям сражений');
  assert.equal(favorite.path, '/guides/battlegrounds/'); assert.match(favorite.id, /^[0-9a-f-]{36}$/i);
  assert.ok(Number.isSafeInteger(favorite.createdAt));
  const own = await (await f.call('/reader-api/v1/favorites', { headers: reader.headers })).json();
  assert.equal(own.items.length, 1);
  assert.deepEqual((await (await f.call('/reader-api/v1/favorites', { headers: other.headers })).json()).items, []);
  assert.equal((await f.call('/reader-api/v1/favorites/18', { method: 'PUT', headers: reader.headers })).status, 404);
  f.denyFavorite(17);
  assert.equal((await f.call('/reader-api/v1/favorites/17', { headers: reader.headers })).status, 404, 'a withdrawn article has no favorite status');
  assert.deepEqual((await (await f.call('/reader-api/v1/favorites', { headers: reader.headers })).json()).items, [],
    'a withdrawn article is purged before a private list can reveal its old title or path');
  assert.deepEqual(await (await f.call('/reader-api/v1/favorites/17', { method: 'DELETE', headers: reader.headers })).json(), { postId: 17, saved: false });
});
