(function () {
    'use strict';

    if (!window.tinymce || !tinymce.PluginManager) {
        return;
    }

    tinymce.PluginManager.add('hs_manacost_inline_deck', function (editor) {
        if (!editor || editor.id !== 'content') {
            return;
        }

        editor.addButton('hs_manacost_inline_deck', {
            title: 'Привязать изображение колоды',
            icon: 'image',
            onclick: createInlineDeck
        });

        function createInlineDeck() {
            var selectedHtml = editor.selection.getContent({ format: 'html' });
            var selectedText = editor.selection.getContent({ format: 'text' }).trim();

            if (!selectedText) {
                editor.windowManager.alert('Сначала выделите слово или фразу.');
                return;
            }

            var bookmark = editor.selection.getBookmark(2, true);
            var modal = null;
            var pollTimer = null;
            var pollAttempts = 0;
            var busy = false;
            var closed = false;

            function escapeHtml(value) {
                var node = document.createElement('div');
                node.textContent = value || '';
                return node.innerHTML;
            }

            function setStatus(message, isError, isBusy) {
                busy = !!isBusy;
                if (!modal || closed) {
                    return;
                }

                var root = modal.getEl();
                var status = root && root.querySelector('#hs-mi-editor-status');
                var submit = root && root.querySelector('.mce-primary');

                if (status) {
                    status.textContent = message || '';
                    status.style.color = isError ? '#b32d2e' : '#50575e';
                }
                if (submit) {
                    submit.disabled = busy;
                    submit.setAttribute('aria-busy', busy ? 'true' : 'false');
                }
            }

            function responseMessage(xhr, fallback) {
                var response = xhr && xhr.responseJSON;
                return response && response.data && response.data.message
                    ? response.data.message
                    : fallback;
            }

            function request(action, code, jobHash, done) {
                var config = window.hsManacostInlineDeck || {};

                if (!window.jQuery || !config.ajaxUrl || !config.nonce) {
                    setStatus('Системный модуль не загрузился. Обновите страницу.', true, false);
                    return;
                }

                window.jQuery.ajax({
                    url: config.ajaxUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: action,
                        nonce: config.nonce,
                        post_id: config.postId || 0,
                        code: code,
                        selected_html: selectedHtml,
                        job_hash: jobHash || ''
                    }
                }).done(function (response) {
                    if (!response || !response.success || !response.data) {
                        setStatus(
                            response && response.data && response.data.message
                                ? response.data.message
                                : 'Не удалось создать изображение.',
                            true,
                            false
                        );
                        return;
                    }
                    done(response.data);
                }).fail(function (xhr) {
                    setStatus(responseMessage(xhr, 'Сервис временно недоступен.'), true, false);
                });
            }

            function insertShortcode(data) {
                if (!data.shortcode) {
                    setStatus('Сервер не вернул готовую вставку.', true, false);
                    return;
                }

                if (pollTimer) {
                    window.clearTimeout(pollTimer);
                }

                editor.focus();
                editor.selection.moveToBookmark(bookmark);
                editor.insertContent(data.shortcode);
                editor.windowManager.close();
            }

            function handleJob(data, code) {
                var config = window.hsManacostInlineDeck || {};

                if (data.status === 'ready') {
                    insertShortcode(data);
                    return;
                }

                if (data.status !== 'pending' || !data.job_hash) {
                    setStatus(data.message || 'Не удалось создать изображение.', true, false);
                    return;
                }

                pollAttempts += 1;
                if (pollAttempts > 60) {
                    setStatus('Генерация заняла слишком много времени. Повторите через минуту.', true, false);
                    return;
                }

                setStatus('Создаём изображение и сохраняем его в медиатеку…', false, true);
                pollTimer = window.setTimeout(function () {
                    request(config.finishAction, code, data.job_hash, function (nextData) {
                        handleJob(nextData, code);
                    });
                }, 1500);
            }

            modal = editor.windowManager.open({
                title: 'Изображение колоды',
                body: [
                    {
                        type: 'container',
                        html: '<div style="box-sizing:border-box;margin:0 0 10px;padding:8px 10px;border-left:4px solid #179bd7;background:#f0f6f9;color:#1d2327"><strong>Фраза:</strong> ' +
                            escapeHtml(selectedText) + '</div>'
                    },
                    {
                        type: 'textbox',
                        name: 'code',
                        label: 'Код колоды',
                        multiline: true,
                        minHeight: 68,
                        placeholder: 'Вставьте код из Hearthstone'
                    },
                    {
                        type: 'container',
                        html: '<div id="hs-mi-editor-status" role="status" aria-live="polite" style="box-sizing:border-box;margin-top:10px;min-height:20px;color:#50575e">Картинка сохранится в медиатеке и не будет загружаться с внешнего сервера.</div>'
                    }
                ],
                width: 500,
                height: 270,
                onsubmit: function (event) {
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }
                    if (busy) {
                        return false;
                    }

                    var config = window.hsManacostInlineDeck || {};
                    var code = ((event.data && event.data.code) || '').replace(/\s+/g, '');
                    if (!code) {
                        setStatus('Вставьте код колоды.', true, false);
                        return false;
                    }

                    pollAttempts = 0;
                    setStatus('Проверяем код и запускаем генерацию…', false, true);
                    request(config.startAction, code, '', function (data) {
                        handleJob(data, code);
                    });
                    return false;
                },
                onclose: function () {
                    closed = true;
                    if (pollTimer) {
                        window.clearTimeout(pollTimer);
                    }
                }
            });
        }
    });
}());
