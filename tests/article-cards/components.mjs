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
      assert.equal(await page.locator('#hs-v-preview [role=tab]').nth(1).getAttribute('aria-selected'), 'true', 'Preview follows the section being edited');
      await page.locator('#hs-v-density').selectOption('compact');
      assert.equal(await page.locator('#hs-v-preview [role=tab]').nth(1).getAttribute('aria-selected'), 'true', 'Formatting keeps the preview tab selected');
      await page.locator('#hs-v-density').selectOption('normal');
      await page.locator('#hs-v-add-section').click();
      await page.locator('#hs-v-sections fieldset').nth(2).waitFor();
      await page.locator('#hs-v-sections fieldset').nth(2).getByRole('button', { name: 'Удалить вкладку' }).click();
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
  await page.locator('#hs-v-type').selectOption('author');
  await page.locator('#hs-v-author-name').fill('Редакция Manacost');
  await page.locator('#hs-v-author-bio').fill('Гайды и разборы Hearthstone.');
  await page.locator('#hs-v-social-label').fill('Наш Telegram');
  await page.locator('#hs-v-social-url').fill('javascript:alert(1)');
  await page.locator('#hs-v-insert').click();
  assert.match(await page.locator('#hs-v-status').innerText(), /https/);
  await page.locator('#hs-v-social-url').fill('https://t.me/manacost');
  assert.equal(await page.locator('#hs-v-preview .hs-v-social').getAttribute('href'), 'https://t.me/manacost');
  await page.locator('#hs-v-insert').click();
  for (const type of ['divider', 'icon-list', 'steps', 'callout']) {
    await open();
    await page.locator('#hs-v-type').selectOption(type);
    await page.locator('#hs-v-title').fill('Оформление статьи');
    await page.locator('#hs-v-width').selectOption('narrow');
    await page.locator('#hs-v-density').selectOption('compact');
    if (type === 'divider') await page.locator('#hs-v-divider-style').selectOption('ornament');
    if (type === 'icon-list' || type === 'steps') {
      await page.locator('#hs-v-items').fill('Подготовьте колоду\nСыграйте пробный матч\nОцените результат');
      if (type === 'icon-list') await page.locator('#hs-v-icon').selectOption('star');
      await page.waitForFunction(() => document.querySelectorAll('#hs-v-preview li').length === 3);
      assert.equal(await page.locator('#hs-v-preview li').count(), 3);
    }
    if (type === 'callout') {
      await page.locator('#hs-v-body').fill('Больше разборов и полезных материалов.');
      await page.locator('#hs-v-action-label').fill('Читать разбор');
      await page.locator('#hs-v-action-url').fill('javascript:alert(1)');
      await page.locator('#hs-v-insert').click();
      assert.match(await page.locator('#hs-v-status').innerText(), /https/);
      await page.locator('#hs-v-action-url').fill('https://example.org/article');
      await page.locator('#hs-v-action-target').selectOption('_self');
      await page.locator('#hs-v-tone').selectOption('info');
    }
    if (visual) {
      await page.setViewportSize({ width: 390, height: 844 });
      await page.locator('#hs-v-preview').screenshot({ path: `.artifacts/article-cards/screenshots/decoration-${type}.png` });
    }
    await page.locator('#hs-v-insert').click();
  }
  // Reopen through each editor's existing UI and round-trip decoration settings.
  for (const [index, type] of ['divider', 'icon-list', 'steps', 'callout'].entries()) {
    const block = await page.evaluate(() => Boolean(window.hsArticleCards.blockEditor));
    if (block) {
      const canvas = await page.locator('iframe[name="editor-canvas"]').count() ? page.frameLocator('iframe[name="editor-canvas"]') : page;
      await canvas.getByRole('button', { name: 'Настроить компонент', exact: true }).nth(index + 6).click();
    } else {
      await page.evaluate(type => {
        const editor = tinymce.get('content');
        editor.focus(); editor.selection.select(editor.getBody().querySelector(`.hs-v-${type}`));
      }, type);
      await open();
    }
    assert.equal(await page.locator('#hs-v-type').inputValue(), type);
    assert.equal(await page.locator('#hs-v-title').inputValue(), 'Оформление статьи');
    assert.equal(await page.locator('#hs-v-width').inputValue(), 'narrow');
    if (type === 'divider') assert.equal(await page.locator('#hs-v-divider-style').inputValue(), 'ornament');
    if (type === 'icon-list') assert.equal(await page.locator('#hs-v-icon').inputValue(), 'star');
    if (type === 'steps') assert.match(await page.locator('#hs-v-items').inputValue(), /Подготовьте колоду/);
    if (type === 'callout') assert.equal(await page.locator('#hs-v-action-target').inputValue(), '_self');
    await page.locator('#hs-v-insert').click();
  }
  await open();
  await page.keyboard.press('Escape');
  assert.equal(await page.locator('.hs-components-open').evaluate(el => el === document.activeElement), true);
}

export async function verifyComponents(page, selector = '.td-post-content') {
  const scope = page.locator(selector);
  if (await scope.evaluate(el => el.classList.contains('is-layout-constrained'))) {
    const viewport = page.viewportSize();
    for (const width of [320, 390, 768, 1024, 1440]) {
      await page.setViewportSize({ width, height: 1000 });
      const fits = await scope.evaluate(el => {
        const paragraph = el.querySelector(':scope > p');
        if (!paragraph) return false;
        const measure = paragraph.getBoundingClientRect();
        return [...el.querySelectorAll(':scope > .hs-article-component, :scope > .hs-article-cards, :scope > .hs-article-card')].every(block => {
          const rect = block.getBoundingClientRect();
          return rect.width <= measure.width + 1 && rect.x >= measure.x - 1 && rect.right <= measure.right + 1;
        });
      });
      assert.ok(fits, `Components stay within the real theme's article measure at ${width}px`);
    }
    await page.setViewportSize(viewport);
  }
  assert.equal(await scope.locator('.hs-article-component').count(), 10);
  assert.equal(await scope.locator('.hs-v-divider').getAttribute('data-divider-style'), 'ornament');
  assert.equal(await scope.locator('.hs-v-divider-label').innerText(), 'Оформление статьи');
  assert.equal(await scope.locator('.hs-v-icon-list li').count(), 3);
  assert.equal(await scope.locator('.hs-v-icon-list').getAttribute('data-icon'), 'star');
  assert.equal(await scope.locator('.hs-v-steps ol > li').count(), 3);
  const action = scope.locator('.hs-v-callout .hs-v-action');
  assert.equal(await action.getAttribute('href'), 'https://example.org/article');
  assert.equal(await action.getAttribute('target'), null, 'Current-tab choice survives saving');
  assert.equal(await action.innerText(), 'Читать разбор');
  for (const type of ['divider', 'icon-list', 'steps', 'callout']) {
    const component = scope.locator(`.hs-v-${type}`);
    assert.equal(await component.getAttribute('data-width'), 'narrow');
    assert.equal(await component.getAttribute('data-density'), 'compact');
    assert.equal(await component.evaluate(el => el.scrollWidth <= el.clientWidth + 1), true);
  }
  const social = scope.locator('.hs-v-author .hs-v-social');
  assert.equal(await social.getAttribute('href'), 'https://t.me/manacost');
  assert.equal(await social.getAttribute('target'), '_blank');
  assert.match(await social.getAttribute('rel'), /noopener/);
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
