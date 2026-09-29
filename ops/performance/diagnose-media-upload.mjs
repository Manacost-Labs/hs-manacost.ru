#!/usr/bin/env node

import { chromium } from '@playwright/test';
import { createHash, randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { performance } from 'node:perf_hooks';
import { crc32, deflateSync } from 'node:zlib';
import { parseAsyncUpload } from '../../tests/integration/media-upload-response.mjs';
import { finishAdminLogin } from './browser-admin-login.mjs';

// This diagnostic creates and deletes media. Never accept another host or scheme.
if (!/^https:\/\/test\.hs-manacost\.ru\/?$/.test(process.env.WP_TEST_BASE_URL ?? '')) {
  throw new Error('Only HTTPS staging is allowed');
}
const baseURL = 'https://test.hs-manacost.ru';
const username = process.env.WP_TEST_ADMIN_USER;
const password = process.env.WP_TEST_ADMIN_PASSWORD;
const httpUsername = process.env.STAGING_HTTP_USER;
const httpPassword = process.env.STAGING_HTTP_PASSWORD;
if (!username || !password || !httpUsername || !httpPassword) {
  throw new Error('All four staging credentials are required');
}

function pngChunk(type, data) {
  const chunk = Buffer.alloc(data.length + 12);
  chunk.writeUInt32BE(data.length);
  chunk.write(type, 4, 4, 'ascii');
  data.copy(chunk, 8);
  chunk.writeUInt32BE(crc32(chunk.subarray(4, -4)), chunk.length - 4);
  return chunk;
}

// Controlled RGB source below WordPress's scaling threshold: the source must survive intact.
const width = 2048;
const height = 1024;
const header = Buffer.alloc(13);
header.writeUInt32BE(width);
header.writeUInt32BE(height, 4);
header[8] = 8;
header[9] = 2;
const pixels = Buffer.alloc((width * 3 + 1) * height, 96);
for (let row = 0; row < height; row++) pixels[row * (width * 3 + 1)] = 0;
const fixture = Buffer.concat([
  Buffer.from('89504e470d0a1a0a', 'hex'),
  pngChunk('IHDR', header),
  pngChunk('IDAT', deflateSync(pixels)),
  pngChunk('IEND', Buffer.alloc(0)),
]);
const hash = buffer => createHash('sha256').update(buffer).digest('hex');
const sourceHash = hash(fixture);
const prefix = `hs-media-probe-ci-${randomUUID()}`;
const outputDirectory = path.resolve(process.argv[2] ?? '.artifacts/admin-performance/reports');
const report = {
  schema_version: 1, environment: 'staging', authenticated_role: 'administrator',
  fixture_prefix: prefix, fixture_width: width, fixture_height: height,
  fixture_bytes: fixture.length, fixture_sha256: sourceHash,
  samples: [], fixtures_cleaned: false, success: false,
};
const ownedIds = new Set();
let browser;
let context;
let page;
let nonce;
let authorId;
let stage = 'browser-start';
let uploadPending = false;

class DiagnosticFailure extends Error {}

function check(condition, message) {
  if (!condition) throw new DiagnosticFailure(message);
}

async function rest(route, method = 'GET') {
  return page.evaluate(async ({ route, method, nonce }) => {
    const response = await fetch(`/wp-json/wp/v2/${route}`, {
      method, headers: { 'X-WP-Nonce': nonce }, signal: AbortSignal.timeout(30_000),
    });
    let data = null;
    try { data = await response.json(); } catch { /* Preserve status without logging a response body. */ }
    return { status: response.status, data };
  }, { route, method, nonce });
}

async function readImage(url) {
  const parsed = new URL(url);
  check(parsed.protocol === 'https:' && !parsed.username && !parsed.password, 'Unsafe media URL');
  if (parsed.origin === baseURL) {
    // Use the browser's Basic Auth challenge state for protected staging assets.
    const image = await page.evaluate(async url => {
      const response = await fetch(url, { signal: AbortSignal.timeout(30_000) });
      return {
        status: response.status, type: response.headers.get('content-type'),
        bytes: Array.from(new Uint8Array(await response.arrayBuffer())),
      };
    }, url);
    check(image.status === 200, `Image HTTP response failed (${image.status})`);
    check(/^image\//i.test(image.type ?? ''), 'Image MIME check failed');
    return Buffer.from(image.bytes);
  }
  const response = await context.request.get(url, { timeout: 30_000, maxRedirects: 3 });
  check(response.status() === 200, `Image HTTP response failed (${response.status()})`);
  check(/^image\//i.test(response.headers()['content-type'] ?? ''), 'Image MIME check failed');
  const buffer = await response.body();
  await response.dispose();
  return buffer;
}

try {
  browser = await chromium.launch({
    headless: true,
    ...(process.env.PLAYWRIGHT_EXECUTABLE_PATH
      ? { executablePath: process.env.PLAYWRIGHT_EXECUTABLE_PATH } : {}),
    ...(process.env.PLAYWRIGHT_HOST_RESOLVER_RULES
      ? { args: [`--host-resolver-rules=${process.env.PLAYWRIGHT_HOST_RESOLVER_RULES}`] } : {}),
  });
  context = await browser.newContext({
    viewport: { width: 1440, height: 900 }, locale: 'ru-RU',
    httpCredentials: { username: httpUsername, password: httpPassword, origin: baseURL },
  });
  page = await context.newPage();
  page.setDefaultTimeout(30_000);
  stage = 'login';
  await page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
  await finishAdminLogin(page, baseURL);
  stage = 'rest-nonce';
  nonce = await page.evaluate(async () => {
    const response = await fetch('/wp-admin/admin-ajax.php?action=rest-nonce');
    return response.ok ? response.text() : '';
  });
  check(/^[a-z0-9]{10}$/i.test(nonce), 'REST nonce unavailable');
  stage = 'account-role';
  const user = await rest('users/me?context=edit');
  check(user.status === 200 && user.data.roles?.includes('administrator'), 'Administrator role required');
  authorId = user.data.id;

  for (let index = 0; index < 5; index++) {
    stage = `upload-${index + 1}`;
    await page.goto(`${baseURL}/wp-admin/media-new.php`, { waitUntil: 'domcontentloaded' });
    const started = performance.now();
    uploadPending = true;
    const [response] = await Promise.all([
      page.waitForResponse(result => new URL(result.url()).pathname === '/wp-admin/async-upload.php'
        && result.request().method() === 'POST', { timeout: 60_000 }),
      page.locator('input[type=file]').first().setInputFiles({
        name: `${prefix}-${index + 1}.png`, mimeType: 'image/png', buffer: fixture,
      }),
    ]);
    const parsed = parseAsyncUpload(await response.text());
    uploadPending = false;
    const sample = { upload_ms: Math.round(performance.now() - started), http_status: response.status() };
    report.samples.push(sample);
    check(response.status() === 200 && parsed.ok && Number.isSafeInteger(parsed.id)
      && parsed.id > 0 && !ownedIds.has(parsed.id), 'Upload response failed');

    stage = `verify-${index + 1}`;
    const attachment = await rest(`media/${parsed.id}?context=edit`);
    check(attachment.status === 200 && attachment.data.author === authorId
      && attachment.data.title?.raw === `${prefix}-${index + 1}`, 'Attachment ownership mismatch');
    ownedIds.add(parsed.id);
    sample.attachment_id = parsed.id;
    const media = attachment.data;
    check(new URL(media.source_url).pathname.includes(`${prefix}-${index + 1}.png`),
      'Original filename mismatch');
    check(media.media_details?.width === width && media.media_details?.height === height,
      'Original dimensions changed');
    const original = await readImage(media.source_url);
    check(hash(original) === sourceHash, 'Original image hash changed');
    sample.source_unchanged = true;
    const thumbnail = media.media_details.sizes?.thumbnail;
    check(thumbnail?.source_url && thumbnail.width > 0 && thumbnail.height > 0,
      'Thumbnail metadata missing');
    await readImage(thumbnail.source_url);
    const dimensions = await page.evaluate(async url => {
      const image = new Image();
      image.src = url;
      await Promise.race([
        image.decode(),
        new Promise((_, reject) => setTimeout(() => reject(new Error('Image decode timeout')), 30_000)),
      ]);
      return { width: image.naturalWidth, height: image.naturalHeight };
    }, thumbnail.source_url);
    check(dimensions.width === thumbnail.width && dimensions.height === thumbnail.height,
      'Thumbnail decode failed');
    sample.thumbnail_valid = true;
  }
  report.success = true;
} catch (error) {
  // Browser errors can embed form values or responses. Keep artifacts free of credentials.
  report.failed_stage = stage;
  if (error instanceof DiagnosticFailure) report.failed_check = error.message;
} finally {
  if (nonce && authorId && page) {
    try {
      // Recover an attachment even when its upload response was lost or invalid.
      const found = await rest(`media?context=edit&search=${prefix}&per_page=100&_fields=id,author,title`);
      check(found.status === 200 && Array.isArray(found.data), `Cleanup lookup failed (${found.status})`);
      for (const media of found.data) {
        if (media.author === authorId && media.title?.raw?.startsWith(`${prefix}-`)
          && Number.isSafeInteger(media.id) && media.id > 0) ownedIds.add(media.id);
      }
      for (const id of ownedIds) {
        const deleted = await rest(`media/${id}?force=true`, 'DELETE');
        check(deleted.status === 200 && deleted.data.deleted === true,
          `Attachment cleanup failed (${deleted.status})`);
        const absent = await rest(`media/${id}?context=edit`);
        check(absent.status === 404, `Attachment still exists (${absent.status})`);
      }
      // A timed-out request may still be processing; do not certify its cleanup.
      report.fixtures_cleaned = !uploadPending;
      report.cleaned_attachment_count = ownedIds.size;
    } catch (error) {
      report.cleanup_failed = true;
      if (error instanceof DiagnosticFailure) report.cleanup_failed_check = error.message;
    }
  }
  report.success = report.success && report.fixtures_cleaned;
  await context?.close().catch(() => {});
  await browser?.close().catch(() => {});
  await mkdir(outputDirectory, { recursive: true });
  await writeFile(path.join(outputDirectory, 'diagnostic-media-upload.json'), `${JSON.stringify(report, null, 2)}\n`);
}

if (!report.success) {
  console.error(`FAIL: staging media diagnostic (${report.failed_stage ?? 'cleanup'}); see sanitized report`);
  process.exitCode = 1;
} else {
  console.log(`PASS: five staging image uploads, original hashes, thumbnails and fixture cleanup (${report.samples.map(sample => sample.upload_ms).join(', ')} ms)`);
}
