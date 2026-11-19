/**
 * Adds tag tabs to the tagDiv related-articles block.
 *
 * The tabs reuse tagDiv's markup and AJAX request, so they look and load like
 * "Еще от автора". tagDiv binds its click handler only to links that exist when
 * its script initialises: if it bound ours, it calls preventDefault() first and
 * the delegated handler below stays idle; otherwise the handler does the same work.
 */
(function () {
	'use strict';

	var config = window.hsRelatedTagTabs;
	var ACTIVE_CLASS = 'td-cur-simple-item';
	var TAB_CLASS = 'hs-related-tag-tab';

	if (!config || !Array.isArray(config.tabs) || config.tabs.length === 0) {
		return;
	}

	function mountTabs() {
		var title = document.querySelector('.td-related-title');
		var builtInTab = title && title.querySelector('a[data-td_block_id]');

		if (!builtInTab || title.querySelector('.' + TAB_CLASS)) {
			return;
		}

		config.tabs.forEach(function (tab) {
			var link = document.createElement('a');

			link.className = 'td-related-right ' + TAB_CLASS;
			link.href = '#';
			link.setAttribute('data-td_block_id', builtInTab.getAttribute('data-td_block_id'));
			link.setAttribute('data-td_filter_value', String(tab.value));
			link.textContent = String(tab.label);
			// The built-in tab labels are upper-case translation strings.
			link.style.textTransform = 'uppercase';
			// Tag names can be long; wrap inside the tab instead of being clipped on phones.
			link.style.whiteSpace = 'normal';
			title.appendChild(link);
		});
	}

	function onTabClick(event) {
		var link = event.target instanceof Element ? event.target.closest('.td-related-title a.' + TAB_CLASS) : null;
		var blocks = window.tdBlocks;
		var block;

		if (!link || event.defaultPrevented) {
			return;
		}

		event.preventDefault();

		if (!blocks || typeof blocks.tdGetBlockObjById !== 'function' || typeof blocks.tdAjaxDoBlockRequest !== 'function') {
			return;
		}

		block = blocks.tdGetBlockObjById(link.getAttribute('data-td_block_id'));
		if (!block) {
			return;
		}

		Array.prototype.forEach.call(link.parentNode.querySelectorAll('a'), function (tab) {
			tab.classList.remove(ACTIVE_CLASS);
		});
		link.classList.add(ACTIVE_CLASS);

		block.td_filter_value = link.getAttribute('data-td_filter_value');
		block.td_current_page = 1;
		blocks.tdAjaxDoBlockRequest(block, 'pull_down');
	}

	document.addEventListener('click', onTabClick);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', mountTabs);
	} else {
		mountTabs();
	}
})();
