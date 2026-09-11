import { randomUUID } from 'node:crypto';
import { fail, isUUID, transaction } from './community-errors.js';

function validSubject(subject) {
  return typeof subject === 'string' && subject.length > 0 && subject.length <= 512;
}

function article(input) {
  if (!input || Object.getPrototypeOf(input) !== Object.prototype
    || !Number.isSafeInteger(input.postId) || input.postId < 1
    || typeof input.title !== 'string' || !input.title.trim() || input.title.length > 1000
    || typeof input.path !== 'string' || input.path.length > 2000
    || !/^\/(?!\/)/.test(input.path) || /[\\\s?#%\u0000-\u001f\u007f]/.test(input.path)) fail(400, 'invalid_input');
  return { postId: input.postId, title: input.title.trim(), path: input.path };
}

function dto(row) {
  return { id: row.id, postId: row.post_id, title: row.title, path: row.path, createdAt: row.created_at };
}

/** Private article shortcuts backed by the independent Reader profile, not WP users. */
export class ReaderArticleFavorites {
  constructor({ db, issuer, now = Date.now } = {}) {
    if (!db?.exec || !db?.prepare || typeof issuer !== 'string' || !issuer || typeof now !== 'function') fail(500, 'configuration_error');
    this.db = db;
    this.issuer = issuer;
    this.now = now;
    db.exec('BEGIN IMMEDIATE');
    try {
      db.exec(`CREATE TABLE IF NOT EXISTS reader_article_favorites (
        id TEXT PRIMARY KEY, issuer TEXT NOT NULL, subject TEXT NOT NULL, profile_id TEXT NOT NULL,
        post_id INTEGER NOT NULL, title TEXT NOT NULL, path TEXT NOT NULL, created_at INTEGER NOT NULL,
        UNIQUE(issuer, profile_id, post_id)
      ); CREATE INDEX IF NOT EXISTS reader_article_favorites_list
        ON reader_article_favorites(issuer, subject, profile_id, created_at DESC, id DESC);`);
      db.exec('COMMIT');
    } catch (error) {
      try { db.exec('ROLLBACK'); } catch {}
      throw error;
    }
  }

  assertReader(subject, profileId) {
    if (!validSubject(subject) || !isUUID(profileId)) fail(401, 'authentication_required');
  }

  save(subject, profileId, rawArticle) {
    this.assertReader(subject, profileId);
    const value = article(rawArticle);
    return transaction(this.db, () => {
      const previous = this.db.prepare(`SELECT * FROM reader_article_favorites
        WHERE issuer=? AND subject=? AND profile_id=? AND post_id=?`).get(this.issuer, subject, profileId, value.postId);
      if (previous) {
        this.db.prepare(`UPDATE reader_article_favorites SET title=?,path=?
          WHERE id=? AND issuer=? AND subject=? AND profile_id=? AND post_id=?`).run(
          value.title, value.path, previous.id, this.issuer, subject, profileId, value.postId,
        );
        return dto({ ...previous, title: value.title, path: value.path });
      }
      const row = { id: randomUUID(), ...value, createdAt: this.now() };
      this.db.prepare(`INSERT INTO reader_article_favorites
        (id,issuer,subject,profile_id,post_id,title,path,created_at) VALUES (?,?,?,?,?,?,?,?)`).run(
        row.id, this.issuer, subject, profileId, row.postId, row.title, row.path, row.createdAt,
      );
      return dto({ id: row.id, post_id: row.postId, title: row.title, path: row.path, created_at: row.createdAt });
    });
  }

  /** Refresh stored display metadata only after a fresh editorial allow decision. */
  reconcile(subject, profileId, id, rawArticle) {
    this.assertReader(subject, profileId);
    if (!isUUID(id)) fail(400, 'invalid_input');
    const value = article(rawArticle);
    const changed = this.db.prepare(`UPDATE reader_article_favorites SET title=?,path=?
      WHERE id=? AND issuer=? AND subject=? AND profile_id=? AND post_id=?`).run(
      value.title, value.path, id, this.issuer, subject, profileId, value.postId,
    );
    if (changed.changes !== 1) return null;
    const row = this.db.prepare(`SELECT * FROM reader_article_favorites
      WHERE id=? AND issuer=? AND subject=? AND profile_id=?`).get(id, this.issuer, subject, profileId);
    return row ? dto(row) : null;
  }

  status(subject, profileId, postId) {
    this.assertReader(subject, profileId);
    if (!Number.isSafeInteger(postId) || postId < 1) fail(400, 'invalid_input');
    return Boolean(this.db.prepare(`SELECT 1 FROM reader_article_favorites
      WHERE issuer=? AND subject=? AND profile_id=? AND post_id=?`).get(this.issuer, subject, profileId, postId));
  }

  remove(subject, profileId, postId) {
    this.assertReader(subject, profileId);
    if (!Number.isSafeInteger(postId) || postId < 1) fail(400, 'invalid_input');
    this.db.prepare(`DELETE FROM reader_article_favorites
      WHERE issuer=? AND subject=? AND profile_id=? AND post_id=?`).run(this.issuer, subject, profileId, postId);
    return { postId, saved: false };
  }

  list(subject, profileId, { cursor = null, limit = 20 } = {}) {
    this.assertReader(subject, profileId);
    if (!Number.isSafeInteger(limit) || limit < 1 || limit > 50 || (cursor !== null && !isUUID(cursor))) fail(400, 'invalid_input');
    const cursorRow = cursor && this.db.prepare(`SELECT created_at FROM reader_article_favorites
      WHERE id=? AND issuer=? AND subject=? AND profile_id=?`).get(cursor, this.issuer, subject, profileId);
    if (cursor && !cursorRow) fail(400, 'invalid_cursor');
    const rows = this.db.prepare(`SELECT * FROM reader_article_favorites
      WHERE issuer=? AND subject=? AND profile_id=?
      AND (? IS NULL OR created_at < ? OR (created_at=? AND id<?))
      ORDER BY created_at DESC,id DESC LIMIT ?`).all(
      this.issuer, subject, profileId, cursorRow?.created_at ?? null, cursorRow?.created_at ?? null,
      cursorRow?.created_at ?? null, cursor ?? '', limit,
    );
    return { items: rows.map(dto), nextCursor: rows.length === limit ? rows.at(-1).id : null };
  }
}
