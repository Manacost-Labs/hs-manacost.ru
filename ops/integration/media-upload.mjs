import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { mkdtemp, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { chromium, expect } from '@playwright/test';
import { uploadFixture } from './media-upload-fixture.mjs';

// This harness deliberately has no production/staging URL or storage-state
// option. Never use it to work around a blocked production browser session.
const port = process.env.WP_TEST_PORT ?? '8888';
assert.match(port, /^\d+$/, 'Invalid integration port');
assert.ok(Number(port) >= 1024 && Number(port) <= 65535);
assert.ok(!process.env.WP_TEST_BASE_URL, 'Only disposable loopback WordPress is supported');
const baseURL = `http://127.0.0.1:${port}`;
const username = process.env.WP_TEST_ADMIN_USER;
const password = process.env.WP_TEST_ADMIN_PASSWORD;
assert.ok(username && password, 'Integration credentials are missing');
assert.equal(process.env.HS_MEDIA_TEST_DISPOSABLE, '1', 'Run through the isolated integration harness');

const hash = bytes => createHash('sha256').update(bytes).digest('hex');
const report = { environment: 'disposable-integration', uploads: [] };
const fixturePath = path.join(await mkdtemp(path.join(tmpdir(), 'hs-media-http-')), 'hs-http-upload.png');
const browser = await chromium.launch({ headless: true });
try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, serviceWorkers: 'block' });
  await context.route('**/*', route => new URL(route.request().url()).origin === baseURL
    ? route.continue() : route.abort());
  const page = await context.newPage();
  page.setDefaultTimeout(30_000);
  await page.goto(`${baseURL}/wp-login.php`);
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: 'Log In', exact: true }).click();
  await page.waitForURL('**/wp-admin/');
  const anonymous = await browser.newContext();
  for (const [client, status] of [[anonymous.request, 302], [context.request, 403]]) {
    const denied = await client.post(`${baseURL}/wp-admin/async-upload.php`, {
      form: { action: 'upload-attachment', _wpnonce: 'invalid-test-nonce' },
      maxRedirects: 0,
    });
    assert.equal(denied.status(), status, 'Unauthenticated/invalid-nonce upload was not rejected');
    if (status === 302) {
      const redirect = new URL(denied.headers().location, baseURL);
      assert.equal(redirect.origin, baseURL);
      assert.equal(redirect.pathname, '/wp-login.php');
    }
  }
  await anonymous.close();
  report.invalid_nonce_rejected = true;
  report.anonymous_upload_rejected = true;
  await page.goto(`${baseURL}/wp-admin/post-new.php`);
  await page.locator('#title').fill('Disposable HTTP media upload regression');
  report.max_upload_bytes = await page.evaluate(() => Number.parseInt(window._wpPluploadSettings.defaults.filters.max_file_size, 10));
  assert.ok(report.max_upload_bytes >= 50 * 1024 * 1024, `Uploader limit ${report.max_upload_bytes} is below 50MiB`);

  for (const padded of [true, false]) {
    const buffer = uploadFixture(padded);
    if (padded) assert.equal(buffer.length, 50 * 1024 * 1024);
    // Playwright's in-memory transport is capped at 50MiB: use its file path API.
    await writeFile(fixturePath, buffer);
    await page.locator('#insert-media-button').click();
    await page.getByRole('tab', { name: 'Upload files', exact: true }).click();
    const [chooser] = await Promise.all([
      page.waitForEvent('filechooser'),
      page.getByRole('button', { name: /select files/i }).click(),
    ]);
    const started = performance.now();
    const [response] = await Promise.all([
      page.waitForResponse(response =>
        new URL(response.url()).pathname === '/wp-admin/async-upload.php'
        && response.request().method() === 'POST', { timeout: 120_000 }),
      chooser.setFiles(fixturePath),
    ]);
    assert.equal(response.status(), 200, 'Native upload HTTP status');
    const payload = await response.json();
    assert.equal(payload.success, true, 'Native upload rejected the image');
    const attachment = payload.data;
    assert.ok(Number.isInteger(attachment.id) && attachment.id > 0);
    assert.equal(attachment.width, 3000);
    assert.equal(attachment.height, 3000);
    assert.equal(new URL(attachment.url).origin, baseURL);
    // Core's JS response lists selectable sizes, not every metadata size.
    // medium_large is checked directly in metadata by media-upload-check.php.
    for (const size of ['thumbnail', 'medium']) {
      assert.ok(attachment.sizes[size], `Missing immediate ${size}`);
    }
    const uploadResponseMs = Math.round(performance.now() - started);
    const insert = page.getByRole('button', { name: 'Insert into post', exact: true });
    await expect(insert).toBeEnabled();
    await page.locator('.attachment-display-settings select[data-setting="size"]').selectOption('medium');
    await insert.click();
    const inserted = page.frameLocator('#content_ifr').locator(`img.wp-image-${attachment.id}`);
    await expect(inserted).toBeVisible();
    await expect.poll(() => inserted.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    const previewReadyMs = Math.round(performance.now() - started);
    const original = await context.request.get(attachment.url, { maxRedirects: 0, timeout: 30_000 });
    assert.equal(original.status(), 200);
    assert.match(original.headers()['content-type'], /^image\/png/);
    assert.equal(hash(await original.body()), hash(buffer), 'Original changed during upload');
    report.uploads.push({ id: attachment.id, url: attachment.url, bytes: buffer.length,
      sha256: hash(buffer), width: attachment.width, height: attachment.height,
      upload_response_ms: uploadResponseMs, preview_ready_ms: previewReadyMs });
  }
  assert.notEqual(report.uploads[0].id, report.uploads[1].id);
  assert.notEqual(report.uploads[0].url, report.uploads[1].url, 'Duplicate filename collision');
  assert.notEqual(report.uploads[0].sha256, report.uploads[1].sha256);
  // Re-read the first original after the same-name second upload.
  const firstAgain = await context.request.get(report.uploads[0].url, { maxRedirects: 0 });
  assert.equal(firstAgain.status(), 200);
  assert.equal(hash(await firstAgain.body()), report.uploads[0].sha256);
  await Promise.all([
    page.waitForURL('**/wp-admin/post.php?**'),
    page.locator('#save-post').click(),
  ]);
  await page.reload();
  for (const attachment of report.uploads) {
    const inserted = page.frameLocator('#content_ifr').locator(`img.wp-image-${attachment.id}`);
    await expect(inserted).toBeVisible();
    await expect.poll(() => inserted.evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
  }
  report.draft_id = Number(new URL(page.url()).searchParams.get('post'));
  assert.ok(Number.isInteger(report.draft_id) && report.draft_id > 0);
  report.draft_reopened = true;
  // No cookies, nonce values, credentials, HAR or raw page content are saved.
  await writeFile('.artifacts/integration/media-upload-report.json', `${JSON.stringify(report, null, 2)}\n`);
  process.stdout.write('Native HTTP media: 50MiB, duplicate filename, preview, draft reopen: OK\n');
} finally {
  await browser.close();
}
