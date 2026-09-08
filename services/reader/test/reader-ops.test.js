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
