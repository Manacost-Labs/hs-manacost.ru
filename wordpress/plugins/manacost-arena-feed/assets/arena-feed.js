(function($) {
    'use strict';

    function getConfig() {
        return window.manacostArenaFeed || {};
    }

    function setStatus(feed, message, isError) {
        var status = feed.find('.maf-feed-status');
        status.text(message || '');
        status.toggleClass('is-error', !!isError);
    }

    function serializeFilters(feed) {
        var form = feed.find('[data-maf-filters]');
        var data = form.length ? form.serializeArray() : [];
        var payload = {};

        data.forEach(function(item) {
            payload[item.name] = item.value;
        });

        payload.per_page = feed.data('per-page') || payload.per_page || 12;
        return payload;
    }

    function updateUrlFromFilters(feed) {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        var form = feed.find('[data-maf-filters]');
        if (!form.length) {
            return;
        }

        var params = new URLSearchParams(window.location.search);
        form.serializeArray().forEach(function(item) {
            if (item.name === 'per_page') {
                return;
            }
            if (item.value) {
                params.set(item.name, item.value);
            } else {
                params.delete(item.name);
            }
        });

        var query = params.toString();
        var nextUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
        window.history.replaceState({}, '', nextUrl);
    }

    function loadFeed(feed) {
        var config = getConfig();
        if (!config.ajaxurl) {
            return;
        }

        var payload = serializeFilters(feed);
        payload.action = config.filterAction || 'manacost_arena_filter';
        payload.nonce = config.nonce;

        var existingRequest = feed.data('mafRequest');
        if (existingRequest && existingRequest.readyState !== 4) {
            existingRequest.abort();
        }

        feed.addClass('is-loading');
        setStatus(feed, (config.texts && config.texts.loading) || 'Загрузка...', false);

        var request = $.ajax({
            url: config.ajaxurl,
            method: 'POST',
            data: payload
        }).done(function(response) {
            if (!response || !response.success || !response.data) {
                setStatus(feed, (config.texts && config.texts.error) || 'Ошибка', true);
                return;
            }

            feed.find('[data-maf-grid]').html(response.data.html || '');
            feed.find('[data-maf-pagination]').html(response.data.pagination || '');
            setStatus(feed, '', false);
            updateUrlFromFilters(feed);
            scheduleWarmFirstLightboxImage(feed);
        }).fail(function(xhr, status) {
            if (status === 'abort') {
                return;
            }
            setStatus(feed, (config.texts && config.texts.error) || 'Ошибка', true);
        }).always(function() {
            if (feed.data('mafRequest') === request) {
                feed.removeData('mafRequest');
                feed.removeClass('is-loading');
            }
        });

        feed.data('mafRequest', request);
    }

    function updateStars(card, userRating, avg, count, text) {
        var filled = userRating || Math.round(avg || 0);
        card.attr('data-rating', avg || 0);
        card.attr('data-rating-count', count || 0);
        card.attr('data-user-rating', userRating || 0);
        card.find('.maf-star').each(function() {
            var button = $(this);
            var rating = parseInt(button.data('rating'), 10) || 0;
            button.toggleClass('is-filled', rating <= filled);
            button.toggleClass('is-user', rating === userRating);
            button.attr('aria-checked', rating === userRating ? 'true' : 'false');
        });
        card.find('[data-maf-rating-text]').text(text || 'Нет оценок');
    }

    var lightboxPreloadCache = {};

    function preloadLightboxImage(url, priority) {
        if (!url || lightboxPreloadCache[url]) {
            if (lightboxPreloadCache[url] && priority && 'fetchPriority' in lightboxPreloadCache[url]) {
                lightboxPreloadCache[url].fetchPriority = priority;
            }
            return;
        }

        var image = new Image();
        image.decoding = 'async';
        if ('fetchPriority' in image) {
            image.fetchPriority = priority || 'low';
        }
        image.src = url;
        lightboxPreloadCache[url] = image;
    }

    function getLightboxFullUrl(link) {
        return link.data('mafFull') || link.attr('href') || '';
    }

    function revealFullLightboxImage(lightbox, url, title, fallbackUrl) {
        var fullImage = lightbox.find('.maf-lightbox-full');
        var reveal = function() {
            if (lightbox.data('mafFullUrl') !== url || !lightbox.hasClass('is-open')) {
                return;
            }

            var node = fullImage[0];
            var finish = function() {
                window.requestAnimationFrame(function() {
                    lightbox.removeClass('is-loading').addClass('has-full');
                });
            };

            if (node && node.decode) {
                node.decode().catch(function() {}).then(finish);
                return;
            }

            finish();
        };

        fullImage
            .off('.mafFull')
            .one('load.mafFull', reveal)
            .one('error.mafFull', function() {
                if (lightbox.data('mafFullUrl') === url) {
                    if (fallbackUrl && fallbackUrl !== url && !lightbox.data('mafTriedFallback')) {
                        lightbox
                            .data('mafTriedFallback', true)
                            .data('mafFullUrl', fallbackUrl);
                        revealFullLightboxImage(lightbox, fallbackUrl, title, '');
                        return;
                    }
                    lightbox.removeClass('is-loading');
                }
            })
            .attr({
                src: url,
                alt: title || ''
            });

        if (fullImage[0] && fullImage[0].complete && fullImage[0].naturalWidth) {
            reveal();
        }
    }

    function canWarmLightboxImage(url) {
        var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection && (connection.saveData || /2g/.test(connection.effectiveType || ''))) {
            return false;
        }

        return /\.webp(?:$|\?)/i.test(url || '');
    }

    function scheduleWarmFirstLightboxImage(feed) {
        var link = feed.find('[data-maf-lightbox]').first();
        var url = link.length ? getLightboxFullUrl(link) : '';
        if (!url || !canWarmLightboxImage(url) || feed.data('mafWarmUrl') === url) {
            return;
        }

        feed.data('mafWarmUrl', url);
        var warm = function() {
            preloadLightboxImage(url, 'low');
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(warm, { timeout: 2500 });
            return;
        }

        window.setTimeout(warm, 1400);
    }

    function ensureLightbox() {
        var lightbox = $('.maf-lightbox');
        if (lightbox.length) {
            return lightbox;
        }

        lightbox = $(
            '<div class="maf-lightbox" aria-hidden="true">' +
                '<button type="button" class="maf-lightbox-close" aria-label="Закрыть">×</button>' +
                '<figure class="maf-lightbox-figure">' +
                    '<div class="maf-lightbox-stage">' +
                        '<img class="maf-lightbox-image maf-lightbox-preview" alt="">' +
                        '<img class="maf-lightbox-image maf-lightbox-full" alt="">' +
                    '</div>' +
                    '<figcaption class="maf-lightbox-caption"></figcaption>' +
                '</figure>' +
            '</div>'
        );

        $('body').append(lightbox);
        return lightbox;
    }

    function openLightbox(url, title, previewUrl, fallbackUrl) {
        var lightbox = ensureLightbox();
        var previewImage = lightbox.find('.maf-lightbox-preview');
        var fullImage = lightbox.find('.maf-lightbox-full');

        fullImage.off('.mafFull').removeAttr('src').attr('alt', title || '');
        previewImage.attr({
            src: previewUrl || url,
            alt: title || ''
        });
        lightbox.find('.maf-lightbox-caption').text(title || '');
        lightbox
            .data('mafFullUrl', url)
            .removeData('mafTriedFallback')
            .attr('aria-hidden', 'false')
            .removeClass('has-full')
            .addClass('is-open is-loading');
        $('body').addClass('maf-lightbox-open');

        revealFullLightboxImage(lightbox, url, title, fallbackUrl);
    }

    function closeLightbox() {
        var lightbox = $('.maf-lightbox');
        if (!lightbox.length) {
            return;
        }

        lightbox
            .attr('aria-hidden', 'true')
            .removeClass('is-open is-loading has-full')
            .removeData('mafFullUrl')
            .removeData('mafTriedFallback');
        lightbox.find('.maf-lightbox-image').off('.mafFull').removeAttr('src');
        $('body').removeClass('maf-lightbox-open');
    }

    $(document).on('submit', '.maf-filters', function(event) {
        event.preventDefault();
        var form = $(this);
        form.find('[data-maf-page]').val('1');
        loadFeed(form.closest('.maf-arena-feed'));
    });

    $(document).on('change input', '.maf-filters select, .maf-filters input[type="search"]', function() {
        $(this).closest('.maf-filters').find('[data-maf-page]').val('1');
    });

    $(document).on('click', '[data-maf-reset]', function() {
        var form = $(this).closest('.maf-filters');
        form.find('input[type="search"]').val('');
        form.find('select').val('');
        form.find('select[name="arena_period"]').val('0');
        form.find('select[name="arena_sort"]').val('date');
        form.find('[data-maf-page]').val('1');
        loadFeed(form.closest('.maf-arena-feed'));
    });

    $(document).on('click', '.maf-page-btn', function() {
        var button = $(this);
        if (button.prop('disabled')) {
            return;
        }

        var feed = button.closest('.maf-arena-feed');
        var page = parseInt(button.data('page'), 10) || 1;
        feed.find('[data-maf-page]').val(page);
        loadFeed(feed);
    });

    $(document).on('click', '[data-maf-lightbox]', function(event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.which === 2) {
            return;
        }

        event.preventDefault();
        var link = $(this);
        openLightbox(
            getLightboxFullUrl(link),
            link.data('title') || link.find('img').attr('alt') || '',
            link.find('img').attr('currentSrc') || link.find('img').attr('src') || '',
            link.attr('href') || ''
        );
    });

    $(document).on('mouseenter focus pointerdown touchstart', '[data-maf-lightbox]', function() {
        preloadLightboxImage(getLightboxFullUrl($(this)), 'high');
    });

    $(function() {
        $('.maf-arena-feed').each(function() {
            scheduleWarmFirstLightboxImage($(this));
        });
    });

    $(document).on('click', '.maf-lightbox', function(event) {
        if ($(event.target).is('.maf-lightbox, .maf-lightbox-close')) {
            closeLightbox();
        }
    });

    $(document).on('keyup', function(event) {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });

    $(document).on('click', '.maf-star', function() {
        var button = $(this);
        var card = button.closest('[data-maf-card]');
        var config = getConfig();
        var rating = parseInt(button.data('rating'), 10) || 0;
        var hash = card.data('hash');

        if (!config.ajaxurl || !hash || rating < 1 || rating > 5 || card.hasClass('is-rating')) {
            return;
        }

        card.addClass('is-rating');

        $.ajax({
            url: config.ajaxurl,
            method: 'POST',
            data: {
                action: config.rateAction || 'manacost_arena_rate',
                nonce: config.nonce,
                hash: hash,
                rating: rating
            }
        }).done(function(response) {
            if (!response || !response.success || !response.data) {
                return;
            }
            updateStars(
                card,
                parseInt(response.data.user_rating, 10) || rating,
                parseFloat(response.data.rating_avg) || 0,
                parseInt(response.data.rating_count, 10) || 0,
                response.data.rating_text || ''
            );
        }).always(function() {
            card.removeClass('is-rating');
        });
    });
})(jQuery);
