import assert from 'node:assert/strict';
import { readFileSync, mkdirSync } from 'node:fs';
import { chromium } from 'playwright';

// The WordPress 6.9 constrained-layout rule has the same specificity as our
// base wrappers. Reproduce Blocksy's wide article shell, not a pre-sized div.
const core = `.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
  max-width: var(--wp--style--global--content-size); margin-left:auto!important; margin-right:auto!important;
}`;
const theme = `body { margin:0; font:16px/1.6 Arial; }
article { --wp--style--global--content-size:740px; --theme-block-width:90%; }
.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) { width:var(--theme-block-width); }
.hs-v-author { background:#f7f1e5; }`;
const css = ['manacost-tokens.css', 'components.css', 'card.css'].map(name => readFileSync(`wordpress/mu-plugins/hs-article-cards/${name}`, 'utf8')).join('\n');
const types = ['alert', 'spoiler', 'quote', 'tabs', 'table', 'author', 'divider', 'icon-list', 'steps', 'callout'];
const author = '<span class="hs-v-initials" aria-hidden="true">МА</span><div><p class="hs-v-author-name">Автор материала</p><p>Большой разбор Hearthstone: решения, наблюдения и советы для следующей игры.</p><a class="hs-v-social" href="https://example.org/author">Подписаться</a></div>';
const components = types.map(type => `<div class="hs-article-component hs-v-${type}" data-width="full">${type === 'author' ? author : 'Содержимое статьи'}</div>`).join('');
const cards = '<div class="hs-article-card">Отдельная карточка</div><div class="hs-article-cards"><div class="hs-article-card">Карточка подборки</div></div>';
const browser = await chromium.launch({ headless:true, ...(process.env.PLAYWRIGHT_EXECUTABLE_PATH ? { executablePath:process.env.PLAYWRIGHT_EXECUTABLE_PATH } : {}) });
const page = await browser.newPage();
mkdirSync('.artifacts/article-cards/screenshots', { recursive:true });
try {
  for (const order of ['plugin-last', 'theme-last']) {
    await page.setContent(`<style>${order === 'plugin-last' ? core + theme + css : css + core + theme}</style><article><div class="entry-content is-layout-constrained"><p id="measure">Обычный абзац статьи</p>${components}${cards}<div class="hs-article-component hs-v-alert" data-width="narrow">Узкий блок</div><div class="hs-article-cards hs-article-cards--width-narrow">Узкая подборка</div><div id="nested" style="max-width:400px"><div class="hs-article-component">Вложенный блок</div></div></div></article>`);
    for (const width of [320,390,480,768,900,1024,1440,1920]) {
      await page.setViewportSize({ width, height:1000 });
      for (const zoom of [1,2]) {
        await page.evaluate(zoom => { document.body.style.zoom = String(zoom); }, zoom);
        const result = await page.evaluate(() => {
          const bounds = element => { const r=element.getBoundingClientRect(); return { x:r.x,width:r.width,right:r.right }; };
          const content = document.querySelector('.entry-content');
          return { measure:bounds(document.querySelector('#measure')), blocks:[...content.querySelectorAll(':scope > .hs-article-component[data-width="full"], :scope > .hs-article-card, :scope > .hs-article-cards:not(.hs-article-cards--width-narrow)')].map(bounds), narrow:[...content.querySelectorAll(':scope > [data-width="narrow"], :scope > .hs-article-cards--width-narrow')].map(bounds), nested:bounds(document.querySelector('#nested .hs-article-component')), parent:bounds(document.querySelector('#nested')), overflow:document.documentElement.scrollWidth > innerWidth + 1 };
        });
        for (const block of result.blocks) {
          assert.ok(Math.abs(block.width-result.measure.width)<1, `${order}, ${width}px, zoom ${zoom}: component ${block.width}px differs from article ${result.measure.width}px`);
          assert.ok(Math.abs(block.x-result.measure.x)<1, 'Component aligns with article text');
        }
        for (const block of result.narrow) assert.ok(block.width <= Math.min(result.measure.width,560*zoom)+1, 'Narrow stays inside article');
        assert.ok(result.nested.width<=result.parent.width+1,'Nested stays inside its own container');
        assert.equal(result.overflow,false,'No page overflow');
      }
      await page.evaluate(()=>{document.body.style.zoom='';});
      if (order==='plugin-last' && [390,1440].includes(width)) {
        await page.locator('.hs-v-author').screenshot({ path:`.artifacts/article-cards/screenshots/width-author-${width}.png` });
      }
    }
  }
  // Newspaper/Classic editor have no constrained layout or global width token.
  await page.setContent(`<style>${css}</style><div style="width:300px">${components}</div>`);
  for (const block of await page.locator('.hs-article-component').all()) assert.ok((await block.boundingBox()).width<=300,'Classic container preserved');
  console.log('Constrained layout: 10 components, single/group cards, narrow/nested, both stylesheet orders, 8 widths and 200% zoom PASS');
} finally { await browser.close(); }
