/* global wp */
(() => {
	'use strict';

	const media = wp.media;
	const queue = wp.Uploader?.queue;
	if (!media || !queue) return;
	const buttonId = 'hs-ready-selection';
	let observedSelection;

	function refresh() {
		const frame = media.frame;
		const state = frame?.state();
		const toolbar = frame?.toolbar?.get();
		if (state?.id !== 'insert' || !toolbar?.get('insert')) return;

		const selection = state.get('selection');
		if (observedSelection !== selection) {
			observedSelection?.off('add remove reset', schedule);
			observedSelection = selection;
			selection.on('add remove reset', schedule);
		}

		const pending = selection.where({ uploading: true });
		const ready = selection.filter(item => item.id && !item.get('uploading'));
		const button = toolbar.get(buttonId);
		if (!pending.length || !ready.length) {
			if (button) toolbar.unset(buttonId);
			return;
		}
		if (button) return;
		toolbar.set(buttonId, {
			text: wp.i18n.__('Выбрать загруженные', 'hs-manacost'),
			priority: 75,
			click() {
				// Keep the queue running. Only change the selection at the editor's request.
				selection.remove(selection.where({ uploading: true }));
				toolbar.refresh();
				toolbar.get('insert')?.el.focus();
			},
		});
	}

	let scheduled = false;
	function schedule() {
		if (scheduled) return;
		scheduled = true;
		queueMicrotask(() => {
			scheduled = false;
			refresh();
		});
	}

	queue.on('add remove reset change:uploading', schedule);
})();
