import { ReaderCommentError, fail } from './community-errors.js';
import { verifiedReader, verifiedWriter } from './profile-http.js';
import { requireSameSession } from './community-session.js';

const favoriteRoute = /^\/reader-api\/v1\/favorites\/([1-9][0-9]{0,15})$/;
const listRoute = '/reader-api/v1/favorites';

async function noBody(request) {
  if (Number(request.headers.get('content-length') ?? 0) > 0) return false;
  const reader = request.body?.getReader();
  if (!reader) return true;
  try {
    const { done, value } = await reader.read();
    return done || !value?.byteLength;
  } finally {
    await reader.cancel().catch(() => {});
    reader.releaseLock();
  }
}

/** Private favorites API. Article identity comes exclusively from signed WordPress metadata. */
export function createFavoriteRoutes({ community, store, profiles, identity, validWrite, json, csrf }) {
  if (!community?.favorites || !community.favoriteEditorial) return async () => null;
  const { favorites, favoriteEditorial } = community;
  async function reader(id, signal, writer = false) {
    const verified = writer ? await verifiedWriter(store, identity, id, signal) : await verifiedReader(store, identity, id, signal);
    if (!verified) fail(401, 'not_authenticated');
    requireSameSession(store, id, verified.session, signal);
    const profile = profiles.getOrCreate(verified.session.userId, writer ? undefined : verified.profile.displayName);
    return { session: verified.session, profile };
  }
  return async (request, url, id, signal) => {
    const match = url.pathname.match(favoriteRoute);
    const list = url.pathname === listRoute;
    if (!match && !list) return null;
    try {
      if (list) {
        if (request.method !== 'GET' || [...url.searchParams.keys()].some(key => key !== 'cursor') || url.searchParams.getAll('cursor').length > 1) return null;
        if (!store.getSession(id)) fail(401, 'not_authenticated');
        const current = await reader(id, signal);
        const page = favorites.list(current.session.userId, current.profile.id, { cursor: url.searchParams.get('cursor') });
        if (!page.items.length) return json(200, page);
        const articles = await favoriteEditorial.get(page.items.map(item => item.postId), signal);
        requireSameSession(store, id, current.session, signal);
        const items = [];
        for (const favorite of page.items) {
          const article = articles.get(favorite.postId);
          if (article?.allowed === true) {
            const reconciled = favorites.reconcile(current.session.userId, current.profile.id, favorite.id, article);
            if (reconciled) items.push(reconciled);
          } else {
            // Never retain an inaccessible title/path in the private Reader
            // store after WordPress has withdrawn the article.
            favorites.remove(current.session.userId, current.profile.id, favorite.postId);
          }
        }
        return json(200, { ...page, items });
      }
      const postId = Number(match[1]);
      if (!Number.isSafeInteger(postId) || postId < 1 || url.search) fail(400, 'invalid_input');
      if (!['GET', 'PUT', 'DELETE'].includes(request.method)) return null;
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (request.method !== 'GET' && !validWrite(request, id)) fail(403, 'invalid_request');
      if (request.method !== 'GET' && !await noBody(request)) fail(400, 'invalid_input');
      const current = await reader(id, signal, request.method !== 'GET');
      if (request.method === 'GET') {
        const articles = await favoriteEditorial.get([postId], signal);
        requireSameSession(store, id, current.session, signal);
        if (articles.get(postId)?.allowed !== true) fail(404, 'not_found');
        return json(200, { saved: favorites.status(current.session.userId, current.profile.id, postId), csrfToken: csrf(id) });
      }
      if (request.method === 'DELETE') {
        return json(200, favorites.remove(current.session.userId, current.profile.id, postId));
      }
      const articles = await favoriteEditorial.get([postId], signal);
      requireSameSession(store, id, current.session, signal);
      const article = articles.get(postId);
      if (!article?.allowed) fail(404, 'not_found');
      return json(201, { postId, saved: true, favorite: favorites.save(current.session.userId, current.profile.id, article) });
    } catch (error) {
      const response = json(error instanceof ReaderCommentError ? error.status : 503,
        { error: error instanceof ReaderCommentError ? error.code : 'comments_unavailable' });
      if (response.status === 429) response.headers.set('Retry-After', '60');
      return response;
    }
  };
}
