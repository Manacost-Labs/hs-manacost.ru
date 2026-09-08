import { generateKeyPairSync, randomBytes } from 'node:crypto';
import { existsSync, lstatSync, mkdirSync, writeFileSync, chownSync, readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, parse, resolve } from 'node:path';

// Fixed staging targets only; this bootstrap never reads production configuration.
const hp = '/etc/hearthpulse-identity-staging/service.env';
const reader = '/etc/manacost-reader-staging/service.env';
const hpState = '/var/lib/hearthpulse-identity-staging';
const readerState = '/var/lib/manacost-reader-staging';
const apply = process.argv.slice(2).join(' ') === '--apply';
if (process.argv.length > 2 && !apply) throw new Error('Use --apply or no arguments');
function rejectLinks(path) {
  for (let current = resolve(path); current !== parse(current).root; current = dirname(current)) {
    if (existsSync(current) && lstatSync(current).isSymbolicLink()) throw new Error('Symlink target refused');
  }
}
for (const path of [hp, reader, hpState, readerState]) rejectLinks(path);
if ([hp, reader, hpState, readerState].some(existsSync)) throw new Error('Fresh staging targets required; existing keys/state are never overwritten');
if (!apply) {
  console.log('Dry run: fresh staging targets checked; no files or secrets created.');
  process.exit(0);
}
if (process.getuid() !== 0) throw new Error('Root is required for private staging provisioning');
const users = ['hearthpulse-identity-staging', 'manacost-reader-staging'];
const ids = users.map(user => ({
  uid: Number(execFileSync('id', ['-u', user], { encoding: 'utf8' }).trim()),
  gid: Number(execFileSync('id', ['-g', user], { encoding: 'utf8' }).trim()),
}));
process.umask(0o077);
function directory(path, identity) {
  mkdirSync(path, { recursive: true, mode: 0o700 });
  if (identity) chownSync(path, identity.uid, identity.gid);
}
const random = () => randomBytes(32).toString('base64url');
const { privateKey } = generateKeyPairSync('rsa', { modulusLength: 2048 });
const jwk = { ...privateKey.export({ format: 'jwk' }), kid: random(), use: 'sig', alg: 'RS256' };
const clientId = 'manacost-reader-staging';
const clientSecret = random();
const values = {
  NODE_ENV: 'production', HOST: '127.0.0.1', PORT: '18182',
  APP_ROOT_DIR: '/srv/hearthpulse-identity-staging/current', APP_URL: 'https://test.hearthpulse.net',
  SERVER_DATA_DIR: `${hpState}/data`, ECOSYSTEM_DIR: `${hpState}/ecosystem`,
  ECOSYSTEM_DB_FILE: `${hpState}/ecosystem/users.sqlite`,
  KOLODAHS_DB_ROOT: `${hpState}/cards`, KHA_VIP_PROFILES_FILE: `${hpState}/vip.json`,
  OLD_GUIDES_DB_FILE: `${hpState}/old-guides.sqlite`,
  ADMIN_USER_IDS: 'staging-no-admin', BACKGROUND_JOBS_ENABLED: '0',
  ARENA_DRAFT_REFRESH_ENABLED: '0', REDIS_ENABLED: '0',
  AUTH_FROM: 'noreply@hs-manacost.ru', LOCAL_SMTP_HOST: '127.0.0.1',
  LOCAL_SMTP_PORT: '18183', LOCAL_SMTP_TIMEOUT_MS: '5000',
  BROWSER_IDENTITY_ENABLED: '1', BROWSER_IDENTITY_DEPLOYMENT: 'staging',
  BROWSER_IDENTITY_ISSUER: 'https://test.hearthpulse.net/identity', BROWSER_IDENTITY_TRUST_PROXY: '1',
  BROWSER_IDENTITY_ENCRYPTION_KEY: random(), BROWSER_IDENTITY_COOKIE_KEYS: JSON.stringify([random()]),
  BROWSER_IDENTITY_JWKS: JSON.stringify({ keys: [jwk] }),
  BROWSER_IDENTITY_CLIENTS: JSON.stringify([{ id: clientId, secret: clientSecret,
    redirectUri: 'https://test.hs-manacost.ru/reader-auth/callback' }]),
};
const readerValues = {
  READER_ORIGIN: 'https://test.hs-manacost.ru', READER_ISSUER: values.BROWSER_IDENTITY_ISSUER,
  READER_CLIENT_ID: clientId, READER_CLIENT_SECRET: clientSecret, READER_DEPLOYMENT: 'staging',
  READER_DATABASE: `${readerState}/reader.sqlite`, READER_PORT: '18181',
  READER_ENCRYPTION_KEY: random(), READER_CSRF_KEY: random(),
};
directory(dirname(hp)); directory(dirname(reader));
writeFileSync(`${dirname(reader)}/identity-hosts`, readFileSync(new URL('./identity-hosts', import.meta.url)), { mode: 0o644, flag: 'wx' });
directory(hpState, ids[0]); directory(readerState, ids[1]);
for (const name of ['data', 'ecosystem', 'cards']) directory(`${hpState}/${name}`, ids[0]);
for (const [path, environment] of [[hp, values], [reader, readerValues]]) {
  const content = Object.entries(environment).map(([key, value]) => {
    if (/[\r\n']/.test(value)) throw new Error('Unsafe environment value');
    return `${key}='${value}'`;
  }).join('\n') + '\n';
  writeFileSync(path, content, { mode: 0o600, flag: 'wx' });
}
console.log('Fresh private staging configuration provisioned. No secret values logged; services remain stopped.');
