import { createHash, randomUUID } from 'node:crypto';
import { fail, isUUID, isVersion, transaction } from './community-errors.js';

const dto = row => row ? { id: row.id, version: row.version, blocked: row.blocked === 1 } : { id: null, version: 0, blocked: false };

export class ReaderCommentBans {
  constructor({ db, issuer, origin, now }) { Object.assign(this, { db, issuer, origin, now }); }

  readerKey(subject) {
    if (typeof subject !== 'string' || !subject || subject.length > 512) fail(400, 'invalid_input');
    return createHash('sha256').update(`${this.origin}\0${this.issuer}\0${subject}`).digest('hex');
  }
  isBlocked(subject) {
    return this.db.prepare('SELECT blocked FROM reader_comment_bans WHERE issuer=? AND reader_key=?').get(this.issuer, this.readerKey(subject))?.blocked === 1;
  }
  profile(id) {
    if (!isUUID(id)) fail(400, 'invalid_input');
    const profile = this.db.prepare('SELECT subject FROM reader_profiles WHERE issuer=? AND id=?').get(this.issuer, id);
    if (!profile) fail(404, 'profile_not_found');
    return profile;
  }
  forProfile(id) {
    const { subject } = this.profile(id);
    return dto(this.db.prepare('SELECT id,version,blocked FROM reader_comment_bans WHERE issuer=? AND reader_key=?').get(this.issuer, this.readerKey(subject)));
  }
  block(profileId, { version, actor } = {}) {
    if (!isVersion(version)) fail(400, 'invalid_input');
    const actorKey = this.readerKey(actor);
    return transaction(this.db, () => {
      const readerKey = this.readerKey(this.profile(profileId).subject);
      const old = this.db.prepare('SELECT * FROM reader_comment_bans WHERE issuer=? AND reader_key=?').get(this.issuer, readerKey);
      if (old?.blocked === 1 && (version === old.version || version === old.version - 1)) return dto(old);
      if ((old?.version ?? 0) !== version) fail(409, 'ban_version_conflict');
      const id = old?.id ?? randomUUID(); const now = this.now();
      this.db.prepare(`INSERT INTO reader_comment_bans VALUES (?,?,?,?,1,?,?)
        ON CONFLICT(issuer,reader_key) DO UPDATE SET blocked=1,version=excluded.version,profile_id=excluded.profile_id,updated_at=excluded.updated_at`).run(id, this.issuer, readerKey, profileId, version + 1, now);
      this.audit(id, actorKey, 'ban', now);
      return { id, version: version + 1, blocked: true };
    });
  }
  unblock(id, { version, actor } = {}) {
    if (!isUUID(id) || !isVersion(version) || version < 1) fail(400, 'invalid_input');
    const actorKey = this.readerKey(actor);
    return transaction(this.db, () => {
      const old = this.db.prepare('SELECT * FROM reader_comment_bans WHERE issuer=? AND id=?').get(this.issuer, id);
      if (!old) fail(404, 'ban_not_found');
      if (old.blocked === 0 && (version === old.version || version === old.version - 1)) return dto(old);
      if (version !== old.version) fail(409, 'ban_version_conflict');
      const now = this.now();
      this.db.prepare('UPDATE reader_comment_bans SET blocked=0,version=version+1,updated_at=? WHERE issuer=? AND id=? AND version=?').run(now, this.issuer, id, version);
      this.audit(id, actorKey, 'unban', now);
      return { id, version: version + 1, blocked: false };
    });
  }
  audit(id, actorKey, action, now) {
    this.db.prepare('INSERT INTO reader_community_audit VALUES (?,?,?,?,?,?)').run(randomUUID(), this.issuer, id, actorKey, action, now);
  }
  list({ cursor = null } = {}) {
    if (cursor !== null && !isUUID(cursor)) fail(400, 'invalid_input');
    const rows = this.db.prepare(`SELECT b.id,b.version,b.blocked,s.profile_id,s.name FROM reader_comment_bans b
      LEFT JOIN reader_comment_public_profiles s ON s.issuer=b.issuer AND s.profile_id=b.profile_id
        AND EXISTS (SELECT 1 FROM reader_comments c WHERE c.issuer=s.issuer AND c.author_profile_id=s.profile_id AND c.status='published')
      WHERE b.issuer=? AND b.blocked=1 AND (? IS NULL OR b.id>?) ORDER BY b.id LIMIT 21`).all(this.issuer, cursor, cursor);
    return { items: rows.slice(0, 20).map(row => ({ ...dto(row), profile: row.name ? { id: row.profile_id, name: row.name, profileUrl: `/account/?reader=${row.profile_id}` } : null })),
      nextCursor: rows.length > 20 ? rows[19].id : null };
  }
}
