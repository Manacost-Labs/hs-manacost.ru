(function () {
  'use strict';

  if (typeof window !== 'undefined' && window.HS_TOOLTIP_INITIALIZED) {
    return;
  }
  if (typeof window !== 'undefined') {
    window.HS_TOOLTIP_INITIALIZED = true;
  }

  var tooltip = null;
  var tooltipImg = null;
  var tooltipLoader = null;
  var activeTarget = null;
  var offset = 15;
  var preloadedUrls = Object.create(null); // url → pending Image or true after load
  var loadedUrls = Object.create(null);    // url → true once browser cached
  var rafId = 0;
  var lastEvent = null;
  var hideTimer = 0;
  var nearbyRequests = 0;
  var nearbyPending = 0;
  var lastNearbyCheck = 0;
  if (typeof window !== 'undefined') {
    window.HS_TOOLTIP_LOADED = true;
  }

  function debugLog() {
    if (!(typeof window !== 'undefined' && window.HS_TOOLTIP_DEBUG) || !window.console) {
      return;
    }
    try {
      // eslint-disable-next-line no-console
      console.log.apply(console, arguments);
    } catch (e) {
      // ignore
    }
  }

  function preloadImage(url, priority, speculative) {
    if (!url) {
      return;
    }
    var pending = preloadedUrls[url];
    if (pending) {
      if (priority === 'high' && pending !== true && pending.fetchPriority !== undefined) {
        pending.fetchPriority = 'high';
      }
      return;
    }
    var img = new Image();
    preloadedUrls[url] = img;
    if (img.fetchPriority !== undefined) {
      img.fetchPriority = priority || 'low';
    }
    if (speculative) {
      nearbyRequests++;
      nearbyPending++;
    }
    img.onload = function () {
      loadedUrls[url] = true;
      preloadedUrls[url] = true;
      if (speculative) {
        nearbyPending--;
      }
    };
    img.onerror = function () {
      delete preloadedUrls[url];
      if (speculative) {
        nearbyPending--;
      }
    };
    img.src = url;
  }

  function preloadNearbyImage(event) {
    if (nearbyRequests >= 8 || nearbyPending >= 2 || !window.matchMedia
      || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      return;
    }
    var connection = typeof navigator !== 'undefined' && navigator.connection;
    if (connection && (connection.saveData || /^(slow-)?2g$/.test(connection.effectiveType))) {
      return;
    }
    var now = Date.now();
    if (now - lastNearbyCheck < 100) {
      return;
    }
    lastNearbyCheck = now;
    var el = event.target;
    if (el && el.nodeType === 3) {
      el = el.parentElement;
    }
    var scope = el && el.closest && el.closest('p, li, td, th, blockquote');
    if (!scope) {
      return;
    }
    var targets = scope.getElementsByClassName('hs-card-tooltip');
    var nearest = null;
    var distance = 120 * 120;
    // Bound layout reads to the current text block; never scan the whole article.
    for (var i = 0; i < targets.length && i < 20; i++) {
      var rect = targets[i].getBoundingClientRect();
      if (!rect.width || !rect.height) {
        continue;
      }
      var dx = Math.max(rect.left - event.clientX, event.clientX - rect.right, 0);
      var dy = Math.max(rect.top - event.clientY, event.clientY - rect.bottom, 0);
      var squared = dx * dx + dy * dy;
      if (squared < distance) {
        distance = squared;
        nearest = targets[i];
      }
    }
    if (nearest) {
      preloadImage(nearest.getAttribute('data-image'), 'low', true);
    }
  }

  function preloadTargetImages(target, priority) {
    if (!target || !target.getAttribute) {
      return;
    }
    preloadImage(target.getAttribute('data-image'), priority || 'low');
  }

  function createTooltip() {
    var existing = document.querySelectorAll('.hs-tooltip-box[data-hs-tooltip="1"]');
    if (existing.length > 0) {
      tooltip = existing[0];
      tooltipImg = tooltip.querySelector('img');
      tooltipLoader = tooltip.querySelector('.hs-tooltip-loader');
      for (var i = 1; i < existing.length; i++) {
        if (existing[i] && existing[i].parentNode) {
          existing[i].parentNode.removeChild(existing[i]);
        }
      }
      if (tooltipImg && tooltipLoader) {
        debugLog('[hs-tooltip] tooltip reused', tooltip);
        return;
      }
    }

    tooltip = document.createElement('div');
    tooltip.className = 'hs-tooltip-box';
    tooltip.setAttribute('data-hs-tooltip', '1');

    tooltipImg = document.createElement('img');
    tooltipImg.alt = 'Hearthstone card';
    tooltipImg.decoding = 'async';
    tooltipImg.loading = 'eager';
    tooltipImg.fetchPriority = 'high';

    tooltipLoader = document.createElement('div');
    tooltipLoader.className = 'hs-tooltip-loader';

    tooltip.appendChild(tooltipImg);
    tooltip.appendChild(tooltipLoader);
    document.documentElement.appendChild(tooltip);
    debugLog('[hs-tooltip] tooltip created', tooltip);
  }

  function setLoading(isLoading) {
    if (!tooltip) {
      return;
    }
    if (isLoading) {
      tooltip.classList.add('is-loading');
      tooltipLoader.style.display = 'block';
    } else {
      tooltip.classList.remove('is-loading');
      tooltipLoader.style.display = 'none';
    }
  }

  function updatePosition(event) {
    if (!tooltip) {
      return;
    }
    var rect = tooltip.getBoundingClientRect();
    var x = event.clientX + offset;
    var y = event.clientY + offset;

    if (x + rect.width > window.innerWidth) {
      x = event.clientX - rect.width - offset;
    }
    if (y + rect.height > window.innerHeight) {
      y = event.clientY - rect.height - offset;
    }

    tooltip.style.left = Math.max(0, x) + 'px';
    tooltip.style.top = Math.max(0, y) + 'px';
  }

  function showTooltip(target, event) {
    var imageUrl = target.getAttribute('data-image');
    var rawUrl = target.getAttribute('data-image-raw');
    if (!imageUrl) {
      debugLog('[hs-tooltip] missing data-image on target', target);
      return;
    }

    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = 0;
    }
    preloadTargetImages(target, 'high');

    if (!tooltip) {
      createTooltip();
    }

    if (activeTarget !== target) {
      activeTarget = target;
      // Если URL уже загружен в браузерный кэш — не показываем спиннер,
      // картинка появится моментально.
      var isCached = loadedUrls[imageUrl];
      setLoading(!isCached);
      tooltipImg.onload = function () {
        loadedUrls[imageUrl] = true;
        setLoading(false);
        debugLog('[hs-tooltip] image loaded');
      };
      tooltipImg.onerror = function () {
        if (rawUrl && rawUrl !== imageUrl && !tooltipImg.getAttribute('data-fallback')) {
          tooltipImg.setAttribute('data-fallback', '1');
          tooltipImg.src = rawUrl;
          debugLog('[hs-tooltip] proxy failed, trying raw image', {
            proxy: imageUrl,
            raw: rawUrl
          });
          return;
        }
        setLoading(false);
        debugLog('[hs-tooltip] image failed to load', {
          proxy: imageUrl,
          raw: rawUrl || ''
        });
      };
      tooltipImg.removeAttribute('data-fallback');
      tooltipImg.src = imageUrl;
      // Если src был уже загружен ранее (из preload или прошлого hover),
      // .complete будет true сразу — onload может не сработать, тогда
      // просто гасим спиннер сразу.
      if (tooltipImg.complete && tooltipImg.naturalWidth > 0) {
        loadedUrls[imageUrl] = true;
        setLoading(false);
      }
    }

    updatePosition(event);
    tooltip.classList.add('is-visible');
    tooltip.style.display = 'block';
    tooltip.style.visibility = 'visible';
    if (tooltipImg) {
      tooltipImg.style.display = 'block';
    }
    if (typeof window !== 'undefined' && window.HS_TOOLTIP_DEBUG) {
      tooltip.style.outline = '2px solid #ff00ff';
      tooltip.style.backgroundColor = 'rgba(0,0,0,0.9)';
      tooltip.style.zIndex = '2147483647';
    }
    debugLog('[hs-tooltip] show', imageUrl);
  }

  function hideTooltip() {
    if (!tooltip) {
      return;
    }
    tooltip.classList.remove('is-visible');
    activeTarget = null;
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = 0;
    }
    lastEvent = null;
    debugLog('[hs-tooltip] hide');
  }

  function scheduleHide() {
    if (hideTimer) {
      clearTimeout(hideTimer);
    }
    hideTimer = setTimeout(function () {
      hideTimer = 0;
      hideTooltip();
    }, 50);
  }

  function attachListeners() {
    if (typeof window !== 'undefined' && window.HS_TOOLTIP_LISTENERS_ATTACHED) {
      return true;
    }
    if (!document.body) {
      debugLog('[hs-tooltip] body not ready yet');
      return false;
    }
    if (typeof window !== 'undefined') {
      window.HS_TOOLTIP_LISTENERS_ATTACHED = true;
    }
    debugLog('[hs-tooltip] attach listeners');
    document.body.addEventListener('mouseover', function (event) {
      var el = event.target;
      if (el && el.nodeType === 3) {
        el = el.parentElement;
      }
      if (!el || !el.closest) {
        return;
      }
      var target = el.closest('.hs-card-tooltip');
      if (!target) {
        return;
      }
      if (activeTarget !== target) {
        debugLog('[hs-tooltip] hover target', target);
        showTooltip(target, event);
      }
    }, { passive: true });

    document.body.addEventListener('mousemove', function (event) {
      if (!tooltip || !activeTarget) {
        preloadNearbyImage(event);
        return;
      }
      lastEvent = event;
      if (rafId) {
        return;
      }
      rafId = requestAnimationFrame(function () {
        rafId = 0;
        if (lastEvent) {
          updatePosition(lastEvent);
          lastEvent = null;
        }
      });
    }, { passive: true });

    document.body.addEventListener('mouseout', function (event) {
      if (!activeTarget) {
        return;
      }
      var related = event.relatedTarget;
      if (related && related.nodeType === 3) {
        related = related.parentElement;
      }
      if (related && !related.closest) {
        related = null;
      }
      if (related && activeTarget.contains(related)) {
        return;
      }
      scheduleHide();
    }, { passive: true });

    return true;
  }

  if (!attachListeners()) {
    document.addEventListener('DOMContentLoaded', attachListeners, { once: true });
  }
})();
