import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import test from 'node:test';
import sharp from 'sharp';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { createReaderHandler } from '../http.js';

const origin = 'https://test.hs-manacost.ru';
function fixture(t) {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  t.after(() => store.close());
  const profiles = new ReaderProfiles({ db: store.db, issuer: 'https://identity.test/identity' });
  const identity = { verify: async () => true, profile: async () => ({ displayName: 'Читатель' }) };
  const handle = createReaderHandler({ origin, store, profiles, identity, csrfKey: randomBytes(32) });
  async function reader(subject) {
    const session = store.createSession({ userId: subject, upstreamToken: 'synthetic-token', ttlMs: 300000 });
    const headers = { cookie: `__Host-manacost_reader=${session.id}` };
    const me = await (await handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).json();
    return { id: session.id, me, headers: { ...headers, origin, 'x-reader-csrf': me.csrfToken } };
  }
  const call = (path, { method = 'GET', headers = {}, body } = {}) => handle(new Request(origin + path, { method, headers, body }));
  return { store, profiles, identity, reader, call };
}
const draft = version => ({ version, displayName: 'Ледяной маг', bio: 'Играю контроль\nи собираю колоды.', favoriteClass: 'mage', twitchUrl: null, youtubeUrl: null });
const patch = (f, user, input, headers = {}) => f.call('/reader-api/v1/profile', {
  method: 'PATCH', headers: { ...user.headers, 'content-type': 'application/json', ...headers }, body: JSON.stringify(input),
});

test('profile persists across sessions, isolates readers and rejects stale edits', async t => {
  const f = fixture(t); const a = await f.reader('subject-a'); const b = await f.reader('subject-b');
  assert.equal(a.me.profile.version, 1);
  const saved = await patch(f, a, draft(1)); assert.equal(saved.status, 200);
  assert.match(saved.headers.get('cache-control'), /private, no-store/);
  const { profile } = await saved.json();
  assert.equal(profile.displayName, 'Ледяной маг'); assert.equal(profile.version, 2);
  assert.equal((await f.reader('subject-a')).me.profile.id, a.me.profile.id);
  assert.equal((await f.reader('subject-a')).me.profile.favoriteClass, 'mage');
  assert.equal((await f.reader('subject-b')).me.profile.displayName, 'Читатель');
  assert.equal((await patch(f, a, draft(1))).status, 409);
  assert.equal((await patch(f, b, { ...draft(1), subject: 'subject-a' })).status, 400);
  assert.ok(!JSON.stringify(profile).includes('subject-a'));
});

test('profile write returns canonical social profile URLs and retains them for a legacy browser payload', async t => {
  const f = fixture(t); const a = await f.reader('subject-a');
  const saved = await patch(f, a, { ...draft(1), twitchUrl: 'https://twitch.tv/Mana_Cost', youtubeUrl: 'https://youtube.com/@Manacost' });
  assert.equal(saved.status, 200);
  const profile = (await saved.json()).profile;
  assert.equal(profile.twitchUrl, 'https://www.twitch.tv/mana_cost');
  assert.equal(profile.youtubeUrl, 'https://www.youtube.com/@Manacost');
  const legacy = await patch(f, a, { version: profile.version, displayName: 'Ледяной маг', bio: '', favoriteClass: 'mage' });
  assert.equal(legacy.status, 200);
  const stored = (await legacy.json()).profile;
  assert.equal(stored.twitchUrl, profile.twitchUrl);
  assert.equal(stored.youtubeUrl, profile.youtubeUrl);
  const invalid = await patch(f, a, { ...draft(stored.version), twitchUrl: 'https://evil.test/mana_cost' });
  assert.equal(invalid.status, 400);
});

test('writes require online identity, origin and CSRF; malformed inputs remain unchanged', async t => {
  const f = fixture(t); const a = await f.reader('reader');
  assert.equal((await f.call('/reader-api/v1/profile', { method: 'PATCH', body: '{}' })).status, 401);
  assert.equal((await patch(f, a, draft(1), { origin: 'https://evil.test' })).status, 403);
  assert.equal((await patch(f, a, draft(1), { 'x-reader-csrf': 'wrong' })).status, 403);
  assert.equal((await patch(f, a, draft(1), { 'sec-fetch-site': 'cross-site' })).status, 403);
  assert.equal((await patch(f, a, draft(1), { 'content-type': 'text/plain' })).status, 400);
  assert.equal((await patch(f, a, { ...draft(1), displayName: 'x' })).status, 400);
  assert.equal((await patch(f, a, { ...draft(1), bio: 'x'.repeat(5000) })).status, 413);
  f.identity.verify = async () => { throw new Error('private upstream information'); };
  const failed = await patch(f, a, draft(1)); assert.equal(failed.status, 503);
  assert.equal((await failed.text()).includes('private upstream'), false);
  assert.equal(f.profiles.getOrCreate('reader').version, 1);
  f.identity.verify = async () => false;
  assert.equal((await patch(f, a, draft(1))).status, 401);
  assert.equal(f.store.getSession(a.id), null);
});

test('profile writes verify the active token without a second userinfo lookup', async t => {
  const f = fixture(t); const a = await f.reader('reader');
  let userinfoCalls = 0;
  f.identity.verify = async () => true;
  f.identity.profile = async () => { userinfoCalls += 1; throw new Error('userinfo is slow'); };
  const saved = await patch(f, a, draft(1));
  assert.equal(saved.status, 200);
  const bytes = await sharp({ create: { width: 64, height: 32, channels: 3, background: '#224466' } }).png().toBuffer();
  const uploaded = await f.call('/reader-api/v1/profile/avatar', {
    method: 'PUT', headers: { ...a.headers, 'content-type': 'image/png', 'x-reader-profile-version': '2' }, body: bytes,
  });
  assert.equal(uploaded.status, 200);
  assert.equal(userinfoCalls, 0);
});

test('avatar upload does not persist after its second active-token check fails', async t => {
  const f = fixture(t); const a = await f.reader('reader');
  let checks = 0;
  f.identity.verify = async () => { checks += 1; return checks === 1; };
  const bytes = await sharp({ create: { width: 64, height: 32, channels: 3, background: '#224466' } }).png().toBuffer();
  const uploaded = await f.call('/reader-api/v1/profile/avatar', {
    method: 'PUT', headers: { ...a.headers, 'content-type': 'image/png', 'x-reader-profile-version': '1' }, body: bytes,
  });
  assert.equal(uploaded.status, 401);
  assert.equal(checks, 2);
  assert.equal(f.profiles.getOrCreate('reader').version, 1);
});

test('logout wins over an in-flight profile save and /me response', async t => {
  const f = fixture(t); const a = await f.reader('reader');
  let release; let entered;
  const waiting = new Promise(resolve => { entered = resolve; });
  f.identity.verify = () => { entered(); return new Promise(resolve => { release = resolve; }); };
  const pending = patch(f, a, draft(1)); await waiting;
  assert.equal((await f.call('/reader-auth/logout', { method: 'POST', headers: a.headers })).status, 204);
  release({ displayName: 'Читатель' });
  assert.equal((await pending).status, 401);
  assert.equal(f.profiles.getOrCreate('reader').version, 1);
});

test('avatar upload normalizes bytes, is private and removable with version protection', async t => {
  const f = fixture(t); const a = await f.reader('reader-a'); const b = await f.reader('reader-b');
  const bytes = await sharp({ create: { width: 64, height: 32, channels: 3, background: '#224466' } }).png().toBuffer();
  const headers = { ...a.headers, 'content-type': 'image/png', 'x-reader-profile-version': '1' };
  const uploaded = await f.call('/reader-api/v1/profile/avatar', { method: 'PUT', headers, body: bytes });
  assert.equal(uploaded.status, 200); const { profile } = await uploaded.json();
  assert.ok(profile.avatarUrl.startsWith('/reader-api/v1/profile/avatar?v='));
  assert.equal((await f.call(profile.avatarUrl)).status, 401);
  assert.equal((await f.call(profile.avatarUrl, { headers: b.headers })).status, 404);
  const avatar = await f.call(profile.avatarUrl, { headers: a.headers });
  assert.equal(avatar.status, 200); assert.equal(avatar.headers.get('content-type'), 'image/webp');
  assert.match(avatar.headers.get('cache-control'), /no-store/);
  const image = await sharp(Buffer.from(await avatar.arrayBuffer())).metadata();
  assert.equal(image.width, 256); assert.equal(image.height, 256); assert.equal(image.exif, undefined);
  assert.equal((await f.call('/reader-api/v1/profile/avatar', { method: 'PUT', headers, body: bytes })).status, 409);
  const removed = await f.call('/reader-api/v1/profile/avatar', { method: 'DELETE', headers: { ...a.headers, 'x-reader-profile-version': '2' } });
  assert.equal(removed.status, 200); assert.equal((await removed.json()).profile.avatarUrl, null);
  assert.equal((await f.call(profile.avatarUrl, { headers: a.headers })).status, 404);
});

test('invalid avatar and oversized bodies do not mutate the profile', async t => {
  const f = fixture(t); const a = await f.reader('reader');
  const headers = { ...a.headers, 'content-type': 'image/png', 'x-reader-profile-version': '1' };
  for (const [body, expected] of [[Buffer.from('<svg/>'), 400], [Buffer.alloc(4 * 1024 * 1024 + 1), 413]]) {
    assert.equal((await f.call('/reader-api/v1/profile/avatar', { method: 'PUT', headers, body })).status, expected);
  }
  assert.equal(f.profiles.getOrCreate('reader').version, 1);
});
