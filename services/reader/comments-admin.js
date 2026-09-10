import { DatabaseSync } from 'node:sqlite';
import { lstatSync } from 'node:fs';
import { isAbsolute } from 'node:path';
import { ReaderComments } from './comments-store.js';
import { ReaderReactions } from './comment-reactions.js';

export const STAGING_ORIGIN = 'https://test.hs-manacost.ru';
export const PRODUCTION_ISSUER = 'https://hearthpulse.net/identity';

export class ReaderCommentsAdminError extends Error {
  constructor(code, message = code) { super(message); this.name = 'ReaderCommentsAdminError'; this.code = code; }
}

function fail(code, message) { throw new ReaderCommentsAdminError(code, message); }

function stagingEnvironment(env) {
  if (env.READER_DEPLOYMENT !== 'staging' || env.READER_ORIGIN !== STAGING_ORIGIN || env.READER_ISSUER !== PRODUCTION_ISSUER || env.READER_COMMENTS_ENABLED !== '1') {
    fail('staging_only', 'requires explicit staging deployment, exact staging origin, and enabled comments feature');
  }
}

function requiredSchema(db) {
  const names = db.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('reader_profiles','reader_comments','reader_comment_public_profiles','reader_comment_audit','reader_comment_reactions')").all().map(row => row.name);
  if (names.length !== 5) fail('schema_unavailable', 'database has not been initialized by the reader service');
}

function commentsFacade(db, now) {
  // Do not invoke ReaderComments' schema-creating constructor in this operator tool.
  const comments = Object.assign(Object.create(ReaderComments.prototype), { db, issuer: PRODUCTION_ISSUER, now });
  comments.reactions = new ReaderReactions({ db, issuer: PRODUCTION_ISSUER, now, profile: subject => comments.profile(subject) });
  return comments;
}

export function openCommentsAdmin({ filename, env = process.env, now = Date.now } = {}) {
  stagingEnvironment(env);
  if (typeof filename !== 'string' || !isAbsolute(filename)) fail('invalid_database', 'database path must be absolute');
  let stat;
  try { stat = lstatSync(filename); } catch { fail('database_missing', 'database must already exist'); }
  if (!stat.isFile()) fail('invalid_database', 'database path must be a regular file');
  const db = new DatabaseSync(filename);
  try { requiredSchema(db); } catch (error) { db.close(); throw error; }
  return { db, comments: commentsFacade(db, now), close: () => db.close() };
}

function bounded(value, maximum, label) {
  const parsed = Number(value);
  if (!Number.isSafeInteger(parsed) || parsed < 1 || parsed > maximum) fail('invalid_argument', `${label} must be 1..${maximum}`);
  return parsed;
}

export function listPending(admin, { limit = 20, cursor = null } = {}) {
  const page = admin.comments.listPending({ limit: bounded(limit, 50, 'limit'), cursor });
  return { ...page, items: page.items.map(({ body, ...item }) => item) };
}

export function inspect(admin, id, { includeAvatar = false } = {}) {
  const row = admin.db.prepare(`SELECT c.id,c.post_id,c.parent_id,c.body,c.status,c.version,c.profile_version,c.created_at,
    p.display_name,p.bio,p.favorite_class,p.version profile_version_current,p.avatar,p.avatar_version
    FROM reader_comments c JOIN reader_profiles p ON p.id=c.author_profile_id AND p.issuer=c.issuer
    WHERE c.id=? AND c.issuer=?`).get(id, PRODUCTION_ISSUER);
  if (!row) fail('comment_not_found');
  const result = {
    id: row.id, postId: row.post_id, parentId: row.parent_id, body: row.body, status: row.status,
    version: row.version, profileVersion: row.profile_version, createdAt: row.created_at,
    proposedProfile: { name: row.display_name, bio: row.bio, favoriteClass: row.favorite_class, version: row.profile_version_current, avatarVersion: row.avatar_version },
  };
  if (includeAvatar && row.avatar) {
    const avatar = Buffer.from(row.avatar);
    if (avatar.length > 128 * 1024 || avatar.subarray(0, 4).toString('ascii') !== 'RIFF' || avatar.subarray(8, 12).toString('ascii') !== 'WEBP') fail('invalid_avatar');
    result.avatarDataUri = `data:image/webp;base64,${avatar.toString('base64')}`;
  }
  return result;
}

export function decide(admin, id, { version, decision, actor, expectedProfileVersion, expectedAvatarVersion } = {}) {
  if (!['publish', 'reject'].includes(decision)) fail('invalid_argument', 'decision must be publish or reject');
  if (decision === 'publish') {
    if (!Number.isSafeInteger(expectedProfileVersion) || expectedProfileVersion < 1 || (expectedAvatarVersion !== null && typeof expectedAvatarVersion !== 'string')) fail('invalid_argument', 'publish requires inspected profile and avatar versions');
    const profile = admin.db.prepare(`SELECT p.version,p.avatar_version FROM reader_comments c JOIN reader_profiles p ON p.id=c.author_profile_id AND p.issuer=c.issuer WHERE c.id=? AND c.issuer=?`).get(id, PRODUCTION_ISSUER);
    if (!profile || profile.version !== expectedProfileVersion || profile.avatar_version !== expectedAvatarVersion) fail('profile_confirmation_conflict');
  }
  return admin.comments.review(id, { version: bounded(version, Number.MAX_SAFE_INTEGER, 'version'), decision, actor });
}

export function takedown(admin, id, { version, actor } = {}) {
  return admin.comments.moderatorRemove(id, { version: bounded(version, Number.MAX_SAFE_INTEGER, 'version'), actor });
}

function options(argv, allowed) {
  const result = {};
  for (let index = 0; index < argv.length; index += 1) {
    const key = argv[index]; const name = key.slice(2);
    if (!key.startsWith('--') || !allowed.has(name) || Object.hasOwn(result, name)) fail('invalid_argument');
    if (name === 'include-avatar') { result[name] = true; continue; }
    const value = argv[++index]; if (value === undefined || value.startsWith('--')) fail('invalid_argument');
    result[name] = value;
  }
  return result;
}

export function run(argv, { env = process.env, write = console.log, now = Date.now } = {}) {
  const [command, ...rest] = argv;
  const allowed = new Set({ pending: ['db', 'limit', 'cursor'], inspect: ['db', 'id', 'include-avatar'], review: ['db', 'id', 'version', 'decision', 'actor', 'expected-profile-version', 'expected-avatar-version'], takedown: ['db', 'id', 'version', 'actor'] }[command] ?? []);
  const args = options(rest, allowed);
  const required = { pending: ['db'], inspect: ['db', 'id'], review: ['db', 'id', 'version', 'decision', 'actor'], takedown: ['db', 'id', 'version', 'actor'] }[command];
  if (!required || required.some(key => typeof args[key] !== 'string')) fail('invalid_argument');
  const admin = openCommentsAdmin({ filename: args.db, env, now });
  try {
    let result;
    if (command === 'pending') result = listPending(admin, args);
    else if (command === 'inspect') result = inspect(admin, args.id, { includeAvatar: args['include-avatar'] === true });
    else if (command === 'review') result = decide(admin, args.id, { ...args, expectedProfileVersion: args['expected-profile-version'] === undefined ? undefined : bounded(args['expected-profile-version'], Number.MAX_SAFE_INTEGER, 'expected-profile-version'), expectedAvatarVersion: args['expected-avatar-version'] === 'null' ? null : args['expected-avatar-version'] });
    else if (command === 'takedown') result = takedown(admin, args.id, args);
    else fail('invalid_argument', 'command must be pending, inspect, review, or takedown');
    write(JSON.stringify(result)); return result;
  } finally { admin.close(); }
}

if (process.argv[1] && import.meta.url === new URL(`file://${process.argv[1]}`).href) {
  try { run(process.argv.slice(2)); } catch (error) { process.stderr.write(`${error.code ?? 'error'}\n`); process.exitCode = 1; }
}
