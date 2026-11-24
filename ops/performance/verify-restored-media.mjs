import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {chromium} from '@playwright/test';

const origin = 'https://test.hs-manacost.ru';
const fixtures = JSON.parse(await readFile(new URL('../../tests/fixtures/restored-media.json', import.meta.url), 'utf8'));
const username = process.env.STAGING_HTTP_USER;
const password = process.env.STAGING_HTTP_PASSWORD;
assert.ok(username && password, 'Staging HTTP credential missing');
const browser = await chromium.launch({ executablePath: process.env.PLAYWRIGHT_EXECUTABLE_PATH,
  args: ['--host-resolver-rules=MAP test.hs-manacost.ru 151.80.21.140'] });
try {
  const ctx = await browser.newContext({ httpCredentials: {origin, username, password} });
  const page = await ctx.newPage();
  for (const fixture of fixtures) {
    assert.match(fixture.path, /^\d{4}\/\d{2}\/[a-zA-Z0-9_.-]+$/);
    const url = origin + '/wp-content/uploads/' + fixture.path;
    const response = await page.goto(url, {waitUntil: 'load'});
    assert.equal(response.status(), 200, fixture.path);
    assert.equal(page.url(), url);
    const observed = await page.evaluate(async url => {
      const res = await fetch(url, {cache: 'no-store', redirect: 'error'});
      const bytes = await res.arrayBuffer();
      const hash = await crypto.subtle.digest('SHA-256', bytes);
      const decoded = await createImageBitmap(new Blob([bytes], {type: res.headers.get('content-type')}));
      return {sha256: Array.from(new Uint8Array(hash), n => n.toString(16).padStart(2,'0')).join(''),
        width: decoded.width, height: decoded.height, mime: res.headers.get('content-type')};
    }, url);
    const {path, ...expected} = fixture;
    assert.deepEqual(observed, expected, path);
    console.log('Verified ' + path + ' ' + observed.width + 'x' + observed.height);
  }
} finally { await browser.close(); }
