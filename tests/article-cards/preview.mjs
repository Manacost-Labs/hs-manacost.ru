import assert from 'node:assert/strict';

export async function verifyNativeLinks(page, touch = false) {
  let requests = 0;
  await page.route('**/wp-json/hs-article-cards/v1/preview?**', async route => {
    requests++;
    await route.abort();
  });
  const link = page.getByRole('link', { name: 'Источник для предпросмотра', exact: true });
  assert.equal(await link.getAttribute('href'), 'https://example.org/guide');
  assert.equal(await link.getAttribute('data-hs-preview'), null);
  if (!touch) await link.hover();
  await link.focus();
  await page.waitForTimeout(400); // Exceeds the removed intent delay.
  assert.equal(await page.locator('.hs-link-preview, .hs-link-peek').count(), 0);
  assert.equal(requests, 0, 'Ordinary links never fetch preview metadata');
  await page.context().route('https://example.org/guide', route => route.fulfill({ contentType: 'text/html', body: 'Native destination' }));
  const opened = page.waitForEvent('popup');
  if (touch) await link.tap(); else await link.click();
  const popup = await opened;
  await popup.waitForLoadState();
  assert.equal(popup.url(), 'https://example.org/guide');
  await popup.close();
}
