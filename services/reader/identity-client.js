import * as oidc from 'openid-client';
import { ReaderAuthorizationDenied } from './core.js';

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
    && origin === 'https://test.hs-manacost.ru'
    && issuer === 'https://hearthpulse.net/identity'
    && clientId === 'manacost-reader-staging';
  if (allowProductionIdentityForStaging === true && !productionIdentityForStaging) throw new Error('Invalid production identity bridge');
  if (production !== ['hs-manacost.ru', 'hs-manacost.com'].includes(site.hostname)
    || (production && issuer !== 'https://hearthpulse.net/identity')
    || (!production && identity.hostname === 'hearthpulse.net' && !productionIdentityForStaging)) throw new Error('Mixed identity environments');
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

  async function verify(token, subject, signal) {
    const status = await oidc.tokenIntrospection(config(signal), token, { token_type_hint: 'access_token' });
    return Boolean(status.active && status.sub === subject && status.client_id === clientId
      && typeof status.exp === 'number' && status.exp > Date.now() / 1000);
  }

  return {
    profileUrl: options.deployment === 'production' || options.allowProductionIdentityForStaging === true
      && options.deployment === 'staging' && options.origin === 'https://test.hs-manacost.ru'
      && options.issuer === 'https://hearthpulse.net/identity' && options.clientId === 'manacost-reader-staging'
      ? 'https://hearthpulse.net/?login' : null,
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
    verify,
    async profile(token, subject, signal) {
      if (!await verify(token, subject, signal)) return null;
      const configuration = config(signal);
      const profile = await oidc.fetchUserInfo(configuration, token, subject);
      if (typeof profile.name !== 'string' || profile.name.length > 200) throw new Error('Invalid profile');
      return { displayName: profile.name || 'Читатель' };
    },
    async revoke(token, signal) { await oidc.tokenRevocation(config(signal), token, { token_type_hint: 'access_token' }); },
  };
}
