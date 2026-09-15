import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = fileURLToPath(new URL('../../', import.meta.url));
const plugin = `${root}wordpress/mu-plugins/hs-manacost-lightbox/`;
const image = (label, width = 960, height = 640) => `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="${width}" height="${height}" fill="#152c39"/><text x="${width / 2}" y="${height / 2}" fill="#d7b77a" font-family="sans-serif" font-size="72" text-anchor="middle">${label}</text></svg>`;
const longCaption = 'Подробное описание изображения для проверки прокрутки на небольшом экране. '.repeat(28).trim();
const assets = new Map([
  ['/lightbox.css', ['text/css', readFileSync(`${plugin}lightbox.css`)]],
  ['/lightbox.js', ['text/javascript', readFileSync(`${plugin}lightbox.js`)]],
  ['/images/one.jpg', ['image/svg+xml', image('ONE')]],
  ['/images/two.webp', ['image/svg+xml', image('TWO')]],
  ['/images/bare.png', ['image/svg+xml', image('BARE')]],
  ['/images/portrait.jpg', ['image/svg+xml', image('PORTRAIT', 640, 960)]],
  ['/reader-api/v1/comments/comment-1/attachment', ['image/svg+xml', image('COMMENT')]],
]);

const pageHtml = `<!doctype html>
<html lang="ru"><head><meta name="viewport" content="width=device-width"><title>Lightbox fixture</title>
<style>img{max-width:100%;height:auto}</style>
<link rel="stylesheet" href="/lightbox.css"></head><body>
<article>
<div class="td-post-featured-image"><a id="featured-image" href="/images/portrait.jpg"><img class="entry-thumb td-modal-image" src="/images/portrait.jpg" width="640" height="960" alt="Обложка статьи"></a></div>
<div class="td-post-content tagdiv-type">
  <div class="wp-block-gallery">
    <figure><a class="td-modal-image" href="/images/one.jpg" data-caption="Первая карта"><img src="/images/one.jpg" width="960" height="640" alt="Первая карта"></a><figcaption>Первая карта</figcaption></figure>
    <figure><a href="/images/two.webp"><img src="/images/two.webp" width="960" height="640" alt="Вторая карта"></a><figcaption>Вторая карта</figcaption></figure>
  </div>
  <p><img id="bare-image" src="/images/bare.png" data-large-file="/images/bare.png" width="960" height="640" alt="Отдельная иллюстрация"></p>
  <figure><a id="long-caption-link" href="/images/portrait.jpg" data-caption="${longCaption}"><img src="/images/portrait.jpg" width="640" height="960" alt="Иллюстрация с длинной подписью"></a></figure>
  <a id="social-link" href="https://t.me/manacost_ru"><img src="/images/one.jpg" width="49" height="49" alt="Telegram"></a>
  <a id="excluded-link" data-no-lightbox href="/images/one.jpg"><img src="/images/one.jpg" width="960" height="640" alt="Не открывать"></a>
  <button id="image-control" type="button"><img src="/images/portrait.jpg" width="160" height="240" alt="Обычная кнопка с изображением"></button>
</div></article>
<section class="mc-comments__list" id="comments"></section>
<script>
window.themeLightboxOpens = 0;
window.imageControlClicks = 0;
document.addEventListener('click', event => {
  if (event.target.closest('.td-modal-image')) window.themeLightboxOpens += 1;
});
document.querySelector('#image-control').addEventListener('click', () => { window.imageControlClicks += 1; });
window.addCommentImage = () => {
  document.querySelector('#comments').insertAdjacentHTML('beforeend', '<article><a class="mc-comments__image-link" href="/reader-api/v1/comments/comment-1/attachment" target="_blank" rel="noopener" aria-label="Открыть изображение к комментарию"><img class="mc-comments__image" src="/reader-api/v1/comments/comment-1/attachment" width="960" height="640" alt="Изображение к комментарию"></a></article>');
};
</script>
<script>window.hsManacostLightboxConfig={title:'Просмотр изображения',close:'Закрыть',previous:'Предыдущее изображение',next:'Следующее изображение',openOriginal:'Открыть оригинал',loading:'Загрузка изображения',error:'Не удалось загрузить изображение.',imageCount:'%1$d из %2$d',openImage:'Открыть изображение: %s'};</script>
<script src="/lightbox.js" defer></script>
</body></html>`;

const server = createServer((request, response) => {
  if (request.url === '/') {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    response.end(pageHtml);
    return;
  }
  const asset = assets.get(request.url);
  if (!asset) {
    response.writeHead(404);
    response.end();
    return;
  }
  response.writeHead(200, { 'Content-Type': asset[0], 'Cache-Control': 'public, max-age=31536000, immutable' });
  response.end(asset[1]);
});

await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
let browser;

try {
  browser = await chromium.launch({
    headless: true,
    ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}),
  });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  await page.addInitScript(() => {
    window.lightboxLayoutReads = 0;
    window.lightboxPreloadAllocations = 0;
    const original = Element.prototype.getBoundingClientRect;
    Element.prototype.getBoundingClientRect = function (...args) {
      window.lightboxLayoutReads += 1;
      return original.apply(this, args);
    };
    window.Image = new Proxy(window.Image, {
      construct(Target, args) {
        window.lightboxPreloadAllocations += 1;
        return new Target(...args);
      },
    });
  });
  await page.goto(origin, { waitUntil: 'load' });

  assert.equal(await page.getByRole('dialog').count(), 0, 'dialog DOM is created lazily');
  assert.equal(await page.evaluate(() => window.lightboxLayoutReads), 0, 'initial enhancement performs no forced layout reads');
  assert.equal(await page.locator('#social-link').getAttribute('data-hs-lightbox-trigger'), null, 'small external icons are excluded');
  assert.equal(await page.locator('#excluded-link').getAttribute('data-hs-lightbox-trigger'), null, 'explicit opt-out is respected');
  assert.equal(await page.locator('#image-control img').getAttribute('data-hs-lightbox-trigger'), null, 'images used by controls are excluded');
  await page.locator('#image-control').click();
  assert.equal(await page.evaluate(() => window.imageControlClicks), 1, 'control keeps its original activation');
  assert.equal(await page.getByRole('dialog').count(), 0, 'control image does not create the lightbox');

  const featured = page.getByRole('link', { name: 'Обложка статьи' });
  assert.equal(await featured.getAttribute('data-hs-lightbox-trigger'), 'true', 'Newspaper featured images remain enhanced outside article content');
  await featured.focus();
  await featured.click();
  let dialog = page.getByRole('dialog', { name: 'Просмотр изображения' });
  await dialog.waitFor({ state: 'visible' });
  assert.equal(await dialog.getByRole('img', { name: 'Обложка статьи' }).getAttribute('src'), `${origin}/images/portrait.jpg`);
  assert.equal(await page.evaluate(() => window.themeLightboxOpens), 0, 'featured image does not open the Newspaper popup too');
  await page.keyboard.press('Escape');
  await dialog.waitFor({ state: 'hidden' });
  assert.equal(await featured.evaluate(element => document.activeElement === element), true, 'featured image focus is restored');

  const first = page.getByRole('link', { name: 'Первая карта' });
  for (const [label, options] of [
    ['Control-click', { modifiers: ['Control'] }],
    ['middle-click', { button: 'middle' }],
  ]) {
    const popupPromise = page.context().waitForEvent('page');
    await first.click(options);
    const popup = await popupPromise;
    await popup.waitForLoadState('domcontentloaded');
    assert.equal(new URL(popup.url()).pathname, '/images/one.jpg', `${label} keeps native link behavior`);
    assert.equal(await page.getByRole('dialog').count(), 0, `${label} does not create the lightbox`);
    await popup.close();
  }
  await page.evaluate(() => { window.themeLightboxOpens = 0; });

  await first.focus();
  await first.click();
  dialog = page.getByRole('dialog', { name: 'Просмотр изображения' });
  await dialog.waitFor({ state: 'visible' });
  assert.equal(await page.evaluate(() => window.themeLightboxOpens), 0, 'capture handler prevents the Newspaper popup from opening');
  assert.equal(await page.locator('html').getAttribute('data-hs-lightbox-open'), 'true');
  assert.equal(await dialog.getByRole('img', { name: 'Первая карта' }).getAttribute('src'), `${origin}/images/one.jpg`);
  assert.equal(await dialog.getByRole('img', { name: 'Первая карта' }).getAttribute('fetchpriority'), 'high');
	assert.equal(await dialog.getByRole('img', { name: 'Первая карта' }).getAttribute('draggable'), 'false');
	assert.equal(await dialog.locator('.hs-lightbox__stage').evaluate(element => getComputedStyle(element).userSelect), 'none');
  await dialog.locator('.hs-lightbox__image.is-ready').waitFor();
  assert.equal(await page.evaluate(() => window.lightboxPreloadAllocations), 1, 'a two-image gallery preloads its one neighbour once');
  assert.equal(await dialog.getByText('1 из 2').isVisible(), true);
  assert.equal(await dialog.getByText('Первая карта', { exact: true }).last().isVisible(), true);
  assert.equal(await dialog.getByRole('button', { name: 'Закрыть' }).evaluate(element => document.activeElement === element), true, 'close button receives focus');
  if (process.env.LIGHTBOX_SCREENSHOT) await page.screenshot({ path: process.env.LIGHTBOX_SCREENSHOT });

  await page.keyboard.press('ArrowRight');
  assert.equal(await dialog.getByRole('img', { name: 'Вторая карта' }).getAttribute('src'), `${origin}/images/two.webp`);
  assert.equal(await dialog.getByText('2 из 2').isVisible(), true);
	assert.equal(await page.evaluate(() => window.getSelection()?.isCollapsed ?? true), true, 'gallery navigation must not select the page');
  await page.keyboard.press('Escape');
  await dialog.waitFor({ state: 'hidden' });
  assert.equal(await first.evaluate(element => document.activeElement === element), true, 'focus returns to the opener');
  assert.equal(await page.locator('html').getAttribute('data-hs-lightbox-open'), null);

  const bare = page.locator('#bare-image');
  assert.equal(await bare.getAttribute('role'), 'button');
  assert.equal(await bare.getAttribute('tabindex'), '0');
  await bare.focus();
  await page.keyboard.press('Enter');
  await dialog.waitFor({ state: 'visible' });
  assert.equal(await dialog.getByRole('img', { name: 'Отдельная иллюстрация' }).getAttribute('src'), `${origin}/images/bare.png`);
  await dialog.locator('.hs-lightbox__stage').click({ position: { x: 24, y: 24 } });
  await dialog.waitFor({ state: 'hidden' });
  assert.equal(await bare.evaluate(element => document.activeElement === element), true, 'clicking outside the image restores focus');

  await page.evaluate(() => window.addCommentImage());
  await page.getByRole('link', { name: 'Открыть изображение к комментарию' }).click();
  await dialog.waitFor({ state: 'visible' });
  assert.equal(await dialog.getByRole('img', { name: 'Изображение к комментарию' }).getAttribute('src'), `${origin}/reader-api/v1/comments/comment-1/attachment`);
  await page.keyboard.press('Escape');

  await page.setViewportSize({ width: 320, height: 640 });
  await first.click();
  await dialog.waitFor({ state: 'visible' });
  for (const viewport of [
    { width: 320, height: 640 },
    { width: 390, height: 844 },
    { width: 768, height: 1024 },
    { width: 1024, height: 768 },
    { width: 1440, height: 900 },
  ]) {
    await page.setViewportSize(viewport);
    const geometry = await page.evaluate(() => {
      const dialog = document.querySelector('.hs-lightbox');
      const surface = dialog.querySelector('.hs-lightbox__surface');
      const image = dialog.querySelector('.hs-lightbox__image');
      const caption = dialog.querySelector('.hs-lightbox__caption');
      const controls = [...dialog.querySelectorAll('button')].map(button => button.getBoundingClientRect());
      return {
        dialogWidth: dialog.getBoundingClientRect().width,
        dialogRight: dialog.getBoundingClientRect().right,
        surfaceRight: surface.getBoundingClientRect().right,
        imageWidth: image.getBoundingClientRect().width,
        imageCaptionGap: caption.getBoundingClientRect().top - image.getBoundingClientRect().bottom,
        overflow: document.documentElement.scrollWidth - innerWidth,
        viewport: innerWidth,
        controls: controls.map(rect => ({ width: rect.width, height: rect.height })),
      };
    });
    assert.ok(geometry.overflow <= 0, `modal must not create horizontal overflow at ${viewport.width}px`);
    assert.ok(geometry.dialogWidth <= geometry.viewport && geometry.imageWidth <= geometry.viewport, JSON.stringify({ viewport, geometry }));
    assert.ok(Math.abs(geometry.viewport - geometry.dialogRight) <= 1 && Math.abs(geometry.viewport - geometry.surfaceRight) <= 1, `modal must paint through the full viewport at ${viewport.width}px`);
    assert.ok(geometry.imageCaptionGap <= 24, `caption stays attached at ${viewport.width}px: ${geometry.imageCaptionGap}`);
    assert.ok(geometry.controls.every(control => control.width >= 44 && control.height >= 44), `modal controls keep 44px targets at ${viewport.width}px`);
    if (viewport.width === 320 && process.env.LIGHTBOX_MOBILE_SCREENSHOT) await page.screenshot({ path: process.env.LIGHTBOX_MOBILE_SCREENSHOT });
  }
  await page.keyboard.press('Escape');

  await page.locator('#long-caption-link').click();
  await dialog.waitFor({ state: 'visible' });
  for (const viewport of [
    { width: 320, height: 640 },
    { width: 768, height: 512 },
    { width: 1440, height: 900 },
  ]) {
    await page.setViewportSize(viewport);
    const geometry = await dialog.locator('.hs-lightbox__caption').evaluate(element => {
      const caption = element.getBoundingClientRect();
      const image = element.parentElement.querySelector('.hs-lightbox__image').getBoundingClientRect();
      const modal = element.closest('.hs-lightbox').getBoundingClientRect();
      return {
        captionBottom: caption.bottom,
        imageBottom: image.bottom,
        imageCaptionGap: caption.top - image.bottom,
        captionTop: caption.top,
        modalBottom: modal.bottom,
        modalTop: modal.top,
        clientHeight: element.clientHeight,
        scrollHeight: element.scrollHeight,
      };
    });
    assert.ok(geometry.captionBottom <= geometry.modalBottom && geometry.captionTop >= geometry.modalTop, JSON.stringify({ viewport, geometry }));
    assert.ok(geometry.imageBottom <= geometry.captionTop, JSON.stringify({ viewport, geometry }));
    assert.ok(geometry.imageCaptionGap <= 24, `caption stays visually attached to its image at ${viewport.width}x${viewport.height}: ${geometry.imageCaptionGap}`);
    assert.ok(geometry.scrollHeight > geometry.clientHeight, `long portrait caption remains scrollable at ${viewport.width}x${viewport.height}`);
  }
  await page.keyboard.press('Escape');
  assert.deepEqual(pageErrors, []);
  console.log('content-lightbox-browser: pass');
} finally {
  if (browser) await browser.close();
  await new Promise(resolve => server.close(resolve));
}
