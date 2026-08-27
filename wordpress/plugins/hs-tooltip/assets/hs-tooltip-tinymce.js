/* global tinymce */
(function () {
  'use strict';

  if (!window.tinymce) {
    return;
  }

  tinymce.PluginManager.add('hs_smart_tooltip', function (editor) {
    editor.addButton('hs_smart_tooltip', {
      text: 'HS Card',
      icon: false,
      tooltip: 'Insert Hearthstone card by ID',
      onclick: function () {
        var cardId = window.prompt('Card ID (например: EX1_116)');
        if (!cardId) {
          return;
        }
        var label = window.prompt('Текст (необязательно)');
        var shortcode = '[hs_card id="' + cardId + '"]';
        if (label) {
          shortcode += label + '[/hs_card]';
        }
        editor.insertContent(shortcode);
      }
    });
  });
})();
