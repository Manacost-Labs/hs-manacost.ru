import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { DatabaseSync } from 'node:sqlite';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { decide, inspect, listPending, openCommentsAdmin, PRODUCTION_ISSUER, ReaderCommentsAdminError, run, takedown } from '../comments-admin.js';
import { ReaderComments } from '../comments-store.js';
import { ReaderProfiles } from '../profiles.js';

const env = { READER_DEPLOYMENT: 'staging', READER_ORIGIN: 'https://test.hs-manacost.ru', READER_ISSUER: 'https://hearthpulse.net/identity', READER_COMMENTS_ENABLED: '1' };
const error = (fn, code) => assert.throws(fn, item => item instanceof ReaderCommentsAdminError && item.code === code);
function fixture() {
  const dir = mkdtempSync(join(tmpdir(), 'reader-admin-')); const filename = join(dir, 'reader.sqlite'); const db = new DatabaseSync(filename);
  const profiles = new ReaderProfiles({ db, issuer: 'https://hearthpulse.net/identity', now: () => 1 }); const comments = new ReaderComments({ db, issuer: 'https://hearthpulse.net/identity', now: () => 1 });
  const profile = profiles.getOrCreate('private-subject', 'Модерируемый'); const item = comments.submit('private-subject', { postId: 1, body: 'Проверяемый текст', parentId: null, operationId: randomUUID(), profileVersion: profile.version, publicConsent: true }); db.close();
  return { filename, item, cleanup: () => rmSync(dir, { recursive: true, force: true }) };
}

test('admin fails closed outside explicit staging and never creates databases', () => {
  const item = fixture(); try {
    error(() => openCommentsAdmin({ filename: item.filename, env: { ...env, READER_DEPLOYMENT: 'production' } }), 'staging_only');
    error(() => openCommentsAdmin({ filename: item.filename, env: { ...env, READER_ISSUER: 'https://hearthpulse.net' } }), 'staging_only');
    error(() => openCommentsAdmin({ filename: item.filename, env: { ...env, READER_COMMENTS_ENABLED: '0' } }), 'staging_only');
    error(() => openCommentsAdmin({ filename: 'relative.sqlite', env }), 'invalid_database');
    error(() => openCommentsAdmin({ filename: join(tmpdir(), `missing-${randomUUID()}.sqlite`), env }), 'database_missing');
  } finally { item.cleanup(); }
});

test('list and inspect expose no upstream subject, and review/takedown require exact CAS inputs', () => {
  const item = fixture(); try {
    const admin = openCommentsAdmin({ filename: item.filename, env, now: () => 1 });
    const pending = listPending(admin); assert.equal(JSON.stringify(pending).includes('private-subject'), false); assert.equal(JSON.stringify(pending).includes('Проверяемый текст'), false);
    const detail = inspect(admin, item.item.id); assert.equal(JSON.stringify(detail).includes('private-subject'), false);
    error(() => decide(admin, item.item.id, { version: 0, decision: 'publish', actor: 'mod' }), 'invalid_argument');
    error(() => decide(admin, item.item.id, { version: 1, decision: 'nope', actor: 'mod' }), 'invalid_argument');
    error(() => decide(admin, item.item.id, { version: 1, decision: 'publish', actor: 'mod' }), 'invalid_argument');
    assert.deepEqual(decide(admin, item.item.id, { version: 1, decision: 'publish', actor: 'mod', expectedProfileVersion: 1, expectedAvatarVersion: null }), { id: item.item.id, status: 'published', version: 2 });
    assert.deepEqual(takedown(admin, item.item.id, { version: 2, actor: 'mod' }), { id: item.item.id, status: 'deleted', version: 3 });
    admin.close();
  } finally { item.cleanup(); }
});

test('publish rejects stale operator snapshot confirmation', () => {
  const item = fixture(); try {
    const admin = openCommentsAdmin({ filename: item.filename, env, now: () => 1 });
    error(() => decide(admin, item.item.id, { version: 1, decision: 'publish', actor: 'mod', expectedProfileVersion: 2, expectedAvatarVersion: null }), 'profile_confirmation_conflict');
    admin.close();
  } finally { item.cleanup(); }
});

test('argument parser rejects duplicates and accepts include-avatar in any position', () => {
  const item = fixture(); try {
    error(() => run(['inspect', '--db', item.filename, '--id', item.item.id, '--id', item.item.id], { env, now: () => 1 }), 'invalid_argument');
    const result = run(['inspect', '--include-avatar', '--db', item.filename, '--id', item.item.id], { env, now: () => 1, write: () => {} });
    assert.equal(result.avatarDataUri, undefined);
  } finally { item.cleanup(); }
});

test('inspect accepts canonical WebP avatars through 128 KiB and rejects larger bytes', () => {
  const item = fixture(); try {
    const db = new DatabaseSync(item.filename);
    const valid = Buffer.alloc(96 * 1024); valid.write('RIFF'); valid.write('WEBP', 8);
    db.prepare('UPDATE reader_profiles SET avatar=?, avatar_version=? WHERE issuer=?').run(valid, 'valid-avatar', 'https://hearthpulse.net/identity');
    db.close();
    const admin = openCommentsAdmin({ filename: item.filename, env, now: () => 1 });
    assert.match(inspect(admin, item.item.id, { includeAvatar: true }).avatarDataUri, /^data:image\/webp;base64,/);
    admin.close();
    const oversized = Buffer.alloc(128 * 1024 + 1); oversized.write('RIFF'); oversized.write('WEBP', 8);
    const update = new DatabaseSync(item.filename); update.prepare('UPDATE reader_profiles SET avatar=? WHERE issuer=?').run(oversized, 'https://hearthpulse.net/identity'); update.close();
    const rejected = openCommentsAdmin({ filename: item.filename, env, now: () => 1 });
    error(() => inspect(rejected, item.item.id, { includeAvatar: true }), 'invalid_avatar'); rejected.close();
  } finally { item.cleanup(); }
});
