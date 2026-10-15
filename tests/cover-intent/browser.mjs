import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';

const script = await readFile(new URL('../../wordpress/mu-plugins/manacost-site-navigation.js', import.meta.url), 'utf8');
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=', 'base64');
const requests = [];
const server = createServer((request, response) => {
	requests.push(request.url);
	if (request.url === '/navigation.js') { response.setHeader('Content-Type', 'text/javascript'); response.end(script); return; }
	if (request.url.startsWith('/wp-content/uploads/')) { response.setHeader('Content-Type', 'image/png'); response.setHeader('Cache-Control', 'public, max-age=3600'); response.end(png); return; }
	if (request.url === '/redirect/') { response.writeHead(302, { Location: '/account/' }); response.end(); return; }
	if (request.url === '/json/') { response.setHeader('Content-Type', 'application/json'); response.end('{}'); return; }
	if (request.url === '/views/hit') { response.setHeader('Content-Type', 'text/javascript'); response.end('window.viewHit=true'); return; }
	if (request.url === '/paid/') { response.setHeader('Content-Type', 'text/html'); response.end('<body>Paid fixture</body>'); return; }
	response.setHeader('Content-Type', 'text/html');
	if (request.url === '/') {
		response.end(`<!doctype html><html><body><img src="/wp-content/uploads/known.png.webp"><div class="td-module-title">${['article', 'second', 'early', 'empty', 'redirect', 'json', 'foreign', 'big', 'account', 'wp-admin'].map(path => `<a href="/${path}/">${path}</a>`).join(' ')}<a class="no-prefetch" href="/article/">opt out</a><a rel="nofollow" href="/article/">nofollow</a><a href="/%61ccount/">encoded private</a><a href="/%zz/">invalid encoding</a><a href="/article/?preview=true">preview</a><a href="/article/#comments">fragment</a><a href="https://example.com/article/">external</a><a target="_blank" href="/article/">new tab</a><a href="/article/" download>download</a></div><script defer src="/navigation.js"></script></body></html>`);
		return;
	}
	const source = request.url === '/foreign/' ? 'https://example.com/cover.png' : '/wp-content/uploads/cover.png' + (request.headers.accept?.includes('image/webp') ? '.webp' : '');
	if (request.url === '/empty/') { response.end(`<div class="td-post-featured-image"></div><aside><img src="${source}"></aside>`); return; }
	if (request.url === '/early/') {
		response.write(`<div class="td-post-featured-image"><img src="${source}"></div>`);
		setTimeout(() => { if (!response.destroyed) response.end('x'.repeat(600000)); }, 600);
		return;
	}
	response.end(`<!doctype html><html><body>${request.url === '/big/' ? 'x'.repeat(600000) : ''}<div class="td-post-featured-image"><img src="${source}" onload="window.untrustedExecuted=true" srcset="${source} 600w" sizes="100vw"></div><script>window.articleExecuted=true</script><script src="/views/hit"></script><iframe src="/paid/"></iframe><link rel="stylesheet" href="/unused.css"></body></html>`);
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;
let browser;
try {
	browser = await chromium.launch({ headless: true, ...(process.env.READER_TEST_CHROMIUM ? { executablePath: process.env.READER_TEST_CHROMIUM } : {}) });
	async function scenario(run, connection) {
		const context = await browser.newContext();
		try {
			if (connection) await context.addInitScript(value => Object.defineProperty(navigator, 'connection', { value }), connection);
			const page = await context.newPage();
			await page.goto(origin, { waitUntil: 'load' });
			requests.length = 0;
			await run(page);
		} finally { await context.close(); }
	}
	await scenario(async page => {
		await page.getByRole('link', { name: 'article', exact: true }).hover();
		await page.waitForFunction(() => performance.getEntriesByType('resource').some(r => r.name.includes('/wp-content/uploads/cover.png')));
		assert.deepEqual(requests.filter(p => p !== '/favicon.ico'), ['/article/', '/wp-content/uploads/cover.png.webp']);
		assert.equal(await page.evaluate(() => Boolean(window.articleExecuted || window.untrustedExecuted)), false);
		assert.equal(page.url(), origin + '/');
		await page.getByRole('link', { name: 'second', exact: true }).hover();
		await page.waitForTimeout(300);
		assert.equal(requests.includes('/second/'), false, 'one destination per document');
		await page.getByRole('link', { name: 'article', exact: true }).click();
		await page.waitForURL(origin + '/article/');
		assert.equal(await page.evaluate(() => performance.getEntriesByType('resource').find(r => r.name.endsWith('/cover.png.webp'))?.transferSize), 0, 'the navigation reuses the matching modern-format URL');
		assert.equal(await page.evaluate(() => window.articleExecuted), true, 'ordinary navigation still executes the article');
		await page.goBack({ waitUntil: 'load' });
		assert.equal(page.url(), origin + '/');
	});
	await scenario(async page => {
		await page.getByRole('link', { name: 'article', exact: true }).focus();
		await page.waitForFunction(() => performance.getEntriesByType('resource').some(r => r.name.includes('/wp-content/uploads/cover.png')));
		assert.equal(requests.filter(p => p === '/article/').length, 1, 'keyboard intent');
	});
	await scenario(async page => {
		await page.getByRole('link', { name: 'early', exact: true }).hover();
		await page.waitForFunction(() => performance.getEntriesByType('resource').some(r => r.name.includes('/wp-content/uploads/cover.png')));
		assert.equal(requests.includes('/early/'), true, 'only the prefix containing the cover is needed');
	});
	await scenario(async page => {
		const link = page.getByRole('link', { name: 'article', exact: true });
		await link.dispatchEvent('pointerover', { pointerType: 'touch' });
		await link.focus();
		await page.waitForTimeout(300);
		assert.equal(requests.includes('/article/'), false, 'touch focus does not prefetch');
		await page.keyboard.press('Tab');
		await page.waitForFunction(() => performance.getEntriesByType('resource').some(r => r.name.includes('/wp-content/uploads/cover.png')));
		assert.equal(requests.includes('/second/'), true, 'keyboard still works after touch');
	});
	await scenario(async page => {
		await page.getByRole('link', { name: 'article', exact: true }).hover();
		await page.mouse.move(0, 200);
		await page.waitForTimeout(300);
		assert.equal(requests.includes('/article/'), false, 'brief hover is cancelled');
	});
	for (const connection of [{ saveData: true }, { effectiveType: '2g' }, { effectiveType: 'slow-2g' }]) {
		await scenario(async page => {
			await page.getByRole('link', { name: 'article', exact: true }).hover();
			await page.waitForTimeout(300);
			assert.equal(requests.includes('/article/'), false);
		}, connection);
	}
	for (const name of ['account', 'wp-admin', 'encoded private', 'invalid encoding', 'preview', 'fragment', 'external', 'new tab', 'download', 'opt out', 'nofollow']) {
		await scenario(async page => {
			await page.getByRole('link', { name, exact: true }).hover();
			await page.waitForTimeout(300);
			assert.deepEqual(requests.filter(p => p !== '/favicon.ico'), [], name);
		});
	}
	for (const name of ['redirect', 'json', 'foreign', 'big', 'empty']) {
		await scenario(async page => {
			await page.getByRole('link', { name, exact: true }).hover();
			await page.waitForTimeout(600);
			assert.equal(requests.includes('/account/'), false);
			assert.equal(requests.some(p => p.startsWith('/wp-content/uploads/')), false, name);
			assert.equal(page.url(), origin + '/');
		});
	}
	console.log('Cover intent: mouse, keyboard, cancellation, cache, ordinary navigation/history, one-destination budget, privacy and inert parsing passed.');
} finally {
	await browser?.close();
	await new Promise(resolve => server.close(resolve));
}
