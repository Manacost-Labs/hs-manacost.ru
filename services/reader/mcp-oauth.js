import { createHash, createPublicKey, createCipheriv, createDecipheriv, randomBytes, sign } from 'node:crypto';
import { verifiedWriter, bodyBytes, BodyTooLarge } from './profile-http.js';
import { McpOAuthStore, oauthHash, oauthNonce } from './mcp-oauth-store.js';

const SCOPE = 'articles:read';
const TTL = 900;
const enc = value => Buffer.from(JSON.stringify(value)).toString('base64url');
const escape = value => String(value).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
const headers = { 'Cache-Control': 'private, no-store', Pragma: 'no-cache', 'Referrer-Policy': 'no-referrer',
  'X-Robots-Tag': 'noindex, nofollow', 'X-Content-Type-Options': 'nosniff', 'Content-Security-Policy': "default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'" };
class OAuthError extends Error { constructor(code, status = 400) { super(code); this.code = code; this.status = status; } }
const error = (code, status) => { throw new OAuthError(code, status); };
const json = (status, data, cors = false) => Response.json(data, { status, headers: { ...headers, ...(cors ? { 'Access-Control-Allow-Origin': '*' } : {}) } });
const redirect = uri => new Response(null, { status: 303, headers: { ...headers, Location: String(uri) } });
const page = (body, status = 200) => new Response(`<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Доступ MCP — Manacost</title><style>body{font:1rem/1.6 system-ui,sans-serif;background:#f5f6f8;color:#172033;margin:0;padding:1rem}main{max-width:36rem;margin:3rem auto;background:white;padding:1.5rem;border-radius:.75rem;overflow-wrap:anywhere}h1{font-size:1.5rem}button,a{font:inherit}button{min-height:44px;max-width:100%;padding:.65rem 1rem;border:1px solid #334155;border-radius:.3rem;background:#fff;color:#172033;margin:.25rem}a{color:#0645ad;display:inline-flex;align-items:center;min-height:44px;max-width:100%}a:focus-visible,button:focus-visible{outline:3px solid #075fce;outline-offset:3px}</style></head><body><main><h1>Доступ к статьям Manacost</h1>${body}</main></body></html>`, { status, headers: { ...headers, 'Referrer-Policy': 'origin', 'Content-Type': 'text/html; charset=utf-8' } });

function parameters(params, allowed) {
  for (const key of params.keys()) if (!allowed.includes(key) || params.getAll(key).length !== 1) error('invalid_request');
  return Object.fromEntries(params);
}
async function input(request, type) {
  if (request.headers.get('content-type')?.split(';')[0].trim().toLowerCase() !== type) error('invalid_request');
  const text = (await bodyBytes(request, 4096)).toString('utf8');
  if (type === 'application/json') {
    try { const item = JSON.parse(text); if (!item || Array.isArray(item) || typeof item !== 'object') error('invalid_client_metadata'); return item; }
    catch { error('invalid_client_metadata'); }
  }
  return new URLSearchParams(text);
}
function safeRedirect(value) {
  if (typeof value !== 'string' || value.length > 2048 || /[\u0000-\u0020\u007f\\]/.test(value)) error('invalid_redirect_uri');
  let url; try { url = new URL(value); } catch { error('invalid_redirect_uri'); }
  if (url.username || url.password || url.hash || !(url.protocol === 'https:' || url.protocol === 'http:' && ['localhost', '127.0.0.1', '[::1]'].includes(url.hostname))) error('invalid_redirect_uri');
  return value;
}
function sealSession(id, key, client) {
  const iv = randomBytes(12); const cipher = createCipheriv('aes-256-gcm', key, iv, { authTagLength: 16 }); cipher.setAAD(Buffer.from(`mcp-oauth:${client}`));
  return { iv: iv.toString('base64url'), ciphertext: Buffer.concat([cipher.update(id, 'utf8'), cipher.final()]).toString('base64url'), tag: cipher.getAuthTag().toString('base64url') };
}
function openSession(value, key, client) {
  const cipher = createDecipheriv('aes-256-gcm', key, Buffer.from(value.iv, 'base64url'), { authTagLength: 16 }); cipher.setAAD(Buffer.from(`mcp-oauth:${client}`));
  cipher.setAuthTag(Buffer.from(value.tag, 'base64url')); return Buffer.concat([cipher.update(Buffer.from(value.ciphertext, 'base64url')), cipher.final()]).toString('utf8');
}

/** Own OAuth authority; Hearthpulse stays the identity and current-role authority. */
export function createMcpOAuthRoutes({ origin, store, identity, permissions, signingKey }) {
  if (!['https://hs-manacost.ru', 'https://test.hs-manacost.ru'].includes(origin)
    || signingKey?.type !== 'private' || signingKey.asymmetricKeyType !== 'rsa' || signingKey.asymmetricKeyDetails.modulusLength < 2048
    || typeof permissions?.get !== 'function') throw new Error('Invalid MCP OAuth boundary');
  const issuer = `${origin}/mcp-oauth`; const resource = `${origin}/mcp`;
  const publicJwk = createPublicKey(signingKey).export({ format: 'jwk' });
  const kid = createHash('sha256').update(JSON.stringify(publicJwk)).digest('base64url');
  const state = new McpOAuthStore(store.db, issuer, store.now);
  let start = store.now(); const counts = new Map();
  async function admin(id, signal) {
    const verified = await verifiedWriter(store, identity, id, signal); if (!verified) return null;
    const roles = await permissions.get([verified.session.userId]); signal.throwIfAborted();
    const current = store.getSession(id);
    return roles.get(verified.session.userId) === true && current?.userId === verified.session.userId ? verified.session : null;
  }
  function tokens(document) {
    const now = Math.floor(store.now() / 1000);
    const unsigned = `${enc({ alg: 'RS256', typ: 'at+jwt', kid })}.${enc({ iss: issuer, aud: resource, sub: document.sub,
      client_id: document.client, scope: SCOPE, iat: now, exp: now + TTL, jti: oauthNonce() })}`;
    const access_token = `${unsigned}.${sign('RSA-SHA256', Buffer.from(unsigned), signingKey).toString('base64url')}`;
    const refresh_token = state.put('refresh', document, document.familyExpiresAt - store.now()); state.pruneRefresh(document.client, document.sub);
    state.retainClient(document.client);
    return { access_token, token_type: 'Bearer', expires_in: TTL, refresh_token, scope: SCOPE };
  }
  return async (request, url, id, signal) => {
    const metadata = '/.well-known/oauth-authorization-server/mcp-oauth';
    if (url.pathname !== metadata && !url.pathname.startsWith('/mcp-oauth/')) return null;
    const publicPaths = [metadata, '/mcp-oauth/jwks', '/mcp-oauth/register', '/mcp-oauth/token', '/mcp-oauth/revoke'];
    const publicRoute = publicPaths.includes(url.pathname);
    if (!publicRoute && url.pathname !== '/mcp-oauth/authorize') return json(404, { error: 'not_found' });
    try {
      if (store.now() - start >= 60000) { start = store.now(); counts.clear(); state.cleanup(); }
      const count = (counts.get(url.pathname) ?? 0) + 1; counts.set(url.pathname, count);
      if (count > (url.pathname === '/mcp-oauth/register' ? 30 : 300)) return new Response(null, { status: 429, headers: { ...headers, 'Retry-After': '60' } });
      if (request.method === 'OPTIONS' && publicRoute) return new Response(null, { status: 204, headers: { ...headers, 'Access-Control-Allow-Origin': '*', 'Access-Control-Allow-Methods': 'GET, POST, OPTIONS', 'Access-Control-Allow-Headers': 'Content-Type', 'Access-Control-Max-Age': '600' } });
      if (url.pathname === metadata && request.method === 'GET') return json(200, { issuer, authorization_endpoint: `${issuer}/authorize`,
        token_endpoint: `${issuer}/token`, registration_endpoint: `${issuer}/register`, revocation_endpoint: `${issuer}/revoke`, jwks_uri: `${issuer}/jwks`,
        scopes_supported: [SCOPE], response_types_supported: ['code'], grant_types_supported: ['authorization_code', 'refresh_token'],
        token_endpoint_auth_methods_supported: ['none'], code_challenge_methods_supported: ['S256'],
        authorization_response_iss_parameter_supported: true }, true);
      if (url.pathname === '/mcp-oauth/jwks' && request.method === 'GET') return json(200, { keys: [{ ...publicJwk, kid, alg: 'RS256', use: 'sig' }] }, true);
      if (url.pathname === '/mcp-oauth/register' && request.method === 'POST') {
        const data = await input(request, 'application/json');
        if (!Array.isArray(data.redirect_uris) || data.redirect_uris.length < 1 || data.redirect_uris.length > 5
          || data.token_endpoint_auth_method !== undefined && data.token_endpoint_auth_method !== 'none'
          || data.grant_types !== undefined && (!Array.isArray(data.grant_types) || !data.grant_types.includes('authorization_code') || data.grant_types.some(value => !['authorization_code', 'refresh_token'].includes(value)))
          || data.response_types !== undefined && (!Array.isArray(data.response_types) || data.response_types.length !== 1 || data.response_types[0] !== 'code')
          || data.scope !== undefined && data.scope !== SCOPE) error('invalid_client_metadata');
        const client_name = data.client_name ?? 'MCP client';
        if (typeof client_name !== 'string' || !client_name.trim() || client_name.length > 100 || /[\u0000-\u001f\u007f]/.test(client_name)) error('invalid_client_metadata');
        const document = { client_name, redirect_uris: [...new Set(data.redirect_uris.map(safeRedirect))], token_endpoint_auth_method: 'none',
          grant_types: ['authorization_code', 'refresh_token'], response_types: ['code'] };
        return json(201, { ...document, client_id: state.register(document), client_id_issued_at: Math.floor(store.now() / 1000) }, true);
      }
      if (url.pathname === '/mcp-oauth/authorize' && request.method === 'GET') {
        let nonce; let document;
        if (url.searchParams.has('request')) {
          nonce = parameters(url.searchParams, ['request']).request; document = state.get('requests', nonce);
          if (!document) error('invalid_request');
        } else {
          const data = parameters(url.searchParams, ['client_id', 'redirect_uri', 'response_type', 'scope', 'resource', 'code_challenge', 'code_challenge_method', 'state']);
          const client = state.client(data.client_id); if (!client) error('invalid_client');
          if (!client.redirect_uris.includes(data.redirect_uri)) error('invalid_redirect_uri');
          if (data.response_type !== 'code' || data.scope !== SCOPE || data.resource !== resource || data.code_challenge_method !== 'S256'
            || !/^[A-Za-z0-9_-]{43}$/.test(data.code_challenge ?? '') || (data.state ?? '').length > 1024) error('invalid_request');
          document = { client: data.client_id, redirect: data.redirect_uri, challenge: data.code_challenge, state: data.state ?? '', resource };
          nonce = state.put('requests', document, 300000);
        }
        const client = state.client(document.client); if (!client) error('invalid_client');
        if (!store.getSession(id)) {
          const login = `/reader-auth/start?${new URLSearchParams({ returnTo: `/mcp-oauth/authorize?request=${nonce}` })}`;
          return page(`<p>Войдите в Hearthpulse через существующий аккаунт. Доступ разрешён только администраторам.</p><p><a href="${escape(login)}">Войти через Hearthpulse</a></p>`);
        }
        if (!await admin(id, signal)) error('access_denied', 403);
        const approval = oauthNonce(); document = { ...document, sidHash: oauthHash(id), approvalHash: oauthHash(approval) };
        if (!state.bind(nonce, document)) error('invalid_request');
        return page(`<p>Приложение <strong>${escape(client.client_name)}</strong> запрашивает чтение опубликованных статей через MCP.</p><p>Адрес возврата: ${escape(new URL(document.redirect).host)}.</p><p>Доступ не позволяет редактировать статьи или читать закрытые материалы.</p><form method="post" action="/mcp-oauth/authorize"><input type="hidden" name="request" value="${nonce}"><input type="hidden" name="approval_token" value="${approval}"><button name="decision" value="approve" type="submit">Разрешить чтение</button><button name="decision" value="deny" type="submit">Отказать</button></form>`);
      }
      if (url.pathname === '/mcp-oauth/authorize' && request.method === 'POST') {
        if (request.headers.get('origin') !== origin || request.headers.get('sec-fetch-site') === 'cross-site') error('access_denied', 403);
        const data = parameters(await input(request, 'application/x-www-form-urlencoded'), ['request', 'approval_token', 'decision']);
        const document = state.get('requests', data.request);
        if (!id || !document || document.sidHash !== oauthHash(id) || document.approvalHash !== oauthHash(data.approval_token ?? '')
          || !['approve', 'deny'].includes(data.decision)) error('access_denied', 403);
        const session = await admin(id, signal); if (!session) error('access_denied', 403);
        if (!state.client(document.client)) error('invalid_client');
        if (!state.take('requests', data.request, document)) error('invalid_request');
        const target = new URL(document.redirect); target.searchParams.set('iss', issuer);
        if (document.state) target.searchParams.set('state', document.state);
        if (data.decision === 'deny') target.searchParams.set('error', 'access_denied');
        else { const code = state.put('codes', { ...document, sub: session.userId, session: sealSession(id, store.key, document.client) }, 60000); target.searchParams.set('code', code); }
        return redirect(target);
      }
      if (url.pathname === '/mcp-oauth/token' && request.method === 'POST') {
        const data = parameters(await input(request, 'application/x-www-form-urlencoded'), ['grant_type', 'client_id', 'code', 'code_verifier', 'redirect_uri', 'resource', 'refresh_token', 'scope']);
        if (!state.client(data.client_id)) error('invalid_client');
        if (data.resource !== resource || data.scope && data.scope !== SCOPE) error('invalid_grant');
        const kind = data.grant_type === 'authorization_code' ? 'codes' : data.grant_type === 'refresh_token' ? 'refresh' : error('unsupported_grant_type');
        const value = kind === 'codes' ? data.code : data.refresh_token; const document = state.get(kind, value);
        if (!document) {
          const retired = kind === 'refresh' ? state.get('retired', value) : null;
          if (retired?.client === data.client_id) state.revokeFamily(retired.client, retired.family);
          error('invalid_grant');
        }
        if (document.client !== data.client_id || document.resource !== resource) error('invalid_grant');
        if (kind === 'codes' && (document.redirect !== data.redirect_uri || !/^[A-Za-z0-9._~-]{43,128}$/.test(data.code_verifier ?? '')
          || createHash('sha256').update(data.code_verifier).digest('base64url') !== document.challenge)) error('invalid_grant');
        const sessionId = openSession(document.session, store.key, document.client);
        const session = await admin(sessionId, signal); if (!session || session.userId !== document.sub) error('invalid_grant');
        const result = state.redeem(kind, value, document, grant => tokens({ client: grant.client, sub: grant.sub, resource,
          session: grant.session, family: grant.family, familyExpiresAt: grant.familyExpiresAt }));
        if (!result) error('invalid_grant');
        return json(200, result, true);
      }
      if (url.pathname === '/mcp-oauth/revoke' && request.method === 'POST') {
        const data = parameters(await input(request, 'application/x-www-form-urlencoded'), ['client_id', 'token', 'token_type_hint']);
        if (!state.client(data.client_id)) error('invalid_client');
        const document = state.get('refresh', data.token) ?? state.get('retired', data.token);
        if (document?.client === data.client_id) state.revokeFamily(document.client, document.family);
        return json(200, {}, true);
      }
      return json(404, { error: 'not_found' }, publicRoute);
    } catch (cause) {
      if (!publicRoute) {
        const status = cause instanceof OAuthError ? cause.status : cause instanceof BodyTooLarge ? 413 : 503;
        const message = status === 403 ? 'Доступ разрешён только действующим администраторам Hearthpulse. Проверьте аккаунт и права.'
          : status === 503 ? 'Сейчас не удалось проверить доступ. Повторите подключение из ИИ-клиента немного позже.'
            : 'Запрос подключения недействителен или истёк. Начните новое подключение из ИИ-клиента.';
        return page(`<p role="alert">${message}</p><p><a href="/account/">Проверить аккаунт</a></p>`, status);
      }
      if (cause instanceof OAuthError) return json(cause.status, { error: cause.code }, publicRoute);
      if (cause instanceof BodyTooLarge) return json(413, { error: 'invalid_request' }, publicRoute);
      return json(503, { error: 'temporarily_unavailable' }, publicRoute);
    }
  };
}
