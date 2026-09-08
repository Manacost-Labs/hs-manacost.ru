import assert from 'node:assert/strict';
import { request } from 'node:http';
import test from 'node:test';
import { createReaderServer } from '../server.js';

async function fixture(t, handle) {
  const origin = 'https://test.hs-manacost.ru';
  const server = createReaderServer({ origin, handle });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  t.after(() => new Promise(resolve => server.close(resolve)));
  return (path, body, headers = {}, method = 'PATCH') => new Promise((resolve, reject) => {
    const requestHeaders = { host: new URL(origin).host, 'content-length': Buffer.byteLength(body), ...headers };
    if (headers['transfer-encoding']) delete requestHeaders['content-length'];
    const req = request({ hostname: '127.0.0.1', port: server.address().port, path, method,
      headers: requestHeaders }, res => {
      const chunks = []; res.on('data', chunk => chunks.push(chunk));
      res.on('end', () => resolve({ status: res.statusCode, body: Buffer.concat(chunks).toString(), headers: res.headers }));
    });
    req.on('error', reject); req.end(body);
  });
}

test('native HTTP adapter forwards exact JSON and bounded binary upload bodies', async t => {
  const call = await fixture(t, async req => new Response(await req.arrayBuffer(), { status: 200 }));
  const json = JSON.stringify({ displayName: 'Маг Манакоста', bio: 'Привет!' });
  assert.equal((await call('/reader-api/v1/profile', json, { 'content-type': 'application/json' })).body, json);
  const bytes = Buffer.alloc(8192, 65);
  assert.equal((await call('/reader-api/v1/profile/avatar', bytes, { 'content-type': 'image/png' }, 'PUT')).body.length, 8192);
});

test('chunked uploads cannot bypass native request body limits', async t => {
  let dispatched = 0;
  const call = await fixture(t, async () => { dispatched++; return new Response('unexpected'); });
  const response = await call('/reader-api/v1/profile', Buffer.alloc(8192), { 'transfer-encoding': 'chunked' });
  assert.equal(response.status, 413);
  assert.equal(dispatched, 0);
});

test('at most two image bodies remain in flight while the handler is pending', async t => {
  let release; let ready; let dispatched = 0;
  const pending = new Promise(resolve => { release = resolve; });
  const bothStarted = new Promise(resolve => { ready = resolve; });
  const call = await fixture(t, async () => {
    if (++dispatched === 2) ready();
    await pending;
    return new Response('ok');
  });
  const upload = () => call('/reader-api/v1/profile/avatar', Buffer.alloc(8192), {}, 'PUT');
  const first = upload(); const second = upload();
  try {
    await bothStarted;
    assert.equal((await upload()).status, 503);
    assert.equal(dispatched, 2);
  } finally { release(); await Promise.all([first, second]); }
  assert.equal((await upload()).status, 200);
});

test('native HTTP adapter rejects oversized bodies and foreign hosts before dispatch', async t => {
  let dispatched = 0;
  const call = await fixture(t, async () => { dispatched++; return new Response('unexpected'); });
  for (const [path, bytes, method] of [
    ['/reader-api/v1/profile', Buffer.alloc(4097), 'PATCH'],
    ['/reader-api/v1/profile/avatar', Buffer.alloc(4 * 1024 * 1024 + 1), 'PUT'],
    ['/reader-auth/logout', Buffer.alloc(8192), 'POST'],
    ['/reader-api/v1/profile/avatar/extra', Buffer.alloc(8192), 'PUT'],
  ]) {
    const response = await call(path, bytes, {}, method);
    assert.equal(response.status, 413);
    assert.match(response.headers['cache-control'], /no-store/);
  }
  assert.equal((await call('/reader-api/v1/profile', '{}', { host: 'evil.test' })).status, 400);
  assert.equal(dispatched, 0);
});
