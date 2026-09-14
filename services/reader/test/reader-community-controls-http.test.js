import assert from 'node:assert/strict';
import { randomBytes, randomUUID } from 'node:crypto';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { ReaderComments } from '../comments-store.js';
import { createReaderHandler } from '../http.js';

const origin = 'https://test.hs-manacost.ru';
function fixture(t) {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) }); t.after(() => store.close());
  const issuer = 'https://hearthpulse.net/identity';
  const profiles = new ReaderProfiles({ db: store.db, issuer });
  const comments = new ReaderComments({ db: store.db, issuer });
  const identity = { profile: async () => ({ displayName: 'Тестовый читатель' }), verify: async () => true };
  const admins = new Set(['admin']);
  const paid = new Set(['admin']);
  const permissions = { get: async ids => new Map(ids.map(id => [id, admins.has(id)])) };
  const editorial = { get: async ids => new Map(ids.map(id => [id, { allowed: id === 17 }])) };
  const entitlements = { get: async ids => new Map(ids.map(id => [id, paid.has(id)])) };
  const community = { comments, permissions, editorial, entitlements };
  const handle = createReaderHandler({ origin, store, profiles, identity, community, csrfKey: randomBytes(32) });
  const call = (path, { method = 'GET', headers = {}, body } = {}) => handle(new Request(origin + path, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) }));
  async function reader(subject) {
    const session = store.createSession({ userId: subject, upstreamToken: `synthetic-${subject}`, ttlMs: 300000 });
    const cookie = `__Host-manacost_reader=${session.id}`;
    const me = await (await call('/reader-api/v1/me', { headers: { cookie } })).json();
    return { session, subject, me, headers: { cookie, origin, 'x-reader-csrf': me.csrfToken, 'content-type': 'application/json' } };
  }
  const submit = user => call('/reader-api/v1/threads/17/comments', { method: 'POST', headers: user.headers,
    body: { body: 'Тестовый комментарий', parentId: null, operationId: randomUUID(), profileVersion: user.me.profile.version, attachmentId: null } });
  return { store, profiles, comments, identity, permissions, editorial, entitlements, admins, paid, call, reader, submit };
}

test('reaction HTTP boundary requires canonical reader, origin/CSRF and exact input', async t => {
  const f = fixture(t); const alice = await f.reader('alice'); const { comment } = await (await f.submit(alice)).json();
  const path = `/reader-api/v1/comments/${comment.id}/reaction`;
  assert.equal((await f.call(path, { method: 'PUT', body: { reaction: 'like' } })).status, 401);
  assert.equal((await f.call(path, { method: 'PUT', headers: { ...alice.headers, origin: 'https://evil.example' }, body: { reaction: 'like' } })).status, 403);
  assert.equal((await f.call(path, { method: 'PUT', headers: alice.headers, body: { reaction: 'like', actor: 'admin' } })).status, 400);
  const saved = await f.call(path, { method: 'PUT', headers: alice.headers, body: { reaction: 'like' } });
  assert.equal(saved.status, 200);
  assert.equal((await saved.json()).reactions[0].count, 1);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments', { headers: alice.headers })).json()).items[0].reactions[0].selected, false);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments')).json()).items[0].reactions[0].selected, false);
  const selectedPath = `/reader-api/v1/community/reactions?comment=${comment.id}`;
  const selected = await (await f.call(selectedPath, { headers: alice.headers })).json();
  assert.equal(selected.items[0].commentId, comment.id);
  assert.equal(selected.items[0].reactions[0].selected, true);
  f.editorial.get = async ids => new Map(ids.map(id => [id, { allowed: false }]));
  assert.equal((await f.call(path, { method: 'PUT', headers: alice.headers, body: { reaction: 'fire' } })).status, 404);
  assert.equal((await f.call(selectedPath, { headers: alice.headers })).status, 404);
  f.identity.verify = async () => false;
  assert.equal((await f.call(selectedPath, { headers: alice.headers })).status, 401);
});

test('reaction overlaps HearthPulse token verification with editorial access and never fetches a profile', async t => {
  const f = fixture(t); const alice = await f.reader('alice'); const { comment } = await (await f.submit(alice)).json();
  let enterIdentity; let releaseIdentity; let enterEditorial; let releaseEditorial;
  const identityStarted = new Promise(resolve => { enterIdentity = resolve; });
  const identityWait = new Promise(resolve => { releaseIdentity = resolve; });
  const editorialStarted = new Promise(resolve => { enterEditorial = resolve; });
  const editorialWait = new Promise(resolve => { releaseEditorial = resolve; });
  f.identity.profile = async () => { throw new Error('reaction must not fetch HearthPulse userinfo'); };
  f.identity.verify = async () => { enterIdentity(); await identityWait; return true; };
  f.editorial.get = async ids => { enterEditorial(); await editorialWait; return new Map(ids.map(id => [id, { allowed: true }])); };
  const response = f.call(`/reader-api/v1/comments/${comment.id}/reaction`, { method: 'PUT', headers: alice.headers, body: { reaction: 'like' } });
  try {
    await Promise.all([identityStarted, editorialStarted]);
    await setImmediate();
    releaseIdentity(); releaseEditorial();
    assert.equal((await response).status, 200);
  } finally {
    releaseIdentity?.(); releaseEditorial?.();
  }
});

test('HearthPulse subscription and role control own and public identity badges, never client flags', async t => {
  const f = fixture(t); const admin = await f.reader('admin'); const alice = await f.reader('alice');
  const { comment } = await (await f.submit(admin)).json();
  const own = await (await f.call('/reader-api/v1/community/me', { headers: admin.headers })).json();
  assert.deepEqual(own, { canModerateComments: true, paidSubscriber: true, commentingBlocked: false });
  const publicProfile = await (await f.call(`/reader-api/v1/readers/${admin.me.profile.id}`)).json();
  assert.equal(publicProfile.profile.administrator, true);
  const list = await (await f.call('/reader-api/v1/threads/17/comments')).json();
  assert.equal(list.items[0].author.administrator, true);
  const path = `/reader-api/v1/moderation/comments/${comment.id}`;
  assert.equal((await f.call(path, { method: 'DELETE', headers: alice.headers, body: { version: 1 } })).status, 403);
  assert.equal((await f.call(path, { method: 'DELETE', headers: admin.headers, body: { version: 1, role: 'admin' } })).status, 400);
  f.admins.delete('admin');
  assert.equal((await f.call(path, { method: 'DELETE', headers: admin.headers, body: { version: 1 } })).status, 403);
  assert.equal((await (await f.call('/reader-api/v1/threads/17/comments')).json()).items[0].author.administrator, false);
  f.admins.add('admin');
  assert.equal((await f.call(path, { method: 'DELETE', headers: admin.headers, body: { version: 1 } })).status, 200);
});

test('ban/unban HTTP flow denies ordinary readers and survives erasure via bounded admin list', async t => {
  const f = fixture(t); const admin = await f.reader('admin'); const alice = await f.reader('alice');
  await f.submit(alice);
  const path = `/reader-api/v1/moderation/readers/${alice.me.profile.id}/ban`;
  assert.equal((await f.call(path, { headers: alice.headers })).status, 403);
  assert.equal((await f.call(path, { method: 'PUT', headers: admin.headers, body: { version: 0 } })).status, 200);
  assert.equal((await f.submit(alice)).status, 403);
  assert.equal((await (await f.call('/reader-api/v1/community/me', { headers: alice.headers })).json()).commentingBlocked, true);
  assert.equal((await f.call('/reader-api/v1/me', { headers: alice.headers })).status, 200);
  f.comments.erase('alice');
  const { items } = await (await f.call('/reader-api/v1/moderation/bans', { headers: admin.headers })).json();
  assert.equal(items.length, 1); assert.equal(items[0].profile, null);
  assert.equal((await f.call(`/reader-api/v1/moderation/bans/${items[0].id}`, { method: 'PUT', headers: admin.headers, body: { version: items[0].version } })).status, 200);
  assert.equal((await f.submit(alice)).status, 201);
});

test('provider failure fails moderation closed but preserves public reading', async t => {
  const f = fixture(t); const admin = await f.reader('admin'); const { comment } = await (await f.submit(admin)).json();
  f.permissions.get = async () => { throw new Error('secret detail'); };
  const blocked = await f.call(`/reader-api/v1/moderation/comments/${comment.id}`, { method: 'DELETE', headers: admin.headers, body: { version: 1 } });
  assert.equal(blocked.status, 503); assert.ok(!(await blocked.text()).includes('secret detail'));
  const page = await f.call('/reader-api/v1/threads/17/comments'); assert.equal(page.status, 200);
  assert.equal((await page.json()).items[0].author.administrator, false);
});

test('subscription decoration fails closed without withholding the canonical administrator role', async t => {
  const f = fixture(t); const admin = await f.reader('admin');
  f.entitlements.get = async () => { throw new Error('private subscription error'); };
  const response = await f.call('/reader-api/v1/community/me', { headers: admin.headers });
  assert.equal(response.status, 200);
  assert.deepEqual(await response.json(), { canModerateComments: true, paidSubscriber: false, commentingBlocked: false });
});

test('logout or session token replacement while permission work awaits cancels administrator writes', async t => {
  for (const replace of [false, true]) {
    const f = fixture(t); const admin = await f.reader('admin'); const { comment } = await (await f.submit(admin)).json();
    let entered; let release; const waiting = new Promise(resolve => { entered = resolve; });
    f.permissions.get = async ids => { entered(); await new Promise(resolve => { release = resolve; }); return new Map(ids.map(id => [id, true])); };
    const pending = f.call(`/reader-api/v1/moderation/comments/${comment.id}`, { method: 'DELETE', headers: admin.headers, body: { version: 1 } });
    await waiting;
    if (replace) {
      const old = f.store.getSession.bind(f.store);
      f.store.getSession = id => { const result = old(id); return result && { ...result, upstreamToken: 'different-token' }; };
    } else await f.call('/reader-auth/logout', { method: 'POST', headers: admin.headers });
    release(); assert.equal((await pending).status, 401);
    assert.equal(f.comments.get(comment.id).status, 'published');
  }
});
