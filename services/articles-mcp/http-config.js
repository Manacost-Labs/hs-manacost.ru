/** Bind HTTP tokens to this index's source; never accept another site's issuer. */
export function oauthOptions(sourceOrigin, env) {
  const issuer = `${sourceOrigin}/mcp-oauth`;
  const resource = `${sourceOrigin}/mcp`;
  if (!['https://hs-manacost.ru', 'https://test.hs-manacost.ru'].includes(sourceOrigin)
    || env.MCP_OAUTH_ISSUER !== issuer || env.MCP_RESOURCE_URL !== resource
    || env.MCP_OAUTH_JWKS_URL !== `${issuer}/jwks`) throw new Error('Invalid site OAuth boundary');
  return { issuer, resource, jwksUrl: env.MCP_OAUTH_JWKS_URL };
}
