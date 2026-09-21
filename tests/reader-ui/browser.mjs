import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Self-contained anonymous shell and synthetic API only; never use a live site.
const root = fileURLToPath(new URL('../../', import.meta.url));
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const renderShell = enabled => execFileSync('php', ['-r',
  "define('ABSPATH','/fixture/'); function hs_reader_comments_enabled(){return $GLOBALS['argv'][2] === '1';} function hs_manacost_reader_is_account_request(){return true;} function hs_manacost_reader_default_avatar_url(){return '/wp-content/mu-plugins/hs-manacost-reader/default-avatar.webp?ver=a1b2c3d4e5f6';} function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } function esc_html__($s,$domain='') { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); } require $argv[1]; echo hs_manacost_reader_account_shell();",
  `${plugin}account.php`, enabled ? '1' : '0'], { encoding: 'utf8' });
let shell = renderShell(true);
let sharedBootstrap = true;
let profileEditorDelay = 0;
let parserDelay = 0;
let footerDelay = 0;
const earlyBootstrap = execFileSync('php', ['-r', String.raw`
define('ABSPATH','/fixture/');
function hs_manacost_reader_page(){return (object) array('ID'=>22);}
function is_page($id){return true;}
function hs_reader_public_profile_request(){return false;}
function wp_print_inline_script_tag($code,$attributes){echo '<script id="'.$attributes['id'].'">'.$code.'</script>';}
require $argv[1]; hs_manacost_reader_early_bootstrap();`, `${plugin}assets.php`], {encoding:'utf8'});
let communityIdentity = { canModerateComments: false, paidSubscriber: false, commentingBlocked: false };
const assets = new Map([
  ['/ui.css', ['text/css', readFileSync(`${plugin}ui.css`)]],
  ['/reader.css', ['text/css', readFileSync(`${plugin}reader.css`)]],
  ['/tailwind.css', ['text/css', readFileSync(`${plugin}tailwind.css`)]],
  ['/profile-editor.js', ['text/javascript', readFileSync(`${plugin}profile-editor.js`)]],
  ['/bootstrap.js', ['text/javascript', readFileSync(`${plugin}bootstrap.js`)]],
  ['/account-ready.js', ['text/javascript', 'window.accountScriptReady = performance.now();']],
  ['/parser-block.js', ['text/javascript', 'window.parserScriptReady = performance.now();']],
  ['/footer-block.js', ['text/javascript', 'window.footerScriptReady = performance.now();']],
  ['/reader.js', ['text/javascript', readFileSync(`${plugin}reader.js`)]],
  ['/theme.css', ['text/css', readFileSync(`${root}wordpress/themes/Newspaper_new/style.css`)]],
  ['/theme-boxed.css', ['text/css', readFileSync(`${root}wordpress/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css`)]],
]);
let heldRequest = null;
const heldResponses = new Set();
const nativeDeadlineCalls = [];
const server = createServer((request, response) => {
	if (request.url === '/wp-content/mu-plugins/hs-manacost-reader/default-avatar.webp?ver=a1b2c3d4e5f6') {
		response.writeHead(200, { 'Content-Type': 'image/webp' }); response.end(readFileSync(`${plugin}default-avatar.webp`)); return;
	}
	if (request.url?.startsWith('/wp-content/mu-plugins/hs-manacost-reader/class-icons/')) {
		const name = request.url.split('/').at(-1);
		if (/^(deathknight|demonhunter|druid|hunter|mage|paladin|priest|rogue|shaman|warlock|warrior)\.png$/.test(name || '')) {
			response.writeHead(200, { 'Content-Type': 'image/png' }); response.end(readFileSync(`${plugin}class-icons/${name}`)); return;
		}
	}
  if (heldRequest?.path === request.url && heldRequest.method === request.method) {
    nativeDeadlineCalls.push({ path: request.url, started: Date.now() });
    request.resume();
    heldResponses.add(response);
    const safetyDeadline = setTimeout(() => response.destroy(), 20000);
    response.once('close', () => { clearTimeout(safetyDeadline); heldResponses.delete(response); });
    if (heldRequest.partialJson) {
      response.writeHead(200, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
      response.write('{"profile":'); // Headers succeed; JSON body never completes before the UI deadline.
    }
    return;
  }
	if (request.url === '/reader-api/v1/community/me') { response.writeHead(200, { 'Content-Type': 'application/json' }); response.end(JSON.stringify(communityIdentity)); return; }
  const asset = assets.get(request.url);
  if (asset) {
    const send = () => { response.writeHead(200, { 'Content-Type': asset[0] }); response.end(asset[1]); };
    if (request.url === '/profile-editor.js' && profileEditorDelay) setTimeout(send, profileEditorDelay);
    else if (request.url === '/parser-block.js' && parserDelay) setTimeout(send, parserDelay);
    else if (request.url === '/footer-block.js' && footerDelay) setTimeout(send, footerDelay);
    else send();
    return;
  }
  if (request.url !== '/') { response.writeHead(404); response.end(); return; }
  response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  // The account shell owns the only page title, just as the dedicated template does.
  response.end(`<!doctype html><html lang="ru"><head><meta name="viewport" content="width=device-width">${sharedBootstrap ? earlyBootstrap : ''}<script src="/parser-block.js"></script><link rel="stylesheet" href="/theme.css"><link rel="stylesheet" href="/theme-boxed.css"><link rel="stylesheet" href="/ui.css"><link rel="stylesheet" href="/reader.css"><link rel="stylesheet" href="/tailwind.css"><title>Local reader test</title>${sharedBootstrap ? '<script defer src="/bootstrap.js"></script>' : ''}<script defer src="/profile-editor.js"></script><script defer src="/account-ready.js"></script><script defer src="/reader.js"></script></head><body class="td-boxed-layout"><header class="td-container-wrap" data-theme-header-outer></header><main class="td-main-content-wrap td-container-wrap mc-reader-page"><div class="td-container"><div class="td-page-content">${shell}</div></div></main><footer class="td-container-wrap" data-theme-footer-outer></footer><script defer src="/footer-block.js"></script></body></html>`);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
const screenshotDir = process.env.READER_UI_SCREENSHOTS;
if (screenshotDir) mkdirSync(screenshotDir, { recursive: true });
let browser;
try {
  browser = await chromium.launch({ headless: true,
    ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
  const page = await browser.newPage();
  page.on('pageerror', error => console.error(`Browser page error: ${error.message}`));
  const profileId = '123e4567-e89b-42d3-a456-426614174000';
  const profileDto = overrides => ({ id: profileId, displayName: 'Читатель Манакоста', bio: 'Люблю вдумчивые колоды и длинные партии.', favoriteClass: 'mage', twitchUrl: null, youtubeUrl: null, version: 1, avatarUrl: null, ...overrides });
  const legacyProfileDto = overrides => {
    const legacy = profileDto(overrides);
    delete legacy.twitchUrl;
    delete legacy.youtubeUrl;
    return legacy;
  };
  const sessionDto = overrides => ({ user: { displayName: 'Читатель Манакоста' }, csrfToken: 'synthetic-only', profileUrl: 'https://hearthpulse.net/profile/synthetic', profile: profileDto(), ...overrides });
  let profileStatus = 401;
  let profile = {};
  let logoutStatus = 204;
  let logoutCalls = 0;
  let meCalls = 0;
  let legacyMeCalls = 0;
  let profileWriteStatus = 200;
  let profileWriteResponse = profileDto({ version: 2 });
  let avatarWriteStatus = 200;
  let avatarWriteResponse = profileDto({ version: 2, avatarUrl: '/reader-api/v1/profile/avatar?v=avatar2' });
  let avatarDelay = 0;
  const profileWrites = [];
  const avatarWrites = [];
	const publicationWrites = [];
	const communityErasures = [];
	const communityExportCalls = [];
  const favoriteCalls = [];
  let favorites = [{ id: '523e4567-e89b-42d3-a456-426614174000', postId: 17, title: 'Гайд по старту игры на Полях сражений', path: '/guides/battlegrounds/', createdAt: 1700000000000 }];
  let holdFavoriteRead = false;
  const releaseFavoriteReads = [];
  let publicationStatus = 200;
  const fulfillMe = async route => {
    meCalls += 1;
    if (new URL(route.request().url()).pathname.endsWith('/me')) legacyMeCalls += 1;
    return route.fulfill({ status: profileStatus, json: profile }).catch(() => {});
  };
  await page.route('**/reader-api/v1/me', fulfillMe);
  await page.route('**/reader-api/v1/bootstrap', fulfillMe);
  await page.route('**/reader-api/v1/profile', route => {
    const request = route.request();
    profileWrites.push({ headers: request.headers(), body: request.postDataJSON() });
    if (profileWriteStatus === 0) return route.abort('failed');
    return route.fulfill({ status: profileWriteStatus, json: profileWriteStatus === 200 ? { profile: profileWriteResponse } : { code: 'profile_conflict' } });
  });
  await page.route('**/reader-api/v1/profile/avatar*', async route => {
    const request = route.request();
    if (request.method() === 'GET') {
      return route.fulfill({ status: 200, contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nL8AAAAASUVORK5CYII=', 'base64') });
    }
    avatarWrites.push({ method: request.method(), headers: request.headers(), size: request.postDataBuffer()?.length || 0 });
    if (avatarDelay) await new Promise(resolve => setTimeout(resolve, avatarDelay));
    return route.fulfill({ status: avatarWriteStatus, json: avatarWriteStatus === 200 ? { profile: avatarWriteResponse } : { code: 'invalid_avatar' } });
  });
	await page.route('**/reader-api/v1/community/export*', route => {
		communityExportCalls.push(new URL(route.request().url()).searchParams.get('cursor'));
		return route.fulfill({ status: 200, json: { items: [{ id: 'synthetic-comment' }], reactions: [], nextCursor: null } });
	});
	await page.route('**/reader-api/v1/community/profile', route => {
		if (route.request().method() === 'DELETE') {
			communityErasures.push({ headers: route.request().headers(), body: route.request().postDataJSON() });
			return route.fulfill({ status: 200, json: { erased: true } });
		}
		publicationWrites.push({ headers: route.request().headers(), body: route.request().postDataJSON() });
		return route.fulfill({ status: publicationStatus, json: publicationStatus === 200 ? { profile: profileWriteResponse } : { error: 'public_profile_not_found' } });
	});
  await page.route('**/reader-api/v1/favorites**', async route => {
    const request = route.request();
    const url = new URL(request.url());
    favoriteCalls.push({ method: request.method(), path: url.pathname, headers: request.headers() });
    if (request.method() === 'GET' && url.pathname === '/reader-api/v1/favorites') {
			if (holdFavoriteRead) await new Promise(resolve => releaseFavoriteReads.push(resolve));
      return route.fulfill({ status: 200, json: { items: favorites, nextCursor: null } });
    }
    if (request.method() === 'DELETE' && url.pathname === '/reader-api/v1/favorites/17') {
      favorites = favorites.filter(item => item.postId !== 17);
      return route.fulfill({ status: 200, json: { postId: 17, saved: false } });
    }
    return route.fulfill({ status: 404, json: { error: 'not_found' } });
  });
  await page.route('**/reader-auth/logout', async route => {
    logoutCalls += 1;
    return route.fulfill({ status: logoutStatus }).catch(() => {});
  });
  const status = page.locator('[data-reader-status]');
  const retry = page.locator('[data-reader-actions]').getByRole('button', { name: 'Повторить', exact: true });
  const ready = async () => {
    await page.goto(origin, { waitUntil: 'domcontentloaded' });
    await page.waitForFunction(() => document.querySelector('[data-reader-status]').textContent !== 'Проверяем вход…');
  };
  const capture = async name => {
    if (screenshotDir) await page.screenshot({ path: `${screenshotDir}/${name}.png`, fullPage: true });
  };
  const layout = () => page.evaluate(() => ({
    overflow: document.documentElement.scrollWidth - innerWidth,
    title: parseFloat(getComputedStyle(document.querySelector('.mc-reader__eyebrow')).fontSize),
    viewport: innerWidth,
    sections: [...document.querySelectorAll('.mc-reader__shell > [aria-labelledby]')].filter(element => element.getClientRects().length).map(element => {
      const rect = element.getBoundingClientRect();
      return { bottom: rect.bottom, height: rect.height, left: rect.left, right: rect.right, top: rect.top, width: rect.width };
    }),
  }));
  const assertFits = async () => {
    const result = await layout();
    assert.equal(result.overflow, 0, 'the account page must not horizontally scroll');
    assert.ok(result.title >= 12, 'the cabinet label must remain readable');
    assert.ok(result.sections.every(section => section.left >= 0 && section.right <= result.viewport && section.width > 0), `each account section must fit the viewport: ${JSON.stringify(result)}`);
    return result;
  };
  const assertThemeOuterAlignment = async width => {
    const outerAlignment = await page.evaluate(() => {
      const rect = selector => {
        const { left, right, width } = document.querySelector(selector).getBoundingClientRect();
        return { left, right, width };
      };
      return {
        footer: rect('[data-theme-footer-outer]'),
        header: rect('[data-theme-header-outer]'),
        main: rect('.mc-reader-page'),
        inner: rect('.mc-reader-page > .td-container'),
        mainBackground: getComputedStyle(document.querySelector('.mc-reader-page')).backgroundColor,
      };
    });
    assert.deepEqual(outerAlignment.main, outerAlignment.header, `reader main must share the boxed outer header alignment at ${width}px`);
    assert.deepEqual(outerAlignment.main, outerAlignment.footer, `reader main must share the boxed outer footer alignment at ${width}px`);
    if (width >= 1180) {
      assert.ok(outerAlignment.main.width > outerAlignment.inner.width, 'theme outer wrapper must remain distinct from the inner content container');
    }
    assert.equal(outerAlignment.mainBackground, 'rgb(243, 245, 246)', 'the cabinet must use the cool paper background from the Reader design contract');
  };

  parserDelay = 900; footerDelay = 1400;
  const earlyCalls = meCalls;
  await ready();
  await page.waitForLoadState('load');
  assert.equal(meCalls - earlyCalls, 1, 'inline and deferred bootstrap must reuse the same request');
  assert.equal(await page.evaluate(() => {
    const entries = performance.getEntriesByType('resource');
    return entries.find(x => x.name.endsWith('/reader-api/v1/bootstrap')).startTime <
      entries.find(x => x.name.endsWith('/parser-block.js')).responseEnd;
  }), true, 'identity must start before parser-blocking theme JavaScript completes');
  assert.equal(await page.evaluate(() => window.accountScriptReady < performance.getEntriesByType('resource').find(x => x.name.endsWith('/footer-block.js')).responseEnd), true,
    'account controller must not wait for unrelated deferred footer scripts');
  parserDelay = 0; footerDelay = 0;

  for (const width of [320, 390, 560, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    profileEditorDelay = 700;
    const callsBeforeNavigation = meCalls;
    await ready();
    await page.waitForLoadState('load');
    assert.equal(meCalls - callsBeforeNavigation, 1, 'initial pageshow must not restart the initial identity request');
    assert.equal(legacyMeCalls, 0, 'shared account must not issue a second /me request');
    assert.equal(await page.evaluate(() => performance.getEntriesByType('resource').find(entry => entry.name.endsWith('/reader-api/v1/bootstrap')).startTime <= window.accountScriptReady), true, 'bootstrap must begin before the account controller loads');
    assert.equal(await page.evaluate(() => {
      const resources = performance.getEntriesByType('resource');
      return resources.find(entry => entry.name.endsWith('/reader-api/v1/bootstrap')).startTime <
        resources.find(entry => entry.name.endsWith('/profile-editor.js')).responseEnd;
    }), true, 'slow profile editor must not hold up identity validation');
    profileEditorDelay = 0;
    await assertFits();
    await assertThemeOuterAlignment(width);
    if ([390, 1440].includes(width)) await capture(`guest-${width}`);
  }
  await capture('guest');
  assert.equal(await page.getByRole('heading', { name: 'Личный кабинет', level: 1 }).count(), 1);
  assert.equal(await page.getByText('Профиль Манакоста', { exact: true }).count(), 1);
  assert.equal(await page.getByRole('heading', { name: 'Сохранённые статьи', level: 2 }).count(), 0, 'unavailable future navigation must not be rendered');
  assert.equal(await page.getByText('Закладки пока недоступны.').count(), 0, 'unavailable bookmark copy must not consume account space');
  assert.equal(await status.getAttribute('role'), 'status');
  assert.equal(await status.getAttribute('aria-live'), 'polite');
  const guestLogin = page.getByRole('link', { name: 'Войти через HearthPulse', exact: true });
  for (let step = 0; step < 6 && !await guestLogin.evaluate(element => document.activeElement === element); step++) await page.keyboard.press('Tab');
  assert.equal(await guestLogin.evaluate(element => document.activeElement === element), true);
  assert.ok(await guestLogin.evaluate(element => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return rect.height >= 44 && style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
  }), 'keyboard focus must be visible on a 44px login link');

  const luminance = rgb => rgb.match(/[\d.]+/g).slice(0, 3).map(Number)
    .map(value => value / 255).map(value => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4)
    .reduce((sum, value, index) => sum + value * [0.2126, 0.7152, 0.0722][index], 0);
  for (const selector of ['.mc-reader__intro', '.mc-reader__status', '.mc-reader__note', '.mc-reader__availability', '.mc-reader__empty', '.mc-reader__button--primary']) {
    const visible = page.locator(`${selector}:visible`);
    if (!await visible.count()) continue;
    const colors = await visible.first().evaluate(element => {
      let surface = element;
      while (getComputedStyle(surface).backgroundColor === 'rgba(0, 0, 0, 0)' && surface.parentElement) surface = surface.parentElement;
      return { text: getComputedStyle(element).color, background: getComputedStyle(surface).backgroundColor };
    });
    const values = [luminance(colors.text), luminance(colors.background)].sort((a, b) => b - a);
    assert.ok((values[0] + 0.05) / (values[1] + 0.05) >= 4.5, `${selector} must meet normal-text contrast`);
  }

  profileStatus = 200;
  profile = sessionDto({
    profile: profileDto({
      twitchUrl: 'https://twitch.tv/Mana_Cost',
      youtubeUrl: 'https://youtube.com/@Manacost',
    }),
  });
  communityIdentity = { canModerateComments: true, paidSubscriber: true, commentingBlocked: false };
	for (const width of [1440, 1024, 768, 560, 390, 320]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await page.locator('[data-reader-profile-overview]').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-reader-identity]').textContent(), 'Читатель Манакоста');
    await page.locator('[data-reader-paid]').waitFor({ state: 'visible' });
    await page.locator('[data-reader-administrator]').waitFor({ state: 'visible' });
    await page.locator('[data-reader-avatar-image]').waitFor({ state: 'visible' });
    assert.match(await page.locator('[data-reader-avatar-image]').getAttribute('src'), /default-avatar\.webp\?ver=a1b2c3d4e5f6$/);
    await assertFits();
    const sectionOrder = await page.evaluate(() => {
      const rect = selector => document.querySelector(selector).getBoundingClientRect();
      return { profile: rect('[data-reader-profile-overview]'), favorites: rect('[data-reader-favorites]') };
    });
    const stackedGap = sectionOrder.favorites.top - sectionOrder.profile.bottom;
    assert.ok(stackedGap >= (width <= 560 ? 24 : 32) && stackedGap <= (width <= 560 ? 28 : 36),
      `saved articles must follow the profile as a separate section at ${width}px: ${stackedGap}`);
    const geometry = await page.evaluate(() => {
      const rect = selector => { const { x, y, width, height, bottom, right } = document.querySelector(selector).getBoundingClientRect(); return { x, y, width, height, bottom, right }; };
      return { favorite: rect('.mc-reader__class-mark'), edit: rect('[data-reader-open-editor]') };
    });
    const { favorite, edit } = geometry;
    assert.ok(edit.y >= favorite.bottom + 12 || edit.x >= favorite.right + 12,
      `class and edit action need a deliberate gap at ${width}px: ${JSON.stringify(geometry)}`);
    assert.ok(favorite.height <= 48, 'favorite class must stay compact instead of becoming a large control tile');
    await capture(`authenticated-${width}`);
	}

	await page.setViewportSize({ width: 1024, height: 900 });
	await ready();
	await page.locator('[data-reader-community-data]').waitFor({ state: 'visible' });
	let communityDownload = null;
	page.once('download', download => { communityDownload = download; });
	await page.getByRole('button', { name: 'Скачать мои комментарии' }).click();
	await page.getByText('Выгрузка подготовлена.').waitFor();
	assert.ok(communityDownload, 'account privacy section creates the complete download');
	assert.deepEqual(communityExportCalls.at(-1), null);
	page.once('dialog', dialog => dialog.accept());
	await page.getByRole('button', { name: 'Удалить комментарии и публичный профиль' }).click();
	await page.getByText('Комментарии и публичный профиль удалены. Кабинет сохранён.').waitFor();
	assert.deepEqual(communityErasures.at(-1).body, { profileId, confirm: 'erase-community' });
	assert.equal(communityErasures.at(-1).headers['x-reader-csrf'], 'synthetic-only');

  const favoriteReadsBefore = favoriteCalls.filter(call => call.method === 'GET').length;
  holdFavoriteRead = true;
  await page.setViewportSize({ width: 1440, height: 900 });
  await ready();
  await page.locator('[data-reader-profile-overview]').waitFor({ state: 'visible' });
  await page.locator('[data-reader-favorites]').waitFor({ state: 'visible' });
  assert.equal(await page.getByRole('tab').count(), 0, 'saved articles are a normal section, not a tab panel');
  await page.locator('[data-reader-favorites-sentinel]').scrollIntoViewIfNeeded();
  await page.getByRole('heading', { name: 'Сохранённые статьи', level: 2 }).waitFor();
  await page.waitForTimeout(100);
  assert.ok(releaseFavoriteReads.length >= 1, 'at least one favorites read is deliberately delayed');
  assert.equal(await page.locator('[data-reader-profile-overview]').isVisible(), true, 'a delayed saved-articles response must never delay the profile');
  const meBeforeFavoriteRefresh = meCalls;
  await page.evaluate(() => window.dispatchEvent(new Event('focus')));
  await page.waitForTimeout(50);
  assert.ok(meCalls > meBeforeFavoriteRefresh, 'a session refresh supersedes the stale favorite read');
  holdFavoriteRead = false;
  for (const release of releaseFavoriteReads.splice(0)) release();
  await page.getByRole('link', { name: 'Гайд по старту игры на Полях сражений' }).waitFor();
  assert.ok(favoriteCalls.filter(call => call.method === 'GET').length >= favoriteReadsBefore + 2, 'the visible saved-articles section retries after a stale session refresh');
  assert.equal(favoriteCalls.at(-1).method, 'GET');
  await capture('favorites');
  await page.locator('[data-reader-favorites-list] [data-favorite-id] button').click();
  await page.getByText('Здесь появятся статьи, которые вы сохраните на сайте.').waitFor();
  assert.equal(favoriteCalls.at(-1).method, 'DELETE');
  assert.equal(favoriteCalls.at(-1).headers['x-reader-csrf'], 'synthetic-only');
  await page.locator('[data-reader-profile-overview]').waitFor({ state: 'visible' });
  shell = renderShell(false);
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  assert.equal(await page.locator('.mc-reader__publication').isVisible(), false, 'comments-off hosts do not advertise publication');
  assert.equal(await page.locator('[data-reader-publish-profile]').isDisabled(), true);
  assert.equal(await page.locator('[data-reader-save-profile]').isEnabled(), true, 'private editing remains available when comments are disabled');
  assert.match(await page.locator('[data-reader-social-help]').textContent(), /не публикует ссылки/);
  assert.equal(publicationWrites.length, 0);
  // Reproduce an older cached shell receiving the new script during deployment.
	shell = renderShell(true)
		.replace(/<section class="mc-reader__publication"[\s\S]*?<\/section>/u, '')
		.replace(/<section class="mc-reader__community-data"[\s\S]*?<\/section>/u, '');
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  assert.equal(await page.locator('.mc-reader__publication').count(), 0);
  assert.equal(await page.locator('[data-reader-save-profile]').isEnabled(), true, 'legacy cached markup still supports private editing');
  assert.equal(await page.locator('[data-reader-display-name]').inputValue(), 'Читатель Манакоста');
  shell = renderShell(true);
  await ready();
  const twitchMark = page.getByRole('link', { name: 'Открыть Twitch-канал', exact: true });
  const youtubeMark = page.getByRole('link', { name: 'Открыть YouTube-канал', exact: true });
  assert.equal(await twitchMark.isVisible(), true, 'a saved Twitch channel must be a compact link immediately after the name');
  assert.equal(await youtubeMark.isVisible(), true, 'a saved YouTube channel must be a compact link immediately after the name');
  assert.equal(await twitchMark.getAttribute('href'), 'https://www.twitch.tv/mana_cost');
  assert.equal(await youtubeMark.getAttribute('href'), 'https://www.youtube.com/@Manacost');
  assert.equal(await page.locator('[data-reader-socials]').count(), 0, 'the account overview must not duplicate social links as large profile chips');
  for (const width of [320, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertFits();
    for (const link of [twitchMark, youtubeMark]) {
      await page.keyboard.press('Tab');
      await link.focus();
      const mark = await link.evaluate(element => {
        const rect = element.getBoundingClientRect();
        const style = getComputedStyle(element);
        return {
          active: document.activeElement === element,
          focusVisible: element.matches(':focus-visible'),
          height: rect.height,
          parentClass: element.parentElement?.className || '',
          width: rect.width,
          outlineStyle: style.outlineStyle,
          outlineWidth: parseFloat(style.outlineWidth),
        };
      });
      assert.equal(mark.active, true, `author mark must be keyboard reachable at ${width}px`);
      assert.equal(mark.parentClass, 'mc-reader__identity-line', `author mark must remain beside the name at ${width}px`);
      assert.ok(mark.width >= 44 && mark.height >= 44, `author mark must keep a 44px touch target at ${width}px`);
      assert.ok(mark.width <= 48, `author mark must stay icon-sized beside the name at ${width}px`);
      assert.equal(mark.focusVisible, true, `author mark must expose keyboard focus at ${width}px`);
      assert.ok(mark.outlineStyle !== 'none' && mark.outlineWidth > 0, `author mark focus must be visible at ${width}px`);
    }
    const identityLines = await page.locator('[data-reader-identity]').evaluate(element => {
      const range = document.createRange();
      range.selectNodeContents(element);
      return new Set([...range.getClientRects()].map(rect => Math.round(rect.top))).size;
    });
    assert.ok(identityLines <= 2, `reader name must not collapse into orphaned characters at ${width}px`);
  }
  const profileHierarchy = await page.locator('[data-reader-profile-overview]').evaluate(profile => {
    const rect = element => {
      const value = element.getBoundingClientRect();
      return { top: value.top, bottom: value.bottom, width: value.width };
    };
    const kicker = profile.querySelector('.mc-reader__profile-kicker');
    const identity = profile.querySelector('[data-reader-identity]');
    const classMark = profile.querySelector('.mc-reader__class-mark');
    return {
      kicker: kicker?.textContent?.trim(),
      kickerRect: kicker ? rect(kicker) : null,
      identityRect: identity ? rect(identity) : null,
      classRect: classMark ? rect(classMark) : null,
      classWithinIdentity: Boolean(classMark?.closest('.mc-reader__identity-copy')),
    };
  });
  assert.equal(profileHierarchy.kicker, 'Ваш профиль', 'the profile overview must identify itself as a reader profile');
  assert.ok(profileHierarchy.kickerRect && profileHierarchy.identityRect && profileHierarchy.kickerRect.bottom <= profileHierarchy.identityRect.bottom, 'profile label must stay within the identity composition');
  assert.ok(profileHierarchy.classRect && profileHierarchy.classRect.width > 0, 'favorite class remains part of the visible profile passport');
  assert.equal(profileHierarchy.classWithinIdentity, true, 'favorite class must stay with the identity rather than occupying a detached profile column');
  assert.ok(profileHierarchy.classRect.width < 260, 'favorite class must remain a compact token, not a wide secondary panel');
  const overviewVisual = await page.locator('[data-reader-profile-overview]').evaluate(element => {
    const style = getComputedStyle(element);
    return { background: style.backgroundColor, borderLeft: style.borderLeftWidth, radius: style.borderRadius, shadow: style.boxShadow };
  });
  assert.equal(overviewVisual.background, 'rgb(255, 255, 255)');
  assert.equal(overviewVisual.borderLeft, '1px', 'the profile must not use an ornamental amber rail');
  assert.equal(overviewVisual.radius, '8px', 'the generated Reader Tailwind layer must preserve the shared surface geometry');
  assert.equal(overviewVisual.shadow, 'none', 'the profile surface must remain shadow-free');
  const accountMenu = page.locator('[data-reader-account-menu]');
  const accountSummary = page.getByText('Аккаунт', { exact: true });
  const assertAccountMenuFits = async width => {
    await accountSummary.click();
    const bounds = await accountMenu.evaluate(element => {
      const rect = element.querySelector('[data-reader-account-actions]').getBoundingClientRect();
      const container = element.closest('.mc-reader__masthead').getBoundingClientRect();
      const controls = [...element.querySelectorAll('a, button')].map(control => {
        const controlRect = control.getBoundingClientRect();
        return { left: controlRect.left, right: controlRect.right, top: controlRect.top, bottom: controlRect.bottom, width: controlRect.width, height: controlRect.height };
      });
      return { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, width: rect.width, height: rect.height, viewport: innerWidth, containerLeft: container.left, containerRight: container.right, controls };
    });
    assert.ok(bounds.left >= 0 && bounds.right <= bounds.viewport && bounds.width > 0 && bounds.height > 0, `open Account menu must fit ${width}px: ${JSON.stringify(bounds)}`);
    assert.ok(bounds.controls.every(control => control.left >= 0 && control.right <= bounds.viewport && control.width > 0 && control.height >= 44), `Account controls must fit and keep 44px targets at ${width}px: ${JSON.stringify(bounds)}`);
    assert.ok(bounds.left >= bounds.containerLeft && bounds.right <= bounds.containerRight, `Account menu must stay inside its actual account container at ${width}px: ${JSON.stringify(bounds)}`);
    assert.ok(bounds.controls.every(control => control.left >= bounds.left && control.right <= bounds.right), `Account controls must stay inside the dropdown at ${width}px: ${JSON.stringify(bounds)}`);
    await accountSummary.click();
  };
  assert.equal(await accountMenu.isVisible(), true, 'authenticated controls belong in the account menu');
  assert.equal(await accountMenu.evaluate(element => element.open), false);
  assert.equal(await status.textContent(), '', 'authentication must not leave a redundant visual status row');
  await accountSummary.focus();
  await page.keyboard.press('Enter');
  assert.equal(await accountMenu.evaluate(element => element.open), true, 'native account disclosure opens from the keyboard');
  assert.equal(await page.getByRole('button', { name: 'Выйти', exact: true }).count(), 1);
  await page.keyboard.press('Escape');
  assert.equal(await accountMenu.evaluate(element => element.open), false, 'Escape closes the account disclosure');
  assert.equal(await accountSummary.evaluate(element => element === document.activeElement), true, 'Escape returns focus to the account summary');
  for (const width of [320, 390, 560]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertAccountMenuFits(width);
  }
  profile = sessionDto({
    user: { displayName: 'Совместимый читатель' },
    profile: legacyProfileDto({ id: '323e4567-e89b-42d3-a456-426614174002', displayName: 'Совместимый читатель', bio: '', favoriteClass: null }),
  });
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  const legacyNameField = page.locator('[data-reader-display-name]');
  assert.equal(await page.locator('[data-reader-twitch]').isDisabled(), true, 'an older Reader response must not accept a social link it cannot save');
  assert.equal(await page.locator('[data-reader-youtube]').isDisabled(), true, 'an older Reader response must not accept a social link it cannot save');
  assert.match(await page.locator('[data-reader-social-help]').textContent(), /станут доступны сразу после обновления/);
  await legacyNameField.fill('Совместимый профиль');
  profileWriteResponse = legacyProfileDto({ id: '323e4567-e89b-42d3-a456-426614174002', displayName: 'Совместимый профиль', bio: '', favoriteClass: null, version: 2 });
  await page.locator('[data-reader-save-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Изменения сохранены'));
  assert.deepEqual(profileWrites.at(-1).body, { version: 1, displayName: 'Совместимый профиль', bio: '', favoriteClass: null }, 'the compatibility path must keep the legacy four-field PATCH contract');
  await page.getByRole('button', { name: 'Закрыть', exact: true }).click();
  profile = sessionDto({
    user: { displayName: 'Аватар совместимости' },
    profile: profileDto({ id: '423e4567-e89b-42d3-a456-426614174003', displayName: 'Аватар совместимости', bio: '', favoriteClass: null }),
  });
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  avatarWriteResponse = legacyProfileDto({ id: '423e4567-e89b-42d3-a456-426614174003', displayName: 'Аватар совместимости', bio: '', favoriteClass: null, version: 2, avatarUrl: '/reader-api/v1/profile/avatar?v=legacy-avatar' });
  await page.locator('[data-reader-avatar-input]').setInputFiles({ name: 'legacy-avatar.png', mimeType: 'image/png', buffer: Buffer.from('legacy-avatar') });
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Фотография профиля обновлена'));
  assert.equal(await page.locator('[data-reader-twitch]').isDisabled(), true, 'a legacy mutation response must disable social fields before another write');
  assert.equal(await page.locator('[data-reader-youtube]').isDisabled(), true, 'a legacy mutation response must disable social fields before another write');
  await page.locator('[data-reader-display-name]').fill('Аватар совместим');
  profileWriteResponse = legacyProfileDto({ id: '423e4567-e89b-42d3-a456-426614174003', displayName: 'Аватар совместим', bio: '', favoriteClass: null, version: 3, avatarUrl: '/reader-api/v1/profile/avatar?v=legacy-avatar' });
  await page.locator('[data-reader-save-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Изменения сохранены'));
  assert.deepEqual(profileWrites.at(-1).body, { version: 2, displayName: 'Аватар совместим', bio: '', favoriteClass: null }, 'a legacy mutation response must keep the following PATCH on the four-field contract');
  await page.getByRole('button', { name: 'Закрыть', exact: true }).click();
  profile = sessionDto();
  profileWriteResponse = profileDto({ version: 2 });
  avatarWriteResponse = profileDto({ version: 2, avatarUrl: '/reader-api/v1/profile/avatar?v=avatar2' });
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  assert.equal(await page.locator('[data-reader-profile-overview]').isVisible(), false, 'editing is a dedicated view, not another duplicate profile panel');
  assert.equal(await page.locator('[data-reader-favorites]').isVisible(), false, 'saved articles stay below the profile instead of interrupting its editor');

  for (const width of [320, 390, 560, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await assertFits();
    const editorGeometry = await page.locator('[data-reader-profile-editor]').evaluate(form => {
      const rect = node => { const r = node.getBoundingClientRect(); return { left: r.left, right: r.right, width: r.width, height: r.height }; };
      return {
        preview: Boolean(form.querySelector('[data-reader-editor-avatar]')),
        controls: [...form.querySelectorAll('input:not([type=file]):not([type=checkbox]), textarea, select')].map(element => ({ ...rect(element), social: Object.hasOwn(element.dataset, 'readerTwitch') || Object.hasOwn(element.dataset, 'readerYoutube') })),
        publication: rect(form.querySelector('.mc-reader__publication')),
        photo: rect(form.querySelector('.mc-reader__preview')),
        upload: rect(form.querySelector('[data-reader-avatar-input]')),
      };
    });
    assert.equal(editorGeometry.preview, true, 'the photo must be visible next to its upload control inside the editor');
    assert.ok(editorGeometry.controls.every(r => r.height >= 44 && r.width > 0), 'all fields keep usable targets');
    assert.ok(editorGeometry.publication.height >= 44 && editorGeometry.publication.width > 0, 'publication action remains a readable, deliberate section');
    const primaryControls = editorGeometry.controls.filter(control => !control.social);
    assert.ok(primaryControls.every(r => Math.abs(r.left - primaryControls[0].left) < 1 && Math.abs(r.right - primaryControls[0].right) < 1), 'name, bio and class share one field alignment');
    assert.ok(editorGeometry.upload.left >= editorGeometry.photo.left && editorGeometry.upload.right <= editorGeometry.photo.right, 'upload control stays in the photo component');
    if ([390, 1440].includes(width)) await capture(`editor-${width}`);
  }

  const nameField = page.locator('[data-reader-display-name]');
  const bioField = page.locator('[data-reader-bio]');
  const twitchField = page.locator('[data-reader-twitch]');
  const youtubeField = page.locator('[data-reader-youtube]');
  const classField = page.locator('[data-reader-favorite-class]');
  const editorStatus = page.locator('[data-reader-editor-status]');
  const fortyUnicodeCharacters = '😀'.repeat(40);
  const writesBeforeValidation = profileWrites.length;
  await nameField.fill(fortyUnicodeCharacters);
  assert.equal(await page.locator('[data-reader-name-count]').textContent(), '40 / 40');
  assert.equal(await nameField.evaluate(element => element.checkValidity()), true, '40 Unicode code points must remain valid');
  await nameField.fill('   ');
  await page.locator('[data-reader-save-profile]').click();
  assert.match(await nameField.evaluate(element => element.validationMessage), /2|символ/);
  assert.equal(profileWrites.length, writesBeforeValidation, 'invalid whitespace-only names must not reach the API');
  await nameField.fill('Исправленное имя');
  assert.equal(await nameField.evaluate(element => element.validationMessage), '', 'correcting a name must clear stale custom validity');
  await bioField.fill('Черновик с кириллицей и эмодзи 🃏');
  await twitchField.fill('https://evil.test/not-a-channel');
  assert.equal(await page.locator('[data-reader-twitch-mark]').isHidden(), true, 'unfinished or untrusted social input must never become a public profile link');
  await twitchField.fill('https://twitch.tv/Mana_Cost');
  await youtubeField.fill('https://youtube.com/@Manacost');
  await classField.selectOption('priest');
  assert.equal(await page.locator('[data-reader-identity]').textContent(), 'Исправленное имя');

  await page.getByRole('button', { name: 'Закрыть', exact: true }).click();
  assert.equal(await page.locator('[data-reader-profile-editor]').isVisible(), false);
  assert.equal(await page.locator('[data-reader-favorites]').isVisible(), true, 'saved articles return directly below the profile after editing');
  assert.equal(await page.locator('[data-reader-twitch-mark]').isVisible(), true, 'a valid Twitch link must add a mark after the profile name');
  assert.equal(await page.locator('[data-reader-youtube-mark]').isVisible(), true, 'a valid YouTube link must add a mark after the profile name');
  assert.equal(await page.locator('[data-reader-preview-label]').isVisible(), true);
  assert.match(await page.locator('[data-reader-preview-label]').textContent(), /несохранённые/);
  assert.equal(await page.locator('[data-reader-open-editor]').evaluate(element => element === document.activeElement), true);
  assert.equal(await page.locator('[data-reader-twitch-mark]').getAttribute('href'), 'https://www.twitch.tv/mana_cost');
  assert.equal(await page.locator('[data-reader-youtube-mark]').getAttribute('href'), 'https://www.youtube.com/@Manacost');
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  assert.equal(await nameField.inputValue(), 'Исправленное имя');

  profile = sessionDto({ csrfToken: 'rotated-synthetic-token' });
  const callsBeforeFocus = meCalls;
  await page.evaluate(() => window.dispatchEvent(new Event('focus')));
  await page.waitForFunction(expected => window.__unused === undefined && document.querySelector('[data-reader-display-name]').value === expected, 'Исправленное имя');
  assert.ok(meCalls > callsBeforeFocus, 'focus must recheck the authenticated session');
  assert.equal(await bioField.inputValue(), 'Черновик с кириллицей и эмодзи 🃏', 'focus refresh must preserve dirty text');

  profileWriteStatus = 403;
  await page.locator('[data-reader-save-profile]').click();
  await page.locator('[data-reader-retry-profile]').waitFor();
  profile = sessionDto({ csrfToken: 'csrf-after-403' });
  const callsBeforeCsrfRefresh = meCalls;
  await page.locator('[data-reader-retry-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Вход обновлён'));
  assert.ok(meCalls > callsBeforeCsrfRefresh);
  assert.equal(await page.locator('[data-reader-retry-profile]').isVisible(), false, 'successful CSRF refresh must clear its stale retry');
  assert.equal(await nameField.inputValue(), 'Исправленное имя');

  profileWriteStatus = 409;
  await page.locator('[data-reader-save-profile]').click();
  await page.locator('[data-reader-reload-version]').waitFor();
  assert.equal(profileWrites.at(-1).headers['x-reader-csrf'], 'csrf-after-403');
  assert.deepEqual(profileWrites.at(-1).body, { version: 1, displayName: 'Исправленное имя', bio: 'Черновик с кириллицей и эмодзи 🃏', favoriteClass: 'priest', twitchUrl: 'https://www.twitch.tv/mana_cost', youtubeUrl: 'https://www.youtube.com/@Manacost' });
  profile = sessionDto({ csrfToken: 'csrf-after-403', profile: profileDto({ version: 2, displayName: 'Серверное имя' }) });
  await page.locator('[data-reader-reload-version]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Версия обновлена'));
  assert.equal(await nameField.inputValue(), 'Исправленное имя', 'conflict reload must preserve the text draft');

  profileWriteStatus = 200;
  profileWriteResponse = profileDto({ version: 3, displayName: 'Исправленное имя', bio: 'Черновик с кириллицей и эмодзи 🃏', favoriteClass: 'priest', twitchUrl: 'https://www.twitch.tv/mana_cost', youtubeUrl: 'https://www.youtube.com/@Manacost' });
  await page.locator('[data-reader-save-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Изменения сохранены'));
  assert.equal(profileWrites.at(-1).body.version, 2, 'retry after conflict must use the reloaded version');

  const publicationButton = page.locator('[data-reader-publish-profile]');
  assert.equal(publicationWrites.length, 0, 'saving private fields never silently publishes');
  assert.equal(await page.locator('[data-reader-public-consent]').count(), 0, 'publication has no extra consent checkbox');
  assert.equal(await publicationButton.isDisabled(), false);
  await bioField.fill('Ещё не сохранено');
  assert.equal(await publicationButton.isDisabled(), true, 'unsaved fields cannot be mistaken for the published snapshot');
  await bioField.fill(profileWriteResponse.bio);
  publicationStatus = 404;
  await publicationButton.click();
  await editorStatus.filter({ hasText: 'Публичный профиль появится после первого комментария' }).waitFor();
  publicationStatus = 200;
  await publicationButton.click();
  await editorStatus.filter({ hasText: 'Профиль в комментариях обновлён' }).waitFor();
  assert.deepEqual(publicationWrites.at(-1).body, { profileVersion: 3 });
  assert.equal(publicationWrites.at(-1).headers['x-reader-csrf'], 'csrf-after-403');
  assert.equal(await publicationButton.isDisabled(), false, 'the same saved snapshot may be refreshed again deliberately');

  await page.locator('[data-reader-avatar-input]').setInputFiles({ name: 'invalid.txt', mimeType: 'text/plain', buffer: Buffer.from('not an image') });
  assert.equal(await publicationButton.isDisabled(), false, 'an invalid local image does not alter the saved profile snapshot');
  await bioField.fill('Этот текст нельзя потерять при загрузке фото.');
  avatarWriteResponse = profileDto({ version: 4, displayName: 'Исправленное имя', bio: 'Черновик с кириллицей и эмодзи 🃏', favoriteClass: 'priest', twitchUrl: 'https://www.twitch.tv/mana_cost', youtubeUrl: 'https://www.youtube.com/@Manacost', avatarUrl: '/reader-api/v1/profile/avatar?v=avatar4' });
  avatarDelay = 250;
  const callsBeforeUpload = meCalls;
  await page.locator('[data-reader-avatar-input]').setInputFiles({ name: 'avatar.png', mimeType: 'image/png', buffer: Buffer.from('synthetic-png') });
  await page.evaluate(() => window.dispatchEvent(new Event('focus')));
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Фотография профиля обновлена'));
  assert.equal(meCalls, callsBeforeUpload, 'focus refresh must be skipped during avatar mutation');
  assert.equal(await bioField.inputValue(), 'Этот текст нельзя потерять при загрузке фото.');
  assert.equal(avatarWrites.at(-1).method, 'PUT');
  assert.equal(avatarWrites.at(-1).headers['x-reader-profile-version'], '3');
  assert.equal(avatarWrites.at(-1).headers['x-reader-csrf'], 'csrf-after-403');
  assert.ok(avatarWrites.at(-1).size > 0);
  await page.waitForFunction(() => {
    const image = document.querySelector('[data-reader-editor-avatar-image]');
    return !image.hidden && image.complete && image.naturalWidth > 0;
  });
  assert.equal(await page.locator('[data-reader-remove-avatar]').isVisible(), true);

  avatarDelay = 0;
  avatarWriteResponse = profileDto({ version: 5, displayName: 'Исправленное имя', bio: 'Черновик с кириллицей и эмодзи 🃏', favoriteClass: 'priest', twitchUrl: 'https://www.twitch.tv/mana_cost', youtubeUrl: 'https://www.youtube.com/@Manacost', avatarUrl: null });
  await page.locator('[data-reader-remove-avatar]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Фотография профиля обновлена'));
  assert.equal(avatarWrites.at(-1).method, 'DELETE');
  assert.equal(avatarWrites.at(-1).headers['x-reader-profile-version'], '4');
	  assert.equal(await page.locator('[data-reader-editor-avatar-image]').isVisible(), true);
	  assert.match(await page.locator('[data-reader-editor-avatar-image]').getAttribute('src'), /default-avatar\.webp\?ver=a1b2c3d4e5f6$/);
  profile = sessionDto({ csrfToken: 'csrf-after-403', profile: profileDto({ version: 4, avatarUrl: '/reader-api/v1/profile/avatar?v=stale4' }) });
  await page.evaluate(() => window.dispatchEvent(new Event('focus')));
  await page.waitForTimeout(100);
	  assert.equal(await page.locator('[data-reader-editor-avatar-image]').isVisible(), true, 'stale GET must not roll back the default avatar after removal');
  assert.equal(await bioField.inputValue(), 'Этот текст нельзя потерять при загрузке фото.');

  profileWriteStatus = 0;
  const writesBeforeUnknownResult = profileWrites.length;
  await page.locator('[data-reader-save-profile]').click();
  await page.locator('[data-reader-retry-profile]').waitFor();
  profile = sessionDto({ csrfToken: 'csrf-after-unknown', profile: profileDto({ version: 6, displayName: 'Исправленное имя', bio: 'Черновик с кириллицей и эмодзи 🃏', favoriteClass: 'priest' }) });
  await page.locator('[data-reader-retry-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Версия обновлена'));
  assert.equal(profileWrites.length, writesBeforeUnknownResult + 1, 'unknown write result must reconcile with GET, not replay the write');
  assert.equal(await bioField.inputValue(), 'Этот текст нельзя потерять при загрузке фото.');
  profileWriteStatus = 200;

  const ownerAFavorite = { id: '623e4567-e89b-42d3-a456-426614174007', postId: 18, title: 'Сохранённая статья первого аккаунта', path: '/guides/owner-a/', createdAt: 1700000000000 };
  const ownerBFavorite = { id: '723e4567-e89b-42d3-a456-426614174008', postId: 19, title: 'Сохранённая статья второго аккаунта', path: '/guides/owner-b/', createdAt: 1700000001000 };
  favorites = [ownerAFavorite];
  profile = sessionDto({ profile: profileDto({ id: '123e4567-e89b-42d3-a456-426614174000', displayName: 'Первый читатель', bio: '', favoriteClass: null, version: 1 }) });
  await ready();
  await page.locator('[data-reader-favorites-sentinel]').scrollIntoViewIfNeeded();
  await page.getByRole('link', { name: ownerAFavorite.title }).waitFor();

  holdFavoriteRead = true;
  const favoriteReadsBeforeAccountSwitch = releaseFavoriteReads.length;
  favorites = [ownerBFavorite];
  profile = sessionDto({
    profileUrl: null,
    profile: profileDto({ id: '223e4567-e89b-42d3-a456-426614174001', displayName: 'Другой читатель', bio: '', favoriteClass: null, version: 1 }),
  });
  await page.evaluate(() => window.dispatchEvent(new Event('focus')));
  await page.waitForFunction(() => document.querySelector('[data-reader-display-name]').value === 'Другой читатель');
  assert.equal(await page.getByRole('link', { name: ownerAFavorite.title }).count(), 0, 'account B must never render saved articles from account A');
  assert.equal(await page.getByRole('link', { name: ownerBFavorite.title }).count(), 0, 'the delayed account B response must not be guessed from account A state');
  await page.locator('[data-reader-favorites-sentinel]').scrollIntoViewIfNeeded();
  await page.waitForTimeout(100);
  assert.ok(releaseFavoriteReads.length > favoriteReadsBeforeAccountSwitch, 'account B must request its own saved articles instead of reusing account A data');
  holdFavoriteRead = false;
  for (const release of releaseFavoriteReads.splice(0)) release();
  await page.getByRole('link', { name: ownerBFavorite.title }).waitFor();
  assert.equal(await page.getByRole('link', { name: 'Профиль HearthPulse' }).count(), 0, 'account switch must remove the prior account link');
  await accountSummary.click();
  assert.equal(await page.getByRole('button', { name: 'Выйти', exact: true }).count(), 1);

  const longDisplayName = 'ОченьДлинноеИмяЧитателяБезПробелов123456';
  profile = sessionDto({ user: { displayName: `${'ОченьДлинноеИмяЧитателя'.repeat(12)}${'UnbrokenLatinIdentity'.repeat(14)}` }, profile: profileDto({ displayName: longDisplayName }) });
  for (const width of [320, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await ready();
    await assertFits();
    assert.equal(await page.locator('[data-reader-identity]').textContent(), longDisplayName);
  }
  await accountSummary.click();
  const profileLink = page.getByRole('link', { name: 'Профиль HearthPulse', exact: true });
  for (let step = 0; step < 8 && !await profileLink.evaluate(element => document.activeElement === element); step++) await page.keyboard.press('Tab');
  assert.equal(await profileLink.evaluate(element => document.activeElement === element), true);
  assert.ok(await profileLink.evaluate(element => {
    const style = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    return rect.height >= 44 && style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
  }), 'keyboard focus must be visible on the 44px profile link');
  await page.setViewportSize({ width: 1024, height: 900 });
  await ready();
  let sections = await assertFits();
	assert.equal(sections.sections.length, 3, 'profile, saved articles, and privacy tools remain visible in one reading flow');
  await page.setViewportSize({ width: 560, height: 900 });
  await ready();
  sections = await assertFits();
	assert.equal(sections.sections.length, 3, 'narrow layouts keep profile, saved articles, and privacy tools as stacked sections');
  const identitySize = () => page.locator('[data-reader-identity]').evaluate(element => parseFloat(getComputedStyle(element).fontSize));
  const originalIdentitySize = await identitySize();
  await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
  assert.ok(await identitySize() >= originalIdentitySize * 2, '200% text enlargement must actually resize the identity');
  await assertFits();
  await assertAccountMenuFits('560px at 200% zoom');
  await capture('authenticated-long-name');

  profile = sessionDto({ user: { displayName: '<img src=x onerror=alert(1)> Читатель' }, profileUrl: null, profile: profileDto({ displayName: '<img src=x onerror=alert(1)> Читатель' }) });
  await ready();
  assert.equal(await page.locator('[data-reader-identity]').textContent(), profile.profile.displayName);
  assert.match(await page.locator('[data-reader-avatar-image]').getAttribute('src'), /default-avatar\.webp\?ver=a1b2c3d4e5f6$/);
  assert.equal(await page.getByRole('link', { name: 'Профиль HearthPulse' }).count(), 0);
  await accountSummary.click();
  logoutStatus = 503;
  await page.getByRole('button', { name: 'Выйти', exact: true }).click();
  await retry.waitFor();
  assert.equal(await page.locator('[data-reader-identity]').textContent(), '');
  logoutStatus = 204;
  await retry.click();
  await page.waitForFunction(() => document.querySelector('[data-reader-status]').textContent.includes('вышли'));
  assert.equal(logoutCalls, 2, 'retry must retry logout, not reauthenticate');
  profile = { user: { displayName: 'Missing CSRF' } };
  await ready();
  await retry.waitFor();
  profileStatus = 503;
  await ready();
  assert.equal(await page.locator('[data-reader-actions] button').count(), 1, `${await status.textContent()} / ${await page.locator('[data-reader-actions]').textContent()}`);
  await assertFits();
  await capture('error');
  // Real loopback sockets, not immediate route.abort(): prove the shared five-second deadline.
  await page.unroute('**/reader-api/v1/bootstrap');
  heldRequest = { path: '/reader-api/v1/bootstrap', method: 'GET' };
  const meDeadlineStart = Date.now();
  await page.goto(origin, { waitUntil: 'domcontentloaded' });
  await retry.waitFor({ timeout: 12000 });
  assert.match(await status.textContent(), /слишком много времени/);
  assert.ok(Date.now() - meDeadlineStart >= 4500, 'session request must wait for its actual shared deadline');
  assert.ok(nativeDeadlineCalls.some(call => call.path === heldRequest.path));
  heldRequest = null;
  await page.route('**/reader-api/v1/bootstrap', fulfillMe);
  profileStatus = 200;
  profile = sessionDto({ user: { displayName: 'Synthetic reader' }, profileUrl: null, profile: profileDto({ displayName: 'Synthetic reader' }) });
  await ready();
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  await page.unroute('**/reader-api/v1/profile');
  heldRequest = { path: '/reader-api/v1/profile', method: 'PATCH', partialJson: true };
  await nameField.fill('Черновик после таймаута');
  const bodyDeadlineStart = Date.now();
  await page.locator('[data-reader-save-profile]').click();
  await page.locator('[data-reader-retry-profile]').waitFor({ timeout: 12000 });
  assert.match(await editorStatus.textContent(), /не получен вовремя/);
  assert.ok(Date.now() - bodyDeadlineStart >= 6500, 'partial response body must reach the actual mutation deadline');
  assert.equal(await nameField.inputValue(), 'Черновик после таймаута');
  assert.equal(await page.locator('[data-reader-save-profile]').isDisabled(), false);
  const nativeWrites = nativeDeadlineCalls.filter(call => call.path === heldRequest.path).length;
  heldRequest = null;
  await page.locator('[data-reader-retry-profile]').click();
  await page.waitForFunction(() => document.querySelector('[data-reader-editor-status]').textContent.includes('Версия обновлена'));
  assert.equal(nativeDeadlineCalls.filter(call => call.path === '/reader-api/v1/profile').length, nativeWrites, 'deadline recovery must read state without replaying the write');
  await page.unroute('**/reader-auth/logout');
  heldRequest = { path: '/reader-auth/logout', method: 'POST' };
  const logoutDeadlineStart = Date.now();
  await accountSummary.click();
  await page.getByRole('button', { name: 'Выйти', exact: true }).click();
  await retry.waitFor({ timeout: 12000 });
  assert.match(await status.textContent(), /Не удалось выйти/);
  assert.ok(Date.now() - logoutDeadlineStart >= 6500, 'logout must wait for its actual UI deadline');
  assert.ok(nativeDeadlineCalls.some(call => call.path === heldRequest.path));
  heldRequest = null;
  await ready();
  avatarDelay = 500;
  await page.getByRole('button', { name: 'Изменить профиль' }).click();
  await page.locator('[data-reader-avatar-input]').setInputFiles({ name: 'avatar.png', mimeType: 'image/png', buffer: Buffer.from('pending-avatar') });
  await page.evaluate(() => window.dispatchEvent(new Event('pagehide')));
  assert.equal(await page.locator('[data-reader-identity]').textContent(), '');
  assert.equal(await page.locator('[data-reader-actions]').textContent(), '');
  await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
  await page.waitForFunction(() => !document.querySelector('[data-reader-account-menu]').hidden && document.querySelector('[data-reader-status]').textContent === '');
  for (const selector of ['[data-reader-save-profile]', '[data-reader-avatar-input]', '[data-reader-remove-avatar]']) {
    assert.equal(await page.locator(selector).isDisabled(), false, `${selector} must be re-enabled after pagehide abort and reauthentication`);
  }
  sharedBootstrap = false;
  const legacyBefore = legacyMeCalls;
  await ready();
  await page.getByRole('button', {name: 'Изменить профиль'}).waitFor();
  assert.equal(legacyMeCalls, legacyBefore + 1, 'old cached shells retain /me fallback compatibility');
  console.log('Reader browser regression: shared bootstrap, standalone compatibility, responsive account states, Unicode edits, conflicts, CSRF refresh, avatar writes, account switches, real request/body/logout deadlines, mutation reconciliation and private-state races: PASS');
} finally {
  if (browser) await browser.close();
  for (const response of heldResponses) response.destroy();
  await new Promise(resolve => server.close(resolve));
}
