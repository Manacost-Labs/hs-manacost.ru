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

export class ReaderProfiles {
  constructor({ db, issuer, now = Date.now } = {}) {
    if (!db || typeof db.exec !== 'function' || typeof db.prepare !== 'function') throw new ProfileValidationError('database required');
    if (typeof issuer !== 'string' || !issuer) throw new ProfileValidationError('issuer required');
    if (typeof now !== 'function') throw new ProfileValidationError('clock required');
    this.db = db;
    this.issuer = issuer;
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
      UNIQUE (issuer, subject)
    )`);
    this.statements = {
      select: db.prepare('SELECT id, display_name, bio, favorite_class, version, avatar_version FROM reader_profiles WHERE issuer = ? AND subject = ?'),
      insert: db.prepare('INSERT OR IGNORE INTO reader_profiles (id, issuer, subject, display_name, bio, favorite_class, version, avatar, avatar_version) VALUES (?, ?, ?, ?, \'\', NULL, 1, NULL, NULL)'),
      update: db.prepare('UPDATE reader_profiles SET display_name = ?, bio = ?, favorite_class = ?, version = version + 1 WHERE issuer = ? AND subject = ? AND version = ?'),
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
    if (!input || Object.getPrototypeOf(input) !== Object.prototype || Object.keys(input).length !== 4
      || !['version', 'displayName', 'bio', 'favoriteClass'].every(key => Object.hasOwn(input, key))) {
      throw new ProfileValidationError('complete profile fields required');
    }
    if (!Number.isSafeInteger(input.version) || input.version < 1) throw new ProfileValidationError('profile version invalid');
    const displayName = text(input.displayName, { minimum: 2, maximum: 40, field: 'displayName' });
    const bio = text(input.bio, { minimum: 0, maximum: 280, allowNewline: true, field: 'bio' });
    if (input.favoriteClass !== null && !CLASSES.has(input.favoriteClass)) throw new ProfileValidationError('favoriteClass invalid');
    const result = this.statements.update.run(displayName, bio, input.favoriteClass, this.issuer, subject, input.version);
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
