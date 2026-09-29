import { chromium } from '@playwright/test';
import { finishAdminLogin } from '../../ops/performance/browser-admin-login.mjs';
import { WordPressSource } from './source.js';

/** Staging's edge Basic Auth and WordPress login are independent boundaries. */
export async function openStagingSource(env = process.env) {
  const origin = 'https://test.hs-manacost.ru';
  if (env.MCP_SOURCE_ORIGIN !== origin) throw new Error('staging_only');
  const required = ['STAGING_HTTP_USER', 'STAGING_HTTP_PASSWORD', 'STAGING_DIAGNOSTIC_USER', 'STAGING_DIAGNOSTIC_PASSWORD'];
  if (required.some(key => !env[key])) throw new Error('staging_credentials_required');
  const browser = await chromium.launch({ headless: true,
    ...(env.PLAYWRIGHT_EXECUTABLE_PATH ? { executablePath: env.PLAYWRIGHT_EXECUTABLE_PATH } : {}),
    ...(env.PLAYWRIGHT_HOST_RESOLVER_RULES ? { args: [`--host-resolver-rules=${env.PLAYWRIGHT_HOST_RESOLVER_RULES}`] } : {}) });
  try {
    const context = await browser.newContext({ httpCredentials: { username: env.STAGING_HTTP_USER, password: env.STAGING_HTTP_PASSWORD } });
    const page = await context.newPage();
    const login = await page.goto(`${origin}/wp-login.php`, { waitUntil: 'domcontentloaded' });
    if (login?.status() !== 200 || !(await page.locator('#loginform').count())) {
      throw new Error(`staging_login_unavailable_${login?.status() ?? 'no_response'}`);
    }
    await page.getByLabel(/Username|Email|Имя пользователя/i).fill(env.STAGING_DIAGNOSTIC_USER);
    await page.locator('#user_pass').fill(env.STAGING_DIAGNOSTIC_PASSWORD);
    await page.getByRole('button', { name: /Log In|Войти/i }).click();
    await finishAdminLogin(page, origin);
    const source = new WordPressSource(origin, { transport: async (url, init) => {
      if (url.origin !== origin || !url.pathname.startsWith('/wp-json/wp/v2/')) throw new Error('staging_read_only');
      // Keep requests in Chromium: context.request does not use its origin DNS mapping.
      // No REST nonce: WordPress keeps the actual REST reads anonymous.
      const result = await page.evaluate(async ({ url, headers }) => {
        const response = await fetch(url, { headers, credentials: 'same-origin', redirect: 'error', signal: AbortSignal.timeout(8000) });
        const reader = response.body.getReader(); const chunks = []; let size = 0;
        try {
          while (true) {
            const { done, value } = await reader.read(); if (done) break;
            size += value.length; if (size > 8_000_000) throw new Error('source_response_too_large');
            chunks.push(value);
          }
        } finally { await reader.cancel(); }
        const body = new Uint8Array(size); let offset = 0;
        for (const chunk of chunks) { body.set(chunk, offset); offset += chunk.length; }
        return { status: response.status, body: new TextDecoder().decode(body),
          headers: Object.fromEntries(['content-type', 'x-wp-total'].filter(name => response.headers.has(name)).map(name => [name, response.headers.get(name)])) };
      }, { url: String(url), headers: init.headers });
      return new Response(result.body, { status: result.status, headers: result.headers });
    } });
    return { source, close: () => browser.close() };
  } catch (error) { await browser.close(); throw error; }
}
