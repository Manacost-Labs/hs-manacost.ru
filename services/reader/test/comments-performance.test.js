import assert from 'node:assert/strict';
import { randomBytes, randomUUID } from 'node:crypto';
import { setImmediate } from 'node:timers/promises';
import test from 'node:test';
import { ReaderStore } from '../core.js';
import { ReaderProfiles } from '../profiles.js';
import { ReaderComments } from '../comments-store.js';
import { createReaderHandler } from '../http.js';

test('public comment/profile reads overlap paid and editorial checks without serving erased data', async t => {
  for (const kind of ['thread', 'profile']) {
    const store = new ReaderStore({ encryptionKey: randomBytes(32) });
    t.after(() => store.close());
    const issuer = 'https://hearthpulse.net/identity';
    const profiles = new ReaderProfiles({ db: store.db, issuer });
    const comments = new ReaderComments({ db: store.db, issuer });
    const profile = profiles.getOrCreate('fixture', 'Читатель');
    comments.submit('fixture', { postId: 17, body: 'Тест обсуждения', parentId: null,
      operationId: randomUUID(), profileVersion: profile.version, publicConsent: true });
    let releasePaid, enteredPaid, releaseEditorial;
    const paidStarted = new Promise(resolve => { enteredPaid = resolve; });
    const paidWait = new Promise(resolve => { releasePaid = resolve; });
    const editorialWait = new Promise(resolve => { releaseEditorial = resolve; });
    let editorialStarted = false;
    const handle = createReaderHandler({ origin: 'https://test.hs-manacost.ru', store, profiles,
      identity: { profile: async () => null }, csrfKey: randomBytes(32),
      community: { comments,
        entitlements: { get: async ids => { enteredPaid(); await paidWait; return new Map(ids.map(id => [id, true])); } },
        editorial: { get: async ids => { editorialStarted = true; await editorialWait; return new Map(ids.map(id => [id, { allowed: true }])); } },
      },
    });
    const path = kind === 'thread' ? '/reader-api/v1/threads/17/comments' : `/reader-api/v1/readers/${profile.id}`;
    const response = handle(new Request(`https://test.hs-manacost.ru${path}`));
    try {
      await paidStarted; await setImmediate();
      assert.equal(editorialStarted, true, `${kind}: editorial lookup must not wait for optional paid title`);
    } finally {
      comments.erase('fixture');
      releasePaid(); releaseEditorial();
    }
    const result = await response;
    if (kind === 'profile') assert.equal(result.status, 404);
    else {
      assert.equal(result.status, 200);
      const data = await result.json();
      assert.ok(data.items.every(item => item.body === null && item.author === null), 'erasure wins over every in-flight snapshot');
    }
  }
});
