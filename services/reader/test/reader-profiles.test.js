import assert from 'node:assert/strict';
import { DatabaseSync } from 'node:sqlite';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { ProfileConflictError, ProfileValidationError, ReaderProfiles } from '../profiles.js';

function disk() {
  const dir = mkdtempSync(join(tmpdir(), 'reader-profiles-'));
  return { filename: join(dir, 'reader.sqlite'), cleanup: () => rmSync(dir, { recursive: true, force: true }) };
}

function profiles(db, issuer = 'https://hearthpulse.net/identity') {
  return new ReaderProfiles({ db, issuer, now: () => 1_000 });
}

test('profiles have stable public ids, a safe HP fallback, and persist without later identity changes', () => {
  const d = disk();
  try {
    let db = new DatabaseSync(d.filename);
    let store = profiles(db);
    const first = store.getOrCreate('reader-1', '  Алиса\u202e  ');
    assert.match(first.id, /^[0-9a-f]{8}-[0-9a-f]{4}-[4][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i);
    assert.deepEqual(first, { id: first.id, displayName: 'Алиса', bio: '', favoriteClass: null, version: 1, avatarUrl: null });
    db.close();

    db = new DatabaseSync(d.filename);
    store = profiles(db);
    assert.deepEqual(store.getOrCreate('reader-1', 'Совсем другое имя'), first);
    const fallback = store.getOrCreate('reader-2', 'x');
    assert.equal(fallback.displayName, 'Читатель');
    db.close();
  } finally { d.cleanup(); }
});

test('profiles keep issuer and subject ownership isolated and never expose internal identity values', () => {
  const db = new DatabaseSync(':memory:');
  try {
    const a = profiles(db, 'https://hearthpulse.net/identity').getOrCreate('same-subject', 'Алиса');
    const b = profiles(db, 'https://other.example/identity').getOrCreate('same-subject', 'Боб');
    assert.notEqual(a.id, b.id);
    assert.equal(a.displayName, 'Алиса');
    assert.equal(b.displayName, 'Боб');
    assert.equal(JSON.stringify(a).includes('same-subject'), false);
    assert.equal(JSON.stringify(a).includes('identity'), false);
  } finally { db.close(); }
});

test('profiles strictly validate full updates and use optimistic versions for text and avatars', () => {
  const db = new DatabaseSync(':memory:');
  try {
    const store = profiles(db);
    const initial = store.getOrCreate('reader-1', 'Алиса');
    const updated = store.update('reader-1', {
      version: initial.version, displayName: '  Мария  ', bio: '  Люблю\nHearthstone  ', favoriteClass: 'mage',
    });
    assert.deepEqual(updated, { ...initial, displayName: 'Мария', bio: 'Люблю\nHearthstone', favoriteClass: 'mage', version: 2 });
    assert.throws(() => store.update('reader-1', { ...updated, surprise: true }), ProfileValidationError);
    assert.throws(() => store.update('reader-1', { version: updated.version, displayName: 'Мария', bio: 'ok\u0000', favoriteClass: 'mage' }), ProfileValidationError);
    assert.throws(() => store.update('reader-1', { version: updated.version, displayName: 'М', bio: '', favoriteClass: 'mage' }), ProfileValidationError);
    assert.throws(() => store.update('reader-1', { version: updated.version, displayName: 'Мария', bio: '', favoriteClass: 'evoker' }), ProfileValidationError);
    assert.throws(() => store.update('reader-1', { version: initial.version, displayName: 'Мария', bio: '', favoriteClass: null }), ProfileConflictError);

    const withAvatar = store.setAvatar('reader-1', Buffer.from('normalized-avatar'), updated.version);
    assert.equal(withAvatar.version, 3);
    assert.match(withAvatar.avatarUrl, /^\/reader-api\/v1\/profile\/avatar\?v=[A-Za-z0-9_-]+$/);
    assert.deepEqual(store.avatar('reader-1').bytes, Buffer.from('normalized-avatar'));
    assert.throws(() => store.setAvatar('reader-1', null, updated.version), ProfileConflictError);
    const cleared = store.setAvatar('reader-1', null, withAvatar.version);
    assert.equal(cleared.avatarUrl, null);
    assert.equal(store.avatar('reader-1'), null);
  } finally { db.close(); }
});

test('profiles require a nonempty authenticated subject', () => {
  const db = new DatabaseSync(':memory:');
  try {
    const store = profiles(db);
    for (const subject of [undefined, null, '', '   ']) assert.throws(() => store.getOrCreate(subject, 'Алиса'), ProfileValidationError);
  } finally { db.close(); }
});
