import { ReaderCommentError, fail } from './community-errors.js';
import { verifiedWriter } from './profile-http.js';
import { requireSameSession } from './community-session.js';

const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
const UUID_VALUE = new RegExp(`^${UUID}$`, 'i');
const reactionRoute = new RegExp(`^/reader-api/v1/comments/(${UUID})/reaction$`, 'i');
const removeRoute = new RegExp(`^/reader-api/v1/moderation/comments/(${UUID})$`, 'i');
const banRoute = new RegExp(`^/reader-api/v1/moderation/readers/(${UUID})/ban$`, 'i');
const unbanRoute = new RegExp(`^/reader-api/v1/moderation/bans/(${UUID})$`, 'i');

async function input(request, key) {
  if (!/^application\/json(?:;|$)/i.test(request.headers.get('content-type') ?? '')) fail(400, 'invalid_input');
  if (Number(request.headers.get('content-length') ?? 0) > 512) fail(413, 'request_too_large');
  const stream = request.body?.getReader();
  if (!stream) fail(400, 'invalid_input');
  const chunks = []; let size = 0;
  try {
    while (true) {
      const { done, value } = await stream.read();
      if (done) break;
      size += value.length; if (size > 512) fail(413, 'request_too_large');
      chunks.push(Buffer.from(value));
    }
  } finally { await stream.cancel().catch(() => {}); }
  let data; try { data = JSON.parse(Buffer.concat(chunks).toString('utf8')); } catch { fail(400, 'invalid_input'); }
  if (!data || Object.getPrototypeOf(data) !== Object.prototype || Object.keys(data).length !== 1 || !Object.hasOwn(data, key)) fail(400, 'invalid_input');
  return data;
}

export function createCommunityControlRoutes({ community, store, identity, validWrite, json }) {
  if (!community) return async () => null;
  const { comments, editorial, entitlements, permissions } = community;
  return async (request, url, id, signal) => {
    const me = url.pathname === '/reader-api/v1/community/me';
    const reactionSelections = url.pathname === '/reader-api/v1/community/reactions';
    const list = url.pathname === '/reader-api/v1/moderation/bans';
    const reaction = url.pathname.match(reactionRoute), remove = url.pathname.match(removeRoute);
    const ban = url.pathname.match(banRoute), unban = url.pathname.match(unbanRoute);
    if (!me && !reactionSelections && !list && !reaction && !remove && !ban && !unban) return null;
    const method = request.method;
    if (!((me || reactionSelections || list) && method === 'GET') && !(ban && ['GET', 'PUT'].includes(method))
      && !(remove && method === 'DELETE') && !((unban || reaction) && method === 'PUT')) return null;
    try {
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (method !== 'GET' && !validWrite(request, id)) fail(403, 'invalid_request');
      let selectionIds = null;
      if (reactionSelections) {
        selectionIds = url.searchParams.getAll('comment');
        if ([...url.searchParams.keys()].some(key => key !== 'comment') || selectionIds.length < 1
          || selectionIds.length > 50 || new Set(selectionIds).size !== selectionIds.length
          || selectionIds.some(value => !UUID_VALUE.test(value))) fail(400, 'invalid_input');
      } else if (list
        ? [...url.searchParams.keys()].some(key => key !== 'cursor') || url.searchParams.getAll('cursor').length > 1
        : url.search) fail(400, 'invalid_input');
      const body = method === 'GET' ? null : await input(request, reaction ? 'reaction' : 'version');
      // These routes never render a profile. A token introspection is both the
      // narrower authorization check and cheaper than a HearthPulse userinfo
      // round trip. Start the independent editorial read at the same time;
      // its result is not exposed until the session remains verified below.
      let reactionMetadata = null;
      let selectedComments = null;
      let postIds = null;
      let articlesPromise = null;
      if (reactionSelections) {
        selectedComments = selectionIds.map(commentId => comments.get(commentId));
        if (selectedComments.some(comment => !comment || comment.status !== 'published')) fail(404, 'not_found');
        postIds = [...new Set(selectedComments.map(comment => comment.postId))];
        articlesPromise = editorial.get(postIds, signal);
      } else if (reaction) {
        reactionMetadata = comments.metadataForComment(reaction[1]);
        if (!reactionMetadata) fail(404, 'comment_not_found');
        articlesPromise = editorial.get([reactionMetadata.postId], signal);
      }
      // An invalid identity returns before this independent read is consumed.
      // Keep a later provider rejection handled in that case without changing
      // the authenticated path, which still awaits and reports the failure.
      void articlesPromise?.catch(() => {});
      const verified = await verifiedWriter(store, identity, id, signal);
      if (!verified) fail(401, 'not_authenticated');
      const { session } = verified;
      requireSameSession(store, id, session, signal);
      if (reactionSelections) {
        const articles = await articlesPromise;
        requireSameSession(store, id, session, signal);
        if (postIds.some(postId => articles.get(postId)?.allowed !== true)) fail(404, 'not_found');
        const summaries = comments.reactions.summaries(selectionIds, session.userId);
        return json(200, { items: selectionIds.map(commentId => ({ commentId, reactions: summaries.get(commentId) })) });
      }
      if (reaction) {
        const articles = await articlesPromise;
        requireSameSession(store, id, session, signal);
        if (articles.get(reactionMetadata.postId)?.allowed !== true) fail(404, 'not_found');
        return json(200, { reactions: comments.reactions.set(session.userId, reaction[1], body.reaction) });
      }
      if (!permissions) fail(503, 'comments_unavailable');
      const paid = me && entitlements ? entitlements.get([session.userId], AbortSignal.any([signal, AbortSignal.timeout(350)]))
        .catch(() => new Map()) : Promise.resolve(new Map());
      const [result, paidResult] = await Promise.all([permissions.get([session.userId], signal), paid]);
      requireSameSession(store, id, session, signal);
      const admin = result.get(session.userId) === true;
      if (me) return json(200, { canModerateComments: admin, paidSubscriber: paidResult.get(session.userId) === true,
        commentingBlocked: comments.bans.isBlocked(session.userId) });
      if (!admin) fail(403, 'moderation_forbidden');
      if (list) return json(200, comments.bans.list({ cursor: url.searchParams.get('cursor') }));
      // Actor is always canonical and pseudonymized; no browser field can set it.
      const actor = session.userId;
      if (remove) return json(200, comments.moderatorRemove(remove[1], { ...body, actor: comments.bans.readerKey(actor) }));
      if (ban && method === 'GET') return json(200, { ban: comments.bans.forProfile(ban[1]) });
      if (ban) return json(200, { ban: comments.bans.block(ban[1], { ...body, actor }) });
      return json(200, { ban: comments.bans.unblock(unban[1], { ...body, actor }) });
    } catch (error) {
      const response = json(error instanceof ReaderCommentError ? error.status : 503,
        { error: error instanceof ReaderCommentError ? error.code : 'comments_unavailable' });
      if (response.status === 429) response.headers.set('Retry-After', '60');
      return response;
    }
  };
}
