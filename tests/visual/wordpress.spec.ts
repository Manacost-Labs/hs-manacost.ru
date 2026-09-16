import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

const screenshotStyle = path.join(__dirname, 'screenshot.css');
const RESPONSIVE_WIDTHS = [320, 390, 768, 1024, 1440] as const;
const RESPONSIVE_PATHS = [
  '/',
  '/integration-article/',
  '/category/integration-category/',
] as const;

async function stabilize(page: Page): Promise<void> {
  await page.addStyleTag({ path: screenshotStyle });
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
    window.scrollTo(0, 0);
  });
}

async function removeDynamicEditorNotices(page: Page): Promise<void> {
  const autosaveNotice = page.locator('.notice-warning').filter({
    has: page.locator('a[href*="revision.php"]'),
  });
  await autosaveNotice.evaluateAll(notices => notices.forEach(notice => notice.remove()));
}

test.beforeEach(async ({ context }) => {
  await context.route('**/*', async route => {
    const url = new URL(route.request().url());
    const partner = url.pathname.endsWith('/728x90.jpg.webp')
      ? { label: 'PLAYEROK', color: '#123b5d' }
      : url.pathname.endsWith('/site-media/secondary-mark/') || url.pathname.endsWith('/728h90.png.webp')
        ? { label: 'SIRUS', color: '#35206e' }
        : null;
    if (partner) {
      await route.fulfill({
        status: 200,
        contentType: 'image/svg+xml',
        body: `<svg xmlns="http://www.w3.org/2000/svg" width="729" height="90" viewBox="0 0 729 90"><rect width="729" height="90" fill="${partner.color}"/><text x="364.5" y="56" fill="#fff" font-family="sans-serif" font-size="28" font-weight="700" text-anchor="middle">${partner.label}</text></svg>`,
      });
      return;
    }
    if (url.pathname.endsWith('/wp-content/uploads/2026/01/unnamed.png')) {
      await route.fulfill({
        status: 200,
        contentType: 'image/svg+xml',
        body: '<svg xmlns="http://www.w3.org/2000/svg" width="321" height="234" viewBox="0 0 321 234"><rect width="321" height="234" rx="18" fill="#d8b46b"/><text x="160.5" y="128" fill="#1d252b" font-family="serif" font-size="38" font-weight="700" text-anchor="middle">MANACOST</text></svg>',
      });
      return;
    }
    if (['127.0.0.1', 'localhost'].includes(url.hostname)) await route.continue();
    else await route.abort();
  });
});

for (const target of [
  { name: 'home', path: '/' },
  { name: 'article', path: '/integration-article/' },
  { name: 'category', path: '/category/integration-category/' },
]) {
  test(`public ${target.name} page`, async ({ page }) => {
    const response = await page.goto(target.path, { waitUntil: 'domcontentloaded' });
    expect(response?.status(), 'A screenshot of an error page is not a passing page test').toBe(200);
    await page.addStyleTag({ content: 'img[width="728"][height="90"] { display: none !important; }' });
    const partnership = page.getByRole('complementary', { name: 'Партнёры сайта' });
    await expect(partnership).toBeVisible();
    await expect(partnership.getByText('Реклама', { exact: true })).toHaveCount(0);
    const partnerLinks = partnership.locator('a[rel~="sponsored"]');
    await expect(partnerLinks).toHaveCount(2);
    await expect(partnerLinks.first()).toHaveCSS('border-radius', '10px');
    await expect(partnerLinks.first()).not.toHaveCSS('box-shadow', 'none');
    if ((page.viewportSize()?.width ?? 0) >= 768) {
      await expect(page.getByRole('link', { name: 'Манакост — главная' })).toBeVisible();
    }
    await expect(partnerLinks.first()).toHaveCSS('opacity', '1');
    await partnerLinks.first().focus();
    await expect(partnerLinks.first()).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(partnerLinks.nth(1)).toBeFocused();
    await expect(partnerLinks.nth(1)).toHaveCSS('opacity', '1');
    await expect(partnerLinks.nth(1).locator('img')).toBeVisible();
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    expect(await partnership.evaluate(element => Boolean(element.closest('.td-a-rec, .banner-rotator')))).toBe(false);
    const partnershipBackground = await partnership.evaluate(element => getComputedStyle(element).backgroundColor);
    if ((page.viewportSize()?.width ?? 0) >= 768) {
      const headerBox = await page.locator('.td-header-wrap').boundingBox();
      const partnershipBox = await partnership.boundingBox();
      expect(headerBox).not.toBeNull();
      expect(partnershipBox).not.toBeNull();
      expect(partnershipBackground).toBe('rgba(0, 0, 0, 0)');
      expect(partnershipBox!.y).toBeGreaterThanOrEqual(headerBox!.y);
      expect(partnershipBox!.y + partnershipBox!.height).toBeLessThanOrEqual(headerBox!.y + headerBox!.height);
    } else {
      expect(partnershipBackground).toBe('rgb(0, 40, 68)');
    }
    const openingPlacement = page.getByRole('complementary', { name: 'Реклама: Sirus' });
    if (target.name === 'article') {
      await expect(openingPlacement).toBeVisible();
      await expect(openingPlacement.getByText('Реклама', { exact: true })).toHaveCount(0);
      await expect(openingPlacement.locator('a')).toHaveCSS('border-radius', '10px');
      await expect(openingPlacement.locator('a')).not.toHaveCSS('box-shadow', 'none');
      await expect(openingPlacement.locator('img')).toBeVisible();
      await expect(openingPlacement.locator('img')).toHaveJSProperty('naturalWidth', 729);
    } else {
      await expect(openingPlacement).toHaveCount(0);
    }
    await stabilize(page);
    await expect(page).toHaveScreenshot(`${target.name}.png`);
  });
}

test('responsive overflow sweep', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-1440', 'Run the shared matrix once');

  for (const width of RESPONSIVE_WIDTHS) {
    await page.setViewportSize({ width, height: width <= 390 ? 844 : 1000 });
    for (const targetPath of RESPONSIVE_PATHS) {
      await page.goto(targetPath, { waitUntil: 'domcontentloaded' });
      await stabilize(page);

      const viewport = await page.evaluate(() => {
        const root = document.documentElement;
        const body = document.body;
        const offender = [...document.querySelectorAll<HTMLElement>('body *')].find(element => {
          const bounds = element.getBoundingClientRect();
          return bounds.left < -1 || bounds.right > root.clientWidth + 1;
        });
        const viewportMeta = document.querySelector<HTMLMetaElement>('meta[name="viewport"]')?.content ?? '';
        const maximumScalePart = viewportMeta
          .split(',')
          .map(part => part.trim().split('='))
          .find(([name]) => name?.toLowerCase() === 'maximum-scale');
        const parsedMaximumScale = maximumScalePart?.[1]
          ? Number(maximumScalePart[1])
          : null;
        return {
          overflow: Math.max(root.scrollWidth, body.scrollWidth) - root.clientWidth,
          offender: offender ? `${offender.tagName.toLowerCase()}#${offender.id}.${offender.className}` : null,
          viewportMeta,
          maximumScale: parsedMaximumScale !== null && Number.isFinite(parsedMaximumScale)
            ? parsedMaximumScale
            : null,
        };
      });

      expect(viewport.overflow, `${targetPath} at ${width}px overflows via ${viewport.offender}`).toBeLessThanOrEqual(1);
      expect(viewport.viewportMeta.toLowerCase()).not.toContain('user-scalable=no');
      if (viewport.maximumScale !== null) expect(viewport.maximumScale).toBeGreaterThanOrEqual(2);
    }
  }
});

async function login(page: Page): Promise<void> {
  const username = process.env.WP_TEST_ADMIN_USER;
  const password = process.env.WP_TEST_ADMIN_PASSWORD;
  if (!username || !password) throw new Error('Integration admin credentials are missing');
  await page.goto('/wp-login.php');
  await page.getByLabel('Username or Email Address').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: 'Log In' }).click();
  await page.waitForURL(/\/wp-admin\//);
}

test('authenticated desktop header survives cosmetic ad filtering', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-1440', 'Desktop Newspaper header only');

  await login(page);
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await page.addStyleTag({ content: '.td-banner-wrap-full { display: none !important; }' });

  const mastheadMark = page.getByRole('link', { name: 'Манакост — главная' });
  await expect(mastheadMark).toBeVisible();
  await expect(mastheadMark.locator('img')).toHaveJSProperty('naturalWidth', 321);

  const headerBox = await page.locator('.td-header-wrap').boundingBox();
  const menuBox = await page.locator('.td-header-menu-wrap-full').boundingBox();
  const partnershipBox = await page.getByRole('complementary', { name: 'Партнёры сайта' }).boundingBox();
  const contentBox = await page.locator('.td-main-content-wrap').boundingBox();

  expect(headerBox).not.toBeNull();
  expect(menuBox).not.toBeNull();
  expect(partnershipBox).not.toBeNull();
  expect(contentBox).not.toBeNull();
  expect(headerBox!.height).toBeGreaterThanOrEqual(222);
  expect(partnershipBox!.y + partnershipBox!.height).toBeLessThanOrEqual(menuBox!.y);
  expect(menuBox!.y + menuBox!.height).toBeLessThanOrEqual(contentBox!.y);
});

test('admin dashboard', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/index.php', { waitUntil: 'domcontentloaded' });
  await stabilize(page);
  await expect(page).toHaveScreenshot('admin.png');
});

test('article editor', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/edit.php', { waitUntil: 'domcontentloaded' });
  const editUrl = await page.locator('a.row-title', { hasText: 'Integration article' })
    .first()
    .getAttribute('href');
  if (!editUrl) throw new Error('Seed article edit link not found');
  await page.goto(editUrl, { waitUntil: 'domcontentloaded' });
  await removeDynamicEditorNotices(page);
  await expect(page.locator('.mce-btn button', { hasText: 'Реклама' })).toBeVisible();
  await stabilize(page);
  await expect(page).toHaveScreenshot('editor.png');
});

test('admin UI pattern library', async ({ page }) => {
  await login(page);
  await page.goto('/wp-admin/tools.php?page=hs-admin-ui-patterns', {
    waitUntil: 'domcontentloaded',
  });
  await expect(page.getByRole('heading', { name: 'UI-паттерны Manacost' })).toBeVisible();
  await stabilize(page);
  await expect(page).toHaveScreenshot('admin-ui-patterns.png');

  await page.locator('.hs-ui-open-dialog').click();
  const dialog = page.getByRole('dialog', { name: /Удалить черновик/ });
  await expect(dialog).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(page.locator('.hs-ui-open-dialog')).toBeFocused();

  const contentOverflows = await page.locator('.hs-ui-patterns').evaluate(element => {
    const bounds = element.getBoundingClientRect();
    return bounds.left < -1 || bounds.right > window.innerWidth + 1;
  });
  expect(contentOverflows).toBe(false);
});
