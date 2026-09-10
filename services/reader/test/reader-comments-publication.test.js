import assert from 'node:assert/strict';
import { randomBytes, randomUUID } from 'node:crypto';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { ReaderComments } from '../comments-store.js';
import { createReaderHandler } from '../http.js';

const origin = 'https://test.hs-manacost.ru';
const path = '/reader-api/v1/community/profile';
async function fixture(t) {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) }); t.after(() => store.close());
  const profiles = new ReaderProfiles({ db: store.db, issuer: 'https://hearthpulse.net/identity' });
  const comments = new ReaderComments({ db: store.db, issuer: profiles.issuer });
  const identity = { profile: async () => ({ displayName: 'Читатель' }) };
  const editorial = { get: async ids => new Map(ids.map(id => [id, { allowed: true }])) };
  const handle = createReaderHandler({ origin, store, profiles, identity, csrfKey: randomBytes(32),
    community: { comments, editorial, entitlements: { get: async () => new Map() } } });
  const session = store.createSession({ userId: 'reader', upstreamToken: 'synthetic', ttlMs: 300000 });
  const cookie = `__Host-manacost_reader=${session.id}`;
  const call = (endpoint, method = 'GET', body, headers = {}) => handle(new Request(origin + endpoint, {
    method, headers, ...(body === undefined ? {} : { body: JSON.stringify(body) }),
  }));
  const me = await (await call('/reader-api/v1/me', 'GET', undefined, { cookie })).json();
  const headers = { cookie, origin, 'x-reader-csrf': me.csrfToken, 'content-type': 'application/json' };
  const publish = (body, override = headers) => call(path, 'PUT', body, override);
  comments.submit('reader', { postId: 17, body: 'Первый комментарий', parentId: null, operationId: randomUUID(), profileVersion: 1, publicConsent: true });
  let updated = profiles.update('reader', { version: 1, displayName: 'Новое имя', bio: 'Описание', favoriteClass: 'druid', twitchUrl: 'https://twitch.tv/manacost', youtubeUrl: null });
  updated = profiles.setAvatar('reader', Buffer.from('synthetic-webp'), updated.version);
  return { store, profiles, comments, identity, editorial, call, publish, headers, updated };
}

test('explicit publication refreshes old comments and avatar without posting another comment', async t => {
  const f = await fixture(t);
  const body = { profileVersion: f.updated.version, publicConsent: true };
  assert.equal(f.comments.publicProfile(f.updated.id).avatarVersion, null, 'private edits alone never publish');
  for (let retry = 0; retry < 2; retry++) {
    const response = await f.publish(body);
    assert.equal(response.status, 200);
    assert.equal((await response.json()).profile.version, f.updated.version);
  }
  const page = await (await f.call('/reader-api/v1/threads/17/comments')).json();
  assert.equal(page.items.length, 1, 'no dummy comment is created');
  const author = page.items[0].author;
  assert.equal(author.name, 'Новое имя'); assert.equal(author.hasTwitch, true);
  assert.equal(Object.hasOwn(author, 'twitchUrl'), false);
  const image = await f.call(author.avatarUrl);
  assert.equal(image.status, 200); assert.equal(await image.text(), 'synthetic-webp');
  const profile = await (await f.call(`/reader-api/v1/readers/${f.updated.id}`)).json();
  assert.equal(profile.profile.twitchUrl, 'https://www.twitch.tv/manacost');
});

test('publication rejects guests, CSRF, absent consent, spoofed fields and stale versions', async t => {
  const f = await fixture(t); const body = { profileVersion: f.updated.version, publicConsent: true };
  assert.equal((await f.publish(body, {})).status, 401);
  assert.equal((await f.publish(body, { ...f.headers, origin: 'https://evil.test' })).status, 403);
  for (const invalid of [{ ...body, publicConsent: false }, { profileVersion: body.profileVersion }, { ...body, profileId: 'other' }]) {
    assert.equal((await f.publish(invalid)).status, 400);
  }
  assert.equal((await f.publish({ ...body, profileVersion: 1 })).status, 409);
  assert.equal(f.comments.publicProfile(f.updated.id).avatarVersion, null);
});

test('publication requires an existing visible comment and cannot undo erasure or logout', async t => {
  for (const action of ['hidden', 'erase', 'logout', 'edit']) {
    const f = await fixture(t);
    f.editorial.get = async ids => {
      if (action === 'erase') f.comments.erase('reader');
      if (action === 'logout') await f.call('/reader-auth/logout', 'POST', {}, f.headers);
      if (action === 'edit') f.profiles.setAvatar('reader', null, f.updated.version);
      return new Map(ids.map(id => [id, { allowed: action !== 'hidden' }]));
    };
    assert.equal((await f.publish({ profileVersion: f.updated.version, publicConsent: true })).status,
      action === 'logout' ? 401 : action === 'edit' ? 409 : 404, action);
    assert.equal(f.comments.publicProfile(f.updated.id)?.avatarVersion ?? null, null);
  }
});
