import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import test from 'node:test';
import sharp from 'sharp';
import { DatabaseSync } from 'node:sqlite';
import { ReaderCommentAttachments } from '../comment-attachments.js';
import { ReaderComments, ReaderCommentError } from '../comments-store.js';
import { ReaderArticleFavorites } from '../article-favorites.js';
import { ReaderProfiles } from '../profiles.js';

function setup() {
  const db = new DatabaseSync(':memory:');
  const issuer = 'https://identity.example';
  let now = 1_000_000;
  const profiles = new ReaderProfiles({ db, issuer, now: () => now });
  const attachments = new ReaderCommentAttachments({ db, issuer, now: () => now });
  const comments = new ReaderComments({ db, issuer, now: () => now, attachments });
  const favorites = new ReaderArticleFavorites({ db, issuer, now: () => now });
  const profile = profiles.getOrCreate('reader-one', 'Читатель');
  const input = (overrides = {}) => ({
    postId: 17,
    body: 'Полезный комментарий',
    parentId: null,
    operationId: randomUUID(),
    profileVersion: profiles.getOrCreate('reader-one').version,
    attachmentId: null,
    ...overrides,
  });
  return { db, profiles, attachments, comments, favorites, profile, input, tick: ms => { now += ms; } };
}

const failure = (action, status, code) => assert.throws(action, error => error instanceof ReaderCommentError
  && error.status === status && error.code === code);

test('a reader comment publishes without a separate public-consent checkbox', () => {
  const fixture = setup();
  try {
    const saved = fixture.comments.submit('reader-one', fixture.input());
    assert.equal(saved.status, 'published');
    assert.equal(saved.author.name, 'Читатель');
    failure(() => fixture.comments.submit('reader-one', fixture.input({ publicConsent: true })), 400, 'invalid_input');
  } finally {
    fixture.db.close();
  }
});

test('an attachment is private until the owner publishes it with one comment', async () => {
  const fixture = setup();
  try {
    const png = await sharp({ create: { width: 80, height: 40, channels: 3, background: '#234567' } }).png().toBuffer();
    const staged = await fixture.attachments.stage('reader-one', fixture.profile.id, png, 'image/png');
    assert.equal(fixture.attachments.publicForComment('missing'), null);
    const saved = fixture.comments.submit('reader-one', fixture.input({ attachmentId: staged.id }));
    assert.deepEqual(saved.attachment, { id: staged.id, width: 80, height: 40 });
    const publicAttachment = fixture.attachments.publicForComment(saved.id);
    assert.equal(publicAttachment.id, staged.id);
    assert.equal(publicAttachment.contentType, 'image/webp');
    assert.ok(publicAttachment.bytes.length > 0);
    failure(() => fixture.comments.submit('reader-one', fixture.input({ attachmentId: staged.id })), 409, 'attachment_unavailable');
    fixture.comments.remove('reader-one', saved.id, { version: saved.version });
    assert.equal(fixture.attachments.publicForComment(saved.id), null);
  } finally {
    fixture.db.close();
  }
});

test('favorites are private, idempotent, cursor-paginated reader data', () => {
  const fixture = setup();
  try {
    const first = fixture.favorites.save('reader-one', fixture.profile.id, {
      postId: 17, title: 'Первая статья', path: '/first/',
    });
    fixture.tick(1);
    const second = fixture.favorites.save('reader-one', fixture.profile.id, {
      postId: 18, title: 'Вторая статья', path: '/second/',
    });
    assert.equal(fixture.favorites.save('reader-one', fixture.profile.id, {
      postId: 17, title: 'Подмена', path: '/spoofed/',
    }).id, first.id);
    const page = fixture.favorites.list('reader-one', fixture.profile.id, { limit: 1 });
    assert.deepEqual(page.items.map(item => item.id), [second.id]);
    assert.equal(page.nextCursor, second.id);
    assert.deepEqual(fixture.favorites.list('reader-one', fixture.profile.id, { cursor: page.nextCursor, limit: 1 }).items.map(item => item.id), [first.id]);
    assert.equal(fixture.favorites.status('reader-one', fixture.profile.id, 17), true);
    assert.equal(fixture.favorites.remove('reader-one', fixture.profile.id, 17).saved, false);
    assert.equal(fixture.favorites.status('reader-one', fixture.profile.id, 17), false);
  } finally {
    fixture.db.close();
  }
});
