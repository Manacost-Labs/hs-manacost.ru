#!/usr/bin/env node

import { chromium } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const sampleCount = Number.parseInt(process.env.HS_ADMIN_PERF_SAMPLES ?? '5', 10);
if (!Number.isInteger(sampleCount) || sampleCount < 5 || sampleCount > 20) {
  throw new Error('HS_ADMIN_PERF_SAMPLES must be an integer from 5 to 20');
}

const username = process.env.WP_TEST_ADMIN_USER;
const password = process.env.WP_TEST_ADMIN_PASSWORD;
if (!username || !password) throw new Error('WordPress test credentials are missing');

const port = process.env.WP_TEST_PORT ?? '8888';
const baseURL = process.env.WP_TEST_BASE_URL ?? `http://127.0.0.1:${port}`;
const outputDirectory = path.resolve(
  process.argv[2] ?? '.artifacts/admin-performance/raw',
);
const datasetSize = Number.parseInt(process.env.WP_TEST_DATASET_SIZE ?? '1', 10);
if (!Number.isInteger(datasetSize) || datasetSize < 1) {
  throw new Error('WP_TEST_DATASET_SIZE must be a positive integer');
}

const httpUsername = process.env.STAGING_HTTP_USER;
const httpPassword = process.env.STAGING_HTTP_PASSWORD;
if (Boolean(httpUsername) !== Boolean(httpPassword)) {
  throw new Error('Both staging HTTP credential variables must be set together');
}

const browser = await chromium.launch({ headless: true });
const contextOptions = {
  viewport: { width: 1440, height: 900 },
  locale: 'ru-RU',
  timezoneId: 'Europe/Moscow',
  reducedMotion: 'reduce',
  ...(httpUsername && httpPassword
    ? { httpCredentials: { username: httpUsername, password: httpPassword } }
    : {}),
};

async function login(context) {
  const page = await context.newPage();
  await page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.getByLabel(/Username|Email|Имя пользователя/i).fill(username);
  await page.locator('#user_pass').fill(password);
  await page.getByRole('button', { name: /Log In|Войти/i }).click();
  await page.waitForURL(/\/wp-admin\//);
  await page.close();
}

async function addLongTaskObserver(context) {
  await context.addInitScript(() => {
    window.__hsLongTasks = 0;
    if ('PerformanceObserver' in window) {
      try {
        const observer = new PerformanceObserver(entries => {
          window.__hsLongTasks += entries.getEntries().length;
        });
        observer.observe({ type: 'longtask', buffered: true });
      } catch {
        window.__hsLongTasks = 0;
      }
    }
  });
}

async function discoverEditorPath(context) {
  const page = await context.newPage();
  await page.goto(`${baseURL}/wp-admin/edit.php`, { waitUntil: 'domcontentloaded' });
  const href = await page.locator('a.row-title').first().getAttribute('href');
  await page.close();
  return href ? `/wp-admin/${href.replace(/^.*\/wp-admin\//, '')}` : null;
}

async function measure(page, screen) {
  await page.goto(`${baseURL}${screen.path}`, { waitUntil: 'domcontentloaded' });
  await page.locator(screen.selector).first().waitFor({ state: 'visible' });
  await page.waitForTimeout(150);
  return page.evaluate(() => {
    const navigation = performance.getEntriesByType('navigation')[0];
    const server = window.__hsAdminPerformance;
    if (!navigation || !server) throw new Error('Admin performance probe is unavailable');
    return {
      ttfb_ms: Math.max(0, navigation.responseStart - navigation.requestStart),
      interactive_ms: Math.max(0, navigation.domInteractive - navigation.startTime),
      sql_queries: Number(server.sql_queries),
      peak_memory_mb: Number(server.peak_memory_mb),
      long_tasks: Number(window.__hsLongTasks ?? 0),
    };
  });
}

async function verifyMobile(screens) {
  const context = await browser.newContext({
    ...contextOptions,
    viewport: { width: 390, height: 844 },
  });
  await login(context);
  const page = await context.newPage();
  for (const screen of screens) {
    await page.goto(`${baseURL}${screen.path}`, { waitUntil: 'domcontentloaded' });
    await page.locator(screen.selector).first().waitFor({ state: 'visible' });
    const hasPageOverflow = await page.evaluate(() => {
      const content = document.querySelector('#wpbody-content');
      if (!content) return true;
      const bounds = content.getBoundingClientRect();
      return bounds.left < -1 || bounds.right > window.innerWidth + 1;
    });
    if (hasPageOverflow) throw new Error(`Horizontal page overflow on ${screen.name}`);
  }
  await context.close();
  return true;
}

async function verifyPermissionBoundary(screen) {
  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();
  await page.goto(`${baseURL}${screen.path}`, { waitUntil: 'domcontentloaded' });
  const redirectedToLogin = /\/wp-login\.php/.test(page.url());
  await context.close();
  if (!redirectedToLogin) throw new Error('Unauthenticated admin request was not rejected');
  return true;
}

async function verifyErrorPath() {
  const context = await browser.newContext(contextOptions);
  await login(context);
  const page = await context.newPage();
  const response = await page.goto(
    `${baseURL}/wp-admin/admin.php?page=hs-performance-probe-missing`,
    { waitUntil: 'domcontentloaded' },
  );
  const visibleText = (await page.locator('body').innerText()).trim();
  await context.close();
  if (!response || visibleText.length < 10) throw new Error('Admin error path is blank');
  return true;
}

try {
  await mkdir(outputDirectory, { recursive: true });
  const context = await browser.newContext(contextOptions);
  await addLongTaskObserver(context);
  await login(context);
  const editorPath = await discoverEditorPath(context);
  const screens = [
    { name: 'dashboard', path: '/wp-admin/index.php', selector: '#wpbody-content' },
    { name: 'posts-list', path: '/wp-admin/edit.php', selector: '.wp-list-table' },
    { name: 'media-library', path: '/wp-admin/upload.php', selector: '#wpbody-content' },
    ...(editorPath
      ? [{ name: 'post-editor', path: editorPath, selector: '#post' }]
      : []),
  ];
  const page = await context.newPage();
  const measurements = new Map(screens.map(screen => [screen.name, []]));
  for (let sample = 0; sample < sampleCount; sample += 1) {
    for (const screen of screens) {
      measurements.get(screen.name).push(await measure(page, screen));
    }
  }
  await context.close();

  const mobile = await verifyMobile(screens);
  const permissions = await verifyPermissionBoundary(screens[0]);
  const errorPath = await verifyErrorPath();
  for (const screen of screens) {
    const report = {
      schema_version: 1,
      environment: process.env.HS_ADMIN_PERF_ENVIRONMENT ?? 'integration',
      screen: screen.name,
      authenticated_role: 'administrator',
      dataset_size: datasetSize,
      cache_state: process.env.HS_ADMIN_PERF_CACHE_STATE ?? 'warm',
      viewport: 'desktop-1440',
      samples: measurements.get(screen.name),
      functional_checks: {
        behavior: true,
        permissions,
        desktop: true,
        mobile,
        error_path: errorPath,
      },
    };
    await writeFile(
      path.join(outputDirectory, `${screen.name}.json`),
      `${JSON.stringify(report, null, 2)}\n`,
      'utf8',
    );
  }
  process.stdout.write(`Admin performance evidence: ${screens.length} screen(s)\n`);
} finally {
  await browser.close();
}
