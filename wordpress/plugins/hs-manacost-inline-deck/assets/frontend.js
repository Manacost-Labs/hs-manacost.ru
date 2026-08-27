(function () {
    'use strict';

    if (window.hsManacostInlineDeckFrontendReady) {
        return;
    }
    window.hsManacostInlineDeckFrontendReady = true;

    var selector = '.hs-mi-deck-link';
    var preloads = Object.create(null);
    var lightbox = null;
    var image = null;
    var caption = null;
    var copyButton = null;
    var lastTrigger = null;
    var activeCode = '';

    function preload(src) {
        if (!src) {
            return null;
        }
        if (preloads[src]) {
            return preloads[src];
        }

        var candidate = new Image();
        candidate.decoding = 'async';
        candidate.src = src;
        preloads[src] = candidate;
        return candidate;
    }

    function triggerFromEvent(event) {
        return event.target && event.target.closest
            ? event.target.closest(selector)
            : null;
    }

    function warmTrigger(trigger) {
        if (trigger) {
            preload(trigger.getAttribute('href') || '');
        }
    }

    function ensureLightbox() {
        if (lightbox) {
            return;
        }

        lightbox = document.createElement('div');
        lightbox.className = 'hs-mi-lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.setAttribute('aria-hidden', 'true');
        lightbox.setAttribute('aria-label', 'Изображение колоды');
        lightbox.innerHTML =
            '<div class="hs-mi-lightbox__panel" role="document">' +
                '<button type="button" class="hs-mi-lightbox__close" aria-label="Закрыть">×</button>' +
                '<div class="hs-mi-lightbox__image-wrap">' +
                    '<img class="hs-mi-lightbox__image" src="" alt="">' +
                    '<span class="hs-mi-lightbox__loader" aria-hidden="true"></span>' +
                '</div>' +
                '<div class="hs-mi-lightbox__caption"></div>' +
                '<button type="button" class="hs-mi-lightbox__copy">Скопировать код колоды</button>' +
            '</div>';
        document.body.appendChild(lightbox);

        image = lightbox.querySelector('.hs-mi-lightbox__image');
        caption = lightbox.querySelector('.hs-mi-lightbox__caption');
        copyButton = lightbox.querySelector('.hs-mi-lightbox__copy');

        image.addEventListener('load', function () {
            image.classList.add('is-ready');
        });
        lightbox.querySelector('.hs-mi-lightbox__close').addEventListener('click', close);
        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) {
                close();
            }
        });
        copyButton.addEventListener('click', copyCode);
    }

    function open(trigger) {
        ensureLightbox();

        var src = trigger.getAttribute('href') || '';
        var title = trigger.getAttribute('data-hs-mi-title') || '';
        activeCode = trigger.getAttribute('data-hs-mi-code') || '';
        lastTrigger = trigger;

        image.classList.remove('is-ready');
        image.alt = title;
        image.src = src;
        if (image.complete && image.naturalWidth) {
            image.classList.add('is-ready');
        }

        caption.textContent = title;
        caption.hidden = !title;
        copyButton.hidden = !activeCode;
        copyButton.textContent = 'Скопировать код колоды';
        copyButton.classList.remove('is-copied');

        document.body.classList.add('hs-mi-lightbox-open');
        lightbox.setAttribute('aria-hidden', 'false');
        lightbox.classList.add('is-open');
        lightbox.querySelector('.hs-mi-lightbox__close').focus();
    }

    function close() {
        if (!lightbox || !lightbox.classList.contains('is-open')) {
            return;
        }

        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('hs-mi-lightbox-open');
        window.setTimeout(function () {
            if (image && !lightbox.classList.contains('is-open')) {
                image.src = '';
                image.classList.remove('is-ready');
            }
        }, 160);

        if (lastTrigger && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus();
        }
    }

    function legacyCopy(value) {
        var textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }
        textarea.remove();
        return copied ? Promise.resolve() : Promise.reject();
    }

    function copyCode() {
        if (!activeCode) {
            return;
        }

        var operation = navigator.clipboard && navigator.clipboard.writeText
            ? navigator.clipboard.writeText(activeCode)
            : legacyCopy(activeCode);

        operation.then(function () {
            copyButton.textContent = 'Код скопирован';
            copyButton.classList.add('is-copied');
            window.setTimeout(function () {
                if (copyButton) {
                    copyButton.textContent = 'Скопировать код колоды';
                    copyButton.classList.remove('is-copied');
                }
            }, 1800);
        }).catch(function () {
            copyButton.textContent = 'Не удалось скопировать';
        });
    }

    document.addEventListener('pointerover', function (event) {
        warmTrigger(triggerFromEvent(event));
    }, true);
    document.addEventListener('focusin', function (event) {
        warmTrigger(triggerFromEvent(event));
    }, true);
    document.addEventListener('touchstart', function (event) {
        warmTrigger(triggerFromEvent(event));
    }, { capture: true, passive: true });

    // Capture phase prevents Newspaper's global image modal from handling
    // this one isolated link.
    document.addEventListener('click', function (event) {
        var trigger = triggerFromEvent(event);
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
        warmTrigger(trigger);
        open(trigger);
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            close();
        }
    });

    function warmNearby() {
        var triggers = document.querySelectorAll(selector);
        if (!triggers.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            warmTrigger(triggers[0]);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    warmTrigger(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '300px 0px' });

        Array.prototype.forEach.call(triggers, function (trigger) {
            observer.observe(trigger);
        });
    }

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warmNearby, { timeout: 1000 });
    } else {
        window.setTimeout(warmNearby, 250);
    }
}());
