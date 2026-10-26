/* LinkPreview adapter: links keep their native click; metadata loads on intent. */
(() => {
	'use strict';
	if (!window.hsLinkPreview) return;
	const links = [...document.querySelectorAll('a[data-hs-preview]')];
	if (!links.length) return;
	const bubble = document.createElement('aside'); bubble.className = 'hs-link-preview'; bubble.id = 'hs-link-preview';
	bubble.hidden = true; bubble.setAttribute('role', 'dialog'); bubble.setAttribute('aria-label', 'Предпросмотр ссылки');
	const image = document.createElement('img'), title = document.createElement('strong'), description = document.createElement('p'), host = document.createElement('span');
	const close = document.createElement('button'); close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'Закрыть предпросмотр');
	image.alt = ''; image.referrerPolicy = 'no-referrer'; image.hidden = true;
	bubble.append(image, host, title, description, close); document.body.append(bubble);
	const cache = new Map();
	let active = null, opener = null, opening = null, closing = null;
	function hide() {
		clearTimeout(opening); clearTimeout(closing); bubble.hidden = true;
		opener?.setAttribute('aria-expanded', 'false'); active = null;
	}
	function position() {
		if (!active || bubble.hidden) return;
		const box = active.getBoundingClientRect(), size = bubble.getBoundingClientRect();
		const left = Math.max(8, Math.min(innerWidth - size.width - 8, box.left));
		const above = box.top - size.height - 10;
		const top = above >= 8 ? above : Math.max(8, Math.min(innerHeight - size.height - 8, box.bottom + 10));
		bubble.style.left = `${left}px`; bubble.style.top = `${top}px`;
	}
	async function metadata(link) {
		const signature = link.dataset.hsSignature;
		if (!signature) return null;
		const href = link.dataset.hsPreviewUrl || link.getAttribute('href'), key = `${link.dataset.hsPreview}|${href}|${signature}`;
		if (!cache.has(key)) {
			cache.set(key, (async () => {
				const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), 6000);
				try {
					const url = new URL(hsLinkPreview.endpoint, location.href);
					url.searchParams.set('post', link.dataset.hsPreview); url.searchParams.set('url', href); url.searchParams.set('signature', signature);
					const response = await fetch(url, { credentials: 'omit', signal: controller.signal });
					return response.ok ? await response.json() : null;
				} catch { return null; } finally { clearTimeout(timeout); }
			})());
		}
		return cache.get(key);
	}
	async function show(link, button = null) {
		clearTimeout(opening); clearTimeout(closing);
		opener?.setAttribute('aria-expanded', 'false'); active = link; opener = button;
		opener?.setAttribute('aria-expanded', 'true');
		title.textContent = link.textContent.trim() || new URL(link.href).hostname;
		host.textContent = new URL(link.href).hostname; description.textContent = ''; image.hidden = true; image.removeAttribute('src');
		bubble.hidden = false; position();
		const data = await metadata(link);
		if (active !== link || !data) return;
		if (typeof data.title === 'string' && data.title) title.textContent = data.title;
		if (typeof data.description === 'string') description.textContent = data.description;
		try {
			const url = new URL(data.image);
			if (['http:', 'https:'].includes(url.protocol) && !url.username && !url.password) { image.src = url.href; image.hidden = false; }
		} catch { /* A missing cover keeps text and the original link usable. */ }
		position();
	}
	const schedule = link => { clearTimeout(opening); clearTimeout(closing); opening = setTimeout(() => show(link), 300); };
	const leave = () => {
		if (active && (opener || document.activeElement === active || bubble.contains(document.activeElement))) return;
		clearTimeout(opening); clearTimeout(closing); closing = setTimeout(hide, 200);
	};
	for (const link of links) {
		link.addEventListener('pointerenter', event => { if (event.pointerType !== 'touch') schedule(link); });
		link.addEventListener('pointerleave', event => { if (event.pointerType !== 'touch') leave(); });
		link.addEventListener('focus', () => schedule(link));
		link.addEventListener('blur', event => { if (!bubble.contains(event.relatedTarget)) leave(); });
		link.addEventListener('click', hide);
		const button = document.createElement('button'); button.type = 'button'; button.className = 'hs-link-peek'; button.textContent = 'ⓘ';
		button.setAttribute('aria-label', `Предпросмотр: ${link.textContent.trim().slice(0, 120) || 'ссылка'}`);
		button.setAttribute('aria-controls', bubble.id); button.setAttribute('aria-expanded', 'false');
		button.addEventListener('click', () => { if (active === link && !bubble.hidden) hide(); else show(link, button); });
		link.after(button);
	}
	bubble.addEventListener('pointerenter', () => clearTimeout(closing)); bubble.addEventListener('pointerleave', leave);
	bubble.addEventListener('focusin', () => clearTimeout(closing));
	bubble.addEventListener('focusout', event => { if (!bubble.contains(event.relatedTarget)) leave(); });
	close.addEventListener('click', () => { const target = opener || active; hide(); target?.focus(); clearTimeout(opening); });
	image.addEventListener('load', position); image.addEventListener('error', () => { image.hidden = true; position(); });
	document.addEventListener('keydown', event => { if (event.key === 'Escape' && !bubble.hidden) { const target = opener || active; hide(); if (bubble.contains(document.activeElement)) target?.focus(); clearTimeout(opening); } });
	document.addEventListener('pointerdown', event => { if (!bubble.contains(event.target) && event.target !== active && event.target !== opener) hide(); });
	window.addEventListener('resize', position);
	window.addEventListener('scroll', () => {
		if (!active) return;
		const box = active.getBoundingClientRect();
		if (box.bottom < 0 || box.top > innerHeight) hide(); else position();
	}, { passive: true });
})();
