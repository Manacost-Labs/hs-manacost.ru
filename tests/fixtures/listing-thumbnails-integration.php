<?php
/** Missing Newspaper thumbnail fallback with real WordPress image metadata. */

$thumbnail_previous_query = $GLOBALS['wp_query'];
$thumbnail_previous_user = get_current_user_id();
$thumbnail_previous_sizes = $GLOBALS['_wp_additional_image_sizes'];
add_image_size('td_696x0', 696, 0, ['center', 'top']);
add_image_size('td_485x360', 485, 360, ['center', 'top']);
$thumbnail_id = wp_insert_attachment([
    'post_title' => 'Disposable thumbnail fallback fixture',
    'post_mime_type' => 'image/png',
    'post_status' => 'inherit',
]);
$thumbnail_metadata = [
    'file' => '2026/09/thumbnail-fixture.png',
    'width' => 1176,
    'height' => 597,
    'sizes' => [
        'medium_large' => ['file' => 'thumbnail-fixture-768x390.png', 'width' => 768, 'height' => 390, 'mime-type' => 'image/png'],
    ],
];
update_post_meta($thumbnail_id, '_wp_attached_file', $thumbnail_metadata['file']);
wp_update_attachment_metadata($thumbnail_id, $thumbnail_metadata);
$thumbnail_numeric = wp_get_attachment_image_src($thumbnail_id, [696, 0]);
$thumbnail_filter_registered = remove_filter('wp_get_attachment_image_src', ['Manacost_Listing_Thumbnails', 'fallback'], 10);
$thumbnail_unmodified = wp_get_attachment_image_src($thumbnail_id, 'td_696x0');
if ($thumbnail_filter_registered) {
    add_filter('wp_get_attachment_image_src', ['Manacost_Listing_Thumbnails', 'fallback'], 10, 4);
}
wp_set_current_user(0);
$GLOBALS['wp_query'] = new WP_Query(['post_type' => 'post']);
$GLOBALS['wp_query']->is_home = true;

try {
    $thumbnail_full = wp_get_attachment_image_src($thumbnail_id, 'full');
    $thumbnail_medium = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_696x0') === $thumbnail_medium, 'missing Newspaper size still downloads original PNG');
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_485x360') === $thumbnail_medium, 'missing cropped card size still downloads original PNG');
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'full') === $thumbnail_full, 'original image API changed');
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_1068x0') === $thumbnail_full, 'unrelated Newspaper size changed');
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, [696, 0]) === $thumbnail_numeric, 'numeric size request changed');

    wp_set_current_user(1);
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_696x0') === $thumbnail_unmodified, 'authenticated image request changed');
    wp_set_current_user(0);
    $GLOBALS['wp_query']->is_preview = true;
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_696x0') === $thumbnail_unmodified, 'preview image changed');
    $GLOBALS['wp_query']->is_preview = false;
    $GLOBALS['wp_query'] = new WP_Query(['p' => $postId]);
    hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_696x0') === $thumbnail_unmodified, 'article image changed');
    $GLOBALS['wp_query'] = new WP_Query(['post_type' => 'post']);
    $GLOBALS['wp_query']->is_home = true;

    $thumbnail_existing = $thumbnail_metadata;
    $thumbnail_existing['sizes']['td_696x0'] = ['file' => 'thumbnail-fixture-696x353.png', 'width' => 696, 'height' => 353, 'mime-type' => 'image/png'];
    wp_update_attachment_metadata($thumbnail_id, $thumbnail_existing);
    $thumbnail_exact = wp_get_attachment_image_src($thumbnail_id, 'td_696x0');
    hs_integration_assert($thumbnail_exact[1] === 696 && str_contains($thumbnail_exact[0], '-696x353.png'), 'existing theme thumbnail replaced');

    foreach (['missing', 'cropped', 'too-small', 'not-smaller'] as $thumbnail_case) {
        $thumbnail_invalid = $thumbnail_metadata;
        if ($thumbnail_case === 'missing') {
            $thumbnail_invalid['sizes'] = [];
        } elseif ($thumbnail_case === 'cropped') {
            $thumbnail_invalid['sizes']['medium_large']['height'] = 768;
        } elseif ($thumbnail_case === 'too-small') {
            $thumbnail_invalid['sizes']['medium_large']['width'] = 300;
            $thumbnail_invalid['sizes']['medium_large']['height'] = 152;
        } else {
            $thumbnail_invalid['width'] = 768;
            $thumbnail_invalid['height'] = 390;
        }
        wp_update_attachment_metadata($thumbnail_id, $thumbnail_invalid);
        remove_filter('wp_get_attachment_image_src', ['Manacost_Listing_Thumbnails', 'fallback'], 10);
        $thumbnail_core = wp_get_attachment_image_src($thumbnail_id, 'td_696x0');
        add_filter('wp_get_attachment_image_src', ['Manacost_Listing_Thumbnails', 'fallback'], 10, 4);
        hs_integration_assert(wp_get_attachment_image_src($thumbnail_id, 'td_696x0') === $thumbnail_core, 'unsafe thumbnail fallback: ' . $thumbnail_case);
    }
    hs_integration_assert(wp_get_attachment_image_src(0, 'td_696x0') === false, 'missing attachment no longer returns false');
} finally {
    wp_delete_attachment($thumbnail_id, true);
    $GLOBALS['wp_query'] = $thumbnail_previous_query;
    $GLOBALS['_wp_additional_image_sizes'] = $thumbnail_previous_sizes;
    wp_set_current_user($thumbnail_previous_user);
}

echo "Listing thumbnail integration assertions: OK\n";
