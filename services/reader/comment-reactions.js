import { createHash } from 'node:crypto';
import { fail, isUUID, transaction } from './community-errors.js';

const KINDS = ['like', 'thanks', 'fire'];
const MAX_ERASURE = 5000;

export class ReaderReactions {
  constructor({ db, issuer, now, profile }) { Object.assign(this, { db, issuer, now, profile }); }

  summaries(ids, viewerSubject = null) {
    if (!Array.isArray(ids) || ids.length > 50 || !ids.every(isUUID)) fail(400, 'invalid_input');
    const viewer = viewerSubject === null ? null : this.profile(viewerSubject).id;
    const placeholders = ids.map(() => '?').join(',') || "''";
    const rows = this.db.prepare(`SELECT r.comment_id,r.kind,count(*) count,max(r.profile_id=?) selected
      FROM reader_comment_reactions r JOIN reader_comments c ON c.id=r.comment_id AND c.issuer=r.issuer
      WHERE r.issuer=? AND c.status='published' AND r.comment_id IN (${placeholders}) GROUP BY r.comment_id,r.kind`).all(viewer ?? '', this.issuer, ...ids);
    return new Map(ids.map(id => [id, KINDS.map(kind => {
      const row = rows.find(item => item.comment_id === id && item.kind === kind);
      return { kind, count: row?.count ?? 0, selected: row?.selected === 1 };
    })]));
  }

  set(subject, id, kind) {
    if (!isUUID(id) || (kind !== null && !KINDS.includes(kind))) fail(400, 'invalid_input');
    return transaction(this.db, () => {
      const profile = this.profile(subject);
      const comment = this.db.prepare("SELECT id FROM reader_comments WHERE issuer=? AND id=? AND status='published' AND body IS NOT NULL").get(this.issuer, id);
      if (!comment) fail(404, 'comment_not_found');
      const previous = this.db.prepare('SELECT kind FROM reader_comment_reactions WHERE issuer=? AND comment_id=? AND profile_id=?').get(this.issuer, id, profile.id);
      if ((previous?.kind ?? null) === kind) return this.summaries([id], subject).get(id);
      const now = this.now();
      const rateKey = createHash('sha256').update(`${this.issuer}\0${subject}`).digest('hex');
      const rate = this.db.prepare('SELECT count(*) day,sum(created_at>?) minute FROM reader_reaction_events WHERE issuer=? AND rate_key=? AND created_at>?').get(now - 60_000, this.issuer, rateKey, now - 86_400_000);
      if (rate.day >= 200 || rate.minute >= 30) fail(429, 'reaction_rate_limited');
      if (kind !== null && !previous) {
        const owned = this.db.prepare('SELECT count(*) n FROM reader_comment_reactions WHERE issuer=? AND profile_id=?').get(this.issuer, profile.id).n;
        const received = this.db.prepare('SELECT count(*) n FROM reader_comment_reactions WHERE issuer=? AND comment_id=?').get(this.issuer, id).n;
        if (owned >= 1000 || received >= 5000) fail(429, 'reaction_limit_reached');
      }
      if (kind === null) this.db.prepare('DELETE FROM reader_comment_reactions WHERE issuer=? AND comment_id=? AND profile_id=?').run(this.issuer, id, profile.id);
      else this.db.prepare(`INSERT INTO reader_comment_reactions VALUES (?,?,?,?,?)
        ON CONFLICT(issuer,comment_id,profile_id) DO UPDATE SET kind=excluded.kind,updated_at=excluded.updated_at`).run(this.issuer, id, profile.id, kind, now);
      this.db.prepare('INSERT INTO reader_reaction_events VALUES (?,?,?)').run(this.issuer, rateKey, now);
      return this.summaries([id], subject).get(id);
    });
  }

  /** Caller holds the comment removal transaction. */
  removeForComment(id) {
    const count = this.db.prepare('SELECT count(*) n FROM (SELECT 1 FROM reader_comment_reactions WHERE issuer=? AND comment_id=? LIMIT 5001)').get(this.issuer, id).n;
    if (count > MAX_ERASURE) fail(409, 'erasure_needs_operator');
    this.db.prepare('DELETE FROM reader_comment_reactions WHERE issuer=? AND comment_id=?').run(this.issuer, id);
  }

  /** Called before any erasure writes, under the same transaction as comment erasure. */
  erase(profileId) {
    const predicate = `issuer=? AND (profile_id=? OR comment_id IN (SELECT id FROM reader_comments WHERE issuer=? AND author_profile_id=?))`;
    const args = [this.issuer, profileId, this.issuer, profileId];
    const count = this.db.prepare(`SELECT count(*) n FROM (SELECT 1 FROM reader_comment_reactions WHERE ${predicate} LIMIT 5001)`).get(...args).n;
    if (count > MAX_ERASURE) fail(409, 'erasure_needs_operator');
    this.db.prepare(`DELETE FROM reader_comment_reactions WHERE ${predicate}`).run(...args);
  }

  ownExport(subject) {
    const profileId = this.profile(subject).id;
    const rows = this.db.prepare(`SELECT comment_id,kind,updated_at FROM reader_comment_reactions WHERE issuer=? AND profile_id=? ORDER BY comment_id LIMIT 1000`).all(this.issuer, profileId);
    return { items: rows.map(row => ({ commentId: row.comment_id, kind: row.kind, updatedAt: row.updated_at })) };
  }
}
