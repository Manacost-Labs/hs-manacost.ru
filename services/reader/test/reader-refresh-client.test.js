import assert from 'node:assert/strict';
import test from 'node:test';
import { createIdentityClient } from '../identity-client.js';

const options = { origin: 'https://test.hs-manacost.ru', issuer: 'https://identity.example/identity',
  clientId: 'manacost-reader-staging', clientSecret: 'x'.repeat(43), deployment: 'test' };
const access = { active: true, sub: 'reader', client_id: options.clientId, exp: Math.floor(Date.now() / 1000) + 300 };
const tokens = { access_token: 'new-access', refresh_token: 'new-refresh', token_type: 'Bearer', expires_in: 300 };

test('pinned OIDC client refreshes server-side and validates subject/client/expiry through introspection', async () => {
  const paths = [];
  const identity = createIdentityClient(options, async (url, init) => {
    const path = new URL(url).pathname; paths.push(path);
    assert.equal(init.redirect, 'error');
    assert.ok(new Headers(init.headers).get('authorization')?.startsWith('Basic '));
    const body = new URLSearchParams(init.body);
    if (path.endsWith('/token')) {
      assert.equal(body.get('grant_type'), 'refresh_token');
      assert.equal(body.get('refresh_token'), 'old-refresh');
      return Response.json(tokens);
    }
    assert.equal(body.get('token'), 'new-access');
    return Response.json(access);
  });
  assert.deepEqual(await identity.refresh('old-refresh', 'reader'), {
    subject: 'reader', accessToken: 'new-access', refreshToken: 'new-refresh', expiresIn: 300,
  });
  assert.deepEqual(paths, ['/identity/token', '/identity/token/introspection']);
});

test('inactive, foreign subject/client and expired refreshed credentials are rejected', async () => {
  for (const bad of [{ ...access, active: false }, { ...access, sub: 'someone-else' },
    { ...access, client_id: 'other-client' }, { ...access, exp: 1 }]) {
    const identity = createIdentityClient(options, async url => Response.json(String(url).endsWith('/token') ? tokens : bad));
    await assert.rejects(identity.refresh('old-refresh', 'reader'), /Invalid refreshed identity/);
  }
});

test('malformed token responses and invalid_grant do not trigger a retry', async () => {
  for (const body of [{ error: 'invalid_grant' }, { ...tokens, refresh_token: undefined },
    { ...tokens, expires_in: 0 }, { ...tokens, id_token: 'untrusted.jwt.value' }]) {
    let calls = 0;
    const identity = createIdentityClient(options, async () => { calls++; return Response.json(body, { status: body.error ? 400 : 200 }); });
    await assert.rejects(identity.refresh('old-refresh', 'reader'));
    assert.equal(calls, 1);
  }
});

test('durable revocation sends no access-only hint when revoking a refresh-token family', async () => {
  const identity = createIdentityClient(options, async (url, init) => {
    assert.equal(String(url), 'https://identity.example/identity/token/revocation');
    const body = new URLSearchParams(init.body);
    assert.equal(body.get('token'), 'old-refresh');
    assert.equal(body.has('token_type_hint'), false);
    return new Response(null, { status: 200 });
  });
  await identity.revoke('old-refresh');
});
