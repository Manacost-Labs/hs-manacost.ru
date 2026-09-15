import { expect, test, type Page } from '@playwright/test';

const introBanner = 'R-A-16113237-6';
const footerBanner = 'R-A-16113237-5';
const manualBanner = 'R-A-16113237-12';
const floorAd = 'R-A-16113237-7';
const manualArticlePath = '/rsya-manual-page/';

// Replace only the paid SDK at the HTTP boundary. No ad impressions or
// third-party measurement requests leave this disposable WordPress fixture.
async function interceptSdk(page: Page, emptyBlock = '', delayed = false) {
  page.on('pageerror', error => console.error('RSYA fixture page error:', error.message));
  let release!: () => void;
  const ready = new Promise<void>(resolve => { release = resolve; });
  const script = `
    window.rsyaTestCalls = [];
    window.Ya = { Context: { AdvManager: { render: function (options, noFill) {
      window.rsyaTestCalls.push({ blockId: options.blockId, renderTo: options.renderTo, platform: options.platform });
      if (options.blockId === ${JSON.stringify(emptyBlock)}) { noFill(); return; }
      if (options.renderTo) {
        const container = document.getElementById(options.renderTo);
        if (!container) { options.onError({type:'error', code:'CONTAINER_NOT_FOUND'}); return; }
        const creative = document.createElement('div');
        creative.textContent = 'Test creative ' + options.blockId;
        creative.style.cssText = 'width:100%;height:100%;background:#ddd';
        container.appendChild(creative);
      }
      options.onRender({ product: 'direct' });
    } } } };
    const queued = window.yaContextCb || [];
    window.yaContextCb = { push: callback => callback() };
    queued.forEach(callback => callback());
  `;
  await page.context().route('**/*', async route => {
    const url = new URL(route.request().url());
    if (url.hostname === 'yandex.ru' && url.pathname === '/ads/system/context.js') {
      if (delayed) await ready;
      await route.fulfill({ contentType: 'application/javascript', body: script });
    } else if (
      ['127.0.0.1', 'localhost'].includes(url.hostname)
      && url.pathname === '/reader-api/v1/ad-status'
    ) {
      await route.fulfill({
        contentType: 'application/json',
        headers: { 'Cache-Control': 'private, no-store' },
        body: JSON.stringify({ adFree: false }),
      });
    } else if (['127.0.0.1', 'localhost'].includes(url.hostname)) {
      await route.continue();
    } else {
      await route.abort();
    }
  });
  return release;
}

async function verifyPlacements(page: Page) {
  const units = page.locator('[data-manacost-rsya-unit]');
  await expect(units).toHaveCount(1);
  for (const unit of await units.all()) {
    await expect(unit).toHaveAttribute('data-manacost-rsya-state', 'rendered');
    await expect(unit).toBeVisible();
    expect(await unit.evaluate(element => element.closest('.mtp-spoiler-wrapper, .su-spoiler, details') === null)).toBe(true);
    const geometry = await unit.evaluate(element => {
      const rect = element.getBoundingClientRect();
      return { width: rect.width, height: rect.height, left: rect.left, right: rect.right, viewport: innerWidth };
    });
    expect(geometry.width).toBeGreaterThanOrEqual(160);
    expect(geometry.height).toBeGreaterThanOrEqual(50);
    expect(geometry.left).toBeGreaterThanOrEqual(0);
    expect(geometry.right).toBeLessThanOrEqual(geometry.viewport + 1);
  }
  await expect(page.locator('#manacost-rsya-floor-ad')).toHaveAttribute('data-manacost-rsya-state', 'rendered');
  const calls = await page.evaluate(() => (window as any).rsyaTestCalls);
  expect(calls.map((call: any) => call.blockId)).toEqual([manualBanner, floorAd]);
  await expect(page.locator('script[src*="yandex.ru/ads/system/context.js"]')).toHaveCount(1);
}

test('manual page placement survives reload, resize and slow SDK without duplicate calls', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  const release = await interceptSdk(page, '', true);
  await page.goto(manualArticlePath, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('[data-manacost-rsya-unit]')).toHaveCount(1);
  release();
  await verifyPlacements(page);
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await verifyPlacements(page);
  }
  // Running an emitted tag twice must not start a second auction for a slot.
  for (const script of await page.locator('script').allTextContents()) {
    if (script.includes('var container = document.getElementById("yandex_rtb_')) {
      await page.addScriptTag({ content: script });
    }
  }
  await verifyPlacements(page);
  for (let attempt = 0; attempt < 2; attempt++) {
    await page.reload({ waitUntil: 'domcontentloaded' });
    await verifyPlacements(page);
  }
  expect(errors).toEqual([]);
});

test(`no-fill at ${manualBanner} hides only the manual placement`, async ({ page }) => {
  await interceptSdk(page, manualBanner);
  await page.goto(manualArticlePath, { waitUntil: 'domcontentloaded' });
  const emptySlot = page.locator(`[data-manacost-rsya-unit]:has([id^="yandex_rtb_${manualBanner}"])`);
  await expect(emptySlot).toHaveAttribute('data-manacost-rsya-state', 'no-fill');
  await expect(emptySlot).toBeHidden();
  await expect(page.locator('#manacost-rsya-floor-ad')).toHaveAttribute('data-manacost-rsya-state', 'rendered');
});

test('authenticated non-subscriber gets the manual and Floor placements', async ({ page }) => {
  await interceptSdk(page);
  const username = process.env.WP_TEST_ADMIN_USER;
  const password = process.env.WP_TEST_ADMIN_PASSWORD;
  if (!username || !password) throw new Error('Disposable integration credentials required');
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: 'Log In' }).click();
  await page.waitForURL(/\/wp-admin\//);
  await page.goto(manualArticlePath, { waitUntil: 'domcontentloaded' });
  await verifyPlacements(page);
});

test('article without an editor block gets automatic inline and Floor placements', async ({ page }) => {
  await interceptSdk(page);
  await page.goto('/integration-article/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('[data-manacost-rsya-unit]')).toHaveCount(2);
  await expect(page.locator('[data-manacost-rsya-unit][data-manacost-rsya-state="rendered"]')).toHaveCount(2);
  await expect(page.locator('#manacost-rsya-floor-ad')).toHaveAttribute('data-manacost-rsya-state', 'rendered');
});

test('homepage and search load only the sitewide Floor placement', async ({ page }) => {
  await interceptSdk(page);
  for (const path of ['/', '/?s=integration']) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-manacost-rsya-unit]')).toHaveCount(0);
    await expect(page.locator('#manacost-rsya-floor-ad')).toHaveAttribute('data-manacost-rsya-state', 'rendered');
  }
});
