#!/usr/bin/env node
import { chromium, devices } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { collectFrontendProfile } from './browser-frontend-profile.mjs';

const origin = 'https://test.hs-manacost.ru';
const username = process.env.STAGING_HTTP_USER;
const password = process.env.STAGING_HTTP_PASSWORD;
if (!username || !password) throw new Error('Protected staging HTTP credentials required');
const directory = path.resolve(process.argv[2] ?? '.artifacts/admin-performance/reports');
await mkdir(directory, { recursive: true });
const browser = await chromium.launch({ headless: true,
  ...(process.env.PLAYWRIGHT_EXECUTABLE_PATH ? { executablePath: process.env.PLAYWRIGHT_EXECUTABLE_PATH } : {}),
  ...(process.env.PLAYWRIGHT_HOST_RESOLVER_RULES ? { args: [`--host-resolver-rules=${process.env.PLAYWRIGHT_HOST_RESOLVER_RULES}`] } : {}),
});
const allowed = new Set(['test.hs-manacost.ru', 'hs-manacost.ru', 'hs-manacost.com', 'fonts.googleapis.com', 'fonts.gstatic.com', 'api.kolodahs.ru', 'cards.hearthstonejson.com', 'blz-contentstack-assets.akamaized.net']);
const report = { environment: 'staging', state: 'anonymous', network: { latency_ms: 150, download_bytes_second: 200000, cpu_slowdown: 4 }, samples: [], layouts: [], errors: [] };
try {
  for (const routePath of ['/', '/page/2/', '/account/']) {
    for (let sample = 1; sample <= 5; sample++) {
      const context = await browser.newContext({ ...devices['Pixel 7'], viewport: { width: 390, height: 844 }, deviceScaleFactor: 3,
        isMobile: true, hasTouch: true, locale: 'ru-RU', serviceWorkers: 'block',
        httpCredentials: { username, password, origin } });
      try {
        await context.route('**/*', async route => {
          const request = route.request(); const url = new URL(request.url());
          if (!['GET', 'HEAD'].includes(request.method()) || !allowed.has(url.hostname)
            || /\/views\/hit|\/mca\/|manacost_monitoring|td_ajax_update.*views|\/wp-admin\/admin-ajax\.php/.test(request.url())) return route.abort();
          // Production hosts supply only static assets, never pages or APIs.
          if (url.origin !== origin && !['image', 'font', 'stylesheet', 'script'].includes(request.resourceType())) return route.abort();
          return route.continue();
        });
        await context.addInitScript(() => {
          window.__hsFrontend = { shifts: [], lcp: 0, longTasks: 0 };
          for (const [type, collect] of [
            ['layout-shift', entry => { if (!entry.hadRecentInput) window.__hsFrontend.shifts.push({ time: entry.startTime, value: entry.value }); }],
            ['largest-contentful-paint', entry => { window.__hsFrontend.lcp = entry.startTime; window.__hsFrontend.lcpEntry = { startTime: entry.startTime, url: entry.url,
              tag: entry.element?.tagName, kind: !entry.element ? 'detached' : !entry.url ? 'text' : entry.element.tagName === 'IMG' ? 'img' : 'background-image',
              loading: entry.element?.getAttribute('loading'), fetchpriority: entry.element?.getAttribute('fetchpriority') }; }],
            ['longtask', () => { window.__hsFrontend.longTasks++; }],
          ]) { try { new PerformanceObserver(list => list.getEntries().forEach(collect)).observe({ type, buffered: true }); } catch {} }
        });
        const page = await context.newPage();
        const cdp = await context.newCDPSession(page);
        await cdp.send('Network.enable');
        await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 200000, uploadThroughput: 75000 });
        await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
        const response = await page.goto(origin + routePath, { waitUntil: 'domcontentloaded', timeout: 90000 });
        await page.evaluate(() => Promise.race([document.fonts.ready, new Promise(resolve => setTimeout(resolve, 20000))]));
        if (routePath === '/account/') await page.waitForFunction(() => document.querySelector('.mc-reader__actions a'), undefined, { timeout: 30000 });
        await page.waitForTimeout(3000);
        const data = await page.evaluate(() => {
          const measured = window.__hsFrontend; let cls = 0, value = 0, start = 0, last = 0;
          for (const shift of measured.shifts) { if (shift.time - last < 1000 && shift.time - start < 5000) value += shift.value; else { value = shift.value; start = shift.time; } last = shift.time; cls = Math.max(cls, value); }
          const navigation = performance.getEntriesByType('navigation')[0];
          const images = [...document.images].filter(image => { const bounds = image.getBoundingClientRect(); return bounds.top < innerHeight && bounds.bottom > 0 && bounds.width > 0 && bounds.height > 0; });
          return { cls, lcp_ms: measured.lcp, long_tasks: measured.longTasks, ttfb_ms: navigation?.responseStart - navigation?.requestStart,
            overflow: document.documentElement.scrollWidth > innerWidth,
            failed_visible_images: images.filter(image => image.complete && !image.naturalWidth).length,
            pending_visible_images: images.filter(image => !image.complete).length,
            native_thumbnails: document.querySelectorAll('.manacost-native-thumbnail').length,
            fonts: [...document.fonts].filter(font => font.status === 'loaded').map(font => ({ family: font.family, weight: font.weight, style: font.style })),
            account_status_length: document.querySelector('.mc-reader__status')?.textContent?.length ?? 0,
            account_action_present: Boolean(document.querySelector('.mc-reader__actions a')) };
        });
        const headers = response?.headers() ?? {};
        const resourceProfile = await page.evaluate(collectFrontendProfile);
        const row = { ...resourceProfile, path: routePath, sample, status: response?.status(), robots: headers['x-robots-tag'] ?? '', cache_control: headers['cache-control'] ?? '', ...data };
        report.samples.push(row);
        if (row.status !== 200 || !/noindex/.test(row.robots) || row.cls > 0.1 || row.overflow || row.failed_visible_images || row.pending_visible_images) report.errors.push({ path: routePath, sample, reason: 'status/noindex/CLS/layout/image gate' });
        // Check additional widths with the already loaded assets.
        if (sample === 5) for (const width of [320, 390, 768, 1024, 1440]) {
          await page.setViewportSize({ width, height: 844 });
          await page.waitForTimeout(300);
          const layout = await page.evaluate(() => ({ width: innerWidth, overflow: document.documentElement.scrollWidth > innerWidth }));
          report.layouts.push({ path: routePath, ...layout });
          if (layout.overflow) report.errors.push({ path: routePath, width, reason: 'horizontal overflow' });
        }
      } catch (error) {
        // Error objects can contain request context; keep only the error class.
        report.errors.push({ path: routePath, sample, reason: error.name });
      } finally { await context.close(); }
      await writeFile(path.join(directory, 'frontend-slow-mobile.json'), JSON.stringify(report, null, 2));
    }
  }
} finally { await browser.close(); }
console.log(JSON.stringify({ samples: report.samples.length, layouts: report.layouts.length, failures: report.errors.length }));
if (report.samples.length !== 15 || report.errors.length) process.exitCode = 1;
