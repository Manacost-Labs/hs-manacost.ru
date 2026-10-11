// Opt-in: use the isolated integration stack and its controlled minute cron.
import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium, expect } from '@playwright/test';

assert.equal(process.env.HS_EDITOR_AI_TEST_DISPOSABLE, '1');
const phase = process.env.HS_EDITOR_AI_PERF_PHASE;
assert.ok(['before', 'after'].includes(phase));
const base = `http://127.0.0.1:${Number(process.env.WP_TEST_PORT || 8888)}`;
const post = Number(process.env.HS_EDITOR_AI_TEST_POST);
assert.ok(post > 0);
const browser = await chromium.launch();
const samples = [];
try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  await context.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort());
  const page = await context.newPage();
  page.setDefaultTimeout(90000);
  await page.goto(`${base}/wp-login.php`);
  await page.locator('#user_login').fill(process.env.WP_TEST_ADMIN_USER);
  await page.locator('#user_pass').fill(process.env.WP_TEST_ADMIN_PASSWORD);
  await page.getByRole('button', { name: 'Log In', exact: true }).click();
  await page.waitForURL('**/wp-admin/');
  const editorUrl = `${base}/wp-admin/post.php?post=${post}&action=edit`;
  await page.goto(editorUrl); // Warm-up, excluded from the five samples.
  await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
  for (let i = 0; i < 5; i++) {
    await page.goto(editorUrl);
    await page.waitForFunction(() => window.tinymce?.get('content')?.initialized);
    await page.evaluate(({ phase, i }) => {
      window.aiLongTasks = [];
      new PerformanceObserver(list => window.aiLongTasks.push(...list.getEntries().map(entry => entry.duration))).observe({ type: 'longtask' });
      const editor = window.tinymce.get('content');
      editor.setContent(`<p>Проверка ${phase} ${i}: карта стоит 3 мнаны.</p>`);
      editor.selection.setCursorLocation(editor.getBody().firstChild, 0);
      editor.save();
    }, { phase, i });
    const begin = await page.evaluate(() => performance.now());
    await page.locator('.hs-editor-ai-open[data-mode="proofread"]').click();
    const dialog = page.locator('#hs-editor-ai-dialog');
    await expect(dialog).toBeVisible();
    const openMs = await page.evaluate(start => performance.now() - start, begin);
    const responsePromise = page.waitForResponse(response => response.request().method() === 'POST' && response.url().endsWith('/hs-editor-ai/v1/jobs'));
    const started = await page.evaluate(() => performance.now());
    await dialog.getByRole('button', { name: 'Отправить текст', exact: true }).click();
    const response = await responsePromise;
    assert.equal(response.status(), 200);
    await expect(dialog.locator('#hs-editor-ai-status')).toContainText('Предложение готово', { timeout: 90000 });
    const timing = await page.evaluate(start => ({
      interactive_ms: performance.now() - start,
      ttfb_ms: performance.getEntriesByType('navigation')[0].responseStart,
      long_tasks: window.aiLongTasks.length,
    }), started);
    const headers = await response.allHeaders();
    timing.sql_queries = Number(headers['x-ai-test-queries']);
    timing.peak_memory_mb = Number(headers['x-ai-test-memory']);
    assert.ok(timing.sql_queries > 0 && timing.peak_memory_mb > 0);
    samples.push({ ...timing, dialog_open_ms: openMs });
    await dialog.getByRole('button', { name: 'Применить изменения' }).click();
    assert.match(await page.evaluate(() => window.tinymce.get('content').getContent()), /3 маны/);
    console.log(`Sample ${i + 1}: result ${Math.round(timing.interactive_ms)} ms, dialog ${Math.round(openMs)} ms`);
  }
  await mkdir('.artifacts/editor-ai-performance', { recursive: true });
  await writeFile(`.artifacts/editor-ai-performance/${phase}.json`, JSON.stringify({
    phase, environment: 'disposable WordPress 6.9.7, controlled 60-second cron',
    role: 'administrator in existing rollout', dataset_size: 1, cache: 'warm',
    viewport: '1440x900', network: 'loopback',
    provider: 'deterministic synthetic transport; no measurement of Claude inference', samples,
  }, null, 2));
} finally { await browser.close(); }
