import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { once } from 'node:events';
import { mkdtemp, rm, symlink } from 'node:fs/promises';
import { createServer, request } from 'node:http';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { setTimeout as delay } from 'node:timers/promises';

for (const bridge of [false, true]) test(`release symlink boots ${bridge ? 'exact production identity bridge' : 'isolated identity'} and stops cleanly`, async () => {
  const directory = await mkdtemp(join(tmpdir(), 'reader-entrypoint-'));
  const reservation = createServer();
  reservation.listen(0, '127.0.0.1');
  await once(reservation, 'listening');
  const port = reservation.address().port;
  await new Promise(resolve => reservation.close(resolve));
  await symlink(fileURLToPath(new URL('../', import.meta.url)), join(directory, 'current'), 'dir');
  const child = spawn(process.execPath, [join(directory, 'current/server.js')], {
    env: { PATH: process.env.PATH, READER_ORIGIN: 'https://test.hs-manacost.ru',
      READER_ISSUER: bridge ? 'https://hearthpulse.net/identity' : 'https://identity.invalid/identity',
      READER_DEPLOYMENT: bridge ? 'staging' : 'test', READER_ALLOW_PRODUCTION_IDENTITY_FOR_STAGING: bridge ? '1' : '0',
      READER_CLIENT_ID: bridge ? 'manacost-reader-staging' : 'entrypoint-test', READER_CLIENT_SECRET: randomBytes(32).toString('base64url'),
      READER_DATABASE: join(directory, 'reader.sqlite'), READER_PORT: String(port),
      READER_ENCRYPTION_KEY: randomBytes(32).toString('base64url'), READER_CSRF_KEY: randomBytes(32).toString('base64url') },
    stdio: 'ignore',
  });
  const closed = once(child, 'close');
  try {
    let status;
    for (let attempt = 0; attempt < 100 && status === undefined && child.exitCode === null; attempt++) {
      status = await new Promise(resolve => {
        const req = request({ hostname: '127.0.0.1', port, path: '/reader-api/v1/me', headers: { Host: 'test.hs-manacost.ru' }, timeout: 250 }, response => {
          response.resume(); resolve(response.statusCode);
        });
        req.on('error', () => resolve(undefined));
        req.on('timeout', () => req.destroy());
        req.end();
      });
      if (status === undefined) await delay(25);
    }
    assert.equal(status, 401, 'service launched through current must stay alive and serve anonymous profile');
    child.kill('SIGTERM');
    const [code] = await closed;
    assert.equal(code, 0);
  } finally {
    if (child.exitCode === null) child.kill('SIGKILL');
    await closed;
    await rm(directory, { recursive: true, force: true });
  }
});
