const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

const assets = resolve(__dirname, '../../wordpress/plugins/hs-tooltip/assets');

function loadScript(filename, hasObserver = true, options = {}) {
  const requests = [];
  const callbacks = [];
  const observers = [];
  const listeners = {};
  const images = [];
  let now = 1000;
  const targets = Array.from({ length: 30 }, (_, index) => ({
    getAttribute: () => `/cards/${index}.png`,
    setAttribute() {},
    getBoundingClientRect: () => ({ left: index * 200, right: index * 200 + 100, top: 0, bottom: 20, width: 100, height: 20 }),
  }));
  const scope = { getElementsByClassName: () => targets };
  const eventTarget = { closest: selector => selector === '.hs-card-tooltip' ? null : scope };
  const window = { requestIdleCallback: callback => callbacks.push(callback), matchMedia: () => ({ matches: options.finePointer !== false }) };
  function createElement() {
    const attributes = {};
    return { style: {}, classList: { add() {}, remove() {} }, appendChild() {},
      setAttribute(key, value) { attributes[key] = value; },
      getAttribute: key => attributes[key], removeAttribute(key) { delete attributes[key]; },
      getBoundingClientRect: () => ({ width: 300, height: 400 }),
    };
  }
  const context = {
    window,
    navigator: { connection: options.connection },
    Date: { now: () => now },
    document: {
      body: { addEventListener(type, callback) { listeners[type] = callback; } },
      documentElement: { appendChild() {} },
      createElement,
      querySelectorAll: selector => selector.startsWith('.hs-tooltip-box') ? [] : targets,
    },
    Image: class {
      constructor() { images.push(this); }
      fetchPriority = 'auto';
      set src(url) { requests.push({ url, priority: this.fetchPriority }); }
    },
  };
  if (hasObserver) {
    context.IntersectionObserver = class {
      constructor(callback) { this.callback = callback; observers.push(this); }
      observe() {}
      unobserve() {}
    };
  }
  runInNewContext(readFileSync(resolve(assets, filename), 'utf8'), context);
  callbacks.forEach(callback => callback());
  return { requests, targets, observers, images, hover(index = 0) {
    const target = targets[index];
    listeners.mouseover({ target: { closest: () => target }, clientX: 10, clientY: 10 });
  }, move(x = 150, y = 10, elapsed = 100) {
    now += elapsed;
    listeners.mousemove({ target: eventTarget, clientX: x, clientY: y });
  } };
}

for (const filename of ['hs-tooltip.js', 'hs-tooltip-v115.js']) {
  test(`${filename}: offscreen cards do not compete with the first screen`, () => {
    const { requests } = loadScript(filename);
    assert.deepEqual(requests, []);
  });

  test(`${filename}: intersecting cards stay unloaded until interaction`, () => {
    const { requests, targets, observers } = loadScript(filename);
    const entries = [
      { target: targets[28], isIntersecting: true },
      { target: targets[29], isIntersecting: false },
    ];
    if (observers[0]) {
      observers[0].callback(entries);
      observers[0].callback(entries);
    }
    assert.deepEqual(requests, []);
  });

  test(`${filename}: missing IntersectionObserver does not trigger a bulk fallback`, () => {
    const { requests } = loadScript(filename, false);
    assert.deepEqual(requests, []);
  });
}

for (const filename of ['hs-tooltip.js', 'hs-tooltip-v115.js']) {
  test(`${filename}: approaching a card fetches only the nearest image`, () => {
    const { requests, move } = loadScript(filename);
    move(140);
    move(140);
    assert.deepEqual(requests, [{ url: '/cards/0.png', priority: 'low' }]);
  });

  test(`${filename}: distant or invisible cards are not prefetched`, () => {
    const { requests, targets, move } = loadScript(filename);
    move(140, 200);
    targets[0].getBoundingClientRect = () => ({ left: 0, right: 0, top: 0, bottom: 0, width: 0, height: 0 });
    move(10);
    assert.deepEqual(requests, []);
  });

  test(`${filename}: speculative downloads are bounded and failed requests can retry`, () => {
    const { requests, images, move } = loadScript(filename);
    move(10);
    move(210);
    move(410);
    assert.equal(requests.length, 2, 'at most two in flight');
    images[0].onerror();
    move(10);
    assert.equal(requests.length, 3, 'failed request retries when approached again');
    images[1].onload();
    images[2].onload();
    for (let index = 2; index < 12; index++) {
      const previous = requests.length;
      move(index * 200 + 10);
      if (requests.length > previous) images.at(-1).onload();
    }
    assert.equal(requests.length, 8, 'at most eight speculative requests per page');
  });

  test(`${filename}: touch, data saver and slow networks keep interaction-only loading`, () => {
    for (const options of [{ finePointer: false }, { connection: { saveData: true } },
      { connection: { effectiveType: '2g' } }, { connection: { effectiveType: 'slow-2g' } }]) {
      const { requests, move } = loadScript(filename, true, options);
      move();
      assert.deepEqual(requests, []);
    }
  });
}

for (const filename of ['hs-tooltip.js', 'hs-tooltip-v115.js']) {
  test(`${filename}: hover promotes an in-flight preload without another download`, () => {
    const { requests, images, move, hover } = loadScript(filename);
    move(10);
    hover();
    assert.equal(requests.length, 1);
    assert.equal(images[0].fetchPriority, 'high');
  });

  test(`${filename}: foreground hover still loads when speculative work is disabled`, () => {
    const { requests, hover } = loadScript(filename, true, { connection: { saveData: true } });
    hover();
    assert.deepEqual(requests, [{ url: '/cards/0.png', priority: 'high' }]);
  });

  test(`${filename}: a reached background budget does not block another hovered card`, () => {
    const { requests, images, move, hover } = loadScript(filename);
    for (let index = 0; index < 8; index++) { move(index * 200 + 10); images.at(-1).onload(); }
    hover(9);
    assert.equal(requests.length, 9);
    assert.deepEqual(requests.at(-1), { url: '/cards/9.png', priority: 'high' });
  });
}

for (const filename of ['hs-tooltip.js', 'hs-tooltip-v115.js']) {
  test(`${filename}: mouse tracking throttles and bounds layout reads`, () => {
    const { targets, move } = loadScript(filename);
    let reads = 0;
    for (const target of targets) {
      const bounds = target.getBoundingClientRect;
      target.getBoundingClientRect = () => { reads++; return bounds(); };
    }
    move(140);
    assert.equal(reads, 20);
    move(140, 10, 50);
    assert.equal(reads, 20);
    move(140, 10, 50);
    assert.equal(reads, 40);
  });
}
