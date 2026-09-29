import test from 'node:test';
import assert from 'node:assert/strict';
import { once } from 'node:events';
import { createHttpServer } from '../http.js';

test('HTTP rejects oversized bodies and rate limits an authenticated client', async () => {
  const server = createHttpServer({ library: {}, resource: 'https://mcp.example.org/mcp', issuer: 'https://identity.example.org', authorize: async () => 'fixture' });
  server.listen(0, '127.0.0.1'); await once(server, 'listening');
  const url = `http://127.0.0.1:${server.address().port}/mcp`;
  try {
    const tooLarge = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ input: 'x'.repeat(20000) }) });
    assert.equal(tooLarge.status, 413);
    for (let i = 0; i < 59; i++) await fetch(url);
    assert.equal((await fetch(url)).status, 429);
  } finally { server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
});
