# РСЯ и подписка HearthPulse

Единственная исходная точка РСЯ — `wordpress/mu-plugins/manacost-rsya-inline.php`.
Не добавляйте `context.js`, `Ya.Context.AdvManager.render` или блоки РСЯ через
Newspaper, Ad Inserter, HTML-виджеты и шаблоны: так обходится единый gate.

## Правило показа

Перед загрузкой `https://yandex.ru/ads/system/context.js` страница вызывает
приватный `GET /reader-api/v1/ad-status` с cookie Reader. Endpoint проверяет
активный токен HearthPulse и серверный entitlement. Только точный ответ
`{ "adFree": false }` разрешает загрузить РСЯ. Платная подписка, неизвестный
статус или ошибка Reader/HearthPulse означают отсутствие РСЯ. Не передавайте
cookie, subject HearthPulse, токен, e-mail или профиль в РСЯ и не используйте
клиентский claim о подписке как авторизацию.

Блоки до результата скрыты, поэтому платный читатель не получает запрос к РСЯ,
не видит пустое место и не создаёт рекламный показ. Ответ endpoint всегда
`private, no-store`; его нельзя кэшировать на edge или смешивать между людьми.

## Настройка нового блока

1. Добавляйте block ID только в `manacost-rsya-inline.php`.
2. Новый вызов `Ya.Context.AdvManager.render` обязан ждать
   `window.manacostRsyaReady` и прекращаться при `false`.
3. До разрешения gate не вставляйте внешний script, iframe, пиксель или
   preconnect РСЯ.
4. Сохраняйте `.ru` canonical, `.com` noindex-зеркало и отключённую аналитику
   на staging.
5. Проверяйте: гость/неплатный получает один loader; платный не получает
   `context.js` и ни одного РСЯ-запроса; ошибка entitlement также без рекламы.

## Проверки

```bash
make reader-test
python3 -m unittest tests/test_rsya_inline_banner.py
READER_TEST_CHROMIUM=/usr/bin/chromium make reader-browser-test
make check
```
