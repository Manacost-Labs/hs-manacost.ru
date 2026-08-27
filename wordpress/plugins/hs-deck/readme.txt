=== HS Deck Manager ===
Plugin Name: HS Deck Manager
Main file: hs-deck-manager.php

Структура папки плагина (должна быть такой на сервере):
  wp-content/plugins/<имя-папки>/hs-deck-manager.php
  wp-content/plugins/<имя-папки>/build/
  (имя-папки может быть hs-deck-manager или hs-deck — но путь в БД и на диске должны совпадать)

Если видите ошибку "Файл плагина не найден":
1. WordPress запоминает путь плагина при активации (например hs-deck/hs-deck-manager.php).
2. Если вы переименовали папку или установили новый zip в другую папку, путь в БД и на диске не совпадают.
3. Решение:
   - Вариант А: Переименуйте папку плагина на сервере так, чтобы путь совпадал. Например, если в БД записано "hs-deck/hs-deck-manager.php", папка должна называться hs-deck и в ней файл hs-deck-manager.php.
   - Вариант Б: Через phpMyAdmin откройте таблицу wp_options, найдите запись option_name = 'active_plugins', удалите из option_value строку с путём старого плагина (например "hs-deck/hs-deck-manager.php" или "hs-deck-manager/hs-deck-manager.php"). Сохраните. Затем в админке WordPress → Плагины найдите "HS Deck Manager" и включите его снова.
