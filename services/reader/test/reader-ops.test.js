import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';

test('staging origin reader location bounds uploads without weakening privacy/auth', () => {
  const source = readFileSync(new URL('../../../ops/reader/origin-staging.conf', import.meta.url), 'utf8');
  for (const contract of ['client_max_body_size 4m;', 'client_body_timeout 10s;',
    'proxy_request_buffering off;', 'proxy_pass http://127.0.0.1:18181;',
    'proxy_set_header Host test.hs-manacost.ru;', 'proxy_set_header Authorization "";',
    'proxy_cache off;', 'access_log off;', 'proxy_read_timeout 10s;']) assert.ok(source.includes(contract), contract);
  assert.equal(source.includes('auth_basic off'), false);
});

test('staging-only release helper is valid shell and requires explicit activation', () => {
  const file = new URL('../../../ops/reader/release-staging.sh', import.meta.url);
  execFileSync('bash', ['-n', file.pathname]);
  const source = readFileSync(file, 'utf8');
  assert.ok(source.includes("readonly app='/srv/manacost-reader-staging'"));
  assert.ok(source.includes('git -C "$root" archive "$sha:services/reader"'));
  assert.ok(source.includes('status --porcelain'));
  assert.ok(source.includes('rev-parse origin/main'));
  assert.ok(source.includes('Release parents must be root-owned'));
  assert.ok(source.includes('! -user root'));
  assert.ok(source.includes('\\( -type f -o -type d \\) -perm /022'));
  assert.ok(source.includes('sudo install -d -m 0750 -o root'));
  assert.equal(source.includes('sudo -u manacost-reader-staging npm'), false);
  assert.equal(/systemctl (?:restart|start|stop)\b/.test(source), false);
  assert.equal(source.includes('service.env'), false);
});

test('production origin reader location is private, bounded and loopback-only', () => {
  const source = readFileSync(new URL('../../../ops/reader/origin-production.conf', import.meta.url), 'utf8');
  for (const contract of ['client_max_body_size 4m;', 'client_body_timeout 10s;',
    'proxy_request_buffering off;', 'proxy_pass http://127.0.0.1:18183;',
    'proxy_set_header Host hs-manacost.ru;', 'proxy_set_header Authorization "";',
    'proxy_cache off;', 'proxy_buffering off;', 'access_log off;',
    'proxy_read_timeout 10s;']) assert.ok(source.includes(contract), contract);
  assert.equal(source.includes('auth_basic'), false);
});

test('loopback editorial endpoints expose only signed WordPress predicates', () => {
  const source = readFileSync(new URL('../../../ops/reader/internal-editorial.conf', import.meta.url), 'utf8');
  for (const contract of ['listen 127.0.0.1:18184;', 'listen 127.0.0.1:18185;',
    'location ~ ^/wp-json/manacost-reader/v1/(?:threads|favorites)$',
    'limit_except POST { deny all; }', 'fastcgi_param HTTP_AUTHORIZATION $http_authorization;',
    'fastcgi_param HTTP_HOST hs-manacost.ru;', 'fastcgi_param HTTP_HOST test.hs-manacost.ru;',
    'fastcgi_param HTTP_ORIGIN "";', 'fastcgi_param HTTP_COOKIE "";',
    'fastcgi_pass unix:/var/www/php-fpm/hs-manacost-php84.sock;', 'fastcgi_cache off;',
    'access_log off;', 'return 404;']) assert.ok(source.includes(contract), contract);
  assert.equal(source.includes('proxy_pass'), false);
  assert.equal(source.includes('auth_basic'), false);
});

test('production edge reader location uses a dedicated verified origin pool', () => {
  const location = readFileSync(new URL('../../../ops/reader/proxy-production-reader.conf', import.meta.url), 'utf8');
  for (const contract of ['client_max_body_size 4m;', 'proxy_request_buffering off;',
    'proxy_pass https://hs_manacost_reader_production_origin;', 'proxy_ssl_name hs-manacost.ru;',
    'proxy_ssl_verify on;', 'proxy_ssl_session_reuse off;', 'proxy_set_header Connection close;',
    'proxy_set_header Host hs-manacost.ru;', 'proxy_set_header Authorization "";',
    'proxy_cache off;', 'proxy_buffering off;', 'access_log off;']) assert.ok(location.includes(contract), contract);
  assert.equal(location.includes('$http_authorization'), false);
  const upstream = readFileSync(new URL('../../../ops/reader/proxy-production-upstream.conf', import.meta.url), 'utf8');
  assert.ok(upstream.includes('upstream hs_manacost_reader_production_origin'));
  assert.equal(upstream.includes('keepalive'), false);
});

test('production service template isolates state, secrets and the loopback listener', () => {
  const source = readFileSync(new URL('../../../ops/reader/manacost-reader-production.service', import.meta.url), 'utf8');
  for (const contract of ['User=manacost-reader', 'Group=manacost-reader',
    'WorkingDirectory=/srv/manacost-reader/current',
    'EnvironmentFile=/etc/manacost-reader/service.env', 'Environment=NODE_ENV=production',
    'Environment=READER_PORT=18183', 'Environment=READER_DATABASE=/var/lib/manacost-reader/reader.sqlite',
    'UMask=0077', 'ProtectSystem=strict', 'ProtectHome=true', 'NoNewPrivileges=true',
    'PrivateTmp=true', 'IPAddressDeny=any', 'IPAddressAllow=213.186.33.99']) assert.ok(source.includes(contract), contract);
  assert.equal(/(?:SECRET|TOKEN|PASSWORD|COOKIE_KEYS)=/.test(source), false);
});

test('production release helper prepares an immutable exact-SHA artifact without activation', () => {
  const file = new URL('../../../ops/reader/release-production.sh', import.meta.url);
  execFileSync('bash', ['-n', file.pathname]);
  const source = readFileSync(file, 'utf8');
  for (const contract of ["readonly app='/srv/manacost-reader'",
    'git -C "$root" archive "$sha:services/reader"', 'status --porcelain',
    'rev-parse origin/main', 'Release parents must be root-owned', '! -user root',
    '\\( -type f -o -type d \\) -perm /022',
    'sudo install -d -m 0750 -o root -g manacost-reader "$release"']) assert.ok(source.includes(contract), contract);
  assert.equal(/systemctl (?:restart|start|stop|enable)\b/.test(source), false);
  assert.equal(source.includes('current.new'), false);
  assert.equal(source.includes('previous.new'), false);
  assert.equal(source.includes('service.env'), false);
});
