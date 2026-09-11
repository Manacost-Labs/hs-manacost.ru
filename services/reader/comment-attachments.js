import { randomUUID } from 'node:crypto';
import { AvatarBusyError, AvatarValidationError, normalizeCommentImage } from './avatars.js';
import { fail, isUUID, transaction } from './community-errors.js';

const PENDING_TTL = 24 * 60 * 60 * 1000;
const MAX_PENDING_PER_READER = 3;

function validSubject(subject) {
  return typeof subject === 'string' && subject.length > 0 && subject.length <= 512;
}

function attachmentDTO(row) {
  return { id: row.id, width: row.width, height: row.height };
}

/**
 * Isolated image staging for discussion posts. Bytes are private until a
 * matching ReaderComments transaction attaches them to a published comment.
 */
export class ReaderCommentAttachments {
  constructor({ db, issuer, now = Date.now } = {}) {
    if (!db?.exec || !db?.prepare || typeof issuer !== 'string' || !issuer || typeof now !== 'function') fail(500, 'configuration_error');
    this.db = db;
    this.issuer = issuer;
    this.now = now;
    db.exec('BEGIN IMMEDIATE');
    try {
      db.exec(`CREATE TABLE IF NOT EXISTS reader_comment_attachments (
        id TEXT PRIMARY KEY, issuer TEXT NOT NULL, subject TEXT NOT NULL, profile_id TEXT NOT NULL,
        bytes BLOB NOT NULL, width INTEGER NOT NULL CHECK(width > 0), height INTEGER NOT NULL CHECK(height > 0),
        attached_comment_id TEXT UNIQUE, created_at INTEGER NOT NULL, expires_at INTEGER NOT NULL
      ); CREATE INDEX IF NOT EXISTS reader_comment_attachments_pending
        ON reader_comment_attachments(issuer, subject, profile_id, attached_comment_id, expires_at);`);
      db.exec('COMMIT');
    } catch (error) {
      try { db.exec('ROLLBACK'); } catch {}
      throw error;
    }
  }

  pendingCount(subject, profileId, now) {
    return this.db.prepare(`SELECT count(*) count FROM reader_comment_attachments
      WHERE issuer=? AND subject=? AND profile_id=? AND attached_comment_id IS NULL AND expires_at>?`).get(
      this.issuer, subject, profileId, now,
    ).count;
  }

  async stage(subject, profileId, bytes, contentType) {
    if (!validSubject(subject) || !isUUID(profileId)) fail(401, 'authentication_required');
    const now = this.now();
    if (this.pendingCount(subject, profileId, now) >= MAX_PENDING_PER_READER) fail(429, 'attachment_limit_reached');
    let image;
    try {
      image = await normalizeCommentImage(bytes, contentType);
    } catch (error) {
      if (error instanceof AvatarValidationError) fail(400, 'invalid_attachment');
      if (error instanceof AvatarBusyError) fail(503, 'attachment_busy');
      throw error;
    }
    return transaction(this.db, () => {
      const createdAt = this.now();
      if (this.pendingCount(subject, profileId, createdAt) >= MAX_PENDING_PER_READER) fail(429, 'attachment_limit_reached');
      const row = { id: randomUUID(), width: image.width, height: image.height };
      this.db.prepare(`INSERT INTO reader_comment_attachments
        (id,issuer,subject,profile_id,bytes,width,height,attached_comment_id,created_at,expires_at)
        VALUES (?,?,?,?,?,?,?,NULL,?,?)`).run(
        row.id, this.issuer, subject, profileId, image.bytes, row.width, row.height, createdAt, createdAt + PENDING_TTL,
      );
      return attachmentDTO(row);
    });
  }

  /** Caller owns the same transaction that creates the comment. */
  claim(subject, profileId, attachmentId, commentId) {
    if (attachmentId === null) return null;
    if (!validSubject(subject) || !isUUID(profileId) || !isUUID(attachmentId) || !isUUID(commentId)) fail(400, 'invalid_input');
    const now = this.now();
    const row = this.db.prepare(`SELECT id,width,height FROM reader_comment_attachments
      WHERE id=? AND issuer=? AND subject=? AND profile_id=? AND attached_comment_id IS NULL AND expires_at>?`).get(
      attachmentId, this.issuer, subject, profileId, now,
    );
    if (!row) fail(409, 'attachment_unavailable');
    const changed = this.db.prepare(`UPDATE reader_comment_attachments SET attached_comment_id=?
      WHERE id=? AND issuer=? AND subject=? AND profile_id=? AND attached_comment_id IS NULL AND expires_at>?`).run(
      commentId, attachmentId, this.issuer, subject, profileId, now,
    );
    if (changed.changes !== 1) fail(409, 'attachment_unavailable');
    return attachmentDTO(row);
  }

  publicForComment(commentId) {
    if (!isUUID(commentId)) return null;
    const row = this.db.prepare(`SELECT a.id,a.bytes,a.width,a.height,c.post_id FROM reader_comment_attachments a
      JOIN reader_comments c ON c.id=a.attached_comment_id AND c.issuer=a.issuer
      WHERE a.issuer=? AND a.attached_comment_id=? AND c.status='published' AND c.body IS NOT NULL`).get(this.issuer, commentId);
    return row ? { ...attachmentDTO(row), postId: row.post_id, bytes: Buffer.from(row.bytes), contentType: 'image/webp' } : null;
  }

  removeForComment(commentId) {
    if (!isUUID(commentId)) return;
    this.db.prepare('DELETE FROM reader_comment_attachments WHERE issuer=? AND attached_comment_id=?').run(this.issuer, commentId);
  }

  /** Discard an unshared staged image so changing one's mind never consumes the quota. */
  discard(subject, profileId, attachmentId) {
    if (!validSubject(subject) || !isUUID(profileId) || !isUUID(attachmentId)) fail(400, 'invalid_input');
    const changed = this.db.prepare(`DELETE FROM reader_comment_attachments
      WHERE id=? AND issuer=? AND subject=? AND profile_id=? AND attached_comment_id IS NULL`).run(
      attachmentId, this.issuer, subject, profileId,
    ).changes;
    return { id: attachmentId, discarded: changed === 1 };
  }

  erase(subject, profileId) {
    if (!validSubject(subject) || !isUUID(profileId)) return;
    this.db.prepare('DELETE FROM reader_comment_attachments WHERE issuer=? AND subject=? AND profile_id=?').run(this.issuer, subject, profileId);
  }

  cleanup() {
    return this.db.prepare(`DELETE FROM reader_comment_attachments
      WHERE issuer=? AND attached_comment_id IS NULL AND expires_at<=?`).run(this.issuer, this.now()).changes;
  }
}
