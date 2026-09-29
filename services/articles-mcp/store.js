import { DatabaseSync } from 'node:sqlite';
import { validateOrigin } from './content.js';

export class ArticleStore {
  constructor(filename, origin) {
    validateOrigin(origin); this.db = new DatabaseSync(filename);
    this.db.exec(`PRAGMA journal_mode=WAL; PRAGMA busy_timeout=3000;
      CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL);
      CREATE TABLE IF NOT EXISTS articles (id INTEGER PRIMARY KEY, modified TEXT NOT NULL, document TEXT NOT NULL);
      CREATE VIRTUAL TABLE IF NOT EXISTS article_search USING fts5(title, body, tokenize='unicode61');`);
    const previous = this.db.prepare("SELECT value FROM settings WHERE key='origin'").get()?.value;
    if (previous && previous !== origin) { this.db.close(); throw new Error('index_origin_mismatch'); }
    this.db.prepare("INSERT OR IGNORE INTO settings VALUES ('origin', ?)").run(origin);
  }
  lastSync() { return this.db.prepare("SELECT value FROM settings WHERE key='synced_at'").get()?.value ?? '1970-01-01T00:00:00Z'; }
  count() { return this.db.prepare('SELECT COUNT(*) AS n FROM articles').get().n; }
  get(id) { const row = this.db.prepare('SELECT document FROM articles WHERE id=?').get(id); return row ? JSON.parse(row.document) : null; }
  versions() { return new Map(this.db.prepare('SELECT id, modified FROM articles').all().map(row => [row.id, row.modified])); }
  beginSync() {
    this.db.exec('DROP TABLE IF EXISTS temp.pending_articles; CREATE TEMP TABLE pending_articles (document TEXT NOT NULL)');
  }
  stage(documents) {
    const insert = this.db.prepare('INSERT INTO pending_articles VALUES (?)');
    for (const article of documents) insert.run(JSON.stringify(article));
  }
  replace(ids) {
    this.db.exec('BEGIN IMMEDIATE');
    try {
      for (const row of this.db.prepare('SELECT document FROM pending_articles').iterate()) {
        const article = JSON.parse(row.document);
        this.db.prepare('INSERT OR REPLACE INTO articles VALUES (?, ?, ?)').run(Number(article.id), article.modified, JSON.stringify(article));
        this.db.prepare('DELETE FROM article_search WHERE rowid=?').run(Number(article.id));
        this.db.prepare('INSERT INTO article_search(rowid, title, body) VALUES (?, ?, ?)').run(Number(article.id), article.title, article.text);
      }
      const retained = new Set(ids);
      for (const { id } of this.db.prepare('SELECT id FROM articles').all()) {
        if (!retained.has(id)) {
          this.db.prepare('DELETE FROM articles WHERE id=?').run(id);
          this.db.prepare('DELETE FROM article_search WHERE rowid=?').run(id);
        }
      }
      this.db.prepare("INSERT OR REPLACE INTO settings VALUES ('synced_at', ?)").run(new Date().toISOString());
      this.db.exec('COMMIT');
    } catch (error) { this.db.exec('ROLLBACK'); throw error; }
  }
  search(query) {
    const words = query.normalize('NFKC').match(/[\p{L}\p{N}]+/gu)?.slice(0, 12) ?? [];
    if (!words.length) return [];
    const match = words.map(word => `"${word}"*`).join(' AND ');
    return this.db.prepare(`SELECT articles.document FROM article_search JOIN articles ON articles.id=article_search.rowid
      WHERE article_search MATCH ? ORDER BY bm25(article_search, 5.0, 1.0), articles.id LIMIT 20`).all(match).map(row => JSON.parse(row.document));
  }
  list(after, limit) { return this.db.prepare('SELECT document FROM articles WHERE id>? ORDER BY id LIMIT ?').all(after, limit).map(row => JSON.parse(row.document)); }
  close() { this.db.close(); }
}
