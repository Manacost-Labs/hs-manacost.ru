<?php
/** Reproduce the active theme's boxed wallpaper only on the disposable browser-test URL. */
if (!isset($_GET['integration-neverest'])) {
    return;
}

add_filter('body_class', static function ($classes) {
    $classes[] = 'td-boxed-layout';
    return $classes;
});
add_action('wp_head', static function () {
    echo '<style>body {background-image:url("/wp-content/uploads/2026/11/neverest_manacost_wallpaper_2400x1500.png.webp")}</style>';
});
