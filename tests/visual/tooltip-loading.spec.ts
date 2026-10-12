import { expect, test } from '@playwright/test';
import path from 'node:path';

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l1cAAAAASUVORK5CYII=', 'base64');

test('approaching a card hides part of the image wait without bulk downloads', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop-1440');
  const delays: number[] = [];
  for (let sample = 0; sample < 5; sample++) {
    await page.goto('about:blank');
    let requests = 0;
    await page.route('https://tooltip.example/cards/**', async route => {
      requests++;
      await new Promise(resolve => setTimeout(resolve, 600));
      await route.fulfill({ contentType: 'image/png', body: png });
    });
    await page.setContent(`<!doctype html><html lang="ru"><head>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <style>body { margin: 100px; font: 16px Arial; } p { height: 40px; }</style>
      </head><body>${Array.from({ length: 30 }, (_, index) => `<p><span class="hs-card-tooltip"
        data-image="https://tooltip.example/cards/${sample}-${index}.png">Карта ${index}</span></p>`).join('')}</body></html>`);
    await page.addStyleTag({ path: path.resolve('wordpress/plugins/hs-tooltip/assets/hs-tooltip.css') });
    await page.addScriptTag({ path: path.resolve(process.env.HS_TOOLTIP_TEST_SCRIPT || 'wordpress/plugins/hs-tooltip/assets/hs-tooltip-v115.js') });
    await page.waitForTimeout(100);
    expect(requests).toBe(0);
    const label = page.getByText('Карта 0', { exact: true });
    const rect = (await label.boundingBox())!;
    await page.mouse.move(rect.x + rect.width + 50, rect.y + rect.height / 2);
    await page.waitForTimeout(300);
    expect(requests).toBe(1);
    await page.evaluate(() => {
      document.body.addEventListener('mouseover', event => {
        if ((event.target as Element).closest('.hs-card-tooltip')) {
          (window as any).hsHoverStarted = performance.now();
        }
      }, { capture: true });
    });
    await page.mouse.move(rect.x + rect.width / 2, rect.y + rect.height / 2);
    const image = page.getByAltText('Hearthstone card');
    const delay = await page.evaluate(() => new Promise<number>(resolve => {
      function check() {
        const img = document.querySelector<HTMLImageElement>('.hs-tooltip-box img');
        if (img?.complete && img.naturalWidth && !img.parentElement?.classList.contains('is-loading')) {
          resolve(performance.now() - (window as any).hsHoverStarted);
        } else requestAnimationFrame(check);
      }
      check();
    }));
    delays.push(Math.round(delay));
    await expect(image).toBeVisible();
    expect(requests).toBe(1);
    await expect(image).toHaveJSProperty('fetchPriority', 'high');
    expect(await label.boundingBox()).toEqual(rect);
    await page.unroute('https://tooltip.example/cards/**');
  }
  delays.sort((a, b) => a - b);
  // A cold 600ms response should have at least 100ms of its wait hidden by approach.
  expect(delays[2], `hover-to-image ms: ${delays.join(', ')}`).toBeLessThan(500);
});
