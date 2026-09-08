import { createHmac, randomBytes, timingSafeEqual } from 'node:crypto';
import { ReaderAuthorizationDenied, ReaderValidationError } from './core.js';
import { createProfileRoutes, verifiedReader } from './profile-http.js';
import { createCommentRoutes } from './comments-http.js';

const SESSION_COOKIE = '__Host-manacost_reader';
const ATTEMPT_COOKIE = '__Host-manacost_reader_login';
const TTL = 300_000;
const securityHeaders = { 'Cache-Control': 'private, no-store', Pragma: 'no-cache',
  'Referrer-Policy': 'no-referrer', 'X-Robots-Tag': 'noindex, nofollow',
  'X-Content-Type-Options': 'nosniff', 'Content-Security-Policy': "default-src 'none'; frame-ancestors 'none'" };
const json = (status, body) => Response.json(body, { status, headers: securityHeaders });
const redirect = location => new Response(null, { status: 303, headers: { ...securityHeaders, Location: String(location) } });
const loginCookie = (value, maxAge = 300) => `${ATTEMPT_COOKIE}=${value}; Max-Age=${maxAge}; Path=/; HttpOnly; Secure; SameSite=Lax`;
function readCookie(request, name) {
  const values = (request.headers.get('cookie') ?? '').split(';').map(part => part.trim()).filter(part => part.startsWith(`${name}=`));
  if (values.length !== 1) return '';
  const value = values[0].slice(name.length + 1);
  return /^[A-Za-z0-9_-]{43}$/.test(value) ? value : '';
}
const matches = (left, right) => typeof left === 'string' && Buffer.byteLength(left) === Buffer.byteLength(right)
  && timingSafeEqual(Buffer.from(left), Buffer.from(right));

/** Same-origin HTTP boundary. The first slice deliberately uses five-minute sessions without offline access. */
export function createReaderHandler({ origin, store, identity, csrfKey, profiles, community }) {
  if (new URL(origin).origin !== origin || !origin.startsWith('https://') || csrfKey?.length !== 32) throw new Error('Invalid reader HTTP configuration');
  const csrf = id => createHmac('sha256', csrfKey).update(id).digest('base64url');
  const validWrite = (request, id) => request.headers.get('origin') === origin
    && request.headers.get('sec-fetch-site') !== 'cross-site' && Boolean(id)
    && matches(request.headers.get('x-reader-csrf'), csrf(id));
  const profileRoutes = createProfileRoutes({ store, identity, profiles, validWrite, json, securityHeaders });
  if (community && origin !== 'https://test.hs-manacost.ru') throw new Error('Comments are staging-only');
  const commentRoutes = createCommentRoutes({ community, store, identity, profiles, validWrite, json, securityHeaders });
  let windowStart = Date.now();
  const buckets = new Map();
  async function dispatch(request) {
    const url = new URL(request.url);
    if (url.origin !== origin || url.href.length > 8192) return json(400, { error: 'invalid_request' });
    const now = Date.now();
    if (now - windowStart >= 60_000) { windowStart = now; buckets.clear(); store.cleanup(); }
    const signal = AbortSignal.any([request.signal, AbortSignal.timeout(5000)]);
    const id = readCookie(request, SESSION_COOKIE);
    const localSession = store.getSession(id);
    // Anonymous overload must never prevent a valid reader from ending their own session.
    if (url.pathname !== '/reader-auth/logout') {
      const bucket = localSession ? csrf(id) : 'anonymous';
      if (!buckets.has(bucket) && buckets.size >= 4096) return json(503, { error: 'identity_unavailable' });
      const count = (buckets.get(bucket) ?? 0) + 1; buckets.set(bucket, count);
      if (count > (localSession ? 120 : 1000)) return new Response(null, { status: 429, headers: { ...securityHeaders, 'Retry-After': '60' } });
    }
    if (url.pathname === '/reader-auth/start' && request.method === 'GET') {
      if (url.searchParams.getAll('returnTo').length > 1 || request.headers.get('sec-fetch-site') === 'cross-site') return json(400, { error: 'invalid_request' });
      const browserNonce = randomBytes(32).toString('base64url');
      const attempt = store.createLoginAttempt({ returnTo: url.searchParams.get('returnTo') ?? '/account/', browserNonce,
        parentSessionId: localSession ? id : null });
      const response = redirect(identity.authorizationUrl(attempt));
      response.headers.append('Set-Cookie', loginCookie(browserNonce));
      return response;
    }
    if (url.pathname === '/reader-auth/callback' && request.method === 'GET') {
      const state = url.searchParams.get('state') ?? '';
      if (!/^[A-Za-z0-9_-]{43}$/.test(state) || ['state', 'code', 'error', 'iss'].some(key => url.searchParams.getAll(key).length > 1)) return json(400, { error: 'invalid_request' });
      const attempt = store.consumeLoginAttempt(state, readCookie(request, ATTEMPT_COOKIE));
      let result;
      try { result = await identity.exchange(url, { ...attempt, state }, signal); }
      catch (error) {
        if (!(error instanceof ReaderAuthorizationDenied)) throw error;
        const response = redirect(attempt.returnTo); response.headers.append('Set-Cookie', loginCookie('', 0)); return response;
      }
      if (!result.subject || !result.accessToken || !Number.isFinite(result.expiresIn) || result.expiresIn <= 0) throw new Error('Invalid identity response');
      const ttlMs = Math.min(TTL, Math.floor(result.expiresIn * 1000));
      let session;
      try {
        if (attempt.parentSessionId) {
          if (id !== attempt.parentSessionId) throw new ReaderValidationError('session changed during login');
          session = store.rotateSession(attempt.parentSessionId, { userId: result.subject, upstreamToken: result.accessToken, ttlMs });
        } else {
          if (localSession) throw new ReaderValidationError('session changed during login');
          session = store.createSession({ userId: result.subject, upstreamToken: result.accessToken, ttlMs });
        }
      } catch (error) { store.queueRevocation(result.accessToken, Date.now() + ttlMs); throw error; }
      const response = redirect(attempt.returnTo);
      response.headers.append('Set-Cookie', store.serializeCookie(session.id, ttlMs));
      response.headers.append('Set-Cookie', loginCookie('', 0));
      return response;
    }
    if (url.pathname === '/reader-api/v1/me' && request.method === 'GET') {
      const verified = await verifiedReader(store, identity, id, signal);
      if (!verified) return json(401, { error: 'not_authenticated' });
      const profile = profiles?.getOrCreate(verified.session.userId, verified.profile.displayName);
      return json(200, { user: { displayName: profile?.displayName ?? verified.profile.displayName },
        csrfToken: csrf(id), profileUrl: identity.profileUrl ?? null, ...(profile ? { profile } : {}) });
    }
    if (url.pathname === '/reader-auth/logout' && request.method === 'POST') {
      if (!validWrite(request, id)) return json(403, { error: 'invalid_request' });
      store.revokeAndQueue(id);
      const response = new Response(null, { status: 204, headers: securityHeaders });
      response.headers.append('Set-Cookie', store.serializeCookie('', 0));
      return response;
    }
    return await profileRoutes(request, url, id, signal)
      ?? await commentRoutes(request, url, id, signal) ?? json(404, { error: 'not_found' });
  }
  return async request => {
    try { return await dispatch(request); }
    catch (error) { return json(error instanceof ReaderValidationError ? 400 : 503,
      { error: error instanceof ReaderValidationError ? 'invalid_request' : 'identity_unavailable' }); }
  };
}

export async function drainRevocations(store, identity, signal = AbortSignal.timeout(5000)) {
  for (const entry of store.pendingRevocations()) {
    if (signal.aborted) break;
    try { await identity.revoke(entry.token, signal); store.completeRevocation(entry.id); } catch { break; }
  }
}
