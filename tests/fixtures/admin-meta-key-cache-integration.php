<?php
/**
 * Disposable-local integration probe for the Custom Fields meta-key cache.
 *
 * Run with `wp eval-file` only after the isolated integration site is ready.
 * This fixture deliberately refuses every host except the local test site before
 * it changes a user, post, cache generation, or metadata row.
 */

if ( ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
	throw new RuntimeException( 'Integration fixture requires the local environment.' );
}

$integration_host = wp_parse_url( home_url(), PHP_URL_HOST );
if ( ! in_array( $integration_host, array( '127.0.0.1', 'localhost' ), true ) ) {
	throw new RuntimeException( 'Integration fixture requires a localhost URL.' );
}

$integration_admin = get_user_by( 'login', 'integration-admin' );
if ( ! $integration_admin || (int) $integration_admin->ID < 1 ) {
	throw new RuntimeException( 'Integration fixture requires its integration administrator.' );
}

wp_set_current_user( (int) $integration_admin->ID );

require_once ABSPATH . 'wp-admin/includes/template.php';
set_current_screen( 'post' );

/**
 * @param bool   $condition Expected condition.
 * @param string $label     Stable failure label; never include editor output.
 * @return void
 */
function hs_admin_meta_cache_expect( $condition, $label ) {
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
}

/**
 * Render core's real Custom Fields form without exposing its HTML, nonce or values.
 *
 * @param WP_Post $post Post to render.
 * @return array{hash:string,queries:int}
 */
function hs_admin_meta_cache_render( $post ) {
	global $wpdb;
	$before = (int) $wpdb->num_queries;
	ob_start();
	meta_form( $post );
	$html = (string) ob_get_clean();

	return array(
		'hash'    => hash( 'sha256', $html ),
		'queries' => (int) $wpdb->num_queries - $before,
	);
}

/**
 * @param WP_Post $post Post used as the Custom Fields screen context.
 * @return array<int, string>|null
 */
function hs_admin_meta_cache_dropdown_keys( $post ) {
	return apply_filters( 'postmeta_form_keys', null, $post );
}

/**
 * @param string $needle Expected meta-key name.
 * @param array<int, string>|null $keys Candidate key list.
 * @return void
 */
function hs_admin_meta_cache_expect_key( $needle, $keys ) {
	hs_admin_meta_cache_expect( is_array( $keys ) && in_array( $needle, $keys, true ), 'dropdown key missing' );
}

/**
 * @param string $needle Unexpected meta-key name.
 * @param array<int, string>|null $keys Candidate key list.
 * @return void
 */
function hs_admin_meta_cache_expect_no_key( $needle, $keys ) {
	hs_admin_meta_cache_expect( is_array( $keys ) && ! in_array( $needle, $keys, true ), 'dropdown key was retained' );
}

global $wpdb;

$post_id = 0;

try {
	$marker   = wp_generate_uuid4();
	$seed_key = 'aaa_hs_admin_meta_cache_seed';
	$post_id  = wp_insert_post(
		array(
			'post_status'  => 'draft',
			'post_title'   => 'HS integration meta cache fixture',
			'post_content' => 'hs-admin-meta-cache:' . $marker,
		),
		true
	);
	hs_admin_meta_cache_expect( ! is_wp_error( $post_id ) && (int) $post_id > 0, 'temporary draft was not created' );
	$post_id = (int) $post_id;

	$original_content_hash = hash( 'sha256', (string) get_post_field( 'post_content', $post_id ) );
	add_post_meta( $post_id, $seed_key, 'seed' );
	$private_mid = (int) add_post_meta( $post_id, '_hs_admin_meta_cache_private', 'private' );
	hs_admin_meta_cache_expect( $private_mid > 0, 'private seed was not created' );
	$post = get_post( $post_id );
	hs_admin_meta_cache_expect( $post instanceof WP_Post, 'temporary draft unavailable' );

	// Native core establishes the real form's result before the MU filter is restored.
	remove_filter( 'postmeta_form_keys', array( 'HS_Admin_Meta_Key_Cache', 'keys' ), PHP_INT_MAX );
	$native = hs_admin_meta_cache_render( $post );
	add_filter( 'postmeta_form_keys', array( 'HS_Admin_Meta_Key_Cache', 'keys' ), PHP_INT_MAX, 1 );

	// Start a known cache generation so this render is cold and the next is warm.
	HS_Admin_Meta_Key_Cache::invalidate( 0, $post_id, $seed_key );
	$cold = hs_admin_meta_cache_render( $post );
	$warm = hs_admin_meta_cache_render( $post );
	hs_admin_meta_cache_expect( $native['hash'] === $cold['hash'] && $cold['hash'] === $warm['hash'], 'core form parity changed' );
	hs_admin_meta_cache_expect( $cold['queries'] === $native['queries'], 'cold dropdown query count changed' );
	hs_admin_meta_cache_expect( $warm['queries'] === $cold['queries'] - 1, 'warm dropdown query was not eliminated' );
	hs_admin_meta_cache_expect_key( $seed_key, hs_admin_meta_cache_dropdown_keys( $post ) );
	hs_admin_meta_cache_dropdown_keys( $post );
	hs_admin_meta_cache_expect( (bool) update_post_meta( $post_id, $seed_key, 'seed changed' ), 'public value update failed' );
	$before_public = (int) $wpdb->num_queries;
	hs_admin_meta_cache_dropdown_keys( $post );
	hs_admin_meta_cache_expect( (int) $wpdb->num_queries === $before_public, 'public value update invalidated keys' );

	$added_key = 'aaa_hs_admin_meta_cache_added';
	add_post_meta( $post_id, $added_key, 'added' );
	hs_admin_meta_cache_expect_key( $added_key, hs_admin_meta_cache_dropdown_keys( $post ) );
	delete_post_meta( $post_id, $added_key );
	hs_admin_meta_cache_expect_no_key( $added_key, hs_admin_meta_cache_dropdown_keys( $post ) );

	$rename_key = 'aaa_hs_admin_meta_cache_rename';
	$rename_id  = add_post_meta( $post_id, $rename_key, 'rename' );
	hs_admin_meta_cache_expect( (int) $rename_id > 0, 'rename seed was not created' );
	hs_admin_meta_cache_expect_key( $rename_key, hs_admin_meta_cache_dropdown_keys( $post ) );
	hs_admin_meta_cache_expect( (bool) update_metadata_by_mid( 'post', (int) $rename_id, 'private rename', '_hs_admin_meta_cache_renamed' ), 'rename failed' );
	hs_admin_meta_cache_expect_no_key( $rename_key, hs_admin_meta_cache_dropdown_keys( $post ) );

	// A private value-only update must preserve the already-warm dropdown generation.
	hs_admin_meta_cache_dropdown_keys( $post );
	hs_admin_meta_cache_expect( (bool) update_metadata_by_mid( 'post', $private_mid, 'private changed' ), 'private value update failed' );
	$before_private = (int) $wpdb->num_queries;
	hs_admin_meta_cache_dropdown_keys( $post );
	hs_admin_meta_cache_expect( (int) $wpdb->num_queries === $before_private, 'private value update invalidated keys' );

	$override_key = 'aaa_hs_admin_meta_cache_override';
	$override     = static function () use ( $override_key ) {
		return array( $override_key );
	};
	add_filter( 'postmeta_form_keys', $override, PHP_INT_MAX - 1 );
	hs_admin_meta_cache_expect( array( $override_key ) === hs_admin_meta_cache_dropdown_keys( $post ), 'upstream override changed' );
	remove_filter( 'postmeta_form_keys', $override, PHP_INT_MAX - 1 );

	$denied_key = 'aaa_hs_admin_meta_cache_denied';
	add_post_meta( $post_id, $denied_key, 'denied' );
	$deny = static function () {
		return false;
	};
	add_filter( "auth_post_meta_{$denied_key}_for_post", $deny, 10 );
	ob_start();
	meta_form( $post );
	$permission_html = (string) ob_get_clean();
	remove_filter( "auth_post_meta_{$denied_key}_for_post", $deny, 10 );
	hs_admin_meta_cache_expect( false === strpos( $permission_html, "value='" . $denied_key . "'" ), 'core meta permission was bypassed' );
	hs_admin_meta_cache_expect( false !== strpos( $permission_html, "value='" . $seed_key . "'" ), 'core meta permission hid allowed key' );
	hs_admin_meta_cache_expect( $original_content_hash === hash( 'sha256', (string) get_post_field( 'post_content', $post_id ) ), 'draft content changed' );

	echo "PASS guard\nPASS parity\nPASS warm-query\nPASS mutations\nPASS override-permission\n";
} finally {
	if ( is_int( $post_id ) && $post_id > 0 ) {
		wp_delete_post( $post_id, true );
	}
}
