import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFile, writeFile, readdir } from 'node:fs/promises';
import { join } from 'node:path';
import { chromium, expect } from '@playwright/test';
import { parseAsyncUpload } from './media-upload-response.mjs';

const root = new URL('../..', import.meta.url).pathname;
const runtime = process.env.HS_MEDIA_RUNTIME;
const baseURL = process.env.HS_MEDIA_BASE_URL;
assert.ok(runtime && baseURL && new URL(baseURL).hostname === '127.0.0.1', 'Isolated local WordPress only');
const label = process.env.HS_MEDIA_LABEL;
assert.ok(['before', 'after'].includes(label));
const settings = Object.fromEntries((await readFile(join(runtime, 'runtime.env'), 'utf8')).trim().split('\n').map(line => {
  const index = line.indexOf('=');
  return [line.slice(0, index), line.slice(index + 1)];
}));
const month = new Date().toISOString().slice(0, 7).replace('-', '/');
const source = await readFile(join(runtime, 'site/wp-content/uploads', month, 'media-pipeline-fixture.png'));
function crc32(buffer) {
  let crc = 0xffffffff;
  for (const byte of buffer) {
    crc ^= byte;
    for (let bit = 0; bit < 8; bit += 1) {
      crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function paddedPng(source, targetSize) {
  assert.ok(source.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10])), 'Expected PNG fixture');
  let offset = 8;
  let iendOffset = -1;
  while (offset + 12 <= source.length) {
    const length = source.readUInt32BE(offset);
    const type = source.subarray(offset + 4, offset + 8).toString('ascii');
    if (type === 'IEND') {
      iendOffset = offset;
      break;
    }
    offset += length + 12;
  }
  assert.ok(iendOffset > 0, 'PNG fixture has no IEND chunk');
  const payloadLength = targetSize - source.length - 12;
  assert.ok(payloadLength >= 3, 'PNG fixture is already too large');
  const payload = Buffer.alloc(payloadLength, 0x61);
  payload.write('HS', 0, 'ascii');
  payload[2] = 0; // Valid PNG tEXt keyword separator; payload is non-image metadata.
  const type = Buffer.from('tEXt');
  const crcInput = Buffer.concat([type, payload]);
  const chunk = Buffer.alloc(payloadLength + 12);
  chunk.writeUInt32BE(payloadLength, 0);
  type.copy(chunk, 4);
  payload.copy(chunk, 8);
  chunk.writeUInt32BE(crc32(crcInput), payloadLength + 8);
  return Buffer.concat([source.subarray(0, iendOffset), chunk, source.subarray(iendOffset)]);
}


const buffer = paddedPng(source, 50 * 1024 * 1024);
const sourceHash = createHash('sha256').update(buffer).digest('hex');
const uploadPath = join(root, '.artifacts/media-editor-' + label + '.png');
await writeFile(uploadPath, buffer);
const browser = await chromium.launch({headless: true});
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const rows = [];
const uploads = join(runtime, 'site/wp-content/uploads', month);
const initialFiles = new Set(await readdir(uploads));
const thumbnailFor = id => page.locator('.media-item').filter({
  has: page.locator('a[href*="post=' + id + '&"]'),
}).locator('img.pinkynail');
try {
  await page.goto(baseURL + '/wp-login.php');
  await page.getByLabel('Username or Email Address').fill(settings.WP_TEST_ADMIN_USER);
  await page.getByLabel('Password', { exact: true }).fill(settings.WP_TEST_ADMIN_PASSWORD);
  await Promise.all([
    page.waitForURL(/\/wp-admin\//),
    page.getByRole('button', { name: 'Log In', exact: true }).click(),
  ]);
  await page.goto(baseURL + '/wp-admin/media-new.php', {waitUntil: 'domcontentloaded'});
  for (let index = 0; index < 6; index++) {
    const completed = page.waitForResponse(response =>
      new URL(response.url()).pathname === '/wp-admin/async-upload.php'
      && response.request().method() === 'POST'
      && (response.request().headers()['content-type'] ?? '').startsWith('multipart/form-data'),
      { timeout: 90000 });
    const start = performance.now();
    const [response] = await Promise.all([
      completed,
      page.locator('input[type=file]').first().setInputFiles(uploadPath),
    ]);
    await response.finished();
    const elapsed = performance.now() - start;
    const parsed = parseAsyncUpload(await response.text());
    assert.equal(response.status(), 200);
    assert.equal(parsed.ok, true, 'Upload must return a valid attachment ID');
    await expect(thumbnailFor(parsed.id)).toBeVisible({timeout: 30000});
    const ready = performance.now() - start;
    const thumbnail = thumbnailFor(parsed.id);
    await expect.poll(() => thumbnail.evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
    rows.push({ id: parsed.id, warmup: index === 0, response_ms: Math.round(elapsed), preview_ms: Math.round(ready) });
  }
  // A responsive editor preview must remain usable without re-uploading.
  await page.setViewportSize({width: 390, height: 844});
  await expect(thumbnailFor(rows.at(-1).id)).toBeVisible();
  assert.deepEqual(errors, []);
  const files = (await readdir(uploads)).filter(file => !initialFiles.has(file) && new RegExp('^media-editor-' + label + '(?:-\\d+)?\\.png$').test(file));
  assert.equal(files.length, 6, 'Repeated filenames must not overwrite an earlier upload');
  for (const file of files) {
    assert.equal(createHash('sha256').update(await readFile(join(uploads, file))).digest('hex'), sourceHash);
  }
  const result = { label, source_bytes: buffer.length, dimensions: [3000, 3000], source_hash: sourceHash,
    original_unchanged: true, unique_filenames: true, mobile_preview: true, page_errors: errors, rows };
  await writeFile(join(root, '.artifacts/media-editor-' + label + '.json'), JSON.stringify(result, null, 2));
  console.log(JSON.stringify(result));
} finally {
  await context.close();
  await browser.close();
}
