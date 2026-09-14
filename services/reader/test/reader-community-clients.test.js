import assert from 'node:assert/strict';
import test from 'node:test';
import { createHmac } from 'node:crypto';
import { createEditorialClient, createFavoriteEditorialClient, createPaidTitleClient, createReaderPermissionsClient } from '../community-clients.js';

const editorial = { key: 'x'.repeat(43), username: 'synthetic-editorial', password: 'y'.repeat(43),
  origin: 'https://test.hs-manacost.ru' };

test('private HearthPulse community clients accept only exact staging or production Reader ids', () => {
  const clientSecret = 'z'.repeat(43);
  for (const clientId of ['manacost-reader-staging', 'manacost-reader-production']) {
    assert.doesNotThrow(() => createPaidTitleClient({ clientId, clientSecret }));
    assert.doesNotThrow(() => createReaderPermissionsClient({ clientId, clientSecret }));
  }
  for (const clientId of ['reader', 'manacost-reader-production-preview', '']) {
    assert.throws(() => createPaidTitleClient({ clientId, clientSecret }));
    assert.throws(() => createReaderPermissionsClient({ clientId, clientSecret }));
  }
});
test('editorial request is signed, fixed-destination and rejects mismatched metadata', async () => {
  let called;
  const client = createEditorialClient(editorial, async (url, options) => {
    called = { url, options };
    return Response.json({ site: 'test.hs-manacost.ru', threads: [{ postId: 17, allowed: true, title: 'Статья', path: '/article/' }] });
  });
  assert.equal((await client.get([17])).get(17).path, '/article/');
  assert.equal(called.url, 'https://test.hs-manacost.ru/wp-json/manacost-reader/v1/threads');
  assert.equal(called.options.redirect, 'error');
  assert.equal(called.options.headers['x-reader-signature'], createHmac('sha256', editorial.key).update(`POST\n/manacost-reader/v1/threads\n${called.options.headers['x-reader-time']}\n${called.options.body}`).digest('hex'));
  for (const body of [
    { site: 'hs-manacost.ru', threads: [] },
    { site: 'test.hs-manacost.ru', threads: [{ postId: 18, allowed: false }] },
    { site: 'test.hs-manacost.ru', threads: [{ postId: 17, allowed: true, title: 'Private', path: '//evil.test/' }] },
    { site: 'test.hs-manacost.ru', threads: [{ postId: 17, allowed: true, title: 'Private', path: '/wp-admin/' }] },
  ]) await assert.rejects(createEditorialClient(editorial, async () => Response.json(body)).get([17]));
  await assert.rejects(client.get([17, 17]));
});

test('editorial clients use only the exact configured Manacost origin', async () => {
  for (const [create, route] of [[createEditorialClient, 'threads'], [createFavoriteEditorialClient, 'favorites']]) {
    let called;
    const client = create({ ...editorial, origin: 'https://hs-manacost.ru' }, async (url) => {
      called = url;
      return Response.json({ site: 'hs-manacost.ru', threads: [{ postId: 17, allowed: false }] });
    });
    assert.equal((await client.get([17])).get(17).allowed, false);
    assert.equal(called, `https://hs-manacost.ru/wp-json/manacost-reader/v1/${route}`);
  }
  for (const origin of ['https://hs-manacost.com', 'https://evil.test', 'https://hs-manacost.ru.evil.test']) {
    assert.throws(() => createEditorialClient({ ...editorial, origin }));
  }
  await assert.rejects(createEditorialClient({ ...editorial, origin: 'https://hs-manacost.ru' }, async () =>
    Response.json({ site: 'test.hs-manacost.ru', threads: [{ postId: 17, allowed: false }] })).get([17]));
});

test('editorial clients may use only their matching loopback transport endpoint', async () => {
  for (const [origin, editorialOrigin] of [
    ['https://test.hs-manacost.ru', 'http://127.0.0.1:18185'],
    ['https://hs-manacost.ru', 'http://127.0.0.1:18184'],
  ]) {
    let called;
    const client = createEditorialClient({ ...editorial, origin, editorialOrigin }, async url => {
      called = url;
      return Response.json({ site: new URL(origin).host, threads: [{ postId: 17, allowed: false }] });
    });
    assert.equal((await client.get([17])).get(17).allowed, false);
    assert.equal(called, `${editorialOrigin}/wp-json/manacost-reader/v1/threads`);
  }
  for (const editorialOrigin of ['http://127.0.0.1:18184/', 'https://127.0.0.1:18184',
    'http://127.0.0.1:18185', 'http://localhost:18184', 'https://hs-manacost.ru']) {
    assert.throws(() => createEditorialClient({ ...editorial, origin: 'https://hs-manacost.ru', editorialOrigin }));
  }
});

test('editorial failures never grant visibility and oversized responses are bounded', async () => {
  for (const response of [new Response('', { status: 401 }), new Response('', { status: 429 }), new Response('', { status: 503 }), new Response('not json'), Response.json({ enormous: 'x'.repeat(33000) })]) {
    await assert.rejects(createEditorialClient(editorial, async () => response).get([17]));
  }
});

test('paid title is subject-bound, fresh, and fails closed without breaking comments', async () => {
  const options = { clientId: 'manacost-reader-staging', clientSecret: 'z'.repeat(43) };
  const now = Date.now();
  const paid = { subject: 'reader-a', paid: true, checkedAt: now - 1000, validUntil: now + 60000 };
  const client = body => createPaidTitleClient(options, async (url, request) => {
    assert.equal(url, 'https://hearthpulse.net/identity/reader-entitlements');
    assert.equal(request.redirect, 'error');
    assert.equal(Object.hasOwn(request.headers, 'cookie'), false);
    return Response.json(body);
  });
  assert.equal((await client({ entitlements: [paid] }).get(['reader-a'])).get('reader-a'), true);
  for (const item of [
    { ...paid, subject: 'reader-b' }, { ...paid, paid: 'true' },
    { ...paid, checkedAt: now - 1800001 }, { ...paid, validUntil: now - 1 },
    { ...paid, validUntil: now + 1800001 }, { ...paid, checkedAt: now + 60000 },
  ]) assert.notEqual((await client({ entitlements: [item] }).get(['reader-a'])).get('reader-a'), true);
  const unavailable = createPaidTitleClient(options, async () => { throw new Error('private provider information'); });
  assert.equal((await unavailable.get(['reader-a'])).size, 0);
});

test('permissions use the exact authenticated server bridge and reject stale-shaped or spoofed records', async () => {
  const options = { clientId: 'manacost-reader-staging', clientSecret: 'z'.repeat(43) };
  let calls = 0; let allowed = true;
  const client = createReaderPermissionsClient(options, async (url, request) => {
    calls++;
    assert.equal(url, 'https://hearthpulse.net/identity/reader-permissions');
    assert.equal(request.redirect, 'error');
    assert.equal(request.headers.authorization, `Basic ${Buffer.from(`${options.clientId}:${options.clientSecret}`).toString('base64')}`);
    assert.equal(Object.hasOwn(request.headers, 'cookie'), false);
    return Response.json({ permissions: [{ subject: 'admin', canModerateComments: allowed }] });
  });
  assert.equal((await client.get(['admin'])).get('admin'), true);
  allowed = false;
  assert.equal((await client.get(['admin'])).get('admin'), false);
  assert.equal(calls, 2, 'permissions must not be cached');
  for (const body of [
    { permissions: [{ subject: 'other', canModerateComments: true }] },
    { permissions: [{ subject: 'admin', canModerateComments: 'true' }] },
    { permissions: [{ subject: 'admin', canModerateComments: true, role: 'admin' }] },
    { permissions: [] }, { permissions: [{ subject: 'admin', canModerateComments: true }], extra: true },
  ]) await assert.rejects(createReaderPermissionsClient(options, async () => Response.json(body)).get(['admin']));
  await assert.rejects(client.get(['admin', 'admin']));
  await assert.rejects(createReaderPermissionsClient(options, async () => new Response('down', { status: 503 })).get(['admin']));
  await assert.rejects(createReaderPermissionsClient(options, async () => Response.json({ enormous: 'x'.repeat(33000) })).get(['admin']));
});
