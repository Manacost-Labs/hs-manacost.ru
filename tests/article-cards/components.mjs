import assert from 'node:assert/strict';

// Shared by the disposable integration fixture and the owned staging draft.
export async function insertComponents(page, visual = false) {
  async function open() {
    if (!(await page.locator('.hs-components-open').isVisible())) {
      await page.getByRole('tab', { name: 'Post', exact: true }).click();
    }
    await page.locator('.hs-components-open').click();
  }
  const safe = await page.evaluate(() => window.hsManacostComponents.clean('<div class="hs-article-component hs-v-alert" onclick="alert(1)"><p style="position:fixed">Safe</p><script>alert(1)</script><img src=x onerror="alert(1)"></div>').outerHTML);
  assert.doesNotMatch(safe, /script|img|onclick|onerror|style=/);
  assert.match(safe, /Safe/);
  await open();
  await page.locator('#hs-v-insert').click();
  assert.match(await page.locator('#hs-v-status').innerText(), /Добавьте текст/);
  await page.locator('#hs-v-title').fill('Важно <script>alert(1)</script>');
  await page.locator('#hs-v-body').fill('Первый абзац.\nВторая строка.');
  await page.locator('#hs-v-tone').selectOption('warning');
  assert.equal(await page.locator('#hs-v-preview script').count(), 0);
  if (visual) {
    for (const width of [320, 390, 480, 768, 900, 1024, 1440]) {
      await page.setViewportSize({ width, height: 1000 });
      assert.equal(await page.locator('#hs-v-dialog').evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
    }
    await page.locator('#hs-v-title').fill('Важно');
    await page.screenshot({ path: '.artifacts/article-cards/screenshots/valyro-editor.png' });
    await page.locator('#hs-v-title').fill('Важно <script>alert(1)</script>');
    await page.locator('#hs-v-dialog').evaluate(el => { el.style.zoom = '2'; });
    assert.equal(await page.locator('#hs-v-dialog').evaluate(el => el.getBoundingClientRect().right <= window.innerWidth + 1), true);
    await page.locator('#hs-v-dialog').evaluate(el => { el.style.zoom = ''; });
  }
  await page.locator('#hs-v-insert').click();
  for (const type of ['spoiler', 'quote', 'tabs', 'table']) {
    await open();
    await page.locator('#hs-v-type').selectOption(type);
    if (type === 'spoiler') {
      await page.locator('#hs-v-title').fill('Подробности');
      await page.locator('#hs-v-body').fill('Скрытый текст спойлера.');
    } else if (type === 'quote') {
      await page.locator('#hs-v-body').fill('Текст цитаты.');
      await page.locator('#hs-v-author').fill('Автор цитаты');
    } else if (type === 'tabs') {
      await page.locator('#hs-v-sections input').nth(0).fill('Первый раздел');
      await page.locator('#hs-v-sections textarea').nth(0).fill('Содержимое первого раздела.');
      await page.locator('#hs-v-sections input').nth(1).fill('Второй раздел');
      await page.locator('#hs-v-sections textarea').nth(1).fill('Содержимое второго раздела.');
      await page.locator('#hs-v-add-section').click();
      await page.locator('#hs-v-sections button').last().click();
    } else {
      await page.locator('#hs-v-caption').fill('Сравнение колод');
      await page.locator('#hs-v-cells input').nth(0).fill('Колода');
      await page.locator('#hs-v-cells input').nth(1).fill('Стоимость');
      await page.locator('#hs-v-cells input').nth(2).fill('Маг');
      await page.locator('#hs-v-cells input').nth(3).fill('1600');
    }
    await page.locator('#hs-v-insert').click();
  }
  await open();
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('.hs-components-open').evaluate(el => el === document.activeElement), true);
}

export async function verifyComponents(page) {
  const scope = page.locator('.td-post-content');
  assert.equal(await scope.locator('.hs-article-component').count(), 5);
  assert.match(await scope.locator('.hs-v-alert').innerText(), /<script>alert\(1\)<\/script>/);
  const spoiler = scope.locator('details.hs-v-spoiler');
  await spoiler.locator('summary').click();
  assert.equal(await spoiler.getAttribute('open'), '');
  await spoiler.locator('summary').focus();
  await page.keyboard.press('Enter');
  assert.equal(await spoiler.getAttribute('open'), null);
  const tabs = scope.locator('.hs-v-tabs');
  await tabs.getByRole('tab').first().focus();
  await page.keyboard.press('ArrowRight');
  assert.equal(await tabs.getByRole('tab').nth(1).getAttribute('aria-selected'), 'true');
  assert.equal(await tabs.getByRole('tabpanel').filter({ visible: true }).count(), 1);
  assert.match(await tabs.getByRole('tabpanel').filter({ visible: true }).innerText(), /второго раздела/);
  await page.keyboard.press('Home');
  assert.equal(await tabs.getByRole('tab').first().getAttribute('aria-selected'), 'true');
  assert.equal(await scope.locator('table caption').innerText(), 'Сравнение колод');
  assert.equal(await scope.locator('table th[scope="col"]').count(), 2);
  assert.match(await scope.locator('.hs-v-quote').innerText(), /Автор цитаты/);
}
