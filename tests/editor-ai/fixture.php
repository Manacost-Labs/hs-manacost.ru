<?php
/** Disposable integration transport, never copied by deployment. */
if ( 'local' !== wp_get_environment_type() || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Editor AI tests require disposable loopback WordPress.' );
}
putenv( 'HS_EDITOR_AI_API_KEY=integration-fixture' );
if ( ! getenv( 'HS_EDITOR_AI_REAL_TOOLTIP' ) ) {
	function hs_smart_tooltip_get_search_catalog() {
		return array( array( 'id' => 'EX1_116', 'name' => 'Лирой Дженкинс' ) );
	}
	function hs_smart_tooltip_get_bgs_search_catalog() {
		return array( array( 'id' => 'BG_001', 'name' => 'Лирой Дженкинс' ) );
	}
}
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( 'https://api.anthropic.com/v1/messages' !== $url ) {
		return $pre;
	}
	update_option( 'hs_editor_ai_test_calls', (int) get_option( 'hs_editor_ai_test_calls', 0 ) + 1, false );
	$delay = min( 1500, max( 0, (int) get_option( 'hs_editor_ai_test_delay_ms', 0 ) ) );
	if ( $delay ) {
		usleep( $delay * 1000 );
	}
	$variant = get_option( 'hs_editor_ai_test_response', 'normal' );
	if ( 'timeout' === $variant ) {
		return new WP_Error( 'transport', 'PRIVATE PROVIDER DETAIL' );
	}
	if ( is_numeric( $variant ) ) {
		return array( 'headers' => array( 'retry-after' => '120' ), 'body' => 'PRIVATE PROVIDER DETAIL', 'response' => array( 'code' => (int) $variant, 'message' => 'fixture' ) );
	}
	$request = json_decode( $args['body'], true );
	$input = json_decode( $request['messages'][0]['content'], true );
	$format = $request['output_config']['format'] ?? array();
	$expected = isset( $input['original'] ) ? array( 'approved' ) : array( 'edits' );
	if ( 'json_schema' !== ( $format['type'] ?? '' ) || ( $format['schema']['required'] ?? null ) !== $expected || false !== ( $format['schema']['additionalProperties'] ?? null ) ) {
		throw new RuntimeException( 'Structured response contract is required' );
	}
	if ( isset( $input['original'] ) ) {
		$result = array( 'approved' => 'reject-review' !== $variant );
	} else {
		$edits = array();
		foreach ( $input['segments'] ?? array() as $segment ) {
			$text = $segment['text'];
			if ( ! empty( $input['catalog'] ) ) {
				$tag = str_contains( $input['context'], 'Полях сражений' ) ? 'hs_bg' : 'hs_card';
				$id = 'hs_bg' === $tag ? 'BG_001' : 'EX1_116';
				$text = str_replace( 'Лирой Дженкинс', '[' . $tag . ' id="' . $id . '"]Лирой Дженкинс[/' . $tag . ']', $text );
			} else {
				$text = str_replace( 'мнаны', 'маны', $text );
			}
			if ( $text !== $segment['text'] ) {
				$edits[] = array( 'segment' => $segment['segment'], 'replacement' => $text );
			}
		}
		$result = array( 'edits' => $edits );
	}
	$body = 'malformed' === $variant ? 'invalid' : wp_json_encode( array( 'stop_reason' => 'truncated' === $variant ? 'max_tokens' : 'end_turn', 'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $result ) ) ) ) );
	return array( 'headers' => array(), 'body' => $body, 'response' => array( 'code' => 200, 'message' => 'OK' ) );
}, 10, 3 );
// GET remains read-only: browser tests must dispatch both real worker phases.
// Numeric diagnostics exist only in this explicitly enabled local test fixture.
add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
	if ( get_option( 'hs_editor_ai_test_performance' ) && str_starts_with( $request->get_route(), '/hs-editor-ai/v1/jobs' ) ) {
		global $wpdb;
		$response->header( 'X-AI-Test-Queries', (string) $wpdb->num_queries );
		$response->header( 'X-AI-Test-Memory', (string) round( memory_get_peak_usage( true ) / 1048576, 2 ) );
	}
	return $response;
}, 10, 3 );
