/** Additive, repeatable schema. The caller holds the existing community migration transaction. */
export function migrateCommunityControls(db) {
  db.exec(`CREATE TABLE IF NOT EXISTS reader_comment_reactions (
    issuer TEXT NOT NULL, comment_id TEXT NOT NULL, profile_id TEXT NOT NULL,
    kind TEXT NOT NULL CHECK(kind IN ('like','thanks','fire')), updated_at INTEGER NOT NULL,
    PRIMARY KEY(issuer,comment_id,profile_id)
  ); CREATE INDEX IF NOT EXISTS reader_reactions_owner ON reader_comment_reactions(issuer,profile_id,comment_id);
  CREATE TABLE IF NOT EXISTS reader_reaction_events (
    issuer TEXT NOT NULL, rate_key TEXT NOT NULL, created_at INTEGER NOT NULL
  ); CREATE INDEX IF NOT EXISTS reader_reaction_events_owner ON reader_reaction_events(issuer,rate_key,created_at);
  CREATE TABLE IF NOT EXISTS reader_comment_bans (
    id TEXT PRIMARY KEY, issuer TEXT NOT NULL, reader_key TEXT NOT NULL, profile_id TEXT,
    blocked INTEGER NOT NULL CHECK(blocked IN (0,1)), version INTEGER NOT NULL CHECK(version > 0),
    updated_at INTEGER NOT NULL, UNIQUE(issuer,reader_key)
  ); CREATE INDEX IF NOT EXISTS reader_bans_listing ON reader_comment_bans(issuer,blocked,id);
  CREATE TABLE IF NOT EXISTS reader_community_audit (
    id TEXT PRIMARY KEY, issuer TEXT NOT NULL, target_id TEXT NOT NULL, actor_key TEXT NOT NULL,
    action TEXT NOT NULL CHECK(action IN ('ban','unban')), created_at INTEGER NOT NULL
  );`);
}
