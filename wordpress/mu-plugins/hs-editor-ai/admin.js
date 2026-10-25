(function () {
	'use strict';
	const config = window.hsEditorAi;
	const dialog = document.getElementById('hs-editor-ai-dialog');
	if (!config || !dialog) return;
	const status = dialog.querySelector('#hs-editor-ai-status');
	const run = dialog.querySelector('#hs-editor-ai-run');
	const apply = dialog.querySelector('#hs-editor-ai-apply');
	const compare = dialog.querySelector('.hs-editor-ai-compare');
	const input = dialog.querySelector('.hs-editor-ai-input');
	const markup = dialog.querySelector('#hs-editor-ai-markup');
	const steps = dialog.querySelector('.hs-editor-ai-steps');
	const dismiss = dialog.querySelector('#hs-editor-ai-dismiss');
	const area = document.getElementById('content');
	if (!area) return;
	let session = null;
	let opener = null;
	let busy = false;
	const titles = { cards: 'Заполнить карты', editor: 'Редактура EditorTeam', proofread: 'Пунктуация и опечатки' };
	const descriptions = {
		cards: 'Добавим hs_card или hs_bg к названиям карт с учётом всей статьи. Слова останутся прежними.',
		editor: 'Отредактируем статью по правилам EditorTeam и отдельно проверим сохранность смысла.',
		proofread: 'Исправим пунктуацию и небольшие опечатки, сохранив формулировки и числа.',
	};
	function announce(message, state = '') {
		if (status.textContent !== message) status.textContent = message;
		dialog.dataset.state = state;
		steps.querySelectorAll('li').forEach(step => {
			if (step.dataset.step === state) step.setAttribute('aria-current', 'step');
			else step.removeAttribute('aria-current');
		});
	}
	// Plain-text preview never inserts article/model HTML into the document.
	function readable(source) {
		const entities = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' };
		return source.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, '')
			.replace(/<br\s*\/?\s*>|<\/(?:p|div|h[1-6]|li|blockquote)>/gi, '\n')
			.replace(/<[^>]*>/g, '')
			.replace(/&(#x[\da-f]+|#\d+|amp|lt|gt|quot|apos|nbsp);/gi, (entity, code) => {
				if (!code.startsWith('#')) return entities[code.toLowerCase()];
				const number = code[1].toLowerCase() === 'x' ? parseInt(code.slice(2), 16) : Number(code.slice(1));
				return number > 0 && number <= 0x10ffff && !(number >= 0xd800 && number <= 0xdfff) ? String.fromCodePoint(number) : entity;
			}).trim();
	}
	function highlighted(text, other, tag) {
		const fragment = document.createDocumentFragment();
		const lines = text.split('\n');
		const previous = other.split('\n');
		lines.forEach((line, index) => {
			const peer = previous[index] || '';
			let start = 0;
			let end = 0;
			while (start < line.length && start < peer.length && line[start] === peer[start]) start++;
			while (end < line.length - start && end < peer.length - start && line[line.length - end - 1] === peer[peer.length - end - 1]) end++;
			fragment.append(document.createTextNode(line.slice(0, start)));
			if (start < line.length - end) {
				const change = document.createElement(tag);
				change.textContent = line.slice(start, line.length - end);
				fragment.append(change);
			}
			fragment.append(document.createTextNode(line.slice(line.length - end) + (index < lines.length - 1 ? '\n' : '')));
		});
		return fragment;
	}
	function renderComparison() {
		if (!session || session.result === null) return;
		const before = markup.checked ? session.source : readable(session.source);
		const after = markup.checked ? session.result : readable(session.result);
		dialog.querySelector('#hs-editor-ai-before').replaceChildren(highlighted(before, after, 'del'));
		dialog.querySelector('#hs-editor-ai-after').replaceChildren(highlighted(after, before, 'mark'));
	}
	markup.addEventListener('change', renderComparison);
	function visualEditor() {
		const editor = window.tinymce && window.tinymce.get('content');
		return editor && editor.initialized && !editor.isHidden() ? editor : null;
	}
	function documentContent() {
		const editor = visualEditor();
		return editor ? editor.getContent() : area.value;
	}
	function articleTitle() {
		const title = document.getElementById('title');
		return title ? title.value : '';
	}
	function capture(mode) {
		const editor = visualEditor();
		const whole = documentContent();
		const selection = editor ? editor.selection.getContent({ format: 'html' }) : area.value.slice(area.selectionStart, area.selectionEnd);
		const selected = mode !== 'editor' && selection.trim().length > 0;
		return { mode, whole, title: articleTitle(), source: selected ? selection : whole, selected, visual: Boolean(editor), bookmark: editor ? editor.selection.getBookmark(2, true) : null, start: area.selectionStart, end: area.selectionEnd, result: null, id: null, workerResult: null };
	}
	function close() {
		dialog.close();
		if (opener) opener.focus();
	}
	document.querySelectorAll('.hs-editor-ai-open').forEach(button => {
		button.addEventListener('mousedown', event => event.preventDefault());
		button.addEventListener('click', () => {
			const captured = capture(button.dataset.mode);
			if (busy || (session && !session.stale && session.mode === captured.mode && session.source === captured.source && session.result !== null && captured.whole === session.whole && captured.title === session.title && captured.visual === session.visual)) {
				opener = button; dialog.showModal(); return;
			}
			opener = button;
			session = captured;
			dialog.querySelector('#hs-editor-ai-title').textContent = titles[session.mode];
			dialog.querySelector('#hs-editor-ai-description').textContent = descriptions[session.mode];
			const scope = session.selected ? 'Только выделение · контекст всей статьи' : 'Вся статья';
			dialog.querySelector('#hs-editor-ai-scope').textContent = scope + ' · ' + readable(session.source).length.toLocaleString('ru-RU') + ' символов';
			dialog.querySelector('#hs-editor-ai-input').textContent = readable(session.source);
			announce(session.mode === 'cards' && !session.selected ? 'Выделите фрагмент с названиями карт и снова нажмите кнопку.' : '');
			run.disabled = session.mode === 'cards' && !session.selected;
			if (!session.source.trim()) { run.disabled = true; announce('Добавьте текст в статью, затем запустите обработку.'); }
			run.textContent = 'Отправить текст';
			run.hidden = false;
			apply.hidden = true;
			apply.disabled = false;
			compare.hidden = true;
			input.hidden = false;
			markup.checked = false;
			steps.hidden = true;
			dismiss.textContent = 'Оставить исходный текст';
			dialog.showModal();
		});
	});
	dialog.querySelectorAll('.hs-editor-ai-close').forEach(button => button.addEventListener('click', close));
	dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
	async function request(url, data, timeout = 12000) {
		const controller = new AbortController();
		const timer = setTimeout(() => controller.abort(), timeout);
		try {
			const response = await fetch(url, { method: data ? 'POST' : 'GET', signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: data ? JSON.stringify(data) : undefined });
			const result = await response.json();
			if (!response.ok) {
				const error = new Error(result.message || 'Запрос не выполнен. Текст сохранён.');
				error.status = response.status;
				throw error;
			}
			return result;
		} finally { clearTimeout(timer); }
	}
	function dispatch(current) {
		if (current.dispatching) return;
		current.dispatching = true;
		const workerId = current.id;
		// One provider call per request stays within the site's PHP-FPM deadline.
		request(config.url + '/' + workerId + '/run', {}, 95000)
			.then(result => { if (current.id === workerId && ['ready', 'failed'].includes(result.state)) current.workerResult = result; })
			.catch(() => { current.fallback = true; })
			.finally(() => { current.dispatching = false; });
	}
	async function poll(id, current) {
		const started = Date.now();
		const deadline = Date.now() + 330000;
		let failures = 0;
		while (Date.now() < deadline) {
			const delay = Date.now() - started < 5000 ? 750 : 3000;
			await new Promise(resolve => setTimeout(resolve, document.hidden ? 5000 : delay));
			let result;
			try {
				result = current.workerResult || await request(config.url + '/' + id);
				failures = 0;
			} catch (error) {
				if ([401, 403, 404].includes(error.status) || ++failures >= 3) throw error;
				announce('Связь прервалась. Проверяем готовность результата; текст сохранён.', 'queued');
				continue;
			}
			if (result.state === 'failed') { current.id = null; throw new Error(result.error || 'Не удалось проверить результат.'); }
			if (result.state !== 'ready') {
				const phase = result.phase === 'review' ? 'review' : result.state;
				if (phase === 'review' && result.state === 'queued' && !current.fallback) dispatch(current);
				const message = phase === 'review' ? 'Проверяем сохранность смысла после редактуры…' : phase === 'running' ? 'Claude обрабатывает текст. Можно закрыть окно и продолжить писать.' : 'Ожидаем начала обработки. Можно закрыть окно и продолжить писать.';
				announce(message, phase);
				continue;
			}
			current.result = result.result;
			renderComparison();
			compare.hidden = false;
			input.hidden = true;
			apply.hidden = current.source === current.result;
			announce(current.source === current.result ? 'Изменений не требуется.' : 'Предложение готово. Проверьте выделенные изменения перед применением.', 'ready');
			run.hidden = true;
			dismiss.textContent = 'Оставить исходный текст';
			return;
		}
		throw new Error('Обработка не завершилась вовремя. Исходный текст сохранён.');
	}
	run.addEventListener('click', async () => {
		if (!session || busy) return;
		const current = session;
		busy = true;
		run.disabled = true;
		steps.hidden = false;
		dismiss.textContent = 'Продолжить писать';
		announce('Отправляем текст. Статью можно сохранять во время обработки.', 'queued');
		try {
			if (!current.id) {
				const job = await request(config.url, { post_id: config.postId, mode: current.mode, source: current.source, context: current.title + '\n' + current.whole });
				current.id = job.id;
				current.workerResult = null;
			}
			// Separate, bounded worker request. Cron remains the fallback if it fails.
			current.fallback = false;
			dispatch(current);
			await poll(current.id, current);
		} catch (error) {
			const message = error.name === 'AbortError' || error instanceof TypeError || error instanceof SyntaxError ? 'Нет ответа от сервера. Проверьте соединение и повторите проверку; исходный текст сохранён.' : error.message;
			announce(message, 'failed');
			run.textContent = current.id ? 'Проверить результат' : 'Повторить отправку';
			dismiss.textContent = 'Оставить исходный текст';
		} finally {
			busy = false;
			run.disabled = false;
		}
	});
	apply.addEventListener('click', () => {
		if (!session || session.result === null) return;
		const editor = visualEditor();
		if (Boolean(editor) !== session.visual || documentContent() !== session.whole || articleTitle() !== session.title) {
			announce('Статья изменилась после отправки. Закройте окно и запустите обработку заново — старое предложение не применено.', 'failed');
			apply.disabled = true;
			session.stale = true;
			return;
		}
		if (editor) {
			editor.undoManager.transact(() => {
				if (session.selected) {
					editor.selection.moveToBookmark(session.bookmark);
					editor.insertContent(session.result);
				} else editor.setContent(session.result);
			});
			editor.setDirty(true);
			editor.save();
			editor.fire('change');
		} else {
			if (session.selected) area.setRangeText(session.result, session.start, session.end, 'select');
			else area.value = session.result;
			area.dispatchEvent(new Event('input', { bubbles: true }));
			area.dispatchEvent(new Event('change', { bubbles: true }));
		}
		session = null;
		close();
	});
}());
