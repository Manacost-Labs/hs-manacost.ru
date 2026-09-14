import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { createReaderHandler } from '../http.js';
import { createIdentityClient } from '../identity-client.js';

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
