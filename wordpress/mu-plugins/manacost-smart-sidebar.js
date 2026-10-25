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
			// Let the compositor hold a pinned edge. Counter-scrolling a relative
			// transform on every event visibly bounces when the main thread is busy.
			const viewportTop = sidebar.position - scrollY;
			const pinned = sidebar.enabled && sidebar.position > sidebar.start && sidebar.position < sidebar.end
				&& (Math.abs(viewportTop - sidebar.top) < 0.5
					|| Math.abs(viewportTop - Math.min(sidebar.top, innerHeight - sidebar.height)) < 0.5);
			if (pinned) {
				sidebar.element.style.setProperty('--manacost-sidebar-top', viewportTop + 'px');
			}
			sidebar.element.classList.toggle('manacost-sidebar-pinned', pinned);
		}

		function follow(sidebar) {
			if (!sidebar.enabled) {
				sidebar.focusScroll = null;
				sidebar.position = sidebar.start;
			} else if (sidebar.focusScroll === scrollY && sidebar.element.contains(document.activeElement)) {
				// A queued scroll/resize notification must not undo keyboard reveal.
				sidebar.position = clamp(sidebar.position, sidebar.start, sidebar.end);
			} else {
				sidebar.focusScroll = null;
				const bottom = scrollY + Math.min(sidebar.top, innerHeight - sidebar.height);
				const top = scrollY + sidebar.top;
				// Preserve document position on reversal, until the opposite edge is
				// reached. Clamp in the same scroll event, without a deferred snap.
				sidebar.position = clamp(clamp(sidebar.position, bottom, top), sidebar.start, sidebar.end);
			}
			render(sidebar);
		}

		function measure(sidebar) {
			const pinned = innerWidth >= 768 && sidebar.element.classList.contains('manacost-sidebar-pinned');
			const bounds = (pinned ? sidebar.space : sidebar.element).getBoundingClientRect();
			const start = bounds.top + scrollY - (!pinned && innerWidth >= 768 ? sidebar.offset : 0);
			sidebar.element.style.setProperty('--manacost-sidebar-width', bounds.width + 'px');
			const height = sidebar.element.getBoundingClientRect().height;
			sidebar.space.style.height = height + 'px';
			const end = Math.max(start, sidebar.content.getBoundingClientRect().bottom + scrollY - height);
			const top = sidebar.topOffset + (document.body.classList.contains('admin-bar') ? (innerWidth > 782 ? 32 : 46) : 0);
			const interval = window.tdViewport?.getCurrentIntervalIndex();
			const enabled = innerWidth >= 768 && end > start && (!sidebar.enabledOn || sidebar.enabledOn[interval]);
			// A positioning change can trigger an observer without changing layout.
			// Do not undo keyboard reveal in that notification.
			if (sidebar.start === start && sidebar.height === height && sidebar.end === end
				&& sidebar.width === bounds.width && sidebar.top === top && sidebar.enabled === enabled) {
				return;
			}
			Object.assign(sidebar, { start, height, end, top, enabled, width: bounds.width });
			follow(sidebar);
		}

		function register(element, content, topOffset = 0, enabledOn) {
			if (!element || !content || sidebars.some(sidebar => sidebar.element === element)) {
				return;
			}
			element.classList.add('manacost-smart-sidebar');
			const space = document.createElement('div');
			space.className = 'manacost-sidebar-space';
			space.setAttribute('aria-hidden', 'true');
			element.after(space);
			const sidebar = { element, content, space, topOffset, enabledOn, offset: 0, position: element.getBoundingClientRect().top + scrollY };
			sidebars.push(sidebar);
			measure(sidebar);
			if (typeof ResizeObserver !== 'undefined') {
				const observer = new ResizeObserver(() => measure(sidebar));
				observer.observe(element);
				observer.observe(content);
			}
		}

		document.querySelectorAll('.td-main-sidebar > .td-ss-main-sidebar').forEach(element => {
			register(element, element.closest('.td-pb-row')?.querySelector('.td-ss-main-content'));
		});

		// Composer registers column pairs directly, without the classic markup.
		// Keep its configured offset/breakpoints and remove each managed native item.
		function registerNativeItems() {
			const native = window.tdSmartSidebar;
			if (native) {
				native.items.forEach(item => {
					const element = item.sidebar_jquery_obj[0];
					if (element?.parentElement.classList.contains('td-is-sticky')) {
						register(element, item.content_jquery_obj[0], Number(item.offset) || 0, item.enabled_on);
					}
				});
				native.items = native.items.filter(item => !sidebars.some(sidebar => item.sidebar_jquery_obj[0] === sidebar.element));
				native.hasItems = native.items.length > 0;
			}
		}
		registerNativeItems();
		window.addEventListener('load', () => {
			registerNativeItems();
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
			if (adjustment === 0) {
				return;
			}
			// The compositor can move a fixed sidebar before its scroll handler runs.
			const documentTop = sidebar.element.getBoundingClientRect().top + scrollY;
			sidebar.position = clamp(documentTop + adjustment, sidebar.start, sidebar.end);
			sidebar.focusScroll = scrollY;
			render(sidebar);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
}());
