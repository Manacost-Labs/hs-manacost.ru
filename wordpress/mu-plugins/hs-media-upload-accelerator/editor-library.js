/* global wp */
(() => {
	'use strict';

	const editor = wp.media?.editor;
	if (!editor?.add) return;

	const add = editor.add;
	editor.add = function (id, options = {}) {
		// Only adjust the default Add Media workflow in the main post editor.
		// Core uses perPage for both the query and the pagination cursor.
		if (id === 'content' && (options?.frame ?? 'post') === 'post' &&
			(options?.state ?? 'insert') === 'insert' && options?.library === undefined) {
			return add.call(this, id, { ...options, library: { perPage: 16 } });
		}
		return add.apply(this, arguments);
	};
})();
