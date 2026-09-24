import { chromium } from '@playwright/test';
import { writeFile } from 'node:fs/promises';

const port = Number(process.env.WP_TEST_PORT);
if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Expected disposable integration port');
const origin = `http://127.0.0.1:${port}`;
const browser = await chromium.launch();
try {
  const page = await browser.newPage();
  await page.route('**/*', route => new URL(route.request().url()).origin === origin ? route.continue() : route.abort());
  await page.goto(`${origin}/wp-login.php`);
  await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
  await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
  await page.locator('#wp-submit').click();
  await page.waitForURL(/\/wp-admin\//);
  await page.waitForFunction(() => Boolean(window.QueryMonitorData?.data));
  const result = await page.evaluate(() => {
    const panels = {};
    for (const [name, panel] of Object.entries(window.QueryMonitorData.data)) {
      if (!panel || typeof panel !== 'object') continue;
      panels[name] = {};
      for (const [key, value] of Object.entries(panel.data ?? {})) {
        if (typeof value === 'number' && Number.isFinite(value)) panels[name][key] = value;
        else if (Array.isArray(value)) panels[name][`${key}_count`] = value.length;
      }
    }
    return { panels, notice: 'Only numeric metrics/counts; no SQL text, cookies, nonces or credentials exported.' };
  });
  if (!Object.keys(result.panels).some(name => name.includes('db'))) throw new Error('Query Monitor database panel missing');
  await writeFile(process.argv[2], JSON.stringify(result, null, 2) + '\n', { mode: 0o600 });
  console.log(`Query Monitor collected ${Object.keys(result.panels).length} panels`);
} finally {
  await browser.close();
}
