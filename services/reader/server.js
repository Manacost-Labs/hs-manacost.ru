import { createServer } from 'node:http';
import { realpathSync } from 'node:fs';
import { isAbsolute } from 'node:path';
import { fileURLToPath } from 'node:url';
import { ReaderStore } from './core.js';
import { createIdentityClient } from './identity-client.js';
import { createReaderHandler, drainRevocations } from './http.js';

export function createReaderServer({ origin, handle }) {
  const authority = new URL(origin).host;
  return createServer({ maxHeaderSize: 8192, requestTimeout: 6000, headersTimeout: 6000 }, async (req, res) => {
    if (req.headers.host !== authority || !req.url?.startsWith('/') || req.url.startsWith('//')) {
      res.writeHead(400, { 'Cache-Control': 'private, no-store' }); res.end(); return;
    }
    try {
      let size = 0;
      for await (const chunk of req) { size += chunk.length; if (size > 4096) { res.writeHead(413, { 'Cache-Control': 'private, no-store' }); res.end(); return; } }
      const response = await handle(new Request(new URL(req.url, origin), { method: req.method, headers: req.headers }));
      const headers = Object.fromEntries(response.headers);
      const cookies = response.headers.getSetCookie();
      if (cookies.length) headers['set-cookie'] = cookies;
      res.writeHead(response.status, headers); res.end(Buffer.from(await response.arrayBuffer()));
    } catch { res.writeHead(503, { 'Cache-Control': 'private, no-store' }); res.end(); }
  });
}

function start() {
  process.umask(0o077);
  const options = { origin: process.env.READER_ORIGIN, issuer: process.env.READER_ISSUER,
    clientId: process.env.READER_CLIENT_ID, clientSecret: process.env.READER_CLIENT_SECRET,
    deployment: process.env.READER_DEPLOYMENT,
    allowProductionIdentityForStaging: process.env.READER_ALLOW_PRODUCTION_IDENTITY_FOR_STAGING === '1' };
  const identity = createIdentityClient(options);
  const filename = process.env.READER_DATABASE;
  if (!filename || !isAbsolute(filename)) throw new Error('An absolute private READER_DATABASE path is required');
  const store = new ReaderStore({ filename, encryptionKey: Buffer.from(process.env.READER_ENCRYPTION_KEY ?? '', 'base64url') });
  const handle = createReaderHandler({ origin: options.origin, identity, store,
    csrfKey: Buffer.from(process.env.READER_CSRF_KEY ?? '', 'base64url') });
  const server = createReaderServer({ origin: options.origin, handle });
  const port = Number(process.env.READER_PORT || 18081);
  if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Invalid reader port');
  let draining = false;
  const timer = setInterval(async () => {
    if (draining) return;
    draining = true;
    try { store.cleanup(); await drainRevocations(store, identity); } catch { /* Retry on the next tick; never log tokens. */ }
    finally { draining = false; }
  }, 15_000);
  timer.unref();
  server.listen(port, '127.0.0.1');
  for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, () => { clearInterval(timer); server.close(() => { store.close(); }); });
}
if (process.argv[1] && realpathSync(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try { start(); } catch { process.stderr.write('Reader configuration invalid; service not started.\n'); process.exitCode = 1; }
}
