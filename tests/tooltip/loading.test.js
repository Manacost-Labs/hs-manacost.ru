const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

const assets = resolve(__dirname, '../../wordpress/plugins/hs-tooltip/assets');

function loadScript(filename, hasObserver = true) {
  const requests = [];
  const callbacks = [];
  const observers = [];
  const targets = Array.from({ length: 30 }, (_, index) => ({
    getAttribute: () => `/cards/${index}.png`,
    setAttribute() {},
  }));
  const window = { requestIdleCallback: callback => callbacks.push(callback) };
  const context = {
    window,
    document: {
      body: { addEventListener() {} },
      documentElement: {},
      querySelectorAll: () => targets,
    },
    Image: class {
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
  return { requests, targets, observers };
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
