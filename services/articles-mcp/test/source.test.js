import test from 'node:test';
import assert from 'node:assert/strict';
import { WordPressSource } from '../source.js';
import { ArticleStore } from '../store.js';
import { syncArticles } from '../sync.js';

const origin = 'https://hs-manacost.ru';
const post = { id: 1, modified_gmt: '2026-09-29T00:00:00', title: { rendered: 'Гайд' }, link: `${origin}/guide/`, content: { protected: false, rendered: '<p>Полный текст</p>' }, categories: [] };
const response = (body, total = 1) => new Response(JSON.stringify(body), { headers: { 'Content-Type': 'application/json', 'X-WP-Total': String(total) } });

test('real source parser preserves full body; validation reads never render article content', async () => {
  const calls = [];
  const source = new WordPressSource(origin, { pauseMs: 0, transport: async (url, init) => { calls.push([url, init]); return response([post]); } });
  const store = new ArticleStore(':memory:', origin);
  await syncArticles(store, source);
  assert.equal(store.get('1').text, 'Полный текст');
  await source.visible([1]); assert.equal(calls.at(-1)[0].searchParams.get('_fields'), 'id,modified_gmt,content.protected');
  assert.ok(calls.every(([url, init]) => url.origin === origin && init.redirect === 'error' && init.signal)); store.close();
});

test('fails closed on malformed, denied, throttled and unavailable upstream responses', async () => {
  for (const status of [401, 403, 429, 500]) {
    const source = new WordPressSource(origin, { transport: async () => new Response('failure', { status }) });
    await assert.rejects(source.visible([1]), new RegExp(`source_http_${status}`));
  }
  const source = new WordPressSource(origin, { transport: async () => response([{ id: 1 }]) });
  await assert.rejects(source.visible([1]));
  await assert.rejects(new WordPressSource(origin, { transport: async () => { throw new Error('timeout'); } }).visible([1]), /timeout/);
});

test('a changing or duplicate catalog is rejected before reconciliation', async () => {
  const source = new WordPressSource(origin, { pauseMs: 0, transport: async () => response([post, post], 2) });
  await assert.rejects(source.catalog(), /incomplete_scan/);
  assert.throws(() => new WordPressSource('https://evil.example'), /invalid_source_origin/);
  assert.throws(() => new WordPressSource(origin, { authorization: 'fixture' }), /staging_only/);
});
