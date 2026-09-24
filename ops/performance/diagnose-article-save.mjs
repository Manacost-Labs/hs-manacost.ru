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
});
await context.addInitScript(() => {
  window.__hsLongTasks = 0;
  try {
    new PerformanceObserver(entries => {
      window.__hsLongTasks += entries.getEntries().length;
    }).observe({ type: 'longtask', buffered: true });
  } catch {
    // Older browsers may not expose long tasks.
  }
});
const page = await context.newPage();
const samples = [];
const openSamples = [];
const fixtureTitle = `Admin performance probe ${Date.now()}`;
let postId;
let cleaned = false;
let savedContent = '';

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
  return {
    response_ms: Math.round(responseMs),
    ready_ms: Math.round(performance.now() - started),
    qm_wp_time_ms: qmTime ? Math.round(Number.parseFloat(qmTime.replace(',', '.')) * 1000) : null,
  };
}

async function measureEditorOpen(id) {
  const started = performance.now();
  const response = await page.goto(
    `${baseURL}/wp-admin/post.php?post=${id}&action=edit`,
    { waitUntil: 'domcontentloaded', timeout: 60_000 },
  );
  if (!response || response.status() !== 200) {
    throw new Error(`Article editor returned HTTP ${response?.status() ?? 'none'}`);
  }
  await page.locator('#content-html').waitFor({ state: 'visible' });
  const editorReadyMs = Math.round(performance.now() - started);
  const timings = await page.evaluate(() => {
    const navigation = performance.getEntriesByType('navigation')[0];
    const server = window.__hsAdminPerformance;
    if (!navigation || !server) throw new Error('Editor performance probe is unavailable');
    const resources = performance.getEntriesByType('resource');
    const scripts = resources.filter(resource => resource.initiatorType === 'script');
    const styles = resources.filter(resource => resource.initiatorType === 'link');
    const assetGroups = new Map();
    for (const resource of [...scripts, ...styles]) {
      const resourceURL = new URL(resource.name);
      const pathname = resourceURL.pathname;
      const parts = pathname.split('/');
      let owner = 'other';
      if (resourceURL.origin !== location.origin) {
        owner = 'external';
      } else if (pathname.startsWith('/wp-includes/') || pathname.startsWith('/wp-admin/')) {
        owner = 'wordpress-core';
      } else if (pathname.startsWith('/wp-content/plugins/')) {
        owner = /^[a-z0-9_-]{1,64}$/i.test(parts[3]) ? `plugin:${parts[3]}` : 'plugin:other';
      } else if (pathname.startsWith('/wp-content/mu-plugins/')) {
        owner = 'mu-plugins';
      } else if (pathname.startsWith('/wp-content/themes/')) {
        owner = 'theme';
      }
      const group = assetGroups.get(owner) ?? {
        owner, script_count: 0, style_count: 0,
        transfer_bytes: 0, encoded_body_bytes: 0, latest_end_ms: 0,
      };
      group[resource.initiatorType === 'script' ? 'script_count' : 'style_count'] += 1;
      group.transfer_bytes += resource.transferSize;
      group.encoded_body_bytes += resource.encodedBodySize;
      group.latest_end_ms = Math.max(group.latest_end_ms, Math.round(resource.responseEnd));
      assetGroups.set(owner, group);
    }
    const queryRows = window.QueryMonitorData?.data?.db_queries?.data?.rows;
    const callerTotals = new Map();
    if (queryRows && typeof queryRows === 'object') {
      for (const row of Object.values(queryRows)) {
        const seconds = Number(row?.ltime);
        if (!Number.isFinite(seconds) || seconds < 0) continue;
        const stack = Array.isArray(row?.stack) ? row.stack : [];
        const safeNames = stack.filter(name =>
          typeof name === 'string' && /^[A-Za-z_\\][A-Za-z0-9_\\:>\-]{0,79}$/.test(name));
        const caller = safeNames.find(name => /^(hs_|wf|td_|AIOSEO|WPRocket)/i.test(name))
          ?? safeNames[0] ?? 'other';
        const total = callerTotals.get(caller) ?? { caller, count: 0, total_ms: 0 };
        total.count += 1;
        total.total_ms += seconds * 1000;
        callerTotals.set(caller, total);
      }
    }
    return {
      ttfb_ms: Math.round(navigation.responseStart - navigation.requestStart),
      dom_interactive_ms: Math.round(navigation.domInteractive - navigation.startTime),
      sql_queries: Number(server.sql_queries),
      peak_memory_mb: Number(server.peak_memory_mb),
      long_tasks: Number(window.__hsLongTasks ?? 0),
      script_count: scripts.length,
      script_transfer_bytes: scripts.reduce((sum, resource) => sum + resource.transferSize, 0),
      style_count: styles.length,
      asset_groups: [...assetGroups.values()]
        .sort((left, right) => right.latest_end_ms - left.latest_end_ms)
        .slice(0, 12),
      sql_profile: {
        available: Boolean(queryRows),
        profiled_queries: [...callerTotals.values()].reduce((sum, item) => sum + item.count, 0),
        top_callers: [...callerTotals.values()]
          .sort((left, right) => right.total_ms - left.total_ms)
          .slice(0, 12)
          .map(item => ({ ...item, total_ms: Math.round(item.total_ms) })),
      },
    };
  });
  return { ...timings, editor_ready_ms: editorReadyMs };
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
  savedContent = await page.locator('#content').inputValue();
  if (!savedContent.includes('Synthetic staging performance content.')) {
    throw new Error('Published article content did not round-trip');
  }

  for (let sample = 0; sample < 5; sample += 1) {
    openSamples.push(await measureEditorOpen(postId));
    if (await page.locator('#content').inputValue() !== savedContent) {
      throw new Error('Article content changed while opening the editor');
    }
  }

  for (let sample = 0; sample < 5; sample += 1) {
    const title = `${fixtureTitle} ${sample}`;
    await page.locator('#title').fill(title);
    samples.push(await submitPublishButton());
    if (await page.locator('#title').inputValue() !== title) {
      throw new Error('Saved title did not round-trip');
    }
    if (await page.locator('#content').inputValue() !== savedContent) {
      throw new Error('Unchanged article content was modified during save');
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
    await writeFile(path.join(outputDirectory, 'open-article-diagnostic.json'), `${JSON.stringify({
      environment: 'staging',
      screen: 'published-article-open',
      authenticated_role: 'administrator',
      cache_state: 'warm',
      viewport: 'desktop-1440',
      fixture_bytes: Buffer.byteLength(savedContent ?? '', 'utf8'),
      samples: openSamples,
      fixture_cleaned: cleaned,
    }, null, 2)}\n`);
    await context.close();
    await browser.close();
  }
}

if (!cleaned || samples.length !== 5 || openSamples.length !== 5) {
  throw new Error('Article save diagnosis was incomplete or fixture cleanup failed');
}
const responses = samples.map(sample => sample.response_ms).sort((a, b) => a - b);
process.stdout.write(`Article save response: median ${responses[2]} ms, p95 ${responses[4]} ms; fixture cleaned\n`);
if (responses[4] > 1500) {
  throw new Error(`Article save p95 ${responses[4]} ms exceeds the 1500 ms budget`);
}
