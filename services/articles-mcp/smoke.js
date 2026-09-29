// Read-only, bounded WordPress fixture. Never emit article bodies or credentials.
import assert from 'node:assert/strict';
import { Client, InMemoryTransport } from '@modelcontextprotocol/client';
import { WordPressSource } from './source.js';
import { ArticleStore } from './store.js';
import { ArticleLibrary } from './library.js';
import { createMcpServer } from './mcp.js';
import { syncArticles } from './sync.js';

const origin = process.env.MCP_SOURCE_ORIGIN;
if (origin !== 'https://test.hs-manacost.ru') throw new Error('staging_only');
const user = process.env.STAGING_HTTP_USER; const password = process.env.STAGING_HTTP_PASSWORD;
if (!user || !password) throw new Error('staging_http_credentials_required');
const source = new WordPressSource(origin, { authorization: `Basic ${Buffer.from(`${user}:${password}`).toString('base64')}` });
const { data } = await source.request({ per_page: 20, orderby: 'id', order: 'desc', _fields: 'id,modified_gmt,content.protected' });
const catalog = data.filter(row => !row.content.protected);
assert.ok(catalog.length > 0, 'published staging fixture required');
source.catalog = async () => catalog;
const store = new ArticleStore(':memory:', origin);
const server = createMcpServer(new ArticleLibrary(store, source));
const client = new Client({ name: 'manacost-staging-probe', version: '1' });
try {
  const stats = await syncArticles(store, source);
  const [clientTransport, serverTransport] = InMemoryTransport.createLinkedPair();
  await server.connect(serverTransport); await client.connect(clientTransport);
  assert.equal((await client.listTools()).tools.length, 4);
  const times = []; let bytes;
  for (let i = 0; i < 5; i++) {
    const start = performance.now();
    const result = await client.callTool({ name: 'fetch', arguments: { id: String(catalog[0].id) } });
    assert.ok(!result.isError); const article = JSON.parse(result.content[0].text);
    assert.ok(article.text.length > 0); assert.equal(new URL(article.url).origin, origin);
    bytes = Buffer.byteLength(article.text); times.push(Math.round(performance.now() - start));
  }
  const title = store.get(String(catalog[0].id)).title.match(/[\p{L}\p{N}]{3,}/u)?.[0];
  assert.ok(title);
  const search = await client.callTool({ name: 'search', arguments: { query: title } });
  assert.ok(JSON.parse(search.content[0].text).results.length > 0);
  const categories = await client.callTool({ name: 'list_categories', arguments: {} }); assert.ok(!categories.isError);
  console.log(JSON.stringify({ ...stats, fetch_ms: times, article_bytes: bytes, source: 'staging', tools: 4 }));
} finally { await client.close(); await server.close(); store.close(); }
