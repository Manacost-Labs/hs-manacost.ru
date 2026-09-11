import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { DatabaseSync } from 'node:sqlite';
import test from 'node:test';
import { ReaderComments } from '../comments-store.js';
import { ReaderProfiles } from '../profiles.js';

function fixture(t) {
  const db = new DatabaseSync(':memory:'); t.after(() => db.close());
  const issuer = 'https://hearthpulse.net/identity';
  const profiles = new ReaderProfiles({ db, issuer });
  const comments = new ReaderComments({ db, issuer });
  const profile = profiles.getOrCreate('reader-one', 'Алиса');
  const input = (overrides = {}) => ({ postId: 17, body: 'Полезный разбор!', parentId: null,
    operationId: randomUUID(), profileVersion: profile.version, attachmentId: null, ...overrides });
  return { db, profiles, comments, profile, input };
}

test('a posted comment and its current public profile are immediately visible to a guest', t => {
  const f = fixture(t);
  const avatar = f.profiles.setAvatar('reader-one', Buffer.from('synthetic-avatar'), 1);
  const request = f.input({ profileVersion: avatar.version });
  const comment = f.comments.submit('reader-one', request);
  assert.equal(comment.status, 'published');
  assert.equal(comment.version, 1);
  assert.equal(comment.author.name, 'Алиса');
  assert.deepEqual(f.comments.list(17).items, [comment]);
  assert.equal(f.comments.publicProfile(f.profile.id).name, 'Алиса');
  assert.deepEqual(f.comments.publicAvatar(f.profile.id, comment.author.avatarVersion), Buffer.from('synthetic-avatar'));
  assert.deepEqual(f.comments.listPending().items, []);
  assert.deepEqual(f.comments.submit('reader-one', request), comment);
});

test('a stale profile cannot publish private changes; a new comment can', t => {
  const f = fixture(t);
  const original = f.input();
  f.comments.submit('reader-one', original);
  const changed = f.profiles.update('reader-one', {
    version: 1, displayName: 'Новое имя', bio: 'Личное описание', favoriteClass: 'mage',
    twitchUrl: 'https://twitch.tv/mana_cost', youtubeUrl: 'https://youtube.com/@Manacost',
  });
  assert.equal(f.comments.publicProfile(f.profile.id).name, 'Алиса');
  assert.throws(() => f.comments.submit('reader-one', f.input()), { code: 'profile_version_conflict' });
  assert.throws(() => f.comments.submit('reader-one', { ...f.input({ profileVersion: changed.version }), publicConsent: true }), { code: 'invalid_input' });
  assert.equal(f.comments.publicProfile(f.profile.id).bio, '');
  assert.equal(f.comments.publicProfile(f.profile.id).twitchUrl, null);
  // An acknowledged old request is a retry, never consent to a later private edit.
  assert.equal(f.comments.submit('reader-one', original).author.name, 'Алиса');
  const next = f.comments.submit('reader-one', f.input({ profileVersion: changed.version }));
  assert.equal(next.author.name, 'Новое имя');
  assert.equal(f.comments.publicProfile(f.profile.id).bio, 'Личное описание');
  assert.equal(f.comments.publicProfile(f.profile.id).twitchUrl, 'https://www.twitch.tv/mana_cost');
  assert.equal(f.comments.publicProfile(f.profile.id).youtubeUrl, 'https://www.youtube.com/@Manacost');
  assert.equal(f.comments.list(17).items.every(item => item.author.name === 'Новое имя'), true);
});

test('publication and profile snapshot roll back together on storage failure', t => {
  const f = fixture(t);
  f.db.exec("CREATE TRIGGER fail_snapshot BEFORE INSERT ON reader_comment_public_profiles BEGIN SELECT RAISE(ABORT, 'synthetic storage failure'); END");
  assert.throws(() => f.comments.submit('reader-one', f.input()), /synthetic storage failure/);
  assert.deepEqual(f.comments.list(17).items, []);
  assert.equal(f.comments.publicProfile(f.profile.id), null);
  assert.equal(f.db.prepare('SELECT count(*) n FROM reader_comment_rate_events').get().n, 0);
});

test('public profile snapshot migration adds social columns without replacing existing consented data', t => {
  const db = new DatabaseSync(':memory:'); t.after(() => db.close());
  const issuer = 'https://hearthpulse.net/identity';
  new ReaderProfiles({ db, issuer });
  db.exec(`CREATE TABLE reader_comment_public_profiles (
    profile_id TEXT NOT NULL, issuer TEXT NOT NULL, name TEXT NOT NULL, bio TEXT NOT NULL, favorite_class TEXT,
    avatar BLOB, avatar_version TEXT, profile_version INTEGER NOT NULL, consent_revision INTEGER NOT NULL,
    approved_at INTEGER NOT NULL, PRIMARY KEY(profile_id, issuer)
  ); INSERT INTO reader_comment_public_profiles VALUES ('123e4567-e89b-42d3-a456-426614174000', '${issuer}', 'Алиса', '', NULL, NULL, NULL, 1, 1, 1)`);
  new ReaderComments({ db, issuer });
  const columns = db.prepare('PRAGMA table_info(reader_comment_public_profiles)').all().map(row => row.name);
  assert.ok(columns.includes('twitch_url')); assert.ok(columns.includes('youtube_url'));
  assert.equal(db.prepare('SELECT name FROM reader_comment_public_profiles').get().name, 'Алиса');
});

test('immediate replies remain one level, same-article and parent-status checked', t => {
  const f = fixture(t);
  const root = f.comments.submit('reader-one', f.input());
  const reply = f.comments.submit('reader-one', f.input({ parentId: root.id }));
  assert.equal(reply.status, 'published');
  assert.throws(() => f.comments.submit('reader-one', f.input({ parentId: reply.id })), { code: 'invalid_parent' });
  assert.throws(() => f.comments.submit('reader-one', f.input({ postId: 18, parentId: root.id })), { code: 'invalid_parent' });
  f.comments.moderatorRemove(root.id, { version: 1, actor: 'test-operator' });
  assert.throws(() => f.comments.submit('reader-one', f.input({ parentId: root.id })), { code: 'invalid_parent' });
  assert.equal(f.comments.list(17).items.find(item => item.id === reply.id).status, 'published');
});

test('direct publication retains limits, deletion CAS, export and erasure replay protection', t => {
  const f = fixture(t); const request = f.input();
  const first = f.comments.submit('reader-one', request);
  for (let i = 0; i < 4; i++) f.comments.submit('reader-one', f.input());
  assert.throws(() => f.comments.submit('reader-one', f.input()), { code: 'rate_limited' });
  assert.equal(f.comments.submit('reader-one', request).id, first.id);
  assert.throws(() => f.comments.remove('reader-one', first.id, { version: 2 }), { code: 'comment_version_conflict' });
  const deleted = f.comments.remove('reader-one', first.id, { version: 1 });
  assert.deepEqual(deleted, { id: first.id, status: 'deleted', version: 2 });
  assert.deepEqual(f.comments.remove('reader-one', first.id, { version: 1 }), deleted);
  assert.equal(f.comments.ownExport('reader-one').items.length, 5);
  f.comments.erase('reader-one');
  assert.equal(f.comments.publicProfile(f.profile.id), null);
  assert.equal(f.comments.list(17).items.every(item => item.body === null && item.author === null), true);
  assert.throws(() => f.comments.submit('reader-one', request), { code: 'erased' });
  assert.throws(() => f.comments.submit('reader-one', f.input()), { code: 'rate_limited' });
});
