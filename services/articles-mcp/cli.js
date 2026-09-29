import { isAbsolute } from 'node:path';
import { StdioServerTransport } from '@modelcontextprotocol/server/stdio';
import { ArticleStore } from './store.js';
import { WordPressSource } from './source.js';
import { ArticleLibrary } from './library.js';
import { syncArticles } from './sync.js';
import { createMcpServer } from './mcp.js';
import { createAuthorization } from './auth.js';
import { createHttpServer } from './http.js';
import { createReaderPermissionsClient } from '../reader/community-clients.js';

process.umask(0o077);
try {
  const filename = process.env.MCP_DATABASE;
  if (!filename || !isAbsolute(filename)) throw new Error('absolute_database_path_required');
  const source = new WordPressSource(process.env.MCP_SOURCE_ORIGIN, { authorization: process.env.MCP_STAGING_SOURCE_AUTHORIZATION });
  const store = new ArticleStore(filename, source.origin); const library = new ArticleLibrary(store, source);
  const mode = process.argv[2];
  if (mode === 'sync') {
    try { console.log(JSON.stringify(await syncArticles(store, source, { full: process.argv[3] === '--full' }))); } finally { store.close(); }
  } else if (mode === 'stdio') {
    await createMcpServer(library).connect(new StdioServerTransport());
  } else if (mode === 'http') {
    if (process.env.MCP_OAUTH_ISSUER !== 'https://hearthpulse.net/identity') throw new Error('hearthpulse_issuer_required');
    const clientId = source.origin === 'https://hs-manacost.ru' ? 'manacost-reader-production' : 'manacost-reader-staging';
    const permissions = createReaderPermissionsClient({ clientId, clientSecret: process.env.MCP_PERMISSIONS_CLIENT_SECRET });
    const options = { resource: process.env.MCP_RESOURCE_URL, issuer: process.env.MCP_OAUTH_ISSUER,
      jwksUrl: process.env.MCP_OAUTH_JWKS_URL, permissions };
    const authorize = createAuthorization(options); const port = Number(process.env.MCP_PORT ?? 8792);
    if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('invalid_port');
    const server = createHttpServer({ library, ...options, authorize }); server.listen(port, '127.0.0.1');
    process.once('SIGTERM', () => { server.close(() => { store.close(); process.exit(0); }); });
  } else { store.close(); throw new Error('expected_sync_stdio_or_http'); }
} catch { console.error('Articles MCP failed. Check configuration and source availability; details are withheld to protect credentials.'); process.exitCode = 1; }
