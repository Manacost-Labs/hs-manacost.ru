import { ReaderCommentError } from './comments-store.js';
import { verifiedReader } from './profile-http.js';
import { requireSameSession } from './community-session.js';

const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
const publicRoute = new RegExp(`^/reader-api/v1/readers/(${UUID})(/avatar)?$`, 'i');
const commentRoute = new RegExp(`^/reader-api/v1/comments/(${UUID})$`, 'i');
const fail = (status, code) => { throw new ReaderCommentError(status, code); };

async function input(request, keys) {
  if (!/^application\/json(?:;|$)/i.test(request.headers.get('content-type') ?? '')) fail(400, 'invalid_input');
  const text = await request.text();
  if (Buffer.byteLength(text) > 4096) fail(413, 'request_too_large');
  let body; try { body = JSON.parse(text); } catch { fail(400, 'invalid_input'); }
  if (!body || Object.getPrototypeOf(body) !== Object.prototype || Object.keys(body).length !== keys.length
    || !keys.every(key => Object.hasOwn(body, key))) fail(400, 'invalid_input');
  return body;
}

/** Public DTO decoration never changes authorization and never exposes upstream subjects. */
function authorDTO(author, paid, pending = false, includeSocials = false, administrator = false) {
  if (!author) return null;
  const dto = { id: author.id, name: author.name, bio: author.bio, favoriteClass: author.favoriteClass,
    avatarVersion: pending ? null : author.avatarVersion,
    avatarUrl: !pending && author.avatarVersion ? `/reader-api/v1/readers/${author.id}/avatar?v=${author.avatarVersion}` : null,
    profileUrl: pending ? null : `/account/?reader=${author.id}`, paidSubscriber: !pending && paid === true,
    hasTwitch: !pending && typeof author.twitchUrl === 'string',
    hasYoutube: !pending && typeof author.youtubeUrl === 'string', administrator: !pending && administrator === true };
  // Social links are consented profile data. They belong on the public profile,
  // never on every thread response.
  if (includeSocials) {
    dto.twitchUrl = author.twitchUrl ?? null;
    dto.youtubeUrl = author.youtubeUrl ?? null;
  }
  return dto;
}

export function createCommentRoutes({ community, store, profiles, identity, validWrite, json, securityHeaders }) {
  if (!community) return async () => null;
  const { comments, editorial, entitlements, permissions } = community;

  async function allowed(postId, signal) {
    const result = await editorial.get([postId], signal);
    signal.throwIfAborted();
    if (result.get(postId)?.allowed !== true) fail(404, 'not_found');
  }

  async function paidFor(ids, signal) {
    if (!ids.length) return new Map();
    const subjects = comments.subjectsForProfiles([...new Set(ids)]);
    try {
      const result = await entitlements.get([...new Set(subjects.values())], signal);
      return new Map([...subjects].map(([id, subject]) => [id, result.get(subject) === true]));
    } catch { return new Map(); }
  }

  async function adminsFor(ids, signal) {
    if (!ids.length || !permissions) return new Map();
    const subjects = comments.subjectsForProfiles([...new Set(ids)]);
    try {
      const result = await permissions.get([...new Set(subjects.values())], signal);
      return new Map([...subjects].map(([id, subject]) => [id, result.get(subject) === true]));
    } catch { return new Map(); }
  }

  async function reader(id, signal) {
    const verified = await verifiedReader(store, identity, id, signal);
    if (!verified) fail(401, 'not_authenticated');
    requireSameSession(store, id, verified.session, signal);
    profiles.getOrCreate(verified.session.userId, verified.profile.displayName);
    return verified.session;
  }

  async function publicProfile(request, url, match, signal) {
    const profileId = match[1]; const avatar = Boolean(match[2]);
    if (request.method !== 'GET') return null;
    if ([...url.searchParams.keys()].some(key => key !== 'v') || url.searchParams.getAll('v').length > 1) fail(400, 'invalid_input');
    const profile = comments.publicProfile(profileId);
    if (!profile) fail(404, 'not_found');
    const ids = comments.postIdsForProfile(profileId);
    if (!ids.length) fail(404, 'not_found');
    const [paid, articles, admins] = await Promise.all([
      avatar ? new Map() : paidFor([profileId], signal),
      editorial.get(ids, signal),
      avatar ? new Map() : adminsFor([profileId], signal),
    ]);
    signal.throwIfAborted();
    if (!ids.some(id => articles.get(id)?.allowed === true)) fail(404, 'not_found');
    // Re-read after awaits: erasure/takedown must win over an in-flight public response.
    const current = comments.publicProfile(profileId);
    if (!current || !comments.postIdsForProfile(profileId).some(id => articles.get(id)?.allowed === true)) fail(404, 'not_found');
    if (!avatar) return json(200, { profile: authorDTO(current, paid.get(profileId), false, true, admins.get(profileId)) });
    const version = url.searchParams.get('v');
    if (typeof version !== 'string' || !/^[A-Za-z0-9_-]{32}$/.test(version)) fail(404, 'not_found');
    const bytes = comments.publicAvatar(profileId, version);
    return bytes ? new Response(bytes, { headers: { ...securityHeaders, 'Content-Type': 'image/webp' } }) : json(404, { error: 'not_found' });
  }

  async function thread(request, url, postId, id, signal) {
    if (!Number.isSafeInteger(postId) || postId < 1) fail(400, 'invalid_input');
    if (request.method === 'POST') {
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (!validWrite(request, id)) fail(403, 'invalid_request');
      const body = await input(request, ['body', 'parentId', 'operationId', 'profileVersion', 'publicConsent']);
      await allowed(postId, signal);
      const session = await reader(id, signal);
      signal.throwIfAborted();
      const comment = comments.submit(session.userId, { postId, ...body });
      return json(201, { comment: { ...comment, author: authorDTO(comment.author, false, comment.status === 'pending') } });
    }
    if (request.method !== 'GET') return null;
    if ([...url.searchParams.keys()].some(key => key !== 'cursor') || url.searchParams.getAll('cursor').length > 1) fail(400, 'invalid_input');
    const options = { cursor: url.searchParams.get('cursor'), viewerSubject: null };
    let viewerSession = null;
    if (store.getSession(id)) {
      try { viewerSession = await reader(id, signal); options.viewerSubject = viewerSession.userId; } catch { /* Public reading survives an unavailable identity provider. */ }
    }
    const initial = comments.list(postId, options);
    const authorIds = initial.items.filter(item => item.status === 'published').map(item => item.author?.id).filter(Boolean);
    const [paid, admins] = await Promise.all([
      paidFor(authorIds, signal), adminsFor(authorIds, signal),
      allowed(postId, signal),
    ]);
    if (viewerSession) {
      try { requireSameSession(store, id, viewerSession, signal); } catch { options.viewerSubject = null; }
    }
    const result = comments.list(postId, options);
    const reactions = comments.reactions.summaries(result.items.map(item => item.id), options.viewerSubject);
    return json(200, { ...result, items: result.items.map(item => ({ ...item,
      reactions: reactions.get(item.id),
      author: authorDTO(item.author, paid.get(item.author?.id), item.status === 'pending', false, admins.get(item.author?.id)) })) });
  }

  return async (request, url, id, signal) => {
    const publicMatch = url.pathname.match(publicRoute);
    const threadMatch = url.pathname.match(/^\/reader-api\/v1\/threads\/([1-9][0-9]{0,15})\/comments$/);
    const removeMatch = url.pathname.match(commentRoute);
    const exportRoute = url.pathname === '/reader-api/v1/community/export';
    const eraseRoute = url.pathname === '/reader-api/v1/community/profile';
    if (!publicMatch && !threadMatch && !removeMatch && !exportRoute && !eraseRoute) return null;
    try {
      if (publicMatch) return await publicProfile(request, url, publicMatch, signal);
      if (threadMatch) return await thread(request, url, Number(threadMatch[1]), id, signal);
      if (eraseRoute && request.method === 'PUT') {
        if (!store.getSession(id)) fail(401, 'not_authenticated');
        if (!validWrite(request, id)) fail(403, 'invalid_request');
        if (url.search) fail(400, 'invalid_input');
        const body = await input(request, ['profileVersion', 'publicConsent']);
        const subject = store.getSession(id).userId;
        const profile = profiles.getOrCreate(subject);
        const ids = comments.postIdsForProfile(profile.id);
        if (!ids.length) fail(404, 'public_profile_not_found');
        const articles = await editorial.get(ids, signal);
        const session = await reader(id, signal);
        signal.throwIfAborted();
        if (session.userId !== subject) fail(401, 'not_authenticated');
        if (!comments.postIdsForProfile(profile.id).some(postId => articles.get(postId)?.allowed === true)) fail(404, 'public_profile_not_found');
        comments.refreshProfile(subject, body);
        return json(200, { profile: profiles.getOrCreate(subject) });
      }
      if (request.method !== (exportRoute ? 'GET' : 'DELETE')) return null;
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (!exportRoute && !validWrite(request, id)) fail(403, 'invalid_request');
      if (exportRoute) {
        if ([...url.searchParams.keys()].some(key => key !== 'cursor') || url.searchParams.getAll('cursor').length > 1) fail(400, 'invalid_input');
        const session = await reader(id, signal);
        return json(200, { ...comments.ownExport(session.userId, { cursor: url.searchParams.get('cursor'), limit: 100 }), reactions: comments.reactions.ownExport(session.userId).items });
      }
      const body = await input(request, eraseRoute ? ['profileId', 'confirm'] : ['version']);
      const session = await reader(id, signal);
      signal.throwIfAborted();
      if (removeMatch) return json(200, comments.remove(session.userId, removeMatch[1], body));
      if (body.confirm !== 'erase-community' || body.profileId !== profiles.getOrCreate(session.userId).id) fail(400, 'invalid_input');
      return json(200, comments.erase(session.userId));
    } catch (error) {
      if (!(error instanceof ReaderCommentError)) return json(503, { error: 'comments_unavailable' });
      const response = json(error.status, { error: error.code });
      if (error.status === 429) response.headers.set('Retry-After', '60');
      return response;
    }
  };
}
