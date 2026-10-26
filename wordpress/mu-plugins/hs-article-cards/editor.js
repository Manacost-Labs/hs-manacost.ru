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
	let page = 1, pages = 1, categoryPage = 0, selected = [], opener = null;
	let bookmark = null, editor = null, selection = null, debounce = null;
	let generation = 0, categoryGeneration = 0, inserting = false;
	const requests = new Map();
	const settingsControls = Object.fromEntries(Object.keys(config.defaults).map(key => [key, byId(`setting-${key}`)]));
	let recent = [];
	const articleKey = article => `${article.source}:${article.id}`;
	const settings = () => Object.fromEntries(Object.entries(settingsControls).map(([key, control]) => [key, control.value]));
	const groupClass = options => `hs-article-cards hs-article-cards--${layout.value} hs-article-cards--columns-${options.columns} hs-article-cards--spacing-${options.spacing} hs-article-cards--width-${options.width}`;

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
		settingsControls.columns.disabled = layout.value !== 'grid';
		if (!selected.length) return;
		const options = settings();
		const group = node('div', groupClass(options));
		for (const article of selected) {
			const card = node('div', `hs-article-card hs-article-card--${layout.value} hs-article-card--image-${options.image} hs-article-card--radius-${options.radius}`);
			const link = node('a', 'hs-article-card__link');
			link.href = article.url;
			link.tabIndex = -1;
			link.setAttribute('aria-label', `${article.title} — ${labels.newTab}`);
			const text = node('span', 'hs-article-card__text');
			text.append(node('span', 'hs-article-card__title', article.title));
			if (options.date === 'show') text.append(node('span', 'hs-article-card__date', article.date_label));
			link.append(thumbnail(article, 'hs-article-card__image'), text, node('span', 'hs-article-card__arrow', '↗'));
			card.append(link);
			group.append(card);
		}
		preview.append(group);
		window.hsFoldedCards?.enhance(preview);
	}

	function updateSelection() {
		insert.disabled = inserting || selected.length === 0;
		insert.textContent = `${labels.insert}${selected.length ? ` (${selected.length})` : ''}`;
		byId('selection-status').textContent = `${labels.selected}: ${selected.length} / 6`;
		byId('selected').replaceChildren();
		dialog.querySelectorAll('.hs-ac-choice, .hs-ac-recent-choice').forEach(button => button.setAttribute('aria-pressed', String(selected.some(article => articleKey(article) === button.dataset.key))));
		selected.forEach((article, index) => {
			const item = node('li', 'hs-ac-selected-item');
			item.append(node('span', '', article.title));
			const actions = node('div', 'hs-ac-selected-actions');
			for (const [action, label, disabled] of [['earlier', labels.earlier, index === 0], ['later', labels.later, index === selected.length - 1], ['remove', labels.remove, false]]) {
				const button = node('button', 'button', label);
				button.type = 'button';
				button.disabled = disabled;
				button.setAttribute('aria-label', `${label}: ${article.title}`);
				button.dataset.action = action;
				button.addEventListener('click', () => {
					if (inserting) return;
					const target = action === 'earlier' ? index - 1 : index + 1;
					if (action === 'remove') selected.splice(index, 1);
					else [selected[index], selected[target]] = [selected[target], selected[index]];
					updateSelection();
					const row = byId('selected').children[Math.min(action === 'remove' ? index : target, selected.length - 1)];
					(row?.querySelector('button:not(:disabled)') || search).focus();
				});
				actions.append(button);
			}
			item.append(actions);
			byId('selected').append(item);
		});
		showPreview();
	}

	function choiceFor(article, isRecent = false) {
		const choice = node('button', isRecent ? 'hs-ac-recent-choice' : 'hs-ac-choice');
		choice.type = 'button';
		choice.dataset.key = articleKey(article);
		choice.setAttribute('aria-pressed', String(selected.some(item => articleKey(item) === articleKey(article))));
		const text = node('span');
		text.append(node('strong', '', article.title), node('small', '', article.date_label));
		choice.append(thumbnail(article, ''), text);
		choice.addEventListener('click', () => {
			if (inserting) return;
			const index = selected.findIndex(item => articleKey(item) === articleKey(article));
			if (index >= 0) selected.splice(index, 1);
			else if (selected.length < 6) selected.push(article);
			else { byId('selection-status').textContent = labels.limit; return; }
			updateSelection();
		});
		return choice;
	}

	function resetSelection() {
		selected = [];
		updateSelection();
	}

	function navigation(busy = false) {
		byId('prev').disabled = busy || page <= 1;
		byId('next').disabled = busy || page >= pages;
		byId('page').textContent = `${labels.page} ${page} ${labels.of} ${pages}`;
	}

	async function loadPosts() {
		const ticket = ++generation;
		results.replaceChildren();
		results.setAttribute('aria-busy', 'true');
		status.textContent = labels.loading;
		byId('retry').hidden = true;
		navigation(true);
		try {
			const data = await request('posts', { mode: 'posts', search: search.value, category: category.value, page });
			if (ticket !== generation || !dialog.open) return;
			pages = data.pages;
			for (const article of data.items) results.append(choiceFor({ ...article, source: source.value }));
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
		results.replaceChildren();
		status.textContent = labels.loading;
		navigation(true);
		page = 1;
		debounce = setTimeout(loadPosts, delay);
	}

	function open(event) {
		if (inserting) return;
		opener = event.currentTarget;
		editor = window.tinymce?.get('content');
		bookmark = editor && !editor.isHidden() ? editor.selection.getBookmark(2, true) : null;
		const textarea = document.getElementById('content');
		selection = textarea ? [textarea.selectionStart, textarea.selectionEnd] : null;
		updateSelection();
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
		if (!selected.length || inserting) return;
		const ticket = generation;
		inserting = true;
		insert.disabled = true;
		insert.textContent = labels.inserting;
		const controls = [...dialog.querySelectorAll('.hs-ac-body button, .hs-ac-body select, .hs-ac-body input')].map(control => [control, control.disabled]);
		controls.forEach(([control]) => { control.disabled = true; });
		try {
			const chosen = [...selected];
			const options = settings();
			const cards = await Promise.all(chosen.map(article => request(`card-${articleKey(article)}`, { mode: 'card', source: article.source, id: article.id, layout: layout.value, settings: JSON.stringify(options) })));
			if (ticket !== generation || !dialog.open) return;
			const group = node('div', groupClass(options));
			group.innerHTML = cards.map(card => card.html).join(''); // Only server-rendered, validated card markup.
			insertHtml(group.outerHTML);
			recent = [...chosen, ...recent.filter(article => !chosen.some(item => articleKey(item) === articleKey(article)))].slice(0, 6);
			byId('recent-results').replaceChildren(...recent.map(article => choiceFor(article, true)));
			byId('recent').hidden = false;
			dialog.close();
		} catch (error) {
			if (ticket === generation && dialog.open) status.textContent = error.name === 'AbortError' ? labels.error : error.message;
		} finally {
			inserting = false;
			controls.forEach(([control, disabled]) => { control.disabled = disabled; });
			updateSelection();
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
	Object.values(settingsControls).forEach(control => control.addEventListener('change', showPreview));
	byId('reset-settings').addEventListener('click', () => {
		layout.value = 'compact';
		Object.entries(config.defaults).forEach(([key, value]) => { settingsControls[key].value = value; });
		showPreview();
	});
	byId('prev').addEventListener('click', () => { if (page > 1 && !inserting) { page--; loadPosts(); } });
	byId('next').addEventListener('click', () => { if (page < pages && !inserting) { page++; loadPosts(); } });
	byId('retry').addEventListener('click', loadPosts);
	byId('more-categories').addEventListener('click', () => loadCategories());
})();
