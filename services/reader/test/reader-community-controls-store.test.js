import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { DatabaseSync } from 'node:sqlite';
import test from 'node:test';
import { ReaderComments, ReaderCommentError } from '../comments-store.js';
import { ReaderProfiles } from '../profiles.js';

const issuer = 'https://hearthpulse.net/identity';
const denied = (fn, code) => assert.throws(fn, error => error instanceof ReaderCommentError && error.code === code);
function fixture(t) {
  const db = new DatabaseSync(':memory:'); t.after(() => db.close());
  let time = 1_000_000;
  const profiles = new ReaderProfiles({ db, issuer });
  const comments = new ReaderComments({ db, issuer, now: () => time });
  const alice = profiles.getOrCreate('alice', 'Алиса');
  const bob = profiles.getOrCreate('bob', 'Боб');
  const submit = (subject = 'alice', extra = {}) => comments.submit(subject, {
    postId: 17, body: 'Полезный разбор', parentId: null, operationId: randomUUID(),
    profileVersion: profiles.getOrCreate(subject).version, attachmentId: null, ...extra,
  });
  return { db, profiles, comments, alice, bob, submit, tick: n => { time += n; } };
}

test('one persistent reaction per reader/comment: explicit repeat, switch and withdrawal', t => {
  const f = fixture(t); const item = f.submit(); const reactions = f.comments.reactions;
  assert.deepEqual(reactions.set('alice', item.id, 'like'), [
    { kind: 'like', count: 1, selected: true }, { kind: 'thanks', count: 0, selected: false }, { kind: 'fire', count: 0, selected: false },
  ]);
  reactions.set('alice', item.id, 'like');
  assert.equal(f.db.prepare('SELECT count(*) n FROM reader_reaction_events').get().n, 1);
  reactions.set('bob', item.id, 'like'); reactions.set('alice', item.id, 'thanks');
  const restarted = new ReaderComments({ db: f.db, issuer });
  assert.deepEqual(restarted.reactions.summaries([item.id], 'alice').get(item.id), [
    { kind: 'like', count: 1, selected: false }, { kind: 'thanks', count: 1, selected: true }, { kind: 'fire', count: 0, selected: false },
  ]);
  reactions.set('alice', item.id, null); reactions.set('alice', item.id, null);
  assert.equal(reactions.summaries([item.id]).get(item.id)[0].selected, false);
  denied(() => reactions.set('alice', item.id, 'arbitrary'), 'invalid_input');
  assert.deepEqual(reactions.ownExport('bob').items.map(row => row.kind), ['like']);
});

test('reactions cannot cross issuers, target pending/deleted comments or expose voters', t => {
  const f = fixture(t); const item = f.submit();
  const other = new ReaderComments({ db: f.db, issuer: 'https://other.example' });
  new ReaderProfiles({ db: f.db, issuer: other.issuer }).getOrCreate('alice');
  denied(() => other.reactions.set('alice', item.id, 'fire'), 'comment_not_found');
  f.comments.reactions.set('bob', item.id, 'fire');
  assert.equal(JSON.stringify(f.comments.reactions.summaries([item.id]).get(item.id)).includes('bob'), false);
  assert.equal(other.reactions.summaries([item.id]).get(item.id)[2].count, 0);
  f.comments.remove('alice', item.id, { version: 1 });
  assert.equal(f.comments.reactions.ownExport('bob').items.length, 0);
  denied(() => f.comments.reactions.set('bob', item.id, 'like'), 'comment_not_found');
  const pending = f.submit();
  f.db.prepare("UPDATE reader_comments SET status='pending' WHERE id=?").run(pending.id);
  denied(() => f.comments.reactions.set('bob', pending.id, 'like'), 'comment_not_found');
});

test('reaction abuse limits persist, same-state retries do not charge quota', t => {
  const f = fixture(t); const item = f.submit();
  for (let i = 0; i < 30; i++) f.comments.reactions.set('bob', item.id, i % 2 ? 'like' : 'thanks');
  f.comments.reactions.set('bob', item.id, 'like');
  denied(() => f.comments.reactions.set('bob', item.id, 'fire'), 'reaction_rate_limited');
  f.tick(60_001);
  f.comments.reactions.set('bob', item.id, 'fire');
  assert.equal(f.comments.reactions.summaries([item.id], 'bob').get(item.id)[2].selected, true);
});

test('versioned commenting ban survives community erasure and can be revoked by ban ID', t => {
  const f = fixture(t); const item = f.submit(); const bans = f.comments.bans;
  const initial = bans.forProfile(f.alice.id);
  assert.equal(initial.version, 0); assert.equal(initial.blocked, false);
  const ban = bans.block(f.alice.id, { version: 0, actor: 'moderator' });
  assert.equal(ban.blocked, true); assert.equal(ban.version, 1);
  denied(() => f.submit(), 'commenting_blocked');
  denied(() => bans.block(f.alice.id, { version: 99, actor: 'moderator' }), 'ban_version_conflict');
  f.comments.erase('alice');
  denied(() => f.submit(), 'commenting_blocked');
  const list = bans.list(); assert.equal(list.items[0].profile, null);
  assert.equal(JSON.stringify(list).includes('alice'), false);
  const unblocked = bans.unblock(ban.id, { version: 1, actor: 'moderator' });
  assert.equal(unblocked.blocked, false); assert.equal(unblocked.version, 2);
  assert.deepEqual(bans.unblock(ban.id, { version: 1, actor: 'moderator' }), unblocked);
  assert.equal(f.submit().status, 'published');
  assert.equal(f.comments.get(item.id).status, 'deleted');
});

test('ban identity is issuer scoped, survives private profile replacement and list is bounded', t => {
  const f = fixture(t); f.submit();
  const ban = f.comments.bans.block(f.alice.id, { version: 0, actor: 'moderator' });
  f.comments.erase('alice');
  f.db.prepare('DELETE FROM reader_profiles WHERE issuer=? AND subject=?').run(issuer, 'alice');
  f.profiles.getOrCreate('alice');
  denied(() => f.submit(), 'commenting_blocked');
  const other = new ReaderComments({ db: f.db, issuer: 'https://other.example' });
  assert.equal(other.bans.isBlocked('alice'), false);
  denied(() => other.bans.unblock(ban.id, { version: 1, actor: 'moderator' }), 'ban_not_found');
  assert.equal(other.bans.list().items.length, 0);
  assert.equal(f.comments.bans.list().items.length, 1);
  denied(() => f.comments.bans.list({ cursor: 'bad' }), 'invalid_input');
});

test('admin deletion covers all states, is idempotent and atomically cleans reactions and audits', t => {
  for (const status of ['published', 'pending', 'rejected']) {
    const f = fixture(t); const item = f.submit(); const reply = f.submit('bob', { parentId: item.id });
    f.comments.reactions.set('bob', item.id, 'thanks');
    f.db.prepare('UPDATE reader_comments SET status=? WHERE id=?').run(status, item.id);
    const result = f.comments.moderatorRemove(item.id, { version: 1, actor: 'server-derived-admin' });
    assert.equal(result.status, status === 'published' ? 'deleted' : 'rejected');
    assert.deepEqual(f.comments.moderatorRemove(item.id, { version: 1, actor: 'server-derived-admin' }), result);
    assert.deepEqual(f.comments.moderatorRemove(item.id, { version: 2, actor: 'server-derived-admin' }), result);
    denied(() => f.comments.moderatorRemove(item.id, { version: 20, actor: 'server-derived-admin' }), 'review_conflict');
    assert.equal(f.comments.reactions.ownExport('bob').items.length, 0);
    assert.equal(f.db.prepare('SELECT count(*) n FROM reader_comment_audit').get().n, 1);
    assert.equal(f.comments.list(17).items.some(row => row.id === reply.id), true);
  }
});

test('erasure removes owned and received reactions but retains bans and foreign issuer rows', t => {
  const f = fixture(t); const aliceComment = f.submit(); const bobComment = f.submit('bob');
  f.comments.reactions.set('alice', bobComment.id, 'like');
  f.comments.reactions.set('bob', aliceComment.id, 'thanks');
  f.comments.bans.block(f.alice.id, { version: 0, actor: 'moderator' });
  f.comments.erase('alice');
  assert.equal(f.db.prepare('SELECT count(*) n FROM reader_comment_reactions').get().n, 0);
  assert.equal(f.comments.bans.isBlocked('alice'), true);
  assert.equal(f.comments.list(17).items.find(row => row.id === bobComment.id).body, 'Полезный разбор');
});
