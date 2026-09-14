(function () {
  "use strict";

  if (!window.tinymce || !window.tinymce.PluginManager) {
    return;
  }

  window.tinymce.PluginManager.add("manacost_rsya", function (editor) {
    var insert = function (format) {
      editor.insertContent('[manacost_rsya format="' + format + '"]');
    };
    var menu = [
      {
        text: "Баннер РСЯ",
        onAction: function () {
          insert("banner");
        },
        onclick: function () {
          insert("banner");
        },
      },
      {
        text: "Лента РСЯ",
        onAction: function () {
          insert("feed");
        },
        onclick: function () {
          insert("feed");
        },
      },
    ];

    if (editor.ui && editor.ui.registry && editor.ui.registry.addMenuButton) {
      editor.ui.registry.addMenuButton("manacost_rsya", {
        text: "Реклама",
        tooltip: "Вставить рекламный блок РСЯ",
        fetch: function (callback) {
          callback(menu);
        },
      });
      return;
    }

    if (editor.addButton) {
      editor.addButton("manacost_rsya", {
        title: "Вставить рекламный блок РСЯ",
        text: "Реклама",
        type: "menubutton",
        menu: menu,
      });
    }
  });
})();
