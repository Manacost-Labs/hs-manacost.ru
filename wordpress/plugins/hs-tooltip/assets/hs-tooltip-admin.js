/* global jQuery, HSTooltipAdmin, tinymce */
(function ($) {
  'use strict';

  var $input = $('#hs-tooltip-search');
  var $results = $('#hs-tooltip-search-results');
  var $insert = $('#hs-tooltip-insert');
  var selected = null;
  var timer = null;
  var mainRequest = null;
  var mainSequence = 0;
  var mainCache = Object.create(null);

  function renderResults(items) {
    $results.empty();
    selected = null;
    if (!items.length) {
      $results.append('<div style="color:#777;">Нет результатов</div>');
      return;
    }
    items.forEach(function (item) {
      var $row = $('<div class="hs-tooltip-item" style="padding:4px 0; cursor:pointer;"></div>');
      $row.text(item.name + ' — ' + item.id);
      $row.data('id', item.id);
      $row.data('name', item.name);
      $results.append($row);
    });
  }

  function fetchResults(term) {
    var sequence = ++mainSequence;
    if (mainCache[term]) {
      renderResults(mainCache[term]);
      return;
    }
    if (mainRequest && mainRequest.readyState !== 4) {
      mainRequest.abort();
    }
    mainRequest = $.get(HSTooltipAdmin.ajaxUrl, {
      action: 'hs_tooltip_search',
      term: term,
      nonce: HSTooltipAdmin.nonce
    }).done(function (resp) {
      if (sequence === mainSequence && resp && resp.success) {
        mainCache[term] = resp.data || [];
        renderResults(mainCache[term]);
      }
    });
  }

  $input.on('input', function () {
    var term = $.trim($input.val());
    clearTimeout(timer);
    if (term.length < 2) {
      mainSequence += 1;
      if (mainRequest && mainRequest.readyState !== 4) {
        mainRequest.abort();
      }
      $results.empty();
      return;
    }
    timer = setTimeout(function () {
      fetchResults(term);
    }, 120);
  });

  $results.on('click', '.hs-tooltip-item', function () {
    $results.find('.hs-tooltip-item').css('font-weight', 'normal');
    $(this).css('font-weight', 'bold');
    selected = {
      // Не делаем .toUpperCase(): HSJSON-ID регистрозависимы
      // (CATA_190h, Core_CS2_200 и т.п.) и URL-рендер тоже.
      id: String($(this).data('id') || ''),
      name: String($(this).data('name') || '')
    };
  });

  function getSelectedText() {
    if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
      return tinymce.activeEditor.selection.getContent({ format: 'text' });
    }
    var textarea = document.getElementById('content');
    if (textarea && typeof textarea.selectionStart === 'number') {
      return textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
    }
    return '';
  }

  function insertContent(text) {
    if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
      tinymce.activeEditor.selection.setContent(text);
      return;
    }
    var textarea = document.getElementById('content');
    if (!textarea || typeof textarea.selectionStart !== 'number') {
      return;
    }
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
  }

  $insert.on('click', function () {
    if (!selected || !selected.id) {
      return;
    }
    var label = getSelectedText();
    if (!label) {
      label = selected.name;
    }
    var shortcode = '[hs_card id="' + selected.id + '"]' + label + '[/hs_card]';
    insertContent(shortcode);
  });

  // --- Battlegrounds search ---
  var $bgInput = $('#hs-tooltip-bgs-search');
  var $bgResults = $('#hs-tooltip-bgs-results');
  var $bgInsert = $('#hs-tooltip-bgs-insert');
  var bgSelected = null;
  var bgTimer = null;
  var bgRequest = null;
  var bgSequence = 0;
  var bgCatalog = null;
  var bgCatalogPromise = null;
  var bgCatalogSource = Array.isArray(HSTooltipAdmin.bgsCatalog) ? HSTooltipAdmin.bgsCatalog : null;
  var bgCatalogUrl = String(HSTooltipAdmin.bgsCatalogUrl || '');
  var bgLocalSearchAvailable = Boolean(
    window.HSTooltipSearch &&
    (bgCatalogSource || bgCatalogUrl)
  );

  function renderBgResults(items) {
    $bgResults.empty();
    bgSelected = null;
    if (!items.length) {
      $bgResults.append('<div style="color:#777;">Нет результатов</div>');
      return;
    }
    items.forEach(function (item) {
      var tier = item.tech ? ' [T' + item.tech + ']' : '';
      var type = item.type === 'HERO' ? ' (Герой)' :
        item.type === 'BATTLEGROUND_SPELL' ? ' (Заклинание BG)' :
        item.type === 'BATTLEGROUND_ANOMALY' ? ' (Аномалия)' :
        item.type === 'BATTLEGROUND_TRINKET' ? ' (Безделушка)' :
        item.type === 'BATTLEGROUND_QUEST_REWARD' ? ' (Награда за задание)' : '';
      var $row = $('<div class="hs-tooltip-bg-item" style="padding:4px 0; cursor:pointer;"></div>');
      $row.text(item.name + tier + type + ' — ' + item.id);
      $row.data('id', item.id);
      $row.data('name', item.name);
      $bgResults.append($row);
    });
  }

  function loadBgCatalog() {
    if (!bgLocalSearchAvailable) {
      return Promise.reject(new Error('local-search-unavailable'));
    }
    if (bgCatalog) {
      return Promise.resolve(bgCatalog);
    }
    if (bgCatalogPromise) {
      return bgCatalogPromise;
    }
    var sourcePromise;
    if (bgCatalogSource) {
      sourcePromise = Promise.resolve(bgCatalogSource);
    } else if (window.fetch) {
      sourcePromise = window.fetch(bgCatalogUrl, {
        credentials: 'same-origin',
        cache: 'force-cache'
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('catalog-http-' + response.status);
        }
        return response.json();
      });
    } else {
      sourcePromise = new Promise(function (resolve, reject) {
        $.getJSON(bgCatalogUrl).done(resolve).fail(reject);
      });
    }
    bgCatalogPromise = sourcePromise.then(function (source) {
      bgCatalog = window.HSTooltipSearch.buildBgCatalog(source);
      return bgCatalog;
    }).catch(function (error) {
      bgLocalSearchAvailable = false;
      bgCatalogPromise = null;
      throw error;
    });
    return bgCatalogPromise;
  }

  function fetchBgResults(term, sequence) {
    if (bgRequest && bgRequest.readyState !== 4) {
      bgRequest.abort();
    }
    bgRequest = $.get(HSTooltipAdmin.ajaxUrl, {
      action: HSTooltipAdmin.bgsAction || 'hs_tooltip_search_bgs',
      term: term,
      nonce: HSTooltipAdmin.nonce
    }).done(function (resp) {
      if (sequence === bgSequence && resp && resp.success) {
        renderBgResults(resp.data || []);
      }
    });
  }

  function searchBg(term) {
    var sequence = ++bgSequence;
    if (!bgLocalSearchAvailable) {
      fetchBgResults(term, sequence);
      return;
    }
    loadBgCatalog().then(function (catalog) {
      if (sequence !== bgSequence) {
        return;
      }
      renderBgResults(window.HSTooltipSearch.search(catalog, term, 15));
    }).catch(function () {
      if (sequence === bgSequence) {
        fetchBgResults(term, sequence);
      }
    });
  }

  $bgInput.on('input', function () {
    var term = $.trim($bgInput.val());
    clearTimeout(bgTimer);
    if (term.length < 2) {
      bgSequence += 1;
      if (bgRequest && bgRequest.readyState !== 4) {
        bgRequest.abort();
      }
      $bgResults.empty();
      return;
    }
    bgTimer = setTimeout(function () {
      searchBg(term);
    }, 60);
  });

  // Keep post.php light: start the cacheable catalog request only when the BG
  // search is actually used. AJAX remains the transparent fallback.
  if ($bgInput.length && bgLocalSearchAvailable) {
    $bgInput.one('focus', function () {
      loadBgCatalog().catch(function () {
        // AJAX remains the transparent fallback.
      });
    });
  }

  $bgResults.on('click', '.hs-tooltip-bg-item', function () {
    $bgResults.find('.hs-tooltip-bg-item').css('font-weight', 'normal');
    $(this).css('font-weight', 'bold');
    bgSelected = {
      id: String($(this).data('id') || ''),
      name: String($(this).data('name') || '')
    };
  });

  $bgInsert.on('click', function () {
    if (!bgSelected || !bgSelected.id) {
      return;
    }
    var label = getSelectedText();
    if (!label) {
      label = bgSelected.name;
    }
    var shortcode = '[hs_bg id="' + bgSelected.id + '"]' + label + '[/hs_bg]';
    insertContent(shortcode);
  });
})(jQuery);
