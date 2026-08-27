<?php
/**
 * Хранилище логов импорта (REST /manacost/v1/ingest-log).
 *
 * Раньше логи писались в `wp_options` как сериализованный массив (autoload=false).
 * При активном импорте это давало конкурентные `update_option`, постоянно перезаписывало
 * один и тот же blob и плохо масштабировалось. Сейчас — отдельная индексированная таблица
 * с ограничением хранилища через периодическое усечение.
 *
 * Старые данные из опции `manacost_ingest_logs` импортируются один раз при первой
 * инсталляции/апгрейде, после чего опция удаляется.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Ingest_Logs {

    /**
     * Сколько последних записей хранить максимум.
     * При вставке новой записи лишние старые удаляются.
     */
    const MAX_ROWS = 1000;

    /**
     * Версия схемы. Меняется при изменении DDL.
     * Хранится в опции `unified_hs_ingest_logs_db_version`.
     */
    const SCHEMA_VERSION = '1';

    /**
     * Имя таблицы (без префикса WP).
     */
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'manacost_ingest_logs';
    }

    /**
     * Создаёт/обновляет таблицу через dbDelta. Идемпотентно.
     * Вызывается на активации плагина и в авто-миграции при апгрейде.
     */
    public static function install() {
        global $wpdb;

        $stored_version = get_option('unified_hs_ingest_logs_db_version');
        if ($stored_version === self::SCHEMA_VERSION) {
            return;
        }

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta требует особого форматирования (двойные пробелы перед PRIMARY KEY и т.д.).
        // См. https://codex.wordpress.org/Creating_Tables_with_Plugins
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            log_time VARCHAR(40) NOT NULL DEFAULT '',
            status VARCHAR(40) NOT NULL DEFAULT '',
            deck_name VARCHAR(255) NOT NULL DEFAULT '',
            deck_code TEXT NOT NULL,
            streamer VARCHAR(120) NOT NULL DEFAULT '',
            format VARCHAR(40) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('unified_hs_ingest_logs_db_version', self::SCHEMA_VERSION, false);

        // Одноразовая миграция данных из старой опции.
        self::migrate_from_option();
    }

    /**
     * Импортирует существующие записи из опции `manacost_ingest_logs` в таблицу
     * и удаляет опцию. Идемпотентно — после миграции опция отсутствует и метод выходит.
     */
    private static function migrate_from_option() {
        $legacy = get_option('manacost_ingest_logs');
        if (!is_array($legacy) || empty($legacy)) {
            delete_option('manacost_ingest_logs');
            return;
        }
        foreach ($legacy as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            self::add($entry, false /* skip_prune — обрежем в конце один раз */);
        }
        self::prune();
        delete_option('manacost_ingest_logs');
    }

    /**
     * Добавляет запись в лог. $entry — ассоциативный массив:
     *   time, status, deck_name, deck_code, streamer, format, message
     *
     * Все поля пропускаются через sanitize_text_field/_textarea_field.
     */
    public static function add(array $entry, $do_prune = true) {
        global $wpdb;

        $row = array(
            'log_time'  => isset($entry['time']) ? sanitize_text_field((string) $entry['time']) : current_time('mysql'),
            'status'    => isset($entry['status']) ? sanitize_text_field((string) $entry['status']) : '',
            'deck_name' => isset($entry['deck_name']) ? sanitize_text_field((string) $entry['deck_name']) : '',
            'deck_code' => isset($entry['deck_code']) ? sanitize_textarea_field((string) $entry['deck_code']) : '',
            'streamer'  => isset($entry['streamer']) ? sanitize_text_field((string) $entry['streamer']) : '',
            'format'    => isset($entry['format']) ? sanitize_text_field((string) $entry['format']) : '',
            'message'   => isset($entry['message']) ? sanitize_textarea_field((string) $entry['message']) : '',
        );

        // %s для всех полей — все строковые. created_at заполняется DEFAULT.
        $wpdb->insert(
            self::table_name(),
            $row,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($do_prune) {
            // Усечение делается не на каждой вставке, чтобы не нагружать БД.
            // Раз в ~50 вставок достаточно (в худшем случае таблица превысит лимит на ~2%).
            if (mt_rand(1, 50) === 1) {
                self::prune();
            }
        }
    }

    /**
     * Удаляет старые записи, оставляя только MAX_ROWS свежих (по id DESC).
     */
    public static function prune() {
        global $wpdb;
        $table = self::table_name();
        $max = (int) self::MAX_ROWS;

        // Находим id-границу: всё с id меньше — на удаление.
        $boundary = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d",
            $max
        ));
        if ($boundary !== null) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE id <= %d",
                (int) $boundary
            ));
        }
    }

    /**
     * Возвращает последние записи (свежие первыми).
     */
    public static function recent($limit = 200) {
        global $wpdb;
        $limit = max(1, min(1000, (int) $limit));
        $table = self::table_name();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT log_time, status, deck_name, deck_code, streamer, format, message
             FROM {$table} ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A);
        if (!is_array($rows)) {
            return array();
        }
        // Совместимый со старым форматом ключ `time`.
        return array_map(function ($row) {
            $row['time'] = isset($row['log_time']) ? $row['log_time'] : '';
            unset($row['log_time']);
            return $row;
        }, $rows);
    }

    /**
     * Полная очистка таблицы.
     */
    public static function clear() {
        global $wpdb;
        $wpdb->query('DELETE FROM ' . self::table_name());
    }
}
