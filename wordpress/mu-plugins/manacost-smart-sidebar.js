(function () {
	'use strict';

	function init() {
		const sidebars = [];
		const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

		function render(sidebar) {
			const offset = sidebar.position - sidebar.start;
			if (offset !== sidebar.offset) {
				sidebar.offset = offset;
				sidebar.element.style.setProperty('--manacost-sidebar-offset', offset + 'px');
			}
		}

		function follow(sidebar) {
			if (!sidebar.enabled) {
				sidebar.position = sidebar.start;
			} else {
				const bottom = scrollY + Math.min(sidebar.top, innerHeight - sidebar.height);
				const top = scrollY + sidebar.top;
				// Preserve document position on reversal, until the opposite edge is
				// reached. Clamp in the same scroll event, without a deferred snap.
				sidebar.position = clamp(clamp(sidebar.position, bottom, top), sidebar.start, sidebar.end);
			}
			render(sidebar);
		}

		function measure(sidebar) {
			const bounds = sidebar.element.getBoundingClientRect();
			sidebar.start = bounds.top + scrollY - (innerWidth >= 768 ? sidebar.offset : 0);
			sidebar.height = bounds.height;
			sidebar.end = Math.max(sidebar.start, sidebar.content.getBoundingClientRect().bottom + scrollY - bounds.height);
			sidebar.top = document.body.classList.contains('admin-bar') ? (innerWidth > 782 ? 32 : 46) : 0;
			sidebar.enabled = innerWidth >= 768 && sidebar.end > sidebar.start;
			follow(sidebar);
		}

		document.querySelectorAll('.td-main-sidebar > .td-ss-main-sidebar').forEach(element => {
			const content = element.closest('.td-pb-row')?.querySelector('.td-ss-main-content');
			if (!content) {
				return;
			}
			element.classList.add('manacost-smart-sidebar');
			const sidebar = { element, content, offset: 0, position: element.getBoundingClientRect().top + scrollY };
			sidebars.push(sidebar);
			measure(sidebar);
			if (typeof ResizeObserver !== 'undefined') {
				const observer = new ResizeObserver(() => measure(sidebar));
				observer.observe(element);
				observer.observe(content);
			}
		});

		if (!sidebars.length) {
			return;
		}

		// Composer sidebars keep their native controller. Only classic columns
		// managed above are removed, so two controllers cannot reposition them.
		function detachNativeItems() {
			const native = window.tdSmartSidebar;
			if (native) {
				native.items = native.items.filter(item => !sidebars.some(sidebar => item.sidebar_jquery_obj[0] === sidebar.element));
				native.hasItems = native.items.length > 0;
			}
		}
		detachNativeItems();
		window.addEventListener('load', () => {
			detachNativeItems();
			sidebars.forEach(measure);
		}, { once: true });
		window.addEventListener('scroll', () => sidebars.forEach(follow), { passive: true });
		window.addEventListener('resize', () => sidebars.forEach(measure), { passive: true });
		document.addEventListener('focusin', event => {
			if (!(event.target instanceof HTMLElement) || !event.target.matches(':focus-visible')) {
				return;
			}
			const sidebar = sidebars.find(item => item.element.contains(event.target));
			if (!sidebar?.enabled) {
				return;
			}
			const bounds = event.target.getBoundingClientRect();
			const adjustment = bounds.top < sidebar.top + 64 ? sidebar.top + 64 - bounds.top : Math.min(0, innerHeight - 16 - bounds.bottom);
			sidebar.position = clamp(sidebar.position + adjustment, sidebar.start, sidebar.end);
			render(sidebar);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
}());
