import assert from 'node:assert/strict';
import { createHash, randomUUID } from 'node:crypto';
import { crc32, deflateSync } from 'node:zlib';
import { deleteUploadFixture } from './delete-upload-fixture.mjs';

const workloads = { single1: [1, 600, 555], single5: [1, 1250, 1333], batch: [20, 250, 399] };

function chunk(name, data) {
  const body = Buffer.concat([Buffer.from(name), data]);
  const size = Buffer.alloc(4);
  const crc = Buffer.alloc(4);
  size.writeUInt32BE(data.length);
  crc.writeUInt32BE(crc32(body));
  return Buffer.concat([size, body, crc]);
}

function file(name, width, height, seed) {
  const stride = width * 3 + 1;
  const pixels = Buffer.alloc(stride * height);
  for (let i = 0; i < pixels.length; i++) {
    if (i % stride === 0) continue;
    seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0;
    pixels[i] = seed >>> 24;
  }
  const header = Buffer.alloc(13);
  header.writeUInt32BE(width);
  header.writeUInt32BE(height, 4);
  header[8] = 8;
  header[9] = 2;
  return { name: name + '.png', mimeType: 'image/png', buffer: Buffer.concat([
    Buffer.from('89504e470d0a1a0a','hex'), chunk('IHDR', header), chunk('IDAT', deflateSync(pixels)), chunk('IEND', Buffer.alloc(0)),
  ]) };
}

async function verifyImage(admin, origin, attachment, fixture) {
  const original = new URL(attachment.source_url);
  assert.ok(original.pathname.endsWith('/' + fixture.name) && !original.username && !original.password);
  const expected = createHash('sha256').update(fixture.buffer).digest('hex');
  let actual;
  if (original.origin === origin) {
    actual = await admin.evaluate(async url => {
      const response = await fetch(url, {redirect: 'error', signal: AbortSignal.timeout(30000)});
      if (!response.ok || !response.headers.get('content-type')?.startsWith('image/')) throw Error('Original missing');
      const digest = await crypto.subtle.digest('SHA-256', await response.arrayBuffer());
      return Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
    }, original.href);
  } else {
    assert.equal(original.protocol, 'https:');
    const response = await fetch(original, {redirect: 'error', credentials: 'omit', signal: AbortSignal.timeout(30000)});
    assert.ok(response.ok && response.headers.get('content-type')?.startsWith('image/'));
    actual = createHash('sha256').update(new Uint8Array(await response.arrayBuffer())).digest('hex');
  }
  assert.equal(actual, expected);
  const thumb = attachment.media_details?.sizes?.thumbnail;
  assert.ok(thumb && (new URL(thumb.source_url).origin === origin || new URL(thumb.source_url).protocol === 'https:'));
  const size = await admin.evaluate(async url => {
    const image = new Image();
    image.src = url;
    let timer;
    try {
      await Promise.race([image.decode(), new Promise((_, reject) => {
        timer = setTimeout(() => reject(Error('Thumbnail timeout')), 30000);
      })]);
      return [image.naturalWidth, image.naturalHeight];
    } finally { clearTimeout(timer); }
  }, thumb.source_url);
  assert.deepEqual(size, [thumb.width, thumb.height]);
}

// The caller owns the synthetic article and removes it after the series.
export async function measureEditorMedia(page, postId, scenario) {
  const origin = new URL(page.url()).origin;
  assert.ok(origin === 'https://test.hs-manacost.ru' ||
    (process.env.HS_MEDIA_TEST_DISPOSABLE === '1' && /^http:\/\/127\.0\.0\.1:\d+$/.test(origin)));
  assert.ok(Number.isSafeInteger(postId) && postId > 0 && Object.hasOwn(workloads, scenario));
  const [count, width, height] = workloads[scenario];
  const prefix = 'hs-media-probe-' + randomUUID();
  const files = Array.from({length: count}, (_, i) => file(prefix + '-' + (i + 1), width, height, i + 1));
  const names = new Set(files.map(item => item.name.slice(0, -4)));
  const admin = await page.context().newPage();
  const cdp = await page.context().newCDPSession(page);
  let started = false;
  let completed = false;
  let owner;
  let progress = 'open';
  const network = [];
  const isUpload = request => new URL(request.url()).pathname === '/wp-admin/async-upload.php';
  const onFailure = request => {
    if (!isUpload(request) || network.length >= 40) return;
    const code = request.failure()?.errorText || '';
    network.push({failed: /^net::ERR_[A-Z0-9_]+$/.test(code) ? code : 'other'});
  };
  const onResponse = response => {
    if (isUpload(response.request()) && network.length < 40) network.push({status: response.status()});
  };
  page.on('requestfailed', onFailure);
  page.on('response', onResponse);
  const diagnose = error => Object.assign(error, {mediaDiagnostic: {scenario, progress, network}});
  let nonce;
  const rest = (route, method = 'GET') => admin.evaluate(async ({route, method, nonce}) => {
    const response = await fetch('/wp-json/wp/v2/' + route, { method, headers: {'X-WP-Nonce': nonce},
      signal: AbortSignal.timeout(method === 'DELETE' ? 60000 : 30000) });
    return { status: response.status, data: await response.json() };
  }, { route, method, nonce });

  try {
    await admin.goto(origin + '/wp-admin/admin-ajax.php?action=rest-nonce');
    nonce = (await admin.locator('body').innerText()).trim();
    assert.match(nonce, /^[a-z0-9]{10}$/i);
    const user = await rest('users/me');
    owner = user.data.id;
    assert.ok(user.status === 200 && Number.isSafeInteger(owner) && owner > 0);
    const post = await rest('posts/' + postId + '?context=edit&_fields=author,title,content');
    assert.ok(post.status === 200 && post.data.author === owner &&
      /^Admin performance probe \d{13}(?: [0-4])?$/.test(post.data.title.raw) &&
      post.data.content.raw.trim() === 'Synthetic staging performance content. '.repeat(1750).trim());

    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', {cacheDisabled: true});
    await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 100,
      downloadThroughput: 1250000, uploadThroughput: 1250000 });
    await page.goto(origin + '/wp-admin/post.php?action=edit&post=' + postId);
    await page.locator('#insert-media-button').click();
    const dialog = page.locator('.media-modal:visible');
    await dialog.getByRole('tab', {name: /Upload files|Загрузить файлы/i}).click();
    const [input] = await Promise.all([page.waitForEvent('filechooser'),
      dialog.getByRole('button', {name: /Select files|Выберите файлы|Выбрать файлы/i}).click()]);
    const start = performance.now();
    progress = 'upload';
    started = true;
    await input.setFiles(files);
    const readyTime = async target => {
      await page.waitForFunction(({prefix, target, count}) => {
        const dialog = document.querySelector('.media-modal');
        const thumbnails = [...dialog.querySelectorAll('.attachment img')].filter(img =>
          img.src.includes(prefix) && img.complete && img.naturalWidth && img.getBoundingClientRect().width);
        const insert = dialog.querySelector('.media-button-insert');
        const selectReady = dialog.querySelector('.media-button-hs-ready-selection');
        const usable = el => el && !el.disabled && el.getBoundingClientRect().width > 0;
        return new Set(thumbnails.map(img => img.currentSrc || img.src)).size >= target &&
          (usable(insert) || (target < count && usable(selectReady)));
      }, {prefix, target, count}, {timeout: 120000});
      return Math.round(performance.now() - start);
    };
    const firstSelectableMs = await readyTime(1);
    const allReadyMs = await readyTime(count);
    progress = 'selection';
    const ids = await page.evaluate(prefix => wp.media.frame.state().get('selection').filter(
      item => String(item.get('filename') || '').startsWith(prefix)).map(item => item.id), prefix);
    assert.equal(new Set(ids).size, count);
    completed = true;
    const media = await rest('media?context=edit&per_page=100&search=' + prefix +
      '&_fields=id,author,post,title,source_url,media_details');
    assert.equal(media.status, 200);
    assert.deepEqual(new Set(media.data.map(item => item.id)), new Set(ids));
    progress = 'verify';
    for (const item of media.data) {
      const fixture = files.find(test => test.name === item.title.raw + '.png');
      assert.ok(fixture && item.author === owner && item.post === postId);
      await verifyImage(admin, origin, item, fixture);
    }
    return { originals_and_thumbnails_verified: count, scenario, count, bytes: files.reduce((sum, item) => sum + item.buffer.length, 0),
      first_selectable_ms: firstSelectableMs, all_ready_ms: allReadyMs, unique_attachments: ids.length };
  } catch (error) {
    throw diagnose(error);
  } finally {
    try {
      if (started) {
        const lookup = await rest('media?context=edit&per_page=100&search=' + prefix + '&_fields=id,author,post,title');
        assert.equal(lookup.status, 200);
        const isOwned = item => item.author === owner && item.post === postId && names.has(item.title.raw);
        assert.ok(lookup.data.every(isOwned));
        const unconfirmed = [];
        for (const item of lookup.data) {
          try {
            await deleteUploadFixture(rest, item.id);
          } catch {
            unconfirmed.push(item.id);
          }
        }
        for (const id of unconfirmed) {
          const remaining = await rest('media/' + id + '?context=edit&_fields=id,author,post,title');
          if (remaining.status === 404) continue;
          assert.ok(remaining.status === 200 && remaining.data.id === id && isOwned(remaining.data));
          await deleteUploadFixture(rest, id);
        }
        assert.ok(completed && lookup.data.length === files.length, 'Unconfirmed cleanup of in-flight attachments');
      }
    } catch (error) {
      // Allow operators to find only this run's fixtures after a lost response.
      error.fixturePrefix = prefix;
      throw diagnose(error);
    } finally {
      page.off('requestfailed', onFailure);
      page.off('response', onResponse);
      await cdp.send('Network.emulateNetworkConditions', {offline: false, latency: 0, downloadThroughput: -1, uploadThroughput: -1});
      await cdp.send('Network.setCacheDisabled', {cacheDisabled: false});
      await cdp.detach();
      await admin.close();
    }
  }
}
