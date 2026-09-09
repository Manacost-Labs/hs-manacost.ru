import assert from 'node:assert/strict';
import { randomBytes, randomUUID } from 'node:crypto';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { ReaderComments } from '../comments-store.js';
import { createReaderHandler } from '../http.js';

const origin = 'https://test.hs-manacost.ru';
function fixture(t, enabled = true) {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) }); t.after(() => store.close());
  const issuer = 'https://hearthpulse.net/identity';
  const profiles = new ReaderProfiles({ db: store.db, issuer });
  const comments = new ReaderComments({ db: store.db, issuer });
  const identity = { profile: async () => ({ displayName: 'Читатель' }) };
  const editorial = { get: async ids => new Map(ids.map(postId => [postId, { postId, allowed: postId === 17, title: 'Пилот', path: '/pilot/' }])) };
  const entitlements = { get: async ids => new Map(ids.map(id => [id, id === 'paid-reader'])) };
  const handle = createReaderHandler({ origin, store, profiles, identity, csrfKey: randomBytes(32),
    ...(enabled ? { community: { comments, editorial, entitlements } } : {}) });
  const call = (path, { method = 'GET', headers = {}, body } = {}) => handle(new Request(origin + path, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) }));
  async function reader(subject) {
    const session = store.createSession({ userId: subject, upstreamToken: 'synthetic-token', ttlMs: 300000 });
    const cookie = `__Host-manacost_reader=${session.id}`;
    const me = await (await call('/reader-api/v1/me', { headers: { cookie } })).json();
    return { id: session.id, subject, me, headers: { cookie, origin, 'x-reader-csrf': me.csrfToken, 'content-type': 'application/json' } };
  }
  const submit = async (user, extra = {}, postId = 17) => call(`/reader-api/v1/threads/${postId}/comments`, {
    method: 'POST', headers: user.headers, body: { body: 'Полезная статья', parentId: null, operationId: randomUUID(), profileVersion: user.me.profile.version, publicConsent: true, ...extra },
  });
  return { store, profiles, comments, identity, editorial, entitlements, call, reader, submit };
}

test('comments are absent by default and reject guest, CSRF, spoofing and nonpublic articles', async t => {
  const disabled = fixture(t, false);
  assert.equal((await disabled.call('/reader-api/v1/threads/17/comments')).status, 404);
  const f = fixture(t); const user = await f.reader('one');
  assert.equal((await f.call('/reader-api/v1/threads/17/comments', { method: 'POST', body: {} })).status, 401);
  assert.equal((await f.submit({ ...user, headers: { ...user.headers, origin: 'https://evil.test' } })).status, 403);
  for (const extra of [{ paidSubscriber: true }, { authorId: 'other' }, { postId: 18 }, { publicConsent: false }]) assert.equal((await f.submit(user, extra)).status, 400);
  assert.equal((await f.submit(user, {}, 18)).status, 404);
  assert.equal((await f.call('/reader-api/v1/threads/18/comments')).status, 404);
  f.editorial.get = async () => { throw new Error('private editorial configuration'); };
  const failed = await f.submit(user); assert.equal(failed.status, 503);
  assert.ok(!(await failed.text()).includes('private editorial'));
  assert.equal(f.comments.listPending().items.length, 0);
});

test('new posts are immediately public with server-derived author links and paid title', async t => {
  const f = fixture(t); const user = await f.reader('paid-reader'); const other = await f.reader('other');
  const updated = f.profiles.update('paid-reader', {
    version: user.me.profile.version, displayName: 'Читатель', bio: '', favoriteClass: null,
    twitchUrl: 'https://twitch.tv/Mana_Cost', youtubeUrl: 'https://youtube.com/@Manacost',
  });
  const posted = await f.submit(user, { profileVersion: updated.version }); assert.equal(posted.status, 201); const { comment } = await posted.json();
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments')).json()).items[0].id, comment.id);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments', { headers: other.headers })).json()).items[0].id, comment.id);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments', { headers: user.headers })).json()).items[0].status, 'published');
  assert.equal((await f.call(`/reader-api/v1/readers/${user.me.profile.id}`)).status, 200);
  assert.equal(comment.author.paidSubscriber, false, 'POST does not fetch decoration; the subsequent GET verifies it');
  assert.equal(comment.status, 'published');
  const page = await (await f.call('/reader-api/v1/threads/17/comments')).json();
  assert.equal(page.items[0].author.paidSubscriber, true);
  assert.equal(page.items[0].author.profileUrl, `/account/?reader=${user.me.profile.id}`);
  assert.equal(Object.hasOwn(page.items[0].author, 'twitchUrl'), false);
  assert.equal(Object.hasOwn(page.items[0].author, 'youtubeUrl'), false);
  assert.ok(!JSON.stringify(page).includes('paid-reader'));
  const profile = await (await f.call(`/reader-api/v1/readers/${user.me.profile.id}`)).json();
  assert.equal(profile.profile.paidSubscriber, true);
  assert.equal(profile.profile.twitchUrl, 'https://www.twitch.tv/mana_cost');
  assert.equal(profile.profile.youtubeUrl, 'https://www.youtube.com/@Manacost');
  f.entitlements.get = async () => { throw new Error('private provider error'); };
  const withoutBadge = await f.call('/reader-api/v1/threads/17/comments'); assert.equal(withoutBadge.status, 200);
  assert.equal((await withoutBadge.json()).items[0].author.paidSubscriber, false);
  f.editorial.get = async ids => new Map(ids.map(id => [id, { allowed: false }]));
  assert.equal((await f.call('/reader-api/v1/threads/17/comments')).status, 404);
  assert.equal((await f.call(`/reader-api/v1/readers/${user.me.profile.id}`)).status, 404);
});

test('logout during editorial work prevents posting', async t => {
  const f = fixture(t); const user = await f.reader('one');
  let entered; let release; const waiting = new Promise(resolve => { entered = resolve; });
  f.editorial.get = async ids => { entered(); await new Promise(resolve => { release = resolve; }); return new Map(ids.map(id => [id, { allowed: true }])); };
  const pending = f.submit(user); await waiting;
  await f.call('/reader-auth/logout', { method: 'POST', headers: user.headers }); release();
  assert.equal((await pending).status, 401); assert.equal(f.comments.listPending().items.length, 0);
});

test('payment decoration failure does not prevent immediate publication', async t => {
  const f = fixture(t); const user = await f.reader('paid-reader');
  f.entitlements.get = async () => { throw new Error('provider unavailable'); };
  const response = await f.submit(user);
  assert.equal(response.status, 201);
  const { comment } = await response.json();
  assert.equal(comment.status, 'published');
  assert.equal(comment.author.paidSubscriber, false);
  assert.equal(comment.author.profileUrl, `/account/?reader=${user.me.profile.id}`);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments')).json()).items[0].id, comment.id);
});

test('POST never looks up entitlements, including disallowed articles and revoked identities', async t => {
  const f = fixture(t); const user = await f.reader('paid-reader');
  let calls = 0;
  f.entitlements.get = async () => { calls++; throw new Error('must not be called by POST'); };
  assert.equal((await f.submit(user, {}, 18)).status, 404);
  assert.equal((await f.submit(user)).status, 201);
  f.identity.profile = async () => null;
  assert.equal((await f.submit(user)).status, 401);
  assert.equal(calls, 0);
  assert.equal(f.comments.list(17).items.length, 1, 'rejected requests did not persist');
});

test('erasure during public profile or avatar reads wins over their earlier snapshots', async t => {
  for (const avatar of [false, true]) {
    const f = fixture(t); const user = await f.reader('one');
    const updated = f.profiles.setAvatar('one', Buffer.from('synthetic-webp'), user.me.profile.version);
    const { comment } = await (await f.submit(user, { profileVersion: updated.version })).json();
    const version = f.profiles.avatar('one').version;
    const path = `/reader-api/v1/readers/${user.me.profile.id}${avatar ? `/avatar?v=${version}` : ''}`;
    assert.equal((await f.call(path)).status, 200);
    let entered; let release; const waiting = new Promise(resolve => { entered = resolve; });
    f.editorial.get = async ids => { entered(); await new Promise(resolve => { release = resolve; }); return new Map(ids.map(id => [id, { allowed: true }])); };
    const pending = f.call(path); await waiting;
    f.comments.erase('one'); release();
    assert.equal((await pending).status, 404);
  }
});

test('own deletion/export/erasure are authorized and clear public snapshots and images', async t => {
  const f = fixture(t); const user = await f.reader('one'); const other = await f.reader('two');
  const { comment } = await (await f.submit(user)).json();
  assert.equal((await f.call(`/reader-api/v1/comments/${comment.id}`, { method: 'DELETE', headers: other.headers, body: { version: 2 } })).status, 404);
  assert.equal((await (await f.call('/reader-api/v1/community/export', { headers: user.headers })).json()).items.length, 1);
  assert.equal((await (await f.call('/reader-api/v1/community/export', { headers: other.headers })).json()).items.length, 0);
  assert.equal((await f.call('/reader-api/v1/community/profile', { method: 'DELETE', headers: user.headers, body: { profileId: other.me.profile.id, confirm: 'erase-community' } })).status, 400);
  assert.equal((await f.call('/reader-api/v1/community/profile', { method: 'DELETE', headers: user.headers, body: { profileId: user.me.profile.id, confirm: 'erase-community' } })).status, 200);
  assert.equal((await f.call(`/reader-api/v1/readers/${user.me.profile.id}`)).status, 404);
  const page = await (await f.call('/reader-api/v1/threads/17/comments')).json();
  assert.equal(page.items[0].body, null); assert.equal(page.items[0].author, null);
  assert.equal((await f.call('/reader-api/v1/me', { headers: user.headers })).status, 200);
});
