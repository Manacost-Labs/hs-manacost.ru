/* Classic Editor's wpAutoResize focuses TinyMCE even behind a native modal. */
(() => {
	'use strict';
	const selector = '#hs-article-cards-dialog[open], #hs-v-dialog[open]';
	const focused = new WeakMap(), bound = new WeakSet();
	document.addEventListener('focusin', event => {
		const dialog = event.target.closest(selector);
		if (!dialog) return;
		focused.set(dialog, event.target);
		const editor = window.tinymce?.get('content');
		if (!editor || bound.has(editor)) return;
		bound.add(editor);
		editor.on('ExecCommand', command => {
			if (command.command !== 'wpAutoResize') return;
			// Run after TinyMCE's own queued focus restoration, without disabling resize.
			setTimeout(() => {
				const modal = document.querySelector(selector);
				const control = modal && focused.get(modal);
				if (control?.isConnected && modal.contains(control)) control.focus({ preventScroll: true });
			}, 0);
		});
	});
})();
