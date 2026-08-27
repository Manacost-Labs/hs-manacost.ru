jQuery(document).ready(function($) {
    const deckCardSelector = '.deck-card:not(.hs-decks-ad-card)';
    const feedAdSelector = '.hs-decks-ad-card';
    let modal = $();
    let modalImg = $();
    let modalLastTrigger = null;

    function ensureImageModal() {
        const existingModals = $('[id="image-modal"].hs-modal');
        modal = existingModals.first();

        if (!modal.length) {
            modal = $('<div/>', {
                id: 'image-modal',
                class: 'hs-modal',
                role: 'dialog',
                'aria-modal': 'true',
                'aria-hidden': 'true',
                'aria-label': 'Просмотр изображения колоды'
            });

            const closeButton = $('<button/>', {
                type: 'button',
                class: 'hs-modal-close',
                'aria-label': 'Закрыть просмотр изображения'
            });
            $('<span/>', { 'aria-hidden': 'true', text: '×' }).appendTo(closeButton);
            closeButton.appendTo(modal);
            $('<img/>', { id: 'modal-image', class: 'hs-modal-content', alt: '' }).appendTo(modal);
        }

        if (!modal.parent().is('body')) {
            modal.detach().appendTo(document.body);
        }

        // Дополняем accessibility-атрибуты и для старого HTML из page cache.
        modal.attr({
            role: 'dialog',
            'aria-modal': 'true',
            'aria-label': 'Просмотр изображения колоды'
        });
        if (!modal.is('[aria-hidden]')) {
            modal.attr('aria-hidden', modal.is(':visible') ? 'false' : 'true');
        }

        let closeButton = modal.children('.hs-modal-close').first();
        if (!closeButton.is('button')) {
            const legacyClose = closeButton;
            closeButton = $('<button/>', {
                type: 'button',
                class: 'hs-modal-close',
                'aria-label': 'Закрыть просмотр изображения'
            });
            $('<span/>', { 'aria-hidden': 'true', text: '×' }).appendTo(closeButton);
            if (legacyClose.length) {
                legacyClose.replaceWith(closeButton);
            } else {
                closeButton.prependTo(modal);
            }
        }

        // Старый кэш страницы может ещё содержать modal внутри каждого shortcode.
        // Оставляем только первый, уже перенесённый непосредственно в body.
        $('[id="image-modal"].hs-modal').not(modal).remove();

        modalImg = modal.find('[id="modal-image"]').first();
        if (!modalImg.length) {
            modalImg = $('<img/>', { id: 'modal-image', class: 'hs-modal-content', alt: '' }).appendTo(modal);
        }

        return modal;
    }

    function openImageModal(imageUrl, imageAlt, trigger) {
        if (!imageUrl) {
            return;
        }

        const currentModal = ensureImageModal();
        modalLastTrigger = trigger || document.activeElement;
        modalImg.attr({
            src: imageUrl,
            alt: imageAlt || 'Изображение колоды'
        });
        currentModal.attr('aria-hidden', 'false').stop(true, true).fadeIn(180, function() {
            currentModal.find('.hs-modal-close').trigger('focus');
        });
        $('body').addClass('hs-modal-open');
    }

    function closeImageModal() {
        const currentModal = ensureImageModal();
        currentModal.attr('aria-hidden', 'true').stop(true, true).fadeOut(160, function() {
            modalImg.removeAttr('src').attr('alt', '');
            $('body').removeClass('hs-modal-open');
            if (modalLastTrigger && document.documentElement.contains(modalLastTrigger)) {
                modalLastTrigger.focus();
            }
            modalLastTrigger = null;
        });
    }

    ensureImageModal();

    document.addEventListener('error', function(event) {
        const img = event.target;
        if (!img || !img.matches) {
            return;
        }

        if (img.matches('.deck-class-icon')) {
            img.style.display = 'none';
            return;
        }

        if (!img.matches('.hs-archetype-row-card-art img[data-fallback], .deck-img-clickable[data-fallback]')) {
            return;
        }

        const fallback = img.getAttribute('data-fallback');
        if (!fallback || img.src === fallback) {
            return;
        }

        img.removeAttribute('data-fallback');
        const artFrame = img.closest('.hs-archetype-row-card-art');
        if (artFrame) {
            artFrame.style.backgroundImage = 'url("' + fallback.replace(/"/g, '\\"') + '")';
        }
        img.src = fallback;
    }, true);

    function trackDeckEvent(deckId, eventType, source, context) {
        if (!eventType || typeof hsDecks === 'undefined' || !hsDecks.ajaxurl) {
            return;
        }
        $.ajax({
            url: hsDecks.ajaxurl,
            type: 'POST',
            data: {
                action: hsDecks.eventAction || 'hs_decks_track_event',
                post_id: deckId || 0,
                event_type: eventType,
                source: source || 'frontend',
                context: context || {},
                nonce: hsDecks.nonce
            }
        });
    }

    const deckViewQueue = [];
    const deckViewQueued = {};
    let deckViewFlushTimer = null;
    const deckViewBatchSize = 30;
    const deckViewFlushDelay = 2500;
    const deckViewLocalTtl = 60 * 60 * 1000;
    const deckCopyLocalTtl = 60 * 1000;

    function deckViewLocalKey(deckId) {
        return 'hsDeckView:' + deckId;
    }

    function wasDeckViewRecentlySent(deckId) {
        try {
            const sentAt = parseInt(window.localStorage.getItem(deckViewLocalKey(deckId)), 10) || 0;
            return sentAt > 0 && (Date.now() - sentAt) < deckViewLocalTtl;
        } catch (e) {
            return false;
        }
    }

    function markDeckViewSent(deckId) {
        try {
            window.localStorage.setItem(deckViewLocalKey(deckId), String(Date.now()));
        } catch (e) {
            // localStorage can be unavailable in strict privacy modes; server-side guards still apply.
        }
    }

    function deckCopyLocalKey(deckId) {
        return 'hsDeckCopy:' + deckId;
    }

    function wasDeckCopyRecentlySent(deckId) {
        try {
            const sentAt = parseInt(window.localStorage.getItem(deckCopyLocalKey(deckId)), 10) || 0;
            return sentAt > 0 && (Date.now() - sentAt) < deckCopyLocalTtl;
        } catch (e) {
            return false;
        }
    }

    function markDeckCopySent(deckId) {
        try {
            window.localStorage.setItem(deckCopyLocalKey(deckId), String(Date.now()));
        } catch (e) {
            // Server-side dedupe still protects the counter.
        }
    }

    function scheduleDeckViewFlush() {
        if (deckViewFlushTimer) {
            return;
        }
        deckViewFlushTimer = window.setTimeout(flushDeckViewQueue, deckViewFlushDelay);
    }

    function flushDeckViewQueue() {
        if (deckViewFlushTimer) {
            window.clearTimeout(deckViewFlushTimer);
            deckViewFlushTimer = null;
        }

        if (!deckViewQueue.length || typeof hsDecks === 'undefined' || !hsDecks.ajaxurl) {
            return;
        }

        const ids = deckViewQueue.splice(0, deckViewBatchSize);
        ids.forEach(function(id) {
            delete deckViewQueued[id];
        });

        $.ajax({
            url: hsDecks.ajaxurl,
            type: 'POST',
            data: {
                action: hsDecks.eventAction || 'hs_decks_track_event',
                post_ids: ids,
                event_type: 'deck_view',
                source: 'feed',
                nonce: hsDecks.nonce
            }
        });

        if (deckViewQueue.length) {
            scheduleDeckViewFlush();
        }
    }

    function trackDeckView(deckId) {
        const numericDeckId = parseInt(deckId, 10);
        if (!numericDeckId || deckViewQueued[numericDeckId]) {
            return;
        }
        if (wasDeckViewRecentlySent(numericDeckId)) {
            return;
        }

        deckViewQueued[numericDeckId] = true;
        markDeckViewSent(numericDeckId);
        deckViewQueue.push(numericDeckId);

        if (deckViewQueue.length >= deckViewBatchSize) {
            flushDeckViewQueue();
        } else {
            scheduleDeckViewFlush();
        }
    }

    function setupDeckViewTracking(scope) {
        const cards = (scope ? $(scope) : $(document)).find(deckCardSelector);
        if (!cards.length || typeof IntersectionObserver === 'undefined') {
            return;
        }

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting || entry.intersectionRatio < 0.45) {
                    return;
                }
                const card = $(entry.target);
                if (card.data('viewSent')) {
                    observer.unobserve(entry.target);
                    return;
                }
                card.data('viewSent', true);
                trackDeckView(card.data('deck-id'));
                observer.unobserve(entry.target);
            });
        }, { threshold: [0.45] });

        cards.each(function() {
            observer.observe(this);
        });
    }

    function getDeckCards() {
        return $(deckCardSelector).not('.no-decks-filter').not('.deck-single');
    }

    function placeFeedAds(visibleDecks) {
        const ads = $(feedAdSelector);
        if (!ads.length) {
            return;
        }

        ads.hide();
        let adIndex = 0;
        visibleDecks.each(function(index) {
            const deckPosition = index + 1;
            if (deckPosition % 6 !== 0) {
                return;
            }
            const ad = ads.eq(adIndex);
            if (!ad.length) {
                return;
            }
            ad.insertAfter($(this)).css('display', 'block');
            adIndex++;
        });
    }

    function setupAdImpressions() {
        const ads = $(feedAdSelector);
        if (!ads.length || typeof IntersectionObserver === 'undefined') {
            return;
        }

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (!entry.isIntersecting || entry.intersectionRatio < 0.3) {
                    return;
                }

                const ad = $(entry.target);
                if (ad.data('impressionSent')) {
                    observer.unobserve(entry.target);
                    return;
                }

                ad.data('impressionSent', true);
                $.ajax({
                    url: hsDecks.ajaxurl,
                    type: 'POST',
                    data: {
                        action: hsDecks.adImpressionAction || 'hs_decks_ad_impression',
                        ad: ad.data('ad-key') || 'boosty_feed',
                        nonce: hsDecks.nonce
                    }
                });
                observer.unobserve(entry.target);
            });
        }, { threshold: [0.3] });

        ads.each(function() {
            observer.observe(this);
        });
    }
    
    $(document).on('click', '.deck-img-clickable', function(e) {
        e.preventDefault();
        trackDeckEvent($(this).closest('.deck-card, .hs-deck-group-card').data('deck-id'), 'image_open', 'deck_card');
        openImageModal($(this).attr('src'), $(this).attr('alt'), this);
    });

    $(document).on('keydown', '.deck-img-clickable', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        e.preventDefault();
        $(this).trigger('click');
    });
    
    $(document).on('click', '.proof-btn', function(e) {
        e.preventDefault();
        const proofUrl = $(this).data('proof');
        if (proofUrl) {
            trackDeckEvent($(this).closest('.deck-card, .hs-deck-group-card').data('deck-id'), 'proof_open', 'deck_card');
            openImageModal(proofUrl, 'Доказательство ранга Легенды', this);
        }
    });

    $(document).on('click', '.deck-source', function() {
        trackDeckEvent($(this).closest('.deck-card, .hs-deck-group-card').data('deck-id'), 'source_click', 'deck_card');
    });

    $(document).on('input', '.hs-archetypes-search', function() {
        const search = String($(this).val() || '').toLowerCase().trim();
        $('.hs-archetype-card').each(function() {
            const card = $(this);
            const haystack = String(card.data('search') || '').toLowerCase();
            card.toggle(!search || haystack.indexOf(search) !== -1);
        });
    });

    const archetypeIndexCache = {};

    function collectArchetypeIndexParams(section, page) {
        const form = section.find('.hs-archetypes-toolbar');
        const params = {};
        form.serializeArray().forEach(function(item) {
            params[item.name] = item.value;
        });
        params.hs_arch_page = page || params.hs_arch_page || 1;
        return params;
    }

    function buildArchetypeIndexUrl(params) {
        const url = new URL(window.location.href);
        Object.keys(params).forEach(function(key) {
            const value = String(params[key] || '').trim();
            if (value && !(key === 'hs_arch_page' && value === '1')) {
                url.searchParams.set(key, value);
            } else {
                url.searchParams.delete(key);
            }
        });
        return url.toString();
    }

    function loadArchetypeIndex(section, params) {
        if (!section.length || typeof hsDecks === 'undefined' || !hsDecks.ajaxurl) {
            return;
        }
        const targetUrl = buildArchetypeIndexUrl(params);
        const cacheKey = JSON.stringify({
            url: targetUrl,
            per_page: section.data('per-page') || 12,
            limit: section.data('limit') || 0,
            min_decks: section.data('min-decks') || 5
        });
        if (archetypeIndexCache[cacheKey]) {
            section.replaceWith($(archetypeIndexCache[cacheKey].html));
            if (window.history && window.history.replaceState && archetypeIndexCache[cacheKey].url) {
                window.history.replaceState({}, '', archetypeIndexCache[cacheKey].url);
            }
            return;
        }
        section.addClass('is-loading');
        $.ajax({
            url: hsDecks.ajaxurl,
            type: 'POST',
            data: $.extend({}, params, {
                action: hsDecks.archetypesAction || 'hs_deck_archetypes_page',
                nonce: hsDecks.nonce,
                base_url: targetUrl,
                per_page: section.data('per-page') || 12,
                limit: section.data('limit') || 0,
                min_decks: section.data('min-decks') || 5
            }),
            success: function(response) {
                if (!response || !response.success || !response.data || !response.data.html) {
                    return;
                }
                archetypeIndexCache[cacheKey] = {
                    html: response.data.html,
                    url: response.data.url || targetUrl
                };
                const nextSection = $(response.data.html);
                section.replaceWith(nextSection);
                if (window.history && window.history.replaceState && response.data.url) {
                    window.history.replaceState({}, '', response.data.url);
                }
            },
            complete: function() {
                section.removeClass('is-loading');
            }
        });
    }

    $(document).on('submit', '.hs-archetypes-toolbar', function(event) {
        const section = $(this).closest('[data-hs-archetypes]');
        if (!section.length) {
            return;
        }
        event.preventDefault();
        loadArchetypeIndex(section, collectArchetypeIndexParams(section, 1));
    });

    $(document).on('change', '.hs-archetypes-toolbar select', function() {
        $(this).closest('.hs-archetypes-toolbar').trigger('submit');
    });

    $(document).on('click', '.hs-archetype-pagination a.page-numbers, .hs-archetypes-reset', function(event) {
        const section = $(this).closest('[data-hs-archetypes]');
        if (!section.length) {
            return;
        }
        event.preventDefault();
        const url = new URL(this.href, window.location.href);
        const params = collectArchetypeIndexParams(section, url.searchParams.get('hs_arch_page') || 1);
        params.hs_arch_search = url.searchParams.get('hs_arch_search') || '';
        params.hs_arch_class = url.searchParams.get('hs_arch_class') || '';
        loadArchetypeIndex(section, params);
    });
    
    $(document).on('click', '.hs-modal-close', function(e) {
        e.preventDefault();
        closeImageModal();
    });

    $(document).on('click', '#image-modal.hs-modal', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });
    
    $(document).keyup(function(e) {
        if (e.key === 'Escape' && modal.length && modal.is(':visible')) {
            closeImageModal();
        }
    });

    $(document).on('keydown', '#image-modal.hs-modal', function(e) {
        if (e.key !== 'Tab' || !modal.is(':visible')) {
            return;
        }
        // В диалоге только один интерактивный элемент: удерживаем фокус на нём.
        e.preventDefault();
        modal.find('.hs-modal-close').trigger('focus');
    });

    function getVoteCards(scope) {
        const root = scope ? $(scope) : $(document);
        let cards = root.find('.deck-card[data-deck-id]');
        if (root.is('.deck-card[data-deck-id]')) {
            cards = cards.add(root);
        }
        return cards.filter(function() {
            return $(this).find('.vote-btn').length > 0;
        });
    }

    function hasVoteCookie(deckId) {
        const prefix = 'hs_voted_' + deckId + '=';
        return document.cookie.split(';').some(function(cookie) {
            return cookie.trim().indexOf(prefix) === 0;
        });
    }

    function setupVoteState(scope) {
        getVoteCards(scope).each(function() {
            const card = $(this);
            if (!hasVoteCookie(card.data('deck-id'))) {
                return;
            }
            card.addClass('voted');
            card.find('.vote-btn').prop('disabled', true);
        });
    }

    function hydrateVoteCounts(scope) {
        if (typeof hsDecks === 'undefined' || !hsDecks.ajaxurl) {
            return;
        }

        const cards = getVoteCards(scope);
        const ids = [];
        cards.each(function() {
            const deckId = parseInt($(this).data('deck-id'), 10);
            if (deckId && ids.indexOf(deckId) === -1 && ids.length < 100) {
                ids.push(deckId);
            }
        });
        if (!ids.length) {
            return;
        }

        $.ajax({
            url: hsDecks.ajaxurl,
            type: 'POST',
            data: {
                action: hsDecks.voteCountsAction || 'hs_decks_vote_counts',
                post_ids: ids,
                nonce: hsDecks.nonce
            },
            success: function(response) {
                if (!response || !response.success || !response.data || !response.data.counts) {
                    return;
                }
                Object.keys(response.data.counts).forEach(function(deckId) {
                    const counts = response.data.counts[deckId] || {};
                    const matchingCards = $('.deck-card[data-deck-id="' + parseInt(deckId, 10) + '"]').filter(function() {
                        // Не перезаписываем более свежий результат локального vote AJAX
                        // ответом hydration-запроса, который мог стартовать раньше.
                        return !$(this).data('voteUpdated');
                    });
                    matchingCards.attr('data-likes', parseInt(counts.likes, 10) || 0);
                    matchingCards.attr('data-dislikes', parseInt(counts.dislikes, 10) || 0);
                    matchingCards.find('.like-btn .vote-count').text(parseInt(counts.likes, 10) || 0);
                    matchingCards.find('.dislike-btn .vote-count').text(parseInt(counts.dislikes, 10) || 0);
                });
            }
        });
    }
    
    $(document).on('click', '.copy-code-btn', function() {
        const btn = $(this);
        const code = btn.data('code');
        const deckId = btn.closest('.deck-card, .hs-deck-group-card').data('deck-id');
        
        if (!code) {
            alert('Код колоды не найден');
            return;
        }
        
        navigator.clipboard.writeText(code).then(function() {
            const originalText = btn.text();
            btn.addClass('copy-success');
            btn.text('✓ Скопировано!');
            
            if (deckId && !wasDeckCopyRecentlySent(deckId)) {
                markDeckCopySent(deckId);
                $.ajax({
                    url: hsDecks.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'copy_deck_code',
                        post_id: deckId,
                        nonce: hsDecks.nonce
                    }
                });
            }
            
            setTimeout(function() {
                btn.removeClass('copy-success');
                btn.text(originalText);
            }, 2000);
        }).catch(function(err) {
            alert('Ошибка копирования');
            console.error('Copy failed:', err);
        });
    });
    
    $(document).on('click', '.vote-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        const deckCard = btn.closest('.deck-card');
        const deckId = deckCard.data('deck-id');
        const voteType = btn.data('vote');
        
        if (deckCard.hasClass('voted')) {
            alert('Вы уже голосовали за эту колоду');
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: hsDecks.ajaxurl,
            type: 'POST',
            data: {
                action: 'vote_deck',
                post_id: deckId,
                vote_type: voteType,
                nonce: hsDecks.nonce
            },
            success: function(response) {
                if (response.success) {
                    const newCount = response.data.new_count;
                    const matchingCards = $('.deck-card[data-deck-id="' + parseInt(deckId, 10) + '"]');
                    const responseLikes = parseInt(response.data.likes, 10);
                    const responseDislikes = parseInt(response.data.dislikes, 10);
                    const previousLikes = parseInt(matchingCards.first().attr('data-likes'), 10) || 0;
                    const previousDislikes = parseInt(matchingCards.first().attr('data-dislikes'), 10) || 0;
                    const likesCount = Number.isNaN(responseLikes)
                        ? (voteType === 'like' ? newCount : previousLikes)
                        : responseLikes;
                    const dislikesCount = Number.isNaN(responseDislikes)
                        ? (voteType === 'dislike' ? newCount : previousDislikes)
                        : responseDislikes;
                    matchingCards.data('voteUpdated', true);
                    matchingCards.find('.like-btn .vote-count').text(likesCount);
                    matchingCards.find('.dislike-btn .vote-count').text(dislikesCount);
                    btn.addClass('vote-success');
                    matchingCards.addClass('voted');
                    
                    matchingCards.attr('data-likes', likesCount);
                    matchingCards.attr('data-dislikes', dislikesCount);
                    
                    setTimeout(function() {
                        btn.removeClass('vote-success');
                    }, 500);
                    
                    matchingCards.find('.vote-btn').prop('disabled', true);
                } else {
                    alert(response.data.message || 'Ошибка голосования');
                    btn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX:', {xhr: xhr, status: status, error: error});
                alert('Ошибка сервера');
                btn.prop('disabled', false);
            }
        });
    });

    if ($('#decks-grid').data('server-feed')) {
        const form = $('#hs-decks-filter-form');
        const grid = $('#decks-grid');
        const loadMore = $('#hs-load-more');
        const perPage = parseInt(grid.data('per-page'), 10) || 13;
        let serverFilterTimeout = null;
        let serverRequest = null;
        let pendingFilterKey = '';
        let appliedFilterKey = '';

        function getActiveServerTags() {
            const tags = [];
            $('.tag-filter-btn.active').each(function() {
                tags.push(String($(this).data('tag') || '').toLowerCase());
            });
            return tags;
        }

        function updateServerTagFields() {
            const tags = getActiveServerTags();
            $('#hs-tags-value').val(tags.join(','));
            $('#hs-tags-mode-value').val($('#tags-all-checkbox').is(':checked') ? 'all' : 'any');
            if (tags.length >= 2) {
                $('#tags-logic-label').fadeIn(120);
            } else {
                $('#tags-logic-label').fadeOut(120);
                $('#tags-all-checkbox').prop('checked', false);
                $('#hs-tags-mode-value').val('any');
            }
        }

        function collectServerFilters(page) {
            updateServerTagFields();
            const values = {
                hs_search: $('#deck-search').val() || '',
                hs_period: $('#filter-period').val() || '',
                hs_class: $('#filter-class').val() || '',
                hs_mode: $('#filter-mode').val() || grid.data('mode') || '',
                hs_archetype: $('#filter-archetype').val() || grid.data('archetype') || '',
                hs_streamer: $('#filter-streamer').val() || '',
                hs_source: $('#filter-source').val() || '',
                hs_games_min: $('#filter-games').val() || '',
                hs_winrate_min: $('#filter-winrate').val() || '',
                hs_dust_max: $('#filter-dust').val() || '',
                hs_sort: $('#filter-sort').val() || 'date',
                hs_tags: $('#hs-tags-value').val() || '',
                hs_tags_mode: $('#hs-tags-mode-value').val() || 'any',
                hs_page: page || $('#hs-page').val() || 1
            };
            return values;
        }

        function updateServerUrl(filters) {
            if (!window.history || !window.history.replaceState) {
                return;
            }
            const url = new URL(window.location.href);
            Object.keys(filters).forEach(function(key) {
                const value = String(filters[key] || '').trim();
                if (value && !(key === 'hs_page' && value === '1')) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });
            window.history.replaceState({}, '', url.toString());
        }

        function getServerFilterKey(filters) {
            return JSON.stringify($.extend({}, filters, {
                per_page: perPage
            }));
        }

        function scheduleServerFilters(page, scrollToGrid, delay) {
            if (serverFilterTimeout) {
                window.clearTimeout(serverFilterTimeout);
            }
            serverFilterTimeout = window.setTimeout(function() {
                serverFilterTimeout = null;
                applyServerFilters(page, scrollToGrid);
            }, delay || 300);
        }

        function applyServerFilters(page, scrollToGrid) {
            if (serverFilterTimeout) {
                window.clearTimeout(serverFilterTimeout);
                serverFilterTimeout = null;
            }

            const filters = collectServerFilters(page || 1);
            filters.hs_page = parseInt(filters.hs_page, 10) || 1;
            $('#hs-page').val(filters.hs_page);
            updateServerUrl(filters);

            const filterKey = getServerFilterKey(filters);
            if (filterKey === appliedFilterKey && (!serverRequest || serverRequest.readyState === 4)) {
                return;
            }
            if (filterKey === pendingFilterKey && serverRequest && serverRequest.readyState !== 4) {
                return;
            }

            if (serverRequest && serverRequest.readyState !== 4) {
                serverRequest.abort();
            }

            pendingFilterKey = filterKey;
            grid.addClass('hs-decks-grid-loading');
            serverRequest = $.ajax({
                url: hsDecks.ajaxurl,
                type: 'POST',
                data: $.extend({}, filters, {
                    action: hsDecks.filterAction || 'hs_decks_filter',
                    per_page: perPage,
                    nonce: hsDecks.nonce
                }),
                success: function(response) {
                    if (!response || !response.success || !response.data) {
                        return;
                    }
                    grid.html(response.data.html || '');
                    loadMore.html(response.data.pagination || '');
                    setupVoteState(grid);
                    hydrateVoteCounts(grid);
                    setupAdImpressions();
                    setupDeckViewTracking(grid);
                    appliedFilterKey = filterKey;
                    if (scrollToGrid && grid.length) {
                        $('html, body').animate({ scrollTop: grid.offset().top - 150 }, 250);
                    }
                },
                complete: function() {
                    if (pendingFilterKey === filterKey) {
                        pendingFilterKey = '';
                    }
                    grid.removeClass('hs-decks-grid-loading');
                }
            });
        }

        function resetServerFilters() {
            form.find('input[type="text"], input[type="search"], input[type="number"], input[type="hidden"]').val('');
            $('#hs-page').val('1');
            $('#hs-tags-mode-value').val('any');
            form.find('select').each(function() {
                $(this).val($(this).find('option:first').val());
            });
            $('#filter-sort').val('date');
            $('.tag-filter-btn').removeClass('active');
            $('#tags-all-checkbox').prop('checked', false);
            updateServerTagFields();
            appliedFilterKey = '';
            pendingFilterKey = '';
            applyServerFilters(1, true);
        }

        $('#hs-advanced-toggle').on('click', function() {
            const btn = $(this);
            const panel = $('#hs-advanced-filters');
            const expanded = btn.attr('aria-expanded') === 'true';
            btn.attr('aria-expanded', expanded ? 'false' : 'true');
            btn.toggleClass('active', !expanded);
            panel.prop('hidden', expanded);
        });

        $('#hs-filter-reset').on('click', resetServerFilters);

        form.on('submit', function(event) {
            event.preventDefault();
            applyServerFilters(1, true);
        });

        $('#deck-search, #filter-games, #filter-winrate, #filter-dust').on('input', function() {
            scheduleServerFilters(1, false, 600);
        });

        $('#filter-period, #filter-class, #filter-mode, #filter-archetype, #filter-streamer, #filter-source, #filter-sort').on('change', function() {
            scheduleServerFilters(1, false, 300);
        });

        $(document).on('click', '.tag-filter-btn', function() {
            $(this).toggleClass('active');
            updateServerTagFields();
            scheduleServerFilters(1, false, 300);
        });

        $('#tags-all-checkbox').on('change', function() {
            updateServerTagFields();
            scheduleServerFilters(1, false, 300);
        });

        $(document).on('click', '.hs-feed-pagination-server .hs-page-btn', function(event) {
            event.preventDefault();
            const btn = $(this);
            if (btn.prop('disabled')) {
                return;
            }
            applyServerFilters(parseInt(btn.attr('data-page'), 10) || 1, true);
        });

        $(document).on('input', '.hs-archetypes-search', function() {
            const search = String($(this).val() || '').toLowerCase().trim();
            $('.hs-archetype-card').each(function() {
                const card = $(this);
                const haystack = String(card.data('search') || '').toLowerCase();
                card.toggle(!search || haystack.indexOf(search) !== -1);
            });
        });

        updateServerTagFields();
        setupAdImpressions();
        setupDeckViewTracking(document);
        return;
    }
    
    let currentPage = 1;
    const initialPerPage = parseInt($('#decks-grid').data('per-page'), 10) || 13;
    
    function updatePagination() {
        // Игнорируем одиночные карточки (из шортkода [hs_deck])
        const allCards = getDeckCards();
        
        // Получаем все карточки, которые прошли фильтрацию
        let filteredCards = allCards.filter(function() {
            return !$(this).hasClass('deck-filtered-hidden');
        });
        
        const totalCards = filteredCards.length;
        const totalPages = Math.max(1, Math.ceil(totalCards / initialPerPage));
        currentPage = Math.max(1, Math.min(currentPage, totalPages));
        const startIndex = (currentPage - 1) * initialPerPage;
        const endIndex = startIndex + initialPerPage;
        
        // Применяем пагинацию: показываем только карточки текущей страницы.
        filteredCards.each(function(index) {
            if (index >= startIndex && index < endIndex) {
                $(this).css('display', 'block');
            } else {
                $(this).css('display', 'none');
            }
        });
        
        // Скрываем отфильтрованные карточки
        allCards.filter('.deck-filtered-hidden').css('display', 'none');
        placeFeedAds(filteredCards.filter(function() {
            return $(this).css('display') !== 'none';
        }));
        
        const loadMoreContainer = $('#hs-load-more');
        
        if (totalCards <= initialPerPage) {
            loadMoreContainer.empty();
            return;
        }

        let paginationHtml = '<nav class="hs-feed-pagination" aria-label="Пагинация колод">';
        paginationHtml += '<button type="button" class="hs-page-btn hs-page-prev" data-page="' + (currentPage - 1) + '"' + (currentPage <= 1 ? ' disabled' : '') + '>Назад</button>';

        const pageWindow = 2;
        let lastRenderedPage = 0;
        for (let page = 1; page <= totalPages; page++) {
            const isEdgePage = page === 1 || page === totalPages;
            const isNearCurrent = Math.abs(page - currentPage) <= pageWindow;

            if (!isEdgePage && !isNearCurrent) {
                if (lastRenderedPage !== -1) {
                    paginationHtml += '<span class="hs-page-ellipsis" aria-hidden="true">…</span>';
                    lastRenderedPage = -1;
                }
                continue;
            }

            paginationHtml += '<button type="button" class="hs-page-btn' + (page === currentPage ? ' active' : '') + '" data-page="' + page + '"' + (page === currentPage ? ' aria-current="page"' : '') + '>' + page + '</button>';
            lastRenderedPage = page;
        }

        paginationHtml += '<button type="button" class="hs-page-btn hs-page-next" data-page="' + (currentPage + 1) + '"' + (currentPage >= totalPages ? ' disabled' : '') + '>Вперёд</button>';
        paginationHtml += '</nav>';
        paginationHtml += '<div class="hs-load-more-info">Страница ' + currentPage + ' из ' + totalPages + ' · показано ' + (startIndex + 1) + '–' + Math.min(endIndex, totalCards) + ' из ' + totalCards + ' колод</div>';

        loadMoreContainer.html(paginationHtml);
    }
    
    $(document).on('click', '.hs-page-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = $(this);
        if (btn.prop('disabled')) {
            return;
        }

        const targetPage = parseInt(btn.attr('data-page'), 10);
        if (!targetPage || targetPage === currentPage) {
            return;
        }

        currentPage = targetPage;
        updatePagination();

        const grid = $('#decks-grid');
        if (grid.length) {
            $('html, body').animate({
                scrollTop: grid.offset().top - 150
            }, 300);
        }
    });
    
    let searchTimeout;

    function getActiveTags() {
        const activeTags = [];
        $('.tag-filter-btn.active').each(function() {
            activeTags.push(String($(this).data('tag') || '').toLowerCase());
        });
        return activeTags;
    }

    function getCardTags(card) {
        const rawTags = String(card.attr('data-tags') || '');
        if (!rawTags) {
            return [];
        }
        try {
            const parsed = JSON.parse(rawTags);
            if (Array.isArray(parsed)) {
                return parsed.map(function(tag) {
                    return String(tag || '').toLowerCase();
                });
            }
        } catch (e) {
            return rawTags.split('|').map(function(tag) {
                return String(tag || '').toLowerCase().trim();
            }).filter(Boolean);
        }
        return [];
    }

    function getNumberFilter(selector) {
        const value = parseFloat($(selector).val());
        return Number.isFinite(value) ? value : 0;
    }

    function updateTagsLogicVisibility() {
        const activeTagsCount = $('.tag-filter-btn.active').length;
        if (activeTagsCount >= 2) {
            $('#tags-logic-label').fadeIn(200);
        } else {
            $('#tags-logic-label').fadeOut(200);
            $('#tags-all-checkbox').prop('checked', false);
        }
    }

    function resetFilters() {
        $('#deck-search').val('');
        $('#filter-period').val('');
        $('#filter-class').val('');
        $('#filter-mode').val('');
        $('#filter-dust').val('');
        $('#filter-games').val('');
        $('#filter-winrate').val('');
        $('#filter-sort').val('date');
        $('#filter-streamer').val('');
        $('.tag-filter-btn').removeClass('active');
        $('#tags-all-checkbox').prop('checked', false);
        updateTagsLogicVisibility();
        currentPage = 1;
        filterDecks();
    }

    $('#hs-advanced-toggle').on('click', function() {
        const btn = $(this);
        const panel = $('#hs-advanced-filters');
        const expanded = btn.attr('aria-expanded') === 'true';
        btn.attr('aria-expanded', expanded ? 'false' : 'true');
        btn.toggleClass('active', !expanded);
        panel.prop('hidden', expanded);
    });

    $('#hs-filter-reset').on('click', function() {
        resetFilters();
    });
    
    $('#filter-sort').on('change', function() {
        currentPage = 1;
        filterDecks();
    });
    
    $('#filter-dust, #filter-games, #filter-winrate').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            filterDecks();
        }, 300);
    });
    
    $(document).on('click', '.tag-filter-btn', function() {
        $(this).toggleClass('active');
        updateTagsLogicVisibility();
        
        currentPage = 1;
        filterDecks();
    });
    
    $('#tags-all-checkbox').on('change', function() {
        currentPage = 1;
        filterDecks();
    });
    
    function filterByTags() {
        const activeTags = [];
        $('.tag-filter-btn.active').each(function() {
            activeTags.push($(this).data('tag'));
        });
        
        // Игнорируем одиночные карточки
        const allCards = getDeckCards();
        
        if (activeTags.length > 0) {
            const requireAllTags = $('#tags-all-checkbox').is(':checked');
            let visibleCount = 0;
            
            allCards.each(function() {
                const card = $(this);
                const searchData = String(card.attr('data-search') || '').toLowerCase();
                
                let shouldShow = false;
                
                if (requireAllTags) {
                    shouldShow = activeTags.every(function(activeTag) {
                        return searchData.indexOf(activeTag) !== -1;
                    });
                } else {
                    shouldShow = activeTags.some(function(activeTag) {
                        return searchData.indexOf(activeTag) !== -1;
                    });
                }
                
                if (shouldShow) {
                    card.addClass('deck-filtered-visible').removeClass('deck-filtered-hidden');
                    visibleCount++;
                } else {
                    card.addClass('deck-filtered-hidden').removeClass('deck-filtered-visible');
                }
            });
            
            $('.no-decks-filter').remove();
            if (visibleCount === 0) {
                $('#decks-grid').append('<p class="no-decks no-decks-filter">Колоды с выбранными тегами не найдены</p>');
            }
        } else {
            allCards.removeClass('deck-filtered-hidden deck-filtered-visible');
            $('.no-decks-filter').remove();
        }
        
        updatePagination();
    }
    
    function filterDecks() {
        const search = $('#deck-search').val().toLowerCase().trim();
        const classFilter = $('#filter-class').val();
        const modeFilter = $('#filter-mode').val();
        const periodDays = parseInt($('#filter-period').val(), 10) || 0;
        const sortBy = $('#filter-sort').val();
        const maxDust = getNumberFilter('#filter-dust');
        const minGames = getNumberFilter('#filter-games');
        const minWinrate = getNumberFilter('#filter-winrate');
        const streamerFilter = String($('#filter-streamer').val() || '').toLowerCase();
        const activeTags = getActiveTags();
        const requireAllTags = $('#tags-all-checkbox').is(':checked');
        const minTimestamp = periodDays > 0 ? Math.floor(Date.now() / 1000) - (periodDays * 24 * 60 * 60) : 0;
        
        // Игнорируем одиночные карточки
        let allCards = getDeckCards();
        let visibleCount = 0;
        
        allCards.each(function() {
            const card = $(this);
            const searchData = String(card.attr('data-search') || '').toLowerCase();
            const cardClassString = String(card.attr('data-class') || '');
            const cardModeString = String(card.attr('data-mode') || '');
            const cardClasses = cardClassString.split(',').filter(Boolean);
            const cardModes = cardModeString.split(',').filter(Boolean);
            const cardDust = parseInt(card.attr('data-dust')) || 0;
            const cardGames = parseInt(card.attr('data-games')) || 0;
            const cardWinrate = parseFloat(card.attr('data-winrate')) || 0;
            const cardDate = parseInt(card.attr('data-date')) || 0;
            const cardStreamer = String(card.attr('data-streamer') || '').toLowerCase();
            const cardTags = getCardTags(card);
            
            let visible = true;
            
            if (search && searchData.indexOf(search) === -1) {
                visible = false;
            }
            
            if (classFilter && !(cardClasses.includes(classFilter) || cardClasses.includes('all'))) {
                visible = false;
            }
            
            if (modeFilter && !(cardModes.includes(modeFilter) || cardModes.includes('all'))) {
                visible = false;
            }
            
            if (periodDays > 0 && (!cardDate || cardDate < minTimestamp)) {
                visible = false;
            }

            if (maxDust > 0 && cardDust > maxDust) {
                visible = false;
            }

            if (minGames > 0 && cardGames < minGames) {
                visible = false;
            }

            if (minWinrate > 0 && cardWinrate < minWinrate) {
                visible = false;
            }

            if (streamerFilter && cardStreamer !== streamerFilter) {
                visible = false;
            }

            if (activeTags.length > 0) {
                const tagMatched = requireAllTags
                    ? activeTags.every(function(activeTag) {
                        return cardTags.includes(activeTag);
                    })
                    : activeTags.some(function(activeTag) {
                        return cardTags.includes(activeTag);
                    });
                if (!tagMatched) {
                    visible = false;
                }
            }

            if (sortBy === 'dust' && cardDust <= 0) {
                visible = false;
            }
            
            if (visible) {
                visibleCount++;
                card.addClass('deck-filtered-visible').removeClass('deck-filtered-hidden');
            } else {
                card.addClass('deck-filtered-hidden').removeClass('deck-filtered-visible');
            }
        });
        
        if (sortBy) {
            let visibleCards = allCards.filter('.deck-filtered-visible').get();
            
            visibleCards.sort(function(a, b) {
                const cardA = $(a);
                const cardB = $(b);
                
                if (sortBy === 'likes') {
                    const likesA = parseInt(cardA.attr('data-likes')) || 0;
                    const likesB = parseInt(cardB.attr('data-likes')) || 0;
                    return likesB - likesA;
                } else if (sortBy === 'dislikes') {
                    const dislikesA = parseInt(cardA.attr('data-dislikes')) || 0;
                    const dislikesB = parseInt(cardB.attr('data-dislikes')) || 0;
                    return dislikesB - dislikesA;
                } else if (sortBy === 'dust') {
                    const dustA = parseInt(cardA.attr('data-dust')) || 0;
                    const dustB = parseInt(cardB.attr('data-dust')) || 0;
                    return dustA - dustB;
                } else if (sortBy === 'games') {
                    const gamesA = parseInt(cardA.attr('data-games')) || 0;
                    const gamesB = parseInt(cardB.attr('data-games')) || 0;
                    return gamesB - gamesA;
                } else if (sortBy === 'winrate') {
                    const winrateA = parseFloat(cardA.attr('data-winrate')) || 0;
                    const winrateB = parseFloat(cardB.attr('data-winrate')) || 0;
                    return winrateB - winrateA;
                } else if (sortBy === 'date') {
                    const dateA = parseInt(cardA.attr('data-date')) || 0;
                    const dateB = parseInt(cardB.attr('data-date')) || 0;
                    return dateB - dateA;
                }
                return 0;
            });
            
            const grid = $('#decks-grid');
            const noDecksMsg = $('.no-decks-filter');
            
            if (noDecksMsg.length) {
                noDecksMsg.detach();
            }
            
            $.each(visibleCards, function(index, card) {
                grid.append(card);
            });
            
            if (noDecksMsg.length) {
                grid.append(noDecksMsg);
            }
        }
        
        $('.no-decks-filter').remove();
        if (visibleCount === 0) {
            $('#decks-grid').append('<p class="no-decks no-decks-filter">Колоды не найдены</p>');
        }
        
        updatePagination();
    }
    
    $('#deck-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            filterDecks();
        }, 300);
    });
    
    $('#filter-period, #filter-class, #filter-mode, #filter-streamer').on('change', function() {
        currentPage = 1;
        filterDecks();
    });
    
    setupVoteState(document);
    hydrateVoteCounts(document);
    
    updatePagination();
    setupAdImpressions();
    setupDeckViewTracking(document);
});
