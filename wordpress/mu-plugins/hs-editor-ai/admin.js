(function () {
	'use strict';
	const config = window.hsEditorAi;
	const dialog = document.getElementById('hs-editor-ai-dialog');
	if (!config || !dialog) return;
	const status = dialog.querySelector('#hs-editor-ai-status');
	const run = dialog.querySelector('#hs-editor-ai-run');
	const apply = dialog.querySelector('#hs-editor-ai-apply');
	const compare = dialog.querySelector('.hs-editor-ai-compare');
	const area = document.getElementById('content');
	if (!area) return;
	let session = null;
	let opener = null;
	let busy = false;
	const titles = { cards: 'Заполнить карты', editor: 'Редактура EditorTeam', proofread: 'Пунктуация и опечатки' };
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
		return { mode, whole, title: articleTitle(), source: selected ? selection : whole, selected, visual: Boolean(editor), bookmark: editor ? editor.selection.getBookmark(2, true) : null, start: area.selectionStart, end: area.selectionEnd, result: null };
	}
	function close() {
		dialog.close();
		if (opener) opener.focus();
	}
	document.querySelectorAll('.hs-editor-ai-open').forEach(button => {
		button.addEventListener('mousedown', event => event.preventDefault());
		button.addEventListener('click', () => {
			if (busy) { opener = button; dialog.showModal(); return; }
			opener = button;
			session = capture(button.dataset.mode);
			dialog.querySelector('#hs-editor-ai-title').textContent = titles[session.mode];
			dialog.querySelector('#hs-editor-ai-scope').textContent = session.selected ? 'Обрабатывается только выделение. Контекст — вся статья.' : 'Обрабатывается вся статья.';
			status.textContent = session.mode === 'cards' && !session.selected ? 'Выделите фрагмент с названиями карт и снова нажмите кнопку.' : '';
			run.disabled = session.mode === 'cards' && !session.selected;
			run.hidden = false;
			apply.hidden = true;
			apply.disabled = false;
			compare.hidden = true;
			dialog.showModal();
		});
	});
	dialog.querySelectorAll('.hs-editor-ai-close').forEach(button => button.addEventListener('click', close));
	dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
	async function request(url, data) {
		const controller = new AbortController();
		const timer = setTimeout(() => controller.abort(), 12000);
		try {
			const response = await fetch(url, { method: data ? 'POST' : 'GET', signal: controller.signal, credentials: 'same-origin', cache: 'no-store', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: data ? JSON.stringify(data) : undefined });
			const result = await response.json();
			if (!response.ok) throw new Error(result.message || 'Запрос не выполнен. Текст сохранён.');
			return result;
		} finally { clearTimeout(timer); }
	}
	async function poll(id, current) {
		const deadline = Date.now() + 330000;
		while (Date.now() < deadline) {
			await new Promise(resolve => setTimeout(resolve, 1500));
			const result = await request(config.url + '/' + id);
			if (result.state === 'failed') throw new Error(result.error || 'Не удалось проверить результат.');
			if (result.state !== 'ready') continue;
			current.result = result.result;
			dialog.querySelector('#hs-editor-ai-before').textContent = current.source;
			dialog.querySelector('#hs-editor-ai-after').textContent = current.result;
			compare.hidden = false;
			apply.hidden = current.source === current.result;
			status.textContent = current.source === current.result ? 'Изменений не требуется.' : 'Предложение готово. Проверьте изменения перед применением.';
			run.hidden = true;
			return;
		}
		throw new Error('Обработка не завершилась вовремя. Исходный текст сохранён.');
	}
	run.addEventListener('click', async () => {
		if (!session || busy) return;
		const current = session;
		busy = true;
		run.disabled = true;
		status.textContent = 'Запрос поставлен в очередь. Статью можно сохранять; результат будет проверен перед применением.';
		try {
			const job = await request(config.url, { post_id: config.postId, mode: current.mode, source: current.source, context: current.title + '\n' + current.whole });
			await poll(job.id, current);
		} catch (error) {
			status.textContent = error.message;
		} finally {
			busy = false;
			run.disabled = false;
		}
	});
	apply.addEventListener('click', () => {
		if (!session || session.result === null) return;
		const editor = visualEditor();
		if (Boolean(editor) !== session.visual || documentContent() !== session.whole || articleTitle() !== session.title) {
			status.textContent = 'Статья изменилась после отправки. Закройте окно и запустите обработку заново — старое предложение не применено.';
			apply.disabled = true;
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
		close();
	});
}());
