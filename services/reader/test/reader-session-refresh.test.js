import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { createReaderHandler } from '../http.js';
import { verifiedReader, verifiedWriter } from '../profile-http.js';
import { createIdentityClient } from '../identity-client.js';

const DAY = 86400000;
const origin = 'https://test.hs-manacost.ru';
const signal = () => AbortSignal.timeout(5000);
function fixture(t, filename = ':memory:') {
  let now = 1000000; let refreshes = 0;
  const key = randomBytes(32);
  const store = new ReaderStore({ filename, encryptionKey: key, now: () => now });
  t.after(() => store.close());
  const identity = {
    authorizationUrl: attempt => `https://identity.test/identity/auth?state=${attempt.state}`,
    exchange: async () => ({ subject: 'reader', accessToken: 'access-0', refreshToken: 'refresh-0', expiresIn: 300 }),
    refresh: async () => ({ subject: 'reader', accessToken: `access-${++refreshes}`, refreshToken: `refresh-${refreshes}`, expiresIn: 300 }),
    profile: async () => ({ displayName: 'Читатель' }), verify: async () => true,
  };
  const handle = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32) });
  const create = () => store.createSession({ userId: 'reader', upstreamToken: 'access-0', refreshToken: 'refresh-0', accessTtlMs: 300000, ttlMs: 30 * DAY });
  return { store, identity, handle, create, key, now: () => now, advance: ms => { now += ms; }, refreshes: () => refreshes };
}
const cookie = response => response.headers.getSetCookie().map(item => item.split(';')[0]).filter(item => !item.endsWith('=')).join('; ');
async function login(f) {
  const start = await f.handle(new Request(`${origin}/reader-auth/start`));
  assert.match(start.headers.get('set-cookie'), /Max-Age=300;/);
  const state = new URL(start.headers.get('location')).searchParams.get('state');
  return f.handle(new Request(`${origin}/reader-auth/callback?state=${state}&code=code`, { headers: { cookie: cookie(start) } }));
}

test('new offline login persists an opaque secure cookie for 30 days without extending the login attempt', async t => {
  const f = fixture(t); const response = await login(f);
  assert.equal(response.status, 303);
  const sessionCookie = response.headers.getSetCookie().find(item => item.startsWith('__Host-manacost_reader='));
  assert.match(sessionCookie, /^__Host-manacost_reader=[A-Za-z0-9_-]{43}; Max-Age=2592000; Path=\/; HttpOnly; Secure; SameSite=Lax$/);
  assert.doesNotMatch(sessionCookie, /access|refresh/);
  const headers = { cookie: cookie(response) };
  f.advance(29 * DAY);
  const me = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
  assert.equal(me.status, 200); assert.equal(f.refreshes(), 1);
  assert.equal(me.headers.get('set-cookie'), null, 'activity must not slide the expiry');
  assert.doesNotMatch(await me.text(), /access-\d|refresh-\d/);
  f.advance(DAY);
  assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 401);
  assert.equal(f.refreshes(), 1);
});

test('a provider without an offline grant retains the five-minute session', async t => {
  const f = fixture(t);
  f.identity.exchange = async () => ({ subject: 'reader', accessToken: 'access', expiresIn: 300 });
  const response = await login(f);
  assert.match(response.headers.getSetCookie()[0], /Max-Age=300;/);
  f.advance(300000);
  assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers: { cookie: cookie(response) } }))).status, 401);
});

test('offline scope is limited to the exact staging and production Reader clients', () => {
  const options = { origin, issuer: 'https://hearthpulse.net/identity', clientId: 'manacost-reader-staging', clientSecret: 'a'.repeat(43), deployment: 'staging', allowProductionIdentityForStaging: true };
  const attempt = { state: 'state', nonce: 'nonce', codeChallenge: 'challenge' };
  assert.equal(createIdentityClient(options).authorizationUrl(attempt).searchParams.get('scope'), 'openid profile offline_access');
  assert.equal(createIdentityClient({ ...options, origin: 'https://hs-manacost.ru', deployment: 'production', clientId: 'manacost-reader-production', allowProductionIdentityForStaging: false }).authorizationUrl(attempt).searchParams.get('scope'), 'openid profile offline_access');
  assert.equal(createIdentityClient({ ...options, issuer: 'https://identity.example/identity', clientId: 'reader-test', deployment: 'test', allowProductionIdentityForStaging: false }).authorizationUrl(attempt).searchParams.get('scope'), 'openid profile');
});

test('concurrent reads and writes use one rotating refresh token, preserving absolute expiry', async t => {
  const f = fixture(t); const session = f.create();
  f.advance(280000);
  const verified = await Promise.all(Array.from({ length: 8 }, (_, i) => i % 2
    ? verifiedReader(f.store, f.identity, session.id, signal()) : verifiedWriter(f.store, f.identity, session.id, signal())));
  assert.equal(f.refreshes(), 1);
  assert.ok(verified.every(item => item.session.upstreamToken === 'access-1'));
  assert.equal(f.store.getSession(session.id).expiresAt, session.expiresAt);
  assert.equal(f.store.pendingRevocations().length, 0, 'revoking an old access token would revoke the entire grant');
  assert.equal(f.store.tokens.get(session.id).refreshToken, 'refresh-1');
});

test('logout wins over a pending refresh and queues both old and newly rotated credentials', async t => {
  const f = fixture(t); const session = f.create(); let release;
  f.advance(300000);
  f.identity.refresh = () => new Promise(resolve => { release = resolve; });
  const pending = verifiedReader(f.store, f.identity, session.id, signal());
  f.store.revokeAndQueue(session.id);
  release({ subject: 'reader', accessToken: 'new-access', refreshToken: 'new-refresh', expiresIn: 300 });
  assert.equal(await pending, null);
  assert.equal(f.store.getSession(session.id), null);
  const tokens = f.store.pendingRevocations().map(item => item.token);
  for (const token of ['refresh-0', 'new-refresh']) assert.ok(tokens.includes(token));
});

test('ambiguous refresh failure is never retried with a potentially consumed token', async t => {
  const f = fixture(t); const session = f.create(); let calls = 0;
  f.advance(300000);
  f.identity.refresh = async () => { calls++; throw new Error('private upstream timeout'); };
  const headers = { cookie: `__Host-manacost_reader=${session.id}` };
  const response = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
  assert.equal(response.status, 401);
  assert.deepEqual(await response.json(), { error: 'not_authenticated' });
  assert.match(response.headers.get('set-cookie'), /Max-Age=0/);
  assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 401);
  assert.equal(calls, 1);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'refresh-0'));
});

test('subject mismatch and upstream revocation never extend a local session', async t => {
  const f = fixture(t); const session = f.create();
  f.advance(300000);
  f.identity.refresh = async () => ({ subject: 'other-user', accessToken: 'other-access', refreshToken: 'other-refresh', expiresIn: 300 });
  await assert.rejects(verifiedReader(f.store, f.identity, session.id, signal()));
  assert.equal(f.store.getSession(session.id), null);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'other-refresh'));
  const other = f.create();
  f.identity.profile = async () => null;
  assert.equal(await verifiedReader(f.store, f.identity, other.id, signal()), null);
  assert.equal(f.store.getSession(other.id), null);
});

test('encrypted refresh state survives restart and a durable abandoned claim cannot replay', async t => {
  const dir = mkdtempSync(join(tmpdir(), 'reader-refresh-'));
  t.after(() => rmSync(dir, { recursive: true, force: true }));
  const f = fixture(t, join(dir, 'reader.sqlite')); const session = f.create();
  const raw = f.store.db.prepare('SELECT * FROM reader_session_tokens').get();
  assert.doesNotMatch(JSON.stringify(raw), /refresh-0|access-0/);
  const second = new ReaderStore({ filename: join(dir, 'reader.sqlite'), encryptionKey: f.key, now: f.now });
  t.after(() => second.close());
  assert.equal(second.tokens.get(session.id).refreshToken, 'refresh-0');
  f.advance(300000);
  assert.ok(second.tokens.claim(session.id, 'access-0'));
  await assert.rejects(verifiedReader(f.store, f.identity, session.id, signal()), /refresh in progress/);
  assert.equal(f.refreshes(), 0);
  f.advance(10001);
  assert.equal(await verifiedReader(f.store, f.identity, session.id, signal()), null);
  assert.equal(f.refreshes(), 0);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'refresh-0'));
});

test('re-login rotates the cookie and revokes the previous refresh family; cleanup removes credential orphans', t => {
  const f = fixture(t); const first = f.create();
  const second = f.store.rotateSession(first.id, { userId: 'reader', upstreamToken: 'next-access',
    refreshToken: 'next-refresh', accessTtlMs: 300000, ttlMs: 30 * DAY });
  assert.notEqual(second.id, first.id);
  assert.equal(f.store.getSession(first.id), null);
  assert.equal(f.store.tokens.get(first.id), null);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'refresh-0'));
  f.advance(30 * DAY);
  f.store.cleanup();
  assert.equal(f.store.db.prepare('SELECT count(*) n FROM reader_session_tokens').get().n, 0);
});

test('restart resumes a remembered session with the rotated token rather than requiring a browser re-login', async t => {
  const dir = mkdtempSync(join(tmpdir(), 'reader-persistent-'));
  t.after(() => rmSync(dir, { recursive: true, force: true }));
  const f = fixture(t, join(dir, 'reader.sqlite')); const session = f.create();
  f.advance(DAY);
  await verifiedReader(f.store, f.identity, session.id, signal());
  const second = new ReaderStore({ filename: join(dir, 'reader.sqlite'), encryptionKey: f.key, now: f.now });
  t.after(() => second.close());
  f.advance(DAY);
  const result = await verifiedWriter(second, f.identity, session.id, signal());
  assert.equal(result.session.upstreamToken, 'access-2');
  assert.equal(result.session.expiresAt, session.expiresAt);
});

test('HTTP retains the cookie for a live foreign claim but clears it and revokes after a stale claim', async t => {
  const f = fixture(t); const session = f.create();
  f.advance(300000); assert.ok(f.store.tokens.claim(session.id, 'access-0'));
  const headers = { cookie: `__Host-manacost_reader=${session.id}` };
  const busy = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
  assert.equal(busy.status, 503);
  assert.equal(busy.headers.get('set-cookie'), null);
  assert.equal(f.store.pendingRevocations().length, 0);
  assert.ok(f.store.getSession(session.id));
  f.advance(10001);
  const stale = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
  assert.equal(stale.status, 401); assert.match(stale.headers.get('set-cookie'), /Max-Age=0/);
  assert.equal(f.store.getSession(session.id), null);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'refresh-0'));
});

test('duplicate and malformed session cookies are anonymous, never refresh or authorize', async t => {
  const f = fixture(t); const session = f.create(); f.advance(300000);
  for (const value of [`__Host-manacost_reader=${session.id}; __Host-manacost_reader=${session.id}`,
    '__Host-manacost_reader=short', `__Host-manacost_reader=${session.id}%00`]) {
    const response = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers: { cookie: value } }));
    assert.equal(response.status, 401); assert.deepEqual(await response.json(), { error: 'not_authenticated' });
  }
  assert.equal(f.refreshes(), 0); assert.ok(f.store.getSession(session.id));
});

test('successful token rotation followed by an introspection outage ends login without retry', async t => {
  const f = fixture(t); const session = f.create(); f.advance(300000); const paths = [];
  const client = createIdentityClient({ origin, issuer: 'https://identity.example/identity', clientId: 'manacost-reader-staging',
    clientSecret: 'x'.repeat(43), deployment: 'test' }, async url => {
    const path = new URL(url).pathname; paths.push(path);
    if (path === '/identity/token') return Response.json({ access_token: 'rotated-access', refresh_token: 'rotated-refresh', token_type: 'Bearer', expires_in: 300 });
    throw new Error('Introspection network unavailable');
  });
  f.identity.refresh = client.refresh;
  const headers = { cookie: `__Host-manacost_reader=${session.id}` };
  const response = await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }));
  assert.equal(response.status, 401); assert.match(response.headers.get('set-cookie'), /Max-Age=0/);
  assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`, { headers }))).status, 401);
  assert.deepEqual(paths, ['/identity/token', '/identity/token/introspection']);
  assert.ok(f.store.pendingRevocations().some(item => item.token === 'refresh-0'), 'consumed token still revokes the rotated family');
});

test('malformed callback primitives never reach persistence or unsafe revocation hashing', async t => {
  const f = fixture(t);
  const good = { subject: 'reader', accessToken: 'valid-access', refreshToken: 'valid-refresh', expiresIn: 300 };
  for (const result of [null, { ...good, subject: {} }, { ...good, accessToken: 12 },
    { ...good, refreshToken: {} }, { ...good, expiresIn: Infinity }, { ...good, expiresIn: 0.1 }]) {
    f.identity.exchange = async () => result;
    const response = await login(f);
    assert.equal(response.status, 503); assert.equal(response.headers.get('set-cookie'), null);
    assert.deepEqual(await response.json(), { error: 'identity_unavailable' });
  }
  assert.equal(f.store.db.prepare('SELECT count(*) n FROM reader_sessions').get().n, 0);
  assert.ok(f.store.pendingRevocations().every(item => typeof item.token === 'string'));
});
