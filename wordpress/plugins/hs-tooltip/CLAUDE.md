# HS Smart Tooltip — WordPress-плагин

Плагин для WordPress, добавляющий всплывающие подсказки (tooltip) с изображениями карт Hearthstone при наведении курсора на упоминания карт в тексте записей. Работает через предсобранный JSON-словарь русскоязычных названий (со словоформами) и шорткод `[hs_card id="..."]`.

- **Версия:** 1.0.0
- **Требования:** WordPress ≥ 5.9, PHP ≥ 8.0
- **Text Domain:** `hs-smart-tooltip` (интерфейс на русском)

## Структура

```
hs-smart-tooltip.php         # Основной файл плагина (процедурный код)
includes/auto-update.php     # Автообновление основного словаря из HearthstoneJSON
hs_dictionary.json           # Словарь карт: by_name + by_id
hs_dictionary.bak.json       # Бэкап, создаётся перед каждой автоперезаписью
assets/
  hs-tooltip.js              # Фронтенд: показ тултипа на hover
  hs-tooltip.css             # Стили тултипа и редкостей
  hs-tooltip-admin.js        # Админ: поиск карт и вставка шорткода
  hs-tooltip-admin.css       # Стили мета-боксов поиска (редкость, миниатюры, подсветка)
  hs-tooltip-tinymce.js      # TinyMCE-плагин "HS Card" (файл есть, но НЕ подключается — кнопки нет)
set_icon/                    # SVG-иконки сетов Hearthstone (37 файлов)
```

## Структура словаря `hs_dictionary.json`

```json
{
  "by_name": {
    "имя карты в нижнем регистре": {
      "img": "https://d15f34w2p8l1cc.cloudfront.net/...png",
      "rarity": "common|rare|epic|legendary",
      "name": "Отображаемое Имя",
      "set_icon": "Filename_-_SVG_logo.svg"  // опционально
    }
  },
  "by_id": { "EX1_116": { ...то же... } }
}
```

`by_name` содержит все словоформы ключом (именительный, родительный, дательный и т.д.) — по одной записи на форму. Поддерживается также legacy-формат (плоский словарь = `by_name`).

## Архитектура

### PHP (серверный рендеринг)

Код написан в процедурном стиле, все функции имеют префикс `hs_smart_tooltip_`.

**Пайплайн обработки контента:**
1. `the_content` / `get_the_content` (priority 20) → `hs_smart_tooltip_process_content` → `hs_smart_tooltip_process_html`.
2. Сейчас автоматическая замена имён **отключена** — `process_html` лишь выполняет `do_shortcode()`. Вся функция `hs_smart_tooltip_parse_and_wrap` (DOM-обход + подмена упоминаний карт) существует, но не вызывается из фильтров.
3. Пост-мета `_hs_tooltips_enabled = '0'` отключает обработку для конкретной записи.

**Словарь и индексы (hs-smart-tooltip.php:154):**
- `hs_smart_tooltip_get_dictionary()` — загрузка `hs_dictionary.json`, in-request static-кэш + `wp_cache` с инвалидацией по `mtime` файла.
- `hs_smart_tooltip_build_dictionary_index()` — собирает `first_words` (первое слово каждого ключа) и `max_words` (≤5) для быстрого токен-матчинга.
- `hs_smart_tooltip_build_search_catalog()` / `hs_smart_tooltip_get_search_catalog()` — плоский каталог для AJAX-поиска в админке: одна запись на отображаемое имя (перепечатки/VAN_/CORE_ схлопнуты через `hs_smart_tooltip_search_id_rank()`), поля `_nl` (нормализованное имя) и `_blob` (имя + форма без разделителей + id + падежные формы из `by_name`). Кэш в object cache по mtime словаря (`search_catalog_v3_<mtime>`). Сам матчинг — `hs_smart_tooltip_run_search()`: многословный AND-поиск по подстроке с ранжированием (точное имя → префикс запроса → начало слова → вхождение), токены <2 симв. отбрасываются. Аналогично для BG: `hs_smart_tooltip_get_bgs_search_catalog()`.
- `hs_smart_tooltip_normalize_confusables()` — маппит кириллические буквы-ложные-друзья в латиницу (fallback при опечатках типа лат. "c" вместо рус. "с").

**Шорткод `[hs_card id="EX1_116"]текст[/hs_card]` (hs-smart-tooltip.php:653):**
- Берёт запись из `by_id`, рендерит `<span class="hs-card-tooltip" data-image="..." data-image-raw="...">`.
- Текст между тегами = label; если пуст — используется `entry.name` или сам ID.

**Image-прокси `?hs_tooltip_img=<url>` (hs-smart-tooltip.php:548):**
- Проксирует изображения карт через сайт, чтобы обойти CORS.
- Allowlist хостов: `d15f34w2p8l1cc.cloudfront.net`, `api.hearthstonejson.com`, `art.hearthstonejson.com`, `static.wikia.nocookie.net`.
- Кэширует тело + content-type в object cache на 30 дней.

**Автообновление основного словаря (includes/auto-update.php):**
- Источник: `https://api.hearthstonejson.com/v1/latest/ruRU/cards.collectible.json`. Картинки — `https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/{id}.png`.
- Фильтрация: исключаются `set === 'BATTLEGROUNDS'` и типы кроме MINION/SPELL/WEAPON/HERO/LOCATION; HSJSON отдаёт только collectible-карты на этом эндпоинте.
- `hs_smart_tooltip_rebuild_main_dictionary()` строит только `by_id` + одно нормализованное `by_name`-ключ-имя на карту (без падежных форм). При коллизии имён выигрывает карта более высокой редкости.
- Защита: transient-lock `hs_tooltip_main_rebuild_lock` (30 мин), бэкап в `hs_dictionary.bak.json`, атомарный `tmp + rename`. Health-check: < 2000 карт или < 80% от прошлого `by_id` — отказ без перезаписи.
- Триггеры: `admin_post_hs_tooltip_main_rebuild` (кнопка) и WP-Cron `hs_tooltip_main_dict_cron` (раз в сутки, при включённом тогле). Опции в `hs_tooltip_main_autoupdate`.
- На фронте автообновление НЕ выполняется — все вызовы только из admin/cron-контекста.

**Админка:**
- `Settings → HS Smart Tooltip` — автообновление + ручная загрузка `hs_dictionary.json` (форма + nonce + 50 последних логов в `hs_tooltip_logs`).
- Мета-бокс "HS Smart Tooltip" — чекбокс включения подсказок на записи.
- Мета-бокс "HS Card Search" — поиск карт + вставка шорткода (через `hs-tooltip-admin.js`).
- AJAX `wp_ajax_hs_tooltip_search` / `wp_ajax_hs_tooltip_search_bgs` — ранжированный многословный поиск по каталогу (`hs_smart_tooltip_run_search`), до `HS_TOOLTIP_SEARCH_LIMIT` (15) результатов. Поддержка частей слова/падежей, имён с апострофом (Гул'дан → «гулдан») и поиска по ID. Отдаёт `{id, name, img, rarity}` (+ `tech, type` для BG) — JS рисует миниатюру, цвет редкости и подсветку совпадений.
- `assets/hs-tooltip-tinymce.js` содержит TinyMCE-плагин «HS Card», но СЕЙЧАС не регистрируется (нет `mce_external_plugins`/`mce_buttons`) — кнопки в тулбаре нет; единственный путь вставки — мета-бокс поиска. JS мета-бокса (`hs-tooltip-admin.js`): навигация ↑↓, Enter/двойной клик — вставка, защита от гонки запросов, вставка в TinyMCE или в textarea `#content`.

**Интеграции с оптимизаторами:**
- `script_loader_tag` добавляет `defer` к `hs-smart-tooltip` (если не уже отложено).
- Исключения из Delay JS: `rocket_delay_js_exclusions`, `perfmatters_delay_js_exclusions`.
- `hs_smart_tooltip_fallback_loader` на `wp_head` priority 99 — подгружает JS для тем, стрипающих `wp_footer`.

### JS (фронт)

`assets/hs-tooltip.js` — IIFE, защищён флагами `HS_TOOLTIP_INITIALIZED` / `HS_TOOLTIP_LISTENERS_ATTACHED`:
- Делегированные `mouseover` / `mousemove` / `mouseout` на `document.body`.
- Тултип-элемент создаётся один раз, позиция обновляется через `requestAnimationFrame`.
- `data-image` — проксированный URL (основной), `data-image-raw` — прямой (fallback при ошибке загрузки).
- Прелоад картинок при первом hover (`preloadedUrls`), скрытие с задержкой 80ms.
- Debug: `window.HS_TOOLTIP_DEBUG = true` включает логи и яркий outline тултипа.

### Стили

- Класс `.hs-card-tooltip` — inline-flex, nowrap, пунктирное подчёркивание.
- Редкости: `.hs-rarity-{common,rare,epic,legendary}` с палитрой Hearthstone. Common имеет тёмный fallback для `.td-post-content` / `.entry-content` / `.td-container` (тема Newspaper).
- `.hs-tooltip-box` — `position: fixed`, `z-index: 99999`, `contain: layout style paint`, respect `prefers-reduced-motion`.

## Контекст для правок

- **Безопасность:** все выходы экранируются (`esc_url`, `esc_attr`, `esc_html`, `htmlspecialchars`); nonce на всех формах/AJAX; capability-чеки (`manage_options`, `edit_posts`).
- **Исключённые контейнеры** при DOM-обходе (hs-smart-tooltip.php:343, 474): `.hs-single-deck-container`, `.deck-card`, `.deck-header`, `.deck-meta`, `.deck-meta-secondary`, `.deck-image`, `.deck-actions` — не трогать текст внутри другого плагина колод.
- **Кэши:** группа `hs_smart_tooltip` (словарь + прокси-картинки), группа `hs_smart_tooltip_html` объявлена, но не используется. Постовый transient `hs_tooltip_post_{id}` очищается на `save_post`.
- **Язык UI:** все строки интерфейса — русские (`__()` / `_e()` с доменом `hs-smart-tooltip`). Директория `languages/` ожидается, но в репозитории отсутствует.
- **Отсутствует .git** — плагин представлен как папка с исходниками, без истории.
- **Автозамена упоминаний в тексте сейчас не активна** — если нужно включить, `hs_smart_tooltip_process_html` должен вызывать `hs_smart_tooltip_parse_and_wrap($content, hs_smart_tooltip_get_dictionary())` вместо (или до) `do_shortcode()`.
