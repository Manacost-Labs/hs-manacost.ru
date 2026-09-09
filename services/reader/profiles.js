import { randomBytes, randomUUID } from 'node:crypto';

export class ProfileValidationError extends Error {
  constructor(message) { super(message); this.name = 'ProfileValidationError'; }
}

export class ProfileConflictError extends Error {
  constructor(message = 'profile version conflict') { super(message); this.name = 'ProfileConflictError'; }
}

const CLASSES = new Set(['death-knight', 'demon-hunter', 'druid', 'hunter', 'mage', 'paladin', 'priest', 'rogue', 'shaman', 'warlock', 'warrior']);
const CONTROL = /[\p{Cc}\u202a-\u202e\u2066-\u2069]/u;
const FALLBACK_CONTROL = /[\p{Cc}\u202a-\u202e\u2066-\u2069]/gu;
const MAX_SUBJECT_LENGTH = 512;
const LEGACY_PROFILE_FIELDS = [ 'version', 'displayName', 'bio', 'favoriteClass' ];
const PROFILE_FIELDS = [ ...LEGACY_PROFILE_FIELDS, 'twitchUrl', 'youtubeUrl' ];

function boundedSubject(subject) {
  if (typeof subject !== 'string' || !subject.trim() || subject.length > MAX_SUBJECT_LENGTH) throw new ProfileValidationError('authenticated subject required');
  return subject;
}

function codepoints(value) { return Array.from(value); }

function text(value, { minimum, maximum, allowNewline = false, field }) {
  if (typeof value !== 'string') throw new ProfileValidationError(`${field} must be text`);
  const trimmed = value.trim();
  if (CONTROL.test(trimmed) && (!allowNewline || /[\p{Cc}\u202a-\u202e\u2066-\u2069]/u.test(trimmed.replaceAll('\n', '')))) {
    throw new ProfileValidationError(`${field} contains control characters`);
  }
  const length = codepoints(trimmed).length;
  if (length < minimum || length > maximum) throw new ProfileValidationError(`${field} length invalid`);
  return trimmed;
}

function defaultName(value) {
  if (typeof value !== 'string') return 'Читатель';
  const cleaned = codepoints(value.replace(FALLBACK_CONTROL, '').trim()).slice(0, 40).join('');
  return codepoints(cleaned).length >= 2 ? cleaned : 'Читатель';
}

function avatarVersion() { return randomBytes(24).toString('base64url'); }

/**
 * Social accounts are public profile links, not integrations. Keep the accepted
 * shapes deliberately small so rendering never needs to trust arbitrary URLs.
 */
function socialUrl(value, service) {
  if (value === null) return null;
  if (typeof value !== 'string' || value.length > 200 || CONTROL.test(value)) throw new ProfileValidationError(`${service}Url invalid`);
  const raw = value.trim();
  if (!raw) throw new ProfileValidationError(`${service}Url invalid`);
  let url;
  try { url = new URL(raw); } catch { throw new ProfileValidationError(`${service}Url invalid`); }
  if (url.protocol !== 'https:' || url.username || url.password || url.port || url.search || url.hash) throw new ProfileValidationError(`${service}Url invalid`);
  const host = url.hostname.toLowerCase();
  if (service === 'twitch') {
    if (!/^(?:www\.)?twitch\.tv$/i.test(host)) throw new ProfileValidationError('twitchUrl invalid');
    const match = url.pathname.match(/^\/([a-z0-9_]{4,25})\/?$/i);
    if (!match) throw new ProfileValidationError('twitchUrl invalid');
    return `https://www.twitch.tv/${match[1].toLowerCase()}`;
  }
  if (!/^(?:www\.|m\.)?youtube\.com$/i.test(host)) throw new ProfileValidationError('youtubeUrl invalid');
  const handle = url.pathname.match(/^\/@([a-z0-9_.-]{3,30})$/i);
  if (handle) return `https://www.youtube.com/@${handle[1]}`;
  const channel = url.pathname.match(/^\/channel\/(UC[a-z0-9_-]{22})$/i);
  if (channel) return `https://www.youtube.com/channel/${channel[1]}`;
  throw new ProfileValidationError('youtubeUrl invalid');
}

function matchingProfileFields(input) {
  if (!input || Object.getPrototypeOf(input) !== Object.prototype) return null;
  const keys = Object.keys(input);
  if (keys.length === LEGACY_PROFILE_FIELDS.length && LEGACY_PROFILE_FIELDS.every(key => Object.hasOwn(input, key))) return false;
  if (keys.length === PROFILE_FIELDS.length && PROFILE_FIELDS.every(key => Object.hasOwn(input, key))) return true;
  return null;
}

export class ReaderProfiles {
  constructor({ db, issuer, now = Date.now } = {}) {
    if (!db || typeof db.exec !== 'function' || typeof db.prepare !== 'function') throw new ProfileValidationError('database required');
    if (typeof issuer !== 'string' || !issuer) throw new ProfileValidationError('issuer required');
    if (typeof now !== 'function') throw new ProfileValidationError('clock required');
    this.db = db;
    this.issuer = issuer;
    db.exec('BEGIN IMMEDIATE');
    try {
      db.exec(`CREATE TABLE IF NOT EXISTS reader_profiles (
      id TEXT PRIMARY KEY,
      issuer TEXT NOT NULL,
      subject TEXT NOT NULL,
      display_name TEXT NOT NULL,
      bio TEXT NOT NULL,
      favorite_class TEXT,
      version INTEGER NOT NULL CHECK (version > 0),
      avatar BLOB,
      avatar_version TEXT,
      twitch_url TEXT,
      youtube_url TEXT,
      UNIQUE (issuer, subject)
    )`);
      const columns = new Set(db.prepare('PRAGMA table_info(reader_profiles)').all().map(row => row.name));
      if (!columns.has('twitch_url')) db.exec('ALTER TABLE reader_profiles ADD COLUMN twitch_url TEXT');
      if (!columns.has('youtube_url')) db.exec('ALTER TABLE reader_profiles ADD COLUMN youtube_url TEXT');
      db.exec('COMMIT');
    } catch (error) { try { db.exec('ROLLBACK'); } catch {} throw error; }
    this.statements = {
      select: db.prepare('SELECT id, display_name, bio, favorite_class, version, avatar_version, twitch_url, youtube_url FROM reader_profiles WHERE issuer = ? AND subject = ?'),
      insert: db.prepare('INSERT OR IGNORE INTO reader_profiles (id, issuer, subject, display_name, bio, favorite_class, version, avatar, avatar_version, twitch_url, youtube_url) VALUES (?, ?, ?, ?, \'\', NULL, 1, NULL, NULL, NULL, NULL)'),
      update: db.prepare('UPDATE reader_profiles SET display_name = ?, bio = ?, favorite_class = ?, twitch_url = ?, youtube_url = ?, version = version + 1 WHERE issuer = ? AND subject = ? AND version = ?'),
      avatar: db.prepare('SELECT avatar, avatar_version FROM reader_profiles WHERE issuer = ? AND subject = ?'),
      setAvatar: db.prepare('UPDATE reader_profiles SET avatar = ?, avatar_version = ?, version = version + 1 WHERE issuer = ? AND subject = ? AND version = ?'),
    };
  }

  dto(row) {
    return {
      id: row.id,
      displayName: row.display_name,
      bio: row.bio,
      favoriteClass: row.favorite_class,
      twitchUrl: row.twitch_url,
      youtubeUrl: row.youtube_url,
      version: row.version,
      avatarUrl: row.avatar_version ? `/reader-api/v1/profile/avatar?v=${row.avatar_version}` : null,
    };
  }

  row(subject) { return this.statements.select.get(this.issuer, boundedSubject(subject)); }

  getOrCreate(subject, fallbackDisplayName) {
    subject = boundedSubject(subject);
    this.statements.insert.run(randomUUID(), this.issuer, subject, defaultName(fallbackDisplayName));
    return this.dto(this.statements.select.get(this.issuer, subject));
  }

  update(subject, input) {
    subject = boundedSubject(subject);
    const includesSocials = matchingProfileFields(input);
    if (includesSocials === null) {
      throw new ProfileValidationError('complete profile fields required');
    }
    if (!Number.isSafeInteger(input.version) || input.version < 1) throw new ProfileValidationError('profile version invalid');
    const displayName = text(input.displayName, { minimum: 2, maximum: 40, field: 'displayName' });
    const bio = text(input.bio, { minimum: 0, maximum: 280, allowNewline: true, field: 'bio' });
    if (input.favoriteClass !== null && !CLASSES.has(input.favoriteClass)) throw new ProfileValidationError('favoriteClass invalid');
    const current = this.row(subject);
    if (!current) throw new ProfileConflictError();
    const twitchUrl = includesSocials ? socialUrl(input.twitchUrl, 'twitch') : current.twitch_url;
    const youtubeUrl = includesSocials ? socialUrl(input.youtubeUrl, 'youtube') : current.youtube_url;
    const result = this.statements.update.run(displayName, bio, input.favoriteClass, twitchUrl, youtubeUrl, this.issuer, subject, input.version);
    if (result.changes !== 1) throw new ProfileConflictError();
    return this.dto(this.statements.select.get(this.issuer, subject));
  }

  setAvatar(subject, bufferOrNull, version) {
    subject = boundedSubject(subject);
    if (bufferOrNull !== null && (!Buffer.isBuffer(bufferOrNull) || bufferOrNull.length === 0 || bufferOrNull.length > 128 * 1024)) {
      throw new ProfileValidationError('normalized avatar bytes invalid');
    }
    if (!Number.isSafeInteger(version) || version < 1) throw new ProfileValidationError('profile version invalid');
    const result = this.statements.setAvatar.run(bufferOrNull, bufferOrNull === null ? null : avatarVersion(), this.issuer, subject, version);
    if (result.changes !== 1) throw new ProfileConflictError();
    return this.dto(this.statements.select.get(this.issuer, subject));
  }

  avatar(subject) {
    const row = this.statements.avatar.get(this.issuer, boundedSubject(subject));
    return row?.avatar ? { bytes: Buffer.from(row.avatar), version: row.avatar_version } : null;
  }
}
