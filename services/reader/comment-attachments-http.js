import { ReaderCommentError, fail } from './community-errors.js';
import { BodyTooLarge, bodyBytes, verifiedWriter } from './profile-http.js';
import { requireSameSession } from './community-session.js';

const UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
const attachmentRoute = new RegExp(`^/reader-api/v1/comments/(${UUID})/attachment$`, 'i');
const uploadRoute = '/reader-api/v1/comment-attachments';
const draftRoute = new RegExp(`^/reader-api/v1/comment-attachments/(${UUID})$`, 'i');

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

/** Same-origin private staging plus public immutable-in-content image reads. */
export function createCommentAttachmentRoutes({ community, store, profiles, identity, validWrite, json, securityHeaders }) {
  if (!community?.attachments || !community.editorial) return async () => null;
  const { attachments, editorial } = community;
  return async (request, url, id, signal) => {
    const match = url.pathname.match(attachmentRoute);
    const draft = url.pathname.match(draftRoute);
    const upload = url.pathname === uploadRoute;
    if (!upload && !match && !draft) return null;
    try {
      if (match) {
        if (request.method !== 'GET' || url.search) return null;
        const initial = attachments.publicForComment(match[1]);
        if (!initial) fail(404, 'not_found');
        const articles = await editorial.get([initial.postId], signal);
        signal.throwIfAborted();
        if (articles.get(initial.postId)?.allowed !== true) fail(404, 'not_found');
        // A deletion or takedown that wins while the editorial check is in
        // flight must also make the image disappear.
        const image = attachments.publicForComment(match[1]);
        if (!image || image.postId !== initial.postId) fail(404, 'not_found');
        return new Response(image.bytes, { headers: {
          ...securityHeaders,
          'Content-Type': image.contentType,
          'Cross-Origin-Resource-Policy': 'same-origin',
        } });
      }

      if (draft) {
        if (request.method !== 'DELETE' || url.search) return null;
        if (!store.getSession(id)) fail(401, 'not_authenticated');
        if (!validWrite(request, id)) fail(403, 'invalid_request');
        if (!await noBody(request)) fail(400, 'invalid_input');
        const verified = await verifiedWriter(store, identity, id, signal);
        if (!verified) fail(401, 'not_authenticated');
        const subject = verified.session.userId;
        const profile = profiles.getOrCreate(subject);
        requireSameSession(store, id, verified.session, signal);
        return json(200, attachments.discard(subject, profile.id, draft[1]));
      }

      if (request.method !== 'PUT' || url.search) return null;
      if (!store.getSession(id)) fail(401, 'not_authenticated');
      if (!validWrite(request, id)) fail(403, 'invalid_request');
      const bytes = await bodyBytes(request, 4 * 1024 * 1024);
      const verified = await verifiedWriter(store, identity, id, signal);
      if (!verified) fail(401, 'not_authenticated');
      const subject = verified.session.userId;
      const profile = profiles.getOrCreate(subject);
      const attachment = await attachments.stage(subject, profile.id, bytes, request.headers.get('content-type'));
      const current = await verifiedWriter(store, identity, id, signal);
      if (!current || current.session.userId !== subject || current.session.upstreamToken !== verified.session.upstreamToken) fail(401, 'not_authenticated');
      requireSameSession(store, id, current.session, signal);
      return json(201, { attachment });
    } catch (error) {
      const response = json(error instanceof BodyTooLarge ? 413 : error instanceof ReaderCommentError ? error.status : 503,
        { error: error instanceof BodyTooLarge ? 'too_large' : error instanceof ReaderCommentError ? error.code : 'comments_unavailable' });
      if (response.status === 429) response.headers.set('Retry-After', '60');
      return response;
    }
  };
}
