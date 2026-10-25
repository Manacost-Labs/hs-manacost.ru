/* Progressive tabs: saved headings/content stay readable without JavaScript. */
(() => {
	'use strict';
	let sequence = 0;
	function enhance(root = document) {
		root.querySelectorAll('.hs-v-tabs:not([data-enhanced])').forEach(group => {
			const panels = [...group.children].filter(node => node.classList.contains('hs-v-panel'));
			if (panels.length < 2) return;
			group.dataset.enhanced = 'true';
			const id = `hs-v-tabs-${++sequence}`;
			const list = document.createElement('div');
			list.className = 'hs-v-tablist';
			list.setAttribute('role', 'tablist');
			list.setAttribute('aria-label', 'Разделы материала');
			const buttons = panels.map((panel, index) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.textContent = panel.querySelector('.hs-v-tab-title')?.textContent || `Раздел ${index + 1}`;
				button.id = `${id}-tab-${index}`;
				button.setAttribute('role', 'tab');
				button.setAttribute('aria-controls', `${id}-panel-${index}`);
				panel.id = `${id}-panel-${index}`;
				panel.setAttribute('role', 'tabpanel');
				panel.setAttribute('aria-labelledby', button.id);
				panel.tabIndex = 0;
				list.append(button);
				button.addEventListener('click', () => activate(index));
				button.addEventListener('keydown', event => {
					const next = { ArrowRight: (index + 1) % panels.length, ArrowLeft: (index - 1 + panels.length) % panels.length, Home: 0, End: panels.length - 1 }[event.key];
					if (next === undefined) return;
					event.preventDefault();
					activate(next);
					buttons[next].focus();
				});
				return button;
			});
			function activate(index) {
				buttons.forEach((button, i) => {
					button.setAttribute('aria-selected', String(i === index));
					button.tabIndex = i === index ? 0 : -1;
					panels[i].hidden = i !== index;
				});
			}
			group.prepend(list);
			activate(0);
		});
	}
	// Template contents are inert. Rebuild an allowlisted tree before showing stored HTML in wp-admin.
	function clean(html) {
		const template = document.createElement('template'); template.innerHTML = html;
		const allowed = new Set(['DIV', 'P', 'H3', 'DETAILS', 'SUMMARY', 'FIGURE', 'BLOCKQUOTE', 'FIGCAPTION', 'TABLE', 'CAPTION', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'STRONG', 'EM', 'B', 'I', 'U', 'S', 'CODE', 'BR']);
		function copy(source) {
			if (source.nodeType === Node.TEXT_NODE) return document.createTextNode(source.textContent);
			if (source.nodeType !== Node.ELEMENT_NODE || ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'TEMPLATE'].includes(source.tagName)) return document.createTextNode('');
			if (!allowed.has(source.tagName)) return document.createTextNode(source.textContent);
			const target = document.createElement(source.tagName.toLowerCase());
			const classes = [...source.classList].filter(name => name === 'hs-article-component' || /^hs-v-[a-z-]+$/.test(name));
			if (classes.length) target.className = classes.join(' ');
			for (const name of ['data-density', 'data-width', 'data-tone', 'data-align', 'scope']) {
				if (/^(normal|compact|full|narrow|info|warning|success|start|center|col)$/.test(source.getAttribute(name) || '')) target.setAttribute(name, source.getAttribute(name));
			}
			if (target.classList.contains('hs-v-table')) {
				target.tabIndex = 0; target.setAttribute('role', 'region');
				target.setAttribute('aria-label', source.querySelector('caption')?.textContent || 'Таблица');
			}
			target.append(...[...source.childNodes].map(copy));
			return target;
		}
		const source = template.content.querySelector('.hs-article-component');
		return source ? copy(source) : document.createElement('div');
	}
	window.hsManacostComponents = { enhance, clean };
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => enhance());
	else enhance();
})();
