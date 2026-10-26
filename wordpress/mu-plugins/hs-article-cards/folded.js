/* FoldedDepthCards adapter: normal article links remain the no-JS fallback. */
(() => {
	'use strict';
	function enhance(root = document) {
		root.querySelectorAll('.hs-article-cards--folded:not([data-folded])').forEach(group => {
			const cards = [...group.children].filter(card => card.classList.contains('hs-article-card'));
			if (cards.length < 2) return;
			group.dataset.folded = 'true';
			group.setAttribute('role', 'region'); group.setAttribute('aria-label', 'Подборка статей');
			let active = 0, origin = null, swiped = false;
			const controls = document.createElement('div'); controls.className = 'hs-folded-controls';
			const previous = document.createElement('button'), next = document.createElement('button'), status = document.createElement('span');
			previous.type = next.type = 'button'; previous.textContent = '←'; next.textContent = '→';
			previous.setAttribute('aria-label', 'Предыдущая статья'); next.setAttribute('aria-label', 'Следующая статья');
			status.setAttribute('aria-live', 'polite'); status.setAttribute('aria-atomic', 'true');
			controls.append(previous, status, next); group.append(controls);
			function show(index) {
				active = Math.max(0, Math.min(cards.length - 1, index));
				cards.forEach((card, i) => {
					const depth = (i - active + cards.length) % cards.length;
					card.style.setProperty('--hs-fold-depth', String(Math.min(depth, 3)));
					card.dataset.front = String(i === active);
					card.inert = i !== active;
					card.setAttribute('aria-hidden', String(i !== active));
				});
				status.textContent = `${active + 1} / ${cards.length}`;
				previous.disabled = active === 0; next.disabled = active === cards.length - 1;
				if (document.activeElement === next && next.disabled) previous.focus();
				else if (document.activeElement === previous && previous.disabled) next.focus();
			}
			previous.addEventListener('click', () => show(active - 1)); next.addEventListener('click', () => show(active + 1));
			group.addEventListener('keydown', event => {
				const index = { ArrowLeft: active - 1, ArrowRight: active + 1, Home: 0, End: cards.length - 1 }[event.key];
				if (index === undefined) return;
				event.preventDefault(); const linkFocused = cards.some(card => card.contains(document.activeElement));
				show(index); if (linkFocused) cards[active].querySelector('a').focus();
			});
			group.addEventListener('pointerdown', event => { swiped = false; if (event.pointerType === 'touch') origin = { x: event.clientX, y: event.clientY }; });
			group.addEventListener('pointercancel', () => { origin = null; });
			group.addEventListener('pointerup', event => {
				if (!origin) return;
				const dx = event.clientX - origin.x, dy = event.clientY - origin.y; origin = null;
				if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) { swiped = true; show(active + (dx < 0 ? 1 : -1)); }
			});
			group.addEventListener('click', event => { if (swiped) { event.preventDefault(); event.stopPropagation(); swiped = false; } }, true);
			show(0);
		});
	}
	window.hsFoldedCards = { enhance };
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => enhance());
	else enhance();
})();
