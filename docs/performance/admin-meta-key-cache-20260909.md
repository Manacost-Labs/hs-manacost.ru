# Кэш списка произвольных полей редактора

Дата: 2026-09-09 UTC. База: `68b321b783dc8c2c7e1e2eae30f4b7c8ef2c80f1`.
Ветка: `perf/wp-admin-save-20260909`.

## Статус и границы

Модуль установлен непосредственно на hs-manacost.ru **2026-09-09 22:58:51 UTC**
из commit `2446759c37c688ffe59a7f3e294a82c40454588d` по явному разрешению
пользователя. Production получил только один новый MU-файл.
Это не full-tree promotion, не перенос изменений reader и не завершение
всего плана «моментального WordPress». Предыдущая OPcache-правка сохраняется.

Новый MU-модуль: `wordpress/mu-plugins/hs-admin-meta-key-cache.php`.
SHA256: `453b9355d00a5a4c355f825f349035a18099da2c95af0037a94d78eb1de64517`.
Он кэширует только общий список имён полей для стандартного `meta_form()`.
Значения полей, HTML, права доступа, пользовательские данные и nonce не кэшируются.
Core продолжает сам сортировать, экранировать и проверять доступ к каждому полю.
Статьи, реклама, theme, PHP/Nginx settings, schema и аккаунты не изменяются.

## Измеренный компонент

В WordPress 6.9.7 `wp-admin/includes/template.php::meta_form()` получает
до 30 публичных имён полей через `SELECT DISTINCT meta_key` по postmeta.
Скрытый через `default_hidden_meta_boxes` блок `postcustom` не обязательно
исключён из серверного рендеринга. Таблица production — приблизительно
1.59 млн строк по оценке InnoDB; EXPLAIN показал index scan, temporary и filesort.

Пять последовательных production SQL-профилей: 434.351, 423.408, 439.490,
453.475, 435.092 мс; медиана 435.092 мс. Только session-local profiling,
ограничение запроса 3 секунды, без глобальных SQL-логов и вывода имён/значений.

Повтор через настоящий PHP-FPM 8.4.20 и WordPress SHORTINIT: 457.536,
437.328, 438.122, 434.393, 444.522 мс; медиана **438.122 мс**.
В каждой пробе один SQL-запрос, 30 имён, одинаковый hash результата.
Это время одного компонента, **не время открытия всей админки и не save-flow**.
Private probe находится вне web root, работает только через локальный FPM socket,
не выбирает пользователя, не создаёт сессию, не вызывает login или сохранение.

## Контракт кэша

- Используется существующий object cache, TTL 300 секунд, отдельная группа.
- Запрос на промахе совпадает с core; limit входит в cache key. Необычный limit
  за пределами 1–1000 и предыдущий plugin override оставляют core-путь без изменений.
- Добавление/удаление публичного имени и успешное переименование по meta ID
  меняют поколение кэша. Изменение только значения, включая Newspaper
  `post_views_count`, не инвалидирует список. Ошибки записи не считаются успехом.
- Поздний fill использует старое поколение. Полная межзапросная линеаризуемость
  не заявляется: object-cache имеет request-local состояние. Прямые SQL-записи,
  обходящие WordPress hooks, отражаются после TTL. Глобального flush нет.
- Ошибка чтения БД не кэшируется; при недоступном cache возвращается результат
  обычного SQL. Пользовательские значения не попадают в новую группу.

## Проверки до установки

- Регрессионный PHP fixture, запускаемый Python unittest: 19 сценариев PASS.
  До реализации были воспроизведены провалы; отдельный public-value тест
  сначала выявил лишнюю инвалидацию и прошёл после исправления.
- Настоящий WordPress: совпадение hash HTML native/cold/warm `meta_form`,
  устранение одного SQL на warm, insert/delete/rename/public/private value,
  upstream override и настоящие проверки разрешённого/запрещённого option: PASS.
  Fixture перед любыми записями требует local environment, localhost и своего
  integration-admin; создаёт и удаляет только свой draft. Сначала guard правильно
  отказал при неполной CLI environment-конфигурации; настройки исправлены только
  в одноразовом контейнере. Ошибки scope/кавычек самого fixture также исправлены.
- Канонические WordPress integration assertions и views endpoint: PASS.
- `make check`: PASS, 135 Python + 80 Node + 22 reader UI = 237 тестов,
  PHP syntax, contracts, skill audit, shell checks.
- PHPCompatibilityWP, strict complexity, PHPStan level 7 нового файла,
  полный first-party PHPStan и structure gate: PASS.
- Gitleaks нового PHP-файла: PASS, 3965 байт, утечек не найдено.
- Браузер: **21 PASS, 1 предусмотренный SKIP** без изменения snapshots или
  допусков. Первый запуск на порту 18919 дал 20 PASS/1 FAIL/1 SKIP: длиннее
  localhost permalink перенёс кнопку редактора на телефоне. Повтор того же
  кандидата на штатном порту 8888 прошёл.
- Сборщик admin-performance: 5 проб на каждый из четырёх экранов, все budgets
  PASS. Медианы TTFB: dashboard 15.9, статьи 16.4, медиатека 20.3, редактор
  20.9 мс. Это маленький локальный fixture, не production-sized сравнение;
  поля `before` в отчётах — конфигурационные budgets, не реальные пробы «до».

Изоляция: использованы канонические integration/start/test/browser/performance
скрипты с механической заменой project name и artifact directories на
`hs-manacost-meta-cache-20260909` и task-specific пути. Общий Docker project,
предыдущие performance artifacts и постоянный staging не затронуты.

## Независимая проверка и явное исключение quality gate

Код и installer проверены Astra в отдельном контексте. Обнаруженная лишняя
инвалидация на public value updates устранена и покрыта регрессией. После
проверки исправленного кода, реального WP, браузера и точной репетиции отката
reviewer одобрил только эту ограниченную production-активацию.

`CODE_QUALITY_BASE=68b321b make code-quality`: **FAIL**, 0 ошибок и 1 WPCS
warning `WordPress.DB.DirectDatabaseQuery.DirectQuery` в новом `get_col`.
Reviewer принял узкое именованное исключение: точный bounded prepared SELECT
из core, отсутствие эквивалентного API для такого списка, TTL и invalidation.
Владелец исключения — maintainer этого MU-модуля; пересмотреть при изменении
core SQL/API или удалении модуля. Raw warning сохранён; suppressions, baseline
и правила не ослаблены. Старый WPCS-долг ранее изменённого cache-purge класса
также не исчезает. Общий CI нельзя считать зелёным.

## Установка и откат

Installer: `/tmp/hs-admin-meta-cache-20260909-KREw1X/deploy.sh`.
SHA256: `7b42bb513a17883056c119699965e217168ccf4f69813046e68ef789f4e83fea`.
Точный новый source hash и оба ранее установленных OPcache hashes проверяются
перед установкой. Новый PHP-файл устанавливается через extensionless temporary
и no-clobber rename, `koloda:koloda`, 0644. Сохраняется hash-манифест всех
предыдущих MU-файлов и wp-config; их содержимое не копируется в отчёт.

Репетиция тем же installer: apply и recoverable rollback PASS. Откат перемещает
только новый MU-файл из autoload-каталога в root-only backup; прежние файлы
и object cache не трогает. Повторное применение или чужой изменённый hash
должны остановить операцию. Не использовать full-tree deploy этой ветки.

Production backup: `/var/backups/hs-manacost-deploy/20260909-admin-meta-cache-33Jssh/`,
root:root 0700. В нём source, installer, protected manifest и результаты проверки.
При необходимости согласованного отката:

```sh
sudo bash /var/backups/hs-manacost-deploy/20260909-admin-meta-cache-33Jssh/deploy.sh rollback production /var/backups/hs-manacost-deploy/20260909-admin-meta-cache-33Jssh
```

Evidence этой задачи: `/tmp/hs-admin-meta-cache-20260909-KREw1X/` и
`.artifacts/admin-meta-cache-performance-20260909/`.

## Фактические production-результаты

После установки новый файл имеет утверждённый SHA256, `koloda:koloda`, 0644.
Манифест всех прежних MU-файлов и wp-config совпал после установки и повторно
после проверки. Предыдущие два OPcache-файла не изменились. Данные и сервисы
не перезапускались, broad purge не выполнялся.

| Режим в отдельных запросах PHP-FPM | Время lookup, мс | SQL |
|---|---:|---:|
| Native, медиана 5 проб до установки | 438.122 | 1 в каждой |
| Первый cache miss после установки | 427.557 | 1 |
| Следующие 4 cache hits | 0.042–0.052 | 0 |
| Повторные 5 cache hits после проверки сайта | 0.039–0.050; медиана 0.042 | 0 |

Во всех 15 пробах один и тот же hash 30 имён; ошибок БД нет. В cache-пробах
подтверждены PHP-FPM 8.4.20, external object cache и точный live source hash.
Кэш сохраняется между отдельными FPM-запросами, не только внутри одной команды.

Production smoke PASS для `.ru`, `.com`, origin, Москвы и Новосибирска.
Публичная форма входа: HTTP 200 и настоящий `loginform`; анонимная админка:
ожидаемый 302 на вход. Это не проверка входа под зарегистрированным пользователем.
В коротком error-log окне после активации новых строк, fatal/parse/uncaught и
упоминаний нового модуля не обнаружено. Долгосрочная стабильность этим не доказана.

Private FPM probe после замеров перемещён из исполняемого пути в backup;
пустой диагностический каталог удалён. Одноразовые Docker-контейнеры и их
тестовая tmpfs-БД остановлены/удалены; данные production не затронуты.
Исходники отправлены в [PR #50](https://github.com/Manacost-Labs/hs-manacost.ru/pull/50).
Ветка не merged и full-tree deployment не выполнялся. Raw CI check остаётся
FAIL; это не выпуск через обычный полностью зелёный pipeline.

## Осталось за пределами доказанного результата

Авторизованный production-сценарий редактор → save → autosave → preview →
publish и медиазагрузка ещё не проверены. Не использовались чужие учётные
записи или сессии. Ускорение одного SQL нельзя выдавать за «весь WordPress
мгновенный»; дальнейшие изменения должны опираться на реальные профили
сохранения, внешних HTTP hooks и медиатеки.
