import { normalizeAvatar, AvatarBusyError, AvatarValidationError } from './avatars.js';
import { ProfileConflictError, ProfileValidationError } from './profiles.js';

export async function verifiedReader(store, identity, id, signal) {
  const session = store.getSession(id);
  if (!session) return null;
  const profile = await identity.profile(session.upstreamToken, session.userId, signal);
  signal.throwIfAborted();
  if (!profile) { store.revokeAndQueue(id); return null; }
  // Logout/expiry while the upstream request was pending must win.
  if (!store.getSession(id)) return null;
  return { session, profile };
}

/** A profile write needs an active subject, not a display-name round trip. */
export async function verifiedWriter(store, identity, id, signal) {
  const session = store.getSession(id);
  if (!session) return null;
  const active = await identity.verify(session.upstreamToken, session.userId, signal);
  signal.throwIfAborted();
  if (!active) { store.revokeAndQueue(id); return null; }
  const current = store.getSession(id);
  if (!current || current.userId !== session.userId || current.upstreamToken !== session.upstreamToken) return null;
  return { session: current };
}

class BodyTooLarge extends Error {}
async function bodyBytes(request, limit) {
  if (Number(request.headers.get('content-length') ?? 0) > limit) throw new BodyTooLarge();
  const reader = request.body?.getReader();
  if (!reader) return Buffer.alloc(0);
  const chunks = []; let size = 0;
  try {
    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      size += value.byteLength;
      if (size > limit) { await reader.cancel(); throw new BodyTooLarge(); }
      chunks.push(value);
    }
    return Buffer.concat(chunks, size);
  } finally { reader.releaseLock(); }
}

/** Private profile routes; no public author lookup or comments are enabled here. */
export function createProfileRoutes({ store, identity, profiles, validWrite, json, securityHeaders }) {
  const uploads = new Map(); let windowStart = Date.now();
  return async (request, url, id, signal) => {
    const avatar = url.pathname === '/reader-api/v1/profile/avatar';
    const edit = url.pathname === '/reader-api/v1/profile' && request.method === 'PATCH';
    if (!profiles || (!edit && !(avatar && ['GET', 'PUT', 'DELETE'].includes(request.method)))) return null;
    if (!store.getSession(id)) return json(401, { error: 'not_authenticated' });
    if (request.method !== 'GET' && !validWrite(request, id)) return json(403, { error: 'invalid_request' });
    try {
      let input; let bytes;
      if (edit) {
        if (request.headers.get('content-type')?.split(';')[0].trim() !== 'application/json') throw new ProfileValidationError();
        const body = await bodyBytes(request, 4096);
        try { input = JSON.parse(body.toString('utf8')); } catch { throw new ProfileValidationError(); }
      }
      if (avatar && request.method === 'PUT') bytes = await bodyBytes(request, 4 * 1024 * 1024);
      const verified = request.method === 'GET'
        ? await verifiedReader(store, identity, id, signal)
        : await verifiedWriter(store, identity, id, signal);
      if (!verified) return json(401, { error: 'not_authenticated' });
      const subject = verified.session.userId;
      if (request.method === 'GET') {
        const image = profiles.avatar(subject);
        if (!image || (url.searchParams.has('v') && url.searchParams.get('v') !== image.version)) return json(404, { error: 'not_found' });
        return new Response(image.bytes, { headers: { ...securityHeaders, 'Content-Type': 'image/webp', 'Cross-Origin-Resource-Policy': 'same-origin' } });
      }
      const current = profiles.getOrCreate(subject, 'Читатель');
      if (edit) return json(200, { profile: profiles.update(subject, input) });
      const value = request.headers.get('x-reader-profile-version') ?? '';
      if (!/^[1-9][0-9]{0,14}$/.test(value)) throw new ProfileValidationError();
      const version = Number(value);
      if (current.version !== version) throw new ProfileConflictError();
      if (request.method === 'DELETE') return json(200, { profile: profiles.setAvatar(subject, null, version) });
      if (Date.now() - windowStart >= 60000) { uploads.clear(); windowStart = Date.now(); }
      if (!uploads.has(subject) && uploads.size >= 4096) throw new AvatarBusyError();
      const count = (uploads.get(subject) ?? 0) + 1; uploads.set(subject, count);
      if (count > 10) return new Response(null, { status: 429, headers: { ...securityHeaders, 'Retry-After': '60' } });
      const normalized = await normalizeAvatar(bytes, request.headers.get('content-type'));
      // Decoding may outlive the first identity check. Verify again without fetching userinfo.
      const stillVerified = await verifiedWriter(store, identity, id, signal);
      if (!stillVerified || stillVerified.session.userId !== subject
        || stillVerified.session.upstreamToken !== verified.session.upstreamToken) {
        return json(401, { error: 'not_authenticated' });
      }
      return json(200, { profile: profiles.setAvatar(subject, normalized, version) });
    } catch (error) {
      if (error instanceof BodyTooLarge) return json(413, { error: 'too_large' });
      if (error instanceof ProfileConflictError) return json(409, { error: 'profile_conflict' });
      if (error instanceof ProfileValidationError) return json(400, { error: 'invalid_profile' });
      if (error instanceof AvatarValidationError) return json(400, { error: 'invalid_avatar' });
      if (error instanceof AvatarBusyError) return json(503, { error: 'avatar_busy' });
      throw error;
    }
  };
}
