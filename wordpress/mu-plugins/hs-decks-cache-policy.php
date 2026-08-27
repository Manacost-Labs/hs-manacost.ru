<?php
/**
 * Plugin Name: HS Decks Cache Policy
 * Description: Extends safe cache lifetimes for expensive public deck feed AJAX responses.
 * Version: 1.0.0
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'hs_decks_filter_ajax_cache_ttl',
	static function (): int {
		return 6 * HOUR_IN_SECONDS;
	}
);
