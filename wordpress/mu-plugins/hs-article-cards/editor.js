/* global hsArticleCards, tinymce, wp */
(() => {
	'use strict';
	const config = hsArticleCards;
	const labels = config.labels;
	const byId = id => document.getElementById(`hs-ac-${id}`);
	const dialog = document.getElementById('hs-article-cards-dialog');
	if (!dialog) return;
	const source = byId('source'), category = byId('category'), search = byId('search');
	const results = byId('results'), preview = byId('preview'), insert = byId('insert');
	const layout = byId('layout'), status = byId('status');
	let page = 1, pages = 1, categoryPage = 0, selected = null, opener = null;
	let bookmark = null, editor = null, selection = null, debounce = null;
	let generation = 0, categoryGeneration = 0, inserting = false;
	const requests = new Map();

	function node(tag, className = '', text = '') {
		const element = document.createElement(tag);
		element.className = className;
		element.textContent = text;
		return element;
	}

	async function request(channel, fields) {
		requests.get(channel)?.abort();
		const controller = new AbortController();
		requests.set(channel, controller);
		const timer = setTimeout(() => controller.abort(), 12000);
		try {
			const response = await fetch(config.url, {
				method: 'POST', credentials: 'same-origin', signal: controller.signal,
				body: new URLSearchParams({ action: 'hs_article_cards', nonce: config.nonce, postId: config.postId, source: source.value, ...fields }),
			});
			const payload = await response.json();
			if (!response.ok || !payload.success) throw new Error(payload.data?.message || labels.error);
			return payload.data;
		} finally {
			clearTimeout(timer);
			if (requests.get(channel) === controller) requests.delete(channel);
		}
	}

	function thumbnail(article, className) {
		const fallback = () => {
			const isPreview = className === 'hs-article-card__image';
			const placeholder = node('span', isPreview ? 'hs-article-card__placeholder' : 'hs-ac-no-image', isPreview ? '↗' : labels.noImage);
			if (isPreview) placeholder.setAttribute('aria-hidden', 'true');
			return placeholder;
		};
		if (!article.image) return fallback();
		const image = node('img', className);
		image.src = article.image;
		image.alt = '';
		image.loading = 'lazy';
		image.referrerPolicy = 'no-referrer';
		image.addEventListener('error', () => image.replaceWith(fallback()), { once: true });
		return image;
	}

	function showPreview() {
		preview.replaceChildren();
		if (!selected) return;
		const card = node('div', `hs-article-card hs-article-card--${layout.value}`);
		const link = node('a', 'hs-article-card__link');
		link.href = selected.url;
		link.tabIndex = -1;
		link.setAttribute('aria-label', `${selected.title} — ${labels.newTab}`);
		const text = node('span', 'hs-article-card__text');
		text.append(node('span', 'hs-article-card__title', selected.title), node('span', 'hs-article-card__date', selected.date_label));
		link.append(thumbnail(selected, 'hs-article-card__image'), text, node('span', 'hs-article-card__arrow', '↗'));
		card.append(link);
		preview.append(card);
	}

	function resetSelection() {
		selected = null;
		insert.disabled = true;
		preview.replaceChildren();
	}

	function navigation(busy = false) {
		byId('prev').disabled = busy || page <= 1;
		byId('next').disabled = busy || page >= pages;
		byId('page').textContent = `${labels.page} ${page} ${labels.of} ${pages}`;
	}

	async function loadPosts() {
		const ticket = ++generation;
		resetSelection();
		results.replaceChildren();
		results.setAttribute('aria-busy', 'true');
		status.textContent = labels.loading;
		byId('retry').hidden = true;
		navigation(true);
		try {
			const data = await request('posts', { mode: 'posts', search: search.value, category: category.value, page });
			if (ticket !== generation || !dialog.open) return;
			pages = data.pages;
			for (const article of data.items) {
				const choice = node('button', 'hs-ac-choice');
				choice.type = 'button';
				choice.setAttribute('aria-pressed', 'false');
				const text = node('span');
				text.append(node('strong', '', article.title), node('small', '', article.date_label));
				choice.append(thumbnail(article, ''), text);
				choice.addEventListener('click', () => {
					if (inserting) return;
					results.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', 'false'));
					choice.setAttribute('aria-pressed', 'true');
					selected = article;
					insert.disabled = false;
					showPreview();
				});
				results.append(choice);
			}
			status.textContent = data.items.length ? labels.ready : labels.empty;
			navigation();
		} catch (error) {
			if (ticket !== generation || !dialog.open) return;
			status.textContent = error.name === 'AbortError' ? labels.error : error.message;
			byId('retry').hidden = false;
		} finally {
			if (ticket === generation) results.setAttribute('aria-busy', 'false');
		}
	}

	async function loadCategories(reset = false) {
		const ticket = ++categoryGeneration;
		if (reset) {
			categoryPage = 0;
			category.replaceChildren(new Option(labels.categories, '0'));
		}
		byId('more-categories').disabled = true;
		byId('category-status').textContent = '';
		try {
			const data = await request('categories', { mode: 'categories', page: categoryPage + 1 });
			if (ticket !== categoryGeneration || !dialog.open) return;
			for (const term of data.items) category.add(new Option(term.name, term.id));
			categoryPage++;
			byId('more-categories').hidden = categoryPage >= data.pages;
		} catch {
			if (ticket !== categoryGeneration || !dialog.open) return;
			byId('category-status').textContent = labels.categoryError;
			byId('more-categories').hidden = false;
		} finally {
			if (ticket === categoryGeneration) byId('more-categories').disabled = false;
		}
	}

	function filterChanged(delay = 0) {
		clearTimeout(debounce);
		generation++;
		requests.get('posts')?.abort();
		resetSelection();
		results.replaceChildren();
		status.textContent = labels.loading;
		navigation(true);
		page = 1;
		debounce = setTimeout(loadPosts, delay);
	}

	function open(event) {
		opener = event.currentTarget;
		editor = window.tinymce?.get('content');
		bookmark = editor && !editor.isHidden() ? editor.selection.getBookmark(2, true) : null;
		const textarea = document.getElementById('content');
		selection = textarea ? [textarea.selectionStart, textarea.selectionEnd] : null;
		dialog.showModal();
		search.focus();
		loadCategories(true);
		loadPosts();
	}

	function close() {
		generation++;
		categoryGeneration++;
		clearTimeout(debounce);
		requests.forEach(controller => controller.abort());
		requests.clear();
		resetSelection();
		opener?.focus();
	}

	function insertHtml(html) {
		if (config.blockEditor && window.wp?.blocks && wp.data) {
			wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/html', { content: html }));
		} else if (editor && !editor.isHidden()) {
			editor.focus();
			if (bookmark) editor.selection.moveToBookmark(bookmark);
			editor.undoManager.transact(() => editor.insertContent(`${html}<p></p>`));
			editor.save();
		} else {
			const textarea = document.getElementById('content');
			if (!textarea || !selection) throw new Error(labels.editorError);
			textarea.setRangeText(`\n${html}\n`, selection[0], selection[1], 'end');
			textarea.dispatchEvent(new Event('input', { bubbles: true }));
			textarea.dispatchEvent(new Event('change', { bubbles: true }));
		}
	}

	insert.addEventListener('click', async () => {
		if (!selected || inserting) return;
		const ticket = generation;
		inserting = true;
		insert.disabled = true;
		insert.textContent = labels.inserting;
		[source, category, search, layout].forEach(control => { control.disabled = true; });
		try {
			const data = await request('card', { mode: 'card', id: selected.id, layout: layout.value });
			if (ticket !== generation || !dialog.open) return;
			insertHtml(data.html);
			dialog.close();
		} catch (error) {
			if (ticket === generation && dialog.open) status.textContent = error.name === 'AbortError' ? labels.error : error.message;
		} finally {
			inserting = false;
			insert.disabled = !selected;
			insert.textContent = labels.insert;
			[source, category, search, layout].forEach(control => { control.disabled = false; });
		}
	});

	document.querySelectorAll('.hs-article-cards-open').forEach(button => button.addEventListener('click', open));
	byId('close').addEventListener('click', () => dialog.close());
	dialog.addEventListener('keydown', event => {
		if (event.key === 'Tab') {
			const controls = [...dialog.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled)')]
				.filter(control => control.getClientRects().length > 0);
			const first = controls[0], last = controls[controls.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
		if (event.key === 'Escape') {
			event.preventDefault();
			event.stopPropagation();
			dialog.close();
		}
	}, true);
	dialog.addEventListener('close', close);
	source.addEventListener('change', () => { loadCategories(true); filterChanged(); });
	category.addEventListener('change', () => filterChanged());
	search.addEventListener('input', () => filterChanged(300));
	search.addEventListener('keydown', event => {
		if (event.key === 'Enter') { event.preventDefault(); filterChanged(); }
	});
	layout.addEventListener('change', showPreview);
	byId('prev').addEventListener('click', () => { if (page > 1 && !inserting) { page--; loadPosts(); } });
	byId('next').addEventListener('click', () => { if (page < pages && !inserting) { page++; loadPosts(); } });
	byId('retry').addEventListener('click', loadPosts);
	byId('more-categories').addEventListener('click', () => loadCategories());
})();
