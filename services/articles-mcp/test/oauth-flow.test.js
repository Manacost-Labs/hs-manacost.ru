import assert from 'node:assert/strict';
import test from 'node:test';
import { once } from 'node:events';
import { randomBytes, generateKeyPairSync } from 'node:crypto';
import { auth, Client, StreamableHTTPClientTransport } from '@modelcontextprotocol/client';
import { createLocalJWKSet } from 'jose';
import { ReaderStore } from '../../reader/core.js';
import { createReaderHandler } from '../../reader/http.js';
import { createMcpOAuthRoutes } from '../../reader/mcp-oauth.js';
import { createAuthorization } from '../auth.js';
import { createHttpServer } from '../http.js';
import { oauthOptions } from '../http-config.js';

const origin = 'https://test.hs-manacost.ru'; const resource = `${origin}/mcp`;
const issuer = `${origin}/mcp-oauth`;

test('HTTP configuration binds the resource, issuer and JWKS to exactly the indexed site', () => {
  const valid = { MCP_RESOURCE_URL: resource, MCP_OAUTH_ISSUER: issuer, MCP_OAUTH_JWKS_URL: `${issuer}/jwks` };
  assert.equal(oauthOptions(origin, valid).issuer, issuer);
  for (const [field, value] of [['MCP_RESOURCE_URL', 'https://hs-manacost.ru/mcp'], ['MCP_OAUTH_ISSUER', 'https://hearthpulse.net/identity'], ['MCP_OAUTH_JWKS_URL', `${issuer}/jwks?evil=1`]]) {
    assert.throws(() => oauthOptions(origin, { ...valid, [field]: value }));
  }
});

test('official SDK discovers, registers, runs PKCE and reads through a real HTTP MCP server using the issued JWT', async () => {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  let admin = true; let available = true;
  const permissions = { get: async subjects => { if (!available) throw new Error('provider_down'); return new Map(subjects.map(sub => [sub, admin])); } };
  const identity = { verify: async () => true };
  const mcpOAuth = createMcpOAuthRoutes({ origin, store, identity, permissions, signingKey: generateKeyPairSync('rsa', { modulusLength: 2048 }).privateKey });
  const reader = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32), mcpOAuth });
  const cookie = `__Host-manacost_reader=${store.createSession({ userId: 'admin-1', upstreamToken: 'private-upstream', ttlMs: 300000 }).id}`;
  const jwks = await (await reader(new Request(`${issuer}/jwks`))).json();
  const authorize = createAuthorization({ issuer, resource, jwksUrl: `${issuer}/jwks`, permissions }, createLocalJWKSet(jwks));
  const library = { fetch: async () => ({ id: '1', title: 'Гайд', text: 'Полный текст статьи', url: `${origin}/guide/` }) };
  const server = createHttpServer({ library, issuer, resource, authorize });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  const local = `http://127.0.0.1:${server.address().port}`;
  const fetchFn = async (input, init) => {
    const request = new Request(input, init); const url = new URL(request.url);
    assert.equal(url.origin, origin, 'OAuth must not fetch a user-supplied external URL');
    if (url.pathname === '/mcp' || url.pathname.startsWith('/.well-known/oauth-protected-resource')) {
      return fetch(`${local}${url.pathname}${url.search}`, request);
    }
    return reader(request);
  };
  let clientInformation, tokens, codeVerifier, authorizationUrl, discoveryState;
  const provider = {
    redirectUrl: 'http://127.0.0.1:3456/callback',
    clientMetadata: { client_name: 'SDK test', redirect_uris: ['http://127.0.0.1:3456/callback'], token_endpoint_auth_method: 'none', grant_types: ['authorization_code', 'refresh_token'], response_types: ['code'] },
    state: () => 'sdk-state', clientInformation: () => clientInformation, saveClientInformation: value => { clientInformation = value; },
    tokens: () => tokens, saveTokens: value => { tokens = value; }, codeVerifier: () => codeVerifier,
    saveCodeVerifier: value => { codeVerifier = value; }, redirectToAuthorization: value => { authorizationUrl = value; },
    discoveryState: () => discoveryState, saveDiscoveryState: value => { discoveryState = value; },
  };
  const client = new Client({ name: 'oauth-integration', version: '1' });
  try {
    assert.equal(await auth(provider, { serverUrl: resource, fetchFn }), 'REDIRECT');
    assert.equal(authorizationUrl.origin, origin); assert.equal(authorizationUrl.searchParams.get('code_challenge_method'), 'S256');
    const consent = await reader(new Request(authorizationUrl, { headers: { cookie } }));
    assert.equal(consent.status, 200); const html = await consent.text();
    const input = name => html.match(new RegExp(`name="${name}" value="([A-Za-z0-9_-]+)"`))[1];
    const approval = await reader(new Request(`${issuer}/authorize`, { method: 'POST', headers: { cookie, Origin: origin, 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ request: input('request'), approval_token: input('approval_token'), decision: 'approve' }) }));
    assert.equal(approval.status, 303); const callback = new URL(approval.headers.get('location'));
    assert.equal(callback.searchParams.get('state'), 'sdk-state'); assert.equal(callback.searchParams.get('iss'), issuer);
    assert.equal(await auth(provider, { serverUrl: resource, authorizationCode: callback.searchParams.get('code'), iss: issuer, fetchFn }), 'AUTHORIZED');
    assert.equal(await authorize(`Bearer ${tokens.access_token}`), 'admin-1');
    await client.connect(new StreamableHTTPClientTransport(new URL(resource), { requestInit: { headers: { Authorization: `Bearer ${tokens.access_token}` } }, fetch: fetchFn }));
    assert.equal((await client.listTools()).tools.length, 4);
    assert.match((await client.callTool({ name: 'fetch', arguments: { id: '1' } })).content[0].text, /Полный текст статьи/);
    admin = false;
    assert.equal((await fetchFn(resource, { method: 'POST', headers: { Authorization: `Bearer ${tokens.access_token}`, 'Content-Type': 'application/json' }, body: '{}' })).status, 401);
    available = false; await assert.rejects(authorize(`Bearer ${tokens.access_token}`), /provider_down/);
  } finally { await client.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); store.close(); }
});
