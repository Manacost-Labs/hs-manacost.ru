(function () {
  'use strict';

  const documentRoot = document.documentElement;
  const rootSelectors = [
    '.td-post-content',
    '.tdb_single_content',
    '.entry-content',
    '.wp-block-post-content',
    '.mc-comments__list',
  ];
  const rootSelector = rootSelectors.join(',');
  const contentImageSelector = rootSelectors.map(selector => `${selector} img`).join(',');
  const initialImageSelector = [
    contentImageSelector,
    'img.td-modal-image',
    '.td-modal-image img',
    'img[data-hs-lightbox]',
    '[data-hs-lightbox] img',
  ].join(',');
  const gallerySelector = [
    '.wp-block-gallery',
    '.gallery',
    '.tiled-gallery',
    '.td-lightbox-enabled',
    '.mc-comments__item',
  ].join(',');
  const forcedLinkSelector = [
    '.td-modal-image',
    '.gallery-icon > a',
    '.td-lightbox-enabled a',
    '.mc-comments__image-link',
    '[data-hs-lightbox]',
  ].join(',');
  const excludedSelector = [
    '[data-no-lightbox]',
    '.no-lightbox',
    '.custom-logo-link',
    '.td-main-logo',
    '.td-header-logo',
    '.td-a-rec',
    '.td-adspot-title',
    '.adsbygoogle',
    '.mc-comments__avatar',
    '.mc-reader__avatar',
    '.avatar',
    '[data-ad]',
    '[data-comments-attachment-preview]',
  ].join(',');
  const imageUrlPattern = /\.(?:avif|gif|jpe?g|png|webp)(?:$|[?#])/i;
  const defaultMessages = {
    title: 'Просмотр изображения',
    close: 'Закрыть',
    previous: 'Предыдущее изображение',
    next: 'Следующее изображение',
    openOriginal: 'Открыть оригинал',
    loading: 'Загрузка изображения',
    error: 'Не удалось загрузить изображение.',
    imageCount: '%1$d из %2$d',
    openImage: 'Открыть изображение: %s',
  };
  const messages = Object.assign({}, defaultMessages, window.hsManacostLightboxConfig || {});

  let dialog = null;
  let dialogNodes = null;
  let activeItems = [];
  let activeIndex = 0;
  let opener = null;
  let pointerStart = null;

  function safeUrl(value) {
    if (!value) return '';
    try {
      const url = new URL(value, document.baseURI);
      return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch (_error) {
      return '';
    }
  }

  function looksLikeImageUrl(value) {
    return imageUrlPattern.test(value || '');
  }

  function imageDimensions(image) {
    return {
      width: Number(image.getAttribute('width')) || image.naturalWidth,
      height: Number(image.getAttribute('height')) || image.naturalHeight,
    };
  }

  function isExcluded(image, trigger) {
    if (image.closest(excludedSelector) || trigger.closest(excludedSelector)) return true;
    const control = image.closest('button, [role="button"]');
    if (control && control !== image) return true;
    const dimensions = imageDimensions(image);
    return dimensions.width > 0 && dimensions.height > 0 && dimensions.width <= 96 && dimensions.height <= 96;
  }

  function findImage(target) {
    if (!(target instanceof Element)) return null;
    if (target.matches('img')) return target;
    return target.closest('a')?.querySelector('img') || null;
  }

  function itemFromImage(image) {
    const contentRoot = image?.closest(rootSelector);
    const forcedContainer = image?.closest(forcedLinkSelector);
    if (!contentRoot && !forcedContainer) return null;

    const link = image.closest('a');
    const trigger = link || image;
    if (isExcluded(image, trigger)) return null;

    const forcedLink = Boolean(forcedContainer);
    const linkUrl = safeUrl(link?.getAttribute('href'));
    if (link && !forcedLink && !looksLikeImageUrl(linkUrl)) return null;

    const source = linkUrl || safeUrl(
      image.dataset.largeFile ||
      image.dataset.origFile ||
      image.dataset.full ||
      image.currentSrc ||
      image.getAttribute('src')
    );
    if (!source) return null;

    const figureCaption = image.closest('figure')?.querySelector('figcaption');
    const caption = (
      trigger.dataset.caption ||
      image.dataset.caption ||
      figureCaption?.textContent ||
      image.getAttribute('title') ||
      ''
    ).replace(/\s+/g, ' ').trim();
    const alt = (image.getAttribute('alt') || caption || messages.title).replace(/\s+/g, ' ').trim();

    return { alt, caption, image, source, trigger };
  }

  function itemFromTarget(target) {
    const image = findImage(target);
    return itemFromImage(image);
  }

  function enhanceItem(item) {
    const { trigger, alt } = item;
    trigger.dataset.hsLightboxTrigger = 'true';
    trigger.setAttribute('aria-haspopup', 'dialog');
    if (trigger.matches('img')) {
      trigger.setAttribute('role', 'button');
      trigger.setAttribute('tabindex', '0');
      trigger.setAttribute('aria-label', messages.openImage.replace('%s', alt));
    }
  }

  function enhance(root) {
    root.querySelectorAll(initialImageSelector).forEach(image => {
      const item = itemFromImage(image);
      if (item) enhanceItem(item);
    });
  }

  function itemsFor(item) {
    const group = item.trigger.closest(gallerySelector) || item.trigger.closest(rootSelector);
    if (!group) return [item];

    const items = [];
    const seen = new Set();
    group.querySelectorAll('img').forEach(image => {
      const candidate = itemFromImage(image);
      if (!candidate || seen.has(candidate.trigger)) return;
      seen.add(candidate.trigger);
      enhanceItem(candidate);
      items.push(candidate);
    });
    return items.length ? items : [item];
  }

  function icon(path) {
    return `<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="${path}"/></svg>`;
  }

  function createDialog() {
    const element = document.createElement('dialog');
    element.className = 'hs-lightbox';
    element.setAttribute('role', 'dialog');
    element.setAttribute('aria-modal', 'true');
    element.setAttribute('aria-labelledby', 'hs-lightbox-title');
    element.innerHTML = `
      <div class="hs-lightbox__surface">
        <h2 class="hs-lightbox__title" id="hs-lightbox-title"></h2>
        <div class="hs-lightbox__toolbar">
          <span class="hs-lightbox__counter" aria-live="polite"></span>
          <a class="hs-lightbox__original" target="_blank" rel="noopener">${icon('M14 3h7v7m0-7-9 9M5 5h5M5 5v14h14v-5')}<span class="hs-lightbox__original-label"></span></a>
          <button class="hs-lightbox__control hs-lightbox__close" type="button">${icon('M6 6l12 12M18 6 6 18')}</button>
        </div>
        <div class="hs-lightbox__stage">
          <button class="hs-lightbox__control hs-lightbox__nav hs-lightbox__previous" type="button">${icon('m15 18-6-6 6-6')}</button>
          <figure class="hs-lightbox__figure">
            <span class="hs-lightbox__loading" role="status"></span>
            <img class="hs-lightbox__image" alt="">
            <figcaption class="hs-lightbox__caption"></figcaption>
            <p class="hs-lightbox__error" role="alert"></p>
          </figure>
          <button class="hs-lightbox__control hs-lightbox__nav hs-lightbox__next" type="button">${icon('m9 18 6-6-6-6')}</button>
        </div>
      </div>`;
    document.body.append(element);

    const nodes = {
      caption: element.querySelector('.hs-lightbox__caption'),
      close: element.querySelector('.hs-lightbox__close'),
      counter: element.querySelector('.hs-lightbox__counter'),
      error: element.querySelector('.hs-lightbox__error'),
      image: element.querySelector('.hs-lightbox__image'),
      loading: element.querySelector('.hs-lightbox__loading'),
      next: element.querySelector('.hs-lightbox__next'),
      original: element.querySelector('.hs-lightbox__original'),
      originalLabel: element.querySelector('.hs-lightbox__original-label'),
      previous: element.querySelector('.hs-lightbox__previous'),
      stage: element.querySelector('.hs-lightbox__stage'),
      title: element.querySelector('.hs-lightbox__title'),
    };
    nodes.title.textContent = messages.title;
    nodes.close.setAttribute('aria-label', messages.close);
    nodes.previous.setAttribute('aria-label', messages.previous);
    nodes.next.setAttribute('aria-label', messages.next);
    nodes.originalLabel.textContent = messages.openOriginal;
    nodes.original.setAttribute('aria-label', messages.openOriginal);

    nodes.close.addEventListener('click', closeDialog);
    nodes.previous.addEventListener('click', () => showRelative(-1));
    nodes.next.addEventListener('click', () => showRelative(1));
    nodes.stage.addEventListener('pointerdown', event => {
      pointerStart = { id: event.pointerId, x: event.clientX, y: event.clientY };
    });
    nodes.stage.addEventListener('pointerup', handleSwipe);
    nodes.stage.addEventListener('click', event => {
      if (event.target === nodes.stage) closeDialog();
    });
    element.addEventListener('cancel', event => {
      event.preventDefault();
      closeDialog();
    });
    element.addEventListener('click', event => {
      if (event.target === element) closeDialog();
    });

    dialog = element;
    dialogNodes = nodes;
  }

  function formatCount(current, total) {
    return messages.imageCount.replace('%1$d', String(current)).replace('%2$d', String(total));
  }

  function preloadAdjacent() {
    if (activeItems.length < 2) return;
    [-1, 1].forEach(offset => {
      const item = activeItems[(activeIndex + offset + activeItems.length) % activeItems.length];
      const preload = new Image();
      preload.decoding = 'async';
      preload.src = item.source;
    });
  }

  function renderItem() {
    const item = activeItems[activeIndex];
    const hasGallery = activeItems.length > 1;
    dialogNodes.counter.textContent = hasGallery ? formatCount(activeIndex + 1, activeItems.length) : '';
    dialogNodes.previous.hidden = !hasGallery;
    dialogNodes.next.hidden = !hasGallery;
    dialogNodes.caption.textContent = item.caption;
    dialogNodes.caption.hidden = !item.caption;
    dialogNodes.original.href = item.source;
    dialogNodes.error.hidden = true;
    dialogNodes.error.textContent = '';
    dialogNodes.loading.hidden = false;
    dialogNodes.loading.textContent = messages.loading;
    dialogNodes.image.classList.remove('is-ready');
    dialogNodes.image.alt = item.alt;

    dialogNodes.image.onload = () => {
      dialogNodes.loading.hidden = true;
      dialogNodes.image.classList.add('is-ready');
    };
    dialogNodes.image.onerror = () => {
      dialogNodes.loading.hidden = true;
      dialogNodes.error.hidden = false;
      dialogNodes.error.textContent = messages.error;
    };
    dialogNodes.image.src = item.source;
    if (dialogNodes.image.complete && dialogNodes.image.naturalWidth > 0) dialogNodes.image.onload();
    preloadAdjacent();
  }

  function showRelative(offset) {
    if (activeItems.length < 2) return;
    activeIndex = (activeIndex + offset + activeItems.length) % activeItems.length;
    renderItem();
  }

  function handleSwipe(event) {
    if (!pointerStart || pointerStart.id !== event.pointerId) return;
    const deltaX = event.clientX - pointerStart.x;
    const deltaY = event.clientY - pointerStart.y;
    pointerStart = null;
    if (Math.abs(deltaX) < 56 || Math.abs(deltaX) <= Math.abs(deltaY)) return;
    showRelative(deltaX < 0 ? 1 : -1);
  }

  function openDialog(item) {
    if (!dialog) createDialog();
    activeItems = itemsFor(item);
    activeIndex = Math.max(0, activeItems.findIndex(candidate => candidate.trigger === item.trigger));
    opener = item.trigger;
    renderItem();
    documentRoot.dataset.hsLightboxOpen = 'true';
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    dialogNodes.close.focus({ preventScroll: true });
  }

  function restorePage() {
    delete documentRoot.dataset.hsLightboxOpen;
    dialogNodes.image.removeAttribute('src');
    if (opener?.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  }

  function closeDialog() {
    if (!dialog) return;
    if (typeof dialog.close === 'function' && dialog.open) {
      dialog.close();
      restorePage();
    }
    else {
      dialog.removeAttribute('open');
      restorePage();
    }
  }

  function interceptActivation(event) {
    if (event.type === 'click' && (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)) return;
    const item = itemFromTarget(event.target);
    if (!item) return;
    enhanceItem(item);
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openDialog(item);
  }

  document.addEventListener('click', interceptActivation, true);
  document.addEventListener('keydown', event => {
    if (dialog?.hasAttribute('open')) {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeDialog();
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showRelative(-1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        showRelative(1);
      }
      return;
    }
    if (!(event.target instanceof Element) || !['Enter', ' '].includes(event.key) || !event.target.matches('img[data-hs-lightbox-trigger="true"]')) return;
    interceptActivation(event);
  }, true);

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => enhance(document), { once: true });
  else enhance(document);
}());
