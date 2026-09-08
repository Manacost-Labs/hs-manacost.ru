#!/usr/bin/env node

import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, relative, resolve } from 'node:path';
import { createServer } from 'node:net';
import { spawn } from 'node:child_process';

const PROJECT_ROOT = resolve(import.meta.dirname, '..', '..', '..');
const TEMPLATE_PATH = join(PROJECT_ROOT, 'services/reader/editorial-staging.nginx.conf');
const FIXTURE_PREFIX = 'mc152-editorial-nginx-';
const TARGET_DOCROOT = '/var/www/koloda/data/www/test-hs-manacost-wordpress';
const TARGET_SOCKET = 'unix:/var/www/php-fpm/hs-manacost-php84.sock';
const FALLBACK_BINARIES = Object.freeze({
  nginx: ['/usr/sbin/nginx'],
  'php-fpm8.4': ['/opt/php84/sbin/php-fpm'],
  openssl: ['/usr/bin/openssl'],
});

function requireBinary(name) {
  const paths = FALLBACK_BINARIES[name] ?? [];
  const binary = paths.find((candidate) => existsSync(candidate));
  if (!binary) {
    throw new Error(`Blocked: required binary ${name} is unavailable at its allowlisted path; no installation was attempted.`);
  }
  return binary;
}

function assertOwnedFixture(path) {
  const resolved = resolve(path);
  const expectedPrefix = join(tmpdir(), FIXTURE_PREFIX);
  assert.ok(resolved.startsWith(expectedPrefix), `refusing to clean non-fixture path: ${resolved}`);
  assert.ok(!relative(tmpdir(), resolved).startsWith('..'), `fixture escaped ${tmpdir()}`);
}

function onceExit(child) {
  return new Promise((resolveExit) => child.once('exit', (code, signal) => resolveExit({ code, signal })));
}

function startProcess(binary, args, options = {}) {
  const child = spawn(binary, args, { stdio: ['pipe', 'pipe', 'pipe'], ...options });
  const stderr = [];
  child.stderr.on('data', (chunk) => stderr.push(chunk));
  return { child, exited: onceExit(child), stderr };
}

async function run(binary, args, input = '') {
  const { child, exited, stderr } = startProcess(binary, args);
  child.stdin.end(input);
  const stdout = [];
  child.stdout.on('data', (chunk) => stdout.push(chunk));
  const result = await exited;
  if (result.code !== 0) {
    throw new Error(`${binary} failed (${result.code ?? result.signal}): ${Buffer.concat(stderr).toString().trim()}`);
  }
  return Buffer.concat(stdout).toString().trim();
}

async function reserveLoopbackPort() {
  const server = createServer();
  await new Promise((resolveListen, rejectListen) => {
    server.once('error', rejectListen);
    server.listen({ host: '127.0.0.1', port: 0 }, resolveListen);
  });
  const { port } = server.address();
  await new Promise((resolveClose) => server.close(resolveClose));
  return port;
}

async function waitForServer(url) {
  const deadline = Date.now() + 3_000;
  let lastError;
  while (Date.now() < deadline) {
    try {
      const response = await fetch(url, { signal: AbortSignal.timeout(500) });
      await response.arrayBuffer();
      return;
    } catch (error) {
      lastError = error;
      await new Promise((resolveDelay) => setTimeout(resolveDelay, 50));
    }
  }
  throw new Error(`local nginx did not become ready: ${lastError?.message ?? 'unknown error'}`);
}

async function waitForSocket(socket, processInfo) {
  const deadline = Date.now() + 3_000;
  while (Date.now() < deadline) {
    if (existsSync(socket)) return;
    if (processInfo.child.exitCode !== null) {
      throw new Error(`temporary PHP-FPM exited before creating its socket: ${Buffer.concat(processInfo.stderr).toString().trim()}`);
    }
    await new Promise((resolveDelay) => setTimeout(resolveDelay, 50));
  }
  throw new Error(`temporary PHP-FPM did not create its socket: ${Buffer.concat(processInfo.stderr).toString().trim()}`);
}

async function stop(processInfo) {
  if (!processInfo || processInfo.child.exitCode !== null) return;
  processInfo.child.kill('SIGTERM');
  await Promise.race([
    processInfo.exited,
    new Promise((resolveDelay) => setTimeout(resolveDelay, 3_000)),
  ]);
  if (processInfo.child.exitCode === null) processInfo.child.kill('SIGKILL');
}

async function request(url, { authorization, method = 'POST', body } = {}) {
  const headers = authorization ? { authorization } : {};
  return fetch(url, { method, headers, body, signal: AbortSignal.timeout(3_000) });
}

async function main() {
  const nginx = requireBinary('nginx');
  const phpFpm = requireBinary('php-fpm8.4');
  const openssl = requireBinary('openssl');
  const phpVersion = await run(phpFpm, ['-v']);
  assert.match(phpVersion, /PHP 8\.4\./, 'ephemeral harness requires PHP-FPM 8.4');
  const fixtureDir = await mkdtemp(join(tmpdir(), FIXTURE_PREFIX));
  assertOwnedFixture(fixtureDir);

  let nginxProcess;
  let phpProcess;
  try {
    const docroot = join(fixtureDir, 'wordpress');
    const socket = join(fixtureDir, 'php-fpm.sock');
    const port = await reserveLoopbackPort();
    const username = 'fixture-reader';
    const password = randomBytes(24).toString('hex');
    const secondUsername = 'fixture-editor';
    const secondPassword = randomBytes(24).toString('hex');
    const authorization = `Basic ${Buffer.from(`${username}:${password}`).toString('base64')}`;
    const secondAuthorization = `Basic ${Buffer.from(`${secondUsername}:${secondPassword}`).toString('base64')}`;
    const passwordHash = await run(openssl, ['passwd', '-apr1', '-stdin'], `${password}\n`);
    const secondPasswordHash = await run(openssl, ['passwd', '-apr1', '-stdin'], `${secondPassword}\n`);
    const template = await readFile(TEMPLATE_PATH, 'utf8');
    const location = template
      .replaceAll(TARGET_DOCROOT, docroot)
      .replaceAll(TARGET_SOCKET, `unix:${socket}`);

    assert.equal(location.includes(TARGET_DOCROOT), false, 'test must replace only the target docroot');
    assert.equal(location.includes(TARGET_SOCKET), false, 'test must replace only the target socket');
    await mkdir(docroot);
    await writeFile(join(docroot, 'index.php'), "<?php\n$has = static fn ($key) => array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '';\nheader('Content-Type: application/json');\necho json_encode(['authorization' => $has('HTTP_AUTHORIZATION'), 'user' => $has('PHP_AUTH_USER'), 'password' => $has('PHP_AUTH_PW')]);\n");
    await writeFile(join(fixtureDir, 'credentials.htpasswd'), `${username}:${passwordHash}\n${secondUsername}:${secondPasswordHash}\n`);
    await writeFile(join(fixtureDir, 'php-fpm.conf'), `[global]\ndaemonize = no\nerror_log = ${join(fixtureDir, 'php-fpm.error.log')}\n\n[www]\nlisten = ${socket}\npm = static\npm.max_children = 1\nclear_env = yes\ncatch_workers_output = yes\n`);
    await writeFile(join(fixtureDir, 'nginx.conf'), `worker_processes 1;\npid ${join(fixtureDir, 'nginx.pid')};\nerror_log ${join(fixtureDir, 'nginx.error.log')} notice;\nevents { worker_connections 16; }\nhttp {\n    access_log off;\n    server {\n        listen 127.0.0.1:${port};\n        server_name localhost;\n        root ${docroot};\n        auth_basic \"fixture staging\";\n        auth_basic_user_file ${join(fixtureDir, 'credentials.htpasswd')};\n        ${location}\n    }\n}\n`);

    await run(nginx, ['-t', '-p', fixtureDir, '-c', join(fixtureDir, 'nginx.conf')]);
    phpProcess = startProcess(phpFpm, ['--nodaemonize', '--fpm-config', join(fixtureDir, 'php-fpm.conf')]);
    await waitForSocket(socket, phpProcess);
    nginxProcess = startProcess(nginx, ['-p', fixtureDir, '-c', join(fixtureDir, 'nginx.conf'), '-g', 'daemon off;']);

    const endpoint = `http://127.0.0.1:${port}/wp-json/manacost-reader/v1/threads`;
    await waitForServer(endpoint);
    assert.equal((await request(endpoint)).status, 401, 'missing Basic auth must remain rejected by outer server auth');
    assert.equal((await request(endpoint, { authorization: 'Basic d3Jvbmc6d3Jvbmc=' })).status, 401, 'wrong Basic auth must be rejected');
    const valid = await request(endpoint, { authorization, body: '{}' });
    assert.equal(valid.status, 200, 'authenticated POST must reach the PHP front controller');
    assert.deepEqual(await valid.json(), { authorization: false, user: false, password: false }, 'PHP must receive no Basic credential variables');
    const secondValid = await request(endpoint, { authorization: secondAuthorization, body: '{}' });
    assert.equal(secondValid.status, 200, 'any valid inherited staging credential must reach the PHP front controller');
    assert.deepEqual(await secondValid.json(), { authorization: false, user: false, password: false }, 'the second valid staging credential must also be cleared before PHP');
    assert.equal((await request(endpoint, { authorization, method: 'GET' })).status, 403, 'GET must be denied');
    assert.equal((await request(`${endpoint}?rest_route=/wp/v2/users`, { authorization })).status, 400, 'query routing override must be rejected');
    assert.equal((await request(endpoint, { authorization, body: Buffer.alloc(1025) })).status, 413, 'oversize request must be rejected');
    process.stdout.write('editorial nginx boundary: PASS\n');
  } finally {
    await stop(nginxProcess);
    await stop(phpProcess);
    assertOwnedFixture(fixtureDir);
    await rm(fixtureDir, { recursive: true, force: true });
  }
}

await main();
