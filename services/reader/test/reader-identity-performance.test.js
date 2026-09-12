import assert from 'node:assert/strict';
import test from 'node:test';
import { createIdentityClient } from '../identity-client.js';

const options = {
  origin: 'https://test.hs-manacost.ru',
  issuer: 'https://identity.test/identity',
  deployment: 'test',
  clientId: 'reader',
  clientSecret: 'a'.repeat(43),
};

test('parallel profile reads share one userinfo request and prime a short write verification', async () => {
  const paths = [];
  const transport = async url => {
    const path = new URL(url).pathname;
    paths.push(path);
    await new Promise(resolve => setTimeout(resolve, 20));
    if (path.endsWith('/token/introspection')) return Response.json({
      active: true, sub: 'reader-one', client_id: 'reader', exp: Math.floor(Date.now() / 1000) + 300,
    });
    return Response.json({ sub: 'reader-one', name: 'Читатель' });
  };
  const identity = createIdentityClient(options, transport);
  const [first, second] = await Promise.all([
    identity.profile('access-token', 'reader-one'),
    identity.profile('access-token', 'reader-one'),
  ]);
  assert.deepEqual(first, { displayName: 'Читатель' });
  assert.deepEqual(second, first);
  assert.equal(await identity.verify('access-token', 'reader-one'), true);
  assert.deepEqual(paths, ['/identity/me']);
});

test('cached profile text survives while private reads still recheck short-lived authorization', async t => {
  const realNow = Date.now;
  let now = 1_000_000;
  Date.now = () => now;
  t.after(() => { Date.now = realNow; });
  const paths = [];
  const identity = createIdentityClient(options, async url => {
    const path = new URL(url).pathname;
    paths.push(path);
    if (path.endsWith('/token/introspection')) return Response.json({
      active: true, sub: 'reader-one', client_id: 'reader', exp: Math.floor(now / 1000) + 300,
    });
    return Response.json({ sub: 'reader-one', name: 'Читатель' });
  });
  assert.deepEqual(await identity.profile('access-token', 'reader-one'), { displayName: 'Читатель' });
  now += 5_001;
  assert.deepEqual(await identity.profile('access-token', 'reader-one'), { displayName: 'Читатель' });
  assert.deepEqual(paths, ['/identity/me', '/identity/token/introspection']);
});

test('authorization freshness starts before a slow upstream verification', async t => {
  const realNow = Date.now;
  let now = 2_000_000;
  Date.now = () => now;
  t.after(() => { Date.now = realNow; });
  let introspections = 0;
  const identity = createIdentityClient(options, async url => {
    assert.match(new URL(url).pathname, /token\/introspection$/);
    introspections += 1;
    now += 4_900;
    return Response.json({
      active: true, sub: 'reader-one', client_id: 'reader', exp: Math.floor(now / 1000) + 300,
    });
  });
  assert.equal(await identity.verify('access-token', 'reader-one'), true);
  now += 101;
  assert.equal(await identity.verify('access-token', 'reader-one'), true);
  assert.equal(introspections, 2, 'a slow response must not extend the five-second authorization window');
});

test('a rejected userinfo token remains an unauthenticated session instead of a gateway error', async () => {
  const identity = createIdentityClient(options, async () => new Response(
    JSON.stringify({ error: 'invalid_token' }),
    { status: 401, headers: { 'content-type': 'application/json' } },
  ));

  assert.equal(await identity.profile('revoked-access-token', 'reader-one'), null);
});
