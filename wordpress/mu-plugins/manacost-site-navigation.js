(function () {
	'use strict';

	const connection = navigator.connection;
	if (connection && (connection.saveData || /^(slow-)?2g$/.test(connection.effectiveType))) return;
	let supportsWebp = false;
	const formatProbe = new Image();
	formatProbe.onload = () => { supportsWebp = formatProbe.naturalWidth === 1; };
	formatProbe.src = 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==';

	let timer;
	let candidate;
	let controller;
	let image;
	let used = false;
	let touch = false;
	const selector = '.td-image-wrap, .td-module-title a, .td-related-title a, .td-post-next-prev-content a';

	function cancel() {
		clearTimeout(timer);
		candidate = null;
	}

	function destination(target) {
		const link = target instanceof Element ? target.closest(selector) : null;
		if (!link || link.tagName !== 'A' || link.hasAttribute('download')
			|| link.closest('.no-prefetch') || (link.target && link.target !== '_self')
			|| link.relList.contains('nofollow')) return null;
		let url;
		let path;
		try {
			url = new URL(link.href, location.href);
			path = decodeURIComponent(url.pathname);
		} catch { return null; }
		if (url.origin !== location.origin || url.search || url.hash || url.pathname === location.pathname
			|| !/^\/[^/]+\/$/.test(path)
			|| /^\/(?:wp-|account|reader|profile|community|login|logout|search)/i.test(path)) return null;
		return link;
	}

	function source(value, base) {
		if (!value || value.length > 2048) return null;
		const url = new URL(value, base);
		const path = decodeURIComponent(url.pathname);
		return url.origin === location.origin && path.startsWith('/wp-content/uploads/')
			&& !path.split('/').includes('..') && /\.(?:jpe?g|png|webp|avif|gif)$/i.test(path) ? url.href : null;
	}

	async function readDocument(response) {
		const reader = response.body.getReader();
		const decoder = new TextDecoder();
		let bytes = 0;
		let html = '';
		try {
			while (true) {
				const chunk = await reader.read();
				if (chunk.done) return html + decoder.decode();
				bytes += chunk.value.byteLength;
				if (bytes > 524288) return '';
				html += decoder.decode(chunk.value, { stream: true });
				if (findCover(html)) return html;
			}
		} finally {
			await reader.cancel().catch(() => {});
		}
	}

	function findCover(html) {
		const template = document.createElement('template');
		// This detached, inert template is never inserted into the live document.
		template.innerHTML = html;
		return template.content.querySelector('.td-post-featured-image > img, .td-post-featured-image > a > img');
	}

	function warmCover(html, base) {
		const cover = findCover(html);
		if (!cover) return;
		const src = source(cover.getAttribute('data-lazy-src') || cover.getAttribute('src'), base);
		if (!src) return;
		const srcset = cover.getAttribute('data-lazy-srcset') || cover.getAttribute('srcset') || '';
		if (srcset.length > 8192 || (srcset && srcset.split(',').some(item => {
			const parts = item.trim().split(/\s+/);
			return parts.length !== 2 || !/^\d+(?:\.\d+)?[wx]$/.test(parts[1]) || !source(parts[0], base);
		}))) return;
		image = new Image();
		image.fetchPriority = 'low';
		image.decoding = 'async';
		image.sizes = cover.getAttribute('data-lazy-sizes') || cover.getAttribute('sizes') || '100vw';
		image.srcset = srcset;
		image.src = src;
	}

	async function warm(link) {
		if (used || !supportsWebp || document.visibilityState !== 'visible' || document.readyState !== 'complete') return;
		used = true;
		controller = new AbortController();
		const timeout = setTimeout(() => controller.abort(), 2500);
		try {
			// Match the modern document variant without guessing from file names.
			const response = await fetch(link.href, {
				credentials: 'same-origin', redirect: 'error', signal: controller.signal,
				headers: { Accept: 'text/html,image/webp' }
			});
			if (!response.ok || !response.headers.get('content-type')?.includes('text/html')) return;
			const html = await readDocument(response);
			if (!controller.signal.aborted && document.visibilityState === 'visible') warmCover(html, link.href);
		} catch {
			// A failed hint must never affect the user's ordinary navigation.
		} finally {
			clearTimeout(timeout);
		}
	}

	function schedule(target) {
		cancel();
		if (used) return;
		candidate = destination(target);
		if (candidate) timer = setTimeout(() => warm(candidate), 180);
	}

	document.addEventListener('pointerover', event => {
		touch = event.pointerType !== 'mouse';
		if (!touch && candidate !== destination(event.target)) schedule(event.target);
	}, { passive: true });
	document.addEventListener('pointerout', event => {
		if (candidate && !candidate.contains(event.relatedTarget)) cancel();
	}, { passive: true });
	document.addEventListener('focusin', event => { if (!touch) schedule(event.target); });
	document.addEventListener('keydown', event => { if (event.key === 'Tab') touch = false; });
	document.addEventListener('focusout', cancel);
	addEventListener('pagehide', () => {
		cancel();
		controller?.abort();
		image?.removeAttribute('srcset');
		image?.removeAttribute('src');
		image = null;
	});
}());
