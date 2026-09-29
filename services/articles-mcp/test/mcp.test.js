import test from 'node:test';
import assert from 'node:assert/strict';
import { once } from 'node:events';
import { Client, InMemoryTransport, StreamableHTTPClientTransport } from '@modelcontextprotocol/client';
import { createMcpServer } from '../mcp.js';
import { createHttpServer } from '../http.js';
import { createAuthorization } from '../auth.js';
import { generateKeyPair, SignJWT } from 'jose';

const library = { search: async () => ({ results: [{ id: '1', title: 'Гайд', url: 'https://hs-manacost.ru/guide/' }] }),
  fetch: async () => ({ id: '1', title: 'Гайд', text: 'Полный текст', url: 'https://hs-manacost.ru/guide/' }), list: async () => ({ results: [], next_cursor: null }) };
const resource = 'https://mcp.example.org/mcp'; const issuer = 'https://identity.example.org';

test('official client discovers only read tools and rejects invalid inputs', async () => {
  const [clientTransport, serverTransport] = InMemoryTransport.createLinkedPair();
  const server = createMcpServer(library); const client = new Client({ name: 'test', version: '1' });
  try {
    await server.connect(serverTransport); await client.connect(clientTransport);
    const { tools } = await client.listTools(); assert.deepEqual(tools.map(t => t.name).sort(), ['fetch', 'list_articles', 'list_categories', 'search']);
    assert.ok(tools.every(t => t.annotations.readOnlyHint));
    const result = await client.callTool({ name: 'fetch', arguments: { id: '1' } }); assert.match(result.content[0].text, /Полный текст/);
    const invalid = await client.callTool({ name: 'fetch', arguments: { id: '../secret' } }); assert.equal(invalid.isError, true);
  } finally { await client.close(); await server.close(); }
});

test('OAuth validates token and current Hearthpulse admin role; revocation fails closed', async () => {
  const { publicKey, privateKey } = await generateKeyPair('RS256');
  let isAdmin = true;
  const permissions = { get: async subjects => new Map(subjects.map(subject => [subject, subject === 'editor' && isAdmin])) };
  const auth = createAuthorization({ issuer, resource, jwksUrl: `${issuer}/jwks`, permissions }, publicKey);
  const token = (claims = {}, audience = resource) => new SignJWT({ scope: 'articles:read', ...claims }).setProtectedHeader({ alg: 'RS256', typ: 'at+jwt' })
    .setIssuer(issuer).setSubject(claims.sub ?? 'editor').setAudience(audience).setIssuedAt().setExpirationTime(claims.exp ?? '5m').sign(privateKey);
  assert.equal(await auth(`Bearer ${await token()}`), 'editor');
  await assert.rejects(auth(`Bearer ${await token({}, 'https://wrong.example/')}`));
  await assert.rejects(auth(`Bearer ${await token({ scope: 'write' })}`));
  await assert.rejects(auth(`Bearer ${await token({ exp: 1 })}`));
  await assert.rejects(auth(`Bearer ${await token({ sub: 'another-user' })}`));
  await assert.rejects(auth('Bearer not-a-token'));
  isAdmin = false; await assert.rejects(auth(`Bearer ${await token()}`), /forbidden/);
  permissions.get = async () => { throw new Error('provider_down'); };
  await assert.rejects(auth(`Bearer ${await token()}`), /provider_down/);
});

test('HTTP requires authentication, rejects browser origins and works with official remote client', async () => {
  const server = createHttpServer({ library, resource, issuer, authorize: async header => { if (header !== 'Bearer fixture') throw new Error(); return 'editor'; } });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  const base = `http://127.0.0.1:${server.address().port}`;
  const client = new Client({ name: 'remote-test', version: '1' });
  try {
    assert.equal((await fetch(`${base}/mcp`, { method: 'POST' })).status, 401);
    assert.equal((await fetch(`${base}/mcp`, { method: 'POST', headers: { Origin: 'https://evil.example' } })).status, 403);
    const legacy = await fetch(`${base}/mcp`, { method: 'POST', headers: { Authorization: 'Bearer fixture', 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' }, body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2025-11-25', capabilities: {}, clientInfo: { name: 'legacy-probe', version: '1' } } }) });
    assert.equal(legacy.status, 200); assert.equal((await legacy.json()).result.protocolVersion, '2025-11-25');
    const meta = await (await fetch(`${base}/.well-known/oauth-protected-resource/mcp`)).json(); assert.equal(meta.resource, resource);
    await client.connect(new StreamableHTTPClientTransport(new URL(`${base}/mcp`), { authProvider: { token: async () => 'fixture' } }));
    const result = await client.callTool({ name: 'search', arguments: { query: 'маг' } }); assert.match(result.content[0].text, /Гайд/);
  } finally { await client.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
});
