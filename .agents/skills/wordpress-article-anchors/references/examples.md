# Примеры редакторских переходов

Это формы разметки, а не готовые ID или media URL для вставки. Брать реальные attachment attributes и существующие классы текущего материала.

## Текст ведёт к разделу

```html
<nav aria-label="Колоды по классам">
  <ul>
    <li><a href="#mage">Колоды Мага</a></li>
    <li><a href="#warrior">Колоды Воина</a></li>
  </ul>
</nav>

<h2 id="mage">Колоды Мага</h2>
<h3 id="deck-disc-leyline-mage">Disc Leyline Mage</h3>
[hs_deck id="POST_ID"]

<h2 id="warrior">Колоды Воина</h2>
```

Класс и отдельная колода имеют разные цели. Не добавлять второй `id="mage"` к картинке той же секции и не использовать ID записи в роли fragment без явного HTML-элемента.

## Изображение ведёт к разделу

```html
<a href="#mage" aria-label="Перейти к колодам Мага">
  <img src="EXISTING_CLASS_ICON_URL" alt="" width="96" height="96" />
</a>
```

Когда рядом есть видимая подпись, лучше одна ссылка:

```html
<a href="#mage">
  <img src="EXISTING_CLASS_ICON_URL" alt="" width="96" height="96" />
  <span>Колоды Мага</span>
</a>
```

Реальные `srcset`, `sizes`, классы и атрибуты исходного изображения нужно сохранить. Иконка не должна содержать единственный доступный текст без подходящего accessible name.

## Изображение само является целью

```html
<a href="#deck-preview">Посмотреть изображение колоды</a>

<figure id="deck-preview">
  <img src="EXISTING_ATTACHMENT_URL" alt="Состав колоды Disc Leyline Mage" />
  <figcaption>Disc Leyline Mage</figcaption>
</figure>
```

`id` на `figure` прокручивает к изображению с подписью. Если тот же image является ссылкой на увеличенную версию, сохранить её внутри figure, не оборачивая figure поверх уже существующих ссылок.

## Текст открывает изображение колоды с кодом

На обоих сайтах существует зарегистрированный shortcode, но реализация и attachment metadata различаются. Проверить текущий контракт выбранного плагина:

```text
[hs_deck_link image_id="ATTACHMENT_ID" code="SOURCE_DECKSTRING"]Disc Leyline Mage[/hs_deck_link]
```

Это просмотр колоды, а не внутренний якорь. Не вставлять вместо него голую ссылку на файл, если пользователь ожидает штатные lightbox и копирование кода.

## Ссылка на другую статью

Использовать реальный канонический URL выбранного сайта. Fragment можно добавить только после проверки существования цели в той статье. Для внешней ссылки с запрошенным открытием в новой вкладке:

```html
<a href="VERIFIED_SOURCE_URL" target="_blank" rel="noopener">Оригинальная подборка колод</a>
```

Для несмежных переходов не копировать preview nonce, cookies, signed media URLs или административные токены в публикуемую статью.
