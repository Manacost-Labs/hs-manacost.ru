/* Native HTML adapters for Valyro Manacost editorial components. */
(() => {
	'use strict';
	const dialog = document.getElementById('hs-v-dialog');
	if (!dialog || !window.hsArticleCards) return;
	const byId = id => document.getElementById(`hs-v-${id}`);
	const field = id => byId(id).value;
	const form = byId('form'), preview = byId('preview'), status = byId('status');
	let opener, editor, bookmark, selection, editingBlock = null, editingElement = null;
	let sections = [{ title: 'Первый раздел', text: '' }, { title: 'Второй раздел', text: '' }];
	let cells = Array.from({ length: 13 }, () => Array(6).fill(''));
	function node(tag, className = '', text) {
		const el = document.createElement(tag);
		if (className) el.className = className;
		if (text !== undefined) el.textContent = text;
		return el;
	}
	function paragraphs(parent, text) {
		text.split('\n').forEach(line => parent.append(node('p', '', line || '\u00a0')));
	}
	function build() {
		const type = field('type');
		const root = node(type === 'spoiler' ? 'details' : type === 'quote' ? 'figure' : 'div', `hs-article-component hs-v-${type}`);
		root.dataset.density = field('density');
		root.dataset.width = field('width');
		if (type === 'alert' || type === 'spoiler') {
			root.dataset.tone = field('tone');
			root.append(node(type === 'spoiler' ? 'summary' : 'p', 'hs-v-title', field('title')));
			const body = node('div', 'hs-v-body');
			paragraphs(body, field('body')); root.append(body);
		} else if (type === 'divider') {
			root.dataset.dividerStyle = field('divider-style');
			root.setAttribute('role', 'separator');
			if (field('divider-style') === 'ornament') {
				const ornament = node('span', 'hs-v-divider-ornament', '◆'); ornament.setAttribute('aria-hidden', 'true'); root.append(ornament);
			}
			if (field('title').trim()) {
				root.setAttribute('aria-label', field('title').trim());
				root.append(node('span', 'hs-v-divider-label', field('title').trim()));
			}
		} else if (type === 'icon-list' || type === 'steps') {
			if (field('title').trim()) root.append(node('p', 'hs-v-title', field('title').trim()));
			const list = node(type === 'steps' ? 'ol' : 'ul', 'hs-v-items');
			list.setAttribute('role', 'list');
			if (type === 'icon-list') root.dataset.icon = field('icon');
			field('items').split('\n').map(line => line.trim()).filter(Boolean).forEach((text, index) => {
				const item = node('li');
				const marker = node('span', 'hs-v-item-marker', type === 'steps' ? String(index + 1) : { check: '✓', star: '★', arrow: '→' }[field('icon')]);
				marker.setAttribute('aria-hidden', 'true');
				item.append(marker, node('span', 'hs-v-item-text', text)); list.append(item);
			});
			root.append(list);
		} else if (type === 'callout') {
			root.dataset.tone = field('tone');
			root.append(node('p', 'hs-v-title', field('title')));
			const body = node('div', 'hs-v-body'); paragraphs(body, field('body')); root.append(body);
			const url = window.hsManacostComponents.safeUrl(field('action-url'));
			if (url) {
				const link = node('a', 'hs-v-action', field('action-label'));
				link.href = url;
				if (field('action-target') === '_blank') { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
				root.append(link);
			}
		} else if (type === 'quote') {
			root.dataset.align = field('align');
			const quote = node('blockquote', 'hs-v-quote-text');
			paragraphs(quote, field('body')); root.append(quote);
			if (field('author').trim()) root.append(node('figcaption', '', field('author')));
		} else if (type === 'author') {
			const avatar = window.hsManacostComponents.safeUrl(field('avatar'));
			if (avatar) {
				const image = node('img', 'hs-v-avatar'); image.src = avatar; image.alt = ''; image.width = 80; image.height = 80;
				image.loading = 'lazy'; image.referrerPolicy = 'no-referrer'; root.append(image);
			} else {
				const initials = field('author-name').trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0)).join('');
				root.append(node('span', 'hs-v-initials', initials || 'М'));
			}
			const content = node('div', 'hs-v-author-content');
			content.append(node('p', 'hs-v-author-name', field('author-name') || 'Имя автора'));
			if (field('author-role').trim()) content.append(node('p', 'hs-v-author-role', field('author-role')));
			const bio = node('div', 'hs-v-author-bio'); paragraphs(bio, field('author-bio')); content.append(bio);
			const url = window.hsManacostComponents.safeUrl(field('social-url'));
			if (url) {
				const link = node('a', 'hs-v-social', field('social-label') || 'Подписаться');
				link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; content.append(link);
			}
			root.append(content);
		} else if (type === 'tabs') {
			sections.forEach(section => {
				const panel = node('div', 'hs-v-panel');
				panel.append(node('h3', 'hs-v-tab-title', section.title));
				(section.before || []).forEach(child => panel.append(child.cloneNode(true)));
				const body = section.originalBody && section.text === section.originalText ? section.originalBody.cloneNode(true) : node('div', 'hs-v-body');
				if (!section.originalBody || section.text !== section.originalText) paragraphs(body, section.text);
				panel.append(body);
				(section.after || []).forEach(child => panel.append(child.cloneNode(true)));
				root.append(panel);
			});
		} else if (type === 'table') {
			const table = node('table');
			table.append(node('caption', '', field('caption')));
			const head = node('thead'), body = node('tbody');
			for (let row = 0; row <= Number(field('rows')); row++) {
				const tr = node('tr');
				for (let col = 0; col < Number(field('cols')); col++) {
					const cell = node(row === 0 ? 'th' : 'td', '', cells[row][col]);
					if (!row) cell.scope = 'col';
					tr.append(cell);
				}
				(row === 0 ? head : body).append(tr);
			}
			table.append(head, body); root.append(table);
			root.tabIndex = 0;
			root.setAttribute('role', 'region');
			root.setAttribute('aria-label', field('caption') || 'Таблица');
		}
		return root;
	}
	function refresh() {
		const type = field('type');
		const focusedSection = document.activeElement?.closest('#hs-v-sections fieldset');
		const tabIndex = focusedSection ? [...byId('sections').children].indexOf(focusedSection)
			: [...preview.querySelectorAll('[role="tab"]')].findIndex(tab => tab.getAttribute('aria-selected') === 'true');
		dialog.querySelectorAll('[data-for]').forEach(group => { group.hidden = !group.dataset.for.split(' ').includes(type); });
		preview.replaceChildren(build());
		window.hsManacostComponents.enhance(preview);
		const tabs = preview.querySelectorAll('[role="tab"]');
		tabs[Math.min(Math.max(tabIndex, 0), tabs.length - 1)]?.click();
		status.textContent = '';
	}
	function sectionFields() {
		byId('sections').replaceChildren();
		sections.forEach((section, index) => {
			const group = node('fieldset'); group.append(node('legend', '', `Вкладка ${index + 1}`));
			for (const [key, title, tag] of [['title', 'Заголовок', 'input'], ['text', 'Текст', 'textarea']]) {
				const input = node(tag); input.id = `hs-v-section-${index}-${key}`; input.value = section[key];
				input.maxLength = key === 'title' ? 120 : 10000;
				if (tag === 'textarea') {
					input.rows = 4;
					if (section.richBody) {
						input.readOnly = true;
						const hint = node('p', '', 'Форматирование и изображения сохранятся. Меняйте это содержимое в основном редакторе статьи.');
						hint.id = `${input.id}-hint`; input.setAttribute('aria-describedby', hint.id); group.append(hint);
					}
				}
				const label = node('label', '', title); label.htmlFor = input.id;
				input.addEventListener('input', () => { section[key] = input.value; });
				group.append(label, input);
			}
			const remove = node('button', 'button', 'Удалить вкладку'); remove.type = 'button'; remove.disabled = sections.length <= 2;
			remove.addEventListener('click', () => {
				sections.splice(index, 1); sectionFields(); refresh();
				byId('sections').querySelectorAll('input')[Math.min(index, sections.length - 1)].focus();
			});
			group.append(remove); byId('sections').append(group);
		});
		byId('add-section').disabled = sections.length >= 6;
	}
	function tableFields() {
		const table = node('table'), body = node('tbody');
		for (let row = 0; row <= Number(field('rows')); row++) {
			const tr = node('tr');
			for (let col = 0; col < Number(field('cols')); col++) {
				const td = node('td'), input = node('input'); input.value = cells[row][col]; input.maxLength = 500;
				input.setAttribute('aria-label', `${row ? `Строка ${row}` : 'Заголовок'}, столбец ${col + 1}`);
				input.addEventListener('input', () => { cells[row][col] = input.value; });
				td.append(input); tr.append(td);
			}
			body.append(tr);
		}
		table.append(body); byId('cells').replaceChildren(table);
	}
	function validate() {
		const type = field('type');
		if (type === 'author') {
			if (!field('author-name').trim() || !field('author-bio').trim() || !field('social-label').trim()) return 'Заполните имя, описание автора и текст кнопки.';
			if (!window.hsManacostComponents.safeUrl(field('social-url')) || (field('avatar').trim() && !window.hsManacostComponents.safeUrl(field('avatar')))) return 'Укажите полный адрес https:// для соцсети и фото.';
		}
		if (['alert', 'spoiler', 'quote', 'callout'].includes(type) && !field('body').trim()) return 'Добавьте текст компонента.';
		if (['alert', 'spoiler', 'callout'].includes(type) && !field('title').trim()) return 'Добавьте заголовок.';
		if (type === 'icon-list' || type === 'steps') {
			const count = field('items').split('\n').filter(line => line.trim()).length;
			if (count < 1 || count > 20) return 'Добавьте от 1 до 20 пунктов, каждый с новой строки.';
		}
		if (type === 'callout' && (!field('action-label').trim() || !window.hsManacostComponents.safeUrl(field('action-url')))) return 'Укажите текст кнопки и полный адрес https:// для ссылки.';
		if (type === 'tabs' && sections.some(section => !section.title.trim() || (!section.text.trim() && !section.hasMedia))) return 'Заполните заголовок и текст каждой вкладки.';
		if (type === 'table' && (!field('caption').trim() || cells[0].slice(0, Number(field('cols'))).some(value => !value.trim()))) return 'Добавьте название таблицы и заголовки столбцов.';
		return '';
	}
	form.addEventListener('input', refresh);
	form.addEventListener('change', refresh);
	for (const id of ['rows', 'cols']) byId(id).addEventListener('change', tableFields);
	byId('add-section').addEventListener('click', () => {
		if (sections.length >= 6) return;
		sections.push({ title: `Раздел ${sections.length + 1}`, text: '' }); sectionFields(); refresh();
		byId('sections').querySelectorAll('input')[sections.length - 1].focus();
	});
	byId('reset').addEventListener('click', () => {
		for (const id of ['tone', 'align', 'density', 'width', 'divider-style', 'icon']) byId(id).selectedIndex = 0;
		refresh();
	});
	byId('close').addEventListener('click', () => dialog.close());
	dialog.addEventListener('close', () => { if (!dialog.open) opener?.focus(); });
	document.querySelectorAll('.hs-components-open').forEach(button => button.addEventListener('click', () => {
		editingBlock = null; editingElement = null; byId('insert').textContent = 'Вставить компонент';
		opener = button; editor = window.tinymce?.get('content');
		bookmark = editor && !editor.isHidden() ? editor.selection.getBookmark(2, true) : null;
		const current = bookmark ? editor.selection.getNode().closest('.hs-article-component') : null;
		if (current && editor.getBody().contains(current)) {
			document.dispatchEvent(new CustomEvent('hs-components-edit', { detail: { html: current.outerHTML, opener: button, editorNode: current } }));
			return;
		}
		const textarea = document.getElementById('content');
		selection = textarea ? [textarea.selectionStart, textarea.selectionEnd] : null;
		sectionFields(); tableFields(); refresh(); dialog.showModal(); byId('type').focus();
	}));
	document.addEventListener('hs-components-edit', event => {
		const { clientId, html, opener: button, editorNode } = event.detail;
		if (clientId) {
			if (window.wp?.data?.select('core/block-editor').getBlock(clientId)?.name !== 'hs-manacost/component') return;
		} else if (!editorNode || !editor?.getBody().contains(editorNode) || !editorNode.classList.contains('hs-article-component')) return;
		editingBlock = clientId || null; editingElement = editorNode || null; opener = button;
		byId('insert').textContent = 'Применить изменения';
		form.reset();
		const root = window.hsManacostComponents.clean(html);
		const type = ['alert', 'spoiler', 'quote', 'tabs', 'table', 'author', 'divider', 'icon-list', 'steps', 'callout'].find(value => root.classList.contains(`hs-v-${value}`)) || 'alert';
		byId('type').value = type;
		for (const [fieldName, attr] of [['density', 'density'], ['width', 'width'], ['tone', 'tone'], ['align', 'align'], ['divider-style', 'dividerStyle'], ['icon', 'icon']]) {
			const value = root.dataset[attr];
			if ([...byId(fieldName).options].some(option => option.value === value)) byId(fieldName).value = value;
		}
		const text = el => el ? [...el.children].map(child => child.textContent).join('\n') || el.textContent : '';
		byId('title').value = root.querySelector('.hs-v-title')?.textContent || '';
		byId('body').value = text(root.querySelector(type === 'quote' ? 'blockquote' : '.hs-v-body'));
		byId('author').value = root.querySelector('figcaption')?.textContent || '';
		if (type === 'divider') byId('title').value = root.querySelector('.hs-v-divider-label')?.textContent || '';
		if (type === 'icon-list' || type === 'steps') byId('items').value = [...root.querySelectorAll('.hs-v-item-text')].map(item => item.textContent).join('\n');
		if (type === 'callout') {
			const link = root.querySelector('.hs-v-action');
			byId('action-label').value = link?.textContent || '';
			byId('action-url').value = link?.getAttribute('href') || '';
			byId('action-target').value = link?.getAttribute('target') === '_blank' ? '_blank' : '_self';
		}
		if (type === 'author') {
			for (const key of ['author-name', 'author-role', 'social-label']) byId(key).value = root.querySelector(key === 'social-label' ? '.hs-v-social' : `.hs-v-${key}`)?.textContent || '';
			byId('author-bio').value = text(root.querySelector('.hs-v-author-bio'));
			byId('social-url').value = root.querySelector('.hs-v-social')?.getAttribute('href') || '';
			byId('avatar').value = root.querySelector('.hs-v-avatar')?.getAttribute('src') || '';
		}
		sections = [...root.querySelectorAll('.hs-v-panel')].slice(0, 6).map(panel => {
			const heading = panel.querySelector('.hs-v-tab-title'), body = panel.querySelector('.hs-v-body');
			const children = [...panel.children], position = children.indexOf(body);
			return {
				title: heading?.textContent || '', text: text(body), originalText: text(body), originalBody: body,
				before: children.filter((child, index) => child !== heading && child !== body && (position < 0 || index < position)),
				after: children.filter((child, index) => position >= 0 && index > position),
				richBody: Boolean(body?.querySelector('img, a, ul, ol, table, strong, em, b, i')),
				hasMedia: Boolean(panel.querySelector('img')),
			};
		});
		while (sections.length < 2) sections.push({ title: `Раздел ${sections.length + 1}`, text: '' });
		cells = Array.from({ length: 13 }, () => Array(6).fill(''));
		if (type === 'table') {
			byId('caption').value = root.querySelector('caption')?.textContent || '';
			const rows = [...root.querySelectorAll('tr')].slice(0, 13);
			byId('rows').value = String(Math.max(1, rows.length - 1));
			byId('cols').value = String(Math.max(2, Math.min(6, rows[0]?.children.length || 2)));
			rows.forEach((row, i) => [...row.children].slice(0, 6).forEach((cell, j) => { cells[i][j] = cell.textContent; }));
		}
		sectionFields(); tableFields(); refresh(); dialog.showModal(); byId('type').focus();
	});
	form.addEventListener('submit', event => {
		event.preventDefault();
		const error = validate();
		if (error) { status.textContent = error; return; }
		const html = build().outerHTML;
		try {
			if (window.hsArticleCards.blockEditor && window.wp?.blocks && wp.data) {
				if (editingBlock) {
					if (wp.data.select('core/block-editor').getBlock(editingBlock)?.name !== 'hs-manacost/component') throw new Error('Block removed');
					wp.data.dispatch('core/block-editor').updateBlockAttributes(editingBlock, { content: html });
				} else {
					wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('hs-manacost/component', { content: html }));
				}
			} else if (editor && !editor.isHidden()) {
				editor.focus();
				if (editingElement) {
					if (!editor.getBody().contains(editingElement)) throw new Error('Component removed');
					editor.undoManager.transact(() => {
						const next = editor.getDoc().importNode(build(), true);
						editingElement.replaceWith(next); editor.selection.select(next); editor.selection.collapse(false); editor.nodeChanged();
					});
				} else {
					if (bookmark) editor.selection.moveToBookmark(bookmark);
					editor.undoManager.transact(() => editor.insertContent(`${html}<p></p>`));
				}
				editor.save();
			} else {
				const textarea = document.getElementById('content');
				if (!textarea || !selection) throw new Error('Editor not ready');
				textarea.setRangeText(`\n${html}\n`, selection[0], selection[1], 'end');
				textarea.dispatchEvent(new Event('input', { bubbles: true })); textarea.dispatchEvent(new Event('change', { bubbles: true }));
			}
			dialog.close();
			// Avoid accidentally repeating content in the next insertion; cancelled forms retain their text.
			form.reset(); sections = [{ title: 'Первый раздел', text: '' }, { title: 'Второй раздел', text: '' }];
			cells = Array.from({ length: 13 }, () => Array(6).fill(''));
		} catch { status.textContent = 'Редактор ещё не готов. Закройте окно и повторите вставку.'; }
	});
})();
