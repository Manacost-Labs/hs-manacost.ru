/* global hsCardChanges, tinymce, wp */
(() => {
	'use strict';
	const config = hsCardChanges;
	const dialog = document.getElementById('hs-card-changes-dialog');
	if (!dialog) return;
	const byId = id => document.getElementById(`hs-cc-${id}`);
	const search = byId('search'), kind = byId('kind'), date = byId('date'), status = byId('status');
	const results = byId('results'), list = byId('items'), preview = byId('preview'), insert = byId('insert');
	const columns = byId('columns'), size = byId('size'), captions = byId('captions'), itemsStatus = byId('items-status');
	const text = {
		short: 'Введите хотя бы две буквы названия.', loading: 'Загружаем…', empty: 'Ничего не найдено. Проверьте написание или добавьте карту по идентификатору.',
		error: 'Не удалось выполнить запрос. Повторите. Если сессия истекла, сохраните текст и войдите снова.',
		limit: `В статью можно добавить до ${config.maxItems} изменений за раз.`, editorError: 'Редактор ещё не готов. Закройте окно и повторите вставку.',
		pair: (item) => `До — ${item.before.at} UTC · После — ${item.after.at} UTC`,
		single: (item) => `Пары для этой даты нет — будет показана текущая карта (${item.after.at} UTC).`,
		none: 'Для этой карты нет архивных изображений — вставятся только плашки.',
		unavailable: 'История сейчас недоступна — вставятся плашки; карты подтянутся, когда сервис ответит.',
		customLabel: 'Своя метка',
	};
	const toneNames = { auto: 'Цвет: авто', good: 'Лучше', bad: 'Хуже', neutral: 'Нейтрально' };
	let items = [], uid = 0, opener = null, editor = null, bookmark = null, selection = null;
	let searchTicket = 0, composeTicket = 0, searchTimer = null, composeTimer = null, shortcode = '', inserting = false;
	const requests = new Map();

	function node(tag, className = '', content = '') {
		const element = document.createElement(tag);
		element.className = className;
		element.textContent = content;
		return element;
	}

	function button(label, onClick, aria = '', disabled = false) {
		const control = node('button', 'button', label);
		control.type = 'button';
		control.disabled = disabled;
		if (aria) control.setAttribute('aria-label', aria);
		control.addEventListener('click', onClick);
		return control;
	}

	async function request(channel, fields) {
		requests.get(channel)?.abort();
		const controller = new AbortController();
		requests.set(channel, controller);
		const timer = setTimeout(() => controller.abort(), 15000);
		try {
			const response = await fetch(config.url, {
				method: 'POST', credentials: 'same-origin', signal: controller.signal,
				body: new URLSearchParams({ action: 'hs_card_changes', nonce: config.nonce, postId: config.postId, ...fields }),
			});
			const payload = await response.json();
			if (!response.ok || !payload.success) throw new Error(payload.data?.message || text.error);
			return payload.data;
		} catch (error) {
			throw error.name === 'AbortError' ? Object.assign(new Error(text.error), { name: 'AbortError' }) : error;
		} finally {
			clearTimeout(timer);
			if (requests.get(channel) === controller) requests.delete(channel);
		}
	}

	async function loadResults() {
		const term = search.value.trim(), ticket = ++searchTicket;
		results.replaceChildren();
		if (term.length < 2) { status.textContent = text.short; return; }
		status.textContent = text.loading;
		try {
			const data = await request('search', { mode: 'search', term, kind: kind.value });
			if (ticket !== searchTicket || !dialog.open) return;
			for (const found of data.items) results.append(resultRow(found));
			const note = { unavailable: ' Архив карт сейчас недоступен — показан только словарь.', limited: ' Архив карт временно не опрашивается (слишком много запросов) — показан только словарь.' }[data.archive] || '';
			status.textContent = (data.items.length ? `Найдено: ${data.items.length}. Нажмите на карту, чтобы добавить.` : text.empty) + note;
		} catch (error) {
			if (ticket === searchTicket && error.name !== 'AbortError') status.textContent = error.message;
		}
	}

	function resultRow(found) {
		const choice = node('button', 'hs-ccd-result');
		choice.type = 'button';
		if (found.img) {
			const image = node('img');
			image.src = found.img; image.alt = ''; image.loading = 'lazy'; image.referrerPolicy = 'no-referrer';
			choice.append(image);
		}
		const label = node('span');
		label.append(node('strong', '', found.name), node('small', '', `${config.modeNames[found.mode]} · ${found.id}`));
		choice.append(label);
		choice.addEventListener('click', () => addItem(found));
		const item = node('li');
		item.append(choice);
		return item;
	}

	function addById() {
		const id = byId('id').value.trim();
		if (!/^[A-Za-z][A-Za-z0-9_]{1,119}$/.test(id)) { status.textContent = 'Идентификатор — латинские буквы, цифры и «_», например BG36_362.'; return; }
		const mode = kind.value !== 'all' ? kind.value : (/^(BG\d|BGS_|TB_Bacon|BaconShop)/.test(id) ? 'bg' : 'constructed');
		addItem({ id, mode, name: '' });
		byId('id').value = '';
	}

	function addItem(found) {
		if (inserting) return;
		if (items.length >= config.maxItems) { status.textContent = text.limit; return; }
		const item = { uid: ++uid, card: found.id, mode: found.mode, name: found.name, date: date.value, state: 'loading', before: null, after: null, plates: [], message: '' };
		items.push(item);
		status.textContent = `Добавлено: ${found.name || found.id}.`;
		renderItems();
		resolveItem(item);
	}

	async function resolveItem(item) {
		item.state = 'loading';
		renderItems();
		try {
			const data = await request(`resolve-${item.uid}`, { mode: 'resolve', card: item.card, cardMode: item.mode, date: item.date });
			if (!items.includes(item)) return;
			Object.assign(item, { name: item.name || data.name, state: data.state, before: data.before, after: data.after, plates: data.plates.map(plate => ({ ...plate })), message: '' });
		} catch (error) {
			if (!items.includes(item) || error.name === 'AbortError' && dialog.open === false) return;
			Object.assign(item, { state: 'error', message: error.message });
		}
		renderItems();
		scheduleCompose();
	}

	function stateText(item) {
		if (item.state === 'loading') return text.loading;
		if (item.state === 'error') return item.message;
		const value = text[item.state];
		return typeof value === 'function' ? value(item) : value;
	}

	function move(index, offset) {
		const target = index + offset;
		if (target < 0 || target >= items.length || inserting) return;
		[items[index], items[target]] = [items[target], items[index]];
		renderItems();
		list.children[target].querySelector('.hs-ccd-row__tools button:not(:disabled)')?.focus();
		scheduleCompose();
	}

	function removeItem(index) {
		if (inserting) return;
		requests.get(`resolve-${items[index].uid}`)?.abort();
		items.splice(index, 1);
		renderItems();
		(list.children[Math.min(index, items.length - 1)]?.querySelector('.hs-ccd-row__tools button') || search).focus();
		scheduleCompose();
	}

	function tools(item, index) {
		const box = node('div', 'hs-ccd-row__tools');
		box.append(
			button('Выше', () => move(index, -1), `Выше: ${item.name}`, index === 0),
			button('Ниже', () => move(index, 1), `Ниже: ${item.name}`, index === items.length - 1),
			button('Убрать', () => removeItem(index), `Убрать: ${item.name}`)
		);
		return box;
	}

	function plateRow(item, plate, index) {
		const row = node('li', 'hs-ccd-plate');
		const custom = plate.key === 'custom', labelled = plate.key !== 'text';
		if (custom) {
			const input = node('input');
			input.maxLength = 30; input.value = plate.label; input.setAttribute('aria-label', 'Название плашки');
			input.addEventListener('input', () => { plate.label = input.value.trim(); scheduleCompose(); });
			row.append(input);
		} else {
			row.append(node('span', 'hs-ccd-plate__label', plate.label));
		}
		if (labelled) {
			for (const [field, label] of [['old', 'Было'], ['new', 'Стало']]) {
				const input = node('input', 'hs-ccd-plate__value');
				input.maxLength = 14; input.value = plate[field]; input.setAttribute('aria-label', `${plate.label}: ${label}`);
				input.addEventListener('input', () => { plate[field] = input.value.trim(); plate.tone = 'auto'; row.querySelector('select').value = 'auto'; scheduleCompose(); });
				row.append(input);
			}
		}
		const tone = node('select');
		tone.setAttribute('aria-label', `${plate.label}: цвет`);
		for (const [value, name] of Object.entries(toneNames)) tone.add(new Option(name, value));
		tone.value = plate.tone;
		tone.addEventListener('change', () => { plate.tone = tone.value; scheduleCompose(); });
		const remove = button('×', () => { item.plates.splice(index, 1); renderItems(); scheduleCompose(); }, `Убрать плашку: ${plate.label}`);
		remove.classList.add('hs-ccd-plate__remove');
		row.append(tone, remove);
		return row;
	}

	function plateAdder(item) {
		const box = node('div', 'hs-ccd-plate-add');
		const select = node('select');
		select.setAttribute('aria-label', 'Новая плашка');
		for (const [key, label] of Object.entries(config.keys[item.mode])) select.add(new Option(label, key));
		select.add(new Option(text.customLabel, 'custom'));
		box.append(select, button('Добавить плашку', () => {
			const key = select.value, label = key === 'custom' ? '' : config.keys[item.mode][key];
			item.plates.push({ key, label: label || 'Новая', old: '', new: '', tone: 'auto' });
			renderItems();
			scheduleCompose();
		}));
		return box;
	}

	function itemRow(item, index) {
		const row = node('li', 'hs-ccd-row');
		const head = node('div', 'hs-ccd-row__head');
		const title = node('strong', '', item.name || item.card);
		head.append(title, node('span', 'hs-ccd-badge', config.modeNames[item.mode]), tools(item, index));
		const state = node('p', `hs-ccd-state hs-ccd-state--${item.state}`, stateText(item));
		row.append(head, state);
		if (item.state === 'error') row.append(button('Повторить', () => resolveItem(item), `Повторить: ${item.name}`));
		if (item.state === 'loading' || item.state === 'error') return row;
		const when = node('label', '', 'Дата патча (UTC) ');
		const input = node('input');
		input.type = 'datetime-local'; input.value = item.date;
		input.addEventListener('change', () => { if (input.value) { item.date = input.value; resolveItem(item); } });
		when.append(input);
		const plates = node('fieldset', 'hs-ccd-plates');
		plates.append(node('legend', '', 'Плашки изменений'));
		const platesList = node('ul', 'hs-ccd-plates__list');
		item.plates.forEach((plate, plateIndex) => platesList.append(plateRow(item, plate, plateIndex)));
		plates.append(platesList, plateAdder(item), button('Пересчитать из истории', () => resolveItem(item), `Пересчитать плашки из истории: ${item.name}`));
		row.append(when, plates);
		return row;
	}

	function renderItems() {
		list.replaceChildren(...items.map(itemRow));
		const count = items.length;
		itemsStatus.textContent = count ? `Выбрано изменений: ${count} из ${config.maxItems}.` : 'Найдите карту слева и нажмите на неё, чтобы добавить изменение.';
		updateInsert();
	}

	function updateInsert() {
		insert.disabled = inserting || !shortcode;
		insert.textContent = `Вставить изменения${items.length ? ` (${items.length})` : ''}`;
	}

	function scheduleCompose() {
		shortcode = '';
		updateInsert();
		clearTimeout(composeTimer);
		composeTimer = setTimeout(compose, 500);
	}

	async function compose() {
		const rows = items.filter(item => item.state !== 'loading' && item.state !== 'error').map(item => ({
			card: item.card, mode: item.mode, date: item.date, name: item.name, before: item.before?.hash || '', after: item.after?.hash || '', plates: item.plates,
		}));
		const ticket = ++composeTicket;
		if (!rows.length) { preview.replaceChildren(); shortcode = ''; updateInsert(); return; }
		try {
			const data = await request('compose', { mode: 'compose', items: JSON.stringify(rows), columns: columns.value, size: size.value, captions: captions.checked ? 'yes' : 'no' });
			if (ticket !== composeTicket) return;
			preview.innerHTML = data.html; // Server-rendered from validated fields only.
			shortcode = data.shortcode;
			fitPreview();
		} catch (error) {
			if (ticket !== composeTicket || error.name === 'AbortError') return;
			itemsStatus.textContent = error.message;
			shortcode = '';
		}
		updateInsert();
	}

	// The preview is laid out at article width (680 px) and scaled to the panel.
	function fitPreview() {
		const frame = byId('fit');
		const scale = window.matchMedia('(max-width: 720px)').matches ? 1 : Math.min(1, frame.clientWidth / 680);
		preview.style.transform = scale < 1 ? `scale(${scale})` : '';
		frame.style.height = scale < 1 ? `${Math.ceil(preview.offsetHeight * scale)}px` : '';
	}

	function insertShortcode(code) {
		if (config.blockEditor && window.wp?.blocks && wp.data) {
			wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/shortcode', { text: code }));
		} else if (editor && !editor.isHidden()) {
			const paragraphs = code.split('\n').map(line => `<p>${line.replace(/&/g, '&amp;').replace(/</g, '&lt;')}</p>`).join('');
			editor.focus();
			if (bookmark) editor.selection.moveToBookmark(bookmark);
			editor.undoManager.transact(() => editor.insertContent(paragraphs));
			editor.save();
		} else {
			const textarea = document.getElementById('content');
			if (!textarea || !selection) throw new Error(text.editorError);
			textarea.setRangeText(`\n${code}\n`, selection[0], selection[1], 'end');
			textarea.dispatchEvent(new Event('input', { bubbles: true }));
			textarea.dispatchEvent(new Event('change', { bubbles: true }));
		}
	}

	function open(event) {
		if (inserting) return;
		opener = event.target.closest('.hs-card-changes-open');
		editor = window.tinymce?.get('content');
		bookmark = editor && !editor.isHidden() ? editor.selection.getBookmark(2, true) : null;
		const textarea = document.getElementById('content');
		selection = textarea ? [textarea.selectionStart, textarea.selectionEnd] : null;
		date.value = config.defaultDate;
		status.textContent = text.short;
		renderItems();
		dialog.showModal();
		search.focus();
	}

	function close() {
		if (dialog.open) return;
		searchTicket++; composeTicket++;
		clearTimeout(searchTimer); clearTimeout(composeTimer);
		requests.forEach(controller => controller.abort());
		requests.clear();
		items = []; shortcode = ''; search.value = '';
		results.replaceChildren(); preview.replaceChildren();
		renderItems();
		opener?.focus();
	}

	insert.addEventListener('click', async () => {
		if (!items.length || inserting) return;
		inserting = true;
		updateInsert();
		clearTimeout(composeTimer);
		try {
			await compose();
			if (!shortcode) throw new Error(itemsStatus.textContent || text.error);
			insertShortcode(shortcode);
			dialog.close();
		} catch (error) {
			itemsStatus.textContent = error.message;
		} finally {
			inserting = false;
			updateInsert();
		}
	});

	document.addEventListener('click', event => { if (event.target instanceof Element && event.target.closest('.hs-card-changes-open')) open(event); });
	preview.addEventListener('click', event => { if (event.target instanceof Element && event.target.closest('a')) event.preventDefault(); });
	byId('close').addEventListener('click', () => dialog.close());
	if (window.ResizeObserver) { const observer = new ResizeObserver(fitPreview); observer.observe(byId('fit')); observer.observe(preview); }
	dialog.addEventListener('close', close);
	dialog.addEventListener('keydown', event => {
		if (event.key === 'Tab') {
			const controls = [...dialog.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled)')].filter(control => control.getClientRects().length > 0);
			const first = controls[0], last = controls[controls.length - 1];
			if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
			else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
		}
		if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); dialog.close(); }
	}, true);
	search.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadResults, 300); });
	search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); clearTimeout(searchTimer); loadResults(); } });
	kind.addEventListener('change', loadResults);
	byId('add-id').addEventListener('click', addById);
	byId('id').addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); addById(); } });
	for (const control of [columns, size, captions]) control.addEventListener('change', scheduleCompose);
})();
