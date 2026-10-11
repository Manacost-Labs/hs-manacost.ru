import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

// Exercise our controller with the exact Newspaper library and markup. The
// isolated WP fixture omits commercial Composer, so supply only its environment.
async function sidebarFixture(page: Page, width: number, height = 1200, admin = false, composer = false) {
  await page.setViewportSize({ width, height: 900 });
  await page.setContent(`<!doctype html><html lang="ru"><head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body { margin: 0; font: 16px/1.5 sans-serif; }
      header { height: 200px; }
      .td-pb-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px;
        max-width: 1068px; margin: auto; padding: 0 16px; }
      .td-main-sidebar, .td-is-sticky { position: relative; min-width: 0; }
      .fixture-content { height: 5000px; }
      .fixture-sidebar { height: ${height}px; background: #f7f2e6; }
      .clearfix { clear: both; }
      a { display: block; padding: 16px; }
      footer { height: 400px; }
      @media (max-width: 767px) { .td-pb-row { display: block; } }
    </style></head><body class="${admin ? 'admin-bar' : ''}">
    <header>Манакост</header><div class="td-pb-row">
      <main><div class="fixture-content ${composer ? 'wpb_wrapper' : 'td-ss-main-content'}">Содержание статьи</div></main>
      <aside class="${composer ? 'vc_column td-is-sticky' : 'td-main-sidebar'}"><div class="fixture-sidebar ${composer ? 'wpb_wrapper' : 'td-ss-main-sidebar'}" data-sticky-offset="${composer ? 20 : 0}">
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
    sidebar.sidebar_jquery_obj = runtime.jQuery('.fixture-sidebar');
    sidebar.content_jquery_obj = runtime.jQuery('.fixture-content');
    sidebar.offset = Number(sidebar.sidebar_jquery_obj.attr('data-sticky-offset'));
    sidebar.enabled_on = [false, true, true, true];
    runtime.tdSmartSidebar.add_item(sidebar);
    runtime.tdSmartSidebar.td_events_resize();
    window.addEventListener('scroll', () => runtime.tdSmartSidebar.compute());
    window.addEventListener('resize', () => runtime.tdSmartSidebar.td_events_resize());
  });
  await page.addScriptTag({ path: path.resolve('wordpress/mu-plugins/manacost-smart-sidebar.js') });
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
    await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(-300);
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
    await expect(sidebar).toHaveCSS('position', 'relative');
    const bounds = await sidebar.boundingBox();
    const footer = await page.locator('footer').boundingBox();
    expect(bounds!.y + bounds!.height).toBeLessThanOrEqual(footer!.y + 1);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(200);
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
  expect((await page.locator('.td-ss-main-sidebar').boundingBox())!.y).toBe(32);
});

test('direction changes and the footer boundary never cause a delayed position jump', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  await sidebarFixture(page, 1440);
  await page.evaluate(() => window.scrollTo(0, 1600));
  await page.waitForTimeout(100);
  const jumps = await page.evaluate(async () => {
    const sidebar = document.querySelector('.td-ss-main-sidebar')!;
    const frames: number[] = [];
    let previous = { y: sidebar.getBoundingClientRect().y, scroll: scrollY };
    let frame = 0;
    const sample = () => {
      const current = { y: sidebar.getBoundingClientRect().y, scroll: scrollY };
      frames.push(Math.abs(current.y - previous.y) - Math.abs(current.scroll - previous.scroll));
      previous = current;
      frame = requestAnimationFrame(sample);
    };
    frame = requestAnimationFrame(sample);
    // Reverse near both pinning thresholds and the article's lower boundary.
    for (const y of [1580, 1500, 1400, 1300, 1200, 1210, 1200, 1300, 1400, 1500, 4700, 4750, 4760, 4750, 4740]) {
      scrollTo(0, y);
      await new Promise(resolve => setTimeout(resolve, 60));
    }
    cancelAnimationFrame(frame);
    return frames.filter(excess => excess > 2);
  });
  expect(jumps).toEqual([]);
});

test('late sidebar growth preserves position and resizing restores document flow', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  await sidebarFixture(page, 1440);
  const sidebar = page.locator('.td-ss-main-sidebar');
  await page.evaluate(() => scrollTo(0, 1600));
  await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(-300);
  await sidebar.evaluate(element => { element.style.height = '1500px'; });
  await page.waitForTimeout(100);
  expect((await sidebar.boundingBox())!.y).toBe(-300);
  for (const width of [390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await page.evaluate(() => scrollTo(0, 0));
    await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(width < 768 ? 5200 : 200);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
  }
  await page.locator('.td-ss-main-content').evaluate(element => { (element as HTMLElement).style.height = '600px'; });
  await page.waitForTimeout(100);
  await page.evaluate(() => scrollTo(0, 800));
  await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(200 - 800);
});

test('focusing a visible sidebar link does not move the panel or the page', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  await sidebarFixture(page, 1440, 300);
  const sidebar = page.locator('.td-ss-main-sidebar');
  await page.evaluate(() => scrollTo(0, 1600));
  await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(0);
  await page.getByRole('link', { name: 'Мета-отчёт по Полям Сражений' }).focus();
  await expect(page.getByRole('link', { name: 'Мета-отчёт по Полям Сражений' })).toBeInViewport();
  expect((await sidebar.boundingBox())!.y).toBe(0);
  expect(await page.evaluate(() => scrollY)).toBe(1600);
});


for (const height of [300, 1200]) {
  test(`the browser keeps a ${height}px pinned sidebar still while scroll handlers are delayed`, async ({ page }, info) => {
    test.skip(info.project.name !== 'desktop-1440');
    await sidebarFixture(page, 1440, height);
    await page.evaluate(() => scrollTo(0, 1600));
    await expect.poll(async () => (await page.locator('.td-ss-main-sidebar').boundingBox())?.y).toBe(Math.min(0, 900 - height));
    const client = await page.context().newCDPSession(page);
    const { root } = await client.send('DOM.getDocument');
    const { nodeId } = await client.send('DOM.querySelector', { nodeId: root.nodeId, selector: '.td-ss-main-sidebar' });
    const before = await client.send('DOM.getBoxModel', { nodeId });
    // Async scrolling continues on the compositor while page JavaScript is busy.
    await client.send('Emulation.setScriptExecutionDisabled', { value: true });
    try {
      await page.mouse.move(600, 400);
      await page.mouse.wheel(0, 180);
      await page.waitForTimeout(150);
      const after = await client.send('DOM.getBoxModel', { nodeId });
      expect(after.model.border[1]).toBeCloseTo(before.model.border[1], 0);
    } finally {
      await client.send('Emulation.setScriptExecutionDisabled', { value: false });
      await client.detach();
    }
  });
}

test('Composer column pairs use stable positioning and retain their configured offset', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  await sidebarFixture(page, 1440, 300, false, true);
  const sidebar = page.locator('.fixture-sidebar');
  await expect(sidebar).toHaveClass(/manacost-smart-sidebar/);
  expect(await page.evaluate(() => (window as any).tdSmartSidebar.items.length)).toBe(0);
  await page.evaluate(() => scrollTo(0, 1600));
  await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(20);
  await expect(sidebar).toHaveCSS('position', 'fixed');
  const before = await sidebar.boundingBox();
  await page.evaluate(() => scrollTo(0, 1580));
  await expect.poll(async () => (await sidebar.boundingBox())?.y).toBe(before!.y);
  await page.setViewportSize({ width: 390, height: 900 });
  await expect(sidebar).toHaveCSS('position', 'static');
  expect(await page.getByRole('link').count()).toBe(2);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});
