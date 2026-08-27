(function (root, factory) {
  'use strict';

  var api = factory();
  if (typeof module === 'object' && module.exports) {
    module.exports = api;
  } else {
    root.HSTooltipSearch = api;
  }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  'use strict';

  var confusables = {
    'а': 'a', 'е': 'e', 'о': 'o', 'р': 'p', 'с': 'c', 'х': 'x', 'у': 'y',
    'к': 'k', 'м': 'm', 'т': 't', 'н': 'h', 'в': 'b', 'і': 'i', 'ї': 'i',
    'ё': 'e', 'й': 'i'
  };

  function normalize(value) {
    return String(value || '')
      .toLocaleLowerCase('ru-RU')
      .replace(/[аеорсхукмтнвіїёй]/g, function (letter) {
        return confusables[letter] || letter;
      })
      .replace(/[^\p{L}\p{N}]+/gu, ' ')
      .trim();
  }

  function tokens(term) {
    var norm = normalize(term);
    return {
      norm: norm,
      tokens: norm.split(/\s+/u).filter(function (token) {
        return Array.from(token).length >= 2;
      })
    };
  }

  function buildBgCatalog(dictionary) {
    var byId = {};
    if (Array.isArray(dictionary)) {
      dictionary.forEach(function (entry) {
        if (entry && entry.id) {
          byId[entry.id] = {
            name: entry.name,
            techLevel: entry.tech,
            type: entry.type,
            img: entry.img,
            rarity: entry.rarity
          };
        }
      });
    } else if (dictionary && dictionary.by_id && typeof dictionary.by_id === 'object') {
      byId = dictionary.by_id;
    }
    var best = Object.create(null);

    Object.keys(byId).forEach(function (id) {
      var entry = byId[id];
      if (!id || !entry || typeof entry !== 'object') {
        return;
      }
      var name = typeof entry.name === 'string' ? entry.name : '';
      if (!name) {
        return;
      }
      var tech = Number.parseInt(entry.techLevel, 10) || 0;
      var type = typeof entry.type === 'string' ? entry.type : '';
      var key = name.toLocaleLowerCase('ru-RU') + '|' + tech + '|' + type;
      if (best[key] && Array.from(id).length >= Array.from(best[key].id).length) {
        return;
      }
      best[key] = {
        id: id,
        name: name,
        img: typeof entry.img === 'string' ? entry.img : '',
        rarity: typeof entry.rarity === 'string' ? entry.rarity : 'common',
        tech: tech,
        type: type
      };
    });

    return Object.keys(best).map(function (key) {
      var card = best[key];
      var normalizedName = normalize(card.name);
      card._nl = normalizedName;
      card._blob = [normalizedName, normalizedName.replace(/ /g, ''), normalize(card.id)]
        .filter(Boolean)
        .join(' ');
      return card;
    });
  }

  function allWordStarts(blob, queryTokens) {
    return queryTokens.every(function (token) {
      var pos = blob.indexOf(token);
      while (pos !== -1) {
        if (pos === 0 || blob.charAt(pos - 1) === ' ') {
          return true;
        }
        pos = blob.indexOf(token, pos + 1);
      }
      return false;
    });
  }

  function search(catalog, term, limit) {
    var parsed = tokens(term);
    var norm = parsed.norm;
    var queryTokens = parsed.tokens;
    var maxResults = Number.parseInt(limit, 10) || 15;
    if (!norm || Array.from(norm).length < 2 || !queryTokens.length) {
      return [];
    }

    var scored = [];
    catalog.forEach(function (entry) {
      var blob = entry._blob || '';
      if (!blob || !queryTokens.every(function (token) { return blob.indexOf(token) !== -1; })) {
        return;
      }
      var normalizedName = entry._nl || '';
      var score = normalizedName === norm ? 0 :
        normalizedName.indexOf(norm) === 0 ? 1 :
          allWordStarts(blob, queryTokens) ? 2 : 3;
      scored.push({
        score: score,
        length: Array.from(entry.name || '').length,
        name: entry.name || '',
        entry: entry
      });
    });

    scored.sort(function (a, b) {
      return a.score - b.score || a.length - b.length || a.name.localeCompare(b.name, 'ru-RU');
    });
    return scored.slice(0, maxResults).map(function (row) { return row.entry; });
  }

  return {
    normalize: normalize,
    buildBgCatalog: buildBgCatalog,
    search: search
  };
});
