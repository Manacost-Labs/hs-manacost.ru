<?php
/**
 * Integration assertions for the deck card: HearthPulse archetype link instead
 * of likes, chip icons and the copy button. HearthPulse HTTP is stubbed.
 */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/unified-hs-plugins.php';
Unified_HS_Deck_Activity_Log::install();
Unified_HS_Ad_Tracker::install();

$card_manager = new HS_Decks_Manager();
if (!post_type_exists('hs_deck')) {
    $card_manager->register_post_type();
    $card_manager->register_taxonomies();
}

// Name normalization shared by the index and the lookup.
hs_integration_assert(
    Unified_HS_Hearthpulse_Archetypes::name_key('  Чистый  Паладин ') === Unified_HS_Hearthpulse_Archetypes::name_key('чистый паладин'),
    'archetype names must match case and whitespace insensitively'
);
hs_integration_assert(
    Unified_HS_Hearthpulse_Archetypes::name_key('Ёлка Жрец') === Unified_HS_Hearthpulse_Archetypes::name_key('Елка Жрец'),
    'archetype names must treat ё as е'
);
hs_integration_assert(
    Unified_HS_Hearthpulse_Archetypes::name_key('UUB Egg Death Knight') === Unified_HS_Hearthpulse_Archetypes::name_key('UUB Egg DK'),
    'English archetype names must match DK/DH abbreviations'
);
hs_integration_assert(
    Unified_HS_Hearthpulse_Archetypes::name_key('C’Thun Zee Shaman') === Unified_HS_Hearthpulse_Archetypes::name_key("C'Thun Zee Shaman"),
    'archetype names must ignore apostrophe variants'
);

// Stub the HearthPulse API: one archetype per format, an invalid slug that must be skipped.
$card_api_calls = array();
$card_api_mode = 'ok';
$card_api_stub = function ($preempt, $args, $url) use (&$card_api_calls, &$card_api_mode) {
    if (strpos($url, 'https://hearthpulse.net/api/constructed-archetypes/teaser') !== 0) {
        return $preempt;
    }
    $card_api_calls[] = $url;
    if ($card_api_mode === 'fail') {
        return new WP_Error('http_request_failed', 'stubbed outage');
    }
    $format = strpos($url, 'format=wild') !== false ? 'wild' : 'standard';
    $items = $format === 'wild'
        ? array(array('slug' => 'rafaamlock', 'archetype' => 'Rafaamlock', 'archetypeLabel' => 'Рафаам Чернокнижник'))
        : array(
            array('slug' => 'pure-paladin', 'archetype' => 'Pure Paladin', 'archetypeLabel' => 'Чистый Паладин'),
            array('slug' => 'Bad Slug/../x', 'archetype' => 'Broken', 'archetypeLabel' => 'Сломанный'),
        );
    return array(
        'headers' => array(),
        'body' => wp_json_encode(array('format' => $format, 'items' => $items)),
        'response' => array('code' => 200, 'message' => 'OK'),
        'cookies' => array(),
        'filename' => null,
    );
};
add_filter('pre_http_request', $card_api_stub, 10, 3);

delete_option(Unified_HS_Hearthpulse_Archetypes::OPTION);
Unified_HS_Hearthpulse_Archetypes::flush_runtime_cache();
$card_cache_version = HS_Decks_Manager::get_decks_cache_version();
hs_integration_assert(Unified_HS_Hearthpulse_Archetypes::refresh() === true, 'HearthPulse index refresh failed');
hs_integration_assert(count($card_api_calls) === 2, 'refresh must request the standard and wild indexes');
hs_integration_assert(
    HS_Decks_Manager::get_decks_cache_version() > $card_cache_version,
    'a changed HearthPulse index must invalidate the cached feed HTML'
);

$card_link = Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('чистый паладин'), 'standard');
hs_integration_assert(
    $card_link['matched'] === true && $card_link['url'] === 'https://hearthpulse.net/standard/archetypes/standard/pure-paladin/',
    'Russian archetype name does not resolve to its HearthPulse page'
);
$card_link = Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('Черновик', 'Pure Paladin'), 'standard');
hs_integration_assert($card_link['matched'] === true && $card_link['label'] === 'Чистый Паладин', 'fallback name (deck title) is not used');
$card_link = Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('Чистый Паладин'), 'wild');
hs_integration_assert(
    $card_link['matched'] === false && $card_link['url'] === 'https://hearthpulse.net/standard/archetypes/?format=wild',
    'archetype missing from the wild index must link to the wild archetype list'
);
$card_link = Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('Сломанный'), 'standard');
hs_integration_assert($card_link['matched'] === false, 'invalid HearthPulse slugs must be ignored');
hs_integration_assert(Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('Чистый Паладин'), '') === null, 'non constructed modes must not get a link');

// An outage keeps the previous index and does not invalidate caches.
$card_api_mode = 'fail';
$card_cache_version = HS_Decks_Manager::get_decks_cache_version();
hs_integration_assert(Unified_HS_Hearthpulse_Archetypes::refresh() === false, 'failed refresh must report failure');
Unified_HS_Hearthpulse_Archetypes::flush_runtime_cache();
$card_link = Unified_HS_Hearthpulse_Archetypes::link_for_deck(array('Рафаам Чернокнижник'), 'wild');
hs_integration_assert($card_link['matched'] === true, 'HearthPulse outage must keep the previous index');
hs_integration_assert(HS_Decks_Manager::get_decks_cache_version() === $card_cache_version, 'failed refresh must not invalidate caches');
$card_api_mode = 'ok';

// Unchanged data must not invalidate caches either.
Unified_HS_Hearthpulse_Archetypes::refresh();
$card_cache_version = HS_Decks_Manager::get_decks_cache_version();
Unified_HS_Hearthpulse_Archetypes::refresh();
hs_integration_assert(HS_Decks_Manager::get_decks_cache_version() === $card_cache_version, 'unchanged index must not invalidate caches');

// Like-based sorts went away with voting; stale links fall back to the newest decks.
$card_read_filters = new ReflectionMethod($card_manager, 'read_deck_feed_filters');
foreach (array('likes', 'dislikes') as $card_sort) {
    $card_filters = $card_read_filters->invoke($card_manager, array(), array('hs_sort' => $card_sort));
    hs_integration_assert($card_filters['sort'] === 'date', 'public feed still accepts the ' . $card_sort . ' sort');
}

// Card rendering.
foreach (array(
    array('voin', 'Воин', 'deck_class'),
    array('standart', 'Стандарт', 'deck_mode'),
    array('volnyj', 'Вольный', 'deck_mode'),
    array('tavern', 'Потасовка', 'deck_mode'),
) as $card_term) {
    if (!term_exists($card_term[0], $card_term[2])) {
        wp_insert_term($card_term[1], $card_term[2], array('slug' => $card_term[0]));
    }
}
if (!term_exists('chistyj-paladin', 'deck_archetype')) {
    wp_insert_term('Чистый Паладин', 'deck_archetype', array('slug' => 'chistyj-paladin'));
}

$card_deck_ids = array();
foreach (array(
    array('Чистый Паладин', 'standart', 'chistyj-paladin'),
    array('Неизвестный архетип', 'volnyj', ''),
    array('Колода потасовки', 'tavern', ''),
) as $card_fixture) {
    $card_deck_id = wp_insert_post(array(
        'post_type' => 'hs_deck',
        'post_status' => 'publish',
        'post_title' => $card_fixture[0],
    ), true);
    hs_integration_assert(!is_wp_error($card_deck_id), 'deck card fixture was not created');
    wp_set_object_terms($card_deck_id, 'voin', 'deck_class');
    wp_set_object_terms($card_deck_id, $card_fixture[1], 'deck_mode');
    if ($card_fixture[2] !== '') {
        wp_set_object_terms($card_deck_id, $card_fixture[2], 'deck_archetype');
    }
    update_post_meta($card_deck_id, '_dust_cost', 27840);
    update_post_meta($card_deck_id, '_deck_likes', 5);
    $card_deck_ids[] = $card_deck_id;
}

$card_render = new ReflectionMethod($card_manager, 'render_deck_card');
$card_html = $card_render->invoke($card_manager, $card_deck_ids[0]);

hs_integration_assert(
    strpos($card_html, 'deck-voting') === false && strpos($card_html, 'vote-btn') === false && strpos($card_html, 'Лайк') === false,
    'deck card still renders likes and dislikes'
);
hs_integration_assert(
    (bool) preg_match('#<a class="deck-archetype-link" href="https://hearthpulse\.net/standard/archetypes/standard/pure-paladin/" target="_blank" rel="noopener"#', $card_html)
        && strpos($card_html, '>Об архетипе<') !== false,
    'standard deck card does not link to its HearthPulse archetype page'
);
hs_integration_assert(
    (bool) preg_match('#<span class="deck-class"><img class="deck-chip-icon" src="[^"]+/assets/images/classes/warrior\.webp"[^>]*>Воин</span>#', $card_html),
    'class chip has no class icon'
);
hs_integration_assert(
    (bool) preg_match('#<span class="deck-mode"><img class="deck-chip-icon" src="[^"]+/assets/images/formats/standard\.webp"[^>]*>Стандарт</span>#', $card_html),
    'format chip has no Standard icon'
);
hs_integration_assert(
    (bool) preg_match('#<span class="deck-dust"><img class="deck-chip-icon" src="[^"]+/assets/images/dust\.webp"[^>]*>27 840 пыли</span>#', $card_html),
    'dust chip has no dust icon'
);
hs_integration_assert(
    (bool) preg_match('#<button type="button" class="copy-code-btn" data-code="[^"]*"><span class="copy-code-icon" aria-hidden="true">.*</span><span class="copy-code-label">Скопировать код</span></button>#s', $card_html),
    'copy button has no icon and visible label'
);

$card_html = $card_render->invoke($card_manager, $card_deck_ids[1]);
hs_integration_assert(
    strpos($card_html, 'href="https://hearthpulse.net/standard/archetypes/?format=wild"') !== false
        && strpos($card_html, '>Архетипы Вольного<') !== false
        && strpos($card_html, 'formats/wild.webp') !== false,
    'wild deck without a known archetype must link to the wild archetype list'
);

$card_html = $card_render->invoke($card_manager, $card_deck_ids[2]);
hs_integration_assert(
    strpos($card_html, 'deck-archetype-link') === false && strpos($card_html, 'formats/tavern.webp') !== false,
    'brawl deck must show the brawl icon and no HearthPulse link'
);

// Own deck page: the theme already prints the title, date and image, so the panel must not repeat them.
update_post_meta($card_deck_ids[0], '_deck_code', 'AAECAZ8FBLSBB+XBB53dBwAA');
update_post_meta($card_deck_ids[0], '_deck_streamer', 'Dmoney');
$card_page_panel = new ReflectionMethod($card_manager, 'render_deck_page_panel');
$card_page_html = $card_page_panel->invoke($card_manager, $card_deck_ids[0]);
hs_integration_assert(strpos($card_page_html, 'class="deck-card deck-single deck-page"') !== false, 'deck page panel is not marked as a deck page');
hs_integration_assert(
    strpos($card_page_html, 'deck-title') === false && strpos($card_page_html, 'deck-image') === false && strpos($card_page_html, 'deck-date') === false,
    'deck page panel repeats the title, image or date printed by the theme'
);
hs_integration_assert(
    (bool) preg_match('#<input id="deck-code-\d+" class="deck-code-input" type="text" value="AAECAZ8FBLSBB\+XBB53dBwAA" readonly#', $card_page_html)
        && substr_count($card_page_html, 'class="copy-code-btn"') === 1,
    'deck page panel must show the code once with a single copy button'
);
hs_integration_assert(strpos($card_page_html, '27 840 пыли') !== false, 'deck page panel must show the dust cost');
hs_integration_assert(strpos($card_page_html, '<span class="deck-meta-label">Стример</span> Dmoney') !== false, 'deck page panel must label the streamer');
hs_integration_assert(strpos($card_page_html, 'deck-archetype-link') !== false, 'deck page panel must link to the archetype');
hs_integration_assert(strpos($card_page_html, 'hs-deck-code-block') === false, 'deck page still renders the separate code block');

// Class and format chips link to the filtered feed on the deck page only.
hs_integration_assert(
    (bool) preg_match('#<a class="deck-class deck-chip-link" href="[^"]*/hearthstone-top-decks/\?hs_class=voin"[^>]*>.*?Воин</a>#', $card_page_html)
        && (bool) preg_match('#<a class="deck-mode deck-chip-link" href="[^"]*/hearthstone-top-decks/\?hs_mode=standart"[^>]*>.*?Стандарт</a>#', $card_page_html),
    'deck page chips must link to the feed filtered by class and format'
);
hs_integration_assert(strpos($card_html, 'deck-chip-link') === false, 'feed cards must keep plain chips');

// Related decks: same archetype and format, visible in the feed, never the deck itself.
hs_integration_assert(strpos($card_page_html, 'hs-deck-related') === false, 'related block must stay hidden without other decks');
update_post_meta($card_deck_ids[0], '_hide_from_feed', '0');
$card_related_ids = array();
foreach (array(array('chistyj-paladin', 'standart', '0'), array('chistyj-paladin', 'standart', '0'), array('chistyj-paladin', 'standart', '1'), array('chistyj-paladin', 'volnyj', '0')) as $card_related) {
    $card_related_id = wp_insert_post(array('post_type' => 'hs_deck', 'post_status' => 'publish', 'post_title' => 'Чистый Паладин'), true);
    hs_integration_assert(!is_wp_error($card_related_id), 'related deck fixture was not created');
    wp_set_object_terms($card_related_id, 'voin', 'deck_class');
    wp_set_object_terms($card_related_id, $card_related[1], 'deck_mode');
    wp_set_object_terms($card_related_id, $card_related[0], 'deck_archetype');
    update_post_meta($card_related_id, '_hide_from_feed', $card_related[2]);
    $card_related_ids[] = $card_related_id;
}
$card_page_html = $card_page_panel->invoke($card_manager, $card_deck_ids[0]);
hs_integration_assert(
    strpos($card_page_html, '>Другие колоды архетипа «Чистый Паладин»</h2>') !== false
        && (bool) preg_match('#class="hs-deck-related-all" href="[^"]*/deck-archetype/chistyj-paladin/\?hs_mode=standart"#', $card_page_html),
    'related block must name the archetype and link to its page'
);
hs_integration_assert(substr_count($card_page_html, 'class="hs-deck-related-item"') === 2, 'related block must list only visible decks of the same archetype and format');
hs_integration_assert(strpos($card_page_html, 'href="' . esc_url(get_permalink($card_deck_ids[0])) . '"') === false, 'related block must not link to the deck itself');

// Decks with the placeholder archetype fall back to the same class and format.
if (!term_exists('chernovik', 'deck_archetype')) {
    wp_insert_term('Черновик', 'deck_archetype', array('slug' => 'chernovik'));
}
wp_set_object_terms($card_deck_ids[1], 'chernovik', 'deck_archetype');
update_post_meta($card_deck_ids[1], '_hide_from_feed', '0');
$card_page_html = $card_page_panel->invoke($card_manager, $card_deck_ids[1]);
hs_integration_assert(
    strpos($card_page_html, '>Другие колоды: Воин, вольный</h2>') !== false
        && (bool) preg_match('#class="hs-deck-related-all" href="[^"]*/hearthstone-top-decks/\?hs_class=voin&(amp;|\#038;)hs_mode=volnyj"#', $card_page_html)
        && strpos($card_page_html, 'Черновик') === false,
    'placeholder archetype must fall back to decks of the same class and format'
);
foreach ($card_related_ids as $card_related_id) {
    wp_delete_post($card_related_id, true);
}

// Decks embedded into articles ([hs_deck id=…]) keep their own title.
$card_embed_html = do_shortcode('[hs_deck id="' . (int) $card_deck_ids[0] . '"]');
hs_integration_assert(
    strpos($card_embed_html, 'class="deck-title"') !== false && strpos($card_embed_html, 'deck-page') === false,
    'embedded deck card lost its title'
);

remove_filter('pre_http_request', $card_api_stub, 10);
delete_option(Unified_HS_Hearthpulse_Archetypes::OPTION);
Unified_HS_Hearthpulse_Archetypes::flush_runtime_cache();
foreach ($card_deck_ids as $card_deck_id) {
    wp_delete_post($card_deck_id, true);
}
