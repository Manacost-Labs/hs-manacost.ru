import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { createReaderHandler } from '../http.js';
import { createIdentityClient } from '../identity-client.js';
import { ReaderProfiles } from '../profiles.js';

const origin = 'https://test.hs-manacost.ru';
function fixture() {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  let active = true;
  const identity = {
    authorizationUrl: attempt => { const url = new URL('https://identity.test/identity/auth'); Object.entries(attempt).forEach(([key, value]) => url.searchParams.set(key, value)); return url; },
    exchange: async () => ({ subject: 'reader-1', accessToken: 'private-access', expiresIn: 300 }),
    profile: async () => active ? { displayName: 'Читатель <script>' } : null,
    verify: async () => active,
    revoke: async () => {},
  };
  const handle = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32) });
  return { store, handle, identity, block: () => { active = false; } };
}
const cookie = response => response.headers.getSetCookie().map(item => item.split(';')[0]).filter(item => !item.endsWith('=')).join('; ');
async function login(f) {
  const start = await f.handle(new Request(`${origin}/reader-auth/start?returnTo=/account/`));
  assert.equal(start.status, 303);
  const state = new URL(start.headers.get('location')).searchParams.get('state');
  const callback = await f.handle(new Request(`${origin}/reader-auth/callback?state=${state}&code=code`, { headers: { cookie: cookie(start) } }));
  assert.equal(callback.status, 303);
  assert.equal(callback.headers.get('location'), '/account/');
  return { callback, headers: { cookie: cookie(callback) } };
}

test('anonymous, browser-bound login, no-store profile and authoritative revocation', async () => {
  const f = fixture();
  try {
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`))).status, 401);
    const { headers } = await login(f);
    const profile = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
    assert.match(profile.headers.get('cache-control'), /no-store/);
    assert.equal(profile.headers.get('access-control-allow-origin'), null);
    const body = await profile.json();
    assert.deepEqual(body.user, { displayName: 'Читатель <script>' });
    assert.ok(body.csrfToken);
    assert.ok(!JSON.stringify(body).includes('private-access'));
    f.block();
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 401);
  } finally { f.store.close(); }
});

test('private ad status gives paid HearthPulse readers an ad-free fail-closed gate', async () => {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  let paid = true; let entitlementAvailable = true; let profileCalls = 0;
  const identity = { profile: async () => { profileCalls += 1; return { displayName: 'Reader' }; }, verify: async () => true };
  const handle = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32),
    community: { entitlements: { get: async ids => entitlementAvailable ? new Map(ids.map(id => [id, paid])) : new Map() } } });
  try {
    const session = store.createSession({ userId: 'paid-reader', upstreamToken: 'token', ttlMs: 300000 });
    const headers = { cookie: `__Host-manacost_reader=${session.id}` };
    const anonymous = await handle(new Request(`${origin}/reader-api/v1/ad-status`));
    assert.deepEqual(await anonymous.json(), { adFree: false });
    const subscribed = await handle(new Request(`${origin}/reader-api/v1/ad-status`, { headers }));
    assert.equal(subscribed.status, 200); assert.match(subscribed.headers.get('cache-control'), /private, no-store/);
    assert.deepEqual(await subscribed.json(), { adFree: true });
    paid = false;
    assert.deepEqual(await (await handle(new Request(`${origin}/reader-api/v1/ad-status`, { headers }))).json(), { adFree: false });
    entitlementAvailable = false;
    assert.deepEqual(await (await handle(new Request(`${origin}/reader-api/v1/ad-status`, { headers }))).json(), { adFree: true });
    assert.equal(profileCalls, 0, 'the ad gate verifies only the active token, never userinfo');
  } finally { store.close(); }
});

test('private bootstrap returns the existing profile DTO once and internal metrics stay aggregate-only', async () => {
  const f = fixture();
  try {
    const { headers } = await login(f);
    const bootstrap = await f.handle(new Request(`${origin}/reader-api/v1/bootstrap`, { headers }));
    assert.equal(bootstrap.status, 200);
    assert.match(bootstrap.headers.get('cache-control'), /private, no-store/);
    const body = await bootstrap.json();
    assert.equal(body.user.displayName, 'Читатель <script>');
    assert.ok(body.csrfToken);
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/bootstrap?postId=not-a-number`, { headers }))).status, 404);
    const metrics = await (await f.handle(new Request(`${origin}/reader-internal/metrics`))).json();
    assert.ok(metrics.routes['/reader-api/v1/bootstrap'].requests >= 1);
    assert.equal(JSON.stringify(metrics).includes('private-access'), false);
  } finally { f.store.close(); }
});

test('state requires its browser cookie, replay and cross-origin logout are denied', async () => {
  const f = fixture();
  try {
    const start = await f.handle(new Request(`${origin}/reader-auth/start?returnTo=/account/`));
    const state = new URL(start.headers.get('location')).searchParams.get('state');
    assert.equal((await f.handle(new Request(`${origin}/reader-auth/callback?state=${state}&code=x`))).status, 400);
    const callbackUrl = `${origin}/reader-auth/callback?state=${state}&code=x`;
    const callback = await f.handle(new Request(callbackUrl, { headers: { cookie: cookie(start) } }));
    assert.equal(callback.status, 303);
    assert.equal((await f.handle(new Request(callbackUrl, { headers: { cookie: cookie(start) } }))).status, 400);
    const headers = { cookie: cookie(callback) };
    const me = await (await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).json();
    assert.equal((await f.handle(new Request(`${origin}/reader-auth/logout`, { method: 'POST', headers: { ...headers, origin: 'https://evil.test', 'x-reader-csrf': me.csrfToken } }))).status, 403);
    const logout = await f.handle(new Request(`${origin}/reader-auth/logout`, { method: 'POST', headers: { ...headers, origin, 'x-reader-csrf': me.csrfToken } }));
    assert.equal(logout.status, 204);
    assert.match(logout.headers.get('set-cookie'), /Max-Age=0/);
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 401);
  } finally { f.store.close(); }
});

test('upstream outage fails closed, logout still clears local session with durable revocation', async () => {
  const f = fixture();
  try {
    const { headers } = await login(f);
    const me = await (await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).json();
    f.identity.profile = async () => { throw new Error('private token MUST NOT leak'); };
    const failed = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
    assert.equal(failed.status, 503);
    assert.deepEqual(await failed.json(), { error: 'identity_unavailable' });
    f.identity.revoke = async () => { throw new Error('offline'); };
    const logout = await f.handle(new Request(`${origin}/reader-auth/logout`, { method: 'POST', headers: { ...headers, origin, 'x-reader-csrf': me.csrfToken } }));
    assert.equal(logout.status, 204);
    assert.equal(f.store.pendingRevocations().length, 1);
  } finally { f.store.close(); }
});

test('unsafe return URLs, duplicate auth query values and foreign hosts are rejected', async () => {
  const f = fixture();
  try {
    for (const target of ['//evil.test', '/x/..//evil.test', '/%252fevil.test']) {
      assert.equal((await f.handle(new Request(`${origin}/reader-auth/start?returnTo=${encodeURIComponent(target)}`))).status, 400);
    }
    assert.equal((await f.handle(new Request('https://evil.test/reader-auth/start'))).status, 400);
    assert.equal((await f.handle(new Request(`${origin}/reader-auth/callback?state=a&state=b&code=x`))).status, 400);
  } finally { f.store.close(); }
});

test('logout wins over an in-flight re-login callback and unused token is queued', async () => {
  const f = fixture();
  try {
    const { headers } = await login(f);
    const me = await (await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).json();
    const start = await f.handle(new Request(`${origin}/reader-auth/start?returnTo=/account/`, { headers }));
    const state = new URL(start.headers.get('location')).searchParams.get('state');
    let release; let entered;
    const waiting = new Promise(resolve => { entered = resolve; });
    f.identity.exchange = () => { entered(); return new Promise(resolve => { release = resolve; }); };
    const pending = f.handle(new Request(`${origin}/reader-auth/callback?state=${state}&code=x`, { headers: { cookie: `${headers.cookie}; ${cookie(start)}` } }));
    await waiting;
    assert.equal((await f.handle(new Request(`${origin}/reader-auth/logout`, { method: 'POST', headers: { ...headers, origin, 'x-reader-csrf': me.csrfToken } }))).status, 204);
    release({ subject: 'reader-1', accessToken: 'unused-token', expiresIn: 300 });
    const result = await pending;
    assert.equal(result.status, 400);
    assert.equal(result.headers.get('set-cookie'), null);
    assert.equal(f.store.db.prepare('SELECT count(*) n FROM reader_sessions WHERE revoked_at IS NULL').get().n, 0);
    assert.ok(f.store.pendingRevocations().some(item => item.token === 'unused-token'));
  } finally { f.store.close(); }
});

test('anonymous flood cannot consume an authenticated reader logout or profile budget', async () => {
  const f = fixture();
  try {
    const { headers } = await login(f);
    const me = await (await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).json();
    for (let index = 0; index < 1001; index++) await f.handle(new Request(`${origin}/reader-api/v1/me`));
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 200);
    assert.equal((await f.handle(new Request(`${origin}/reader-auth/logout`, { method: 'POST', headers: { ...headers, origin, 'x-reader-csrf': me.csrfToken } }))).status, 204);
  } finally { f.store.close(); }
});

test('actual OIDC client validates cancellation and returns without replacing a session', async () => {
  const f = fixture();
  try {
    const identity = createIdentityClient({ origin, issuer: 'https://identity.test/identity', deployment: 'test', clientId: 'reader', clientSecret: 'a'.repeat(43) }, () => { throw new Error('Cancellation must not call network'); });
    const handle = createReaderHandler({ origin, store: f.store, identity, csrfKey: randomBytes(32) });
    const start = await handle(new Request(`${origin}/reader-auth/start?returnTo=/account/`));
    const state = new URL(start.headers.get('location')).searchParams.get('state');
    const response = await handle(new Request(`${origin}/reader-auth/callback?state=${state}&error=access_denied&iss=${encodeURIComponent('https://identity.test/identity')}`, { headers: { cookie: cookie(start) } }));
    assert.equal(response.status, 303);
    assert.equal(response.headers.get('location'), '/account/');
    assert.match(response.headers.get('set-cookie'), /reader_login=; Max-Age=0/);
    assert.equal(f.store.db.prepare('SELECT count(*) n FROM reader_sessions').get().n, 0);
  } finally { f.store.close(); }
});

/** Fake identity with call counts and optionally held introspection; no provider or network. */
function accountFixture({ withProfiles = true, community, mcpOAuth } = {}) {
  let now = Date.now();
  const store = new ReaderStore({ encryptionKey: randomBytes(32), now: () => now });
  const profiles = withProfiles ? new ReaderProfiles({ db: store.db, issuer: 'https://identity.test/identity' }) : undefined;
  const calls = { profile: [], verify: [] };
  let hold = null; let entered = () => {};
  const identity = {
    profile: async (token, subject) => { calls.profile.push([token, subject]); return { displayName: 'Внешнее имя' }; },
    verify: async (token, subject) => {
      calls.verify.push([token, subject]);
      if (!hold) return true;
      entered(); return hold;
    },
    revoke: async () => {},
  };
  const handle = createReaderHandler({ origin, store, identity, profiles, community, mcpOAuth, csrfKey: randomBytes(32) });
  const session = store.createSession({ userId: 'local-subject', upstreamToken: 'local-access', ttlMs: 300000 });
  const headers = { cookie: `__Host-manacost_reader=${session.id}` };
  const holdVerify = () => {
    let release;
    hold = new Promise(resolve => { release = resolve; });
    const waiting = new Promise(resolve => { entered = resolve; });
    return { waiting, release: value => { hold = null; release(value); } };
  };
  return { store, profiles, identity, calls, handle, session, headers, holdVerify, advance: ms => { now += ms; } };
}
const get = (f, path) => f.handle(new Request(`${origin}${path}`, { headers: f.headers }));

test('a stored profile answers /me and /bootstrap after token verification without a userinfo request', async () => {
  const f = accountFixture();
  try {
    const stored = f.profiles.getOrCreate('local-subject', 'Локальное имя');
    for (const path of ['/reader-api/v1/me', '/reader-api/v1/bootstrap']) {
      const response = await get(f, path);
      assert.equal(response.status, 200);
      assert.match(response.headers.get('cache-control'), /private, no-store/);
      const body = await response.json();
      assert.deepEqual(Object.keys(body).sort(), ['csrfToken', 'profile', 'profileUrl', 'user']);
      assert.deepEqual(body.user, { displayName: 'Локальное имя' });
      assert.deepEqual(body.profile, stored);
      assert.equal(body.profileUrl, null);
      const text = JSON.stringify(body);
      for (const secret of ['local-subject', 'local-access', f.session.id, 'Внешнее имя']) assert.equal(text.includes(secret), false);
    }
    assert.equal(f.calls.profile.length, 0, 'a stored profile needs no display-name round trip');
    assert.deepEqual(f.calls.verify, [['local-access', 'local-subject'], ['local-access', 'local-subject']]);
  } finally { f.store.close(); }
});

test('a first account read and a deployment without profiles keep the userinfo fallback', async () => {
  const first = accountFixture();
  try {
    const body = await (await get(first, '/reader-api/v1/me')).json();
    assert.equal(body.user.displayName, 'Внешнее имя');
    assert.equal(body.profile.displayName, 'Внешнее имя');
    assert.equal(first.calls.profile.length, 1);
    assert.equal(first.calls.verify.length, 0);
    await get(first, '/reader-api/v1/bootstrap');
    assert.equal(first.calls.profile.length, 1, 'the profile created by the first read is then served locally');
    assert.equal(first.calls.verify.length, 1);
  } finally { first.store.close(); }
  const plain = accountFixture({ withProfiles: false });
  try {
    for (const path of ['/reader-api/v1/me', '/reader-api/v1/bootstrap']) {
      const body = await (await get(plain, path)).json();
      assert.deepEqual(body.user, { displayName: 'Внешнее имя' });
      assert.equal(body.profile, undefined);
    }
    assert.equal(plain.calls.profile.length, 2);
    assert.equal(plain.calls.verify.length, 0);
  } finally { plain.store.close(); }
});

test('a stored profile still ends on upstream revocation and fails closed on an introspection outage', async () => {
  const f = accountFixture();
  try {
    f.profiles.getOrCreate('local-subject', 'Локальное имя');
    f.identity.verify = async () => { throw new Error('local-access MUST NOT leak'); };
    for (const path of ['/reader-api/v1/me', '/reader-api/v1/bootstrap']) {
      const outage = await get(f, path);
      assert.equal(outage.status, 503);
      assert.deepEqual(await outage.json(), { error: 'identity_unavailable' });
    }
    assert.ok(f.store.getSession(f.session.id), 'an outage is not a revocation');
    f.identity.verify = async () => false;
    const revoked = await get(f, '/reader-api/v1/me');
    assert.equal(revoked.status, 401);
    assert.match(revoked.headers.get('set-cookie'), /Max-Age=0/);
    assert.equal(f.store.getSession(f.session.id), null);
    assert.ok(f.store.pendingRevocations().some(item => item.token === 'local-access'));
    assert.equal((await get(f, '/reader-api/v1/bootstrap')).status, 401);
    assert.equal(f.calls.profile.length, 0);
  } finally { f.store.close(); }
});

test('logout, expiry and re-login rotation during a held verification win over a stored profile', async () => {
  for (const [label, interrupt] of [
    ['logout', f => f.store.revokeAndQueue(f.session.id)],
    ['expiry', f => f.advance(300001)],
    ['rotation', f => f.store.rotateSession(f.session.id, { userId: 'local-subject', upstreamToken: 'next-access', ttlMs: 300000 })],
  ]) {
    for (const path of ['/reader-api/v1/me', '/reader-api/v1/bootstrap']) {
      const f = accountFixture();
      try {
        f.profiles.getOrCreate('local-subject', 'Локальное имя');
        const held = f.holdVerify();
        const pending = get(f, path);
        await held.waiting;
        interrupt(f);
        held.release(true);
        const response = await pending;
        assert.equal(response.status, 401, `${label} ${path}`);
        assert.equal(f.calls.profile.length, 0);
      } finally { f.store.close(); }
    }
  }
});

test('bootstrap favorite state keeps postId validation and the editorial session race with a stored profile', async () => {
  let release; let entered;
  const allowed = new Set([7]);
  const community = {
    favorites: { status: (subject, profileId, postId) => subject === 'local-subject' && postId === 7 },
    favoriteEditorial: { get: async ids => {
      if (release === null) { entered(); await new Promise(resolve => { release = resolve; }); }
      return new Map(ids.map(id => [id, { allowed: allowed.has(id) }]));
    } },
  };
  const f = accountFixture({ community });
  try {
    f.profiles.getOrCreate('local-subject', 'Локальное имя');
    const saved = await (await get(f, '/reader-api/v1/bootstrap?postId=7')).json();
    assert.deepEqual(saved.favorite, { postId: 7, saved: true });
    assert.equal(saved.user.displayName, 'Локальное имя');
    for (const query of ['postId=8', 'postId=0', 'postId=1.5', 'postId=x']) assert.equal((await get(f, `/reader-api/v1/bootstrap?${query}`)).status, 404, query);
    for (const query of ['postId=7&postId=7', 'other=1']) assert.equal((await get(f, `/reader-api/v1/bootstrap?${query}`)).status, 400, query);
    release = null;
    const waiting = new Promise(resolve => { entered = resolve; });
    const pending = get(f, '/reader-api/v1/bootstrap?postId=7');
    await waiting;
    f.store.revokeAndQueue(f.session.id);
    release();
    assert.equal((await pending).status, 401);
    assert.equal(f.calls.profile.length, 0);
  } finally { f.store.close(); }
});

test('MCP OAuth handling still runs before the account routes', async () => {
  const seen = [];
  const mcpOAuth = async (request, url) => {
    seen.push(url.pathname);
    return url.pathname === '/mcp' ? new Response(null, { status: 204 }) : null;
  };
  const f = accountFixture({ mcpOAuth });
  try {
    f.profiles.getOrCreate('local-subject', 'Локальное имя');
    assert.equal((await get(f, '/mcp')).status, 204);
    assert.equal((await get(f, '/reader-api/v1/me')).status, 200);
    assert.deepEqual(seen, ['/mcp', '/reader-api/v1/me']);
    assert.equal(f.calls.verify.length, 1);
  } finally { f.store.close(); }
});
