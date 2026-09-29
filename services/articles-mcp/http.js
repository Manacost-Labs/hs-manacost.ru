import { createServer } from 'node:http';
import { NodeStreamableHTTPServerTransport } from '@modelcontextprotocol/node';
import { createMcpServer } from './mcp.js';

export function createHttpServer({ library, resource, issuer, authorize, now = Date.now }) {
  const target = new URL(resource);
  if (target.protocol !== 'https:' || target.pathname !== '/mcp' || target.search || target.hash || target.username || target.password) throw new Error('invalid_resource');
  const metadataUrl = `${target.origin}/.well-known/oauth-protected-resource/mcp`;
  const clients = new Map(); let active = 0; let requests = 0; let windowStart = now();
  return createServer({ requestTimeout: 10000, headersTimeout: 10000, maxHeaderSize: 12288 }, async (req, res) => {
    const send = (status, data) => { res.writeHead(status, { 'Content-Type': 'application/json' }); res.end(JSON.stringify(data)); };
    res.setHeader('Cache-Control', 'no-store'); res.setHeader('X-Robots-Tag', 'noindex, nofollow');
    res.setHeader('X-Content-Type-Options', 'nosniff');
    if (now() - windowStart >= 60000) { windowStart = now(); requests = 0; clients.clear(); }
    if (++requests > 300) { res.setHeader('Retry-After', '60'); send(429, { error: 'rate_limit' }); return; }
    if (![target.host, `127.0.0.1:${req.socket.localPort}`].includes(req.headers.host)
      || req.headers.origin && req.headers.origin !== target.origin) { send(403, { error: 'forbidden_origin' }); return; }
    if (req.method === 'GET' && req.url === '/.well-known/oauth-protected-resource/mcp') {
      send(200, { resource, authorization_servers: [issuer], scopes_supported: ['articles:read'], bearer_methods_supported: ['header'] }); return;
    }
    if (req.url !== '/mcp') { send(404, { error: 'not_found' }); return; }
    let subject;
    try { subject = await authorize(req.headers.authorization); }
    catch { res.setHeader('WWW-Authenticate', `Bearer resource_metadata="${metadataUrl}", scope="articles:read"`); send(401, { error: 'unauthorized' }); return; }
    const count = (clients.get(subject) ?? 0) + 1; clients.set(subject, count);
    if (count > 60 || active >= 4) { res.setHeader('Retry-After', '60'); send(429, { error: 'rate_limit' }); return; }
    if (req.method !== 'POST') { res.setHeader('Allow', 'POST'); send(405, { error: 'method_not_allowed' }); return; }
    if (!req.headers['content-type']?.startsWith('application/json')) { send(415, { error: 'json_required' }); return; }
    active++; let server; let timer;
    try {
      timer = setTimeout(() => res.destroy(), 10000);
      let size = 0; const chunks = [];
      for await (const chunk of req) { size += chunk.length; if (size > 16384) { send(413, { error: 'body_too_large' }); return; } chunks.push(chunk); }
      let body;
      try { body = JSON.parse(Buffer.concat(chunks).toString('utf8')); } catch { send(400, { error: 'invalid_json' }); return; }
      server = createMcpServer(library);
      const transport = new NodeStreamableHTTPServerTransport({ sessionIdGenerator: undefined, enableJsonResponse: true });
      res.once('close', () => { void server.close(); });
      await server.connect(transport); await transport.handleRequest(req, res, body);
    } catch { if (!res.headersSent) send(503, { error: 'temporarily_unavailable' }); else res.destroy(); }
    finally { clearTimeout(timer); active--; }
  });
}
