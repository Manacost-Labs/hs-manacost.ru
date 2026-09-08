import * as oidc from 'openid-client';
import { ReaderAuthorizationDenied } from './core.js';

export function validateIdentityClient({ origin, issuer, clientId, clientSecret, deployment }) {
  const site = new URL(origin); const identity = new URL(issuer);
  if (!['production', 'staging', 'test'].includes(deployment)
    || site.origin !== origin || site.protocol !== 'https:' || site.port
    || identity.href !== issuer || identity.protocol !== 'https:' || identity.pathname !== '/identity'
    || identity.username || identity.password || identity.search || identity.hash || identity.port
    || !clientId || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Invalid identity client configuration');
  const production = deployment === 'production';
  if (production !== ['hs-manacost.ru', 'hs-manacost.com'].includes(site.hostname)
    || production !== (issuer === 'https://hearthpulse.net/identity')
    || (!production && identity.hostname === 'hearthpulse.net')) throw new Error('Mixed identity environments');
}

/** Fixed first-party metadata avoids discovery-controlled outbound destinations. HTTPS and ID-token signatures stay mandatory. */
export function createIdentityClient(options, transport = fetch) {
  validateIdentityClient(options);
  const { issuer, origin, clientId, clientSecret } = options;
  const metadata = { issuer, authorization_endpoint: `${issuer}/auth`, token_endpoint: `${issuer}/token`,
    userinfo_endpoint: `${issuer}/me`, introspection_endpoint: `${issuer}/token/introspection`,
    revocation_endpoint: `${issuer}/token/revocation`, jwks_uri: `${issuer}/jwks`,
    id_token_signing_alg_values_supported: ['RS256'], authorization_response_iss_parameter_supported: true };
  const allowed = new Set(Object.values(metadata).filter(value => typeof value === 'string'));
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
  return {
    profileUrl: options.deployment === 'production' ? 'https://hearthpulse.net/?login' : null,
    authorizationUrl({ state, nonce, codeChallenge }) {
      return oidc.buildAuthorizationUrl(config(), { redirect_uri: `${origin}/reader-auth/callback`,
        scope: 'openid profile', response_type: 'code', prompt: 'login consent', state, nonce,
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
      return { subject: claims.sub, accessToken: tokens.access_token, expiresIn: tokens.expires_in };
    },
    async profile(token, subject, signal) {
      const configuration = config(signal);
      const status = await oidc.tokenIntrospection(configuration, token, { token_type_hint: 'access_token' });
      if (!status.active || status.sub !== subject || status.client_id !== clientId
        || typeof status.exp !== 'number' || status.exp <= Date.now() / 1000) return null;
      const profile = await oidc.fetchUserInfo(configuration, token, subject);
      if (typeof profile.name !== 'string' || profile.name.length > 200) throw new Error('Invalid profile');
      return { displayName: profile.name || 'Читатель' };
    },
    async revoke(token, signal) { await oidc.tokenRevocation(config(signal), token, { token_type_hint: 'access_token' }); },
  };
}
