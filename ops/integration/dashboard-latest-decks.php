<?php
/** Verify dashboard deck feed cache and its public visibility rules. */

$hadDeckPostType = post_type_exists('hs_deck');
if (!$hadDeckPostType) {
    register_post_type('hs_deck', ['public' => true, 'label' => 'Integration decks']);
}
$deckIds = [];
$createDeck = static function (string $title) use (&$deckIds): int {
    $id = wp_insert_post([
        'post_type'   => 'hs_deck',
        'post_status' => 'publish',
        'post_title'  => $title,
    ], true);
    hs_integration_assert(!is_wp_error($id), 'deck fixture could not be published');
    $deckIds[] = (int) $id;
    return (int) $id;
};
$feedIds = static function (): array {
    return array_map(
        static fn (WP_Post $post): int => (int) $post->ID,
        hs_dashboard_get_latest_decks_feed_posts()
    );
};

try {
    hs_integration_assert(function_exists('hs_dashboard_get_latest_decks_feed_posts'), 'dashboard deck feed helper is unavailable');
    $visible = $createDeck('Visible dashboard deck');
    $hidden = $createDeck('Hidden dashboard deck');
    $archived = $createDeck('Archived dashboard deck');
    update_post_meta($hidden, '_hide_from_feed', '1');
    update_post_meta($archived, '_hs_deck_archived', '1');
    delete_transient('hs_dashboard_latest_deck_ids_v1');

    $first = $feedIds();
    hs_integration_assert(in_array($visible, $first, true), 'public deck missing from dashboard');
    hs_integration_assert(!in_array($hidden, $first, true), 'hidden deck leaked into dashboard');
    hs_integration_assert(!in_array($archived, $first, true), 'archived deck leaked into dashboard');
    hs_integration_assert(is_array(get_transient('hs_dashboard_latest_deck_ids_v1')), 'deck ID cache was not populated');
    hs_integration_assert($feedIds() === $first, 'warm deck feed changed ordering');

    $newDeck = $createDeck('New dashboard deck');
    hs_integration_assert(false === get_transient('hs_dashboard_latest_deck_ids_v1'), 'new deck did not invalidate deck cache');
    hs_integration_assert(in_array($newDeck, $feedIds(), true), 'new deck did not enter dashboard');

    delete_post_meta($archived, '_hs_deck_archived');
    hs_integration_assert(false === get_transient('hs_dashboard_latest_deck_ids_v1'), 'archive change did not invalidate deck cache');
    hs_integration_assert(in_array($archived, $feedIds(), true), 'restored deck did not enter dashboard');

    $previousUser = get_current_user_id();
    wp_set_current_user(0);
    ob_start();
    hs_dashboard_render_latest_decks_feed();
    $publicMarkup = ob_get_clean();
    wp_set_current_user($previousUser);
    hs_integration_assert(!str_contains($publicMarkup, 'post.php?post='), 'shared feed cache exposed an edit link');

    update_post_meta($visible, '_hide_from_feed', '1');
    hs_integration_assert(false === get_transient('hs_dashboard_latest_deck_ids_v1'), 'visibility change did not invalidate deck cache');
    hs_integration_assert(!in_array($visible, $feedIds(), true), 'newly hidden deck remained visible');

    delete_post_meta($visible, '_hide_from_feed');
    hs_integration_assert(in_array($visible, $feedIds(), true), 'unhidden deck did not return');
    wp_update_post(['ID' => $visible, 'post_status' => 'draft']);
    hs_integration_assert(false === get_transient('hs_dashboard_latest_deck_ids_v1'), 'status change did not invalidate deck cache');
    hs_integration_assert(!in_array($visible, $feedIds(), true), 'draft deck remained visible');

    wp_delete_post($newDeck, true);
    hs_integration_assert(false === get_transient('hs_dashboard_latest_deck_ids_v1'), 'deleted deck did not invalidate deck cache');
    hs_integration_assert(!in_array($newDeck, $feedIds(), true), 'deleted deck remained visible');
} finally {
    foreach ($deckIds as $deckId) {
        wp_delete_post($deckId, true);
    }
    delete_transient('hs_dashboard_latest_deck_ids_v1');
    if (!$hadDeckPostType) {
        unregister_post_type('hs_deck');
    }
}
