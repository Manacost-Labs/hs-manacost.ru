import assert from 'node:assert/strict';

export async function insertInlineImage(page) {
  await page.evaluate(() => {
    const editor = tinymce.get('content');
    const paragraph = [...editor.getBody().querySelectorAll('p')].find(el => el.textContent.includes('Первый абзац'));
    editor.selection.setCursorLocation(paragraph.firstChild, 0);
  });
  await page.getByRole('button', { name: 'Картинка в строке', exact: true }).click();
  const media = page.locator('.media-modal');
  await media.getByRole('tab', { name: /Upload files|Загрузить файлы/ }).click();
  await media.locator('input[type="file"]').setInputFiles({ name: 'inline-letter.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'base64') });
  await media.locator('.attachment.selected').waitFor();
  await media.locator('[data-setting="alt"] textarea, [data-setting="alt"] input').fill('Символ маны');
  await media.getByRole('button', { name: 'Вставить в строку', exact: true }).click();
  const image = page.frameLocator('#content_ifr').locator('p > img.hs-inline-image');
  await image.waitFor();
  assert.equal(await image.getAttribute('alt'), 'Символ маны');
  await verifyInlineImage(page.frameLocator('#content_ifr'));
  await page.evaluate(() => tinymce.get('content').undoManager.undo());
  assert.equal(await image.count(), 0, 'Native undo removes only the inline image');
  await page.evaluate(() => tinymce.get('content').undoManager.redo());
  await image.waitFor();
}

export async function verifyInlineImage(root) {
  const image = root.locator('p > img.hs-inline-image').first();
  await image.waitFor();
  const result = await image.evaluate(el => {
    const css = getComputedStyle(el), paragraph = el.parentElement;
    return { height: el.getBoundingClientRect().height, font: parseFloat(css.fontSize), display: css.display, text: paragraph.textContent };
  });
  assert.ok(Math.abs(result.height - result.font) < 1, 'Image tracks surrounding font height');
  assert.equal(result.display, 'inline-block');
  assert.match(result.text, /Первый абзац|До После/);
}

export async function insertBlockInlineImage(page, canvas) {
  await page.evaluate(() => wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/paragraph', { content: 'До После' })));
  const paragraph = canvas.locator('p[contenteditable="true"]').filter({ hasText: 'До После' });
  await paragraph.click();
  await page.keyboard.press('Home');
  const control = page.getByRole('button', { name: 'Картинка в строке', exact: true });
  if (!(await control.isVisible())) await page.getByRole('button', { name: 'More', exact: true }).click();
  await page.getByText('Картинка в строке', { exact: true }).click();
  const media = page.locator('.media-modal');
  await media.locator('.attachments .attachment').filter({ has: page.locator('img[src*="inline-letter"]') }).first().click();
  await media.locator('.media-button-select').click();
  await verifyInlineImage(canvas);
}
