import * as oidc from 'openid-client';
import { createHash } from 'node:crypto';
import { ReaderAuthorizationDenied } from './core.js';

const IDENTITY_PROFILE_CACHE_TTL = 30_000;
const IDENTITY_WRITE_CACHE_TTL = 5_000;
const IDENTITY_CACHE_LIMIT = 4096;
const HEARTHPULSE_ISSUER = 'https://hearthpulse.net/identity';
const READER_STAGING_ORIGIN = 'https://test.hs-manacost.ru';
const READER_STAGING_CLIENT_ID = 'manacost-reader-staging';
const READER_PRODUCTION_ORIGIN = 'https://hs-manacost.ru';
const READER_PRODUCTION_CLIENT_ID = 'manacost-reader-production';

function isPersistentReaderIdentity(options) {
  return options.issuer === HEARTHPULSE_ISSUER && (
    options.deployment === 'production'
      && options.origin === READER_PRODUCTION_ORIGIN
      && options.clientId === READER_PRODUCTION_CLIENT_ID
    || options.allowProductionIdentityForStaging === true
      && options.deployment === 'staging'
      && options.origin === READER_STAGING_ORIGIN
      && options.clientId === READER_STAGING_CLIENT_ID
  );
}

export function validateIdentityClient(options) {
  const { origin, issuer, clientId, clientSecret, deployment, allowProductionIdentityForStaging } = options;
  const site = new URL(origin); const identity = new URL(issuer);
  if (!['production', 'staging', 'test'].includes(deployment)
    || site.origin !== origin || site.protocol !== 'https:' || site.port
    || identity.href !== issuer || identity.protocol !== 'https:' || identity.pathname !== '/identity'
    || identity.username || identity.password || identity.search || identity.hash || identity.port
    || !clientId || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Invalid identity client configuration');
  const production = deployment === 'production';
  const productionIdentityForStaging = allowProductionIdentityForStaging === true
    && deployment === 'staging'
    && origin === READER_STAGING_ORIGIN
    && issuer === HEARTHPULSE_ISSUER
    && clientId === READER_STAGING_CLIENT_ID;
  if (allowProductionIdentityForStaging === true && !productionIdentityForStaging) throw new Error('Invalid production identity bridge');
  if (production && !isPersistentReaderIdentity(options)
    || !production && ['hs-manacost.ru', 'hs-manacost.com'].includes(site.hostname)
    || (!production && identity.hostname === 'hearthpulse.net' && !productionIdentityForStaging)) throw new Error('Mixed identity environments');
}

/** Fixed first-party metadata avoids discovery-controlled outbound destinations. HTTPS and ID-token signatures stay mandatory. */
export function createIdentityClient(options, transport = fetch) {
  validateIdentityClient(options);
  const { issuer, origin, clientId, clientSecret } = options;
  const persistentLogin = isPersistentReaderIdentity(options);
  const metadata = { issuer, authorization_endpoint: `${issuer}/auth`, token_endpoint: `${issuer}/token`,
    userinfo_endpoint: `${issuer}/me`, introspection_endpoint: `${issuer}/token/introspection`,
    revocation_endpoint: `${issuer}/token/revocation`, jwks_uri: `${issuer}/jwks`,
    id_token_signing_alg_values_supported: ['RS256'], authorization_response_iss_parameter_supported: true };
  const allowed = new Set(Object.values(metadata).filter(value => typeof value === 'string'));
  const snapshots = new Map();
  const pending = new Map();
  const cacheKey = (token, subject) => createHash('sha256').update(subject).update('\0').update(token).digest('base64url');
  function remember(key, value) {
    if (!snapshots.has(key) && snapshots.size >= IDENTITY_CACHE_LIMIT) snapshots.delete(snapshots.keys().next().value);
    snapshots.set(key, { ...value, expiresAt: Date.now() + IDENTITY_PROFILE_CACHE_TTL });
    return value;
  }
  function remembered(key) {
    const value = snapshots.get(key);
    if (!value || value.expiresAt <= Date.now()) { snapshots.delete(key); return null; }
    return value;
  }
  async function shared(key, task) {
    if (pending.has(key)) return pending.get(key);
    const operation = task().finally(() => pending.delete(key));
    pending.set(key, operation);
    return operation;
  }
  function config(signal = AbortSignal.timeout(5000)) {
    const value = new oidc.Configuration(metadata, clientId, { id_token_signed_response_alg: 'RS256' }, oidc.ClientSecretBasic(clientSecret));
    value.timeout = 5;
    value[oidc.customFetch] = (url, init) => {
      if (!allowed.has(String(url))) throw new Error('Unexpected identity endpoint');
      return transport(url, { ...init, redirect: 'error', signal: AbortSignal.any([signal, ...(init?.signal ? [init.signal] : [])]) });
    };
    oidc.enableNonRepudiationChecks(value);
    return value;
  }

  async function verify(token, subject, signal = AbortSignal.timeout(5000)) {
    const key = cacheKey(token, subject);
    const cached = remembered(key);
    if (cached?.active === true && cached.verifiedAt > Date.now() - IDENTITY_WRITE_CACHE_TTL) return true;
    const result = await shared(`verify:${key}`, async () => {
      const upstreamSignal = AbortSignal.timeout(5000);
      const verifiedAt = Date.now();
      const status = await oidc.tokenIntrospection(config(upstreamSignal), token, { token_type_hint: 'access_token' });
      const active = Boolean(status.active && status.sub === subject && status.client_id === clientId
        && typeof status.exp === 'number' && status.exp > Date.now() / 1000);
      if (!active) {
        snapshots.delete(key);
        return { active: false, profile: null };
      }
      return remember(key, { active: true, profile: cached?.profile ?? null, verifiedAt });
    });
    signal.throwIfAborted();
    return result.active;
  }

  return {
    profileUrl: options.deployment === 'production' || options.allowProductionIdentityForStaging === true
      && options.deployment === 'staging' && options.origin === 'https://test.hs-manacost.ru'
      && options.issuer === 'https://hearthpulse.net/identity' && options.clientId === 'manacost-reader-staging'
      ? 'https://hearthpulse.net/?login' : null,
    authorizationUrl({ state, nonce, codeChallenge }) {
      return oidc.buildAuthorizationUrl(config(), { redirect_uri: `${origin}/reader-auth/callback`,
        scope: persistentLogin ? 'openid profile offline_access' : 'openid profile', response_type: 'code', prompt: 'login consent', state, nonce,
        code_challenge: codeChallenge, code_challenge_method: 'S256' });
    },
    async exchange(url, attempt, signal) {
      if (url.origin !== origin || url.pathname !== '/reader-auth/callback') throw new Error('Unexpected callback');
      let tokens;
      try {
        tokens = await oidc.authorizationCodeGrant(config(signal), url, {
          pkceCodeVerifier: attempt.codeVerifier, expectedState: attempt.state, expectedNonce: attempt.nonce, idTokenExpected: true,
        });
      } catch (error) {
        if (error instanceof oidc.AuthorizationResponseError && error.error === 'access_denied') throw new ReaderAuthorizationDenied();
        throw error;
      }
      const claims = tokens.claims();
      if (!claims?.sub || !tokens.access_token || !tokens.expires_in) throw new Error('Incomplete identity tokens');
      return { subject: claims.sub, accessToken: tokens.access_token, expiresIn: tokens.expires_in,
        ...(persistentLogin && tokens.refresh_token ? { refreshToken: tokens.refresh_token } : {}) };
    },
    async refresh(refreshToken, subject, signal) {
      const tokens = await oidc.refreshTokenGrant(config(signal), refreshToken);
      const claims = tokens.claims();
      if (claims && claims.sub !== subject || !tokens.refresh_token || !tokens.access_token
        || !tokens.expires_in || !await verify(tokens.access_token, subject, signal)) {
        throw new Error('Invalid refreshed identity');
      }
      return { subject, accessToken: tokens.access_token, refreshToken: tokens.refresh_token, expiresIn: tokens.expires_in };
    },
    verify,
    async profile(token, subject, signal = AbortSignal.timeout(5000)) {
      const key = cacheKey(token, subject);
      const cached = remembered(key);
      if (cached?.active === true && cached.profile
        && cached.verifiedAt > Date.now() - IDENTITY_WRITE_CACHE_TTL) return cached.profile;
      if (cached?.profile) {
        if (!await verify(token, subject, signal)) return null;
        return remembered(key)?.profile ?? cached.profile;
      }
      const result = await shared(`profile:${key}`, async () => {
        const upstreamSignal = AbortSignal.timeout(5000);
        const verifiedAt = Date.now();
        let profile;
        try {
          profile = await oidc.fetchUserInfo(config(upstreamSignal), token, subject);
        } catch (error) {
          if (error instanceof oidc.ClientError && error.cause?.status === 401) return { active: false, profile: null };
          throw error;
        }
        if (typeof profile.name !== 'string' || profile.name.length > 200) throw new Error('Invalid profile');
        return remember(key, {
          active: true,
          profile: { displayName: profile.name || 'Читатель' },
          verifiedAt,
        });
      });
      signal.throwIfAborted();
      return result.active ? result.profile : null;
    },
    // Omit the optional hint: the durable queue can contain either access or refresh tokens.
    async revoke(token, signal) { await oidc.tokenRevocation(config(signal), token); },
  };
}
