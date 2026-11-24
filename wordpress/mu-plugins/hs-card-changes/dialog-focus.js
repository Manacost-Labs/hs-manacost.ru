/* WordPress resizes TinyMCE on viewport changes while the picker is open. */
(() => {
	'use strict';
	const selector = '#hs-card-changes-dialog[open]';
	const bound = new WeakSet();
	document.addEventListener('focusin', event => {
		if (!event.target.closest(selector)) return;
		const editor = window.tinymce?.get('content');
		if (!editor || bound.has(editor)) return;
		bound.add(editor);
		const execCommand = editor.execCommand;
		editor.execCommand = function (command, ui, value, options) {
			// TinyMCE takes focus before BeforeExecCommand. Its skip_focus option
			// lets the resize run while keeping the modal's keyboard focus.
			if (command === 'wpAutoResize' && document.querySelector(selector)) {
				return execCommand.call(this, command, ui, value, { ...options, skip_focus: true });
			}
			return execCommand.apply(this, arguments);
		};
	});
})();
