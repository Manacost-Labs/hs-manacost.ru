import { createHash, randomUUID } from 'node:crypto';
import { ReaderCommentError } from './community-errors.js';
import { migrateCommunityControls } from './community-schema.js';
import { ReaderReactions } from './comment-reactions.js';
import { ReaderCommentBans } from './comment-bans.js';
export { ReaderCommentError } from './community-errors.js';

const CONTROL = /[\p{Cc}\u202a-\u202e\u2066-\u2069]/u;
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const own = (value, key) => Object.hasOwn(value, key);
const fail = (status, code, message) => { throw new ReaderCommentError(status, code, message); };
const digest = (value) => createHash('sha256').update(value).digest('hex');
const operationKey = (issuer, profileId, operationId) => digest(`${issuer}\u0000${profileId}\u0000${operationId}`);
const safeText = (value, field, min, max) => {
  if (typeof value !== 'string') fail(400, 'invalid_input', `${field} must be text`);
  const result = value.trim();
  if (CONTROL.test(result.replaceAll('\n', ''))) fail(400, 'invalid_input', `${field} contains controls`);
  const size = Array.from(result).length;
  if (size < min || size > max || Buffer.byteLength(result, 'utf8') > 4096) fail(400, 'invalid_input', `${field} length invalid`);
  return result;
};

export class ReaderComments {
  constructor({ db, issuer, origin = 'https://test.hs-manacost.ru', now = Date.now } = {}) {
    if (!db?.exec || !db?.prepare || typeof issuer !== 'string' || !issuer || typeof now !== 'function') fail(500, 'configuration_error');
    this.db = db; this.issuer = issuer; this.now = now;
    db.exec('BEGIN IMMEDIATE');
    try { db.exec(`CREATE TABLE IF NOT EXISTS reader_comments (
      id TEXT PRIMARY KEY, issuer TEXT NOT NULL, subject TEXT, author_profile_id TEXT, post_id INTEGER NOT NULL,
      body TEXT, parent_id TEXT, status TEXT NOT NULL CHECK(status IN ('pending','published','rejected','deleted')),
      version INTEGER NOT NULL CHECK(version > 0), profile_version INTEGER NOT NULL, operation_id TEXT,
      request_digest TEXT, public_consent INTEGER NOT NULL, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL,
      UNIQUE(issuer, author_profile_id, operation_id)
    ); CREATE INDEX IF NOT EXISTS reader_comments_post_created ON reader_comments(post_id, created_at, id);
    CREATE INDEX IF NOT EXISTS reader_comments_owner_created ON reader_comments(issuer, subject, created_at, id);
    CREATE TABLE IF NOT EXISTS reader_comment_public_profiles (
      profile_id TEXT NOT NULL, issuer TEXT NOT NULL, name TEXT NOT NULL, bio TEXT NOT NULL, favorite_class TEXT,
      avatar BLOB, avatar_version TEXT, twitch_url TEXT, youtube_url TEXT, profile_version INTEGER NOT NULL, consent_revision INTEGER NOT NULL,
      approved_at INTEGER NOT NULL, PRIMARY KEY(profile_id, issuer)
    ); CREATE TABLE IF NOT EXISTS reader_comment_erased_operations (
      operation_key TEXT PRIMARY KEY, request_digest TEXT NOT NULL, expires_at INTEGER NOT NULL
    ); CREATE TABLE IF NOT EXISTS reader_comment_rate_events (
      rate_key TEXT NOT NULL, created_at INTEGER NOT NULL, expires_at INTEGER NOT NULL
    ); CREATE INDEX IF NOT EXISTS reader_comment_rate_events_key ON reader_comment_rate_events(rate_key, created_at);
    CREATE TABLE IF NOT EXISTS reader_comment_audit (
      id TEXT PRIMARY KEY, comment_id TEXT NOT NULL, actor TEXT NOT NULL, action TEXT NOT NULL, created_at INTEGER NOT NULL
    );`);
      const columns = new Set(db.prepare('PRAGMA table_info(reader_comment_public_profiles)').all().map(row => row.name));
      if (!columns.has('twitch_url')) db.exec('ALTER TABLE reader_comment_public_profiles ADD COLUMN twitch_url TEXT');
      if (!columns.has('youtube_url')) db.exec('ALTER TABLE reader_comment_public_profiles ADD COLUMN youtube_url TEXT');
      migrateCommunityControls(db);
      db.exec('COMMIT'); } catch (error) { try { db.exec('ROLLBACK'); } catch {} throw error; }
    this.reactions = new ReaderReactions({ db, issuer, now, profile: subject => this.profile(subject) });
    this.bans = new ReaderCommentBans({ db, issuer, origin, now });
  }

  profile(subject) {
    if (typeof subject !== 'string' || !subject.trim() || subject.length > 512) fail(401, 'authentication_required');
    const row = this.db.prepare('SELECT id, display_name, bio, favorite_class, version, avatar, avatar_version, twitch_url, youtube_url FROM reader_profiles WHERE issuer = ? AND subject = ?').get(this.issuer, subject);
    if (!row) fail(404, 'profile_not_found');
    return row;
  }
  input(input) {
    const keys = ['postId', 'body', 'parentId', 'operationId', 'profileVersion', 'publicConsent'];
    if (!input || Object.getPrototypeOf(input) !== Object.prototype || Object.keys(input).length !== keys.length || !keys.every(key => own(input, key))) fail(400, 'invalid_input');
    if (!Number.isSafeInteger(input.postId) || input.postId < 1) fail(400, 'invalid_input');
    if (typeof input.operationId !== 'string' || !UUID.test(input.operationId) || !Number.isSafeInteger(input.profileVersion) || input.profileVersion < 1 || input.publicConsent !== true) fail(400, 'invalid_input');
    if (input.parentId !== null && (typeof input.parentId !== 'string' || !UUID.test(input.parentId))) fail(400, 'invalid_input');
    return { ...input, body: safeText(input.body, 'body', 2, 1000) };
  }
  dto(row, viewerProfileId = null) {
    const owner = viewerProfileId && row.author_profile_id === viewerProfileId;
    const base = { id: row.id, postId: row.post_id, parentId: row.parent_id, status: row.status, version: row.version, createdAt: row.created_at, body: row.body };
    if (row.status === 'deleted') return { ...base, body: null, author: null };
    if (owner && row.status === 'pending') return { ...base, author: { id: row.author_profile_id, name: row.current_name, bio: row.current_bio, favoriteClass: row.current_class, avatarVersion: row.current_avatar_version } };
    // These are consented snapshot values. The HTTP DTO below reduces them to
    // presence booleans, so thread readers never receive the profile URLs.
    return { ...base, author: row.public_name ? { id: row.author_profile_id, name: row.public_name, bio: row.public_bio, favoriteClass: row.public_class, avatarVersion: row.public_avatar_version, twitchUrl: row.public_twitch_url, youtubeUrl: row.public_youtube_url } : null };
  }
  submit(subject, input) {
    const data = this.input(input); const now = this.now(); const requestDigest = digest(JSON.stringify(data)); const rateKey = digest(`${this.issuer}\u0000${subject}`);
    this.db.exec('BEGIN IMMEDIATE');
    try {
      // Read the consented version under the same write lock as publication.
      const profile = this.profile(subject);
      if (this.bans.isBlocked(subject)) fail(403, 'commenting_blocked');
      const retry = this.db.prepare('SELECT * FROM reader_comments WHERE issuer = ? AND author_profile_id = ? AND operation_id = ?').get(this.issuer, profile.id, data.operationId);
      if (retry) {
        if (retry.request_digest !== requestDigest) fail(409, 'idempotency_conflict');
        if (retry.subject === null || retry.body === null) fail(410, 'erased');
        const snapshot = this.db.prepare('SELECT name,bio,favorite_class,avatar_version,twitch_url,youtube_url FROM reader_comment_public_profiles WHERE profile_id=? AND issuer=?').get(profile.id, this.issuer);
        this.db.exec('COMMIT'); return this.dto({ ...retry, current_name: profile.display_name, current_bio: profile.bio, current_class: profile.favorite_class, current_avatar_version: profile.avatar_version, public_name: snapshot?.name, public_bio: snapshot?.bio, public_class: snapshot?.favorite_class, public_avatar_version: snapshot?.avatar_version, public_twitch_url: snapshot?.twitch_url, public_youtube_url: snapshot?.youtube_url }, profile.id);
      }
      const erased = this.db.prepare('SELECT request_digest FROM reader_comment_erased_operations WHERE operation_key=? AND expires_at>?').get(operationKey(this.issuer, profile.id, data.operationId), now);
      if (erased) fail(erased.request_digest === requestDigest ? 410 : 409, erased.request_digest === requestDigest ? 'erased' : 'idempotency_conflict');
      if (profile.version !== data.profileVersion) fail(409, 'profile_version_conflict');
      const minute = this.db.prepare('SELECT count(*) AS count FROM reader_comment_rate_events WHERE rate_key=? AND created_at>?').get(rateKey, now - 60_000).count;
      const day = this.db.prepare('SELECT count(*) AS count FROM reader_comment_rate_events WHERE rate_key=? AND created_at>?').get(rateKey, now - 86_400_000).count;
      if (minute >= 5 || day >= 50) fail(429, 'rate_limited');
      if (data.parentId) {
        const parent = this.db.prepare('SELECT post_id, parent_id, status FROM reader_comments WHERE id = ? AND issuer=?').get(data.parentId, this.issuer);
        if (!parent || parent.status !== 'published' || parent.post_id !== data.postId || parent.parent_id !== null) fail(409, 'invalid_parent');
      }
      const id = randomUUID();
      this.db.prepare('INSERT INTO reader_comments VALUES (?, ?, ?, ?, ?, ?, ?, \'published\', 1, ?, ?, ?, 1, ?, ?)').run(id, this.issuer, subject, profile.id, data.postId, data.body, data.parentId, data.profileVersion, data.operationId, requestDigest, now, now);
      this.publishProfile(profile, now);
      this.db.prepare('INSERT INTO reader_comment_rate_events VALUES (?, ?, ?)').run(rateKey, now, now + 86_400_000);
      const row = this.db.prepare('SELECT * FROM reader_comments WHERE id = ?').get(id); this.db.exec('COMMIT');
      return this.dto({ ...row, public_name: profile.display_name, public_bio: profile.bio, public_class: profile.favorite_class, public_avatar_version: profile.avatar_version, public_twitch_url: profile.twitch_url, public_youtube_url: profile.youtube_url }, profile.id);
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  /** Refresh an existing public identity, never publish private edits implicitly. */
  refreshProfile(subject, { profileVersion, publicConsent } = {}) {
    if (!Number.isSafeInteger(profileVersion) || profileVersion < 1 || publicConsent !== true) fail(400, 'invalid_input');
    this.db.exec('BEGIN IMMEDIATE');
    try {
      const profile = this.profile(subject);
      if (profile.version !== profileVersion) fail(409, 'profile_version_conflict');
      if (!this.publicProfile(profile.id)) fail(404, 'public_profile_not_found');
      this.publishProfile(profile, this.now());
      this.db.exec('COMMIT');
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  /** Caller holds a transaction and has verified explicit versioned consent. */
  publishProfile(profile, now) {
    this.db.prepare(`INSERT INTO reader_comment_public_profiles (profile_id,issuer,name,bio,favorite_class,avatar,avatar_version,twitch_url,youtube_url,profile_version,consent_revision,approved_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
      ON CONFLICT(profile_id,issuer) DO UPDATE SET name=excluded.name,bio=excluded.bio,favorite_class=excluded.favorite_class,avatar=excluded.avatar,avatar_version=excluded.avatar_version,twitch_url=excluded.twitch_url,youtube_url=excluded.youtube_url,profile_version=excluded.profile_version,consent_revision=excluded.consent_revision,approved_at=excluded.approved_at
      WHERE excluded.profile_version >= reader_comment_public_profiles.profile_version`).run(profile.id, this.issuer, profile.display_name, profile.bio, profile.favorite_class, profile.avatar, profile.avatar_version, profile.twitch_url, profile.youtube_url, profile.version, now);
  }
  list(postId, { viewerSubject = null, cursor = null, limit = 20 } = {}) {
    if (!Number.isSafeInteger(postId) || postId < 1 || !Number.isSafeInteger(limit) || limit < 1 || limit > 50 || (cursor !== null && (typeof cursor !== 'string' || !UUID.test(cursor)))) fail(400, 'invalid_input');
    const viewer = viewerSubject === null ? null : this.profile(viewerSubject);
    const cursorRow = cursor && this.db.prepare("SELECT created_at FROM reader_comments WHERE id=? AND issuer=? AND post_id=? AND (status IN ('published','deleted') OR (status='pending' AND author_profile_id=?))").get(cursor, this.issuer, postId, viewer?.id ?? '');
    if (cursor && !cursorRow) fail(400, 'invalid_cursor');
    const rows = this.db.prepare(`SELECT c.*, p.display_name current_name, p.bio current_bio, p.favorite_class current_class, p.avatar_version current_avatar_version,
      s.name public_name, s.bio public_bio, s.favorite_class public_class, s.avatar_version public_avatar_version, s.twitch_url public_twitch_url, s.youtube_url public_youtube_url
      FROM reader_comments c LEFT JOIN reader_profiles p ON p.id=c.author_profile_id AND p.issuer=c.issuer LEFT JOIN reader_comment_public_profiles s ON s.profile_id=c.author_profile_id AND s.issuer=c.issuer
      WHERE c.issuer=? AND c.post_id=? AND (c.status IN ('published','deleted') OR (c.status='pending' AND c.author_profile_id=?))
      AND (? IS NULL OR c.created_at > ? OR (c.created_at=? AND c.id>?)) ORDER BY c.created_at, c.id LIMIT ?`).all(this.issuer, postId, viewer?.id ?? '', cursorRow?.created_at ?? null, cursorRow?.created_at ?? null, cursorRow?.created_at ?? null, cursor ?? '', limit);
    return { items: rows.map(row => this.dto(row, viewer?.id)), nextCursor: rows.length === limit ? rows.at(-1).id : null };
  }
  publicProfile(id) {
    if (typeof id !== 'string' || !UUID.test(id)) return null;
    const row = this.db.prepare("SELECT s.profile_id id,s.name,s.bio,s.favorite_class favoriteClass,s.avatar_version avatarVersion,s.twitch_url twitchUrl,s.youtube_url youtubeUrl FROM reader_comment_public_profiles s JOIN reader_profiles p ON p.id=s.profile_id AND p.issuer=s.issuer WHERE s.profile_id=? AND s.issuer=? AND EXISTS (SELECT 1 FROM reader_comments c WHERE c.author_profile_id=s.profile_id AND c.issuer=s.issuer AND c.status='published')").get(id, this.issuer);
    return row || null;
  }
  publicAvatar(id, version) {
    if (typeof id !== 'string' || !UUID.test(id) || typeof version !== 'string') return null;
    const row = this.db.prepare("SELECT s.avatar FROM reader_comment_public_profiles s JOIN reader_profiles p ON p.id=s.profile_id AND p.issuer=s.issuer WHERE s.profile_id=? AND s.issuer=? AND s.avatar_version=? AND EXISTS (SELECT 1 FROM reader_comments c WHERE c.author_profile_id=s.profile_id AND c.issuer=s.issuer AND c.status='published')").get(id, this.issuer, version);
    return row?.avatar ? Buffer.from(row.avatar) : null;
  }
  subjectsForProfiles(ids) {
    if (!Array.isArray(ids) || ids.length > 20 || new Set(ids).size !== ids.length || ids.some(id => typeof id !== 'string' || !UUID.test(id))) fail(400, 'invalid_input');
    return new Map(this.db.prepare(`SELECT id, subject FROM reader_profiles WHERE issuer=? AND id IN (${ids.map(() => '?').join(',') || "''"})`).all(this.issuer, ...ids).map(row => [row.id, row.subject]));
  }
  review(id, { version, decision, actor } = {}) {
    if (typeof id !== 'string' || !UUID.test(id) || !Number.isSafeInteger(version) || version < 1 || !['publish', 'reject'].includes(decision) || typeof actor !== 'string' || !actor.trim() || actor.length > 256) fail(400, 'invalid_input');
    const now = this.now(); this.db.exec('BEGIN IMMEDIATE');
    try {
      const row = this.db.prepare('SELECT * FROM reader_comments WHERE id=? AND issuer=?').get(id, this.issuer);
      if (!row || row.status !== 'pending' || row.body === null || row.author_profile_id === null || row.created_at <= now - 30 * 86_400_000 || row.version !== version) fail(409, 'review_conflict');
      if (decision === 'publish') {
        const profile = this.db.prepare('SELECT id,display_name,bio,favorite_class,version,avatar,avatar_version,twitch_url,youtube_url FROM reader_profiles WHERE id=? AND issuer=?').get(row.author_profile_id, this.issuer);
        if (!profile || profile.version !== row.profile_version) fail(409, 'profile_version_conflict');
        this.publishProfile(profile, now);
      }
      const status = decision === 'publish' ? 'published' : 'rejected';
      if (this.db.prepare('UPDATE reader_comments SET status=?,version=version+1,updated_at=? WHERE id=? AND status=\'pending\' AND version=?').run(status, now, id, version).changes !== 1) fail(409, 'review_conflict');
      this.db.prepare('INSERT INTO reader_comment_audit VALUES (?, ?, ?, ?, ?)').run(randomUUID(), id, actor.trim(), decision, now); this.db.exec('COMMIT');
      return { id, status, version: version + 1 };
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  ownExport(subject, { cursor = null, limit = 20 } = {}) {
    const profile = this.profile(subject); if (!Number.isSafeInteger(limit) || limit < 1 || limit > 100 || (cursor !== null && (typeof cursor !== 'string' || !UUID.test(cursor)))) fail(400, 'invalid_input');
    const at = cursor && this.db.prepare('SELECT created_at FROM reader_comments WHERE id=? AND issuer=? AND author_profile_id=?').get(cursor, this.issuer, profile.id); if (cursor && !at) fail(400, 'invalid_cursor');
    const rows = this.db.prepare('SELECT id,post_id,parent_id,body,status,version,created_at FROM reader_comments WHERE issuer=? AND author_profile_id=? AND (? IS NULL OR created_at>? OR (created_at=? AND id>?)) ORDER BY created_at,id LIMIT ?').all(this.issuer, profile.id, at?.created_at ?? null, at?.created_at ?? null, at?.created_at ?? null, cursor ?? '', limit);
    return { items: rows.map(row => ({ id: row.id, postId: row.post_id, parentId: row.parent_id, body: row.body, status: row.status, version: row.version, createdAt: row.created_at })), nextCursor: rows.length === limit ? rows.at(-1).id : null };
  }
  get(id) {
    if (typeof id !== 'string' || !UUID.test(id)) return null;
    const row = this.db.prepare('SELECT id,post_id,parent_id,status,version,created_at,updated_at,profile_version FROM reader_comments WHERE id=? AND issuer=?').get(id, this.issuer);
    return row && { id: row.id, postId: row.post_id, parentId: row.parent_id, status: row.status, version: row.version, profileVersion: row.profile_version, createdAt: row.created_at, updatedAt: row.updated_at };
  }
  metadataForComment(id) {
    const comment = this.get(id);
    return comment && { postId: comment.postId };
  }
  remove(subject, id, { version } = {}) {
    const profile = this.profile(subject);
    if (typeof id !== 'string' || !UUID.test(id) || !Number.isSafeInteger(version) || version < 1) fail(400, 'invalid_input');
    const now = this.now(); this.db.exec('BEGIN IMMEDIATE');
    try {
      const row = this.db.prepare('SELECT id,status,version FROM reader_comments WHERE id=? AND issuer=? AND author_profile_id=?').get(id, this.issuer, profile.id);
      if (!row) fail(404, 'comment_not_found');
      if ((row.status === 'rejected' || row.status === 'deleted') && row.version === version + 1) { this.db.exec('COMMIT'); return { id, status: row.status, version: row.version }; }
      if (row.version !== version || !['pending', 'published'].includes(row.status)) fail(409, 'comment_version_conflict');
      const status = row.status === 'published' ? 'deleted' : 'rejected';
      const changed = this.db.prepare("UPDATE reader_comments SET status=?,body=NULL,version=version+1,updated_at=? WHERE id=? AND issuer=? AND author_profile_id=? AND version=? AND status IN ('pending','published')").run(status, now, id, this.issuer, profile.id, version);
      if (changed.changes !== 1) fail(409, 'comment_version_conflict');
      this.reactions.removeForComment(id);
      this.db.exec('COMMIT'); return { id, status, version: version + 1 };
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  postIdsForProfile(id, { limit = 20 } = {}) {
    if (typeof id !== 'string' || !UUID.test(id) || !Number.isSafeInteger(limit) || limit < 1 || limit > 20) fail(400, 'invalid_input');
    return this.db.prepare("SELECT DISTINCT post_id FROM reader_comments WHERE issuer=? AND author_profile_id=? AND status='published' ORDER BY post_id LIMIT ?").all(this.issuer, id, limit).map(row => row.post_id);
  }
  listPending({ cursor = null, limit = 20 } = {}) {
    if (!Number.isSafeInteger(limit) || limit < 1 || limit > 50 || (cursor !== null && (typeof cursor !== 'string' || !UUID.test(cursor)))) fail(400, 'invalid_input');
    const at = cursor && this.db.prepare("SELECT created_at FROM reader_comments WHERE id=? AND issuer=? AND status='pending'").get(cursor, this.issuer);
    if (cursor && !at) fail(400, 'invalid_cursor');
    const rows = this.db.prepare("SELECT id,post_id,parent_id,body,version,profile_version,created_at FROM reader_comments WHERE issuer=? AND status='pending' AND body IS NOT NULL AND (? IS NULL OR created_at>? OR (created_at=? AND id>?)) ORDER BY created_at,id LIMIT ?").all(this.issuer, at?.created_at ?? null, at?.created_at ?? null, at?.created_at ?? null, cursor ?? '', limit);
    return { items: rows.map(row => ({ id: row.id, postId: row.post_id, parentId: row.parent_id, body: row.body, version: row.version, profileVersion: row.profile_version, createdAt: row.created_at })), nextCursor: rows.length === limit ? rows.at(-1).id : null };
  }
  moderatorRemove(id, { version, actor } = {}) {
    if (typeof id !== 'string' || !UUID.test(id) || !Number.isSafeInteger(version) || version < 1 || typeof actor !== 'string' || !actor.trim() || actor.length > 256) fail(400, 'invalid_input');
    const now = this.now(); this.db.exec('BEGIN IMMEDIATE');
    try {
      const row = this.db.prepare('SELECT status,body,version FROM reader_comments WHERE id=? AND issuer=?').get(id, this.issuer);
      if (!row) fail(409, 'review_conflict');
      if (['deleted', 'rejected'].includes(row.status) && row.body === null && [version, version + 1].includes(row.version)) {
        this.db.exec('COMMIT'); return { id, status: row.status, version: row.version };
      }
      if (row.version !== version) fail(409, 'review_conflict');
      const status = ['published', 'deleted'].includes(row.status) ? 'deleted' : 'rejected';
      const changed = this.db.prepare('UPDATE reader_comments SET status=?,body=NULL,version=version+1,updated_at=? WHERE id=? AND issuer=? AND version=?').run(status, now, id, this.issuer, version);
      if (changed.changes !== 1) fail(409, 'review_conflict');
      this.reactions.removeForComment(id);
      this.db.prepare('INSERT INTO reader_comment_audit VALUES (?, ?, ?, ?, ?)').run(randomUUID(), id, actor.trim(), 'takedown', now);
      this.db.exec('COMMIT'); return { id, status, version: version + 1 };
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  erase(subject) {
    const profile = this.profile(subject); const now = this.now(); this.db.exec('BEGIN IMMEDIATE');
    try {
      const count = this.db.prepare('SELECT count(*) count FROM reader_comments WHERE issuer=? AND author_profile_id=?').get(this.issuer, profile.id).count;
      if (count > 5000) fail(409, 'erasure_needs_operator');
      this.reactions.erase(profile.id);
      const operations = this.db.prepare('SELECT operation_id,request_digest FROM reader_comments WHERE issuer=? AND author_profile_id=? AND operation_id IS NOT NULL').all(this.issuer, profile.id);
      for (const item of operations) this.db.prepare('INSERT OR REPLACE INTO reader_comment_erased_operations VALUES (?, ?, ?)').run(operationKey(this.issuer, profile.id, item.operation_id), item.request_digest, now + 86_400_000);
      this.db.prepare("UPDATE reader_comments SET author_profile_id=NULL,subject=NULL,operation_id=NULL,request_digest=NULL,body=NULL,status=CASE WHEN status='published' THEN 'deleted' ELSE status END,version=version+1,updated_at=? WHERE issuer=? AND author_profile_id=?").run(now, this.issuer, profile.id);
      this.db.prepare('DELETE FROM reader_comment_public_profiles WHERE profile_id=? AND issuer=?').run(profile.id, this.issuer); this.db.exec('COMMIT'); return { erased: true };
    } catch (error) { try { this.db.exec('ROLLBACK'); } catch {} throw error; }
  }
  cleanup() {
    const now = this.now(); const comments = this.db.prepare("UPDATE reader_comments SET body=NULL,status=CASE WHEN status='pending' THEN 'rejected' ELSE status END,version=version+1,updated_at=? WHERE status IN ('pending','rejected') AND body IS NOT NULL AND created_at <= ?").run(now, now - 30 * 86_400_000).changes;
    const audit = this.db.prepare('DELETE FROM reader_comment_audit WHERE created_at <= ?').run(now - 90 * 86_400_000).changes;
    this.db.prepare('DELETE FROM reader_comment_erased_operations WHERE expires_at <= ?').run(now);
    this.db.prepare('DELETE FROM reader_comment_rate_events WHERE expires_at <= ?').run(now);
    this.db.prepare('DELETE FROM reader_reaction_events WHERE issuer=? AND created_at<=?').run(this.issuer, now - 86_400_000);
    this.db.prepare('DELETE FROM reader_community_audit WHERE issuer=? AND created_at<=?').run(this.issuer, now - 90 * 86_400_000);
    return { comments, audit };
  }
}
