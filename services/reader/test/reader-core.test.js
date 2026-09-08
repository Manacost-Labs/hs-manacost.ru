import test from 'node:test';
import assert from 'node:assert/strict';
import { createHash, randomBytes } from 'node:crypto';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { ReaderStore, ReaderValidationError } from '../core.js';

const key = () => randomBytes(32);
const memory = (now = 1_000) => ({ store: new ReaderStore({ filename: ':memory:', encryptionKey: key(), now: () => now }), now });
function disk() { const dir = mkdtempSync(join(tmpdir(), 'reader-')); return { filename: join(dir, 'reader.sqlite'), cleanup: () => rmSync(dir, { recursive: true, force: true }) }; }

test('returnTo is canonical same-origin relative and rejects redirect vectors', () => {
  const { store } = memory();
  assert.equal(store.validateReturnTo('/статья/1?x=1'), '/%D1%81%D1%82%D0%B0%D1%82%D1%8C%D1%8F/1?x=1');
  const unsafe = [
    'https://evil.test', '//evil.test', '/a/..//evil', '/a/%2F%2Fevil',
    '/a/%5Cevil', '/a/%252f%252fevil', '/a/%0d', '/a/%250d',
    '/a/%25250d', '/a\\evil', '/a\u0001',
  ];
  for (const value of unsafe) assert.throws(() => store.validateReturnTo(value), ReaderValidationError);
  store.close();
});

test('attempt payload is encrypted, browser-bound, PKCE/nonce recoverable, single use', () => {
  const { store } = memory();
  try {
    const attempt = store.createLoginAttempt({ returnTo: '/article/1', browserNonce: 'browser-nonce-aa', ttlMs: 500 });
    assert.equal(typeof attempt.nonce, 'string'); assert.equal(attempt.codeVerifier, undefined);
    const row = store.db.prepare('SELECT state_hash, payload_ciphertext FROM login_attempts').get();
    const serialized = JSON.stringify(row);
    assert.equal(row.state_hash, createHash('sha256').update(attempt.state).digest('hex'));
    assert.equal(serialized.includes('browser-nonce-aa'), false);
    assert.equal(serialized.includes(attempt.state), false);
    assert.equal(serialized.includes(attempt.nonce), false);
    assert.equal(store.consumeLoginAttempt(attempt.state, 'browser-nonce-aa').returnTo, '/article/1');
    assert.throws(() => store.consumeLoginAttempt(attempt.state, 'browser-nonce-aa'), /single-use/);
  } finally { store.close(); }
});

test('wrong browser does not consume and expired attempt fails at exact expiry', () => {
  let now = 1_000; const store = new ReaderStore({ filename: ':memory:', encryptionKey: key(), now: () => now });
  try {
    const attempt = store.createLoginAttempt({ returnTo: '/', browserNonce: 'browser-nonce-xx', ttlMs: 10 });
    assert.throws(() => store.consumeLoginAttempt(attempt.state, 'browser-nonce-yy'), /browser/);
    assert.equal(store.db.prepare('SELECT consumed_at FROM login_attempts').get().consumed_at, null);
    now = 1_010; assert.throws(() => store.consumeLoginAttempt(attempt.state, 'browser-nonce-xx'), /expired/);
  } finally { store.close(); }
});

test('invalid TTLs are rejected and cookie descriptor is host-only', () => {
  const { store } = memory();
  try {
    for (const ttl of [0, -1, NaN, Infinity, 600001]) assert.throws(() => store.createLoginAttempt({ returnTo: '/', browserNonce: 'browser-nonce-aa', ttlMs: ttl }), /TTL/);
    const cookie = store.serializeCookie('opaque');
    assert.match(cookie, /^__Host-manacost_reader=opaque;/);
    assert.match(cookie, /Secure/); assert.match(cookie, /HttpOnly/);
    assert.match(cookie, /SameSite=Lax/); assert.match(cookie, /Path=\//);
    assert.doesNotMatch(cookie, /Domain=/);
    assert.match(store.serializeCookie('', 0), /Max-Age=0/);
  } finally { store.close(); }
});

test('sessions persist across restart, rotate, revoke and exact expiry', () => {
  let now = 1_000; const d = disk(); const encryptionKey = key();
  try {
    let store = new ReaderStore({ filename: d.filename, encryptionKey, now: () => now });
    const first = store.createSession({ userId: 'user-a', upstreamToken: 'secret-a', ttlMs: 100 }); store.close();
    store = new ReaderStore({ filename: d.filename, encryptionKey, now: () => now });
    assert.equal(store.getSession(first.id).upstreamToken, 'secret-a');
    assert.equal(JSON.stringify(store.db.prepare('SELECT * FROM reader_sessions').all()).includes('secret-a'), false);
    const second = store.rotateSession(first.id, { userId: 'user-a', upstreamToken: 'secret-b', ttlMs: 100 });
    assert.equal(store.getSession(first.id), null); assert.equal(store.getSession(second.id).upstreamToken, 'secret-b');
    now = 1_100; assert.equal(store.getSession(second.id), null); store.close();
  } finally { d.cleanup(); }
});

test('login attempts persist across restart', () => {
  const d = disk(); const encryptionKey = key();
  try {
    let store = new ReaderStore({ filename: d.filename, encryptionKey, now: () => 1_000 });
    const attempt = store.createLoginAttempt({ returnTo: '/article/2', browserNonce: 'browser-nonce-aa' }); store.close();
    store = new ReaderStore({ filename: d.filename, encryptionKey, now: () => 1_000 });
    assert.equal(store.consumeLoginAttempt(attempt.state, 'browser-nonce-aa').returnTo, '/article/2'); store.close();
  } finally { d.cleanup(); }
});

test('AES-GCM tamper and ciphertext row swap fail closed', () => {
  const { store } = memory();
  try {
    const a = store.createSession({ userId: 'a', upstreamToken: 'a' }); const b = store.createSession({ userId: 'b', upstreamToken: 'b' });
    const rows = store.db.prepare('SELECT id_hash, token_ciphertext FROM reader_sessions ORDER BY user_id').all();
    store.db.prepare('UPDATE reader_sessions SET token_ciphertext = ? WHERE id_hash = ?').run(rows[1].token_ciphertext, rows[0].id_hash);
    assert.equal(store.getSession(a.id), null);
    store.db.prepare('UPDATE reader_sessions SET token_ciphertext = ? WHERE id_hash = ?').run(`${rows[0].token_ciphertext.slice(0, -2)}xx`, rows[1].id_hash);
    assert.equal(store.getSession(b.id), null);
  } finally { store.close(); }
});

test('rotation observes a concurrent revoke inside its transaction', () => {
  const d = disk(); const encryptionKey = key();
  try {
    const first = new ReaderStore({ filename: d.filename, encryptionKey, now: () => 1_000 });
    const second = new ReaderStore({ filename: d.filename, encryptionKey, now: () => 1_000 });
    const session = first.createSession({ userId: 'race-user', upstreamToken: 'old' });
    second.revokeSession(session.id);
    assert.throws(() => first.rotateSession(session.id, { userId: 'race-user', upstreamToken: 'new' }), /session unavailable/);
    assert.equal(first.getSession(session.id), null);
    first.close(); second.close();
  } finally { d.cleanup(); }
});
