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

Точный реестр и маршрутизация находятся в `config/ai-skills.json`. Канонические проектные копии скиллов лежат в `.agents/skills`; синхронизированные копии для Claude Code и Codex лежат в `.claude/skills` и `.codex/skills`. Нельзя менять только одну копию: после изменения канонического скилла выполнить `./ops/sync-ai-skills.sh`.

Официальные WordPress-скиллы зафиксированы на конкретном commit `WordPress/agent-skills`. Они сейчас ориентированы на WordPress 7.0+, а проект работает на WordPress 6.9.7, поэтому version-sensitive API обязательно сверять с установленным core и исходниками проекта.

### Для каждого изменения кода

Использовать последовательно:

1. `agent-test-driven-development` — зафиксировать требуемое поведение тестом или воспроизводимой проверкой.
2. `agent-code-review-and-quality` — проверить корректность, безопасность, поддержку, тесты и влияние на пользователей.
3. `agent-code-simplification` — убрать лишнюю сложность без несвязанных рефакторингов и изменения поведения.
4. `agent-security-and-hardening` — проверить ввод, права, nonce, escaping, секреты и границы данных.
5. `agent-git-workflow-and-versioning` — минимальный diff, атомарный commit и обязательный push.

Для любой WordPress-задачи сначала использовать `wordpress-router` и `wp-project-triage`, затем профильный `wp-plugin-development`, `wp-rest-api`, `wp-wpcli-and-ops`, `wp-performance` или `wp-phpstan`. Проектный `wordpress-plugin-dev` применяется вместе с ними и задаёт локальные ограничения hs-manacost.ru.

Для любого изменения `Newspaper_new`, tagDiv Composer/Standard Pack/Cloud Library, Cloud Templates, блоков, модулей, Theme API, CSS темы или child theme обязательно использовать `newspaper-tagdiv`. Прямое изменение родительской темы или `td-*` плагина допускается только после поиска поддерживаемой точки расширения и запуска `.agents/skills/newspaper-tagdiv/scripts/audit_newspaper_change.py`.

Для любого нового или изменяемого экрана `wp-admin`, страницы настроек плагина, dashboard, таблицы, формы, фильтров, bulk actions, модального окна или editor sidebar обязательно использовать `wordpress-admin-ui`. Навык применяется вместе с WordPress/security-скиллами и требует проверки реального сценария на desktop и mobile, а не только просмотра скриншота.

Для любого изменения редактора статей, Classic Editor, TinyMCE, Gutenberg, редакторских metabox/sidebar, autosave, revisions, предпросмотра, медиазагрузки, S3-вставки, редакторского shortcode или `hs-editor-workspace` обязательно использовать `wordpress-article-editor` вместе с `wordpress-admin-ui`. Если затронут Newspaper/tagDiv, одновременно обязателен `newspaper-tagdiv`. Нельзя считать изменение проверенным без сохранения черновика, autosave, восстановления revision, preview, публикации и проверки сохранённого `post_content`.

Для изменения или диагностики WP Rocket, Redis, Cloudflare, регионального proxy cache, Perfmatters, All in One SEO, Wordfence, Redirection, обновления активного плагина, WAF или `manacost-cache-purge` обязательно использовать `wordpress-runtime-stack`. Сначала определяется владеющий проблемой слой; массовая очистка всех кэшей, Redis flush и отключение защиты не являются первым диагностическим действием.

### По типу задачи

| Задача | Обязательные скиллы | Обязательная проверка |
|---|---|---|
| Админ-панель, настройки, dashboard, таблицы и формы | `wordpress-admin-ui`, `wp-project-triage`, `wp-plugin-development`, `agent-frontend-ui-engineering`, `web-quality-accessibility`, `agent-browser-testing-with-devtools` | Роли и capability, nonce/REST permissions, create/edit/filter/paginate/error/delete, keyboard, 320/768/1024/1440 px |
| Редактор статей, TinyMCE/Gutenberg, autosave, revisions, media и shortcodes | `wordpress-article-editor`, `wordpress-admin-ui`, `wp-project-triage`, `wp-plugin-development`; при tagDiv также `newspaper-tagdiv` | Draft/autosave/revision/preview/publish, no-op `post_content`, роли, keyboard/mobile, S3 и frontend rendering |
| WP Rocket, Redis, Cloudflare/proxy cache, Perfmatters, AIOSEO, Wordfence, Redirection и обновления плагинов | `wordpress-runtime-stack`, `wp-wpcli-and-ops`, `wp-performance`, `agent-performance-optimization`, `agent-security-and-hardening` | Владеющий слой, targeted purge, cold/warm, anonymous/authenticated, staging, origin и оба RU-прокси, rollback |
| Интерфейс, тема, CSS/JS, адаптивность | `agent-frontend-ui-engineering`, `frontend-design`, `web-quality-accessibility`, `agent-browser-testing-with-devtools` | Desktop и mobile, клавиатура, состояния loading/empty/error, отсутствие горизонтального скролла |
| SEO, шаблоны страниц, мета и индексация | `seo`, `seo-technical` и профильный `seo-page`/`seo-schema`/`seo-images`/`seo-sitemap` | Каноникал только на `.ru`; `.com` остаётся noindex-зеркалом; `test` остаётся полностью noindex |
| Производительность и кэширование | `agent-performance-optimization`, `web-quality-performance`, `web-quality-core-web-vitals` | Измерение до/после, отсутствие регрессии LCP/INP/CLS, проверка origin и обоих RU-прокси |
| Полный аудит пользовательского качества | `web-quality-web-quality-audit`, `web-quality-best-practices`, `web-quality-accessibility` | Реальный браузер и приоритизированный список измеримых проблем |
| Ошибка или production-инцидент | `agent-debugging-and-error-recovery`, `agent-test-driven-development` | Воспроизведение → локализация → минимальный fix → regression test → smoke-check |
| Pipeline, nginx, deployment и прокси | `agent-ci-cd-and-automation`, `agent-shipping-and-launch`, `agent-security-and-hardening` | Staging первым, backup/rollback, `nginx -t`, проверка `.ru`, `.com`, origin, Москвы и Новосибирска |

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
