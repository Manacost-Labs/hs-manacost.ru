import {writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';

// Measure the owned authenticated AJAX journey, not production navigation.
export async function measurePicker(page) {
 const samples=[];
 const sampleCount=Number(process.env.HS_GALLERY_PERF_SAMPLES||5);
 assert.ok(Number.isInteger(sampleCount)&&sampleCount>=5&&sampleCount<=20);
 for(let sample=0;sample<=sampleCount;sample+=1) {
  await page.reload({waitUntil:'domcontentloaded'});
  await page.waitForFunction(()=>window.tinymce?.get('content')?.initialized);
  await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
  await page.getByText('Найдено: 2',{exact:true}).waitFor();
  await page.waitForFunction(()=>[...document.querySelectorAll('#hs-api-gallery-results img')].every(i=>i.complete&&i.naturalWidth>0));
  await page.waitForTimeout(150); // Same settled initial library in both revisions.
  await page.evaluate(()=>{
   window.__galleryMeasure={added:0,long_tasks:0,start:performance.now()};
   window.__galleryMutation=new MutationObserver(entries=>{for(const entry of entries)window.__galleryMeasure.added += [...entry.addedNodes].filter(n=>n.nodeType===1&&n.classList.contains('hs-api-gallery__item')).length;});
   window.__galleryMutation.observe(document.querySelector('#hs-api-gallery-results'),{childList:true});
   window.__galleryLongTasks=new PerformanceObserver(entries=>{window.__galleryMeasure.long_tasks+=entries.getEntries().length;});
   window.__galleryLongTasks.observe({type:'longtask'});
  });
  const responsePromise=page.waitForResponse(r=>r.url().includes('/admin-ajax.php')&&r.request().postData()?.includes('library=trinkets'));
  await page.locator('#hs-api-gallery-library').selectOption('trinkets');
  const response=await responsePromise;
  await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===80);
  await page.waitForFunction(()=>document.querySelector('#hs-api-gallery-results img').naturalWidth>0);
  const interactive=await page.evaluate(()=>performance.now()-window.__galleryMeasure.start);
  await page.waitForTimeout(150);
  const browser=await page.evaluate(()=>{
   window.__galleryMutation.disconnect();window.__galleryLongTasks.disconnect();
   return {catalog_items_created:window.__galleryMeasure.added,long_tasks:window.__galleryMeasure.long_tasks,assigned_previews:[...document.querySelectorAll('#hs-api-gallery-results img')].filter(i=>i.getAttribute('src')).length};
  });
  const timing=response.request().timing();
  // Profile the same authenticated server action with the browser idle; keep
  // browser timing separately so rendering cannot be misattributed to PHP.
  const config=await page.evaluate(()=>({url:hsApiGalleryEditor.url,nonce:hsApiGalleryEditor.nonce,post_id:hsApiGalleryEditor.postId}));
  const url=new URL(config.url,page.url());assert.equal(url.hostname,'127.0.0.1');
  const cookies=(await page.context().cookies(url.href)).map(c=>`${c.name}=${c.value}`).join('; ');
  const started=performance.now();
  const server=await fetch(url,{method:'POST',headers:{cookie:cookies,origin:url.origin},body:new URLSearchParams({action:'hs_api_gallery_catalog',nonce:config.nonce,post_id:config.post_id,library:'trinkets',format:'standard',page:1})});
  const ttfb=performance.now()-started;
  const body=await server.json();assert.equal(body.success,true);assert.equal(body.data.items.length,100);
  const measured={ttfb_ms:ttfb,browser_ttfb_ms:timing.responseStart-timing.requestStart,interactive_ms:interactive,sql_queries:Number(server.headers.get('x-hs-gallery-queries')),peak_memory_mb:Number(server.headers.get('x-hs-gallery-memory')),...browser};
  assert.ok(Number.isFinite(measured.sql_queries)&&measured.sql_queries>0,'Owned SQL probe required');
  if(sample>0)samples.push(measured);
 }
 const phase=process.env.HS_GALLERY_PERF_PHASE||'after';
 writeFileSync(`.artifacts/api-gallery/performance-${phase}.json`,JSON.stringify({environment:'integration',authenticated_role:'administrator',dataset_size:137,visible_limit:80,cache_state:'warm',viewport:{width:1440,height:1000},sample_count:samples.length,samples},null,2));
 if(phase!=='before') {
  assert.ok(samples.every(s=>s.catalog_items_created===80),'Unchanged catalog nodes must survive status updates');
  assert.ok(samples.every(s=>s.assigned_previews<40),'Only nearby previews load');
  await page.waitForFunction(()=>document.querySelector('#hs-api-gallery-results img').naturalWidth>0);
  await page.locator('#hs-api-gallery-more').click();
  await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===137);
  await page.locator('#hs-api-gallery-library').selectOption('diamond-cards');
  await page.waitForFunction(()=>document.querySelector('#hs-api-gallery-results img')?.currentSrc.includes('/images/TEST_DIAMOND_')&&document.querySelector('#hs-api-gallery-results img').naturalWidth>0);
  assert.equal(await page.locator('#hs-api-gallery-results select').count(),0,'A single diamond variant needs no redundant dropdown');
  await page.screenshot({path:'.artifacts/api-gallery/screenshots/diamonds-desktop.png'});
  const first=page.locator('#hs-api-gallery-results .hs-api-gallery__item').first();
  await page.route('**/*forced-failure*',route=>route.fulfill({status:503,headers:{'cache-control':'no-store'}}));
  await first.locator('img').evaluate(image=>{image.src+='?forced-failure=1';});
  await first.getByText('Изображение недоступно',{exact:true}).waitFor();
  await first.getByRole('button',{name:/Повторить загрузку/}).click();
  await page.waitForFunction(()=>document.querySelector('#hs-api-gallery-results img').naturalWidth>0);
  assert.equal(await first.getByRole('button',{name:/Повторить загрузку/}).isVisible(),false);
 }
 await page.locator('#hs-api-gallery-close').click();
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-library').selectOption('constructed-cards');
 await page.getByText('Загружено: 2',{exact:true}).waitFor();
}
