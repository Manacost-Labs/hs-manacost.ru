import test from 'node:test';
import assert from 'node:assert/strict';
import { ArticleStore } from '../store.js';
import { ArticleLibrary } from '../library.js';
import { syncArticles } from '../sync.js';
import { articleFromWordPress } from '../content.js';

const post = (id = 1, overrides = {}) => ({ id, link: `https://hs-manacost.ru/article-${id}/`,
  modified_gmt: '2026-09-29T00:00:00', title: { rendered: 'Гайд мага' },
  content: { protected: false, rendered: '<h2>Колода</h2><p>Маг побеждает.</p>' }, categories: [3], ...overrides });
const origin = 'https://hs-manacost.ru';
const fakeSource = (posts) => ({ origin, async catalog() { return posts; }, async documents(ids) { return posts.filter(p => ids.includes(p.id)); },
  async visible(ids) { return posts.filter(p => ids.includes(p.id) && !p.content.protected); } });

test('extracts headings, deck codes, links, images and tables without scripts or forms', () => {
  const article = articleFromWordPress(post(1, { content: { protected: false, rendered: '<h2>Гайд</h2><p><a href="/cards/">Карты</a></p><pre>AAECAw==</pre><img src="https://cdn.example.org/a.webp" alt="Колода"><table><tr><th>Карта</th></tr><tr><td>Маг</td></tr></table><script>secret()</script><form>Account</form>' } }), origin);
  assert.match(article.text, /Гайд/); assert.match(article.text, /AAECAw==/); assert.match(article.text, /cdn.example.org/);
  assert.match(article.text, /Карта/); assert.doesNotMatch(article.text, /secret|Account/);
  assert.match(article.text, /https:\/\/hs-manacost.ru\/cards\//);
  assert.equal(articleFromWordPress(post(2, { content: { protected: true, rendered: 'hidden' } }), origin), null);
});

test('sync is repeatable, reconciles deletions, and does not advance on failure', async () => {
  const store = new ArticleStore(':memory:', origin); const posts = [post()]; const source = fakeSource(posts);
  await syncArticles(store, source); await syncArticles(store, source);
  assert.equal(store.count(), 1);
  posts.push(post(2)); source.documents = async () => { throw new Error('unavailable'); };
  await assert.rejects(syncArticles(store, source)); assert.equal(store.count(), 1);
  posts.splice(0); await syncArticles(store, source); assert.equal(store.count(), 0); store.close();
});

test('search uses Russian prefixes; every response checks live publication and fails closed', async () => {
  const store = new ArticleStore(':memory:', origin); const posts = [post()]; const source = fakeSource(posts);
  await syncArticles(store, source); const library = new ArticleLibrary(store, source);
  assert.equal((await library.search('маг')).results.length, 1);
  assert.match((await library.fetch('1')).text, /Маг побеждает/);
  posts[0].content.protected = true;
  assert.equal((await library.search('маг')).results.length, 0);
  await assert.rejects(library.fetch('1'), /not_found/);
  source.visible = async () => { throw new Error('offline'); };
  await assert.rejects(library.search('маг'), /offline/); store.close();
});

test('changed content is withheld until refresh; pagination reaches all rows', async () => {
  const store = new ArticleStore(':memory:', origin); const posts = Array.from({ length: 23 }, (_, i) => post(i + 1));
  const source = fakeSource(posts); await syncArticles(store, source); const library = new ArticleLibrary(store, source);
  const first = await library.list({ limit: 20 }); const second = await library.list({ cursor: first.next_cursor, limit: 20 });
  assert.equal(first.results.length + second.results.length, 23);
  posts[0].modified_gmt = '2026-09-30T00:00:00'; await assert.rejects(library.fetch('1'), /not_found/);
  assert.throws(() => new ArticleStore(':memory:', 'http://untrusted.test'), /origin/); store.close();
});

test('failed later batch preserves the previous committed snapshot', async () => {
  const store = new ArticleStore(':memory:', origin); const source = fakeSource([post()]);
  await syncArticles(store, source);
  const next = Array.from({ length: 30 }, (_, i) => post(i + 2));
  source.catalog = async () => next;
  source.documents = async ids => { if (ids.includes(31)) throw new Error('later batch failed'); return next.filter(p => ids.includes(p.id)); };
  await assert.rejects(syncArticles(store, source), /later batch/);
  assert.equal(store.count(), 1); assert.ok(store.get('1')); assert.equal(store.get('2'), null); store.close();
});
