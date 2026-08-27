<?php
/**
 * Автообновление основного словаря из HearthstoneJSON API.
 *
 * Без морфологии: пишем только нормализованную форму имени в by_name.
 * Все тяжёлые операции (HTTP, JSON-парсинг) живут только здесь и
 * вызываются из admin_post / WP-Cron — фронт не задевают.
 *
 * @package HS_Smart_Tooltip
 */

if (!defined('ABSPATH')) {
    exit;
}

const HS_TOOLTIP_HSJSON_API_URL   = 'https://api.hearthstonejson.com/v1/latest/ruRU/cards.collectible.json';
const HS_TOOLTIP_HSJSON_RENDER_BASE = 'https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/';
const HS_TOOLTIP_MAIN_CRON_HOOK   = 'hs_tooltip_main_dict_cron';
const HS_TOOLTIP_MAIN_LOCK_KEY    = 'hs_tooltip_main_rebuild_lock';
const HS_TOOLTIP_MAIN_SETTINGS    = 'hs_tooltip_main_autoupdate';
const HS_TOOLTIP_MAIN_MIN_CARDS   = 2000;        // health-check absolute floor
const HS_TOOLTIP_MAIN_MIN_RATIO   = 0.8;         // health-check vs previous count

/**
 * @return array{auto_update: bool, last_run: int, last_status: string, last_count: int, last_message: string}
 */
function hs_smart_tooltip_main_settings(): array
{
    $defaults = [
        'auto_update'  => false,
        'last_run'     => 0,
        'last_status'  => '',
        'last_count'   => 0,
        'last_message' => '',
    ];
    $stored = get_option(HS_TOOLTIP_MAIN_SETTINGS, []);
    if (!is_array($stored)) {
        $stored = [];
    }
    return array_merge($defaults, $stored);
}

function hs_smart_tooltip_main_settings_update(array $patch): void
{
    $current = hs_smart_tooltip_main_settings();
    update_option(HS_TOOLTIP_MAIN_SETTINGS, array_merge($current, $patch), false);
}

/**
 * Маппинг HSJSON set-кодов → имена SVG-файлов в /set_icon/.
 * Неизвестные сеты возвращают '' (set_icon не записывается в словарь).
 *
 * @return array<string, string>
 */
function hs_smart_tooltip_set_icon_map(): array
{
    return [
        // CORE — ротационный базовый сет, в текущем году "Год Скарабея".
        'CORE'                      => 'Year_of_the_Scarab_-_SVG_logo.svg',
        'HOF'                       => "Hall_of_Fame_-_SVG_logo.svg",
        'GVG'                       => "Goblins_vs_Gnomes_-_SVG_logo.svg",
        'TGT'                       => "The_Grand_Tournament_-_SVG_logo.svg",
        'OG'                        => "Whispers_of_the_Old_Gods_-_SVG_logo.svg",
        'GANGS'                     => "Mean_Streets_of_Gadgetzan_-_SVG_logo.svg",
        'UNGORO'                    => 'Journey_to_UnGoro_-_SVG_logo.svg',
        'THE_LOST_CITY'             => 'The_Lost_City_of_UnGoro_-_SVG_logo.svg',
        'ICECROWN'                  => "Knights_of_the_Frozen_Throne_-_SVG_logo.svg",
        'LOOTAPALOOZA'              => "Kobolds_and_Catacombs_-_SVG_logo.svg",
        'GILNEAS'                   => "The_Witchwood_-_SVG_logo.svg",
        'BOOMSDAY'                  => "The_Boomsday_Project_-_SVG_logo.svg",
        'TROLL'                     => 'Rastakhans_Rumble_-_SVG_logo.svg',
        'DALARAN'                   => "Rise_of_Shadows_-_SVG_logo.svg",
        'ULDUM'                     => "Saviors_of_Uldum_-_SVG_logo.svg",
        'DRAGONS'                   => "Descent_of_Dragons_-_SVG_logo.svg",
        'BLACK_TEMPLE'              => "Ashes_of_Outland_-_SVG_logo.svg",
        'DEMON_HUNTER_INITIATE'     => "Demon_Hunter_Initiate_-_SVG_logo.svg",
        'SCHOLOMANCE'               => "Scholomance_Academy_-_SVG_logo.svg",
        'DARKMOON_FAIRE'            => "Madness_at_the_Darkmoon_Faire_-_SVG_logo.svg",
        'THE_BARRENS'               => "Forged_in_the_Barrens_-_SVG_logo.svg",
        'STORMWIND'                 => "United_in_Stormwind_-_SVG_logo.svg",
        'ALTERAC_VALLEY'            => "Fractured_in_Alterac_Valley_-_SVG_logo.svg",
        'THE_SUNKEN_CITY'           => "Voyage_to_the_Sunken_City_-_SVG_logo.svg",
        'REVENDRETH'                => "Murder_at_Castle_Nathria_-_SVG_logo.svg",
        'RETURN_OF_THE_LICH_KING'   => "March_of_the_Lich_King_-_SVG_logo.svg",
        'PATH_OF_ARTHAS'            => "Path_of_Arthas_-_SVG_logo.svg",
        'BATTLE_OF_THE_BANDS'       => "Festival_of_Legends_-_SVG_logo.svg",
        'TITANS'                    => "TITANS_-_SVG_logo.svg",
        'WILD_WEST'                 => "Showdown_in_the_Badlands_-_SVG_logo.svg",
        'BADLANDS'                  => "Showdown_in_the_Badlands_-_SVG_logo.svg",
        'WHIZBANGS_WORKSHOP'        => 'Whizbangs_Workshop_-_SVG_logo.svg',
        'ISLAND_VACATION'           => "Perils_in_Paradise_-_SVG_logo.svg",
        'PERILS_IN_PARADISE'        => "Perils_in_Paradise_-_SVG_logo.svg",
        'EMERALD_DREAM'             => "Into_the_Emerald_Dream_-_SVG_logo.svg",
        'ESCAPEFROM_VIOLET_HOLD'    => "Escape_from_Violet_Hold_-_SVG_logo.svg",
        'GREAT_DARK_BEYOND'         => "The_Great_Dark_Beyond_-_SVG_logo.svg",
        'SPACE'                     => "The_Great_Dark_Beyond_-_SVG_logo.svg",
        'TIME_TRAVEL'               => "Across_the_Timeways_-_SVG_logo.svg",
        'EVENT'                     => "Event_-_SVG_logo.svg",
        'CAVERNS_OF_TIME'           => "Caverns_of_Time_-_SVG_logo.svg",
        'CATACLYSM'                 => "CATACLYSM_-_SVG_logo.svg",
    ];
}

/**
 * Нормализация: lower + схлопывание пробелов + кириллические confusables.
 */
function hs_smart_tooltip_dict_key(string $name): string
{
    $name = trim($name);
    if (function_exists('mb_strtolower')) {
        $name = mb_strtolower($name, 'UTF-8');
    } else {
        $name = strtolower($name);
    }
    $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
    return $name;
}

/**
 * Маппит HSJSON-rarity (FREE/COMMON/RARE/EPIC/LEGENDARY) → внутренний словарь.
 */
function hs_smart_tooltip_normalize_rarity(string $rarity): string
{
    $rarity = strtoupper($rarity);
    return match ($rarity) {
        'LEGENDARY' => 'legendary',
        'EPIC'      => 'epic',
        'RARE'      => 'rare',
        default     => 'common',
    };
}

/**
 * Преобразует один элемент HSJSON в запись словаря или null если фильтруется.
 *
 * @param array<string, mixed> $card
 * @param array<string, string> $set_icons
 * @return array{id: string, dbf: string, entry: array{img: string, rarity: string, name: string, set_icon?: string}}|null
 */
function hs_smart_tooltip_card_to_entry(array $card, array $set_icons): ?array
{
    $id   = isset($card['id']) && is_string($card['id']) ? $card['id'] : '';
    $name = isset($card['name']) && is_string($card['name']) ? trim($card['name']) : '';
    if ($id === '' || $name === '') {
        return null;
    }
    $dbf = isset($card['dbfId']) ? (string) $card['dbfId'] : '';
    $set = isset($card['set']) && is_string($card['set']) ? $card['set'] : '';
    // BATTLEGROUNDS — у нас отдельный словарь.
    // HERO_SKINS — альтернативные арт-варианты героев (~700 штук).
    // PLACEHOLDER_202204 — плейсхолдеры удалённого/непоказываемого контента.
    $excluded_sets = ['BATTLEGROUNDS', 'HERO_SKINS', 'PLACEHOLDER_202204'];
    if (in_array($set, $excluded_sets, true)) {
        return null;
    }
    $type = isset($card['type']) && is_string($card['type']) ? $card['type'] : '';
    $allowed_types = ['MINION', 'SPELL', 'WEAPON', 'HERO', 'LOCATION'];
    if (!in_array($type, $allowed_types, true)) {
        return null;
    }
    // Безопасный fallback: если карта помечена как BG-вариант обычной карты — пропустить.
    if (!empty($card['battlegroundsNormalDbfId'])) {
        return null;
    }

    $rarity = isset($card['rarity']) && is_string($card['rarity'])
        ? hs_smart_tooltip_normalize_rarity($card['rarity'])
        : 'common';

    $entry = [
        'img'    => HS_TOOLTIP_HSJSON_RENDER_BASE . $id . '.png',
        'rarity' => $rarity,
        'name'   => $name,
    ];
    if ($set !== '' && isset($set_icons[$set]) && $set_icons[$set] !== '') {
        $entry['set_icon'] = $set_icons[$set];
    }

    return ['id' => $id, 'dbf' => $dbf, 'entry' => $entry];
}

/**
 * Собирает новый словарь из ответа HSJSON.
 *
 * @param array<int, array<string, mixed>> $cards
 * @return array{by_id: array<string, array>, by_name: array<string, array>, count: int}
 */
function hs_smart_tooltip_build_dictionary_from_cards(array $cards): array
{
    $set_icons = hs_smart_tooltip_set_icon_map();
    $core_icon = $set_icons['CORE'] ?? '';
    $rarity_priority = ['common' => 0, 'rare' => 1, 'epic' => 2, 'legendary' => 3];

    // Pre-pass: соберём ID оригиналов, которые перепечатаны в текущем CORE-сете.
    // CORE-карты имеют ID вида `CORE_<orig>` (или `Core_<orig>`); стрипаем
    // префикс — получаем ID оригинала. Когда позже встретим оригинал с тем
    // же ID, навешиваем ему CORE-иконку: так карта в посте подсвечивается
    // тем сетом, в котором она реально играется в текущем формате.
    $core_originals = [];
    foreach ($cards as $c) {
        if (!is_array($c)) {
            continue;
        }
        if (($c['set'] ?? '') !== 'CORE') {
            continue;
        }
        $cid = isset($c['id']) && is_string($c['id']) ? $c['id'] : '';
        if ($cid === '') {
            continue;
        }
        if (preg_match('/^[Cc][Oo][Rr][Ee]_(.+)$/', $cid, $m)) {
            $core_originals[$m[1]] = true;
        }
    }

    $by_id = [];
    $by_name = [];
    $name_owners = []; // key => rarity-priority for collision resolution.
    $count = 0;        // distinct cards (canonical id only)

    foreach ($cards as $card) {
        if (!is_array($card)) {
            continue;
        }
        $built = hs_smart_tooltip_card_to_entry($card, $set_icons);
        if ($built === null) {
            continue;
        }
        $id = $built['id'];
        $entry = $built['entry'];

        // Если есть CORE-перепечатка с такой же базой ID — навешиваем CORE-иконку.
        if ($core_icon !== '' && isset($core_originals[$id])) {
            $entry['set_icon'] = $core_icon;
        }

        $by_id[$id] = $entry;
        $count++;
        // Алиас по dbfId — для обратной совместимости со старыми постами,
        // где шорткод мог быть вставлен с числовым ID.
        if ($built['dbf'] !== '' && !isset($by_id[$built['dbf']])) {
            $by_id[$built['dbf']] = $entry;
        }

        $key = hs_smart_tooltip_dict_key($entry['name']);
        if ($key === '') {
            continue;
        }
        $current_priority = $rarity_priority[$entry['rarity']] ?? 0;
        if (!isset($by_name[$key]) || $current_priority > ($name_owners[$key] ?? -1)) {
            $by_name[$key] = $entry;
            $name_owners[$key] = $current_priority;
        }
    }

    return ['by_id' => $by_id, 'by_name' => $by_name, 'count' => $count];
}

/**
 * Атомарная запись JSON: tmp → rename. Возвращает true если успешно.
 */
function hs_smart_tooltip_atomic_write_json(string $path, array $data): bool
{
    $payload = wp_json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return false;
    }
    $tmp = $path . '.tmp';
    $written = @file_put_contents($tmp, $payload, LOCK_EX);
    if ($written === false || $written === 0) {
        @unlink($tmp);
        return false;
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Главная процедура: тянет HSJSON, собирает словарь, проходит health-check,
 * делает бэкап и атомарно перезаписывает hs_dictionary.json.
 *
 * @return array{ok: bool, count: int, message: string}
 */
function hs_smart_tooltip_rebuild_main_dictionary(): array
{
    if (get_transient(HS_TOOLTIP_MAIN_LOCK_KEY)) {
        return ['ok' => false, 'count' => 0, 'message' => 'rebuild уже выполняется (lock)'];
    }
    set_transient(HS_TOOLTIP_MAIN_LOCK_KEY, time(), 30 * MINUTE_IN_SECONDS);

    try {
        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }

        $response = wp_safe_remote_get(HS_TOOLTIP_HSJSON_API_URL, [
            'timeout'    => 120,
            'user-agent' => 'HS-Smart-Tooltip/' . HS_SMART_TOOLTIP_VERSION,
        ]);
        if (is_wp_error($response)) {
            return ['ok' => false, 'count' => 0, 'message' => 'HTTP error: ' . $response->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return ['ok' => false, 'count' => 0, 'message' => 'HSJSON HTTP ' . $code];
        }
        $body = wp_remote_retrieve_body($response);
        unset($response);
        if ($body === '') {
            return ['ok' => false, 'count' => 0, 'message' => 'пустой ответ HSJSON'];
        }

        $cards = json_decode($body, true);
        unset($body);
        if (!is_array($cards)) {
            return ['ok' => false, 'count' => 0, 'message' => 'невалидный JSON HSJSON'];
        }

        $built = hs_smart_tooltip_build_dictionary_from_cards($cards);
        unset($cards);
        $count = (int) $built['count'];
        if ($count < HS_TOOLTIP_MAIN_MIN_CARDS) {
            return ['ok' => false, 'count' => $count, 'message' => sprintf('health-check: %d < минимума %d', $count, HS_TOOLTIP_MAIN_MIN_CARDS)];
        }

        // Сравнение с предыдущим словарём — защита от частичной выдачи API.
        // Считаем только канонические ID (XXX_NNN), игнорируем dbfId-алиасы.
        $dict_path = dirname(__DIR__) . '/' . 'hs_dictionary.json';
        if (file_exists($dict_path)) {
            $prev_raw = @file_get_contents($dict_path);
            $prev = $prev_raw !== false ? json_decode($prev_raw, true) : null;
            unset($prev_raw);
            if (is_array($prev) && isset($prev['by_id']) && is_array($prev['by_id'])) {
                $prev_count = 0;
                foreach (array_keys($prev['by_id']) as $k) {
                    if (is_string($k) && preg_match('/^[A-Z][A-Z0-9_]+$/', $k)) {
                        $prev_count++;
                    }
                }
                if ($prev_count > 0 && $count < (int) ($prev_count * HS_TOOLTIP_MAIN_MIN_RATIO)) {
                    return [
                        'ok' => false,
                        'count' => $count,
                        'message' => sprintf('health-check: %d карт < %d%% от прошлого (%d)', $count, (int) (HS_TOOLTIP_MAIN_MIN_RATIO * 100), $prev_count),
                    ];
                }
            }
            unset($prev);

            // Бэкап только перед фактической перезаписью.
            $backup = dirname(__DIR__) . '/' . 'hs_dictionary.bak.json';
            @copy($dict_path, $backup);
        }

        // Записываем только нужные ключи (без 'count').
        $payload = ['by_id' => $built['by_id'], 'by_name' => $built['by_name']];
        if (!hs_smart_tooltip_atomic_write_json($dict_path, $payload)) {
            return ['ok' => false, 'count' => $count, 'message' => 'не удалось записать hs_dictionary.json'];
        }

        wp_cache_delete('dictionary_slim', HS_TOOLTIP_CACHE_GROUP);
        wp_cache_delete('dictionary_full', HS_TOOLTIP_CACHE_GROUP);

        return ['ok' => true, 'count' => $count, 'message' => ''];
    } finally {
        delete_transient(HS_TOOLTIP_MAIN_LOCK_KEY);
    }
}

/**
 * Запуск + запись результата в settings/log. Используется и admin-post, и cron.
 */
function hs_smart_tooltip_run_main_rebuild(string $trigger): array
{
    $result = hs_smart_tooltip_rebuild_main_dictionary();
    hs_smart_tooltip_main_settings_update([
        'last_run'     => time(),
        'last_status'  => $result['ok'] ? 'ok' : 'err',
        'last_count'   => (int) $result['count'],
        'last_message' => (string) $result['message'],
    ]);
    if ($result['ok']) {
        hs_smart_tooltip_add_log(sprintf('Основной словарь обновлён из HSJSON (%s): %d карт.', $trigger, $result['count']), 'info');
    } else {
        hs_smart_tooltip_add_log(sprintf('Ошибка обновления основного словаря (%s): %s', $trigger, $result['message']), 'error');
    }
    return $result;
}

/* ------------------------- admin_post handler ------------------------- */

function hs_smart_tooltip_handle_main_rebuild(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Недостаточно прав.', 'hs-smart-tooltip'));
    }
    if (!isset($_POST['hs_tooltip_main_nonce']) || !wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['hs_tooltip_main_nonce'])),
        'hs_tooltip_main_rebuild'
    )) {
        wp_die(esc_html__('Неверный nonce.', 'hs-smart-tooltip'));
    }

    // Опциональный toggle авто-обновления.
    if (isset($_POST['hs_tooltip_main_auto'])) {
        $auto = $_POST['hs_tooltip_main_auto'] === '1';
        hs_smart_tooltip_main_settings_update(['auto_update' => $auto]);
        hs_smart_tooltip_main_reschedule_cron($auto);
    }

    $do_rebuild = isset($_POST['hs_tooltip_main_run']) && $_POST['hs_tooltip_main_run'] === '1';
    $status = 'main_saved';
    if ($do_rebuild) {
        $result = hs_smart_tooltip_run_main_rebuild('admin');
        if ($result['ok']) {
            $status = 'main_ok';
        } elseif (str_contains($result['message'], 'lock')) {
            $status = 'main_locked';
        } elseif (str_contains($result['message'], 'health-check')) {
            $status = 'main_health_fail';
        } else {
            $status = 'main_err';
        }
    }

    $redirect = admin_url('options-general.php?page=hs-smart-tooltip&hs_tooltip_status=' . $status);
    wp_safe_redirect($redirect);
    exit;
}

add_action('admin_post_hs_tooltip_main_rebuild', 'hs_smart_tooltip_handle_main_rebuild');

/* ----------------------------- WP-Cron ----------------------------- */

function hs_smart_tooltip_main_cron_callback(): void
{
    $settings = hs_smart_tooltip_main_settings();
    if (empty($settings['auto_update'])) {
        return;
    }
    hs_smart_tooltip_run_main_rebuild('cron');
}

add_action(HS_TOOLTIP_MAIN_CRON_HOOK, 'hs_smart_tooltip_main_cron_callback');

function hs_smart_tooltip_main_reschedule_cron(bool $enabled): void
{
    $next = wp_next_scheduled(HS_TOOLTIP_MAIN_CRON_HOOK);
    if ($enabled) {
        if (!$next) {
            // +random offset so multiple sites не бьют API одновременно.
            $offset = wp_rand(0, 6 * HOUR_IN_SECONDS);
            wp_schedule_event(time() + $offset, 'daily', HS_TOOLTIP_MAIN_CRON_HOOK);
        }
    } else {
        if ($next) {
            wp_unschedule_event($next, HS_TOOLTIP_MAIN_CRON_HOOK);
        }
    }
}

/**
 * Поддерживаем расписание в актуальном состоянии при загрузке плагина:
 * включено в настройках, но событие не запланировано → планируем.
 *
 * Хот-пас (расписание уже стоит) ограничен одной проверкой по
 * autoloaded `cron`-опции, без чтения нашей опции настроек.
 */
function hs_smart_tooltip_main_ensure_cron(): void
{
    if (wp_next_scheduled(HS_TOOLTIP_MAIN_CRON_HOOK)) {
        return;
    }
    $settings = hs_smart_tooltip_main_settings();
    if (!empty($settings['auto_update'])) {
        hs_smart_tooltip_main_reschedule_cron(true);
    }
}

add_action('init', 'hs_smart_tooltip_main_ensure_cron');

/**
 * При деактивации плагина чистим расписание.
 */
function hs_smart_tooltip_main_deactivate(): void
{
    hs_smart_tooltip_main_reschedule_cron(false);
    delete_transient(HS_TOOLTIP_MAIN_LOCK_KEY);
    hs_smart_tooltip_bgs_reschedule_cron(false);
}

register_deactivation_hook(
    dirname(__DIR__) . '/' . 'hs-smart-tooltip.php',
    'hs_smart_tooltip_main_deactivate'
);

/* ===================== BG-словарь: WP-Cron ===================== */

const HS_TOOLTIP_BGS_CRON_HOOK     = 'hs_tooltip_bgs_dict_cron';
const HS_TOOLTIP_BGS_SETTINGS_KEY  = 'hs_tooltip_bgs_autoupdate';

/**
 * @return array{auto_update: bool, last_run: int, last_status: string, last_count: int}
 */
function hs_smart_tooltip_bgs_settings(): array
{
    $defaults = [
        'auto_update'  => true,   // по умолчанию включено: BG-карты часто меняют тиры
        'last_run'     => 0,
        'last_status'  => '',
        'last_count'   => 0,
    ];
    $stored = get_option(HS_TOOLTIP_BGS_SETTINGS_KEY, []);
    if (!is_array($stored)) {
        $stored = [];
    }
    return array_merge($defaults, $stored);
}

function hs_smart_tooltip_bgs_settings_update(array $patch): void
{
    update_option(
        HS_TOOLTIP_BGS_SETTINGS_KEY,
        array_merge(hs_smart_tooltip_bgs_settings(), $patch),
        false
    );
}

function hs_smart_tooltip_bgs_cron_callback(): void
{
    $settings = hs_smart_tooltip_bgs_settings();
    if (empty($settings['auto_update'])) {
        return;
    }
    $result = hs_smart_tooltip_rebuild_bgs_dictionary();
    hs_smart_tooltip_bgs_settings_update([
        'last_run'    => time(),
        'last_status' => $result['ok'] ? 'ok' : 'err',
        'last_count'  => (int) $result['count'],
    ]);
    if ($result['ok']) {
        hs_smart_tooltip_add_log(sprintf('BG-словарь обновлён cron-ом: %d карт.', $result['count']), 'info');
    } else {
        hs_smart_tooltip_add_log('Ошибка cron-обновления BG: ' . $result['message'], 'error');
    }
}

add_action(HS_TOOLTIP_BGS_CRON_HOOK, 'hs_smart_tooltip_bgs_cron_callback');

function hs_smart_tooltip_bgs_reschedule_cron(bool $enabled): void
{
    $next = wp_next_scheduled(HS_TOOLTIP_BGS_CRON_HOOK);
    if ($enabled) {
        if (!$next) {
            $offset = wp_rand(0, 6 * HOUR_IN_SECONDS);
            wp_schedule_event(time() + $offset, 'daily', HS_TOOLTIP_BGS_CRON_HOOK);
        }
    } else {
        if ($next) {
            wp_unschedule_event($next, HS_TOOLTIP_BGS_CRON_HOOK);
        }
    }
}

function hs_smart_tooltip_bgs_ensure_cron(): void
{
    if (wp_next_scheduled(HS_TOOLTIP_BGS_CRON_HOOK)) {
        return;
    }
    $settings = hs_smart_tooltip_bgs_settings();
    if (!empty($settings['auto_update'])) {
        hs_smart_tooltip_bgs_reschedule_cron(true);
    }
}

add_action('init', 'hs_smart_tooltip_bgs_ensure_cron');
