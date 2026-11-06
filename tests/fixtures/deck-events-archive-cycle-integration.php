<?php
/** One step of ops/integration/deck-events-archive-cycle.sh, chosen by HS_ARCHIVE_STEP. */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/includes/class-deck-events.php';

global $wpdb;

if (!function_exists('hs_integration_assert')) {
    function hs_integration_assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
}

$cycle_table = Unified_HS_Deck_Events::table_name();
$cycle_day = (string) $wpdb->get_var('SELECT CURDATE() - INTERVAL 130 DAY');
$cycle_rows = static function () use ($wpdb, $cycle_table, $cycle_day): array {
    $wpdb->query('SET SESSION group_concat_max_len = 1048576');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) AS row_count,
                SHA2(GROUP_CONCAT(CONCAT_WS(0x1f, id, post_id, event_type, event_time, visitor_hash, source, IFNULL(context, 0x00)) ORDER BY id SEPARATOR 0x1e), 256) AS checksum
         FROM `{$cycle_table}` WHERE event_time >= %s AND event_time < %s + INTERVAL 1 DAY",
        $cycle_day . ' 00:00:00',
        $cycle_day
    ), ARRAY_A);
};

switch (getenv('HS_ARCHIVE_STEP')) {
    case 'prepare':
        Unified_HS_Deck_Events::install();
        $wpdb->query($wpdb->prepare("DELETE FROM `{$cycle_table}` WHERE event_time < %s", $cycle_day . ' 00:00:00'));
        foreach ([
            [11, 'deck_view', '01:02:03', str_repeat('b', 64), "it's \\ \"quoted\"\n\ttab", null],
            [0, 'filter_apply', '12:00:00', '', 'rest', '{"class":"Маг","note":"\u{1F600}"}'],
            [12, 'deck_copy', '23:59:59', str_repeat('c', 64), '', ''],
        ] as [$post_id, $event_type, $time, $visitor, $source, $context]) {
            $wpdb->insert($cycle_table, [
                'post_id' => $post_id,
                'event_type' => $event_type,
                'event_time' => $cycle_day . ' ' . $time,
                'visitor_hash' => $visitor,
                'source' => $source,
                'context' => $context,
            ]);
        }
        update_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION, (string) $wpdb->get_var('SELECT CURDATE() - INTERVAL 90 DAY'), false);
        update_option('hs_integration_archive_rows', $cycle_rows(), false);
        echo $cycle_day . "\n";
        break;

    case 'delete':
        Unified_HS_Deck_Events::run_retention();
        hs_integration_assert((int) $cycle_rows()['row_count'] === 0, 'retention kept the archived day');
        break;

    case 'verify':
        hs_integration_assert($cycle_rows() === get_option('hs_integration_archive_rows'), 'restored rows differ from the archived ones');
        hs_integration_assert((int) $cycle_rows()['row_count'] === 3, 'restore did not bring back every row');
        echo "Deck events archive restore assertions: OK\n";
        break;

    default:
        throw new RuntimeException('unknown HS_ARCHIVE_STEP');
}
