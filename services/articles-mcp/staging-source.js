import { chromium } from '@playwright/test';
import { finishAdminLogin } from '../../ops/performance/browser-admin-login.mjs';
import { WordPressSource } from './source.js';

/** Staging's edge Basic Auth and WordPress login are independent boundaries. */
export async function openStagingSource(env = process.env) {
  const origin = 'https://test.hs-manacost.ru';
  if (env.MCP_SOURCE_ORIGIN !== origin) throw new Error('staging_only');
  const required = ['STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD', 'STAGING_DIAGNOSTIC_USER', 'STAGING_DIAGNOSTIC_PASSWORD'];
  if (required.some(key => !env[key])) throw new Error('staging_credentials_required');
  const browser = await chromium.launch({ headless: true });
  try {
    const context = await browser.newContext({ httpCredentials: { username: env.STAGING_HTTP_USER, password: env.STAGING_HTTP_PASSWORD } });
    const page = await context.newPage();
    await page.goto(`${origin}/wp-login.php`, { waitUntil: 'domcontentloaded' });
    await page.getByLabel(/Username|Email|Имя пользователя/i).fill(env.STAGING_DIAGNOSTIC_USER);
    await page.locator('#user_pass').fill(env.STAGING_DIAGNOSTIC_PASSWORD);
    await page.getByRole('button', { name: /Log In|Войти/i }).click();
    await finishAdminLogin(page, origin); await page.close();
    const source = new WordPressSource(origin, { transport: async (url, init) => {
      if (url.origin !== origin || !url.pathname.startsWith('/wp-json/wp/v2/')) throw new Error('staging_read_only');
      // Share the browser session without exporting cookies or granting REST edit context.
      // No REST nonce: WordPress keeps the actual REST reads anonymous.
      const response = await context.request.get(String(url), { headers: init.headers, maxRedirects: 0, timeout: 8000 });
      try { return new Response(await response.body(), { status: response.status(), headers: response.headers() }); }
      finally { await response.dispose(); }
    } });
    return { source, close: () => browser.close() };
  } catch (error) { await browser.close(); throw error; }
}
