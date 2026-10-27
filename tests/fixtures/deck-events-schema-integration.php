<?php
/** Integration assertions for the deck events table indexes. */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/includes/class-deck-events.php';

global $wpdb;

$deck_events_table = Unified_HS_Deck_Events::table_name();
$deck_events_indexes = static function () use ($wpdb, $deck_events_table): array {
    return array_values(array_unique($wpdb->get_col("SHOW INDEX FROM `{$deck_events_table}`", 2)));
};

Unified_HS_Deck_Events::install();

// Recreate a schema-version-1 table: it carried the write-only visitor index.
if (!in_array('visitor_event', $deck_events_indexes(), true)) {
    $wpdb->query("ALTER TABLE `{$deck_events_table}` ADD INDEX visitor_event (visitor_hash, event_type, event_time)");
}
update_option('unified_hs_deck_events_db_version', '1', false);

Unified_HS_Deck_Events::install();

$deck_events_after = $deck_events_indexes();
hs_integration_assert(!in_array('visitor_event', $deck_events_after, true), 'deck events upgrade kept the unused visitor_event index');
foreach (['PRIMARY', 'event_time', 'event_type_time', 'post_event_time'] as $deck_events_index) {
    hs_integration_assert(in_array($deck_events_index, $deck_events_after, true), "deck events upgrade lost the {$deck_events_index} index");
}
hs_integration_assert(
    get_option('unified_hs_deck_events_db_version') === Unified_HS_Deck_Events::SCHEMA_VERSION,
    'deck events upgrade did not record its schema version'
);
