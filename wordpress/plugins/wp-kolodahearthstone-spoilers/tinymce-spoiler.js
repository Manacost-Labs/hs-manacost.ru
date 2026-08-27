(function() {
    tinymce.PluginManager.add('mtp_spoiler', function(editor, url) {
        editor.addButton('mtp_spoiler', {
            title: 'Вставить спойлер',
            text: 'Спойлер',
            icon: false,
            cmd: 'mtp_insert_spoiler',
            onclick: function() {
                editor.execCommand('mtp_insert_spoiler');
            }
        });
        
        editor.addCommand('mtp_insert_spoiler', function() {
            var selectedContent = editor.selection.getContent();
            var selectedText = editor.selection.getContent({format: 'text'});
            
            editor.windowManager.open({
                title: 'Вставить спойлер',
                body: [
                    {
                        type: 'textbox',
                        name: 'title',
                        label: 'Заголовок спойлера',
                        value: 'Спойлер',
                        placeholder: 'Введите заголовок'
                    },
                    {
                        type: 'listbox',
                        name: 'color',
                        label: 'Цвет спойлера',
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
                        name: 'icon',
                        label: 'Иконка (необязательно)',
                        value: '▼',
                        placeholder: '▼'
                    },
                    {
                        type: 'container',
                        html: '<p style="margin:10px 0;color:#666;font-size:12px;">Выделите текст, который нужно поместить в спойлер, или оставьте пустым для вставки шаблона.</p>'
                    }
                ],
                onsubmit: function(e) {
                    var content = selectedContent || selectedText || 'Ваш контент здесь';
                    
                    var shortcode = '[spoiler title="' + e.data.title.replace(/"/g, '&quot;') + '" color="' + e.data.color + '"';
                    if (e.data.icon && e.data.icon !== '▼') {
                        shortcode += ' icon="' + e.data.icon.replace(/"/g, '&quot;') + '"';
                    }
                    shortcode += ']' + content + '[/spoiler]';
                    
                    editor.insertContent(shortcode);
                }
            });
        });
    });
})();

