/* Static Gutenberg block: live inline preview and the same composer for editing. */
(() => {
	'use strict';
	if (!window.wp?.blocks || !window.hsManacostComponents) return;
	const { createElement: el, useEffect, useRef, RawHTML } = wp.element;
	const { clean, enhance } = window.hsManacostComponents;
	function Edit({ attributes, clientId }) {
		const canvas = useRef(null);
		const props = wp.blockEditor.useBlockProps();
		useEffect(() => {
			if (!canvas.current) return;
			canvas.current.replaceChildren(clean(attributes.content));
			enhance(canvas.current);
		}, [attributes.content]);
		return el('div', props,
			el('div', { className: 'hs-v-block-preview', ref: canvas }),
			el(wp.components.Button, {
				variant: 'secondary',
				onClick: event => document.dispatchEvent(new CustomEvent('hs-components-edit', { detail: { clientId, html: attributes.content, opener: event.currentTarget } })),
			}, attributes.content ? 'Настроить компонент' : 'Выбрать компонент Manacost')
		);
	}
	wp.blocks.registerBlockType('hs-manacost/component', {
		apiVersion: 3, title: 'Компонент Manacost', icon: 'layout', category: 'design',
		attributes: { content: { type: 'string', source: 'html', default: '' } },
		supports: { html: false, customClassName: false }, edit: Edit,
		save: ({ attributes }) => el(RawHTML, null, clean(attributes.content).outerHTML),
	});
})();
