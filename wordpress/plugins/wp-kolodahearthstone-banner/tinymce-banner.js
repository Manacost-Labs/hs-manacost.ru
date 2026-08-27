(function() {
    tinymce.PluginManager.add('mtp_banner', function(editor, url) {
        editor.addButton('mtp_banner', {
            title: 'Вставить баннер',
            text: 'Баннер',
            icon: false,
            cmd: 'mtp_insert_banner',
            onclick: function() {
                editor.execCommand('mtp_insert_banner');
            }
        });
        
        editor.addCommand('mtp_insert_banner', function() {
            var selectedContent = editor.selection.getContent();
            var selectedText = editor.selection.getContent({format: 'text'});
            
            editor.windowManager.open({
                title: 'Вставить баннер',
                body: [
                    {
                        type: 'textbox',
                        name: 'title',
                        label: 'Заголовок баннера',
                        value: 'Баннер',
                        placeholder: 'Введите заголовок'
                    },
                    {
                        type: 'listbox',
                        name: 'color',
                        label: 'Цвет баннера',
                        values: [
                            {text: 'По умолчанию (бежевый)', value: 'default'},
                            {text: 'Синий', value: 'blue'},
                            {text: 'Зеленый', value: 'green'},
                            {text: 'Красный', value: 'red'},
                            {text: 'Фиолетовый', value: 'purple'},
                            {text: 'Оранжевый', value: 'orange'},
                            {text: 'Желтый', value: 'yellow'},
                            {text: 'Коричневый', value: 'brown'}
                        ],
                        value: 'default'
                    },
                    {
                        type: 'textbox',
                        name: 'url',
                        label: 'Ссылка (необязательно)',
                        value: '',
                        placeholder: 'https://example.com'
                    },
                    {
                        type: 'container',
                        html: '<p style="margin:10px 0;color:#666;font-size:12px;">Выделите текст, который нужно поместить в баннер, или оставьте пустым для вставки шаблона.</p>'
                    }
                ],
                onsubmit: function(e) {
                    var content = selectedContent || selectedText || 'Ваш текст здесь';
                    
                    var shortcode = '[banner title="' + e.data.title.replace(/"/g, '&quot;') + '" color="' + e.data.color + '"';
                    
                    if (e.data.url && e.data.url.trim()) {
                        shortcode += ' url="' + e.data.url.replace(/"/g, '&quot;') + '"';
                    }
                    
                    shortcode += ']' + content + '[/banner]';
                    
                    editor.insertContent(shortcode);
                }
            });
        });
    });
})();

