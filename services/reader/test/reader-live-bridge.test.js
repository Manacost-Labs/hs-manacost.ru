import assert from 'node:assert/strict';
import test from 'node:test';
import { createIdentityClient, validateIdentityClient } from '../identity-client.js';

const secret = 'a'.repeat(43);
const bridge = {
  deployment: 'staging',
  origin: 'https://test.hs-manacost.ru',
  issuer: 'https://hearthpulse.net/identity',
  clientId: 'manacost-reader-staging',
  clientSecret: secret,
};

test('production identity bridge requires its explicit staging tuple and enabled option', () => {
  assert.throws(() => validateIdentityClient(bridge), /Mixed identity environments/);
  assert.throws(() => validateIdentityClient({ ...bridge, allowProductionIdentityForStaging: false }), /Mixed identity environments/);
  assert.doesNotThrow(() => validateIdentityClient({ ...bridge, allowProductionIdentityForStaging: true }));
  assert.equal(createIdentityClient({ ...bridge, allowProductionIdentityForStaging: true }).profileUrl, 'https://hearthpulse.net/?login');
});

test('production identity bridge rejects near matches even when the option is enabled', () => {
  const invalid = [
    { ...bridge, origin: 'https://test.hs-manacost.ru:443' },
    { ...bridge, origin: 'https://test2.hs-manacost.ru' },
    { ...bridge, issuer: 'https://hearthpulse.net/identity/' },
    { ...bridge, issuer: 'https://test.hearthpulse.net/identity' },
    { ...bridge, clientId: 'manacost-reader-staging-extra' },
    { ...bridge, deployment: 'test' },
    { ...bridge, origin: 'not-a-url' },
  ];
  for (const options of invalid) assert.throws(() => validateIdentityClient({ ...options, allowProductionIdentityForStaging: true }));
});

test('ordinary production and test identity configurations retain their existing validity', () => {
  assert.doesNotThrow(() => validateIdentityClient({
    deployment: 'production', origin: 'https://hs-manacost.ru', issuer: 'https://hearthpulse.net/identity', clientId: 'manacost-reader-production', clientSecret: secret,
  }));
  assert.doesNotThrow(() => validateIdentityClient({
    deployment: 'test', origin: 'https://test.hs-manacost.ru', issuer: 'https://identity.test/identity', clientId: 'reader-test', clientSecret: secret,
  }));
});

test('production Manacost origins never accept staging or test deployment with an isolated issuer', () => {
  for (const origin of ['https://hs-manacost.ru', 'https://hs-manacost.com']) {
    for (const deployment of ['staging', 'test']) {
      assert.throws(() => validateIdentityClient({ ...bridge, origin, deployment,
        issuer: 'https://test.hearthpulse.net/identity', allowProductionIdentityForStaging: false }));
    }
  }
});
