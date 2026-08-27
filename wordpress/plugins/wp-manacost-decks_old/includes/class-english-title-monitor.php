<?php
/**
 * Tracks deck posts that still have English titles and retries translation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_English_Title_Monitor {
    const CRON_HOOK = 'unified_hs_check_english_titles';
    const STATUS_META = '_hs_english_title_status';
    const SOURCE_META = '_hs_english_title_source';
    const CHECKED_META = '_hs_english_title_checked_at';
    const TRANSLATED_META = '_hs_english_title_translated_at';
    const DEFAULT_API_URL = 'http://127.0.0.1:5000/deckview-api/v1/archetypes?limit=500';
    const DEFAULT_TRANSLATIONS_URL = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRGMOTwzxCfcpQtX9jW9wVhrkqQIyU42ooWwhPaaOWy76XUes4ymwrshWs0ak_FlqGAm8g76Gluty4m/pubhtml';

    public static function init() {
        add_action('init', array(__CLASS__, 'ensure_schedule'));
        add_action('admin_menu', array(__CLASS__, 'add_admin_page'), 20);
        add_action('save_post_hs_deck', array(__CLASS__, 'track_on_save'), 90, 3);
        add_action('admin_post_unified_hs_scan_english_titles', array(__CLASS__, 'handle_manual_scan'));
        add_action(self::CRON_HOOK, array(__CLASS__, 'run_hourly_check'));
    }

    public static function ensure_schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
        }
    }

    public static function add_admin_page() {
        $capability = self::capability();

        add_submenu_page(
            'edit.php?post_type=hs_deck',
            'Английские названия',
            'Английские названия',
            $capability,
            'hs-decks-english-titles',
            array(__CLASS__, 'render_admin_page')
        );

        add_submenu_page(
            'unified-hs-plugins',
            'Английские названия',
            'Английские названия',
            $capability,
            'hs-decks-english-titles',
            array(__CLASS__, 'render_admin_page')
        );
    }

    public static function capability() {
        if (class_exists('Unified_HS_Capabilities')) {
            return Unified_HS_Capabilities::CAP_MANAGE_DECKS;
        }
        return 'manage_options';
    }

    public static function track_on_save($post_id, $post = null, $update = false) {
        unset($update);

        $post_id = absint($post_id);
        if (!$post_id || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        if (!$post) {
            $post = get_post($post_id);
        }
        if (!$post || $post->post_type !== 'hs_deck') {
            return;
        }

        self::track_post_state($post_id, $post->post_title);
    }

    public static function run_hourly_check() {
        // The scheduled monitor should not rewrite editorial titles without an
        // explicit admin action. It only marks candidates for the admin page.
        return self::scan_existing(1000, false);
    }

    public static function handle_manual_scan() {
        if (!current_user_can(self::capability())) {
            wp_die('Недостаточно прав.');
        }
        check_admin_referer('unified_hs_scan_english_titles');

        $result = self::scan_existing(5000, true);
        $redirect = add_query_arg(array(
            'post_type' => 'hs_deck',
            'page' => 'hs-decks-english-titles',
            'english_scan' => '1',
            'found' => absint($result['found']),
            'translated' => absint($result['translated']),
            'kept' => absint($result['kept']),
        ), admin_url('edit.php'));
        wp_safe_redirect($redirect);
        exit;
    }

    public static function scan_existing($limit = 1000, $translate = true) {
        $limit = max(1, min(10000, absint($limit)));
        $translations = $translate ? self::load_translations() : array();
        $result = array(
            'checked' => 0,
            'found' => 0,
            'translated' => 0,
            'kept' => 0,
            'resolved' => 0,
        );

        $post_ids = self::get_candidate_post_ids($limit);

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'hs_deck') {
                continue;
            }

            $result['checked']++;
            $state = self::track_post_state($post_id, $post->post_title);
            if ($state === 'open') {
                $result['found']++;
                $source_title = (string) get_post_meta($post_id, self::SOURCE_META, true);
                if ($source_title === '') {
                    $source_title = $post->post_title;
                }

                $translated = self::find_translation($source_title, $translations);
                if ($translate && $translated !== '' && $translated !== $post->post_title && self::has_cyrillic($translated)) {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_title' => $translated,
                        'post_name' => sanitize_title($translated),
                    ));
                    update_post_meta($post_id, self::STATUS_META, 'resolved');
                    update_post_meta($post_id, self::TRANSLATED_META, current_time('mysql'));
                    update_post_meta($post_id, self::CHECKED_META, current_time('mysql'));
                    $result['translated']++;
                } else {
                    update_post_meta($post_id, self::CHECKED_META, current_time('mysql'));
                    $result['kept']++;
                }
            } elseif ($state === 'resolved') {
                $result['resolved']++;
            }
        }

        update_option('unified_hs_english_titles_last_scan', array(
            'time' => current_time('mysql'),
            'result' => $result,
        ), false);

        return $result;
    }

    private static function get_candidate_post_ids($limit) {
        global $wpdb;

        $limit = max(1, min(10000, absint($limit)));
        $statuses = array('publish', 'private', 'draft', 'pending', 'future');
        $status_placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        $english_sql = $wpdb->prepare(
            "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = 'hs_deck'
               AND post_status IN ($status_placeholders)
               AND post_title REGEXP '[A-Za-z]'
               AND post_title NOT REGEXP '[А-Яа-яЁё]'
             ORDER BY ID DESC
             LIMIT %d",
            array_merge($statuses, array($limit))
        );
        $ids = array_map('absint', $wpdb->get_col($english_sql));

        $open_sql = $wpdb->prepare(
            "SELECT DISTINCT pm.post_id
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
               AND pm.meta_value = 'open'
               AND p.post_type = 'hs_deck'
               AND p.post_status IN ($status_placeholders)
             ORDER BY pm.post_id DESC
             LIMIT %d",
            array_merge(array(self::STATUS_META), $statuses, array($limit))
        );
        $open_ids = array_map('absint', $wpdb->get_col($open_sql));

        return array_values(array_unique(array_merge($ids, $open_ids)));
    }

    private static function track_post_state($post_id, $title) {
        $post_id = absint($post_id);
        $title = trim(wp_strip_all_tags((string) $title));
        $had_source = (string) get_post_meta($post_id, self::SOURCE_META, true);

        if (self::looks_english($title)) {
            if ($had_source === '') {
                update_post_meta($post_id, self::SOURCE_META, $title);
            }
            update_post_meta($post_id, self::STATUS_META, 'open');
            return 'open';
        }

        if ($had_source !== '' && self::has_cyrillic($title)) {
            update_post_meta($post_id, self::STATUS_META, 'resolved');
            if ((string) get_post_meta($post_id, self::TRANSLATED_META, true) === '') {
                update_post_meta($post_id, self::TRANSLATED_META, current_time('mysql'));
            }
            return 'resolved';
        }

        return 'ignored';
    }

    public static function render_admin_page() {
        if (!current_user_can(self::capability())) {
            wp_die('Недостаточно прав.');
        }

        $translations = self::load_translations();
        $open_posts = self::get_open_posts(300);
        $last_scan = get_option('unified_hs_english_titles_last_scan', array());
        $translations_meta = get_option('unified_hs_english_title_translations_meta', array());
        $scan_result = isset($last_scan['result']) && is_array($last_scan['result']) ? $last_scan['result'] : array();
        ?>
        <div class="wrap">
            <h1>Английские названия колод</h1>

            <?php if (isset($_GET['english_scan'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        Проверка завершена:
                        найдено <?php echo absint(isset($_GET['found']) ? $_GET['found'] : 0); ?>,
                        переведено <?php echo absint(isset($_GET['translated']) ? $_GET['translated'] : 0); ?>,
                        оставлено <?php echo absint(isset($_GET['kept']) ? $_GET['kept'] : 0); ?>.
                    </p>
                </div>
            <?php endif; ?>

            <p>Здесь собраны колоды, у которых текущий заголовок выглядит английским. Раз в час плагин сверяет их с Deckview API переводов и переименовывает только при найденном русском названии.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:16px 0;">
                <?php wp_nonce_field('unified_hs_scan_english_titles'); ?>
                <input type="hidden" name="action" value="unified_hs_scan_english_titles">
                <?php submit_button('Собрать и проверить сейчас', 'primary', 'submit', false); ?>
            </form>

            <p>
                <strong>Ожидают перевода:</strong> <?php echo count($open_posts); ?>.
                <strong>Переводов загружено:</strong> <?php echo count($translations); ?>.
                <?php if (!empty($translations_meta['source'])): ?>
                    <strong>Источник:</strong> <?php echo esc_html($translations_meta['source']); ?>.
                <?php endif; ?>
                <?php if (!empty($last_scan['time'])): ?>
                    <strong>Последняя проверка:</strong> <?php echo esc_html($last_scan['time']); ?>.
                <?php endif; ?>
                <?php if (!empty($scan_result)): ?>
                    <strong>Последний результат:</strong>
                    найдено <?php echo absint(isset($scan_result['found']) ? $scan_result['found'] : 0); ?>,
                    переведено <?php echo absint(isset($scan_result['translated']) ? $scan_result['translated'] : 0); ?>,
                    оставлено <?php echo absint(isset($scan_result['kept']) ? $scan_result['kept'] : 0); ?>.
                <?php endif; ?>
            </p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Английское название</th>
                        <th>Найденный русский перевод</th>
                        <th>Дата</th>
                        <th>Последняя проверка</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($open_posts)): ?>
                    <tr><td colspan="5">Английских названий в ожидании не найдено.</td></tr>
                <?php else: ?>
                    <?php foreach ($open_posts as $post): ?>
                        <?php
                        $source_title = (string) get_post_meta($post->ID, self::SOURCE_META, true);
                        if ($source_title === '') {
                            $source_title = $post->post_title;
                        }
                        $translation = self::find_translation($source_title, $translations);
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">#<?php echo absint($post->ID); ?></a></td>
                            <td><strong><?php echo esc_html($post->post_title); ?></strong><br><code><?php echo esc_html($source_title); ?></code></td>
                            <td><?php echo $translation !== '' ? esc_html($translation) : '<span style="color:#777;">пока нет</span>'; ?></td>
                            <td><?php echo esc_html(get_the_date('d.m.Y H:i', $post)); ?></td>
                            <td><?php echo esc_html((string) get_post_meta($post->ID, self::CHECKED_META, true)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function get_open_posts($limit = 300) {
        return get_posts(array(
            'post_type' => 'hs_deck',
            'post_status' => array('publish', 'private', 'draft', 'pending', 'future'),
            'posts_per_page' => max(1, absint($limit)),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => self::STATUS_META,
                    'value' => 'open',
                    'compare' => '=',
                ),
            ),
        ));
    }

    private static function load_translations() {
        $cached = get_transient('unified_hs_english_title_translations');
        if (is_array($cached)) {
            return $cached;
        }

        $translations = self::load_translations_from_api();
        if (!empty($translations)) {
            update_option('unified_hs_english_title_translations_meta', array(
                'source' => 'Deckview API',
                'count' => count($translations),
                'time' => current_time('mysql'),
            ), false);
            set_transient('unified_hs_english_title_translations', $translations, 5 * MINUTE_IN_SECONDS);
            return $translations;
        }

        $url = apply_filters('unified_hs_english_title_translations_url', self::DEFAULT_TRANSLATIONS_URL);
        $csv_url = strpos($url, '/pubhtml') !== false ? str_replace('/pubhtml', '/pub?output=csv', $url) : $url;
        $response = wp_remote_get($csv_url, array(
            'timeout' => 15,
            'headers' => array('User-Agent' => 'Manacost-English-Title-Monitor/1.0'),
        ));

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            update_option('unified_hs_english_title_translations_meta', array(
                'source' => 'unavailable',
                'count' => 0,
                'time' => current_time('mysql'),
            ), false);
            return array();
        }

        $translations = self::parse_translations_csv((string) wp_remote_retrieve_body($response));
        update_option('unified_hs_english_title_translations_meta', array(
            'source' => 'Google Sheet fallback',
            'count' => count($translations),
            'time' => current_time('mysql'),
        ), false);
        set_transient('unified_hs_english_title_translations', $translations, 15 * MINUTE_IN_SECONDS);
        return $translations;
    }

    private static function load_translations_from_api() {
        $url = apply_filters('unified_hs_english_title_api_url', self::DEFAULT_API_URL);
        $response = wp_remote_get($url, array(
            'timeout' => 8,
            'headers' => array('User-Agent' => 'Manacost-English-Title-Monitor/1.0'),
        ));

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }

        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($payload) || empty($payload['success']) || empty($payload['items']) || !is_array($payload['items'])) {
            return array();
        }

        $translations = array();
        foreach ($payload['items'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $eng = isset($row['name_en']) ? trim((string) $row['name_en']) : '';
            $rus = isset($row['name_ru']) ? trim((string) $row['name_ru']) : '';
            if ($eng === '' || $rus === '') {
                continue;
            }
            foreach (self::translation_keys($eng) as $key) {
                if ($key !== '') {
                    $translations[$key] = $rus;
                }
            }
        }

        return $translations;
    }

    private static function parse_translations_csv($csv) {
        $translations = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) $csv) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            if (count($row) >= 3) {
                $eng = trim((string) $row[1], " \t\n\r\0\x0B\"");
                $rus = trim((string) $row[2], " \t\n\r\0\x0B\"");
            } elseif (count($row) >= 2) {
                $eng = trim((string) $row[0], " \t\n\r\0\x0B\"");
                $rus = trim((string) $row[1], " \t\n\r\0\x0B\"");
            } else {
                continue;
            }

            if ($eng === '' || $rus === '') {
                continue;
            }
            $eng_lc = function_exists('mb_strtolower') ? mb_strtolower($eng, 'UTF-8') : strtolower($eng);
            if (strpos($eng_lc, 'англ') !== false || strpos($eng_lc, 'названия') !== false) {
                continue;
            }
            foreach (self::translation_keys($eng) as $key) {
                if ($key !== '') {
                    $translations[$key] = $rus;
                }
            }
        }
        return $translations;
    }

    private static function find_translation($name, array $translations) {
        if ($name === '' || empty($translations)) {
            return '';
        }
        $candidate_keys = self::translation_keys($name);
        foreach ($candidate_keys as $key) {
            if (isset($translations[$key])) {
                return trim((string) $translations[$key]);
            }
        }

        uksort($translations, static function($a, $b) {
            return strlen($b) - strlen($a);
        });

        $primary_key = reset($candidate_keys);
        foreach ($translations as $eng => $rus) {
            if ($eng === '') {
                continue;
            }
            if (strpos($primary_key, $eng) !== false && self::has_enough_specificity($eng)) {
                return trim((string) $rus);
            }
            if (strpos($eng, $primary_key) !== false && self::has_enough_specificity($primary_key)) {
                return trim((string) $rus);
            }
        }
        return '';
    }

    private static function translation_keys($value) {
        $base = self::normalize_key($value);
        if ($base === '') {
            return array();
        }

        $keys = array($base);
        $keys[] = self::apply_aliases($base);
        $keys[] = self::apply_aliases($base, true);

        return array_values(array_unique(array_filter($keys)));
    }

    private static function apply_aliases($key, $compact = false) {
        $key = ' ' . trim((string) $key) . ' ';
        $replacements = array(
            ' demon hunter ' => ' dh ',
            ' death knight ' => ' dk ',
            ' highlander ' => ' hl ',
            ' renathal ' => ' xl ',
            ' reno ' => ' hl ',
        );

        foreach ($replacements as $from => $to) {
            $key = str_replace($from, $to, $key);
        }

        if ($compact) {
            $key = preg_replace('/\b(xl|hl)\s+(xl|hl)\b/', '$1 $2', $key);
        }

        return trim(preg_replace('/\s+/u', ' ', $key));
    }

    private static function has_enough_specificity($key) {
        $tokens = array_filter(explode(' ', trim((string) $key)));
        $generic = array('dh', 'dk', 'druid', 'hunter', 'mage', 'paladin', 'priest', 'rogue', 'shaman', 'warlock', 'warrior');
        $meaningful = array_values(array_diff($tokens, $generic));

        return count($tokens) >= 2 && count($meaningful) >= 1;
    }

    private static function normalize_key($value) {
        $value = wp_strip_all_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = preg_replace('/[^0-9\p{L}]+/u', ' ', $value);
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private static function looks_english($title) {
        $title = trim((string) $title);
        return $title !== '' && preg_match('/[A-Za-z]/', $title) && !self::has_cyrillic($title);
    }

    private static function has_cyrillic($value) {
        return (bool) preg_match('/[А-Яа-яЁё]/u', (string) $value);
    }
}
