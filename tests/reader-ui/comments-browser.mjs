import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdirSync, readFileSync } from 'node:fs';
import { chromium } from 'playwright';
import './comments-assets.mjs';

// A local, synthetic boundary test: no WordPress runtime or reader data.
const root = new URL('../../', import.meta.url).pathname;
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const php = "define('ABSPATH','/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function esc_html__($v){return $v;} function get_the_ID(){return 7;} function get_permalink(){return 'https://example.test/article/';} function wp_parse_url($v,$part){return '/article/';} require $argv[1]; echo hs_reader_comments_shell();";
const shell = execFileSync('php', ['-r', php, `${plugin}comments.php`], { encoding: 'utf8' });
const favoritePhp = "define('ABSPATH','/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function esc_html__($v){return $v;} function hs_reader_favorite_article($id){return ['allowed'=>true,'path'=>'/article/'];} require $argv[1]; echo hs_reader_article_favorite_shell(7);";
const favoriteShell = execFileSync('php', ['-r', favoritePhp, `${plugin}article-favorite.php`], { encoding: 'utf8' });
const sharedUi = `${plugin}ui.css`;
const assets = new Map([['/community-ui.js', readFileSync(`${plugin}community-ui.js`)],['/comments.js', readFileSync(`${plugin}comments.js`)], ['/comments.css', readFileSync(`${plugin}comments.css`)], ['/article-favorite.js', readFileSync(`${plugin}article-favorite.js`)], ['/article-favorite.css', readFileSync(`${plugin}article-favorite.css`)], ['/ui.css', readFileSync(sharedUi)], ['/theme.css', readFileSync(`${root}wordpress/themes/Newspaper_new/style.css`)], ['/theme-boxed.css', readFileSync(`${root}wordpress/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css`)]]);
const id = '123e4567-e89b-42d3-a456-426614174000';
const reactions = [{ kind: 'like', count: 7, selected: true }, { kind: 'thanks', count: 3, selected: false }, { kind: 'fire', count: 5, selected: false }];
const author = { id, name: 'Маг <script>alert(1)</script>', bio: 'Люблю колоды', favoriteClass: 'mage', avatarVersion: 'a'.repeat(32), avatarUrl: `/reader-api/v1/readers/${id}/avatar?v=${'a'.repeat(32)}`, profileUrl: `/account/?reader=${id}`, paidSubscriber: true, hasTwitch: true, hasYoutube: true };
let comments = [{ id: '223e4567-e89b-42d3-a456-426614174000', postId: 7, parentId: null, status: 'published', version: 1, createdAt: Date.now(), body: '<img src=x onerror=alert(1)> Первый комментарий', author, reactions }];
let writes = [];
let holdCommentBody = false;
let holdRefresh = false;
let heldRefreshes = [];
let failPost = false;
let deletes = [];
const attachmentId = '423e4567-e89b-42d3-a456-426614174000';
let attachmentUploads = 0;
let articleFavoriteSaved = false;
let articleFavoriteWrites = [];
let commentReads = 0;
let meReads = 0;
const articleFavoriteCsrf = 'f'.repeat(43);
const server = createServer(async (req, res) => {
  if (assets.has(req.url)) { res.writeHead(200, {'content-type': req.url.endsWith('css') ? 'text/css' : 'text/javascript'}); return res.end(assets.get(req.url)); }
  if (req.url === '/' || req.url === '/deferred') { const spacer = req.url === '/deferred' ? '<div style="height:2400px"></div>' : ''; res.writeHead(200, {'content-type':'text/html; charset=utf-8'}); return res.end(`<!doctype html><meta charset="utf-8"><meta name=viewport content="width=device-width"><link rel=stylesheet href=/theme.css><link rel=stylesheet href=/theme-boxed.css><link rel=stylesheet href=/ui.css><link rel=stylesheet href=/comments.css><link rel=stylesheet href=/article-favorite.css><body class="td-boxed-layout"><header class="td-container-wrap"></header><main class="td-main-content-wrap td-container-wrap"><div class="td-container"><div class="td-page-content">${spacer}${shell}${favoriteShell}</div></div></main><footer class="td-container-wrap"></footer><script src=/community-ui.js></script><script src=/comments.js></script><script src=/article-favorite.js></script>`); }
	if (req.url.startsWith('/reader-api/v1/readers/')) { res.writeHead(200, {'content-type':'image/svg+xml'}); return res.end('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="#19313e"/><text x="20" y="27" text-anchor="middle" font-size="20" fill="#eef3f5">М</text></svg>'); }
  if (req.url === '/favicon.ico') { res.writeHead(204); return res.end(); }
  if (req.url === '/reader-api/v1/community/me') return res.end(JSON.stringify({ canModerateComments: false, commentingBlocked: false }));
  if (req.url === '/reader-api/v1/me') { meReads++; return res.end(JSON.stringify({ profile: { id, displayName: 'Я', bio: '', favoriteClass: 'mage', version: 1, avatarUrl: null }, csrfToken: 'synthetic' })); }
  if (req.url.startsWith('/reader-api/v1/threads/7/comments') && req.method === 'GET') { commentReads++; if(holdCommentBody){res.writeHead(200,{'content-type':'application/json'});res.write('{"items":');return;} if (holdRefresh) { req.resume(); heldRefreshes.push(res); return; } return res.end(JSON.stringify({items: comments, nextCursor: null})); }
  if (req.url === '/reader-api/v1/comment-attachments' && req.method === 'PUT') { for await (const _chunk of req) {} attachmentUploads++; return res.end(JSON.stringify({attachment:{id:attachmentId,width:96,height:48}})); }
  if (req.url === `/reader-api/v1/comment-attachments/${attachmentId}` && req.method === 'DELETE') { req.resume(); return res.end(JSON.stringify({id:attachmentId,discarded:true})); }
  if (req.url === '/reader-api/v1/favorites/7' && req.method === 'GET') return res.end(JSON.stringify({ postId: 7, saved: articleFavoriteSaved, csrfToken: articleFavoriteCsrf }));
  if (req.url === '/reader-api/v1/favorites/7' && (req.method === 'PUT' || req.method === 'DELETE')) { for await (const _chunk of req) {} articleFavoriteSaved = req.method === 'PUT'; articleFavoriteWrites.push({ method: req.method, headers: req.headers }); return res.end(JSON.stringify(req.method === 'PUT' ? { postId: 7, saved: true, favorite: { id: '523e4567-e89b-42d3-a456-426614174000', postId: 7, title: 'Тестовая статья', path: '/article/', createdAt: Date.now() } } : { postId: 7, saved: false })); }
  if (req.url === '/reader-api/v1/comments/323e4567-e89b-42d3-a456-426614174000/attachment') { res.writeHead(200, {'content-type':'image/svg+xml'}); return res.end('<svg xmlns="http://www.w3.org/2000/svg" width="96" height="48"><rect width="96" height="48" fill="#547"/></svg>'); }
  if (req.url === '/reader-api/v1/threads/7/comments' && req.method === 'POST') { let raw=''; for await (const chunk of req) raw += chunk; const body=JSON.parse(raw); writes.push(body); if(failPost)return req.socket.destroy(); const comment={id:'323e4567-e89b-42d3-a456-426614174000',postId:7,parentId:body.parentId,status:'published',version:1,createdAt:Date.now(),body:body.body,attachment:body.attachmentId ? {id:body.attachmentId,width:96,height:48,url:'/reader-api/v1/comments/323e4567-e89b-42d3-a456-426614174000/attachment'} : null,author:{...author,id,name:'Я',avatarUrl:null,avatarVersion:null,paidSubscriber:false,hasTwitch:false,hasYoutube:false}}; comments=[...comments,comment]; res.writeHead(201,{'content-type':'application/json'}); return res.end(JSON.stringify({comment})); }
	if (req.url === '/reader-api/v1/comments/223e4567-e89b-42d3-a456-426614174000' && req.method === 'DELETE') { let raw=''; for await (const chunk of req) raw += chunk; deletes.push({headers:req.headers,body:JSON.parse(raw)}); return res.end('{}'); }
  res.statusCode=404; res.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
let browser;
try {
  if(process.env.READER_UI_SCREENSHOTS) mkdirSync(process.env.READER_UI_SCREENSHOTS,{recursive:true});
  browser = await chromium.launch({headless:true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {})}); const page = await browser.newPage(); page.setDefaultTimeout(3000);
  const consoleErrors=[]; page.on('console', message=>{if(message.type()==='error')consoleErrors.push(message.text())}); page.on('pageerror', error=>consoleErrors.push(error.message));
  for (const width of [320, 390, 560, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 800 });
    await page.goto(`http://127.0.0.1:${server.address().port}/`);
    await page.getByRole('heading', { name: 'Комментарии' }).waitFor();
    await page.locator('.mc-comments__body').waitFor();
    await page.locator('[data-comments-list] .mc-comments__avatar').waitFor();
    await page.waitForFunction(() => {
      const image = document.querySelector('[data-comments-list] img.mc-comments__avatar');
      return image?.complete && image.naturalWidth > 0;
    });
    assert.equal(await page.locator('.mc-comments__author-badge--twitch').isVisible(), true, 'a Twitch author receives the Twitch mark');
    assert.equal(await page.locator('.mc-comments__author-badge--youtube').isVisible(), true, 'a YouTube author receives the YouTube mark');
    assert.equal(await page.getByRole('group', { name: 'Реакции на комментарий' }).isVisible(), true, 'reactions remain visible in the discussion layout');
    assert.equal(await page.locator('.mc-comments__author-badge--paid').getAttribute('aria-label'), 'Платный подписчик');
    const crown = await page.locator('.mc-comments__author-badge--paid').evaluate(node => ({
      width: node.getBoundingClientRect().width, height: node.getBoundingClientRect().height,
      background: getComputedStyle(node).backgroundColor, fill: getComputedStyle(node.querySelector('svg')).fill,
    }));
    assert.equal(crown.width, crown.height, 'crown backing must be geometrically square');
    assert.notEqual(crown.background, 'rgba(0, 0, 0, 0)');
    assert.notEqual(crown.fill, 'none', 'small crown remains legible as a filled mark');
    const contrast = await page.locator('textarea').evaluate(node => {
      const light = color => color.match(/[\d.]+/g).slice(0, 3).map(Number).map(value => value / 255)
        .map(value => value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4)
        .reduce((sum, value, index) => sum + value * [.2126, .7152, .0722][index], 0);
      const style = getComputedStyle(node);
      const levels = [light(style.color), light(style.backgroundColor)].sort((a, b) => b - a);
      return (levels[0] + .05) / (levels[1] + .05);
    });
    assert.ok(contrast >= 4.5, 'the light comment composer must not inherit the dark account field background');
    if (process.env.READER_UI_SCREENSHOTS) await page.screenshot({
      path: `${process.env.READER_UI_SCREENSHOTS}/comments-${width}.png`, fullPage: true,
    });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
    const layout = await page.locator('.mc-comments__comment').first().evaluate(node => {
      const rect = selector => {
        const r = node.querySelector(selector).getBoundingClientRect();
        return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height };
      };
      return {
        avatar: rect('.mc-comments__avatar'), name: rect('.mc-comments__name'),
        header: rect('.mc-comments__identity'), body: rect('.mc-comments__body'),
        time: rect('.mc-comments__meta'), paid: rect('.mc-comments__author-badge--paid'),
        targets: [...node.querySelectorAll('[data-comment-action]')].map(button => button.getBoundingClientRect().height),
      };
    });
    // Text may legitimately wrap. Measure whitespace after the entire author
    // header, not from the first name line through the badge and timestamp.
    const gap = layout.body.top - layout.header.bottom;
    assert.ok(gap >= 0 && gap <= 12, `header/body gap at ${width}px: ${gap}`);
    const textLeft = width <= 560 ? layout.avatar.left : layout.name.left;
    assert.ok(Math.abs(layout.body.left - textLeft) < 1, 'body follows the responsive identity alignment');
    for (const key of ['name', 'time', 'paid']) {
      assert.ok(layout[key].left >= layout.header.left && layout[key].right <= layout.header.right,
        `${key} remains inside author header at ${width}px`);
    }
    assert.ok(layout.targets.every(height => height >= 44), 'comment actions retain touch targets');
    const composerHeight = await page.locator('[data-comments-form]').evaluate(node => node.getBoundingClientRect().height);
    if (width === 390) assert.ok(composerHeight < 650, `mobile composer remains compact: ${composerHeight}`);
    if (width === 1440) assert.ok(composerHeight < 500, `desktop composer remains compact: ${composerHeight}`);
  }
  const profileSyncGeometry = await page.locator('.mc-comments__profile-sync').evaluate(node => {
    const visibility = [...node.children].map(child => child.hidden);
    [...node.children].forEach(child => { child.hidden = true; });
    const rect = node.getBoundingClientRect();
    const marginTop = parseFloat(getComputedStyle(node).marginTop);
    [...node.children].forEach((child, index) => { child.hidden = visibility[index]; });
    return { height: rect.height, marginTop };
  });
  assert.deepEqual(profileSyncGeometry, { height: 0, marginTop: 0 },
    'a profile sync with no visible controls must contribute no dead composer spacing');
  assert.deepEqual(consoleErrors, [], `fixture console errors: ${consoleErrors.join('; ')}`);
  const deferredPage = await browser.newPage({ viewport: { width: 1440, height: 800 } });
  const readsBeforeDeferred = { comments: commentReads, me: meReads };
  await deferredPage.goto(`http://127.0.0.1:${server.address().port}/deferred`, { waitUntil: 'load' });
  await deferredPage.waitForTimeout(150);
  assert.deepEqual({ comments: commentReads, me: meReads }, readsBeforeDeferred,
    'below-the-fold discussion must not start API requests during article rendering');
  await deferredPage.locator('[data-mc-comments]').scrollIntoViewIfNeeded();
  await deferredPage.locator('.mc-comments__body').waitFor();
  assert.ok(commentReads > readsBeforeDeferred.comments && meReads > readsBeforeDeferred.me,
    'discussion requests start when the reader approaches the section');
  await deferredPage.close();
  const initialScriptCount = await page.locator('script').count();
  // Emulate 200% desktop zoom: 1440 physical pixels / 2 = 720 CSS pixels.
  // This checks zoom-equivalent reflow, not browser-chrome zoom controls.
  const zoomContext = await browser.newContext({ viewport: { width: 720, height: 450 }, deviceScaleFactor: 2 });
  const zoomPage = await zoomContext.newPage();
  await zoomPage.goto(`http://127.0.0.1:${server.address().port}/`);
  await zoomPage.getByLabel('Комментарий', { exact: true }).waitFor();
  await zoomPage.locator('.mc-comments__body').waitFor();
  assert.equal(await zoomPage.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, '200% zoom-equivalent reflow must not overflow');
  assert.equal(await zoomPage.locator('[data-comments-consent]').count(), 0, 'there is no extra publication checkbox');
  assert.equal(await zoomPage.getByRole('button', { name: 'Прикрепить изображение' }).isVisible(), true);
  assert.equal(await zoomPage.getByRole('button', { name: 'Опубликовать', exact: true }).isVisible(), true);
  assert.equal(await zoomPage.getByRole('link', { name: author.name }).isVisible(), true);
  if (process.env.READER_UI_SCREENSHOTS) await zoomPage.screenshot({ path: `${process.env.READER_UI_SCREENSHOTS}/comments-zoom-200-emulated.png`, fullPage: true });
  await zoomContext.close();
  const favoriteButton = page.locator('[data-mc-article-favorite] [data-favorite-toggle]');
  await favoriteButton.click();
  await page.getByText('Статья сохранена в избранное.', { exact: true }).waitFor();
  assert.equal(await favoriteButton.getAttribute('aria-pressed'), 'true');
  assert.equal(articleFavoriteWrites.at(-1).method, 'PUT');
  assert.equal(articleFavoriteWrites.at(-1).headers['x-reader-csrf'], articleFavoriteCsrf);
  await favoriteButton.click();
  await page.getByText('Статья удалена из избранного.', { exact: true }).waitFor();
  assert.equal(await favoriteButton.getAttribute('aria-pressed'), 'false');
  assert.equal(articleFavoriteWrites.at(-1).method, 'DELETE');
  const pasted = await page.getByLabel('Комментарий', { exact: true }).evaluate(node => {
    const clipboard = new DataTransfer();
    clipboard.items.add(new File([new Uint8Array([137, 80, 78, 71])], 'screen.png', { type: 'image/png' }));
    const event = new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: clipboard });
    node.dispatchEvent(event); return event.defaultPrevented;
  });
  assert.equal(pasted, true, 'an image paste is handled by the attachment control');
  await page.getByText('Изображение готово и будет опубликовано вместе с комментарием.').waitFor();
  assert.equal(attachmentUploads, 1);
  await page.getByLabel('Комментарий', { exact: true }).fill('Скриншот из буфера');
  holdRefresh = true;
  await page.getByRole('button', { name: 'Опубликовать', exact: true }).click();
  await page.getByText('Комментарий опубликован.', { exact: true }).waitFor();
  await page.locator('.mc-comments__image').waitFor();
  assert.equal(writes.at(-1).attachmentId, attachmentId);
  await page.waitForFunction(() => document.querySelectorAll('.mc-comments__body').length >= 2);
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(heldRefreshes.length, 1, 'the reconciliation read is in flight after the published comment is already visible');
  holdRefresh = false;
  for (const response of heldRefreshes.splice(0)) response.end(JSON.stringify({ items: comments, nextCursor: null }));
  await page.getByRole('textbox', {name:'Комментарий'}).waitFor(); assert.equal(await page.locator('script').count(), initialScriptCount, 'untrusted comment body is text, not HTML');
  failPost=true; await page.getByLabel('Комментарий', { exact: true }).fill('Новый ответ'); await page.getByRole('button', {name:'Опубликовать', exact: true}).click(); await page.getByRole('button',{name:'Повторить отправку'}).waitFor(); assert.equal(await page.getByLabel('Комментарий', { exact: true }).isDisabled(),true); const original=writes.at(-1); failPost=false; await Promise.all([page.waitForResponse(response => response.request().method() === 'POST' && response.status() === 201), page.getByRole('button',{name:'Повторить отправку'}).click()]);
  await page.getByText('Комментарий опубликован.', {exact:true}).waitFor(); await page.locator('.mc-comments__body').filter({hasText:'Новый ответ'}).waitFor(); assert.equal(await page.locator('.mc-comments__pending').count(),0); const retried = writes.filter(write => write.operationId === original.operationId); assert.ok(retried.length >= 2); assert.ok(retried.every(write=>JSON.stringify(write)===JSON.stringify(original))); assert.equal(original.attachmentId, null); assert.equal(Object.hasOwn(original, 'publicConsent'), false); assert.match(original.operationId, /^[0-9a-f-]{36}$/i);
  await page.getByRole('button', {name:'Ответить'}).first().click(); await page.getByLabel('Комментарий', { exact: true }).fill('Реплика'); assert.equal(await page.getByRole('button', {name:'Отменить ответ'}).isVisible(), true);
  page.once('dialog', dialog => dialog.accept()); await Promise.all([page.waitForResponse(response => response.request().method() === 'DELETE'), page.getByRole('button',{name:'Удалить'}).first().click()]); assert.deepEqual(deletes.at(-1).body,{version:1}); assert.equal(deletes.at(-1).headers['x-reader-csrf'],'synthetic');
  await page.waitForFunction(() => !document.querySelector('[data-comments-attachment-picker]').disabled);
  await page.getByLabel('Комментарий', { exact: true }).focus(); await page.keyboard.press('Tab'); assert.equal(await page.locator('[data-comments-attachment-picker]').evaluate(node => node === document.activeElement), true); await page.keyboard.press('Tab'); if (await page.locator('[data-comments-refresh-profile]').isVisible()) { assert.equal(await page.locator('[data-comments-refresh-profile]').evaluate(node => node === document.activeElement), true); await page.keyboard.press('Tab'); } assert.equal(await page.locator('[data-comments-submit]').evaluate(node => node === document.activeElement), true); assert.ok(await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle !== 'none'));
  holdCommentBody=true; await page.reload(); await page.getByText('Не удалось загрузить комментарии. Повторите попытку позже.').waitFor({timeout:8000});
  console.log('comments-browser: pass');
} finally { await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
