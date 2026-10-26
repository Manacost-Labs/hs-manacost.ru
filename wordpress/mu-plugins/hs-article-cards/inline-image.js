/* Inline media stays an ordinary image inside the paragraph, including revisions. */
(() => {
	'use strict';
	const style = 'display:inline-block;height:1em;width:auto;vertical-align:-0.15em;margin:0 .12em;';
	function attributes(media) {
		try {
			const source = media.sizes?.medium?.url || media.url;
			const url = new URL(source, location.href);
			if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || media.type !== 'image') return null;
			return { src: url.href, alt: media.alt || '', style };
		} catch { return null; }
	}
	for (const button of document.querySelectorAll('.hs-inline-image-open')) {
		button.addEventListener('click', () => {
			const editor = window.tinymce?.get('content');
			const visual = editor && !editor.isHidden();
			const bookmark = visual ? editor.selection.getBookmark(2, true) : null;
			const textarea = document.getElementById('content');
			const start = textarea?.selectionStart, end = textarea?.selectionEnd;
			const frame = wp.media({ title: 'Картинка в строке — высота как у текста', button: { text: 'Вставить в строку' }, library: { type: 'image' }, multiple: false });
			frame.on('select', () => {
				const attrs = attributes(frame.state().get('selection').first().toJSON());
				if (!attrs) return;
				const img = document.createElement('img'); img.className = 'hs-inline-image';
				Object.entries(attrs).forEach(([key, value]) => img.setAttribute(key, value));
				if (visual) {
					editor.focus(); editor.selection.moveToBookmark(bookmark);
					editor.undoManager.transact(() => editor.insertContent(img.outerHTML));
					editor.nodeChanged();
				} else if (textarea) {
					textarea.focus(); textarea.setRangeText(img.outerHTML, start, end, 'end');
					textarea.dispatchEvent(new Event('input', { bubbles: true }));
				}
			});
			frame.open();
		});
	}
	if (!window.wp?.richText || !window.wp?.blockEditor) return;
	const { createElement: el } = wp.element;
	const { MediaUpload, MediaUploadCheck, RichTextToolbarButton } = wp.blockEditor;
	wp.richText.registerFormatType('hs-manacost/inline-image', {
		title: 'Картинка в строке', tagName: 'img', className: 'hs-inline-image', object: true,
		attributes: { src: 'src', alt: 'alt', style: 'style' },
		edit: ({ value, onChange, onFocus }) => el(MediaUploadCheck, null, el(MediaUpload, {
			allowedTypes: ['image'],
			onSelect: media => {
				const attrs = attributes({ ...media, type: media.type || 'image' });
				if (!attrs) return;
				onChange(wp.richText.insertObject(value, { type: 'hs-manacost/inline-image', attributes: attrs }));
				onFocus();
			},
			render: ({ open }) => el(RichTextToolbarButton, { icon: 'format-image', title: 'Картинка в строке', onClick: open }),
		})),
	});
})();
