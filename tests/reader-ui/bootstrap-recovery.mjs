import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

const source=readFileSync(new URL('../../wordpress/mu-plugins/hs-manacost-reader/bootstrap.js',import.meta.url),'utf8');
function fixture({account=false,ignoreAbort=false}={}) {
  const requests=[],timers=new Map(),events=new Map();let sequence=0;
  const sandbox={AbortController,DOMException,document:{querySelector:selector=>selector==='[data-mc-reader-root]'&&account?{}:null},
    window:{location:{hostname:'hs-manacost.ru'},addEventListener:(name,callback)=>events.set(name,callback)},
    setTimeout:callback=>{const id=++sequence;timers.set(id,callback);return id;},clearTimeout:id=>timers.delete(id),
    fetch:(url,options)=>new Promise((resolve,reject)=>{
      if(!ignoreAbort)options.signal.addEventListener('abort',()=>reject(options.signal.reason));
      requests.push({url,options,fail:reject,finish:(status=200,body='{"profile":{"id":"fixture"}}')=>resolve({status,ok:status>=200&&status<300,text:async()=>body})});
    })};
  vm.runInNewContext(source,sandbox);
  return {requests,run:sandbox.window.hsManacostReaderBootstrap,
    loadAgain:()=>vm.runInNewContext(source,sandbox),current:()=>sandbox.window.hsManacostReaderBootstrap,
    fire:name=>events.get(name)?.(),expire:()=>{const [id,callback]=timers.entries().next().value;timers.delete(id);callback();}};
}
test('an early head client survives the deferred bundle without a second identity request',async()=>{
  const f=fixture({account:true}),first=f.run();
  f.loadAgain();assert.equal(f.current(),f.run);assert.equal(f.current()(),first);
  assert.equal(f.requests.length,1);f.requests[0].finish();await first;
});
test('account starts before its UI script and all consumers share one request',async()=>{
  const f=fixture({account:true});assert.equal(f.requests.length,1);
  const a=f.run(),b=f.run();assert.equal(a,b);f.requests[0].finish();await a;
  assert.equal(f.run(),a);assert.equal(f.requests.length,1);
  assert.equal(f.requests[0].options.cache,'no-store');assert.equal(f.requests[0].options.credentials,'same-origin');
});
test('HTTP failures are not permanently memoized',async()=>{
  for(const status of [429,503]){const f=fixture(),a=f.run();f.requests[0].finish(status);assert.equal((await a).response.status,status);const b=f.run();assert.equal(f.requests.length,2);f.requests[1].finish();await b;}
});
test('a fast anonymous preload is reused, while explicit retry revalidates it',async()=>{
  const f=fixture({account:true}),a=f.run();f.requests[0].finish(401);await a;
  assert.equal(f.run(),a);assert.equal(f.requests.length,1);
  const b=f.run(true);f.requests[1].finish();await b;
});
test('network failure can be retried',async()=>{
  const f=fixture(),a=f.run();f.requests[0].fail(new Error('offline'));await assert.rejects(a,/offline/);
  const b=f.run();f.requests[1].finish();await b;
});
test('deadline reports TimeoutError and permits a fresh attempt',async()=>{
  const f=fixture(),a=f.run();f.expire();await assert.rejects(a,{name:'TimeoutError'});
  const b=f.run();f.requests[1].finish();await b;
});
test('refresh cancels stale transport, even when that transport ignores abort',async()=>{
  const f=fixture({ignoreAbort:true}),a=f.run(),b=f.run(true);
  assert.equal(f.requests[0].options.signal.reason.message,'superseded');
  f.requests[0].finish();await assert.rejects(a,{name:'AbortError'});
  assert.equal(f.run(),b);f.requests[1].finish();await b;
});
test('logout and pagehide discard fulfilled and pending private results',async()=>{
  for(const reason of ['logout','pagehide']){
    const f=fixture({ignoreAbort:true}),a=f.run();
    if(reason==='pagehide')f.fire('pagehide');else f.run.invalidate(reason);
    f.requests[0].finish();await assert.rejects(a,{name:'AbortError'});
    const b=f.run();f.requests[1].finish();await b;
    f.run.invalidate(reason);const c=f.run();assert.equal(f.requests.length,3);f.requests[2].finish();await c;
  }
});
test('malformed or oversized response clears the shared failure',async()=>{
  for(const body of ['{','x'.repeat(8193)]){const f=fixture(),a=f.run();f.requests[0].finish(200,body);await assert.rejects(a);const b=f.run();f.requests[1].finish();await b;}
});
