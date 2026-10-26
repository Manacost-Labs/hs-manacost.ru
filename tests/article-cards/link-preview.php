<?php
/** Ordinary links must remain native: no preview endpoint or render decoration. */
function preview_check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
preview_check(!class_exists('HS_Article_Link_Preview'), 'Link preview must not be registered');
$response = rest_do_request(new WP_REST_Request('GET', '/hs-article-cards/v1/preview'));
preview_check($response->get_status() === 404, 'Removed preview endpoint must return 404');
preview_check(!wp_script_is('hs-link-preview', 'registered'), 'No preview JavaScript');
preview_check(!wp_style_is('hs-link-preview', 'registered'), 'No preview stylesheet');
echo "Native article links: PASS\n";
