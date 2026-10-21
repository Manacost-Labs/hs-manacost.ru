// Counters must not fire for a prerendered page until the reader actually opens it.
// Runs the real inline scripts with `document.prerendering` simulated, then activates the page.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { chromium } from '@playwright/test';

const root = new URL('../../', import.meta.url);
const theme = readFileSync(new URL('wordpress/themes/Newspaper_new/functions.php', root)).toString('latin1');
const hit = theme.indexOf('/views/hit');
const viewsScript = theme.slice(theme.lastIndexOf('<script>', hit), theme.indexOf('</script>', hit) + '</script>'.length);
const liveinternet = execFileSync('php', ['-r', `
	define('ABSPATH', '/');
	define('MANACOST_MOBILE_LITE_ENABLED', false);
	define('MANACOST_NATIVE_THUMBNAILS_ENABLED', false);
	define('MANACOST_BACKGROUND_PLACEHOLDER_ENABLED', false);
	function add_action(...$a) {} function add_filter(...$a) {}
	require $argv[1];
	echo Manacost_Performance_Optimizer::optimize_html('<html><head></head><body><!--LiveInternet counter--><script>legacy()</script><!--/LiveInternet--></body></html>');
`, new URL('wordpress/mu-plugins/manacost-performance-optimizer.php', root).pathname]).toString();
assert.match(liveinternet, /id="manacost-liveinternet-delay"/);

const page = `<!doctype html><html><body><span class="td-nr-views-42">0</span>${viewsScript}${liveinternet.slice(liveinternet.indexOf('<body>') + 6, liveinternet.indexOf('</body>'))}</body></html>`;
const server = createServer((request, response) => { response.setHeader('Content-Type', 'text/html'); response.end(page); });
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const browser = await chromium.launch({ headless: true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
try {
	for (const prerendered of [true, false]) {
		const context = await browser.newContext();
		const counted = [];
		await context.route(/\/views\/hit|counter\.yadro\.ru/, route => { counted.push(new URL(route.request().url()).hostname === '127.0.0.1' ? 'views' : 'liveinternet'); route.fulfill({ status: 204 }); });
		await context.addInitScript(value => {
			window.prerenderState = value;
			Object.defineProperty(Document.prototype, 'prerendering', { configurable: true, get: () => window.prerenderState });
		}, prerendered);
		const tab = await context.newPage();
		await tab.clock.install();
		await tab.goto(`http://127.0.0.1:${server.address().port}/`, { waitUntil: 'load' });
		await tab.clock.runFor(10000);
		await tab.waitForTimeout(200);
		if (prerendered) {
			assert.deepEqual(counted, [], 'nothing is counted while the page is only prerendered');
			await tab.evaluate(() => { window.prerenderState = false; document.dispatchEvent(new Event('prerenderingchange')); });
			await tab.waitForTimeout(200);
			assert.deepEqual(counted, ['views'], 'opening the page counts the view at once');
			await tab.clock.runFor(4100);
			await tab.waitForTimeout(200);
		}
		assert.deepEqual(counted.sort(), ['liveinternet', 'views'], prerendered ? 'activated page' : 'ordinary page');
		await context.close();
	}
	console.log('Prerender guards: views and LiveInternet count only shown pages; ordinary pages unchanged.');
} finally {
	await browser.close();
	server.close();
}
