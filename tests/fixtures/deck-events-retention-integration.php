<?php
/** Integration assertions for deck events retention and the daily rollup. */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/includes/class-deck-events.php';

global $wpdb;

Unified_HS_Deck_Events::install();

$retention_table = Unified_HS_Deck_Events::table_name();
$retention_days = Unified_HS_Deck_Events::days_table_name();
$retention_archives = Unified_HS_Deck_Events::archives_table_name();
$wpdb->query("DELETE FROM `{$retention_table}`");
$wpdb->query("DELETE FROM `{$retention_days}`");
$wpdb->query("DELETE FROM `{$retention_archives}`");
delete_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION);

if (!taxonomy_exists('deck_archetype')) {
    register_taxonomy('deck_archetype', 'post');
}
$retention_post = (int) wp_insert_post(['post_title' => 'Retention deck', 'post_status' => 'publish']);
$retention_term = wp_insert_term('Retention archetype', 'deck_archetype');
$retention_term_id = (int) $retention_term['term_id'];
wp_set_object_terms($retention_post, [$retention_term_id], 'deck_archetype');

$retention_day = static function (int $days_ago) use ($wpdb): string {
    return (string) $wpdb->get_var($wpdb->prepare('SELECT CURDATE() - INTERVAL %d DAY', $days_ago));
};
$retention_insert = static function (int $post_id, string $event_type, int $days_ago, int $count) use ($wpdb, $retention_table, $retention_day): void {
    for ($i = 0; $i < $count; $i++) {
        $wpdb->insert($retention_table, [
            'post_id' => $post_id,
            'event_type' => $event_type,
            'event_time' => $retention_day($days_ago) . ' 12:00:00',
        ]);
    }
};
$retention_insert($retention_post, 'deck_view', 120, 2);
$retention_insert($retention_post, 'deck_copy', 95, 1);
$retention_insert($retention_post, 'deck_view', 91, 1);
$retention_insert(0, 'filter_apply', 120, 1);
$retention_insert($retention_post, 'deck_view', 10, 3);
$retention_insert($retention_post, 'deck_copy', 10, 1);

$retention_from = $retention_day(150);
$retention_to = $retention_day(0);
$retention_reports = static function () use ($retention_from, $retention_to, $retention_day, $retention_term_id): array {
    return [
        'summary' => Unified_HS_Deck_Events::summary_counts($retention_from, $retention_to),
        'old_day_summary' => Unified_HS_Deck_Events::summary_counts($retention_day(120), $retention_day(120)),
        'by_post' => Unified_HS_Deck_Events::counts_by_post($retention_from, $retention_to),
        'copies_by_post' => Unified_HS_Deck_Events::counts_by_post($retention_from, $retention_to, ['deck_copy']),
        'daily' => array_map('array_values', Unified_HS_Deck_Events::daily_counts($retention_from, $retention_to)),
        'archetype' => array_map('array_values', Unified_HS_Deck_Events::daily_counts_for_archetype($retention_term_id, $retention_from, $retention_to, 'deck_view')),
    ];
};
$retention_before = $retention_reports();
hs_integration_assert($retention_before['summary']['deck_view'] === 6, 'retention fixture did not record raw deck views');

Unified_HS_Deck_Events::run_retention();

$retention_cutoff = $retention_day(Unified_HS_Deck_Events::RETENTION_DAYS);
hs_integration_assert(
    get_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION) === $retention_cutoff,
    'retention did not roll up every day before the cutoff'
);
$retention_raw_count = static function (int $days_ago) use ($wpdb, $retention_table, $retention_day): int {
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$retention_table}` WHERE event_time >= %s AND event_time < %s + INTERVAL 1 DAY",
        $retention_day($days_ago) . ' 00:00:00',
        $retention_day($days_ago)
    ));
};
hs_integration_assert((int) $wpdb->get_var("SELECT COUNT(*) FROM `{$retention_table}`") === 9, 'retention deleted raw events that were never archived');
hs_integration_assert($retention_reports() === $retention_before, 'reports changed after raw events moved into the daily rollup');

// Only a day with an archive record that still covers every raw row is deleted.
$retention_archive = static function (int $days_ago, int $row_count) use ($wpdb, $retention_archives, $retention_day): void {
    $wpdb->insert($retention_archives, [
        'event_day' => $retention_day($days_ago),
        'row_count' => $row_count,
        'bytes' => 1,
        'sha256' => str_repeat('0', 64),
        'object_key' => 'integration/raw/day.jsonl.gz',
        'archived_at' => current_time('mysql', true),
    ]);
};
$retention_archive(120, 3);
$retention_archive(95, 0);
$retention_archive(91, 1);
Unified_HS_Deck_Events::run_retention();
hs_integration_assert($retention_raw_count(120) === 0, 'retention kept raw events of an archived day');
hs_integration_assert($retention_raw_count(95) === 1, 'retention deleted more raw events than the archive holds');
// Raw rows stay one day behind the boundary for reports that read the previous one.
hs_integration_assert($retention_raw_count(91) === 1, 'retention deleted the day just behind the boundary');
hs_integration_assert($retention_raw_count(10) === 4, 'retention deleted recent raw events');
hs_integration_assert($retention_reports() === $retention_before, 'reports changed after archived raw events were deleted');

Unified_HS_Deck_Events::run_retention();
hs_integration_assert($retention_reports() === $retention_before, 'a repeated retention run changed the reports');

// A lost boundary must resume after the rollup, not re-roll days whose raw rows are gone.
delete_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION);
Unified_HS_Deck_Events::run_retention();
hs_integration_assert(
    get_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION) === $retention_cutoff,
    'retention did not restore a lost boundary from the rollup'
);
hs_integration_assert($retention_reports() === $retention_before, 'a lost boundary changed the reports');

// A concurrent runner holding the lock makes this run a no-op.
$retention_lock = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$retention_lock->get_var("SELECT GET_LOCK('" . Unified_HS_Deck_Events::RETENTION_HOOK . "', 0)");
update_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION, $retention_day(100), false);
Unified_HS_Deck_Events::run_retention();
hs_integration_assert(
    get_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION) === $retention_day(100),
    'retention ran while another runner held its lock'
);
$retention_lock->query("DO RELEASE_LOCK('" . Unified_HS_Deck_Events::RETENTION_HOOK . "')");
update_option(Unified_HS_Deck_Events::ROLLED_UNTIL_OPTION, $retention_cutoff, false);

wp_delete_post($retention_post, true);
wp_delete_term($retention_term_id, 'deck_archetype');

echo "Deck events retention integration assertions: OK\n";
