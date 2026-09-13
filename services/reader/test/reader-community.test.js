import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { createCommunity } from '../community.js';
import { createReaderHandler } from '../http.js';

const options = { origin: 'https://test.hs-manacost.ru', issuer: 'https://hearthpulse.net/identity',
  deployment: 'staging', clientId: 'manacost-reader-staging', clientSecret: 'x'.repeat(43) };
const config = { READER_COMMENTS_ENABLED: '1', READER_EDITORIAL_KEY: 'y'.repeat(43),
  READER_EDITORIAL_USERNAME: 'reader-editorial', READER_EDITORIAL_PASSWORD: 'z'.repeat(43) };
const productionOptions = { ...options, origin: 'https://hs-manacost.ru', deployment: 'production',
  clientId: 'manacost-reader-production' };

test('comments default OFF creates no schema and invalid/mixed config cannot migrate', t => {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) }); t.after(() => store.close());
  new ReaderProfiles({ db: store.db, issuer: options.issuer });
  const tables = () => store.db.prepare("SELECT name FROM sqlite_master WHERE name LIKE 'reader_comment%'").all();
  assert.equal(createCommunity({ options, db: store.db, env: {} }), null);
  assert.deepEqual(tables(), []);
  for (const overrides of [{ deployment: 'production' }, { origin: 'https://hs-manacost.ru' },
    { issuer: 'https://test.hearthpulse.net/identity' }, { clientId: 'other' }]) {
    assert.throws(() => createCommunity({ options: { ...options, ...overrides }, db: store.db, env: config }));
    assert.deepEqual(tables(), []);
  }
  assert.throws(() => createCommunity({ options, db: store.db, env: { READER_COMMENTS_ENABLED: '1' } }));
  assert.deepEqual(tables(), []);
  assert.ok(createCommunity({ options, db: store.db, env: config }).comments);
  assert.ok(tables().length >= 4);
  assert.ok(createCommunity({ options, db: store.db, env: config }).comments);
});

test('production community requires the exact production bridge and a separate explicit gate', t => {
  const store = new ReaderStore({ encryptionKey: randomBytes(32) }); t.after(() => store.close());
  new ReaderProfiles({ db: store.db, issuer: options.issuer });
  const enabled = { ...config, READER_ALLOW_PRODUCTION_COMMUNITY: '1' };
  assert.throws(() => createCommunity({ options: productionOptions, db: store.db, env: config }));
  for (const overrides of [{ origin: 'https://test.hs-manacost.ru' }, { origin: 'https://hs-manacost.com' },
    { deployment: 'staging' }, { issuer: 'https://test.hearthpulse.net/identity' }, { clientId: 'manacost-reader-staging' }]) {
    assert.throws(() => createCommunity({ options: { ...productionOptions, ...overrides }, db: store.db, env: enabled }));
  }
  const community = createCommunity({ options: productionOptions, db: store.db, env: enabled });
  assert.ok(community.comments);
  const handlerConfig = { origin: productionOptions.origin, store, profiles: new ReaderProfiles({ db: store.db, issuer: options.issuer }),
    identity: {}, community, csrfKey: randomBytes(32) };
  assert.throws(() => createReaderHandler(handlerConfig));
  assert.doesNotThrow(() => createReaderHandler({ ...handlerConfig, communityProductionEnabled: true }));
});
