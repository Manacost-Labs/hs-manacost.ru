<?php
/** Integration assertions for responsive deck-image candidates. */

require_once WP_PLUGIN_DIR . '/wp-manacost-decks_old/hs-deck-manager/hs-decks-manager.php';

$deck_manager = new HS_Decks_Manager();
$deck_srcset_method = new ReflectionMethod($deck_manager, 'bound_mobile_image_srcset');
$deck_srcset = implode(', ', [
    'https://hs-manacost.ru/deck.jpg 1484w',
    'https://hs-manacost.ru/deck-300.jpg 300w',
    'https://hs-manacost.ru/deck-696.jpg 696w',
    'https://hs-manacost.ru/deck-768.jpg 768w',
    'https://hs-manacost.ru/deck-1068.jpg 1068w',
]);
$deck_mobile_srcset = implode(', ', [
    'https://hs-manacost.ru/deck-300.jpg 300w',
    'https://hs-manacost.ru/deck-696.jpg 696w',
    'https://hs-manacost.ru/deck-768.jpg 768w',
]);

add_filter('wp_is_mobile', '__return_false');
hs_integration_assert(
    $deck_srcset_method->invoke($deck_manager, $deck_srcset) === $deck_srcset,
    'desktop deck candidates changed'
);
remove_filter('wp_is_mobile', '__return_false');

add_filter('wp_is_mobile', '__return_true');
hs_integration_assert(
    $deck_srcset_method->invoke($deck_manager, $deck_srcset) === $deck_mobile_srcset,
    'mobile deck candidates are not bounded'
);
$deck_small_srcset = 'https://hs-manacost.ru/deck.jpg 1484w, https://hs-manacost.ru/deck-300.jpg 300w';
hs_integration_assert(
    $deck_srcset_method->invoke($deck_manager, $deck_small_srcset) === $deck_small_srcset,
    'mobile deck candidates changed without an adequate fallback'
);
remove_filter('wp_is_mobile', '__return_true');

// HearthstoneJSON art paths are case-sensitive; cyr2lat lowercases sanitized file names.
$deck_asset_id_method = new ReflectionMethod($deck_manager, 'get_tooltip_card_asset_id');
$deck_lowercase_filenames = static function ($filename) {
    return strtolower((string) $filename);
};
add_filter('sanitize_file_name', $deck_lowercase_filenames, 99);
hs_integration_assert(
    $deck_asset_id_method->invoke(
        $deck_manager,
        ['img' => 'https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/CATA_203.png'],
        'CATA_203'
    ) === 'CATA_203',
    'tooltip card asset id lost its case'
);
hs_integration_assert(
    $deck_asset_id_method->invoke($deck_manager, ['img' => ''], 'CORE_EX1_129') === 'CORE_EX1_129',
    'tooltip card fallback id lost its case'
);
hs_integration_assert(
    $deck_asset_id_method->invoke($deck_manager, ['img' => 'https://example.test/cards/a%20b.png'], '123') === '',
    'unsafe or numeric tooltip card ids are accepted'
);
remove_filter('sanitize_file_name', $deck_lowercase_filenames, 99);

echo "Deck image integration assertions: OK\n";
