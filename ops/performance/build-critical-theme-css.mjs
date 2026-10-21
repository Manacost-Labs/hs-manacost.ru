// Builds the render-critical subset of the shared Newspaper stylesheets.
//
// Usage: node ops/performance/build-critical-theme-css.mjs [base-url]
//
// Loads representative anonymous pages at several widths, records which rules of
// the three shared theme sheets Chrome actually used, and writes that subset per
// sheet. The full sheets still load right after first paint, so a rule missed
// here only arrives slightly later; it is never lost.
import { createHash } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { chromium } from '@playwright/test';
import postcss from 'postcss';

const base = new URL(process.argv[2] || 'https://hs-manacost.ru/');
const outDir = new URL('../../wordpress/mu-plugins/manacost-shared-theme-styles/', import.meta.url);
const sheets = {
	'td-theme': '/wp-content/themes/Newspaper_new/style.css',
	'td-legacy-framework-front-style': '/wp-content/plugins/td-composer/legacy/Newspaper/assets/css/td_legacy_main.css',
	'td-standard-pack-framework-front-style': '/wp-content/plugins/td-standard-pack/Newspaper/assets/css/td_standard_pack_main.css',
};
// Every public template family the theme renders for anonymous readers.
const paths = [
	'/', '/category/novosti/', '/category/gajdy-hearthstone/', '/tag/vlast-temnoj-imperii/',
	'/kolody-potasovki-temnaya-imperija-6-pobed/', '/top-10-strategiya-v-14-sezone-polej-srazhenij/',
	'/gajd-kak-nachat-igrat-hearthstone/', '/obnovlenie-37-0-1-ispravlenie-oshibok-igry/', '/arena-decks/', '/?s=druid',
];
const widths = [360, 412, 768, 1024, 1280, 1440];
// Measurement must not create views, analytics events or ad impressions.
const blocked = /googlesyndication|doubleclick|\/mca\/event|admin-ajax\.php|mc\.yandex|\/views\/hit|yandex\.ru\/ads|an\.yandex|yastatic\.net\/partner/;

const used = Object.fromEntries(Object.keys(sheets).map(handle => [handle, new Set()]));
const texts = {};
const versions = {};
const browser = await chromium.launch({ headless: true, ...(process.env.CRITICAL_CSS_CHROMIUM ? { executablePath: process.env.CRITICAL_CSS_CHROMIUM } : {}) });
try {
	for (const width of widths) {
		for (const path of paths) {
			const mobile = width < 768;
			const context = await browser.newContext({ viewport: { width, height: 900 }, isMobile: mobile, hasTouch: mobile });
			await context.route(url => blocked.test(url.href), route => route.abort());
			const page = await context.newPage();
			const cdp = await context.newCDPSession(page);
			const ids = new Map();
			cdp.on('CSS.styleSheetAdded', ({ header }) => {
				// Inline <style> sheets report names such as "rocket-lazyload-inline-css".
				const url = URL.canParse(header.sourceURL) ? new URL(header.sourceURL) : null;
				const handle = url && Object.keys(sheets).find(key => sheets[key] === url.pathname);
				if (handle) {
					ids.set(header.styleSheetId, handle);
					versions[handle] = url.searchParams.get('ver') || '';
				}
			});
			await cdp.send('DOM.enable');
			await cdp.send('CSS.enable');
			await cdp.send('CSS.startRuleUsageTracking');
			await page.goto(new URL(path, base).href, { waitUntil: 'load', timeout: 120000 });
			// Walk the whole document so late, below-the-fold templates are covered too.
			await page.evaluate(async () => {
				for (let y = 0; y < document.body.scrollHeight; y += innerHeight / 2) {
					scrollTo(0, y);
					await new Promise(resolve => setTimeout(resolve, 60));
				}
				scrollTo(0, 0);
			});
			await page.waitForTimeout(500);
			const { ruleUsage } = await cdp.send('CSS.stopRuleUsageTracking');
			for (const rule of ruleUsage) {
				const handle = ids.get(rule.styleSheetId);
				if (handle && rule.used) used[handle].add(rule.startOffset);
			}
			for (const [id, handle] of ids) {
				texts[handle] ??= (await cdp.send('CSS.getStyleSheetText', { styleSheetId: id })).text;
			}
			await context.close();
			console.error(`covered ${width}px ${path}`);
		}
	}
} finally {
	await browser.close();
}

const manifest = {};
await mkdir(outDir, { recursive: true });
for (const [handle, source] of Object.entries(sheets)) {
	if (!texts[handle]) throw new Error(`${handle} was not loaded by any sampled page`);
	const root = postcss.parse(texts[handle]);
	const sheetDir = source.slice(0, source.lastIndexOf('/') + 1);
	root.walkComments(comment => comment.remove());
	root.walkRules(rule => {
		if (rule.parent?.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) return;
		if (!used[handle].has(rule.source.start.offset)) rule.remove();
	});
	// Inline CSS resolves relative URLs against the page, not the original sheet. The query
	// (e.g. newspaper.woff?24) must survive so the full sheet reuses the same font download.
	root.walkDecls(decl => {
		decl.value = decl.value.replace(/url\(\s*(['"]?)(?!data:|https?:|\/)([^'")]+)\1\s*\)/gi, (_, quote, file) => {
			const resolved = new URL(file, `https://x${sheetDir}`);
			return `url(${quote}${resolved.pathname}${resolved.search}${resolved.hash}${quote})`;
		});
	});
	root.walkAtRules(atRule => {
		if (/keyframes$/i.test(atRule.name)) atRule.remove();
		else if (atRule.nodes && atRule.nodes.length === 0) atRule.remove();
	});
	root.walkAtRules(atRule => { if (atRule.nodes && atRule.nodes.length === 0) atRule.remove(); });
	root.walk(node => {
		node.raws.before = '';
		node.raws.after = '';
		node.raws.between = node.type === 'decl' ? ':' : '';
		node.raws.semicolon = false;
	});
	const css = root.toString().replace(/\s*\n\s*/g, '');
	const file = `critical-${handle}.css`;
	await writeFile(new URL(file, outDir), css + '\n');
	manifest[handle] = {
		source,
		version: versions[handle],
		source_sha256: createHash('sha256').update(texts[handle]).digest('hex'),
		critical: file,
		bytes: Buffer.byteLength(css),
	};
	console.error(`${handle}: ${Buffer.byteLength(css)} of ${Buffer.byteLength(texts[handle])} bytes`);
}
await writeFile(new URL('critical-manifest.json', outDir), JSON.stringify({ base: base.origin, widths, paths, sheets: manifest }, null, '\t') + '\n');
