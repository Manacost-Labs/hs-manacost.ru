<?php
/** Verify generated wrappers against the actual installed tooltip contract. */
if ( 'local' !== wp_get_environment_type() || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) || ! getenv( 'HS_EDITOR_AI_REAL_TOOLTIP' ) ) {
	throw new RuntimeException( 'Disposable tooltip integration required.' );
}
add_filter( 'pre_http_request', function () {
	throw new RuntimeException( 'Tooltip catalog verification must not require network access.' );
} );
require WP_PLUGIN_DIR . '/hs-tooltip/hs-smart-tooltip.php';
$catalog = Manacost\EditorAi\Catalog::all();
foreach ( array( 'hs_card', 'hs_bg' ) as $tag ) {
	$entries = array_values( array_filter( $catalog, static fn( $card ) => $card['tag'] === $tag ) );
	$card = $entries[0] ?? null;
	if ( ! $card || ! shortcode_exists( $tag ) ) {
		throw new RuntimeException( 'Installed tooltip catalog/handler missing: ' . $tag );
	}
	$wrapper = '[' . $tag . ' id="' . $card['id'] . '"]' . $card['name'] . '[/' . $tag . ']';
	$value = Manacost\EditorAi\Content::apply( $card['name'], array( array( 'segment' => 0, 'replacement' => $wrapper ) ), 'cards', $catalog );
	if ( is_wp_error( $value ) ) {
		throw new RuntimeException( 'Real card identity rejected: ' . $tag );
	}
	$rendered = do_shortcode( $value );
	if ( str_contains( $rendered, '[' . $tag ) || str_contains( $rendered, 'hs-card-missing' ) || html_entity_decode( wp_strip_all_tags( $rendered ), ENT_QUOTES, 'UTF-8' ) !== $card['name'] ) {
		throw new RuntimeException( 'Generated wrapper incompatible with actual tooltip: ' . $tag );
	}
	echo 'PASS actual installed catalog and shortcode handler ' . $tag . "\n";
}
