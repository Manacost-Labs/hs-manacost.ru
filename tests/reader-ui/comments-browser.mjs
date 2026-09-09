import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdirSync, readFileSync } from 'node:fs';
import { chromium } from 'playwright';

// A local, synthetic boundary test: no WordPress runtime or reader data.
const root = new URL('../../', import.meta.url).pathname;
const plugin = `${root}wordpress/mu-plugins/hs-manacost-reader/`;
const php = "define('ABSPATH','/'); function esc_attr($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');} function esc_html__($v){return $v;} function get_the_ID(){return 7;} function get_permalink(){return 'https://example.test/article/';} function wp_parse_url($v,$part){return '/article/';} require $argv[1]; echo hs_reader_comments_shell();";
const shell = execFileSync('php', ['-r', php, `${plugin}comments.php`], { encoding: 'utf8' });
const assets = new Map([['/comments.js', readFileSync(`${plugin}comments.js`)], ['/comments.css', readFileSync(`${plugin}comments.css`)], ['/theme.css', readFileSync(`${root}wordpress/themes/Newspaper_new/style.css`)], ['/theme-boxed.css', readFileSync(`${root}wordpress/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css`)]]);
const id = '123e4567-e89b-42d3-a456-426614174000';
const author = { id, name: 'Маг <script>alert(1)</script>', bio: 'Люблю колоды', favoriteClass: 'mage', avatarVersion: 'a'.repeat(32), avatarUrl: `/reader-api/v1/readers/${id}/avatar?v=${'a'.repeat(32)}`, profileUrl: `/account/?reader=${id}`, paidSubscriber: true };
let comments = [{ id: '223e4567-e89b-42d3-a456-426614174000', postId: 7, parentId: null, status: 'published', version: 1, createdAt: Date.now(), body: '<img src=x onerror=alert(1)> Первый комментарий', author }];
let writes = [];
let holdCommentBody = false;
let failPost = false;
let deletes = [];
const server = createServer(async (req, res) => {
  if (assets.has(req.url)) { res.writeHead(200, {'content-type': req.url.endsWith('css') ? 'text/css' : 'text/javascript'}); return res.end(assets.get(req.url)); }
  if (req.url === '/') { res.writeHead(200, {'content-type':'text/html; charset=utf-8'}); return res.end(`<!doctype html><meta charset="utf-8"><meta name=viewport content="width=device-width"><link rel=stylesheet href=/theme.css><link rel=stylesheet href=/theme-boxed.css><link rel=stylesheet href=/comments.css><body class="td-boxed-layout"><header class="td-container-wrap"></header><main class="td-main-content-wrap td-container-wrap"><div class="td-container"><div class="td-page-content">${shell}</div></div></main><footer class="td-container-wrap"></footer><script src=/comments.js></script>`); }
	if (req.url.startsWith('/reader-api/v1/readers/')) { res.writeHead(200, {'content-type':'image/svg+xml'}); return res.end('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="#19313e"/><text x="20" y="27" text-anchor="middle" font-size="20" fill="#eef3f5">М</text></svg>'); }
  if (req.url === '/favicon.ico') { res.writeHead(204); return res.end(); }
  if (req.url === '/reader-api/v1/me') return res.end(JSON.stringify({ profile: { id, displayName: 'Я', bio: '', favoriteClass: 'mage', version: 1, avatarUrl: null }, csrfToken: 'synthetic' }));
  if (req.url.startsWith('/reader-api/v1/threads/7/comments') && req.method === 'GET') { if(holdCommentBody){res.writeHead(200,{'content-type':'application/json'});res.write('{"items":');return;} return res.end(JSON.stringify({items: comments, nextCursor: null})); }
  if (req.url === '/reader-api/v1/threads/7/comments' && req.method === 'POST') { let raw=''; for await (const chunk of req) raw += chunk; const body=JSON.parse(raw); writes.push(body); if(failPost)return req.socket.destroy(); const comment={id:'323e4567-e89b-42d3-a456-426614174000',postId:7,parentId:body.parentId,status:'published',version:1,createdAt:Date.now(),body:body.body,author:{...author,id,name:'Я',avatarUrl:null,avatarVersion:null,paidSubscriber:false}}; comments=[...comments,comment]; res.writeHead(201,{'content-type':'application/json'}); return res.end(JSON.stringify({comment})); }
	if (req.url === '/reader-api/v1/comments/223e4567-e89b-42d3-a456-426614174000' && req.method === 'DELETE') { let raw=''; for await (const chunk of req) raw += chunk; deletes.push({headers:req.headers,body:JSON.parse(raw)}); return res.end('{}'); }
  res.statusCode=404; res.end();
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
let browser;
try {
  if(process.env.READER_UI_SCREENSHOTS) mkdirSync(process.env.READER_UI_SCREENSHOTS,{recursive:true});
  browser = await chromium.launch({headless:true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {})}); const page = await browser.newPage(); page.setDefaultTimeout(3000);
  const consoleErrors=[]; page.on('console', message=>{if(message.type()==='error')consoleErrors.push(message.text())}); page.on('pageerror', error=>consoleErrors.push(error.message));
  for (const width of [320,390,560,768,1024,1440]) { await page.setViewportSize({width,height:800}); await page.goto(`http://127.0.0.1:${server.address().port}/`); await page.getByRole('heading', {name:'Комментарии'}).waitFor(); await page.locator('.mc-comments__body').waitFor(); await page.locator('.mc-comments__avatar').waitFor(); if(process.env.READER_UI_SCREENSHOTS) await page.screenshot({path:`${process.env.READER_UI_SCREENSHOTS}/comments-${width}.png`,fullPage:true}); assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false); }
  assert.deepEqual(consoleErrors, [], `fixture console errors: ${consoleErrors.join('; ')}`);
  // Emulate 200% desktop zoom: 1440 physical pixels / 2 = 720 CSS pixels.
  // This checks zoom-equivalent reflow, not browser-chrome zoom controls.
  const zoomContext = await browser.newContext({ viewport: { width: 720, height: 450 }, deviceScaleFactor: 2 });
  const zoomPage = await zoomContext.newPage();
  await zoomPage.goto(`http://127.0.0.1:${server.address().port}/`);
  await zoomPage.getByLabel('Комментарий', { exact: true }).waitFor();
  await zoomPage.locator('.mc-comments__body').waitFor();
  assert.equal(await zoomPage.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, '200% zoom-equivalent reflow must not overflow');
  assert.equal(await zoomPage.getByRole('checkbox', { name: /Согласен/ }).isVisible(), true);
  assert.equal(await zoomPage.getByRole('button', { name: 'Опубликовать', exact: true }).isVisible(), true);
  assert.equal(await zoomPage.getByRole('link', { name: author.name }).isVisible(), true);
  if (process.env.READER_UI_SCREENSHOTS) await zoomPage.screenshot({ path: `${process.env.READER_UI_SCREENSHOTS}/comments-zoom-200-emulated.png`, fullPage: true });
  await zoomContext.close();
  await page.getByRole('textbox', {name:'Комментарий'}).waitFor(); assert.equal(await page.locator('script').count(), 1, 'untrusted comment body is text, not HTML');
  failPost=true; await page.getByRole('checkbox', {name:/Согласен/}).check(); await page.getByLabel('Комментарий').fill('Новый ответ'); await page.getByRole('button', {name:'Опубликовать', exact: true}).click(); await page.getByRole('button',{name:'Повторить отправку'}).waitFor(); assert.equal(await page.getByLabel('Комментарий').isDisabled(),true); const original=writes.at(-1); failPost=false; await Promise.all([page.waitForResponse(response => response.request().method() === 'POST' && response.status() === 201), page.getByRole('button',{name:'Повторить отправку'}).click()]);
  await page.getByText('Комментарий опубликован.', {exact:true}).waitFor(); await page.locator('.mc-comments__body').filter({hasText:'Новый ответ'}).waitFor(); assert.equal(await page.locator('.mc-comments__pending').count(),0); assert.ok(writes.length >= 2); assert.ok(writes.every(write=>JSON.stringify(write)===JSON.stringify(original))); assert.equal(writes[0].publicConsent, true); assert.match(writes[0].operationId, /^[0-9a-f-]{36}$/i);
  await page.getByRole('button', {name:'Ответить'}).first().click(); await page.getByLabel('Комментарий').fill('Реплика'); assert.equal(await page.getByRole('button', {name:'Отменить ответ'}).isVisible(), true);
  page.once('dialog', dialog => dialog.accept()); await Promise.all([page.waitForResponse(response => response.request().method() === 'DELETE'), page.getByRole('button',{name:'Удалить'}).first().click()]); assert.deepEqual(deletes.at(-1).body,{version:1}); assert.equal(deletes.at(-1).headers['x-reader-csrf'],'synthetic');
  await page.getByLabel('Комментарий').focus(); await page.keyboard.press('Tab'); assert.equal(await page.locator('[data-comments-consent]').evaluate(node => node === document.activeElement), true); await page.keyboard.press('Tab'); assert.equal(await page.locator('[data-comments-submit]').evaluate(node => node === document.activeElement), true); assert.ok(await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle !== 'none'));
  holdCommentBody=true; await page.reload(); await page.getByText('Не удалось загрузить комментарии. Повторите попытку позже.').waitFor({timeout:8000});
  console.log('comments-browser: pass');
} finally { await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve)); }
