# Выбрать адаптер сайта

Это разные WordPress-проекты. Указанные контракты проверены в исходниках 19 октября 2026 года; перед записью сверить текущую версию только соответствующего плагина. Не использовать старые ID статей, batch или attachment из предыдущей задачи.

## hs-manacost.ru

- Source: `/srv/projects/wordpress/hs-manacost.ru`; сначала `hs-manacost-project`.
- Активный плагин: `wordpress/plugins/wp-manacost-decks_old`, имя **Manacost: Decks**.
- Тип записи: `hs_deck`; код: `_deck_code`; изображение: штатный `_thumbnail_id` через `set_post_thumbnail`.
- Исключение из основной ленты: `_hide_from_feed = "1"`.
- Исключение из случайных подборок: `_exclude_from_random = "1"`.
- `_hs_deck_archived` должен быть `"0"` или отсутствовать: архив скрывает одиночный шорткод от читателей.
- Одиночное встраивание: `[hs_deck id="POST_ID"]`.
- Источник колоды: `_deck_source_url`; автор/источник в карточке: `_deck_streamer`; пыль: `_dust_cost`. Не записывать придуманные wins/winrate/games.
- `Unified_HS_Deckstring_Helper::assign_terms_from_code()` назначает `deck_class` и `deck_mode`. Проверить итоговые terms; не брать противоречащий коду формат из картинки/API.

Штатный `POST /wp-json/manacost/v1/decks` принимает `title` и `deck_code`, а также `status`, `publish_to_feed`, `hide_from_feed`, `exclude_from_random`, `archived`, `source_url`, `streamer`, `dust_cost`. Нужна реальная авторизация/capability импорта; nonce не заменяет права.

`dedupe_by_deck_code=true` обновляет совпавшую запись и **сохраняет прежний выбор видимости**, игнорируя новые `hide_from_feed`/`publish_to_feed`. Поэтому не использовать такую дедупликацию для превращения чужой видимой колоды в скрытую. Повторный собственный batch продолжать по сохранённым ID. Если запись создаётся прямыми WordPress APIs, сохранить те же контракты и штатные hooks; не писать SQL вместо API.

Создать запись в `draft`, установить перечисленные поля и attachment, проверить скрытие и только затем при необходимости сделать колоду `publish` для штатного читательского встраивания. Статья при этом остаётся `draft`.

Канонический домен — `.ru`; `hs-manacost.com` является noindex-зеркалом, staging изолирован. Для редакторской ссылки на изображение колоды доступен `[hs_deck_link image_id="ATTACHMENT_ID" code="DECKSTRING"]Текст[/hs_deck_link]` из **Manacost: inline deck images**; метаданные attachment могут также хранить код. Это не замена карточке `[hs_deck]`, если пользователь просил полные карточки.

## kolodahearthstone.com

- Source: `/srv/projects/wordpress/kolodahearthstone.com`; сначала `kolodahearthstone-project`.
- Активный плагин: `wordpress/plugins/wp-kolodahearthstone-decks/kolodahearthstone-decks.php`, тема Blocksy.
- Тип записи: `hs_deck`; код: **`_hs_deck_code`**, а не `_deck_code`.
- Изображение: штатный `_thumbnail_id`; встраивание: `[hs_deck id="POST_ID"]`.
- Одиночный шорткод уже выводит собственный заголовок: учитывать его при добавлении внешнего заголовка в статье.
- Есть `[hs_deck_link image_id="ATTACHMENT_ID" code="DECKSTRING"]Текст[/hs_deck_link]`; реализация находится в `includes/class-hs-inline-deck-link.php` этого плагина.

Не переносить endpoint `/manacost/v1/decks`, таксономии и служебные meta Manacost без проверки: совпадение названия шорткода не означает совпадение модели данных.

В проверенной версии Koloda-плагина список `[hs_decks]` выбирает опубликованные `hs_deck`, а эквивалент `_hide_from_feed` не установлен. **Не выдавать запись произвольного meta за реальное скрытие.** Сначала проверить, появился ли поддерживаемый механизм исключения в текущей версии и в проектных расширениях. До этого не публиковать записи в общую ленту вопреки запросу. Можно подготовить изображения и статью-черновик; для полной карточки вне ленты нужен подтверждённый адаптер, а для ссылки на картинку — зарегистрированный inline-шорткод. Подмену полной карточки другим вариантом согласовать, если она меняет запрос пользователя. Не полагаться на случайную доступность чужих черновиков через shortcode как на устойчивый публичный контракт.

Канонический индексируемый сайт — `kolodahearthstone.com`; `.ru` — legacy redirect. Здесь нельзя применять политику noindex-зеркала Manacost к каноническому `.com`. Staging — `test.kolodahearthstone.com`, изолирован и noindex.
