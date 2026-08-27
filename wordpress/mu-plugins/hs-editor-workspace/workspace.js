/*
 * HS Editor Workspace — вкладки панели HS Tooltip и горячая клавиша поиска.
 *
 * Разметку поиска не трогаем: hs-tooltip-admin.js привязывается к полям по ID
 * и грузится в футере, то есть после отрисовки метабоксов.
 */
(function () {
	'use strict';

	var STORAGE_KEY = 'hsEwTooltipTab';
	var root = document.querySelector('[data-hs-ew-tabs]');

	if (!root) {
		return;
	}

	var tabs = root.querySelectorAll('[data-hs-ew-tab]');
	var panels = root.querySelectorAll('[data-hs-ew-panel]');

	function activate(name) {
		var known = false;

		Array.prototype.forEach.call(tabs, function (tab) {
			if (tab.getAttribute('data-hs-ew-tab') === name) {
				known = true;
			}
		});

		if (!known) {
			return;
		}

		Array.prototype.forEach.call(tabs, function (tab) {
			var isActive = tab.getAttribute('data-hs-ew-tab') === name;
			tab.classList.toggle('is-active', isActive);
			tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		Array.prototype.forEach.call(panels, function (panel) {
			panel.classList.toggle('is-active', panel.getAttribute('data-hs-ew-panel') === name);
		});

		try {
			window.localStorage.setItem(STORAGE_KEY, name);
		} catch (e) {
			/* приватный режим — просто не запоминаем вкладку */
		}
	}

	Array.prototype.forEach.call(tabs, function (tab) {
		tab.addEventListener('click', function () {
			activate(tab.getAttribute('data-hs-ew-tab'));
		});
	});

	try {
		var saved = window.localStorage.getItem(STORAGE_KEY);
		if (saved) {
			activate(saved);
		}
	} catch (e) {
		/* нет доступа к localStorage — остаёмся на вкладке по умолчанию */
	}

	/*
	 * Закрепление панели. Состояние держим в localStorage — как и вкладку,
	 * то есть настройка своя на каждом устройстве и не трогает запись.
	 */
	var PIN_KEY = 'hsEwTooltipPinned';
	var pinInput = root.querySelector('.hs-ew-pin-input');
	var box = root.closest('.postbox') || document.getElementById('hs_tooltip_workspace');

	function applyPin(pinned) {
		if (box) {
			box.classList.toggle('hs-ew-is-pinned', pinned);
		}

		if (pinInput) {
			pinInput.checked = pinned;
		}
	}

	if (pinInput && box) {
		var storedPin = null;

		try {
			storedPin = window.localStorage.getItem(PIN_KEY);
		} catch (e) {
			/* нет доступа к localStorage — берём значение по умолчанию */
		}

		// По умолчанию закреплено: до появления галочки панель уже так себя вела.
		applyPin(storedPin === null ? true : storedPin === '1');

		pinInput.addEventListener('change', function () {
			applyPin(pinInput.checked);

			try {
				window.localStorage.setItem(PIN_KEY, pinInput.checked ? '1' : '0');
			} catch (e) {
				/* не запомнили — не страшно, поведение в этой сессии верное */
			}
		});
	}

	function focusCardSearch() {
		var input = document.getElementById('hs-tooltip-search');

		if (!input) {
			return false;
		}

		activate('cards');
		input.focus();
		input.select();

		return true;
	}

	/*
	 * Alt+K. Проверяем code/keyCode, а не key: в русской раскладке key вернёт
	 * «л», и проверка по букве не сработала бы.
	 */
	function isHotkey(event) {
		if (!event.altKey || event.ctrlKey || event.metaKey) {
			return false;
		}

		return event.code === 'KeyK' || event.keyCode === 75;
	}

	document.addEventListener('keydown', function (event) {
		if (isHotkey(event) && focusCardSearch()) {
			event.preventDefault();
		}
	});

	/*
	 * Визуальный редактор — это iframe, события клавиатуры оттуда не всплывают
	 * в документ админки. Поэтому вешаем обработчик ещё и внутрь TinyMCE.
	 */
	function bindEditor(editor) {
		if (!editor || editor.hsEwHotkeyBound) {
			return;
		}

		editor.hsEwHotkeyBound = true;
		editor.on('keydown', function (event) {
			if (isHotkey(event) && focusCardSearch()) {
				event.preventDefault();
			}
		});
	}

	function bindTinyMce() {
		if (!window.tinymce) {
			return;
		}

		if (window.tinymce.editors) {
			Array.prototype.forEach.call(window.tinymce.editors, bindEditor);
		}

		if (typeof window.tinymce.on === 'function') {
			window.tinymce.on('AddEditor', function (event) {
				bindEditor(event.editor);
			});
		}
	}

	bindTinyMce();
	document.addEventListener('DOMContentLoaded', bindTinyMce);
})();
