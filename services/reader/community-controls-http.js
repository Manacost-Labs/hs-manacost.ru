import { ReaderCommentError, fail } from './community-errors.js';
import { verifiedReader } from './profile-http.js';
import { requireSameSession } from './community-session.js';

const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
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

export function createCommunityControlRoutes({ community, store, profiles, identity, validWrite, json }) {
  if (!community) return async () => null;
  const { comments, editorial, permissions } = community;
  return async (request, url, id, signal) => {
    const me = url.pathname === '/reader-api/v1/community/me';
    const list = url.pathname === '/reader-api/v1/moderation/bans';
    const reaction = url.pathname.match(reactionRoute), remove = url.pathname.match(removeRoute);
    const ban = url.pathname.match(banRoute), unban = url.pathname.match(unbanRoute);
    if (!me && !list && !reaction && !remove && !ban && !unban) return null;
    const method = request.method;
    if (!((me || list) && method === 'GET') && !(ban && ['GET', 'PUT'].includes(method))
      && !(remove && method === 'DELETE') && !((unban || reaction) && method === 'PUT')) return null;
    try {
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (method !== 'GET' && !validWrite(request, id)) fail(403, 'invalid_request');
      if (list ? [...url.searchParams.keys()].some(key => key !== 'cursor') || url.searchParams.getAll('cursor').length > 1 : url.search) fail(400, 'invalid_input');
      const body = method === 'GET' ? null : await input(request, reaction ? 'reaction' : 'version');
      const verified = await verifiedReader(store, identity, id, signal);
      if (!verified) fail(401, 'not_authenticated');
      const { session } = verified;
      requireSameSession(store, id, session, signal);
      profiles.getOrCreate(session.userId, verified.profile.displayName);
      if (reaction) {
        const metadata = comments.metadataForComment(reaction[1]);
        if (!metadata) fail(404, 'comment_not_found');
        const articles = await editorial.get([metadata.postId], signal);
        requireSameSession(store, id, session, signal);
        if (articles.get(metadata.postId)?.allowed !== true) fail(404, 'not_found');
        return json(200, { reactions: comments.reactions.set(session.userId, reaction[1], body.reaction) });
      }
      if (!permissions) fail(503, 'comments_unavailable');
      const result = await permissions.get([session.userId], signal);
      requireSameSession(store, id, session, signal);
      const admin = result.get(session.userId) === true;
      if (me) return json(200, { canModerateComments: admin, commentingBlocked: comments.bans.isBlocked(session.userId) });
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
