# Правила работы с hs-manacost.ru

Этот репозиторий — единственный источник кода и конфигурации для одного проекта:

- `hs-manacost.ru` — основной production-домен и SEO-каноникал;
- `hs-manacost.com` — зеркало того же production WordPress, базы и медиатеки;
- `test.hs-manacost.ru` — изолированный тестовый WordPress для проверки изменений.

## Обязательный процесс для AI-агентов

1. Работать только в `/srv/projects/wordpress/hs-manacost.ru`. Не использовать `/var/www` как исходник.
2. Перед изменениями прочитать этот файл и выполнить `git status --short`. Не перезаписывать чужие незакоммиченные изменения.
3. Если появится `.codegraph/`, перед поиском по коду использовать `codegraph explore`.
4. После каждого завершённого изменения выполнить `make check` и проверку секретов.
5. Завершённую и проверенную работу обязательно закоммитить и отправить в `origin`. Нельзя оставлять готовое изменение только на сервере или только в runtime-копии.
6. Обычные и рискованные изменения делать через короткую ветку и pull request. Небольшие явно разрешённые изменения можно отправлять в `main`.
7. Push в `main` автоматически выкладывается только на `test.hs-manacost.ru`. Production не выкладывается прямой командой после push.
8. Production продвигается отдельным workflow `Promote production` только для точного commit SHA, который уже успешно прошёл staging deployment и smoke-check.
9. После production deployment обязательно проверить основной домен, зеркало, origin, московский и новосибирский прокси.
10. Если потребовался аварийный hotfix в runtime, немедленно перенести тот же минимальный diff сюда, проверить, закоммитить и сделать push.

## Обязательные AI-скиллы

Перед началом изменения агент обязан определить тип задачи, назвать применяемые скиллы в рабочем сообщении и полностью прочитать соответствующие `SKILL.md`. Скиллы являются обязательным процессом, а не рекомендацией. Не нужно загружать весь набор сразу: используются базовые скиллы и только относящиеся к задаче специализации.

Для любой задачи в этом проекте первым обязательно использовать `hs-manacost-project`: прочитать его `SKILL.md` и выполнить `.agents/skills/hs-manacost-project/scripts/context-snapshot.sh`. Он задаёт карту source/runtime/data, выбор специализаций, staging/production и формат доказательств. Затем загружать только профильный маршрут из `config/ai-skills.json`.

Точный реестр и маршрутизация находятся в `config/ai-skills.json`. Канонические проектные копии скиллов лежат в `.agents/skills`; синхронизированные копии для Claude Code и Codex лежат в `.claude/skills` и `.codex/skills`. Нельзя менять только одну копию: после изменения канонического скилла выполнить `./ops/sync-ai-skills.sh`.

Для нетривиального повторного использования кода сначала выполните локальный
`context-economy retrieve` по явно выбранным исходникам; расширяйте область
по доказанному пробелу, а OpenRouter включайте явно. Команды, проектный набор
проверочных запросов и порядок учёта принятых задач находятся в
`docs/runbooks/quality-guard.md`. Для задачи, включённой в пилот, запускайте
счётчик до подготовки и записывайте итог только после проверок.

Официальные WordPress-скиллы зафиксированы на конкретном commit `WordPress/agent-skills`. Они сейчас ориентированы на WordPress 7.0+, а проект работает на WordPress 6.9.7, поэтому version-sensitive API обязательно сверять с установленным core и исходниками проекта.

### Для каждого изменения кода

Использовать последовательно:

1. `wordpress-change-impact` — до редактирования определить затронутые поверхности, контракты, домены, обязательные скиллы и проверки; неизвестный first-party путь требует ручной классификации.
2. `wordpress-clean-code` — применить проектные правила чистого WordPress-кода и запустить WPCS, PHPCompatibilityWP и PHPStan только для first-party surface.
3. `agent-test-driven-development` — зафиксировать требуемое поведение тестом или воспроизводимой проверкой.
4. `agent-code-review-and-quality` — проверить корректность, безопасность, поддержку, тесты и влияние на пользователей.
5. `agent-code-simplification` — убрать лишнюю сложность без несвязанных рефакторингов и изменения поведения.
6. `agent-security-and-hardening` — проверить ввод, права, nonce, escaping, секреты и границы данных.
7. `agent-git-workflow-and-versioning` — минимальный diff, атомарный commit и обязательный push.

Постоянный baseline любого проектного задания хранится в `baseline_for_project_tasks`, а baseline изменений кода — в `baseline_for_code_changes`. Внешние AI entrypoints (`CLAUDE.md`, `.github/copilot-instructions.md`) обязаны вести к этому файлу и реестру, а не дублировать собственные расходящиеся правила.

Для любой WordPress-задачи сначала использовать `wordpress-router` и `wp-project-triage`, затем профильный `wp-plugin-development`, `wp-rest-api`, `wp-wpcli-and-ops`, `wp-performance` или `wp-phpstan`. Проектный `wordpress-plugin-dev` применяется вместе с ними и задаёт локальные ограничения hs-manacost.ru.

Для любого изменения `Newspaper_new`, tagDiv Composer/Standard Pack/Cloud Library, Cloud Templates, блоков, модулей, Theme API, CSS темы или child theme обязательно использовать `newspaper-tagdiv` вместе с `wordpress-clean-code`. Прямое изменение родительской темы или `td-*` плагина допускается только после поиска поддерживаемой точки расширения и запуска `.agents/skills/newspaper-tagdiv/scripts/audit_newspaper_change.py`.

Для любого нового или изменяемого экрана `wp-admin`, страницы настроек плагина, dashboard, таблицы, формы, фильтров, bulk actions, модального окна или editor sidebar обязательно использовать `wordpress-admin-ui`. Навык применяется вместе с WordPress/security-скиллами и требует проверки реального сценария на desktop и mobile, а не только просмотра скриншота.

Для медленной админ-панели, запуска редактора, медиабиблиотеки, list table, поиска/фильтрации, autosave, сохранения, AJAX/REST-действия или деградации на больших объёмах данных обязательно использовать `wordpress-admin-performance` вместе с `wordpress-admin-ui`, `wp-performance` и `wordpress-observability`. Требуются минимум пять сопоставимых замеров до/после с одной ролью, объёмом данных и состоянием кэша; производительность нельзя улучшать отключением проверок прав, autosave, revisions, S3, безопасности или корректности данных.

Для любого изменения редактора статей, Classic Editor, TinyMCE, Gutenberg, редакторских metabox/sidebar, autosave, revisions, предпросмотра, медиазагрузки, S3-вставки, редакторского shortcode или `hs-editor-workspace` обязательно использовать `wordpress-article-editor` вместе с `wordpress-admin-ui`. Если затронут Newspaper/tagDiv, одновременно обязателен `newspaper-tagdiv`. Нельзя считать изменение проверенным без сохранения черновика, autosave, восстановления revision, preview, публикации и проверки сохранённого `post_content`.

Для изменения или диагностики WP Rocket, Redis, Cloudflare, регионального proxy cache, Perfmatters, All in One SEO, Wordfence, Redirection, обновления активного плагина, WAF или `manacost-cache-purge` обязательно использовать `wordpress-runtime-stack`. Сначала определяется владеющий проблемой слой; массовая очистка всех кэшей, Redis flush и отключение защиты не являются первым диагностическим действием.

Для редакционного SEO, canonical/robots/schema/sitemap, внутренних ссылок и SEO изображений обязательно использовать `wordpress-seo-editorial`. Он сохраняет `.ru` единственным индексируемым каноникалом, `.com` — функциональным `noindex`-зеркалом, а staging — полностью `noindex`.

Для Playerok и других баннеров, Plausible, событий статей, Newspaper view counters и расхождений рекламы между регионами обязательно использовать `wordpress-ads-analytics`. Пассивная проверка не должна создавать production-события или просмотры; активная проверка счётчика выполняется только на disposable staging-материале.

Перед публикацией, планированием или повторной публикацией материала использовать `wordpress-editorial-publish-gate`. Решение о выпуске принимается только по заполненному evidence-манифесту: редактор/revisions, rendered content, media/S3, SEO hosts, mobile/accessibility, ads/analytics, cache delivery и rollback. Скриншот не заменяет проверку сохранённых данных или сетевого поведения.

Для cookies, consent, форм, комментариев, Telegram-уведомлений, экспорта, retention и удаления персональных данных использовать `wordpress-privacy-consent`. Для Telegram-ботов, API, webhook, OAuth callback, embed, S3 и других внешних зависимостей использовать `wordpress-external-integrations`; обязательны timeout, безопасный retry, idempotency/replay protection, деградация, наблюдаемость и выключатель без секрета в Git.

Для любого изменения frontend, Newspaper, редактора или wp-admin, затрагивающего клавиатуру, focus, headings, labels, contrast, zoom или mobile accessibility, использовать `wordpress-accessibility` вместе с браузерной проверкой. Запрещено отключать пользовательский zoom; автоматический аудит не заменяет ручной keyboard-сценарий.

Для мобильной адаптации, breakpoint, viewport overflow, touch/orientation или расхождений desktop/mobile обязательно использовать `wordpress-responsive-experience` вместе с `newspaper-tagdiv`, `wordpress-accessibility` и `playwright`. Требуются functional parity, реальные состояния и проверка 320/390/768/1024/1440 px, промежуточных ширин и 200% zoom; запрещено скрывать функции или отключать масштабирование для прохождения теста.

Для изменения шрифтов, кириллицы, типографики, контейнеров, сеток, article measure, отступов или vertical rhythm обязательно использовать `wordpress-typography-layout-system` вместе с `wordpress-responsive-experience` и `newspaper-tagdiv`. Проверять итоговые computed styles анонимной и авторизованной страницы, включая runtime override `manacost-font-trim`, font loading и CLS.

Для редизайна, нового визуального направления, дизайн-системы, токенов или крупных изменений шаблонов обязательно использовать `wordpress-redesign-system` вместе с `wordpress-responsive-experience`, `wordpress-typography-layout-system`, `newspaper-tagdiv`, `wordpress-accessibility`, `frontend-design` и браузерными visual-тестами. До широкого кодирования требуется аудит текущего сайта, 2–3 различимых направления, выбор одного направления пользователем и валидный design contract. Нельзя принимать универсальный AI-макет, lorem ipsum или один desktop-скриншот как готовый редизайн.

Для production-инцидента, недоступности, DNS/TLS/502/504, региональной ошибки или массовой поломки обязательно использовать `wordpress-incident-response`. Для отсутствующих, неправильных, перезаписанных или тяжёлых изображений, одинаковых имён файлов, WebP/AVIF, `hs-local-image-optimizer`, `uploads-webpc` и S3 использовать `wordpress-media-integrity`. Оптимизатор создаёт sidecar-файлы и не имеет права заменять исходное изображение.

Для изменения production-данных, `postmeta`, options, URL, сериализованных значений или собственных таблиц использовать `wordpress-database-migrations`. Для PR, staging deployment, production promotion, hotfix и rollback использовать `wordpress-release-manager`. Для health/performance/cron/capacity отчёта использовать `wordpress-observability`. Для проверки опубликованных статей, ссылок, шорткодов, изображений, canonical и robots использовать `wordpress-content-integrity`.

### По типу задачи

| Задача | Обязательные скиллы | Обязательная проверка |
|---|---|---|
| Админ-панель, настройки, dashboard, таблицы и формы | `wordpress-admin-ui`, `wp-project-triage`, `wp-plugin-development`, `agent-frontend-ui-engineering`, `web-quality-accessibility`, `agent-browser-testing-with-devtools` | Роли и capability, nonce/REST permissions, create/edit/filter/paginate/error/delete, keyboard, 320/768/1024/1440 px |
| Производительность wp-admin, редактора, list table, media, AJAX/REST | `wordpress-admin-performance`, `wordpress-admin-ui`, `wp-performance`, `wordpress-observability`, `agent-performance-optimization`, `agent-browser-testing-with-devtools` | Не менее 5 замеров до/после, одинаковые роль/данные/cache state, TTFB/interactive/SQL/memory/long tasks, функциональные и mobile checks |
| Редактор статей, TinyMCE/Gutenberg, autosave, revisions, media и shortcodes | `wordpress-article-editor`, `wordpress-admin-ui`, `wp-project-triage`, `wp-plugin-development`; при tagDiv также `newspaper-tagdiv` | Draft/autosave/revision/preview/publish, no-op `post_content`, роли, keyboard/mobile, S3 и frontend rendering |
| WP Rocket, Redis, Cloudflare/proxy cache, Perfmatters, AIOSEO, Wordfence, Redirection и обновления плагинов | `wordpress-runtime-stack`, `wp-wpcli-and-ops`, `wp-performance`, `agent-performance-optimization`, `agent-security-and-hardening` | Владеющий слой, targeted purge, cold/warm, anonymous/authenticated, staging, origin и оба RU-прокси, rollback |
| Интерфейс, тема, CSS/JS, адаптивность | `wordpress-responsive-experience`, `wordpress-typography-layout-system`, `newspaper-tagdiv`, `wordpress-accessibility`, `playwright`, `agent-frontend-ui-engineering`, `frontend-design` | Functional parity, 320/390/768/1024/1440 px и промежуточные ширины, 200% zoom, touch/keyboard, real-content states, отсутствие горизонтального скролла/белых полос |
| SEO, шаблоны страниц, мета и индексация | `wordpress-seo-editorial`, `seo`, `seo-technical` и профильный `seo-page`/`seo-schema`/`seo-images`/`seo-sitemap` | Каноникал только на `.ru`; `.com` остаётся noindex-зеркалом; `test` остаётся полностью noindex |
| Реклама, Playerok, Plausible и просмотры статей | `wordpress-ads-analytics`, `wordpress-runtime-stack`, `wordpress-observability`, `playwright` | Актуальный баннер на origin/Москва/Новосибирск, один tracker/pageview, staging без analytics, view mutation только на disposable staging post |
| Допуск материала к публикации | `wordpress-editorial-publish-gate` и профильные editor/media/SEO/accessibility/ads skills | Полный evidence-манифест со статусом `READY`, staging-сценарий и rollback revision/commit |
| Приватность, согласия и персональные данные | `wordpress-privacy-consent`, `wordpress-external-integrations`, `agent-security-and-hardening` | Карта данных, consent accept/refuse, retention/export/delete, redacted third-party requests |
| Доступность frontend, редактора и wp-admin | `wordpress-accessibility`, `playwright`, `web-quality-accessibility` и профильный UI skill | Keyboard path, focus, axe/manual checks, 320/768/1024/1440 px, 200% zoom, no horizontal page scroll |
| Редизайн, дизайн-система и крупное изменение шаблонов | `wordpress-redesign-system`, `wordpress-responsive-experience`, `wordpress-typography-layout-system`, `newspaper-tagdiv`, `wordpress-accessibility`, `frontend-design`, `playwright`, `web-perf` | Аудит, выбранное направление, валидные design/responsive/typography contracts, real-content states, 320/390/768/1024/1440 px, visual/accessibility/performance gates, rollback |
| Внешние API, Telegram, webhook, OAuth, S3 и embeds | `wordpress-external-integrations`, `wordpress-privacy-consent`, `wordpress-observability`, `agent-security-and-hardening` | Contract, timeout/401/429/5xx/duplicate/disable/recovery tests, redacted logs, safe degradation |
| Производительность и кэширование | `agent-performance-optimization`, `web-quality-performance`, `web-quality-core-web-vitals` | Измерение до/после, отсутствие регрессии LCP/INP/CLS, проверка origin и обоих RU-прокси |
| Полный аудит пользовательского качества | `web-quality-web-quality-audit`, `web-quality-best-practices`, `web-quality-accessibility` | Реальный браузер и приоритизированный список измеримых проблем |
| Ошибка или production-инцидент | `wordpress-incident-response`, `agent-debugging-and-error-recovery`, `agent-test-driven-development` | Воспроизведение → локализация → минимальный fix → regression test → smoke-check |
| Pipeline, nginx, deployment, Cloudflare и прокси | `wordpress-release-manager`, `cloudflare`, `wrangler`/`workers-best-practices` при Worker-коде, `agent-ci-cd-and-automation`, `agent-shipping-and-launch`, `agent-security-and-hardening` | Staging первым, backup/rollback, `nginx -t`, проверка `.ru`, `.com`, origin, Москвы и Новосибирска |
| Изображения, одинаковые имена, WebP/AVIF, S3 и восстановление media | `wordpress-media-integrity`, `wordpress-article-editor`, `wordpress-runtime-stack` | SHA256/MIME/dimensions, неизменный source image, sidecar, S3, modern/legacy Accept и оба RU-прокси |
| Миграция БД или массовое изменение контента | `wordpress-database-migrations`, `wp-wpcli-and-ops`, `agent-security-and-hardening` | Dry-run/count, idempotence, verified restore, bounded batch, staging и rollback |
| Выпуск, promotion или rollback | `wordpress-release-manager`, `agent-ci-cd-and-automation`, `agent-shipping-and-launch` | Один staging-проверенный SHA, gates, production workflow и региональная проверка |
| Наблюдаемость и health-отчёт | `wordpress-observability`, `wordpress-runtime-stack`, `wp-performance` | Измеряемый интервал, cold/warm, cron/S3/backup/capacity, origin и регионы |
| Целостность опубликованного контента | `wordpress-content-integrity`, `wordpress-article-editor`, `wordpress-media-integrity`, `seo-technical` | Stored/rendered content, links/shortcodes/media/views, canonical/robots по хостам |

SEO-скиллы не имеют права превращать зеркало `.com` в конкурирующий индексируемый сайт. Performance-скиллы не имеют права отключать безопасность, корректность счётчиков, персонализацию или очистку кэша ради синтетического результата. Design-скиллы не имеют права ухудшать доступность или скорость.

## Границы данных и безопасности

- Не коммитить `.env`, `wp-config.php`, пароли, токены, cookies, сертификаты, приватные ключи, дампы БД, медиатеку, логи, кэши и резервные копии.
- Не копировать в Git содержимое S3/Object Storage и `wp-content/uploads`.
- В `config/wordpress-plugins.json` и `wordpress/plugins` хранятся только regular plugins со статусом `active` на production. Неактивные плагины не индексируются; MU-плагины версионируются отдельно в `wordpress/mu-plugins`, runtime drop-ins не коммитятся.
- Настройки, лицензионные ключи и runtime-состояние плагинов не входят в репозиторий. Плагины нельзя обновлять вместе с несвязанной задачей; commercial/tagDiv-пакеты нельзя публиковать вне приватного репозитория.
- Не выводить секреты в команды, CI-логи, issues, pull requests или документацию.
- Nginx-конфиги в `ops/nginx` являются версионированной конфигурацией. Их применение требует отдельной проверки `nginx -t`; обычный WordPress deployment их автоматически не заменяет.
- Прокси не получают отдельную копию WordPress: они обслуживают тот же origin. Pipeline очищает настроенные кэши и проверяет каждый edge отдельно.

## Обязательные проверки

```bash
make check
/home/debian/server/tools/ai-quality/bin/ai-security-check staged
./ops/smoke-check.sh staging
./ops/smoke-check.sh production
```

Для ручного deployment сначала использовать dry-run из `ops/deploy.sh`. Не обходить GitHub pipeline для обычных production-релизов.

## Контракты, integration и эксплуатационная безопасность

- При изменении post meta, options, shortcodes, AJAX/REST endpoints, cron hooks или capabilities выполнить `make contracts`, проверить diff `config/wordpress-contracts.json` и добавить поведенческий тест скрытой зависимости.
- При изменении публикации, редактора, media/S3, каноникалов, просмотров или cache purge обязательно выполнить `make integration`.
- При изменении Newspaper, CSS, frontend или `wp-admin` выполнить `make visual`; новые эталоны принимать только после ручного просмотра и генерировать в зафиксированном Playwright-контейнере.
- Обновляется только один regular plugin за изменение. Commercial и tagDiv/Newspaper обновляются вручную, сначала в integration и staging; production auto-update запрещён.
- Бэкап нельзя считать успешным только по exit code копирования: требуется свежий успешный restore-drill БД и независимой S3-копии. Production БД никогда не является целью тестового восстановления.
