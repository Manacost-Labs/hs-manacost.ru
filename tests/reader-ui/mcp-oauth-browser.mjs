import assert from 'node:assert/strict';
import { randomBytes, generateKeyPairSync, createHash } from 'node:crypto';
import { createServer } from 'node:http';
import { once } from 'node:events';
import { chromium } from 'playwright';
import { ReaderStore } from '../../services/reader/core.js';
import { createReaderHandler } from '../../services/reader/http.js';
import { createMcpOAuthRoutes } from '../../services/reader/mcp-oauth.js';

// Real fixture server and form POSTs, synthetic identity only. The loopback HTTP
// adapter maps its own origin to the canonical HTTPS boundary; no live login/data.
const origin = 'https://test.hs-manacost.ru';
const store = new ReaderStore({ encryptionKey: randomBytes(32) });
let admin = true;
const identity = { verify: async () => true };
const permissions = { get: async ids => new Map(ids.map(id => [id, admin])) };
const mcpOAuth = createMcpOAuthRoutes({ origin, store, identity, permissions,
  signingKey: generateKeyPairSync('rsa', { modulusLength: 2048 }).privateKey });
const handle = createReaderHandler({ origin, store, identity, csrfKey: randomBytes(32), mcpOAuth });
const cookie = `__Host-manacost_reader=${store.createSession({ userId: 'admin-1', upstreamToken: 'synthetic-private', ttlMs: 300000 }).id}`;
let local; const approvals = [];
const server = createServer(async (req, res) => {
  if (req.url.startsWith('/callback')) {
    const url = new URL(req.url, local); approvals.push(url.searchParams.has('code') ? 'approved' : 'denied');
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' }); res.end('<html lang="ru"><title>Клиент</title><main><h1>Возврат в ИИ-клиент</h1></main></html>'); return;
  }
  const chunks = []; for await (const part of req) chunks.push(part);
  const headers = { ...req.headers };
  if (headers.origin === local) headers.origin = origin;
  const response = await handle(new Request(`${origin}${req.url}`, { method: req.method, headers,
    ...(req.method === 'POST' ? { body: Buffer.concat(chunks) } : {}) }));
  res.writeHead(response.status, Object.fromEntries(response.headers)); res.end(Buffer.from(await response.arrayBuffer()));
});
server.listen(0, '127.0.0.1'); await once(server, 'listening'); local = `http://127.0.0.1:${server.address().port}`;
async function consentUrl() {
  const response = await handle(new Request(`${origin}/mcp-oauth/register`, { method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ client_name: 'ИИ-клиент <test> '.repeat(5), redirect_uris: [`${local}/callback`] }) }));
  assert.equal(response.status, 201); const { client_id } = await response.json();
  return `${local}/mcp-oauth/authorize?${new URLSearchParams({ client_id, redirect_uri: `${local}/callback`, scope: 'articles:read',
    response_type: 'code', resource: `${origin}/mcp`, code_challenge: createHash('sha256').update(randomBytes(32)).digest('base64url'), code_challenge_method: 'S256' })}`;
}
let browser;
try {
  browser = await chromium.launch({ headless: true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const context = await browser.newContext({ extraHTTPHeaders: { cookie } });
  const page = await context.newPage();
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 }); await page.goto(await consentUrl());
    assert.equal(await page.getByRole('heading', { level: 1 }).count(), 1);
    assert.ok(await page.getByRole('button', { name: 'Разрешить чтение' }).isVisible());
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `overflow at ${width}`);
    assert.ok(await page.getByRole('button', { name: 'Отказать' }).evaluate(el => el.getBoundingClientRect().height >= 44));
  }
  await page.setViewportSize({ width: 768, height: 1024 });
  await page.evaluate(() => { document.body.style.zoom = '2'; });
  assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), '200% CSS zoom');
  await page.keyboard.press('Tab');
  assert.equal(await page.evaluate(() => document.activeElement.textContent), 'Разрешить чтение');
  assert.equal(await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle), 'solid');
  await page.keyboard.press('Tab');
  assert.equal(await page.evaluate(() => document.activeElement.textContent), 'Отказать');
  await page.keyboard.press('Enter'); await page.waitForURL(`${local}/callback?**`);
  assert.equal(approvals.at(-1), 'denied');
  await page.goto(await consentUrl()); await page.getByRole('button', { name: 'Разрешить чтение' }).click();
  await page.waitForURL(`${local}/callback?**`); assert.equal(approvals.at(-1), 'approved');
  admin = false; const rejected = await page.goto(await consentUrl());
  assert.equal(rejected.status(), 403); assert.match(await page.getByRole('alert').innerText(), /администраторам/);
  const anonymous = await browser.newContext({ viewport: { width: 320, height: 800 } }); const login = await anonymous.newPage(); await login.goto(await consentUrl());
  assert.ok(await login.getByRole('link', { name: 'Войти через Hearthpulse' }).isVisible());
  assert.ok(await login.getByRole('link', { name: 'Войти через Hearthpulse' }).evaluate(el => el.getBoundingClientRect().height >= 44), 'mobile login target');
  await anonymous.close(); await context.close();
  process.stdout.write('MCP OAuth browser: mobile widths, 200% CSS zoom, keyboard approve/deny, denied role and login PASS\n');
} finally { await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); store.close(); }
