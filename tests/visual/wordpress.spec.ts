import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

const screenshotStyle = path.join(__dirname, 'screenshot.css');

async function stabilize(page: Page): Promise<void> {
  await page.addStyleTag({ path: screenshotStyle });
  await page.evaluate(async () => {
    if (document.fonts?.ready) await document.fonts.ready;
    window.scrollTo(0, 0);
  });
}

test.beforeEach(async ({ context }) => {
  await context.route('**/*', async route => {
    const url = new URL(route.request().url());
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
    await page.goto(target.path, { waitUntil: 'domcontentloaded' });
    await stabilize(page);
    await expect(page).toHaveScreenshot(`${target.name}.png`);
  });
}

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
  await stabilize(page);
  await expect(page).toHaveScreenshot('editor.png');
});
