import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const navigationCss = fs.readFileSync(path.resolve('wordpress/mu-plugins/manacost-site-navigation.css'), 'utf8');

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.addEventListener('pagereveal', (event: Event) => {
      const transition = (event as Event & { viewTransition?: { ready: Promise<void> } }).viewTransition;
      sessionStorage.setItem('navigation-transition', transition ? 'started' : 'none');
      transition?.ready.then(() => {
        sessionStorage.setItem('navigation-transition', 'ready');
        sessionStorage.setItem('navigation-composition', JSON.stringify({
          oldAnimation: getComputedStyle(document.documentElement, '::view-transition-old(root)').animationName,
          newBlend: getComputedStyle(document.documentElement, '::view-transition-new(root)').mixBlendMode,
        }));
      }).catch(() => {});
    });
  });
  await page.route('**/__page-transition-fixture/**', async route => {
    const pageName = new URL(route.request().url()).pathname.endsWith('/feed/') ? 'Лента колод' : 'Главная';
    await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>${pageName}</title><style>${navigationCss}</style><style>body{margin:0;min-height:2200px}nav{position:fixed;top:0;background:white}main{padding-top:80px}</style></head><body><nav><a href="../feed/">Лента колод</a></nav><main><h1>${pageName}</h1><p>Настоящий переход между двумя документами.</p></main></body></html>` });
  });
});

test('page navigation fades without replacing documents or breaking history', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.goto('/__page-transition-fixture/home/');
  const nativeTransitions = await page.evaluate(() => 'onpagereveal' in window);
  await page.evaluate(() => scrollTo(0, 500));
  await page.getByRole('link', { name: 'Лента колод' }).click();
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
  if (nativeTransitions) {
    await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe('ready');
    const composition = await page.evaluate(() => JSON.parse(sessionStorage.getItem('navigation-composition')!));
    expect(composition.oldAnimation).toBe('none');
    expect(composition.newBlend).toBe('normal');
  }
  await page.goBack();
  await expect(page.getByRole('heading', { name: 'Главная' })).toHaveText('Главная');
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(500);
  if (nativeTransitions) await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe('ready');
  await page.goForward();
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
});

test('a slow document keeps the outgoing page readable until navigation commits', async ({ page, browserName }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.goto('/__page-transition-fixture/home/');
  const session = browserName === 'chromium' ? await page.context().newCDPSession(page) : null;
  const capture = { clip: { x: 0, y: 80, width: page.viewportSize()!.width, height: 120, scale: 1 } };
  const initialFrame = session ? await session.send('Page.captureScreenshot', capture) : null;
  let release!: () => void;
  let arrived!: () => void;
  const responseGate = new Promise<void>(resolve => { release = resolve; });
  const requestGate = new Promise<void>(resolve => { arrived = resolve; });
  await page.route('**/__page-transition-fixture/feed/', async route => {
    arrived();
    await responseGate;
    await route.fallback();
  });
  const navigation = page.getByRole('link', { name: 'Лента колод' }).click({ noWaitAfter: true });
  try {
    await requestGate;
    // The outgoing JS context can freeze. Read the compositor without DOM calls.
    expect(page.url()).toContain('/__page-transition-fixture/home/');
    if (session) {
      const heldFrame = await session.send('Page.captureScreenshot', capture);
      expect(heldFrame.data).toBe(initialFrame!.data);
    }
  } finally {
    release();
  }
  await navigation;
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
});

test('reduced motion keeps navigation immediate and keyboard links usable', async ({ page, browserName }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/__page-transition-fixture/home/');
  const nativeTransitions = await page.evaluate(() => 'onpagereveal' in window);
  if (browserName === 'webkit') await page.getByRole('link', { name: 'Лента колод' }).focus();
  else await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Лента колод' })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
  await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe(nativeTransitions ? 'none' : null);
  expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
});

test('navigation works across narrow, intermediate and wide viewports and zoom', async ({ page, isMobile }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  for (const width of [320, 390, 530, 768, 900, 1024, 1440]) {
    await page.setViewportSize({ width, height: 844 });
    await page.goto('/__page-transition-fixture/home/');
    if (isMobile) await page.getByRole('link', { name: 'Лента колод' }).tap();
    else await page.getByRole('link', { name: 'Лента колод' }).click();
    await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
    await page.goBack();
    await expect(page.getByRole('heading', { name: 'Главная' })).toBeVisible();
  }
  await page.setViewportSize({ width: 390, height: 844 });
  await page.evaluate(() => { document.documentElement.style.zoom = '2'; });
  await page.getByRole('link', { name: 'Лента колод' }).focus();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
  await page.evaluate(() => { document.documentElement.style.zoom = '2'; });
  expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
});
