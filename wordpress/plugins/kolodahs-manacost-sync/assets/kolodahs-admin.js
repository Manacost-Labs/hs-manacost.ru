jQuery(function ($) {
    window.KolodahsDeckSync = window.KolodahsDeckSync || {};

    function setBusy($button, busyText) {
        $button.data('default-text', $button.data('default-text') || $button.text());
        $button.prop('disabled', true).text(busyText);
    }

    function clearBusy($button) {
        $button.prop('disabled', false).text($button.data('default-text') || $button.text());
    }

    function setStatusError($status, xhr, fallback) {
        var message = fallback || 'Ошибка запроса к WordPress AJAX.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            message = xhr.responseJSON.data.message;
        } else if (xhr && xhr.status) {
            message += ' HTTP ' + xhr.status + '.';
        }
        $status.css('color', '#b32d2e').text(message);
    }

    function refreshNonces() {
        return $.ajax({
            url: window.KolodahsDeckSync.ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kolodahs_api_refresh_nonces'
            }
        }).done(function (response) {
            var data = response && response.data ? response.data : {};
            if (response && response.success) {
                if (data.nonce) {
                    window.KolodahsDeckSync.nonce = data.nonce;
                }
                if (data.shortcodeNonce) {
                    window.KolodahsDeckSync.shortcodeNonce = data.shortcodeNonce;
                }
            }
        });
    }

    function normalizeDeckCode(value) {
        return $.trim(value || '').replace(/\s+/g, '');
    }

    function preview($target, data) {
        $target.empty();
        if (data && data.thumbnail_url) {
            $('<img>', { src: bustUrl(data.thumbnail_url), alt: data.title || 'Generated deck image' }).appendTo($target);
        }
    }

    function bustUrl(url) {
        if (!url) {
            return '';
        }
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'kolodahs_preview=' + Date.now();
    }

    function refreshClassicFeaturedBox(data) {
        var attachmentId = parseInt(data && data.attachment_id, 10);
        if (!attachmentId) {
            return;
        }

        if (typeof window.WPSetThumbnailID === 'function') {
            window.WPSetThumbnailID(attachmentId);
        }
        $('#_thumbnail_id').val(attachmentId).trigger('change');

        if (data.featured_image_html) {
            if (typeof window.WPSetThumbnailHTML === 'function') {
                window.WPSetThumbnailHTML(data.featured_image_html);
            } else {
                $('#postimagediv .inside').html(data.featured_image_html);
            }
        } else {
            var imageUrl = data.thumbnail_url || data.image_url || '';
            if (imageUrl && $('#postimagediv .inside').length) {
                $('#postimagediv .inside').html(
                    '<p class="hide-if-no-js"><a href="#" id="set-post-thumbnail" aria-describedby="set-post-thumbnail-desc">' +
                    '<img src="' + $('<div>').text(bustUrl(imageUrl)).html() + '" alt=""></a></p>' +
                    '<p class="hide-if-no-js howto" id="set-post-thumbnail-desc">Изображение обновлено через Kolodahs.</p>' +
                    '<p class="hide-if-no-js"><a href="#" id="remove-post-thumbnail">Удалить изображение записи</a></p>'
                );
            }
        }

        $('#postimagediv img').each(function () {
            var $img = $(this);
            var src = $img.attr('src');
            if (src) {
                $img.removeAttr('srcset sizes').attr('src', bustUrl(src));
            }
        });
    }

    function refreshBlockFeaturedImage(data) {
        var attachmentId = parseInt(data && data.attachment_id, 10);
        if (!attachmentId || !window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
            return;
        }
        try {
            wp.data.dispatch('core/editor').editPost({ featured_media: attachmentId });
        } catch (error) {}
    }

    function refreshFeaturedImage(data) {
        refreshClassicFeaturedBox(data || {});
        refreshBlockFeaturedImage(data || {});
    }

    window.KolodahsDeckSync.openShortcodeModal = function () {
        $('#kolodahs-shortcode-code').val('');
        $('#kolodahs-shortcode-status').text('').css('color', '');
        $('#kolodahs-shortcode-preview').empty();
        tb_show('Вставить колоду Hearthstone', '#TB_inline?width=640&height=520&inlineId=kolodahs-shortcode-modal');
        setTimeout(function () { $('#kolodahs-shortcode-code').trigger('focus'); }, 100);
    };

    function createShortcode($button, $status, $preview, deckCode, retried) {
        $status.css('color', '#646970').text('Определяю архетип, создаю колоду и картинку...');
        $.ajax({
            url: window.KolodahsDeckSync.ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kolodahs_api_create_shortcode',
                nonce: window.KolodahsDeckSync.shortcodeNonce,
                deck_code: deckCode
            }
        }).done(function (response) {
            var data = response && response.data ? response.data : {};
            if (!response || !response.success) {
                $status.css('color', '#b32d2e').text(data.message || 'Не удалось создать колоду.');
                return;
            }
            preview($preview, data);
            if (window.tinymce && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
                tinymce.activeEditor.execCommand('mceInsertContent', false, data.shortcode);
            } else if (typeof window.send_to_editor === 'function') {
                window.send_to_editor(data.shortcode);
            }
            $status.css('color', '#008a20').text((data.title ? data.title + ': ' : '') + (data.message || 'Шорткод вставлен.'));
            setTimeout(function () { tb_remove(); }, 700);
        }).fail(function (xhr) {
            if (xhr && xhr.status === 403 && !retried) {
                $status.css('color', '#646970').text('Обновляю сессию редактора и повторяю...');
                refreshNonces().done(function () {
                    createShortcode($button, $status, $preview, deckCode, true);
                }).fail(function (nonceXhr) {
                    setStatusError($status, nonceXhr, 'Не удалось обновить сессию редактора.');
                    clearBusy($button);
                });
                return;
            }
            setStatusError($status, xhr, 'Ошибка запроса к WordPress AJAX.');
        }).always(function () {
            if (retried || !$status.text().match(/повторяю/i)) {
                clearBusy($button);
            }
        });
    }

    $('#kolodahs-shortcode-insert').on('click', function (event) {
        event.preventDefault();
        var $button = $(this);
        var $status = $('#kolodahs-shortcode-status');
        var $preview = $('#kolodahs-shortcode-preview');
        var deckCode = normalizeDeckCode($('#kolodahs-shortcode-code').val());
        if (!deckCode) {
            $status.css('color', '#b32d2e').text('Вставьте код колоды.');
            return;
        }
        setBusy($button, 'Создаю...');
        createShortcode($button, $status, $preview, deckCode, false);
    });

    var $code = $('#deck_code');
    if (!$code.length || $('#kolodahs_api_sync_now').length || $('#hs_generate_kolodahs_image').length) {
        return;
    }

    var $row = $('<div class="kolodahs-code-action-row"></div>');
    var $panel = $('<div class="kolodahs-code-action-panel"></div>');
    var $button = $('<button type="button" class="button button-secondary" id="kolodahs_api_sync_now">Сделать картинку и пыль</button>');
    var $status = $('<span class="kolodahs-code-action-status" aria-live="polite"></span>');
    var $preview = $('<div class="kolodahs-code-action-preview"></div>');

    $code.before($row);
    $row.append($code);
    $panel.append($button, $status, $preview);
    $row.append($panel);

    $button.on('click', function (event) {
        event.preventDefault();
        var postId = parseInt($('#post_ID').val(), 10);
        var deckCode = normalizeDeckCode($code.val());
        if (!deckCode) {
            $status.css('color', '#b32d2e').text('Сначала вставьте код колоды.');
            return;
        }
        if (!postId) {
            $status.css('color', '#b32d2e').text('Сначала сохраните черновик колоды.');
            return;
        }
        setBusy($button, 'Генерирую...');
        $status.css('color', '#646970').text('Отправляю код в Kolodahs API...');
        $preview.empty();
        function syncNow(retried) {
            $.ajax({
            url: window.KolodahsDeckSync.ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kolodahs_api_sync_now',
                nonce: window.KolodahsDeckSync.nonce,
                post_id: postId,
                deck_code: deckCode
            }
        }).done(function (response) {
            var data = response && response.data ? response.data : {};
            if (!response || !response.success) {
                $status.css('color', '#b32d2e').text(data.message || 'Не удалось создать картинку.');
                return;
            }
            if (typeof data.dust !== 'undefined' && data.dust !== null && data.dust !== '') {
                $('#dust_cost').val(data.dust);
            }
            if (data.title) {
                $('#title').val(data.title);
                $('#title-prompt-text').addClass('screen-reader-text');
            }
            preview($preview, data);
            refreshFeaturedImage(data);
            $status.css('color', '#008a20').text(data.message || 'Готово: пыль и картинка обновлены.');
        }).fail(function (xhr) {
            if (xhr && xhr.status === 403 && !retried) {
                $status.css('color', '#646970').text('Обновляю сессию редактора и повторяю...');
                refreshNonces().done(function () {
                    syncNow(true);
                }).fail(function (nonceXhr) {
                    setStatusError($status, nonceXhr, 'Не удалось обновить сессию редактора.');
                    clearBusy($button);
                });
                return;
            }
            setStatusError($status, xhr, 'Ошибка запроса к WordPress AJAX.');
        }).always(function () {
            if (retried || !$status.text().match(/повторяю/i)) {
                clearBusy($button);
            }
        });
        }

        syncNow(false);
    });
});
