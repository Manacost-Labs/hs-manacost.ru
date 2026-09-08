import { expect, test, type Page } from '@playwright/test';

const intro = 'R-A-16113237-6';
const footer = 'R-A-16113237-5';
const floor = 'R-A-16113237-7';

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
  await expect(units).toHaveCount(2);
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
  expect(calls.map((call: any) => call.blockId).sort()).toEqual([intro, footer, floor].sort());
  expect(calls.find((call: any) => call.blockId === floor).platform).toBe('desktop');
  await expect(page.locator('script[src*="yandex.ru/ads/system/context.js"]')).toHaveCount(1);
}

test('both article placements survive reload, resize and slow SDK without duplicate calls', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  const release = await interceptSdk(page, '', true);
  await page.goto('/integration-article/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('[data-manacost-rsya-unit]')).toHaveCount(2);
  release();
  await verifyPlacements(page);
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await verifyPlacements(page);
  }
  // Running an emitted tag twice must not start a second auction for a slot.
  for (const script of await page.locator('script').allTextContents()) {
    if (script.includes('var container = document.getElementById("yandex_rtb_') || script.includes('window.manacostRsyaFloorQueued')) {
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

for (const empty of [intro, footer]) {
  test(`no-fill at ${empty} does not hide the other placement`, async ({ page }) => {
    await interceptSdk(page, empty);
    await page.goto('/integration-article/', { waitUntil: 'domcontentloaded' });
    const emptySlot = page.locator(`[data-manacost-rsya-unit]:has([id^="yandex_rtb_${empty}"])`);
    const filledSlot = page.locator(`[data-manacost-rsya-unit]:has([id^="yandex_rtb_${empty === intro ? footer : intro}"])`);
    await expect(emptySlot).toHaveAttribute('data-manacost-rsya-state', 'no-fill');
    await expect(emptySlot).toBeHidden();
    await expect(filledSlot).toHaveAttribute('data-manacost-rsya-state', 'rendered');
    await expect(filledSlot).toBeVisible();
  });
}

test('authenticated visitor gets both placements and the same desktop Floor Ad configuration', async ({ page }) => {
  await interceptSdk(page);
  const username = process.env.WP_TEST_ADMIN_USER;
  const password = process.env.WP_TEST_ADMIN_PASSWORD;
  if (!username || !password) throw new Error('Disposable integration credentials required');
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: 'Log In' }).click();
  await page.waitForURL(/\/wp-admin\//);
  await page.goto('/integration-article/', { waitUntil: 'domcontentloaded' });
  await verifyPlacements(page);
});
