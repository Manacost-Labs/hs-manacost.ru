import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const navigationCss = fs.readFileSync(path.resolve('wordpress/mu-plugins/manacost-site-navigation.css'), 'utf8');

test.beforeEach(async ({ page }) => {
  await page.addInitScript(() => {
    window.addEventListener('pagereveal', (event: Event) => {
      const transition = (event as Event & { viewTransition?: { ready: Promise<void> } }).viewTransition;
      sessionStorage.setItem('navigation-transition', transition ? 'started' : 'none');
      transition?.ready.then(() => sessionStorage.setItem('navigation-transition', 'ready')).catch(() => {});
    });
  });
  await page.route('**/__page-transition-fixture/**', async route => {
    const pageName = new URL(route.request().url()).pathname.endsWith('/feed/') ? 'Лента колод' : 'Главная';
    await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>${pageName}</title><style>${navigationCss}</style><style>body{margin:0;min-height:2200px}nav{position:fixed;top:0;background:white}main{padding-top:80px}</style></head><body><nav><a href="../feed/">Лента колод</a></nav><main><h1>${pageName}</h1><p>Настоящий переход между двумя документами.</p></main></body></html>` });
  });
});

test('page navigation fades without replacing documents or breaking history', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.goto('/__page-transition-fixture/home/');
  await page.evaluate(() => scrollTo(0, 500));
  await page.getByRole('link', { name: 'Лента колод' }).click();
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
  await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe('ready');
  await page.goBack();
  await expect(page.getByRole('heading', { name: 'Главная' })).toHaveText('Главная');
  await expect.poll(() => page.evaluate(() => scrollY)).toBe(500);
  await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe('ready');
});

test('reduced motion keeps navigation immediate and keyboard links usable', async ({ page, browserName }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/__page-transition-fixture/home/');
  if (browserName === 'webkit') await page.getByRole('link', { name: 'Лента колод' }).focus();
  else await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Лента колод' })).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page.getByRole('heading', { name: 'Лента колод' })).toBeVisible();
  await expect.poll(() => page.evaluate(() => sessionStorage.getItem('navigation-transition'))).toBe('none');
  expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
});
