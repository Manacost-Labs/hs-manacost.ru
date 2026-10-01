import assert from 'node:assert/strict';
import test from 'node:test';
import { randomBytes, generateKeyPairSync } from 'node:crypto';
import { mkdtempSync, writeFileSync, chmodSync, symlinkSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { ReaderStore } from '../core.js';
import { createMcpOAuth } from '../mcp-oauth-config.js';
import { McpOAuthStore } from '../mcp-oauth-store.js';

test('OAuth is opt-in and validates deployment, private file and RSA key before additive schema changes', () => {
  const folder = mkdtempSync(join(tmpdir(), 'reader-oauth-key-'));
  const file = join(folder, 'key.pem');
  const key = generateKeyPairSync('rsa', { modulusLength: 2048 }).privateKey.export({ type: 'pkcs8', format: 'pem' });
  writeFileSync(file, key, { mode: 0o600 });
  const store = new ReaderStore({ encryptionKey: randomBytes(32) });
  const options = { deployment: 'staging', origin: 'https://test.hs-manacost.ru', issuer: 'https://hearthpulse.net/identity',
    clientId: 'manacost-reader-staging', clientSecret: randomBytes(32).toString('base64url') };
  const env = { READER_MCP_OAUTH_ENABLED: '1', READER_MCP_OAUTH_SIGNING_KEY_FILE: file };
  const names = () => store.db.prepare("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name").all();
  try {
    const before = names();
    assert.equal(createMcpOAuth({ options, store, env: {} }), null); assert.deepEqual(names(), before);
    for (const change of [{ origin: 'https://hs-manacost.com' }, { issuer: 'https://evil.test' }, { deployment: 'production' }, { clientSecret: 'short' }]) {
      assert.throws(() => createMcpOAuth({ options: { ...options, ...change }, store, env })); assert.deepEqual(names(), before);
    }
    chmodSync(file, 0o644); assert.throws(() => createMcpOAuth({ options, store, env })); chmodSync(file, 0o600);
    symlinkSync(file, join(folder, 'link')); assert.throws(() => createMcpOAuth({ options, store, env: { ...env, READER_MCP_OAUTH_SIGNING_KEY_FILE: join(folder, 'link') } }));
    writeFileSync(file, 'invalid-key'); assert.throws(() => createMcpOAuth({ options, store, env }));
    const weak = generateKeyPairSync('rsa', { modulusLength: 1024 }).privateKey.export({ type: 'pkcs8', format: 'pem' });
    writeFileSync(file, weak); assert.throws(() => createMcpOAuth({ options, store, env })); assert.deepEqual(names(), before);
    writeFileSync(file, key);
    assert.equal(typeof createMcpOAuth({ options, store, env }), 'function');
    const migrated = names(); assert.equal(migrated.length - before.length, 6);
    createMcpOAuth({ options, store, env }); assert.deepEqual(names(), migrated);
    assert.throws(() => new McpOAuthStore(store.db, 'https://hs-manacost.ru/mcp-oauth', store.now), /issuer mismatch/);
    assert.equal(store.db.prepare("SELECT value FROM mcp_oauth_settings WHERE key='issuer'").get().value, `${options.origin}/mcp-oauth`);
    assert.deepEqual(names(), migrated);
    const state = new McpOAuthStore(store.db, `${options.origin}/mcp-oauth`, store.now);
    const document = { client: 'synthetic-client' }; const code = state.put('codes', document, 60000);
    assert.throws(() => state.redeem('codes', code, document, () => { throw new Error('issuance_failed'); }), /issuance_failed/);
    assert.equal(store.db.isTransaction, false); assert.deepEqual(state.get('codes', code), document, 'failed issuance restores the unconsumed grant');
  } finally { store.close(); rmSync(folder, { recursive: true, force: true }); }
});
