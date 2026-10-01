import { createHash, randomBytes } from 'node:crypto';

export const oauthHash = value => createHash('sha256').update(value).digest('hex');
export const oauthNonce = () => randomBytes(32).toString('base64url');
const tables = new Set(['requests', 'codes', 'refresh', 'retired']);

/** Bounded, disposable OAuth state; existing Reader tables are never rewritten. */
export class McpOAuthStore {
  constructor(db, issuer, now) {
    this.db = db; this.now = now;
    db.exec('BEGIN IMMEDIATE');
    try {
      db.exec('CREATE TABLE IF NOT EXISTS mcp_oauth_settings (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
      const previous = db.prepare("SELECT value FROM mcp_oauth_settings WHERE key='issuer'").get()?.value;
      if (previous && previous !== issuer) throw new Error('MCP OAuth issuer mismatch');
      db.exec(`
      CREATE TABLE IF NOT EXISTS mcp_oauth_clients (id TEXT PRIMARY KEY, document TEXT NOT NULL, expires_at INTEGER NOT NULL);
      CREATE TABLE IF NOT EXISTS mcp_oauth_requests (id TEXT PRIMARY KEY, document TEXT NOT NULL, expires_at INTEGER NOT NULL);
      CREATE TABLE IF NOT EXISTS mcp_oauth_codes (id TEXT PRIMARY KEY, document TEXT NOT NULL, expires_at INTEGER NOT NULL);
      CREATE TABLE IF NOT EXISTS mcp_oauth_refresh (id TEXT PRIMARY KEY, document TEXT NOT NULL, expires_at INTEGER NOT NULL);
      CREATE TABLE IF NOT EXISTS mcp_oauth_retired (id TEXT PRIMARY KEY, document TEXT NOT NULL, expires_at INTEGER NOT NULL);`);
      db.prepare("INSERT OR IGNORE INTO mcp_oauth_settings VALUES ('issuer', ?)").run(issuer);
      db.exec('COMMIT');
    } catch (cause) { db.exec('ROLLBACK'); throw cause; }
  }
  cleanup() {
    for (const table of ['clients', ...tables]) this.db.prepare(`DELETE FROM mcp_oauth_${table} WHERE expires_at<=?`).run(this.now());
  }
  client(id) {
    if (!/^[A-Za-z0-9_-]{43}$/.test(id ?? '')) return null;
    const row = this.db.prepare('SELECT document FROM mcp_oauth_clients WHERE id=? AND expires_at>?').get(id, this.now());
    return row ? JSON.parse(row.document) : null;
  }
  register(document) {
    this.cleanup();
    if (this.db.prepare('SELECT count(*) AS n FROM mcp_oauth_clients').get().n >= 500) throw new Error('OAuth capacity');
    const id = oauthNonce();
    this.db.prepare('INSERT INTO mcp_oauth_clients VALUES (?, ?, ?)').run(id, JSON.stringify(document), this.now() + 600000);
    return id;
  }
  retainClient(id) { this.db.prepare('UPDATE mcp_oauth_clients SET expires_at=? WHERE id=?').run(this.now() + 30 * 86400000, id); }
  put(kind, document, ttl, value = oauthNonce()) {
    if (!tables.has(kind)) throw new Error('Invalid OAuth state table');
    this.cleanup();
    const maximum = kind === 'retired' ? 65536 : kind === 'refresh' ? 4096 : 500;
    if (this.db.prepare(`SELECT count(*) AS n FROM mcp_oauth_${kind}`).get().n >= maximum) throw new Error('OAuth capacity');
    this.db.prepare(`INSERT INTO mcp_oauth_${kind} VALUES (?, ?, ?)`).run(oauthHash(value), JSON.stringify(document), this.now() + ttl);
    return value;
  }
  get(kind, value) {
    if (!tables.has(kind) || !/^[A-Za-z0-9_-]{43}$/.test(value ?? '')) return null;
    const row = this.db.prepare(`SELECT document FROM mcp_oauth_${kind} WHERE id=? AND expires_at>?`).get(oauthHash(value), this.now());
    return row ? JSON.parse(row.document) : null;
  }
  bind(value, document) {
    return this.db.prepare('UPDATE mcp_oauth_requests SET document=? WHERE id=? AND expires_at>?')
      .run(JSON.stringify(document), oauthHash(value), this.now()).changes === 1;
  }
  take(kind, value, document) {
    if (!tables.has(kind)) throw new Error('Invalid OAuth state table');
    return this.db.prepare(`DELETE FROM mcp_oauth_${kind} WHERE id=? AND document=? AND expires_at>?`)
      .run(oauthHash(value), JSON.stringify(document), this.now()).changes === 1;
  }
  revokeFamily(client, family) {
    this.db.prepare("DELETE FROM mcp_oauth_refresh WHERE json_extract(document, '$.client')=? AND json_extract(document, '$.family')=?").run(client, family);
  }
  /** Consume, remember rotation and issue its successor in one SQLite transaction. */
  redeem(kind, value, document, issue) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      if (!this.take(kind, value, document)) {
        this.db.exec('ROLLBACK');
        if (kind === 'refresh') this.revokeFamily(document.client, document.family);
        return null;
      }
      if (kind === 'refresh') this.put('retired', { client: document.client, family: document.family }, document.familyExpiresAt - this.now(), value);
      const result = issue({ ...document, family: document.family ?? oauthNonce(), familyExpiresAt: document.familyExpiresAt ?? this.now() + 30 * 86400000 });
      this.db.exec('COMMIT'); return result;
    } catch (cause) {
      if (this.db.isTransaction) this.db.exec('ROLLBACK');
      throw cause;
    }
  }
  pruneRefresh(client, sub) {
    this.db.prepare(`DELETE FROM mcp_oauth_refresh WHERE id IN (
      SELECT id FROM mcp_oauth_refresh WHERE json_extract(document, '$.client')=? AND json_extract(document, '$.sub')=?
      ORDER BY expires_at DESC, rowid DESC LIMIT -1 OFFSET 8)`).run(client, sub);
  }
}
