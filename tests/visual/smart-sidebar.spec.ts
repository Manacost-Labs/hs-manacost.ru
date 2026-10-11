import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

// Exercise the exact Newspaper library enabled by the MU plugin. The normal
// isolated WP fixture omits commercial Composer, so supply only its environment.
async function sidebarFixture(page: Page, width: number, height = 1200, admin = false) {
  await page.setViewportSize({ width, height: 900 });
  await page.setContent(`<!doctype html><html lang="ru"><head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body { margin: 0; font: 16px/1.5 sans-serif; }
      header { height: 200px; }
      .td-pb-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px;
        max-width: 1068px; margin: auto; padding: 0 16px; }
      .td-main-sidebar { position: relative; min-width: 0; }
      .td-ss-main-content { height: 5000px; }
      .td-ss-main-sidebar { height: ${height}px; background: #f7f2e6; }
      .clearfix { clear: both; }
      a { display: block; padding: 16px; }
      footer { height: 400px; }
      @media (max-width: 767px) { .td-pb-row { display: block; } }
    </style></head><body class="${admin ? 'admin-bar' : ''}">
    <header>Манакост</header><div class="td-pb-row">
      <main><div class="td-ss-main-content">Содержание статьи</div></main>
      <aside class="td-main-sidebar"><div class="td-ss-main-sidebar">
        <a href="#support">Поддержите нашу команду</a><h2>VIP</h2>
        <a href="#guide">Мета-отчёт по Полям Сражений</a>
      </div></aside></div><footer>Футер</footer></body></html>`);
  await page.addStyleTag({ path: path.resolve('wordpress/mu-plugins/manacost-smart-sidebar.css') });
  await page.addScriptTag({ path: path.resolve('.artifacts/integration/site/wp-includes/js/jquery/jquery.js') });
  await page.evaluate(() => {
    const runtime = window as any;
    runtime.tdThemeName = 'Newspaper';
    runtime.tdUtil = { getBackendVar: () => '' };
    runtime.tdAffix = { is_menu_affix: false };
    runtime.tdViewport = {
      getCurrentIntervalIndex: () => window.innerWidth < 768 ? 0 : 3,
      getCurrentIntervalItem: () => ({}),
    };
  });
  await page.addScriptTag({ path: path.resolve('wordpress/plugins/td-composer/legacy/Newspaper/js/tdSmartSidebar.js') });
  await page.evaluate(() => {
    const runtime = window as any;
    const sidebar = new runtime.tdSmartSidebar.item();
    sidebar.sidebar_jquery_obj = runtime.jQuery('.td-ss-main-sidebar');
    sidebar.content_jquery_obj = runtime.jQuery('.td-ss-main-content');
    runtime.tdSmartSidebar.add_item(sidebar);
    runtime.tdSmartSidebar.td_events_resize();
    window.addEventListener('scroll', () => runtime.tdSmartSidebar.compute());
    window.addEventListener('resize', () => runtime.tdSmartSidebar.td_events_resize());
  });
}

test('long sidebar follows scrolling, remains reachable and stops before the footer', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  for (const width of [768, 900, 1024, 1280, 1440]) {
    await sidebarFixture(page, width);
    const sidebar = page.locator('.td-ss-main-sidebar');
    await expect(page.getByRole('link', { name: 'Поддержите нашу команду' })).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 1600));
    await expect(sidebar).toHaveCSS('position', 'fixed');
    const first = await sidebar.boundingBox();
    await page.evaluate(() => window.scrollTo(0, 2000));
    await expect.poll(async () => (await sidebar.boundingBox())?.y).toBeCloseTo(first!.y, 0);
    await page.getByRole('link', { name: 'Поддержите нашу команду' }).focus();
    await expect(page.getByRole('link', { name: 'Поддержите нашу команду' })).toBeFocused();
    await expect(page.getByRole('link', { name: 'Поддержите нашу команду' })).toBeInViewport();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Мета-отчёт по Полям Сражений' })).toBeFocused();
    await expect(page.getByRole('link', { name: 'Мета-отчёт по Полям Сражений' })).toBeInViewport();
    await page.evaluate(() => (document.activeElement as HTMLElement)?.blur());
    await page.evaluate(() => window.scrollTo(0, 4500));
    await expect(sidebar).toHaveCSS('position', 'absolute');
    const bounds = await sidebar.boundingBox();
    const footer = await page.locator('footer').boundingBox();
    expect(bounds!.y + bounds!.height).toBeLessThanOrEqual(footer!.y + 1);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(sidebar).toHaveCSS('position', 'static');
  }
  expect(errors).toEqual([]);
});

test('phone and 200% zoom layouts keep the whole sidebar in document flow', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  for (const width of [320, 390, 720]) {
    await sidebarFixture(page, width);
    await page.evaluate(() => window.scrollTo(0, 4500));
    await expect(page.locator('.td-ss-main-sidebar')).toHaveCSS('position', 'static');
    expect(await page.getByRole('link').count()).toBe(2);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  }
});

test('short sidebar follows at the top and accounts for the admin bar', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await sidebarFixture(page, 1440, 300, true);
  await page.evaluate(() => window.scrollTo(0, 1600));
  await expect(page.locator('.td-ss-main-sidebar')).toHaveCSS('position', 'fixed');
  await expect(page.locator('.td-ss-main-sidebar')).toHaveCSS('top', '32px');
});
