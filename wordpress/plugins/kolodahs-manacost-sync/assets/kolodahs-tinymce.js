(function () {
    tinymce.PluginManager.add('kolodahs_deck_shortcode', function (editor) {
        editor.addButton('kolodahs_deck_shortcode', {
            text: 'HS Deck',
            tooltip: 'Вставить колоду Hearthstone',
            onclick: function () {
                if (window.KolodahsDeckSync && typeof window.KolodahsDeckSync.openShortcodeModal === 'function') {
                    window.KolodahsDeckSync.openShortcodeModal();
                }
            }
        });
    });
})();
