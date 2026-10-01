# OAuth для Articles MCP

Модуль находится **в этом репозитории**, в `services/reader/mcp-oauth*.js`.
Hearthpulse остаётся источником входа (включая Telegram) и текущих прав.
Новые OAuth-приложения, подтверждения и токены MCP обслуживает Manacost.
Изменять код Hearthpulse для этого сценария не требуется.

На 2026-10-01 реализован и проверен локальный сценарий с официальным MCP SDK.
Публичный endpoint ещё не включён. Модуль выключен по умолчанию, обычный
deployment WordPress не запускает отдельный MCP-сервис и не применяет nginx-snippets.

## Сценарий подключения

1. Клиент обращается к `https://hs-manacost.ru/mcp` и получает discovery через
   `WWW-Authenticate`/`/.well-known/oauth-protected-resource/mcp`.
2. OAuth discovery находится по адресу
   `https://hs-manacost.ru/.well-known/oauth-authorization-server/mcp-oauth`.
3. Публичный клиент регистрирует свои redirect URI через `/mcp-oauth/register`.
   Поддерживаются HTTPS и HTTP loopback (`localhost`, `127.0.0.1`, `[::1]`).
   URI сопоставляются точно, включая порт и query. Client secret не выдаётся.
4. Клиент открывает `/mcp-oauth/authorize` с PKCE S256, `resource` и
   `scope=articles:read`. Manacost предлагает существующий вход через Hearthpulse.
5. После входа сервер проверяет действующую роль администратора и показывает
   имя приложения, адрес возврата и кнопки «Разрешить чтение»/«Отказать».
   Автоматического согласия нет. POST привязан к той же сессии, Origin и одноразовому подтверждению.
6. Клиент обменивает одноразовый код на токены. `resource` обязателен и должен
   точно совпадать с URL MCP; redirect URI и PKCE проверяются повторно.
7. MCP проверяет подпись, issuer, audience, scope, срок токена и **текущие**
   права Hearthpulse на каждом запросе. Снятие роли или отказ API закрывает доступ.

Это публичный OAuth Authorization Code + PKCE профиль с DCR; входные client secrets,
implicit/password grants и Client ID Metadata Documents не поддерживаются.
Реализация соответствует discovery/resource/PKCE требованиям
[спецификации MCP](https://modelcontextprotocol.io/specification/2026-07-28/basic/authorization).
Официальный SDK 2.2.0 проверен; конкретные сторонние клиенты нужно проверить на staging.

## Endpoint-контракт

| Endpoint | Метод | Доступ / результат |
|---|---|---|
| `/.well-known/oauth-authorization-server/mcp-oauth` | GET | Публичная metadata issuer `/mcp-oauth`, S256, `articles:read`, DCR, issuer в callback. |
| `/mcp-oauth/jwks` | GET | Только публичный RSA ключ, RS256, `kid`. |
| `/mcp-oauth/register` | POST JSON | 1–5 redirect URI; `client_name` до 100 символов; `token_endpoint_auth_method=none`. |
| `/mcp-oauth/authorize` | GET | Параметры OAuth либо серверный `request`; вход и HTML согласия. |
| `/mcp-oauth/authorize` | POST form | `request`, `approval_token`, `decision=approve/deny`; сессия + Origin. |
| `/mcp-oauth/token` | POST form | `authorization_code` или `refresh_token`; `client_id`, точный `resource`. |
| `/mcp-oauth/revoke` | POST form | `client_id`, `token`; отзывает всю refresh-цепочку этого клиента. |

Ответы: `no-store`, `noindex`; страницы без JS и внешних ресурсов. OAuth endpoints
JSON поддерживают CORS без credentials. Страница согласия передаёт в Referer только
origin, чтобы нативный POST сохранял проверяемый Origin. OAuth URL не логируются.
Тела ограничены 4 KiB. DCR ограничен 30 запросами/минуту, остальные маршруты —
300 каждый; действуют и общие лимиты Reader. Ограничения нельзя снимать для подключения клиента.

Код живёт 60 секунд; запрос согласия — 5 минут. JWT access token живёт 15 минут:
`typ=at+jwt`, RS256, `iss=https://hs-manacost.ru/mcp-oauth`,
`aud=https://hs-manacost.ru/mcp`, `sub` Hearthpulse, `scope=articles:read`.
Refresh token одноразовый с ротацией; повтор старого токена отзывает всю его
активную цепочку. Выдача и замена происходят одной транзакцией. Цепочка живёт до 30 дней, **только пока действует исходная
Reader-сессия и роль администратора**. Выход/окончание сессии прекращает продление.
Отзыв refresh token и выход не аннулируют уже выданный JWT немедленно: он действует
не более 15 минут при сохранённой роли. Для немедленного общего отключения остановите
MCP listener и уберите его публичные маршруты. Снятие роли закрывает следующий запрос.

## Настройки запуска

Используйте существующие origin/deployment/client ID/secret и encryption key Reader.
Секреты передаются через защищённый service environment, без значений в Git/README.

| Reader переменная | Production | Staging |
|---|---|---|
| `READER_MCP_OAUTH_ENABLED` | `1` | `1` |
| `READER_ALLOW_PRODUCTION_MCP_OAUTH` | `1` | Не задавать. |
| `READER_MCP_OAUTH_SIGNING_KEY_FILE` | Абсолютный путь к приватному RSA PEM. | Отдельный ключ staging. |

Файл ключа: обычный файл без symlink, mode `0600`, читаемый только сервисным
пользователем, RSA минимум 2048 bit, вне Git/web root. Ключ создаётся оператором
в защищённом каталоге и сохраняется между релизами. Пример команды:

```sh
umask 077
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out /private/path/mcp-signing.pem
```

Reader проверяет конфигурацию и ключ **до** создания OAuth-таблиц. Staging допускает
только `manacost-reader-staging`, production — `manacost-reader-production` и отдельный
production flag. Issuer Hearthpulse для существующего входа остаётся
`https://hearthpulse.net/identity`.

| MCP переменная | Production | Staging |
|---|---|---|
| `MCP_SOURCE_ORIGIN` | `https://hs-manacost.ru` | `https://test.hs-manacost.ru` |
| `MCP_RESOURCE_URL` | `https://hs-manacost.ru/mcp` | `https://test.hs-manacost.ru/mcp` |
| `MCP_OAUTH_ISSUER` | `https://hs-manacost.ru/mcp-oauth` | `https://test.hs-manacost.ru/mcp-oauth` |
| `MCP_OAUTH_JWKS_URL` | `https://hs-manacost.ru/mcp-oauth/jwks` | `https://test.hs-manacost.ru/mcp-oauth/jwks` |
| `MCP_PERMISSIONS_CLIENT_SECRET` | Существующий production Reader secret. | Отдельный staging secret. |
| `MCP_PORT` | `8792` | `8793` |
| `MCP_DATABASE` | Приватный production index. | Отдельный staging index. |

Нельзя смешивать issuer, resource, JWKS и индекс разных сайтов.
Пакет MCP сохраняет относительную структуру `services/articles-mcp` и
`services/reader/community-clients.js`. Установите locked зависимости MCP;
Reader использует свои существующие зависимости. Systemd-шаблон находится в
`ops/articles-mcp/manacost-articles-mcp.service`; staging использует отдельного
пользователя, каталог, environment и порт. Listener доступен только с loopback.
Синхронизация индекса остаётся отдельным bounded worker с `flock`;
см. [основной runbook](articles-mcp.md).

## Данные, миграция и rollback

Selector — только новые `mcp_oauth_*` таблицы в приватной SQLite Reader, без таблиц
WordPress, профилей, комментариев или существующих сессий. В выключенном состоянии
схема не меняется. Включение добавляет 6 таблиц одной транзакцией, привязывает их к
issuer; повторный запуск идемпотентен. Другой issuer отвергается с rollback транзакции.

До включения: при остановленном Reader сделайте приватную копию SQLite, encryption
key и отдельного signing key; проверьте восстановление копии в изолированном каталоге
и read-only counts существующих таблиц. Не проверяйте восстановление на production DB.
Ожидаемый schema diff — ровно 6 новых таблиц; существующие строки не переписываются.
Ограничения: 500 клиентов, по 500 запросов/кодов, 4096 refresh records и 65536 хешированных маркеров ротации, до 8 активных refresh на
пару client/subject. Неиспользованный клиент истекает через 10 минут, активный —
через 30 дней с продлением. Истёкшие записи удаляются при новых операциях/минутной очистке.
Маркеры ротации сохраняются до окончания 30-дневной цепочки для обнаружения повторов.
Коды/refresh/pending request хешируются; Reader session ID в grant хранится только
зашифрованным AES-GCM существующим encryption key. В JWT нет upstream токенов или cookies.

Rollback: убрать новые nginx-маршруты, остановить MCP, отключить Reader OAuth flag,
вернуть предыдущий immutable Reader release. Новые таблицы можно оставить:
старый Reader их игнорирует. Полное восстановление SQLite затронет более новые
Reader-сессии/профили/комментарии, поэтому не делайте его для отката одного OAuth-модуля.

## Выпуск и проверки

1. `make check`, secrets, selected-path `codex-semgrep`; PR с зелёными проверками.
2. На отдельном staging установите тот же SHA Reader/MCP, ключи, index и opt-in
   snippets из `ops/articles-mcp/{origin,proxy}-staging.conf`. Выполните `nginx -t`
   на origin и обоих RU edges до reload. Basic Auth существующих staging WordPress
   и Reader login остаётся включённым; новый scoped OAuth/MCP доступ проверяется
   собственными токенами. Staging source credentials не являются токеном MCP.
3. Проверьте реальный вход Hearthpulse/Telegram, согласие/отказ, официальный и нужные
   сторонние клиенты, PKCE/replay, logout/refresh, снятие роли, недоступность API,
   сохранность существующего Reader account и отсутствие WP/ads/views mutations.
4. Только после staging выпустите точный SHA в production отдельным процессом:
   отдельный MCP daemon + opt-in production nginx-snippets. Проверьте origin,
   основной домен, зеркало без MCP, Москву и Новосибирск. Не выводите коды/cookies
   в curl verbose, журналы или GitHub; access/error logs для этих маршрутов отключены.
5. До активации убедитесь, что REST metadata для проверки публикации не закэширована,
   индекс синхронизирован и scheduled sync работает. Подключение по URL не считается
   завершённым только из-за локальных фикстур.

Локальные проверки:

```sh
node --test services/reader/test/reader-mcp-oauth*.test.js
node --test services/articles-mcp/test/oauth-flow.test.js
node tests/reader-ui/mcp-oauth-browser.mjs
python3 -m unittest discover -s tests/reader-ui -p test_mcp_oauth_proxy.py
```

Проверяются полный SDK OAuth → реальный HTTP MCP read, подпись JWKS, строгая
конфигурация/приватный ключ, отсутствие миграции при disabled, повторный запуск,
реальный Reader login/callback, CSRF, redirect/resource/PKCE, одноразовые/истёкшие
коды, refresh/revocation, шифрование, отказ прав/API; браузер: 320/390/768/1024/1440 px,
200% CSS zoom, видимый focus, клавиатура, мобильные кнопки и нативные approve/deny POST.
Локальный browser fixture использует синтетический аккаунт и mapping loopback origin;
он не доказывает production DNS/TLS или реальный вход провайдера.
