import assert from 'node:assert/strict';
import test from 'node:test';
import { generateKeyPairSync, randomBytes, createHash, verify } from 'node:crypto';
import { ReaderStore } from '../core.js';
import { createReaderHandler } from '../http.js';
import { createMcpOAuthRoutes } from '../mcp-oauth.js';

const origin = 'https://test.hs-manacost.ru';
const resource = `${origin}/mcp`;
const redirectUri = 'http://127.0.0.1:3456/callback';
const pair = generateKeyPairSync('rsa', { modulusLength: 2048 });
const verifier = randomBytes(32).toString('base64url');
const challenge = createHash('sha256').update(verifier).digest('base64url');

function fixture() {
  let now = Date.now();
  const store = new ReaderStore({ encryptionKey: randomBytes(32), now: () => now });
  let admin = true; let available = true;
  const identity = { verify: async () => true, profile: async () => ({ displayName: 'Admin' }),
    authorizationUrl: attempt => { const url = new URL('https://hearthpulse.net/identity/auth'); url.searchParams.set('state', attempt.state); return url; },
    exchange: async () => ({ subject: 'admin-1', accessToken: 'upstream-login', expiresIn: 300 }) };
  const permissions = { get: async subjects => { if (!available) throw new Error('provider_down'); return new Map(subjects.map(sub => [sub, admin])); } };
  const mcpOAuth = createMcpOAuthRoutes({ origin, store, identity, permissions, signingKey: pair.privateKey });
  const handle = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32), mcpOAuth });
  const session = store.createSession({ userId: 'admin-1', upstreamToken: 'upstream-private', ttlMs: 300000 });
  const cookie = `__Host-manacost_reader=${session.id}`;
  const post = (path, body, headers = {}) => handle(new Request(`${origin}${path}`, { method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...headers }, body: new URLSearchParams(body) }));
  const register = async () => {
    const response = await handle(new Request(`${origin}/mcp-oauth/register`, { method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ client_name: 'Test <app>', redirect_uris: [redirectUri], token_endpoint_auth_method: 'none' }) }));
    assert.equal(response.status, 201); return response.json();
  };
  const authorize = async client => {
    const params = new URLSearchParams({ client_id: client.client_id, redirect_uri: redirectUri, response_type: 'code',
      scope: 'articles:read', resource, code_challenge: challenge, code_challenge_method: 'S256', state: 'client-state' });
    const response = await handle(new Request(`${origin}/mcp-oauth/authorize?${params}`, { headers: { cookie } }));
    assert.equal(response.status, 200); const html = await response.text();
    const input = name => html.match(new RegExp(`name="${name}" value="([A-Za-z0-9_-]+)"`))?.[1];
    assert.ok(input('request')); assert.ok(input('approval_token'));
    assert.ok(!html.includes('upstream-private')); assert.match(html, /Test &lt;app&gt;/);
    return { request: input('request'), approval_token: input('approval_token'), decision: 'approve' };
  };
  const grant = async client => {
    const response = await post('/mcp-oauth/authorize', await authorize(client), { cookie, Origin: origin });
    assert.equal(response.status, 303); const url = new URL(response.headers.get('location'));
    assert.equal(url.origin, new URL(redirectUri).origin); assert.equal(url.searchParams.get('state'), 'client-state');
    return url.searchParams.get('code');
  };
  return { store, handle, session, cookie, post, register, authorize, grant, permissions,
    advance: ms => { now += ms; }, demote: () => { admin = false; }, failProvider: () => { available = false; } };
}

test('public metadata and PKCE consent issue a resource-bound JWT; codes and refresh tokens cannot be replayed', async () => {
  const f = fixture();
  try {
    const metadata = await (await f.handle(new Request(`${origin}/.well-known/oauth-authorization-server/mcp-oauth`))).json();
    assert.equal(metadata.issuer, `${origin}/mcp-oauth`); assert.deepEqual(metadata.code_challenge_methods_supported, ['S256']);
    const client = await f.register(); const code = await f.grant(client);
    const body = { grant_type: 'authorization_code', client_id: client.client_id, code, code_verifier: verifier, redirect_uri: redirectUri, resource };
    const response = await f.post('/mcp-oauth/token', body); assert.equal(response.status, 200);
    const tokens = await response.json(); assert.equal(tokens.token_type, 'Bearer'); assert.equal(tokens.scope, 'articles:read');
    const [header, payload, signature] = tokens.access_token.split('.');
    assert.ok(verify('RSA-SHA256', Buffer.from(`${header}.${payload}`), pair.publicKey, Buffer.from(signature, 'base64url')));
    assert.equal(JSON.parse(Buffer.from(header, 'base64url')).typ, 'at+jwt');
    const claims = JSON.parse(Buffer.from(payload, 'base64url')); assert.equal(claims.aud, resource); assert.equal(claims.sub, 'admin-1');
    assert.ok(claims.exp - claims.iat <= 900);
    assert.equal((await f.post('/mcp-oauth/token', body)).status, 400);
    const refresh = { grant_type: 'refresh_token', client_id: client.client_id, refresh_token: tokens.refresh_token, resource };
    const successor = await f.post('/mcp-oauth/token', refresh); assert.equal(successor.status, 200);
    const rotated = await successor.json();
    assert.equal((await f.post('/mcp-oauth/token', refresh)).status, 400);
    assert.equal((await f.post('/mcp-oauth/token', { ...refresh, refresh_token: rotated.refresh_token })).status, 400, 'replay revokes the active refresh family');
  } finally { f.store.close(); }
});

test('consent requires the same browser session, origin and approval token; roles and provider errors fail closed', async () => {
  const f = fixture();
  try {
    const client = await f.register(); const approval = await f.authorize(client);
    assert.equal((await f.post('/mcp-oauth/authorize', approval, { cookie: f.cookie, Origin: 'https://evil.test' })).status, 403);
    assert.equal((await f.post('/mcp-oauth/authorize', { ...approval, approval_token: randomBytes(32).toString('base64url') }, { cookie: f.cookie, Origin: origin })).status, 403);
    assert.equal((await f.post('/mcp-oauth/authorize', approval, { Origin: origin })).status, 403);
    f.demote(); assert.equal((await f.post('/mcp-oauth/authorize', approval, { cookie: f.cookie, Origin: origin })).status, 403);
    f.failProvider(); assert.equal((await f.handle(new Request(`${origin}/mcp-oauth/authorize?request=${approval.request}`, { headers: { cookie: f.cookie } }))).status, 503);
  } finally { f.store.close(); }
});

test('redirect/PKCE/resource mismatch and logout reject grants without changing existing Reader routes', async () => {
  const f = fixture();
  try {
    const client = await f.register(); const code = await f.grant(client);
    const body = { grant_type: 'authorization_code', client_id: client.client_id, code, code_verifier: verifier, redirect_uri: redirectUri, resource };
    assert.equal((await f.post('/mcp-oauth/token', { ...body, code_verifier: randomBytes(32).toString('base64url') })).status, 400);
    assert.equal((await f.post('/mcp-oauth/token', { ...body, redirect_uri: 'https://evil.test/callback' })).status, 400);
    assert.equal((await f.post('/mcp-oauth/token', { ...body, resource: 'https://evil.test/mcp' })).status, 400);
    f.store.revokeAndQueue(f.session.id);
    assert.equal((await f.post('/mcp-oauth/token', body)).status, 400);
    assert.equal((await f.handle(new Request(`${origin}/reader-api/v1/me`))).status, 401);
  } finally { f.store.close(); }
});

test('anonymous authorization resumes through the existing browser-bound Reader login and callback', async () => {
  const f = fixture();
  try {
    const client = await f.register();
    const query = new URLSearchParams({ client_id: client.client_id, redirect_uri: redirectUri, response_type: 'code',
      scope: 'articles:read', resource, code_challenge: challenge, code_challenge_method: 'S256' });
    const response = await f.handle(new Request(`${origin}/mcp-oauth/authorize?${query}`));
    const html = await response.text(); const href = html.match(/href="([^"]+)"/)[1].replaceAll('&amp;', '&');
    const start = await f.handle(new Request(new URL(href, origin), { headers: { 'Sec-Fetch-Site': 'same-origin' } }));
    assert.equal(start.status, 303);
    const cookies = value => value.headers.getSetCookie().map(item => item.split(';')[0]).filter(item => !item.endsWith('=')).join('; ');
    const state = new URL(start.headers.get('location')).searchParams.get('state');
    const callback = await f.handle(new Request(`${origin}/reader-auth/callback?state=${state}&code=test-code`, { headers: { cookie: cookies(start) } }));
    assert.equal(callback.status, 303); assert.match(callback.headers.get('location'), /^\/mcp-oauth\/authorize\?request=[A-Za-z0-9_-]{43}$/);
    const consent = await f.handle(new Request(new URL(callback.headers.get('location'), origin), { headers: { cookie: cookies(callback) } }));
    assert.equal(consent.status, 200); assert.match(await consent.text(), /Разрешить чтение/);
  } finally { f.store.close(); }
});

test('invalid client metadata, duplicated parameters and non-S256 requests never issue a grant', async () => {
  const f = fixture();
  try {
    for (const uri of ['http://evil.test/callback', 'https://good.test/#fragment', 'https://user:pass@good.test/callback', 'javascript:alert(1)', '//evil.test', 'https://good.test/ bad']) {
      const response = await f.handle(new Request(`${origin}/mcp-oauth/register`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ redirect_uris: [uri] }) }));
      assert.equal(response.status, 400, uri);
    }
    const client = await f.register();
    const query = new URLSearchParams({ client_id: client.client_id, redirect_uri: redirectUri, response_type: 'code',
      scope: 'articles:read', resource, code_challenge: challenge, code_challenge_method: 'S256' });
    for (const change of [{ resource: 'https://evil.test/mcp' }, { scope: 'write' }, { code_challenge_method: 'plain' }, { redirect_uri: 'https://evil.test/' }]) {
      const invalid = new URLSearchParams(query); for (const [key, value] of Object.entries(change)) invalid.set(key, value);
      assert.equal((await f.handle(new Request(`${origin}/mcp-oauth/authorize?${invalid}`, { headers: { cookie: f.cookie } }))).status, 400);
    }
    query.append('client_id', client.client_id);
    assert.equal((await f.handle(new Request(`${origin}/mcp-oauth/authorize?${query}`))).status, 400);
    assert.equal((await f.post('/mcp-oauth/token', { grant_type: 'authorization_code', resource })).status, 400);
    assert.equal((await f.post('/mcp-oauth/token', {}, { 'Content-Type': 'application/x-www-form-urlencoded-malicious' })).status, 400);
  } finally { f.store.close(); }
});

test('code expiry, parallel exchanges, refresh revocation and role loss are enforced; stored credentials stay encrypted', async () => {
  const f = fixture();
  try {
    const client = await f.register(); const code = await f.grant(client);
    const body = { grant_type: 'authorization_code', client_id: client.client_id, code, code_verifier: verifier, redirect_uri: redirectUri, resource };
    const responses = await Promise.all([f.post('/mcp-oauth/token', body), f.post('/mcp-oauth/token', body)]);
    assert.deepEqual(responses.map(item => item.status).sort(), [200, 400]);
    const tokens = await responses.find(item => item.status === 200).json();
    const saved = JSON.stringify(f.store.db.prepare('SELECT * FROM mcp_oauth_refresh').all());
    for (const secret of [f.session.id, tokens.refresh_token, 'upstream-private', tokens.access_token]) assert.ok(!saved.includes(secret));
    const refresh = { grant_type: 'refresh_token', client_id: client.client_id, refresh_token: tokens.refresh_token, resource };
    assert.equal((await f.post('/mcp-oauth/revoke', { client_id: client.client_id, token: tokens.refresh_token })).status, 200);
    assert.equal((await f.post('/mcp-oauth/token', refresh)).status, 400);
    const expired = await f.grant(client); f.advance(60001);
    assert.equal((await f.post('/mcp-oauth/token', { ...body, code: expired })).status, 400);
    const demoted = await f.grant(client); f.demote();
    assert.equal((await f.post('/mcp-oauth/token', { ...body, code: demoted })).status, 400);
    f.failProvider(); assert.equal((await f.post('/mcp-oauth/token', { ...body, code: demoted })).status, 503);
  } finally { f.store.close(); }
});
