#!/usr/bin/env node

import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { performance } from 'node:perf_hooks';

const baseURL = process.env.WP_TEST_BASE_URL;
if (!baseURL || new URL(baseURL).hostname !== 'test.hs-manacost.ru') {
  throw new Error('Article save diagnosis is restricted to staging');
}

const username = process.env.WP_TEST_ADMIN_USER;
const password = process.env.WP_TEST_ADMIN_PASSWORD;
const httpUsername = process.env.STAGING_HTTP_USER;
const httpPassword = process.env.STAGING_HTTP_PASSWORD;
if (!username || !password || !httpUsername || !httpPassword) {
  throw new Error('Protected staging credentials are required');
}

const outputDirectory = path.resolve(process.argv[2] ?? '.artifacts/admin-performance/reports');
const browserExecutable = process.env.PLAYWRIGHT_EXECUTABLE_PATH?.trim();
const hostResolverRules = process.env.PLAYWRIGHT_HOST_RESOLVER_RULES?.trim();
const browser = await chromium.launch({
  headless: true,
  ...(browserExecutable ? { executablePath: browserExecutable } : {}),
  ...(hostResolverRules ? { args: [`--host-resolver-rules=${hostResolverRules}`] } : {}),
});
const context = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  locale: 'ru-RU',
  httpCredentials: { username: httpUsername, password: httpPassword },
  extraHTTPHeaders: { 'X-HS-Admin-Perf-Probe': '1' },
});
const page = await context.newPage();
const samples = [];
const fixtureTitle = `Admin performance probe ${Date.now()}`;
let postId;
let cleaned = false;

const isSaveResponse = response => {
  const request = response.request();
  return request.method() === 'POST'
    && new URL(response.url()).pathname === '/wp-admin/post.php';
};

async function submitPublishButton() {
  const started = performance.now();
  let responseMs;
  const [response] = await Promise.all([
    page.waitForResponse(isSaveResponse, { timeout: 60_000 }).then(result => {
      responseMs = performance.now() - started;
      return result;
    }),
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60_000 }),
    page.locator('#publish').click(),
  ]);
  if (response.status() !== 302) {
    throw new Error(`Article save returned HTTP ${response.status()}`);
  }
  await page.locator('#title').waitFor({ state: 'visible' });
  const qmTime = response.headers()['x-qm-overview-time-taken'];
  const phaseHeader = response.headers()['x-hs-perf-phases'];
  return {
    response_ms: Math.round(responseMs),
    ready_ms: Math.round(performance.now() - started),
    qm_wp_time_ms: qmTime ? Math.round(Number.parseFloat(qmTime.replace(',', '.')) * 1000) : null,
    phases_ms: phaseHeader ? JSON.parse(phaseHeader) : null,
  };
}

async function removeFixture() {
  if (!postId) return false;
  await page.goto(`${baseURL}/wp-admin/post.php?post=${postId}&action=edit`);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.locator('#delete-action a.submitdelete').click(),
  ]);
  await page.goto(`${baseURL}/wp-admin/edit.php?post_status=trash&post_type=post`);
  const row = page.locator(`#post-${postId}`);
  await row.waitFor({ state: 'visible' });
  await row.hover();
  page.once('dialog', dialog => dialog.accept());
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    row.locator('.row-actions .delete a').click(),
  ]);
  return (await page.locator(`#post-${postId}`).count()) === 0;
}

try {
  await page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.getByLabel(/Username|Email|Имя пользователя/i).fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: /Log In|Войти/i }).click();
  await page.waitForURL(/\/wp-admin\//);

  await page.goto(`${baseURL}/wp-admin/post-new.php`, { waitUntil: 'domcontentloaded' });
  await page.locator('#title').fill(fixtureTitle);
  await page.locator('#content-html').click();
  await page.locator('#content').fill('Synthetic staging performance content. '.repeat(1750));
  await submitPublishButton();
  postId = Number(new URL(page.url()).searchParams.get('post'));
  if (!Number.isInteger(postId) || postId < 1) {
    throw new Error('Published fixture ID is unavailable');
  }

  for (let sample = 0; sample < 5; sample += 1) {
    const title = `${fixtureTitle} ${sample}`;
    await page.locator('#title').fill(title);
    samples.push(await submitPublishButton());
    if (await page.locator('#title').inputValue() !== title) {
      throw new Error('Saved title did not round-trip');
    }
  }
} finally {
  if (!postId) postId = Number(new URL(page.url()).searchParams.get('post')) || undefined;
  try {
    cleaned = await removeFixture();
  } finally {
    await mkdir(outputDirectory, { recursive: true });
    await writeFile(path.join(outputDirectory, 'save-article-diagnostic.json'), `${JSON.stringify({
      environment: 'staging',
      screen: 'published-article-save',
      authenticated_role: 'administrator',
      cache_state: 'warm',
      viewport: 'desktop-1440',
      samples,
      target_p95_ms: 1500,
      fixture_post_id: postId ?? null,
      fixture_cleaned: cleaned,
    }, null, 2)}\n`);
    await context.close();
    await browser.close();
  }
}

if (!cleaned || samples.length !== 5) {
  throw new Error('Article save diagnosis was incomplete or fixture cleanup failed');
}
const responses = samples.map(sample => sample.response_ms).sort((a, b) => a - b);
process.stdout.write(`Article save response: median ${responses[2]} ms, p95 ${responses[4]} ms; fixture cleaned\n`);
