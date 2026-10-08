import {writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';

export async function measureImports(page) {
 const samples=[];
 const phase=process.env.HS_GALLERY_PERF_PHASE||'after';
 await page.reload({waitUntil:'domcontentloaded'});
 await page.waitForFunction(()=>window.tinymce?.get('content')?.initialized);
 for(let sample=0;sample<=5;sample+=1) {
  await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
  await page.locator('#hs-api-gallery-library').selectOption('trinkets');
  await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===80);
  const wanted=Array.from({length:6},(_,i)=>`TEST_TRINKET_${40+sample*6+i}`);
  for(let i=0;i<6;i+=1)await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(39+sample*6+i).check();
  await page.waitForFunction(()=>[...document.querySelectorAll('#hs-api-gallery-selected img')].every(image=>image.complete&&image.naturalWidth>0));
  await page.waitForTimeout(150);
  let active=0,maximum=0;
  const responses=[],ids=new Map();
  const matching=request=>{
   if(!request.url().includes('/admin-ajax.php'))return false;
   const data=new URLSearchParams(request.postData()||'');
   return data.get('action')==='hs_api_gallery_import'&&wanted.includes(data.get('object_id'));
  };
  const started=request=>{if(matching(request)){active+=1;maximum=Math.max(maximum,active);}};
  const finished=response=>{
   if(!matching(response.request()))return;
   active-=1;
   responses.push((async()=>{
    const body=await response.json();assert.equal(body.success,true);
    ids.set(new URLSearchParams(response.request().postData()).get('object_id'),body.data.attachment_id);
    const headers=response.headers(),timing=response.request().timing();
    const sql=Number(headers['x-hs-gallery-queries']);assert.ok(sql>0,'Local import metrics required');
    return {ttfb_ms:timing.responseStart-timing.requestStart,sql_queries:sql,peak_memory_mb:Number(headers['x-hs-gallery-memory'])};
   })());
  };
  page.on('request',started);page.on('response',finished);
  await page.evaluate(()=>{
   window.__importStart=performance.now();window.__importTasks=[];
   window.__importObserver=new PerformanceObserver(entries=>{
    for(const entry of entries.getEntries())if(entry.startTime>=window.__importStart)window.__importTasks.push({start_ms:entry.startTime-window.__importStart,duration_ms:entry.duration});
   });
   window.__importObserver.observe({type:'longtask'});
  });
  const begin=performance.now();
  await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
  await page.locator('.media-modal:visible select[data-setting="columns"]').waitFor();
  await page.locator('.media-modal:visible .attachments .attachment').nth(5).waitFor();
  assert.equal(await page.locator('.media-modal:visible .attachments .attachment').count(),6);
  const interactive=performance.now()-begin;
  const tasks=await page.evaluate(()=>{window.__importObserver.disconnect();return window.__importTasks;});
  const metrics=await Promise.all(responses);assert.equal(metrics.length,6);
  const order=await page.evaluate(()=>wp.media.frame.state('gallery-edit').get('library').pluck('id'));
  assert.deepEqual(order,wanted.map(id=>ids.get(id)),'Out-of-order downloads must retain selected order');
  page.off('request',started);page.off('response',finished);
  if(sample>0)samples.push({interactive_ms:interactive,long_tasks:tasks.length,long_task_details:tasks,maximum_parallel:maximum,requests:metrics});
  await page.locator('.media-modal:visible .media-modal-close').click();
 }
 writeFileSync(`.artifacts/api-gallery/import-performance-${phase}.json`,JSON.stringify({environment:'integration',authenticated_role:'administrator',dataset_size:6,sample_count:5,cache_state:'cold',network_profile:'One 400 ms and five 200 ms remote image responses; fresh attachments in every sample; one warmup excluded',samples},null,2));
 if(phase!=='before') {
  assert.ok(samples.every(s=>s.maximum_parallel===2),'Cold imports should use exactly two bounded concurrent requests');
  await retryAndCancel(page);
  await distinctFiles(page);
 }
 // Restore a fresh editor so benchmark caches cannot affect later UI scenarios.
 await page.reload({waitUntil:'domcontentloaded'});
 await page.waitForFunction(()=>window.tinymce?.get('content')?.initialized);
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.getByText(/^(?:Загружено|Найдено): 2$/).waitFor();
}

async function distinctFiles(page) {
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 for(const library of ['heroes','coins']) {
  await page.locator('#hs-api-gallery-library').selectOption(library);
  await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===2);
  await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(1).check();
 }
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.locator('.media-modal:visible .attachments .attachment').nth(1).waitFor();
 const files=await page.evaluate(()=>wp.media.frame.state('gallery-edit').get('library').map(model=>model.get('filename')));
 assert.equal(new Set(files).size,2,'Concurrent snapshots with the same object ID must have distinct media files');
 await page.locator('.media-modal:visible .media-modal-close').click();
}

async function retryAndCancel(page) {
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-more').click();
 await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===137);
 for(let i=119;i<125;i+=1)await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(i).check();
 const requests=[];let failed=false;
 const fault=async route=>{
  const values=new URLSearchParams(route.request().postData()||''),id=values.get('object_id');
  if(values.get('action')==='hs_api_gallery_import') {
   requests.push(id);
   if(id==='TEST_TRINKET_120'&&!failed) {
    failed=true;await new Promise(resolve=>setTimeout(resolve,100));
    return route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({success:false,data:{message:'Тестовая ошибка сохранения'}})});
   }
  }
  await route.continue();
 };
 await page.route('**/admin-ajax.php',fault);
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.getByText(/Тестовая ошибка сохранения.*Выбор сохранён/).waitFor();
 assert.equal(requests.length,2,'A failure stops queued imports and drains the two in flight');
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),6);
 assert.equal(await page.locator('#hs-api-gallery-create').isEnabled(),true);
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();
 await page.locator('.media-modal:visible .attachments .attachment').nth(5).waitFor();
 assert.equal(requests.filter(id=>id==='TEST_TRINKET_121').length,1,'Successful in-flight snapshots are reused on retry');
 assert.equal(new Set(await page.evaluate(()=>wp.media.frame.state('gallery-edit').get('library').pluck('id'))).size,6);
 await page.locator('.media-modal:visible .media-modal-close').click();await page.unroute('**/admin-ajax.php',fault);

 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 await page.locator('#hs-api-gallery-more').click();
 await page.waitForFunction(()=>document.querySelectorAll('#hs-api-gallery-results .hs-api-gallery__item').length===137);
 for(let i=129;i<135;i+=1)await page.locator('#hs-api-gallery-results input[type=checkbox]').nth(i).check();
 let release,reached;const held=new Promise(resolve=>{release=resolve;});const both=new Promise(resolve=>{reached=resolve;});let count=0;
 const hold=async route=>{
  const values=new URLSearchParams(route.request().postData()||'');
  if(values.get('action')==='hs_api_gallery_import') {count+=1;if(count===2)reached();await held;}
  await route.continue();
 };
 await page.route('**/admin-ajax.php',hold);
 await page.getByRole('button',{name:'Настроить галерею',exact:true}).click();await both;
 await page.locator('#hs-api-gallery-close').click();
 await page.getByRole('button',{name:'Создать галерею из API',exact:true}).click();
 assert.equal(await page.locator('#hs-api-gallery-results input:checked').count(),0);
 release();await page.waitForFunction(()=>!document.querySelector('#hs-api-gallery-library').disabled);
 assert.equal(count,2,'Cancel stops the remaining queue');assert.equal(await page.locator('.media-modal:visible').count(),0);
 await page.unroute('**/admin-ajax.php',hold);await page.locator('#hs-api-gallery-close').click();
}
