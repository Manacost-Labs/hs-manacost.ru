import assert from 'node:assert/strict';

export async function verifyLinkPreviews(page, touch = false) {
  let requests = 0;
  await page.route('**/wp-json/hs-article-cards/v1/preview?**', async route => {
    requests++;
    const url = new URL(route.request().url());
    assert.equal(url.searchParams.get('url'), 'https://example.org/guide');
    assert.match(url.searchParams.get('signature'), /^[a-f0-9]{64}$/);
    await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ title: 'Предпросмотр источника', description: 'Краткое описание материала.', image: '' }) });
  });
  const link = page.getByRole('link', { name: 'Источник для предпросмотра', exact: true });
  assert.equal(requests, 0, 'No metadata calls before intent');
  assert.equal(await link.getAttribute('href'), 'https://example.org/guide');
  if (touch) await page.getByRole('button', { name: 'Предпросмотр: Источник для предпросмотра', exact: true }).tap();
  else await link.focus();
  const popup = page.getByRole('dialog', { name: 'Предпросмотр ссылки', exact: true });
  await popup.getByText('Предпросмотр источника', { exact: true }).waitFor();
  assert.equal(await popup.evaluate(el => el.getBoundingClientRect().left >= 0 && el.getBoundingClientRect().right <= innerWidth), true);
  await page.keyboard.press('Escape');
  assert.equal(await popup.isVisible(), false);
  if (touch) await page.getByRole('button', { name: 'Предпросмотр: Источник для предпросмотра', exact: true }).tap();
  else { await link.blur(); await link.focus(); }
  await popup.getByText('Предпросмотр источника', { exact: true }).waitFor();
  assert.equal(requests, 1, 'Repeated preview uses the client cache');
  await popup.getByRole('button', { name: 'Закрыть предпросмотр' }).click();
  assert.equal(await popup.isVisible(), false);
}
