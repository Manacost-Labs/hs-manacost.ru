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

echo "Deck image integration assertions: OK\n";
