<?php
/** Integration assertions for the server-rendered [hs_decks] feed layout. */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/unified-hs-plugins.php';
// The plugin stays inactive in this site; create the tables its write hooks log into.
Unified_HS_Deck_Activity_Log::install();
Unified_HS_Ad_Tracker::install();

$feed_manager = new HS_Decks_Manager();
if (!post_type_exists('hs_deck')) {
    $feed_manager->register_post_type();
    $feed_manager->register_taxonomies();
}

// Boosty card cadence: one card after every eighth deck.
$feed_ad_after = new ReflectionMethod($feed_manager, 'should_insert_feed_ad_after');
$feed_ad_slots = array();
for ($feed_index = 1; $feed_index <= 24; $feed_index++) {
    if ($feed_ad_after->invoke($feed_manager, $feed_index)) {
        $feed_ad_slots[] = $feed_index;
    }
}
hs_integration_assert($feed_ad_slots === array(8, 16, 24), 'feed ad card is not inserted after every eighth deck');

// Compact pagination window: edges, current ±1, a single skipped page is shown instead of an ellipsis.
$feed_pagination_items = new ReflectionMethod($feed_manager, 'get_feed_pagination_items');
foreach (array(
    array(1, 250, array(1, 2, 'gap', 250)),
    array(4, 250, array(1, 2, 3, 4, 5, 'gap', 250)),
    array(6, 250, array(1, 'gap', 5, 6, 7, 'gap', 250)),
    array(250, 250, array(1, 'gap', 249, 250)),
    array(3, 4, array(1, 2, 3, 4)),
    array(1, 1, array(1)),
) as $feed_case) {
    hs_integration_assert(
        $feed_pagination_items->invoke($feed_manager, $feed_case[0], $feed_case[1]) === $feed_case[2],
        'feed pagination window is wrong for page ' . $feed_case[0] . ' of ' . $feed_case[1]
    );
}

$feed_pagination = new ReflectionMethod($feed_manager, 'render_decks_feed_pagination');
$feed_first_page = $feed_pagination->invoke($feed_manager, 1, 3, 40, 16);
hs_integration_assert(
    (bool) preg_match('/class="hs-page-btn hs-page-step hs-page-prev" data-page="1"\s+disabled/', $feed_first_page),
    'first feed page does not disable the previous-page step'
);
hs_integration_assert(
    strpos($feed_first_page, 'data-page="2" aria-current') === false
        && strpos($feed_first_page, 'class="hs-page-btn active" data-page="1" aria-current="page"') !== false,
    'current feed page is not marked'
);
$feed_last_page = $feed_pagination->invoke($feed_manager, 3, 3, 40, 16);
hs_integration_assert(
    (bool) preg_match('/class="hs-page-btn hs-page-step hs-page-next" data-page="3"\s+disabled/', $feed_last_page),
    'last feed page does not disable the next-page step'
);

// Deck fixtures for card and feed rendering.
add_filter('hs_decks_disable_filtering', '__return_true');
$feed_deck_ids = array();
for ($feed_index = 1; $feed_index <= 9; $feed_index++) {
    $feed_deck_id = wp_insert_post(array(
        'post_type' => 'hs_deck',
        'post_status' => 'publish',
        'post_title' => 'Integration Feed Deck ' . $feed_index,
        'post_date' => gmdate('Y-m-d H:i:s', time() - ($feed_index * HOUR_IN_SECONDS)),
    ), true);
    hs_integration_assert(!is_wp_error($feed_deck_id), 'feed deck fixture was not created');
    update_post_meta($feed_deck_id, '_deck_source_url', 'https://hearthstone-decks.net/integration-feed-source-' . $feed_index . '/');
    update_post_meta($feed_deck_id, '_hide_from_feed', '0');
    $feed_deck_ids[] = $feed_deck_id;
}
if (!term_exists('voin', 'deck_class')) {
    wp_insert_term('Воин', 'deck_class', array('slug' => 'voin'));
}
wp_set_object_terms($feed_deck_ids[0], 'voin', 'deck_class');

$feed_card = new ReflectionMethod($feed_manager, 'render_deck_card');
$feed_card_html = $feed_card->invoke($feed_manager, $feed_deck_ids[0]);
hs_integration_assert(
    strpos($feed_card_html, 'Integration Feed Deck 1') !== false,
    'deck card fixture did not render'
);
hs_integration_assert(
    strpos($feed_card_html, 'integration-feed-source') === false && strpos($feed_card_html, 'deck-source') === false,
    'deck card still links to the deck source'
);

$feed_html = do_shortcode('[hs_decks deck="16"]');
foreach (array(
    'id="hs-decks-filter-form"',
    'id="deck-search"',
    'id="filter-period"',
    'id="filter-sort"',
    'id="filter-games"',
    'id="filter-winrate"',
    'id="filter-dust"',
    'id="filter-archetype"',
    'id="filter-streamer"',
    'id="filter-source"',
    'id="hs-advanced-toggle"',
    'id="hs-filter-reset"',
    'id="hs-filters-count"',
    'type="radio" name="hs_class" value=""',
    'type="radio" name="hs_mode" value=""',
    'id="decks-grid"',
    'id="hs-load-more"',
) as $feed_contract) {
    hs_integration_assert(strpos($feed_html, $feed_contract) !== false, 'feed filter contract is missing ' . $feed_contract);
}
hs_integration_assert(strpos($feed_html, 'integration-feed-source') === false, 'feed cards still link to the deck source');
hs_integration_assert(
    (bool) preg_match('#name="hs_class" value="voin"[^>]*><span class="hs-chip-body"><img class="hs-chip-icon" src="[^"]+/assets/images/classes/warrior\.webp"#', $feed_html),
    'class filter chip has no bundled class icon'
);

$feed_ad_offset = strpos($feed_html, 'hs-decks-ad-card');
hs_integration_assert(substr_count($feed_html, 'class="deck-card hs-decks-ad-card') === 1, 'nine decks must render exactly one feed ad card');
hs_integration_assert(
    $feed_ad_offset !== false && substr_count(substr($feed_html, 0, $feed_ad_offset), 'data-deck-id="') === 8,
    'feed ad card is not placed after the eighth deck'
);

remove_filter('hs_decks_disable_filtering', '__return_true');
foreach ($feed_deck_ids as $feed_deck_id) {
    wp_delete_post($feed_deck_id, true);
}
