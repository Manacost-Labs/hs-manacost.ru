<?php
/** Article cover checks using the installed WordPress HTML parser. */

$cover_url = 'https://hs-manacost.ru/wp-content/uploads/2026/09/cover.jpg';
$cover_srcset = $cover_url . ' 1176w, https://hs-manacost.ru/wp-content/uploads/2026/09/cover-768x390.jpg 768w';
$cover_sizes = '(max-width: 1176px) 100vw, 1176px';
$cover_preload = '<link rel="preload" data-rocket-preload as="image" href="' . $cover_url . '.webp" imagesrcset="old.webp 1176w" imagesizes="100vw">';
$cover_tag = '<img class="entry-thumb" width="1176" height="597" alt="Обложка &amp; колоды" src="data:image/svg+xml,placeholder" data-lazy-src="' . $cover_url . '" data-lazy-srcset="' . $cover_srcset . '" data-lazy-sizes="' . $cover_sizes . '" loading="lazy">';
$cover_body_image = '<img src="placeholder.gif" data-lazy-src="https://hs-manacost.ru/body.jpg" loading="lazy">';
$cover_module_url = 'https://hs-manacost.ru/wp-content/uploads/2026/09/related-768x390.jpg';
$cover_module_image = '<div class="td-module-thumb"><a href="https://hs-manacost.ru/related/"><img data-no-lazy="1" class="entry-thumb" src="' . $cover_module_url . '" loading="auto"></a></div>';
$cover_page = '<html><head>' . $cover_preload . '<link rel="preload" as="image" href="https://hs-manacost.ru/banner.webp"></head><body><div class="td-post-featured-image"><a href="' . $cover_url . '">' . $cover_tag . '</a><noscript><img src="' . $cover_url . '"></noscript></div>' . $cover_body_image . $cover_module_image . '</body></html>';
$cover_result = class_exists('Manacost_Article_Cover') ? Manacost_Article_Cover::optimize_html($cover_page) : $cover_page;
$cover_parser = new WP_HTML_Tag_Processor($cover_result);
$cover_parser->next_tag('IMG');
hs_integration_assert($cover_parser->get_attribute('loading') === 'eager', 'article cover remains lazy-loaded');
hs_integration_assert($cover_parser->get_attribute('fetchpriority') === 'high', 'article cover lacks high priority');
hs_integration_assert($cover_parser->get_attribute('src') === $cover_url, 'article cover URL changed');
hs_integration_assert($cover_parser->get_attribute('srcset') === $cover_srcset, 'responsive cover candidates changed');
hs_integration_assert($cover_parser->get_attribute('sizes') === $cover_sizes, 'cover sizes changed');
hs_integration_assert($cover_parser->get_attribute('data-lazy-src') === null, 'cover still owned by lazyloader');
hs_integration_assert($cover_parser->get_attribute('data-no-lazy') === '1', 'Rocket may lazyload the cover again');
hs_integration_assert($cover_parser->get_attribute('alt') === 'Обложка & колоды', 'cover alt text changed');
hs_integration_assert($cover_parser->get_attribute('width') === '1176' && $cover_parser->get_attribute('height') === '597', 'cover dimensions changed');
hs_integration_assert(str_contains($cover_result, $cover_body_image), 'below-fold image was changed');
$cover_lazy_priority_image = '<img class="wp-image-123 size-full" src="placeholder.gif" data-lazy-src="https://hs-manacost.ru/body-priority.jpg" data-lazy-srcset="body-small.jpg 300w, body.jpg 1200w" width="1200" height="600" fetchpriority="high">';
$cover_native_lazy_image = '<img class="wp-image-124" src="native-lazy.jpg" loading="lazy" fetchpriority="high">';
$cover_eager_image = '<img class="wp-image-125" src="eager.jpg" loading="eager" fetchpriority="high">';
$cover_optout_image = '<img class="wp-image-126" src="optout.jpg" loading="lazy" data-no-lazy="1" fetchpriority="high">';
$cover_inert_image = '<template>' . $cover_native_lazy_image . '</template><noscript>' . $cover_native_lazy_image . '</noscript>';
$cover_priority_page = str_replace('</body>', $cover_lazy_priority_image . $cover_native_lazy_image . $cover_eager_image . $cover_optout_image . $cover_inert_image . '</body>', $cover_page);
$cover_priority_result = Manacost_Article_Cover::optimize_html($cover_priority_page);
$cover_parser = new WP_HTML_Tag_Processor($cover_priority_result);
$cover_parser->next_tag(['tag_name' => 'IMG', 'class_name' => 'wp-image-123']);
hs_integration_assert($cover_parser->get_attribute('fetchpriority') === null, 'lazy body image competes with the cover');
hs_integration_assert($cover_parser->get_attribute('data-lazy-src') === 'https://hs-manacost.ru/body-priority.jpg', 'body source URL changed');
hs_integration_assert($cover_parser->get_attribute('data-lazy-srcset') === 'body-small.jpg 300w, body.jpg 1200w', 'body responsive candidates changed');
hs_integration_assert($cover_parser->get_attribute('width') === '1200' && $cover_parser->get_attribute('height') === '600', 'body dimensions changed');
$cover_parser->next_tag(['tag_name' => 'IMG', 'class_name' => 'wp-image-124']);
hs_integration_assert($cover_parser->get_attribute('fetchpriority') === null, 'native lazy image retains contradictory high priority');
hs_integration_assert($cover_parser->get_attribute('loading') === 'lazy', 'native image no longer lazy');
hs_integration_assert(str_contains($cover_priority_result, $cover_eager_image), 'eager body image lost its priority');
hs_integration_assert(str_contains($cover_priority_result, $cover_optout_image), 'explicit lazy-load exclusion lost its priority');
hs_integration_assert(str_contains($cover_priority_result, $cover_inert_image), 'inert image markup changed');
foreach ([
    '<template></noscript>' . $cover_native_lazy_image . '</template>',
    '<noscript></template>' . $cover_native_lazy_image . '</noscript>',
    '<template><template>' . $cover_native_lazy_image . '</template></template>',
    '<img class="wp-image-127" src="eager-case.jpg" data-lazy-src="eager-case.jpg" loading="Eager" fetchpriority="high">',
] as $cover_protected_markup) {
    $cover_edge_page = str_replace('</body>', $cover_protected_markup . '</body>', $cover_page);
    hs_integration_assert(str_contains(Manacost_Article_Cover::optimize_html($cover_edge_page), $cover_protected_markup), 'inert markup or case-insensitive eager image was changed');
}
$cover_case_page = str_replace('</body>', '<img class="wp-image-128" src="lazy-case.jpg" loading="LAZY" fetchpriority="HIGH"></body>', $cover_page);
$cover_case_parser = new WP_HTML_Tag_Processor(Manacost_Article_Cover::optimize_html($cover_case_page));
$cover_case_parser->next_tag(['tag_name' => 'IMG', 'class_name' => 'wp-image-128']);
hs_integration_assert($cover_case_parser->get_attribute('fetchpriority') === null, 'case-insensitive lazy image retains high priority');
$cover_attachment_page = str_replace('class="entry-thumb"', 'class="entry-thumb wp-image-129"', $cover_page);
$cover_attachment_parser = new WP_HTML_Tag_Processor(Manacost_Article_Cover::optimize_html($cover_attachment_page));
$cover_attachment_parser->next_tag(['tag_name' => 'IMG', 'class_name' => 'wp-image-129']);
hs_integration_assert($cover_attachment_parser->get_attribute('fetchpriority') === 'high' && $cover_attachment_parser->get_attribute('loading') === 'eager', 'attachment cover lost priority');
hs_integration_assert(Manacost_Article_Cover::optimize_html($cover_priority_result) === $cover_priority_result, 'priority normalization is not idempotent');
hs_integration_assert(str_contains($cover_result, $cover_module_image), 'desktop module thumbnail was changed');
hs_integration_assert(str_contains($cover_result, '<noscript><img src="' . $cover_url . '"></noscript>'), 'no-JS fallback changed');
$cover_parser = new WP_HTML_Tag_Processor($cover_result);
$cover_parser->next_tag('LINK');
hs_integration_assert($cover_parser->get_attribute('href') === $cover_url, 'preload still downloads a different format URL');
hs_integration_assert($cover_parser->get_attribute('imagesrcset') === $cover_srcset, 'preload candidates differ from IMG');
hs_integration_assert($cover_parser->get_attribute('imagesizes') === $cover_sizes, 'preload sizes differ from IMG');
$cover_parser->next_tag('LINK');
hs_integration_assert($cover_parser->get_attribute('href') === 'https://hs-manacost.ru/banner.webp', 'unrelated banner preload changed');
hs_integration_assert(Manacost_Article_Cover::optimize_html($cover_result) === $cover_result, 'cover processing is not idempotent');

$cover_previous_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';
$cover_mobile_result = Manacost_Article_Cover::optimize_html($cover_page);
$cover_mobile_src = 'https://hs-manacost.ru/wp-content/uploads/2026/09/cover-768x390.jpg';
$cover_parser = new WP_HTML_Tag_Processor($cover_mobile_result);
$cover_parser->next_tag('IMG');
hs_integration_assert($cover_parser->get_attribute('src') === $cover_mobile_src, 'mobile cover still downloads the original');
hs_integration_assert($cover_parser->get_attribute('srcset') === $cover_mobile_src . ' 768w', 'mobile cover candidates are not bounded');
$cover_parser = new WP_HTML_Tag_Processor($cover_mobile_result);
$cover_parser->next_tag('LINK');
hs_integration_assert($cover_parser->get_attribute('href') === $cover_mobile_src, 'mobile preload still downloads the original');
hs_integration_assert($cover_parser->get_attribute('imagesrcset') === $cover_mobile_src . ' 768w', 'mobile preload candidates differ from IMG');
$cover_parser = new WP_HTML_Tag_Processor($cover_mobile_result);
$cover_parser->next_tag(['tag_name' => 'DIV', 'class_name' => 'td-module-thumb']);
$cover_parser->next_tag('IMG');
hs_integration_assert($cover_parser->get_attribute('src') === $cover_module_url, 'mobile module thumbnail URL changed');
hs_integration_assert($cover_parser->get_attribute('loading') === 'lazy', 'mobile module thumbnail is not lazy');
hs_integration_assert($cover_parser->get_attribute('decoding') === 'async', 'mobile module thumbnail is not async-decoded');
hs_integration_assert($cover_parser->get_attribute('data-no-lazy') === null, 'mobile module thumbnail still opts out of lazy-loading');
$cover_small_srcset = $cover_url . ' 1176w, https://hs-manacost.ru/wp-content/uploads/2026/09/cover-300x152.jpg 300w';
$cover_small_page = str_replace($cover_srcset, $cover_small_srcset, $cover_page);
$cover_small_result = Manacost_Article_Cover::optimize_html($cover_small_page);
$cover_parser = new WP_HTML_Tag_Processor($cover_small_result);
$cover_parser->next_tag('IMG');
hs_integration_assert($cover_parser->get_attribute('src') === $cover_url, 'mobile cover fell back to an undersized candidate');
hs_integration_assert($cover_parser->get_attribute('srcset') === $cover_small_srcset, 'mobile srcset changed without an adequate candidate');
if (null === $cover_previous_user_agent) {
    unset($_SERVER['HTTP_USER_AGENT']);
} else {
    $_SERVER['HTTP_USER_AGENT'] = $cover_previous_user_agent;
}

foreach (['<div class="td-module-thumb">' . $cover_tag . '</div>', '<div class="td-post-featured-image"></div>' . $cover_tag, '<div class="td-post-featured-image"><picture><source srcset="art.webp">' . $cover_tag . '</picture></div>'] as $cover_unsupported) {
    $cover_unmodified = '<html><head></head><body>' . $cover_unsupported . '</body></html>';
    hs_integration_assert(Manacost_Article_Cover::optimize_html($cover_unmodified) === $cover_unmodified, 'unrelated or unsupported markup was changed');
}

$cover_plain = '<html><head><link rel="preload" as="image" href="' . $cover_url . '.webp" imagesrcset="stale.webp 2x" imagesizes="100vw"></head><body><div class="td-post-featured-image"><img src="' . $cover_url . '" width="1176" height="597"></div></body></html>';
$cover_plain_result = Manacost_Article_Cover::optimize_html($cover_plain);
$cover_parser = new WP_HTML_Tag_Processor($cover_plain_result);
$cover_parser->next_tag('LINK');
hs_integration_assert($cover_parser->get_attribute('imagesrcset') === null && $cover_parser->get_attribute('imagesizes') === null, 'stale responsive preload attributes remain');

// Test actual query/user boundaries; restore the integration caller's globals.
$cover_previous_query = $GLOBALS['wp_query'];
$cover_previous_user = get_current_user_id();
$GLOBALS['wp_query'] = new WP_Query(['p' => $postId, 'post_type' => 'post']);
wp_set_current_user(0);
hs_integration_assert(has_filter('rocket_buffer', ['Manacost_Article_Cover', 'filter_html']) === PHP_INT_MAX, 'final cacheable Rocket pass is missing');
hs_integration_assert(apply_filters('rocket_buffer', $cover_page) !== $cover_page, 'anonymous article not optimized through Rocket');
wp_set_current_user(1);
hs_integration_assert(Manacost_Article_Cover::filter_html($cover_page) === $cover_page, 'authenticated page was changed');
wp_set_current_user(0);
$GLOBALS['wp_query']->is_preview = true;
hs_integration_assert(Manacost_Article_Cover::filter_html($cover_page) === $cover_page, 'preview was changed');
$GLOBALS['wp_query'] = new WP_Query(['post_type' => 'post']);
hs_integration_assert(Manacost_Article_Cover::filter_html($cover_page) === $cover_page, 'listing was changed');
$GLOBALS['wp_query'] = $cover_previous_query;
wp_set_current_user($cover_previous_user);

echo "Article cover integration assertions: OK\n";
