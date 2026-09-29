import { createRemoteJWKSet, jwtVerify } from 'jose';

export function createAuthorization({ issuer, jwksUrl, resource, permissions }, resolveKey) {
  const issuerUrl = new URL(issuer); const keys = new URL(jwksUrl); const audience = new URL(resource);
  for (const url of [issuerUrl, keys, audience]) {
    if (url.protocol !== 'https:' || url.username || url.password || url.search || url.hash) throw new Error('invalid_oauth_configuration');
  }
  if (keys.origin !== issuerUrl.origin || typeof permissions?.get !== 'function') throw new Error('invalid_oauth_configuration');
  const keySet = resolveKey ?? createRemoteJWKSet(keys, { timeoutDuration: 4000, cooldownDuration: 30000, cacheMaxAge: 300000 });
  return async header => {
    if (typeof header !== 'string' || !/^Bearer [A-Za-z0-9_.-]{1,8192}$/.test(header)) throw new Error('unauthorized');
    const { payload, protectedHeader } = await jwtVerify(header.slice(7), keySet, { issuer, audience: resource, algorithms: ['RS256', 'ES256'],
      requiredClaims: ['sub', 'exp', 'iat', 'aud', 'iss'], maxTokenAge: '15m', clockTolerance: 5 });
    if (protectedHeader.typ !== 'at+jwt' || typeof payload.sub !== 'string' || typeof payload.scope !== 'string' || !payload.scope.split(' ').includes('articles:read')) throw new Error('forbidden');
    const roles = await permissions.get([payload.sub]);
    if (roles.get(payload.sub) !== true) throw new Error('forbidden');
    return payload.sub;
  };
}
