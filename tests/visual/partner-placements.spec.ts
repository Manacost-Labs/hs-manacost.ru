import { expect, test, type BrowserContext } from '@playwright/test';
import { installFrontendObservers } from '../../ops/performance/browser-frontend-profile.mjs';

const creative = '<svg xmlns="http://www.w3.org/2000/svg" width="729" height="90"><rect width="729" height="90" fill="#123b5d"/></svg>';

async function isolate(context: BrowserContext, failed: string[] = []) {
  await context.route('**/*', async route => {
    const url = new URL(route.request().url());
    if (url.pathname.startsWith('/site-media/')) {
      if (failed.some(part => url.pathname.includes(part))) await route.abort('blockedbyclient');
      else await route.fulfill({ contentType: 'image/svg+xml', body: creative });
    } else if (/728x90\./.test(url.pathname)) {
      // The EasyList image rule that matched the former Playerok filename.
      await route.abort('blockedbyclient');
    } else if (['127.0.0.1', 'localhost'].includes(url.hostname)) await route.continue();
    else await route.abort();
  });
}

const rootSelector = '.site-partnership';

test('loaded creatives crossfade automatically without buttons and pause on focus/hover', async ({ page, context }) => {
  await isolate(context);
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.clock.install();
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const root = page.locator(rootSelector);
  const items = root.locator('.site-partnership__item');
  await expect(root).toHaveAttribute('data-enhanced', '');
  await expect(items.locator('img').first()).toHaveJSProperty('naturalWidth', 729);
  await expect(items.locator('img').nth(1)).toHaveJSProperty('naturalWidth', 729);
  await page.mouse.move(0, 500);
  await page.clock.fastForward(6100);
  await expect(items.nth(1)).toHaveClass(/is-active/);
  await expect(items.nth(1)).toHaveCSS('transition-property', 'opacity');
  await expect(items.nth(1)).toHaveCSS('transition-duration', '0.7s');
  await items.nth(1).focus();
  await page.clock.fastForward(13000);
  await expect(items.nth(1)).toHaveClass(/is-active/);
  await items.first().focus();
  await expect(items.first()).toHaveClass(/is-active/);
  await page.evaluate(() => (document.activeElement as HTMLElement)?.blur());
  // Hover pause is meaningful only for pointer devices.
  if (!(test.info().project.name.includes('mobile'))) {
    await items.first().hover();
    await page.clock.fastForward(13000);
    await expect(items.first()).toHaveClass(/is-active/);
  }
  await page.mouse.move(0, 500);
  await page.evaluate(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, value: true });
    document.dispatchEvent(new Event('visibilitychange'));
  });
  await page.clock.fastForward(13000);
  await expect(items.first()).toHaveClass(/is-active/);
  await page.evaluate(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, value: false });
    document.dispatchEvent(new Event('visibilitychange'));
  });
  await expect(root.getByRole('button')).toHaveCount(0);
  await page.clock.fastForward(6100);
  await expect(items.nth(1)).toHaveClass(/is-active/);
  await page.clock.fastForward(6100);
  await expect(items.first()).toHaveClass(/is-active/);
});

test('reduced motion starts paused and both links remain keyboard accessible', async ({ page, context }) => {
  await isolate(context);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.clock.install();
  await page.goto('/');
  const root = page.locator(rootSelector);
  await expect(root.getByRole('button')).toHaveCount(0);
  await page.clock.fastForward(13000);
  await expect(root.locator('.site-partnership__item').first()).toHaveClass(/is-active/);
  await root.locator('.site-partnership__item').first().focus();
  await page.keyboard.press('Tab');
  await expect(root.locator('.site-partnership__item').nth(1)).toBeFocused();
  await expect(root.locator('.site-partnership__item').nth(1)).toHaveCSS('opacity', '1');
});

for (const failed of [['primary'], ['primary', 'secondary']]) {
  test(`blocked images retain usable partners: ${failed.join(', ')}`, async ({ page, context }) => {
    await isolate(context, failed);
    await page.goto('/');
    const root = page.locator(rootSelector);
    const first = root.locator('.site-partnership__item').first();
    await expect(first.locator('img')).toHaveAttribute('data-failed', '');
    if (failed.length === 1) {
      await expect(root.locator('.site-partnership__item').nth(1)).toHaveClass(/is-active/);
    }
    await first.focus();
    await expect(first).toHaveCSS('opacity', '1');
    await expect(first.locator('.site-partnership__fallback')).toBeVisible();
    await expect(first).toHaveAttribute('href', 'https://bit.ly/hsmanacostru2110');
  });
}

test('slow second image never becomes an empty automatic slide', async ({ page, context }) => {
  await isolate(context);
  let release: () => void = () => {};
  const gate = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/site-media/secondary-mark/', async route => {
    await gate;
    await route.fulfill({ contentType: 'image/svg+xml', body: creative });
  });
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.clock.install();
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const items = page.locator('.site-partnership__item');
  await expect(items.first().locator('img')).toHaveJSProperty('naturalWidth', 729);
  await page.clock.fastForward(20000);
  await expect(items.first()).toHaveClass(/is-active/);
  release();
  await expect(items.nth(1).locator('img')).toHaveJSProperty('naturalWidth', 729);
  await page.clock.fastForward(6100);
  await expect(items.nth(1)).toHaveClass(/is-active/);
});

test('cosmetic image hiding reveals readable fallback without reinsertion loops', async ({ page, context }) => {
  await isolate(context);
  await page.goto('/');
  await page.addStyleTag({ content: '.site-partnership__item img { display: none !important; }' });
  const first = page.locator('.site-partnership__item').first();
  await expect(first.locator('.site-partnership__fallback')).toBeVisible();
  await expect(first).toHaveCSS('opacity', '1');
});

test('without JavaScript both partner links remain reachable', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, baseURL });
  await isolate(context);
  const page = await context.newPage();
  await page.goto('/');
  const items = page.locator('.site-partnership__item');
  await expect(items).toHaveCount(2);
  await expect(page.locator('.site-partnership__links').getByRole('link', { name: 'Sirus', exact: true })).toBeVisible();
  await items.nth(1).focus();
  await expect(items.nth(1)).toBeFocused();
  await expect(items.nth(1)).toBeInViewport();
  await expect(items.nth(1)).toHaveCSS('opacity', '1');
  await context.close();
});

// Include both Newspaper breakpoint boundaries and widths between them.
test('banner fits without controls at narrow, intermediate and landscape widths', async ({ page, context }, info) => {
  test.skip(info.project.name !== 'desktop-1440', 'Shared responsive matrix');
  await isolate(context);
  for (const width of [320, 390, 600, 767, 768, 900, 1018, 1019, 1024, 1280, 1440]) {
    await page.setViewportSize({ width, height: width >= 768 ? 600 : 844 });
    await page.goto('/');
    const root = page.locator(rootSelector);
    await expect(root.getByRole('button')).toHaveCount(0);
    const creativeBox = await root.locator('.site-partnership__items').boundingBox();
    expect(creativeBox!.x).toBeGreaterThanOrEqual(0);
    expect(creativeBox!.x + creativeBox!.width).toBeLessThanOrEqual(width + 1);
    if (width >= 768) {
      const box = await root.boundingBox();
      const menu = await page.locator('.td-header-menu-wrap-full').boundingBox();
      expect(box!.y + box!.height).toBeLessThanOrEqual(menu!.y);
    }
  }
});

test('delayed controller never relocates either creative after first paint', async ({ page, context }) => {
  await isolate(context);
  await context.addInitScript(installFrontendObservers);
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  let release: () => void = () => {};
  const gate = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/partner-placements.js*', async route => { await gate; await route.continue(); });
  await page.goto('/', { waitUntil: 'commit' });
  const root = page.locator(rootSelector);
  const items = root.locator('.site-partnership__item');
  await expect(items.first().locator('img')).toHaveJSProperty('naturalWidth', 729);
  await expect(items.nth(1).locator('img')).toHaveJSProperty('naturalWidth', 729);
  await page.waitForTimeout(300); // Let the progressive HTML/CSS paint before JS arrives.
  const rectangles = () => items.evaluateAll(links => links.map(link => {
    const { x, y, width, height } = link.getBoundingClientRect();
    return { x, y, width, height };
  }));
  const before = await rectangles();
  release();
  await expect(root).toHaveAttribute('data-enhanced', '');
  expect(await rectangles()).toEqual(before);
  await page.mouse.move(0, 500);
  await expect(items.nth(1)).toHaveClass(/is-active/, { timeout: 10000 });
  await expect(items.nth(1)).toHaveCSS('opacity', '1');
  const bannerShifts = await page.evaluate<number>(`window.__hsFrontend.shifts
    .filter(shift => shift.sources.some(source => source.classes.includes('site-partnership__item')))
    .reduce((total, shift) => total + shift.value, 0)`);
  expect(bannerShifts).toBe(0);
});


test('blocked controller leaves visible partner links usable with JavaScript enabled', async ({ page, context }) => {
  await isolate(context);
  await page.setViewportSize({ width: 320, height: 844 });
  await page.route('**/partner-placements.js*', route => route.abort('blockedbyclient'));
  await page.goto('/');
  const root = page.locator(rootSelector);
  await expect(root).not.toHaveAttribute('data-enhanced', '');
  const fallback = root.locator('.site-partnership__links');
  await expect(fallback.getByRole('link', { name: 'Playerok', exact: true })).toBeVisible();
  await expect(fallback.getByRole('link', { name: 'Sirus', exact: true })).toBeVisible();
  const headerBox = await root.boundingBox();
  for (const link of await fallback.getByRole('link').all()) {
    const box = await link.boundingBox();
    expect(box!.y).toBeGreaterThanOrEqual(headerBox!.y);
    expect(box!.y + box!.height).toBeLessThanOrEqual(headerBox!.y + headerBox!.height);
  }
});

test('late controller preserves keyboard focus on the selected partner', async ({ page, context }) => {
  await isolate(context);
  let release: () => void = () => {};
  const gate = new Promise<void>(resolve => { release = resolve; });
  await page.route('**/partner-placements.js*', async route => { await gate; await route.continue(); });
  await page.goto('/', { waitUntil: 'commit' });
  const root = page.locator(rootSelector);
  await root.locator('.site-partnership__links').getByRole('link', { name: 'Sirus', exact: true }).focus();
  release();
  await expect(root.locator('.site-partnership__item').nth(1)).toBeFocused();
  await expect(root.locator('.site-partnership__item').nth(1)).toHaveCSS('opacity', '1');
});
