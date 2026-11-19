<?php
/**
 * Ссылка «Об архетипе» на страницы архетипов HearthPulse.
 *
 * Индекс архетипов (русское и английское имя → slug) берётся из публичного
 * JSON HearthPulse по WP-cron и хранится в неавтозагружаемой опции. Рендер
 * карточки колоды в сеть не ходит: нет индекса или архетип не найден —
 * карточка ведёт на общий список архетипов формата.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Hearthpulse_Archetypes {
    const CRON_HOOK = 'unified_hs_refresh_hearthpulse_archetypes';
    const OPTION = 'hs_decks_hearthpulse_archetypes';
    const UPDATED_ACTION = 'unified_hs_hearthpulse_archetypes_updated';
    const BASE_URL = 'https://hearthpulse.net';
    const API_PATH = '/api/constructed-archetypes/teaser';

    /** Режимы колод на сайте → форматы HearthPulse. */
    const MODE_FORMATS = array(
        'standart' => 'standard',
        'volnyj' => 'wild',
    );

    private static $stored = null;

    public static function init() {
        add_action('init', array(__CLASS__, 'ensure_schedule'));
        add_action(self::CRON_HOOK, array(__CLASS__, 'refresh'));
    }

    public static function ensure_schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'twicedaily', self::CRON_HOOK);
        }
    }

    public static function format_for_mode_slugs(array $mode_slugs) {
        foreach ($mode_slugs as $slug) {
            $slug = (string) $slug;
            if (isset(self::MODE_FORMATS[$slug])) {
                return self::MODE_FORMATS[$slug];
            }
        }

        return '';
    }

    /**
     * @param string[] $names  Кандидаты в порядке приоритета: архетипы колоды, затем её название.
     * @param string   $format standard|wild.
     * @return array|null url, label (имя архетипа на HearthPulse или ''), matched.
     */
    public static function link_for_deck(array $names, $format) {
        if (!in_array($format, self::MODE_FORMATS, true)) {
            return null;
        }

        $stored = self::stored();
        $index = isset($stored['formats'][$format]) && is_array($stored['formats'][$format])
            ? $stored['formats'][$format]
            : array();

        foreach ($names as $name) {
            $key = self::name_key($name);
            if ($key === '' || !isset($index[$key]) || !is_array($index[$key])) {
                continue;
            }

            $slug = isset($index[$key][0]) ? (string) $index[$key][0] : '';
            if (!self::is_valid_slug($slug)) {
                continue;
            }

            return array(
                'url' => self::BASE_URL . '/standard/archetypes/' . $format . '/' . $slug . '/',
                'label' => isset($index[$key][1]) ? (string) $index[$key][1] : '',
                'matched' => true,
            );
        }

        return array(
            'url' => self::index_url($format),
            'label' => '',
            'matched' => false,
        );
    }

    public static function index_url($format) {
        $url = self::BASE_URL . '/standard/archetypes/';
        return $format === 'wild' ? $url . '?format=wild' : $url;
    }

    /**
     * Обновляет индекс. Формат, который не удалось загрузить, сохраняет
     * прежние данные, чтобы сбой HearthPulse не обнулял ссылки.
     */
    public static function refresh() {
        $stored = self::stored();
        $previous_hash = isset($stored['hash']) ? (string) $stored['hash'] : '';
        $fetched = 0;

        foreach (array_unique(array_values(self::MODE_FORMATS)) as $format) {
            $index = self::fetch_format($format);
            if ($index === null) {
                continue;
            }
            $stored['formats'][$format] = $index;
            $fetched++;
        }

        if ($fetched === 0) {
            return false;
        }

        ksort($stored['formats']);
        $stored['hash'] = md5(wp_json_encode($stored['formats']));
        $stored['fetched_at'] = time();
        update_option(self::OPTION, $stored, false);
        self::$stored = $stored;

        if ($stored['hash'] !== $previous_hash) {
            do_action(self::UPDATED_ACTION);
        }

        return true;
    }

    public static function name_key($name) {
        $name = html_entity_decode(wp_strip_all_tags((string) $name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
        $name = str_replace('ё', 'е', $name);
        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        $name = ' ' . trim((string) preg_replace('/\s+/u', ' ', $name)) . ' ';
        $name = str_replace(array(' death knight ', ' demon hunter '), array(' dk ', ' dh '), $name);

        return trim($name);
    }

    /** Сбрасывает кэш процесса; нужен тестам после записи опции. */
    public static function flush_runtime_cache() {
        self::$stored = null;
    }

    private static function stored() {
        if (self::$stored === null) {
            $stored = get_option(self::OPTION, array());
            if (!is_array($stored)) {
                $stored = array();
            }
            if (!isset($stored['formats']) || !is_array($stored['formats'])) {
                $stored['formats'] = array();
            }
            self::$stored = $stored;
        }

        return self::$stored;
    }

    private static function fetch_format($format) {
        $response = wp_safe_remote_get(
            add_query_arg('format', $format, self::BASE_URL . self::API_PATH),
            array(
                'timeout' => 10,
                'redirection' => 2,
                'headers' => array('Accept' => 'application/json'),
                'user-agent' => 'hs-manacost-decks/1.0 (+https://hs-manacost.ru)',
            )
        );
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return null;
        }

        return self::build_index($data['items']);
    }

    /**
     * Ключ — нормализованное имя (русское и английское), значение — [slug, русское имя].
     */
    public static function build_index(array $items) {
        $index = array();
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['slug'])) {
                continue;
            }
            $slug = strtolower((string) $item['slug']);
            if (!self::is_valid_slug($slug)) {
                continue;
            }

            $label = isset($item['archetypeLabel']) ? sanitize_text_field((string) $item['archetypeLabel']) : '';
            $name = isset($item['archetype']) ? sanitize_text_field((string) $item['archetype']) : '';
            $entry = array($slug, $label !== '' ? $label : $name);

            foreach (array($label, $name) as $candidate) {
                $key = self::name_key($candidate);
                if ($key !== '' && !isset($index[$key])) {
                    $index[$key] = $entry;
                }
            }
        }

        return empty($index) ? null : $index;
    }

    private static function is_valid_slug($slug) {
        return (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,95}$/', (string) $slug);
    }
}
