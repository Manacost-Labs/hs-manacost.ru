import { generateKeyPairSync, randomBytes } from 'node:crypto';
import { existsSync, lstatSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, parse, resolve } from 'node:path';

// Additive private configuration only: no existing account data or auth keys are read.
const providerFile = '/etc/hs-arena/browser-identity.env';
const readerFile = '/etc/manacost-reader-staging/production-identity.env';
const readerDatabase = '/var/lib/manacost-reader-staging/production-identity.sqlite';
const apply = process.argv.slice(2).join(' ') === '--apply';
if (process.argv.length > 2 && !apply) throw new Error('Use --apply or no arguments');
for (const path of [providerFile, readerFile, readerDatabase]) {
  for (let current = resolve(path); current !== parse(current).root; current = dirname(current)) {
    if (existsSync(current) && lstatSync(current).isSymbolicLink()) throw new Error('Symlink target refused');
  }
  if (existsSync(path)) throw new Error('Fresh bridge targets required; existing state is never overwritten');
}
if (!apply) {
  console.log('Fresh bridge targets checked. No keys, users or configuration changed.');
  process.exit(0);
}
if (process.getuid() !== 0) throw new Error('Root is required for private provisioning');
process.umask(0o077);
const random = () => randomBytes(32).toString('base64url');
const { privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048 });
const jwk = { ...privateKey.export({ format: 'jwk' }), kid: random(), use: 'sig', alg: 'RS256' };
const clientId = 'manacost-reader-staging';
const clientSecret = random();
const provider = {
  BROWSER_IDENTITY_ENABLED: '0', BROWSER_IDENTITY_DEPLOYMENT: 'production',
  BROWSER_IDENTITY_ISSUER: 'https://hearthpulse.net/identity', BROWSER_IDENTITY_TRUST_PROXY: '1',
  BROWSER_IDENTITY_ALLOW_STAGING_CLIENT: '1', BROWSER_IDENTITY_ENCRYPTION_KEY: random(),
  BROWSER_IDENTITY_COOKIE_KEYS: JSON.stringify([random()]),
  BROWSER_IDENTITY_JWKS: JSON.stringify({ keys: [jwk] }),
  BROWSER_IDENTITY_CLIENTS: JSON.stringify([{ id: clientId, secret: clientSecret,
    redirectUri: 'https://test.hs-manacost.ru/reader-auth/callback' }]),
};
const reader = {
  READER_ORIGIN: 'https://test.hs-manacost.ru', READER_ISSUER: provider.BROWSER_IDENTITY_ISSUER,
  READER_CLIENT_ID: clientId, READER_CLIENT_SECRET: clientSecret, READER_DEPLOYMENT: 'staging',
  READER_ALLOW_PRODUCTION_IDENTITY_FOR_STAGING: '1', READER_DATABASE: readerDatabase,
  READER_PORT: '18181', READER_ENCRYPTION_KEY: random(), READER_CSRF_KEY: random(),
};
for (const [path, environment] of [[providerFile, provider], [readerFile, reader]]) {
  mkdirSync(dirname(path), { recursive: true, mode: 0o700 });
  const content = Object.entries(environment).map(([key, value]) => {
    if (/[\r\n']/.test(value)) throw new Error('Unsafe environment value');
    return `${key}='${value}'`;
  }).join('\n') + '\n';
  writeFileSync(path, content, { mode: 0o600, flag: 'wx' });
}
console.log('Fresh private bridge keys provisioned; provider disabled, services unchanged.');
