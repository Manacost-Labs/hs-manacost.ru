import { createPrivateKey } from 'node:crypto';
import { openSync, fstatSync, readFileSync, closeSync, constants } from 'node:fs';
import { isAbsolute } from 'node:path';
import { createReaderPermissionsClient } from './community-clients.js';
import { createMcpOAuthRoutes } from './mcp-oauth.js';

function privateKey(filename) {
  if (!filename || !isAbsolute(filename)) throw new Error('Private MCP signing key path required');
  const fd = openSync(filename, constants.O_RDONLY | constants.O_NOFOLLOW);
  try {
    const info = fstatSync(fd);
    if (!info.isFile() || info.size > 16384 || (info.mode & 0o077) !== 0) throw new Error('Unsafe MCP signing key file');
    return createPrivateKey(readFileSync(fd));
  } finally { closeSync(fd); }
}

/** Disabled by default; validate the full boundary before creating OAuth tables. */
export function createMcpOAuth({ options, store, identity, env = process.env, transport = fetch }) {
  if (env.READER_MCP_OAUTH_ENABLED !== '1') return null;
  const staging = options.deployment === 'staging' && options.origin === 'https://test.hs-manacost.ru'
    && options.clientId === 'manacost-reader-staging';
  const production = env.READER_ALLOW_PRODUCTION_MCP_OAUTH === '1' && options.deployment === 'production'
    && options.origin === 'https://hs-manacost.ru' && options.clientId === 'manacost-reader-production';
  if ((!staging && !production) || options.issuer !== 'https://hearthpulse.net/identity') throw new Error('Invalid MCP identity boundary');
  const signingKey = privateKey(env.READER_MCP_OAUTH_SIGNING_KEY_FILE);
  const permissions = createReaderPermissionsClient(options, transport);
  return createMcpOAuthRoutes({ origin: options.origin, store, identity, permissions, signingKey });
}
