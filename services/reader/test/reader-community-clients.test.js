import assert from 'node:assert/strict';
import test from 'node:test';
import { createHmac } from 'node:crypto';
import { createEditorialClient, createPaidTitleClient } from '../community-clients.js';

const editorial = { key: 'x'.repeat(43), username: 'synthetic-editorial', password: 'y'.repeat(43) };
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
