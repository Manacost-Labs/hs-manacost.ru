import { chromium } from 'playwright';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import assert from 'node:assert/strict';
import {measurePicker} from './performance.mjs';
const env=Object.fromEntries(readFileSync('.artifacts/integration/runtime.env','utf8').split('\n').filter(l=>l.includes('=')).map(l=>[l.slice(0,l.indexOf('=')),l.slice(l.indexOf('=')+1)]));
const fixture=JSON.parse(readFileSync('.artifacts/api-gallery/integration-report.json','utf8'));
const base=`http://127.0.0.1:${env.WP_TEST_PORT}`;
const browser=await chromium.launch({headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000}});
// Stub only external image transport; the picker, media import, SQL and editor run unchanged.
async function providerImages(target) {
 await target.route('https://api.kolodahearthstone.com/uploads/hs-gallery-fixture/*',route=>route.fulfill({status:200,contentType:'image/png',body:readFileSync('.artifacts/integration/site/wp-content/uploads/hs-gallery-fixture.png')}));
 await target.route('https://hearthstone.wiki.gg/wiki/Special:Redirect/file/TEST_DIAMOND_*',route=>route.fulfill({status:301,headers:{location:route.request().url().replace('/wiki/Special:Redirect/file/','/images/'),'cross-origin-resource-policy':'same-origin'}}));
 await target.route('https://hearthstone.wiki.gg/images/TEST_DIAMOND_*',route=>route.fulfill({status:200,contentType:'image/png',body:readFileSync('.artifacts/integration/site/wp-content/uploads/hs-gallery-fixture.png')}));
}
await providerImages(context);
const page=await context.newPage();
const report={errors:[],layouts:[],request_errors:[]};
mkdirSync('.artifacts/api-gallery/screenshots',{recursive:true});
page.on('pageerror',e=>report.errors.push(e.message.slice(0,200)));
function observeResponses(target) {
 target.on('response',async response=>{
  if(response.status()>=400&&response.url().includes('/admin-ajax.php')) {
   let message='';try{message=(await response.json()).data?.message||'';}catch{}
   report.request_errors.push({status:response.status(),message:message.slice(0,160)});
  }
 });
}
observeResponses(page);
const content=()=>page.evaluate(()=>tinymce.get('content').getContent());
const initialized=()=>page.waitForFunction(()=>window.tinymce?.get('content')?.initialized);
try {
 await page.goto(`${base}/wp-login.php`);
 await page.waitForFunction(()=>document.activeElement?.id==='user_login');
 await page.getByLabel('Username or Email Address').fill(env.WP_TEST_ADMIN_USER);
 await page.locator('#user_pass').fill(env.WP_TEST_ADMIN_PASSWORD);
 await page.getByRole('button',{name:'Log In',exact:true}).click();
 await page.waitForURL(/\/wp-admin\//);
 await page.goto(`${base}/wp-admin/post.php?post=${fixture.post_id}&action=edit`);
 await initialized();
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-results input[type=checkbox]').first().waitFor();
 await page.getByText('Найдено: 2',{exact:true}).waitFor();
 assert.equal(await page.locator('#hs-api-gallery-more').isVisible(),false,'No redundant pagination after last page');
 await measurePicker(page);
 for(const width of [1440,1024,768,480,390,320]) {
  await page.setViewportSize({width,height:1000});
  await page.waitForFunction(()=>[...document.querySelectorAll('#hs-api-gallery-results img')].every(image=>image.complete&&image.naturalWidth>0));
  const bounds=await page.locator('#hs-api-gallery-dialog').evaluate(d=>({width:d.clientWidth,scroll:d.scrollWidth,left:d.getBoundingClientRect().left,right:d.getBoundingClientRect().right}));
  assert.ok(bounds.scroll<=bounds.width+1&&bounds.left>=0&&bounds.right<=width,`Picker fits ${width}`);
  report.layouts.push({surface:'picker',width,...bounds});
  await page.screenshot({path:`.artifacts/api-gallery/screenshots/picker-${width}.png`});
 }
 for(const viewport of [{width:390,height:844},{width:320,height:568},{width:768,height:390}]) {
  await page.setViewportSize(viewport);
  const catalog=page.locator('.hs-api-gallery__catalog');
  assert.ok((await catalog.boundingBox()).height>=160,'Short viewports keep a usable card catalog');
  for(const selector of ['#hs-api-gallery-query','#hs-api-gallery-results input[type=checkbox]','#hs-api-gallery-clear','#hs-api-gallery-create','#hs-api-gallery-close']) {
   const control=page.locator(selector).first();await control.scrollIntoViewIfNeeded();
   const bounds=await control.boundingBox();
   assert.ok(bounds.y>=0&&bounds.y+bounds.height<=viewport.height,'Picker controls remain reachable in short viewports');
  }
 }
 report.short_viewports=true;
 await page.setViewportSize({width:1440,height:1000});
 const heroResponse=page.waitForResponse(response=>response.url().includes('/admin-ajax.php')&&response.request().postData()?.includes('hs_api_gallery_catalog'));
 await page.locator('#hs-api-gallery-library').selectOption('heroes');
 await heroResponse;
 assert.equal(await page.locator('#hs-api-gallery-format-label').isVisible(),false,'Constructed-only format filter hidden for heroes');
 await page.locator('#hs-api-gallery-library').selectOption('constructed-cards');
 assert.equal(await page.locator('#hs-api-gallery-format-label').isVisible(),true);
 await page.locator('#hs-api-gallery-query').fill('НетТакойКарты');
 await page.getByRole('button',{name:'Найти',exact:true}).click();
 await page.getByText('По вашему запросу ничего не найдено.',{exact:true}).waitFor();
 await page.locator('#hs-api-gallery-query').fill('Тестовая');
 await page.getByRole('button',{name:'Найти',exact:true}).click();
 await page.locator('#hs-api-gallery-results .hs-api-gallery__choice img').first().click();
 assert.equal(await page.locator('#hs-api-gallery-results input[type=checkbox]').first().isChecked(),true,'Clicking the image selects a card');
 await page.locator('#hs-api-gallery-clear').click();
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),0,'Clear resets all selections');
 await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(0).check();
 await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(1).check();
 await page.locator('#hs-api-gallery-ratings').check();
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.locator('.media-modal:visible select[data-setting="columns"]').waitFor();
 await page.locator('.media-modal:visible select[data-setting="columns"]').selectOption('2');
 await page.locator('.media-modal:visible select[data-setting="size"]').selectOption('full');
 assert.equal(await page.locator('.media-modal:visible .hs-gallery-ratings-toggle').isChecked(),true);
 await page.getByRole('button',{name:'Update gallery',exact:true}).click();
 await page.waitForFunction(()=>tinymce.get('content').getContent().includes('[gallery'));
 report.inserted=await content();
 assert.match(report.inserted,/hs_ratings="1"/);assert.match(report.inserted,/columns="2"/);assert.match(report.inserted,/size="full"/);
 // Each creation session starts empty, including after cancelling native settings.
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),0);
 await page.locator('#hs-api-gallery-results input[type=checkbox]').first().check();
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.locator('.media-modal:visible select[data-setting="columns"]').waitFor();
 await page.locator('.media-modal:visible .media-modal-close').click();
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),0,'Cancelled gallery selection must not leak into the next gallery');
 assert.equal(await page.locator('#hs-api-gallery-create').isDisabled(),true);
 await page.locator('#hs-api-gallery-close').click();
 assert.equal(await content(),report.inserted,'Opening/cancelling a new gallery preserves the existing gallery');
 report.fresh_selection=true;
 // Delaying transport keeps the real AJAX/import code intact while cancelling.
 let releaseImport, reachedImport;
 const heldImport=new Promise(resolve=>{releaseImport=resolve;});
 const importReached=new Promise(resolve=>{reachedImport=resolve;});
 const delayImport=async route=>{
  const values=new URLSearchParams(route.request().postData()||'');
  if(values.get('action')==='hs_api_gallery_import'&&values.get('library')==='heroes') {reachedImport();await heldImport;}
  await route.continue();
 };
 await page.route('**/admin-ajax.php',delayImport);
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-library').selectOption('heroes');
 await page.locator('#hs-api-gallery-results input[type=checkbox]').first().check();
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await importReached;await page.locator('#hs-api-gallery-close').click();
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),0);
 releaseImport();await page.waitForFunction(()=>!document.querySelector('#hs-api-gallery-library').disabled);
 assert.equal(await page.locator('.media-modal:visible').count(),0,'Cancelled import cannot reopen native settings in a fresh session');
 assert.equal(await page.locator('#hs-api-gallery-dialog').evaluate(d=>d.open),true);
 await page.unroute('**/admin-ajax.php',delayImport);await page.locator('#hs-api-gallery-close').click();
 report.cancelled_import=true;
 // A second gallery inserts independently at the editor caret.
 const existingShortcodes=report.inserted.match(/\[gallery\b[^\]]*\]/g);
 await page.evaluate(()=>{
  const editor=tinymce.get('content');editor.setContent(editor.getContent()+'<p>Вторая галерея</p>');
  const paragraph=editor.getBody().lastElementChild;editor.selection.setCursorLocation(paragraph,paragraph.childNodes.length);editor.focus();
 });
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-library').selectOption('constructed-cards');
 await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(1).check();
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.getByRole('button',{name:'Update gallery',exact:true}).click();
 await page.waitForFunction(count=>(tinymce.get('content').getContent().match(/\[gallery\b/g)||[]).length===count,existingShortcodes.length+1);
 const secondShortcodes=(await content()).match(/\[gallery\b[^\]]*\]/g);
 assert.equal(secondShortcodes[0],existingShortcodes[0],'Second gallery preserves first shortcode');
 assert.match(secondShortcodes[1],new RegExp(`ids="${fixture.attachment_ids[1]}"`),'Second gallery contains only its new selection');
 report.second_gallery=true;
 await page.locator('#save-post').click();await page.waitForURL(/message=/);await initialized();
 report.saved=await content();assert.match(report.saved,/hs_ratings="1"/);
 const popupPromise=page.waitForEvent('popup');
 await page.locator('#post-preview').click();
 const preview=await popupPromise;await preview.waitForLoadState('domcontentloaded');
 await preview.locator('.hs-gallery-rating').first().waitFor();
 assert.equal(await preview.locator('.hs-gallery-rating').count(),3);
 assert.equal(await preview.locator('.hs-gallery-rating [data-score="5"]').first().isDisabled(),true);
 // Exercise the actual Newspaper article width with three native gallery items.
 await preview.locator('.hs-gallery-rated').first().scrollIntoViewIfNeeded();
 await preview.waitForFunction(()=>[...document.querySelectorAll('.hs-gallery-rated img')].every(image=>image.complete&&image.naturalWidth>0&&!image.currentSrc.startsWith('data:')));
 await preview.waitForFunction(()=>[...document.querySelector('.hs-gallery-rated').querySelectorAll('img')].every(image=>getComputedStyle(image).opacity==='1'));
 await preview.evaluate(()=>{
  const gallery=document.querySelector('.hs-gallery-rated');
  gallery.style.inlineSize='min(640px, 100%)';gallery.style.setProperty('--hs-gallery-columns','3');
  gallery.append(gallery.querySelector('.gallery-item').cloneNode(true));
 });
 await preview.waitForFunction(()=>[...document.querySelectorAll('.hs-gallery-rated img')].every(image=>image.complete&&image.naturalWidth>0));
 await preview.screenshot({path:'.artifacts/api-gallery/screenshots/preview-three-columns.png',fullPage:true});
 const threeColumns=await preview.locator('.hs-gallery-rated').first().evaluate(g=>({columns:getComputedStyle(g).gridTemplateColumns.split(' ').length,tops:[...g.querySelectorAll('.gallery-item')].map(n=>n.getBoundingClientRect().top)}));
 assert.equal(threeColumns.columns,3,'Three rated cards fit a 640px article');
 assert.equal(new Set(threeColumns.tops).size,1,'Three cards remain in the same row');
 report.three_columns=threeColumns;
 await preview.close();report.preview=true;
 // The native edit API is the same one used by TinyMCE's gallery pencil.
 await page.evaluate(()=>wp.media.gallery.edit(tinymce.get('content').getContent().match(/\[gallery[^\]]*\]/)[0]));
 await page.locator('.media-modal:visible .hs-gallery-ratings-toggle').waitFor();
 assert.equal(await page.locator('.media-modal:visible .hs-gallery-ratings-toggle').isChecked(),true);
 assert.equal(await page.locator('.media-modal:visible select[data-setting="columns"]').inputValue(),'2');
 await page.locator('.media-modal:visible .media-modal-close').click();
 await page.locator('#publish').click();await page.waitForURL(/message=/);await initialized();
 await page.evaluate(()=>{const e=tinymce.get('content');e.setContent(e.getContent()+'<p>Первая редакция галереи</p>');tinymce.triggerSave();});
 await page.locator('#publish').click();await page.waitForURL(/message=/);await initialized();
 const autosaveResponse=page.waitForResponse(r=>r.url().includes('/admin-ajax.php')&&(r.request().postData()||'').includes('wp_autosave'));
 await page.evaluate(()=>{const e=tinymce.get('content');e.setContent(e.getContent()+'<p>Проверка автосохранения галереи</p>');tinymce.triggerSave();wp.autosave.server.triggerSave();});
 const autosaved=await (await autosaveResponse).json();
 assert.equal(autosaved.wp_autosave.success,true);report.autosave=true;
 await page.locator('#publish').click();await page.waitForURL(/message=/);await initialized();
 await page.locator('a[href*="revision.php?revision="]:visible').first().click();
 await page.locator('.revisions-previous input').waitFor();
 let restore;
 for(let index=0;index<4;index+=1) {
  await page.locator('.revisions-previous input').click();await page.waitForTimeout(400);
  const candidate=page.locator('.restore-revision:enabled');
  if(await candidate.count()&&(await candidate.inputValue())==='Restore This Revision') {restore=candidate;break;}
 }
 assert.ok(restore,'A prior non-autosave revision is available');
 await restore.click();await page.waitForURL(/post.php/);await initialized();
 const restored=await content();assert.match(restored,/hs_ratings="1"/);assert.match(restored,/columns="2"/);
 assert.ok(!restored.includes('Проверка автосохранения галереи'));report.revision_restore=true;
 await page.goto(`${base}/?p=${fixture.post_id}`);
 await page.locator('.hs-gallery-rating [data-score="5"]:enabled').first().waitFor();
 assert.equal(await page.locator('.hs-gallery-rating').count(),3);
 const native=await page.locator('.hs-gallery-rating').first().evaluate(n=>({figure:n.closest('.gallery-item')?.tagName,image:n.closest('.gallery-item').querySelector('img').getBoundingClientRect().bottom,rating:n.getBoundingClientRect().top,background:getComputedStyle(n).backgroundColor}));
 assert.equal(native.figure,'FIGURE');assert.ok(native.rating>=native.image);assert.equal(native.background,'rgba(0, 0, 0, 0)');
 report.native=native;
 const first=page.locator('.hs-gallery-rating').first();
 await first.getByRole('button',{name:'5 из 5',exact:true}).click();
 await first.getByText(/Ваша оценка: 5/).waitFor();
 await page.waitForTimeout(1100);
 await first.getByRole('button',{name:'3 из 5',exact:true}).click();
 await first.getByText(/Голосов: 1 · Ваша оценка: 3/).waitFor();
 await page.reload();await first.getByText(/Голосов: 1 · Ваша оценка: 3/).waitFor();
 const anonymous=await browser.newContext();const reader=await anonymous.newPage();
 observeResponses(reader);
 await reader.goto(`${base}/?p=${fixture.post_id}`);
 assert.equal((await anonymous.cookies()).some(c=>c.name==='hs_gallery_voter'),false,'No identity cookie during passive reading');
 const readerFirst=reader.locator('.hs-gallery-rating').first();
 const state=await reader.evaluate(async post=>{
  const response=await fetch(hsGalleryRatings.url,{method:'POST',body:new URLSearchParams({action:'hs_api_gallery_state',post_id:post})});return (await response.json()).data;
 },fixture.post_id);
 const endpoint=`${base}/wp-admin/admin-ajax.php`;
 const blocked=await reader.request.post(endpoint,{headers:{Origin:'https://evil.test'},form:{action:'hs_api_gallery_vote',post_id:String(fixture.post_id),image_id:String(fixture.attachment_ids[0]),score:'1',nonce:state.nonce}});
 assert.equal(blocked.status(),403,'Cross-origin write rejected');
 const missingNonce=await reader.request.post(endpoint,{headers:{Origin:base},form:{action:'hs_api_gallery_vote',post_id:String(fixture.post_id),image_id:String(fixture.attachment_ids[0]),score:'1'}});
 assert.equal(missingNonce.status(),403,'Nonce required');
 const invalidImage=await reader.request.post(endpoint,{headers:{Origin:base},form:{action:'hs_api_gallery_vote',post_id:String(fixture.post_id),image_id:'999999',score:'1',nonce:state.nonce}});
 assert.equal(invalidImage.status(),403,'Article attachment membership required');
 const invalidScore=await reader.request.post(endpoint,{headers:{Origin:base},form:{action:'hs_api_gallery_vote',post_id:String(fixture.post_id),image_id:String(fixture.attachment_ids[0]),score:'6',nonce:state.nonce}});
 assert.equal(invalidScore.status(),403,'Invalid score rejected');
 const privateImport=await reader.request.post(endpoint,{form:{action:'hs_api_gallery_import',post_id:String(fixture.post_id)}});
 assert.equal(privateImport.status(),400,'Anonymous import forbidden');
 report.security={origin:true,nonce:true,membership:true,score:true,private_import:true};
 await readerFirst.getByRole('button',{name:'5 из 5',exact:true}).waitFor({state:'visible'});
 await reader.waitForFunction(()=>!document.querySelector('.hs-gallery-rating [data-score="5"]').disabled);
 await readerFirst.getByRole('button',{name:'5 из 5',exact:true}).click();
 await readerFirst.getByText(/Голосов: 2 · Ваша оценка: 5/).waitFor();
 assert.equal((await anonymous.cookies()).filter(c=>c.name==='hs_gallery_voter').length,1);
 await reader.reload();await readerFirst.getByText(/Голосов: 2 · Ваша оценка: 5/).waitFor();
 await reader.waitForTimeout(1100);
 await readerFirst.getByRole('button',{name:'Удалить мою оценку',exact:true}).click();
 await readerFirst.getByText(/Голосов: 1/).waitFor();
 assert.equal(await readerFirst.locator('.hs-gallery-rating__remove').isVisible(),false);
 report.votes={update_preserved_count:true,anonymous_persisted:true,own_removal:true,no_cookie_before_vote:true};
 await anonymous.close();
 const authorContext=await browser.newContext();await providerImages(authorContext);const authorPage=await authorContext.newPage();
 await authorPage.goto(`${base}/wp-login.php`);
 await authorPage.waitForFunction(()=>document.activeElement?.id==='user_login');
 await authorPage.getByLabel('Username or Email Address').fill(env.WP_TEST_AUTHOR_USER);
 await authorPage.locator('#user_pass').fill(env.WP_TEST_AUTHOR_PASSWORD);
 await authorPage.getByRole('button',{name:'Log In',exact:true}).click();await authorPage.waitForURL(/\/wp-admin\//);
 await authorPage.goto(`${base}/wp-admin/post.php?post=${fixture.author_post_id}&action=edit`);
 await authorPage.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await authorPage.locator('#hs-api-gallery-results input[type=checkbox]').first().waitFor();
 await authorPage.locator('#hs-api-gallery-results input[type=checkbox]').first().check();
 await authorPage.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await authorPage.getByRole('button',{name:'Update gallery',exact:true}).click();
 await authorPage.waitForFunction(()=>tinymce.get('content').getContent().includes('[gallery'));
 await authorPage.locator('#save-post').click();await authorPage.waitForURL(/message=/);
 const forbidden=await authorPage.evaluate(async other=>{
  const c=hsApiGalleryEditor;
  const response=await fetch(c.url,{method:'POST',body:new URLSearchParams({action:'hs_api_gallery_import',nonce:c.nonce,post_id:other,library:'constructed-cards',object_id:'TEST_CARD_1',variant:'card'})});
  return response.status;
 },fixture.post_id);
 assert.equal(forbidden,403,'Author cannot import into another author’s article');
 report.author={own_gallery:true,other_article_forbidden:true};await authorContext.close();
 for(const width of [1440,1024,768,480,390,320]) {
  await page.setViewportSize({width,height:1000});
  const layout=await first.evaluate(n=>({width:n.clientWidth,scroll:n.scrollWidth,stars:Array.from(n.querySelectorAll('[data-score]')).map(b=>({top:b.getBoundingClientRect().top,width:b.getBoundingClientRect().width,height:b.getBoundingClientRect().height}))}));
  assert.ok(layout.scroll<=layout.width+1,`Rating fits ${width}`);
  assert.ok(layout.stars.every(b=>b.width>=28&&b.height>=32),'Separated compact pointer targets');
  assert.ok(new Set(layout.stars.map(b=>b.top)).size===1,`Stars in one row ${width}`);
  report.layouts.push({surface:'ratings',width,...layout});
  await page.screenshot({path:`.artifacts/api-gallery/screenshots/ratings-${width}.png`,fullPage:true});
 }
 const touchContext=await browser.newContext({hasTouch:true,viewport:{width:390,height:844}});
 const touchPage=await touchContext.newPage();await touchPage.goto(`${base}/?p=${fixture.post_id}`);
 const touch=await touchPage.locator('.hs-gallery-rating').first().evaluate(n=>({width:n.clientWidth,scroll:n.scrollWidth,stars:[...n.querySelectorAll('[data-score]')].map(b=>({width:b.getBoundingClientRect().width,height:b.getBoundingClientRect().height,top:b.getBoundingClientRect().top}))}));
 assert.ok(touch.stars.every(b=>b.width>=44&&b.height>=44),'Actual touch devices retain 44px targets');
 assert.ok(touch.scroll<=touch.width+1&&new Set(touch.stars.map(b=>b.top)).size===1,'Touch stars fit in one row');
 report.touch=touch;await touchContext.close();
 await first.getByRole('button',{name:'2 из 5',exact:true}).focus();
 await page.keyboard.press('Enter');await first.getByText(/Ваша оценка: 2/).waitFor();
 await page.setViewportSize({width:1440,height:1000});
 await page.evaluate(()=>{document.body.style.zoom='2';});
 assert.ok(await first.getByRole('button',{name:'5 из 5',exact:true}).isVisible(),'Stars remain reachable at 200% zoom');
 const zoomOverflow=await first.evaluate(n=>n.scrollWidth-n.clientWidth);
 assert.ok(zoomOverflow<=1);report.zoom=true;
 assert.deepEqual(report.errors,[]);
 report.ok=true;console.log(JSON.stringify({ok:true,votes:report.votes,security:report.security,preview:report.preview,autosave:report.autosave,revision_restore:report.revision_restore,zoom:report.zoom,layouts:report.layouts.length,errors:report.errors}));
} catch(error) {report.ok=false;report.failure=error.message.slice(0,650);console.log(JSON.stringify({ok:false,failure:report.failure,errors:report.errors}));process.exitCode=1;}
finally {writeFileSync('.artifacts/api-gallery/browser-report.json',JSON.stringify(report,null,2));await browser.close();}
