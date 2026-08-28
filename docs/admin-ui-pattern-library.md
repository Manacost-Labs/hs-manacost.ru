# Admin UI pattern library

Библиотека задаёт единый визуальный и поведенческий язык для собственных экранов `wp-admin`. Живой showcase доступен в local/staging по адресу `Инструменты → UI-паттерны`; production-меню не регистрируется. Стили всегда ограничены корнем `.hs-ui-patterns` или корнем конкретного экрана.

## Design contract

До реализации экрана зафиксировать:

1. роль пользователя и требуемую capability;
2. основную задачу и кратчайший успешный путь;
3. объём данных, серверную пагинацию, фильтры и URL-состояние;
4. состояния `loading`, `empty`, `error`, `success`, `permission denied`, partial/stale data и background work;
5. destructive actions, подтверждение и rollback;
6. мобильный сценарий, клавиатуру, focus и live announcements;
7. performance budget и список browser/integration проверок.

## Паттерны

- Page header: один `h1`, короткое пояснение и одно основное действие.
- Form field: видимая label, связанная подсказка, серверная валидация и inline error без потери введённых данных.
- Filters: поиск, статус, сброс и число результатов; состояние хранится в URL.
- List/table: ограниченная выборка и серверная пагинация; на телефоне остаются идентичность, статус и основное действие.
- Status: текст плюс цвет; цвет никогда не является единственным значением.
- Async action: блокируется только отправленная кнопка, `aria-live` сообщает progress/result, retry остаётся доступен.
- Empty и error: объясняют причину и следующее безопасное действие.
- Dialog: native dialog, понятное последствие, безопасная отмена, Escape и возврат focus к opener.

## Использование

Не копировать showcase целиком. Выбрать только нужные паттерны и состояния, сохранить native WordPress controls и подключать `patterns.css` исключительно на принадлежащем экрану hook suffix. Новый фреймворк для формы, таблицы или диалога не нужен.

## Проверка

- ширины 320, 390, 768, 1024 и 1440 CSS px;
- 200% zoom без белых полос и горизонтальной прокрутки страницы;
- Tab, Shift+Tab, Enter, Space и Escape;
- loading, empty, error, permission denied и успешное сохранение;
- доступность label/description/error, видимый focus и live status;
- отсутствие глобальных CSS-селекторов и assets вне owned screen;
- `make visual`, `make admin-performance`, `make check` и staging browser flow.
