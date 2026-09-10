import { createHash, randomBytes } from 'node:crypto';

const hash = value => createHash('sha256').update(value).digest('hex');
const validToken = value => typeof value === 'string' && value.length > 0 && value.length <= 16384;
export const SESSION_TTL = 30 * 86400000;
export class ReaderSessionEnded extends Error {}

/** Additive encrypted credentials; the original session schema remains rollback-compatible. */
export class SessionTokens {
  constructor(store, cipher) {
    this.store = store; this.db = store.db; this.cipher = cipher;
    this.db.exec(`CREATE TABLE IF NOT EXISTS reader_session_tokens (
      id_hash TEXT PRIMARY KEY, refresh_ciphertext TEXT NOT NULL, access_expires_at INTEGER NOT NULL,
      claim_id TEXT, claimed_at INTEGER
    )`);
  }
  validate(refreshToken, accessTtlMs) {
    if (refreshToken === undefined) return;
    if (!validToken(refreshToken) || !Number.isSafeInteger(accessTtlMs) || accessTtlMs <= 0 || accessTtlMs > 300000) {
      throw new Error('Invalid session credentials');
    }
  }
  insert(id, refreshToken, accessTtlMs) {
    if (refreshToken === undefined) return;
    const idHash = hash(id);
    this.db.prepare('INSERT INTO reader_session_tokens VALUES (?, ?, ?, NULL, NULL)').run(
      idHash, this.cipher.seal(refreshToken, `refresh:${idHash}`), this.store.now() + accessTtlMs,
    );
  }
  get(id) {
    const idHash = hash(id);
    const row = this.db.prepare('SELECT * FROM reader_session_tokens WHERE id_hash = ?').get(idHash);
    if (!row) return null;
    return { refreshToken: this.cipher.open(row.refresh_ciphertext, `refresh:${idHash}`),
      accessExpiresAt: row.access_expires_at, claimId: row.claim_id, claimedAt: row.claimed_at };
  }
  claim(id, expectedToken) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const session = this.store.getSession(id);
      const claimId = randomBytes(24).toString('base64url');
      const changed = session?.upstreamToken === expectedToken
        && this.db.prepare('UPDATE reader_session_tokens SET claim_id = ?, claimed_at = ? WHERE id_hash = ? AND claim_id IS NULL')
          .run(claimId, this.store.now(), hash(id)).changes === 1;
      this.db.exec('COMMIT'); return changed ? claimId : null;
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  complete(id, claimId, expectedToken, result) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const session = this.store.getSession(id); const current = this.get(id);
      if (!session || session.upstreamToken !== expectedToken || current?.claimId !== claimId) {
        this.db.exec('COMMIT'); return null;
      }
      const idHash = hash(id);
      this.db.prepare('UPDATE reader_sessions SET token_ciphertext = ? WHERE id_hash = ?').run(
        this.cipher.seal(result.accessToken, `${idHash}:${session.userId}`), idHash,
      );
      this.db.prepare('UPDATE reader_session_tokens SET refresh_ciphertext = ?, access_expires_at = ?, claim_id = NULL, claimed_at = NULL WHERE id_hash = ?').run(
        this.cipher.seal(result.refreshToken, `refresh:${idHash}`),
        Math.min(session.expiresAt, this.store.now() + Math.min(300000, Math.floor(result.expiresIn * 1000))), idHash,
      );
      this.db.exec('COMMIT'); return this.store.getSession(id);
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  remove(id) { this.db.prepare('DELETE FROM reader_session_tokens WHERE id_hash = ?').run(hash(id)); }
  cleanup() { this.db.exec('DELETE FROM reader_session_tokens WHERE id_hash NOT IN (SELECT id_hash FROM reader_sessions)'); }
}

const flights = new WeakMap();
function queueResult(store, result, expiresAt) {
  for (const token of [result?.refreshToken, result?.accessToken]) {
    if (validToken(token)) store.queueRevocation(token, expiresAt);
  }
}

/** Server-only rotation, with a durable one-shot claim and an absolute session deadline. */
export async function activeReaderSession(store, identity, id, signal) {
  const session = store.getSession(id);
  if (!session) return null;
  let pending = flights.get(store);
  if (!pending) { pending = new Map(); flights.set(store, pending); }
  if (pending.has(id)) { const value = await pending.get(id); signal.throwIfAborted(); return value; }
  const credentials = store.tokens.get(id);
  if (!credentials) return session; // Existing short sessions need no migration or refresh.
  if (credentials.claimId) {
    if (store.now() - credentials.claimedAt <= 10000) throw new Error('Session refresh in progress');
    store.revokeAndQueue(id); return null; // A restart must not replay a possibly consumed token.
  }
  if (credentials.accessExpiresAt > store.now() + 30000) return session;
  const claimId = store.tokens.claim(id, session.upstreamToken);
  if (!claimId) throw new Error('Session refresh in progress');
  const refresh = (async () => {
    let result;
    try {
      // One disconnected caller must not cancel a refresh shared by other requests.
      const refreshSignal = AbortSignal.timeout(4000);
      result = await identity.refresh(credentials.refreshToken, session.userId, refreshSignal);
      refreshSignal.throwIfAborted();
      if (result?.subject !== session.userId || !validToken(result.accessToken) || !validToken(result.refreshToken)
        || result.refreshToken === credentials.refreshToken || !Number.isFinite(result.expiresIn) || result.expiresIn < 1) {
        throw new Error('Invalid refreshed credentials');
      }
      const current = store.tokens.complete(id, claimId, session.upstreamToken, result);
      if (!current) queueResult(store, result, session.expiresAt);
      return current;
    } catch {
      queueResult(store, result, session.expiresAt);
      store.revokeAndQueue(id);
      // The provider may already have rotated the token: never retry it after an ambiguous response.
      throw new ReaderSessionEnded('Reader login must be renewed');
    } finally { pending.delete(id); }
  })();
  pending.set(id, refresh);
  const current = await refresh; signal.throwIfAborted(); return current;
}
