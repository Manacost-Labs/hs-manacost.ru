import { createCipheriv, createDecipheriv, createHash, randomBytes } from 'node:crypto';
import { DatabaseSync } from 'node:sqlite';

export class ReaderValidationError extends Error {}
export class ReaderAuthorizationDenied extends Error {}
const hash = (value) => createHash('sha256').update(value).digest('hex');
const b64 = (value) => value.toString('base64url');
const MAX_ATTEMPT_TTL = 600_000;
const MAX_SESSION_TTL = 30 * 24 * 60 * 60 * 1000;
function ttl(value, maximum) {
  if (!Number.isSafeInteger(value) || value <= 0 || value > maximum) throw new ReaderValidationError('invalid TTL');
  return value;
}
function seal(value, key, aad) {
  const iv = randomBytes(12);
  const cipher = createCipheriv('aes-256-gcm', key, iv, { authTagLength: 16 });
  cipher.setAAD(Buffer.from(aad));
  const ciphertext = Buffer.concat([cipher.update(value, 'utf8'), cipher.final()]);
  return JSON.stringify({ iv: b64(iv), tag: b64(cipher.getAuthTag()), ciphertext: b64(ciphertext) });
}
function open(serialized, key, aad) {
  const item = JSON.parse(serialized);
  const decipher = createDecipheriv('aes-256-gcm', key, Buffer.from(item.iv, 'base64url'), { authTagLength: 16 });
  decipher.setAAD(Buffer.from(aad));
  decipher.setAuthTag(Buffer.from(item.tag, 'base64url'));
  return Buffer.concat([decipher.update(Buffer.from(item.ciphertext, 'base64url')), decipher.final()]).toString('utf8');
}

export class ReaderStore {
  constructor({ filename = ':memory:', encryptionKey, now = () => Date.now() } = {}) {
    if (!Buffer.isBuffer(encryptionKey) || encryptionKey.length !== 32) throw new ReaderValidationError('32-byte encryption key required');
    this.db = new DatabaseSync(filename); this.key = Buffer.from(encryptionKey); this.now = now;
    this.db.exec(`CREATE TABLE IF NOT EXISTS login_attempts (
      state_hash TEXT PRIMARY KEY, browser_nonce_hash TEXT NOT NULL, payload_ciphertext TEXT NOT NULL,
      expires_at INTEGER NOT NULL, consumed_at INTEGER
    ); CREATE TABLE IF NOT EXISTS reader_sessions (
      id_hash TEXT PRIMARY KEY, user_id TEXT NOT NULL, token_ciphertext TEXT NOT NULL,
      expires_at INTEGER NOT NULL, revoked_at INTEGER
    ); CREATE TABLE IF NOT EXISTS reader_revocations (
      id_hash TEXT PRIMARY KEY, token_ciphertext TEXT NOT NULL, expires_at INTEGER NOT NULL
    );`);
  }
  validateReturnTo(returnTo) {
    const unsafeSyntax = typeof returnTo !== 'string'
      || !returnTo.startsWith('/')
      || returnTo.startsWith('//')
      || /[\u0000-\u001f\u007f\\]/.test(returnTo)
      || returnTo.length > 2048
      || /%(?:25|2f|5c|[01][0-9a-f]|7f)/i.test(returnTo);
    if (unsafeSyntax) throw new ReaderValidationError('unsafe returnTo');
    let url; try { url = new URL(returnTo, 'https://manacost.invalid'); } catch { throw new ReaderValidationError('unsafe returnTo'); }
    if (url.origin !== 'https://manacost.invalid' || url.pathname.startsWith('//') || /[\u0000-\u001f\u007f\\]/.test(url.pathname)) throw new ReaderValidationError('unsafe returnTo');
    return `${url.pathname}${url.search}${url.hash}`;
  }
  createLoginAttempt({ returnTo, browserNonce, parentSessionId = null, ttlMs = 300_000 } = {}) {
    const safeReturnTo = this.validateReturnTo(returnTo); ttl(ttlMs, MAX_ATTEMPT_TTL);
    if (typeof browserNonce !== 'string' || browserNonce.length < 16) throw new ReaderValidationError('browser nonce required');
    const state = b64(randomBytes(32)); const nonce = b64(randomBytes(32)); const codeVerifier = b64(randomBytes(32));
    const codeChallenge = b64(createHash('sha256').update(codeVerifier).digest());
    if (parentSessionId !== null && !this.getSession(parentSessionId)) throw new ReaderValidationError('inactive parent session');
    const payload = seal(JSON.stringify({ codeVerifier, nonce, returnTo: safeReturnTo, parentSessionId }), this.key, hash(state));
    this.db.prepare('INSERT INTO login_attempts VALUES (?, ?, ?, ?, NULL)').run(hash(state), hash(browserNonce), payload, this.now() + ttlMs);
    return { state, nonce, codeChallenge };
  }
  consumeLoginAttempt(state, browserNonce) {
    const row = this.db.prepare('SELECT * FROM login_attempts WHERE state_hash = ?').get(hash(state));
    if (!row) throw new ReaderValidationError('unknown state'); if (row.consumed_at !== null) throw new ReaderValidationError('single-use state');
    if (row.expires_at <= this.now()) throw new ReaderValidationError('expired state'); if (row.browser_nonce_hash !== hash(browserNonce)) throw new ReaderValidationError('browser binding mismatch');
    let payload; try { payload = JSON.parse(open(row.payload_ciphertext, this.key, hash(state))); } catch { throw new ReaderValidationError('invalid attempt payload'); }
    const changed = this.db.prepare('UPDATE login_attempts SET consumed_at = ? WHERE state_hash = ? AND consumed_at IS NULL').run(this.now(), hash(state));
    if (changed.changes !== 1) throw new ReaderValidationError('single-use state'); return payload;
  }
  createSession({ userId, upstreamToken, ttlMs = MAX_SESSION_TTL } = {}) {
    ttl(ttlMs, MAX_SESSION_TTL); if (!userId || typeof upstreamToken !== 'string') throw new ReaderValidationError('session fields required');
    const id = b64(randomBytes(32)); const idHash = hash(id);
    this.db.prepare('INSERT INTO reader_sessions VALUES (?, ?, ?, ?, NULL)').run(idHash, String(userId), seal(upstreamToken, this.key, `${idHash}:${userId}`), this.now() + ttlMs);
    return { id, expiresAt: this.now() + ttlMs };
  }
  rotateSession(id, { userId, upstreamToken, ttlMs = MAX_SESSION_TTL } = {}) {
    ttl(ttlMs, MAX_SESSION_TTL);
    if (!userId || typeof upstreamToken !== 'string') throw new ReaderValidationError('session fields required');
    const nextId = b64(randomBytes(32)); const nextHash = hash(nextId); const expiresAt = this.now() + ttlMs;
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const current = this.db.prepare('SELECT * FROM reader_sessions WHERE id_hash = ?').get(hash(id));
      if (!current || current.user_id !== String(userId) || current.revoked_at !== null || current.expires_at <= this.now()) throw new ReaderValidationError('session unavailable');
      const changed = this.db.prepare('UPDATE reader_sessions SET revoked_at = ? WHERE id_hash = ? AND revoked_at IS NULL AND expires_at > ?').run(this.now(), hash(id), this.now());
      if (changed.changes !== 1) throw new ReaderValidationError('session rotation conflict');
      this.db.prepare('INSERT INTO reader_sessions VALUES (?, ?, ?, ?, NULL)').run(nextHash, String(userId), seal(upstreamToken, this.key, `${nextHash}:${userId}`), expiresAt);
      this.queueRevocation(open(current.token_ciphertext, this.key, `${current.id_hash}:${current.user_id}`), current.expires_at);
      this.db.exec('COMMIT');
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
    return { id: nextId, expiresAt };
  }
  getSession(id) {
    if (typeof id !== 'string' || !id) return null; const row = this.db.prepare('SELECT * FROM reader_sessions WHERE id_hash = ?').get(hash(id));
    if (!row || row.revoked_at !== null || row.expires_at <= this.now()) return null;
    try { return { userId: row.user_id, upstreamToken: open(row.token_ciphertext, this.key, `${row.id_hash}:${row.user_id}`), expiresAt: row.expires_at }; } catch { return null; }
  }
  revokeSession(id) { this.db.prepare('UPDATE reader_sessions SET revoked_at = ? WHERE id_hash = ?').run(this.now(), hash(id)); }
  queueRevocation(token, expiresAt) {
    const tokenHash = hash(token);
    this.db.prepare('INSERT OR IGNORE INTO reader_revocations VALUES (?, ?, ?)').run(
      tokenHash, seal(token, this.key, `revocation:${tokenHash}`), expiresAt,
    );
  }
  revokeAndQueue(id) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const session = this.getSession(id);
      if (session) {
        this.queueRevocation(session.upstreamToken, session.expiresAt);
        this.revokeSession(id);
      }
      this.db.exec('COMMIT');
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  pendingRevocations() {
    return this.db.prepare('SELECT * FROM reader_revocations WHERE expires_at > ? LIMIT 20').all(this.now()).map(row => ({
      id: row.id_hash, token: open(row.token_ciphertext, this.key, `revocation:${row.id_hash}`),
    }));
  }
  completeRevocation(id) { this.db.prepare('DELETE FROM reader_revocations WHERE id_hash = ?').run(id); }
  cleanup() {
    for (const table of ['login_attempts', 'reader_sessions', 'reader_revocations']) {
      this.db.prepare(`DELETE FROM ${table} WHERE expires_at <= ?`).run(this.now());
    }
    this.db.prepare('DELETE FROM reader_sessions WHERE revoked_at IS NOT NULL').run();
  }
  cookieDescriptor() { return { name: '__Host-manacost_reader', secure: true, httpOnly: true, sameSite: 'Lax', path: '/', domain: undefined }; }
  serializeCookie(value, maxAge = MAX_SESSION_TTL) {
    if (maxAge !== 0) ttl(maxAge, MAX_SESSION_TTL);
    return `__Host-manacost_reader=${encodeURIComponent(value)}; Max-Age=${Math.floor(maxAge / 1000)}; Path=/; HttpOnly; Secure; SameSite=Lax`;
  }
  close() { this.db.close(); }
}
