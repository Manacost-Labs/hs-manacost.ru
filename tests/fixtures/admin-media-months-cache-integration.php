<?php
/** Native-core test; disposable localhost only, no image files or S3 writes. */
if ( 'local' !== wp_get_environment_type() || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) {
	throw new RuntimeException( 'Local media-cache fixture only.' );
}
$user = get_user_by( 'login', 'integration-admin' );
if ( ! $user ) {
	throw new RuntimeException( 'Fixture administrator missing.' );
}
wp_set_current_user( $user->ID );
set_current_screen( 'post' );

function hs_media_months_expect( $condition, $label ) {
	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}
}

function hs_media_months_render() {
	global $wp_actions, $wpdb;
	$wp_actions['wp_enqueue_media'] = 0;
	$result = null;
	$read = static function ( $settings ) use ( &$result ) {
		$result = $settings['months'];
		return $settings;
	};
	add_filter( 'media_view_settings', $read, PHP_INT_MAX );
	$queries = $wpdb->num_queries;
	wp_enqueue_media();
	remove_filter( 'media_view_settings', $read, PHP_INT_MAX );
	return array( 'months' => $result, 'queries' => $wpdb->num_queries - $queries );
}

function hs_media_months_native() {
	remove_filter( 'media_library_months_with_files', array( 'HS_Admin_Media_Months_Cache', 'months' ), PHP_INT_MAX );
	$result = hs_media_months_render();
	add_filter( 'media_library_months_with_files', array( 'HS_Admin_Media_Months_Cache', 'months' ), PHP_INT_MAX );
	return $result;
}

$original_cache = wp_using_ext_object_cache();
$ids = array();
try {
	// Exercise public object-cache APIs locally. Distributed concurrency is tested separately.
	wp_using_ext_object_cache( true );
	HS_Admin_Media_Months_Cache::invalidate();
	$ids[] = wp_insert_attachment( array( 'post_title' => 'HS disposable media months', 'post_date' => '1998-02-01 12:00:00', 'post_status' => 'inherit', 'post_mime_type' => 'image/png' ), false, 0, true );
	hs_media_months_expect( ! is_wp_error( $ids[0] ), 'attachment creation' );
	$native = hs_media_months_native();
	$cold = hs_media_months_render();
	$warm = hs_media_months_render();
	hs_media_months_expect( serialize( $native['months'] ) === serialize( $cold['months'] ) && serialize( $cold['months'] ) === serialize( $warm['months'] ), 'core month identity' );
	hs_media_months_expect( $cold['queries'] === $warm['queries'] + 1, 'warm query not avoided' );

	wp_update_post( array( 'ID' => $ids[0], 'post_date' => '1997-03-01 12:00:00' ) );
	hs_media_months_expect( null === HS_Admin_Media_Months_Cache::months( null ), 'date invalidation' );
	hs_media_months_expect( serialize( hs_media_months_native()['months'] ) === serialize( hs_media_months_render()['months'] ), 'updated date differs' );
	hs_media_months_render();

	wp_update_post( array( 'ID' => $ids[0], 'post_type' => 'post', 'post_status' => 'draft' ) );
	hs_media_months_expect( null === HS_Admin_Media_Months_Cache::months( null ), 'converted attachment invalidation' );
	hs_media_months_expect( serialize( hs_media_months_native()['months'] ) === serialize( hs_media_months_render()['months'] ), 'converted month differs' );

	$ids[] = wp_insert_attachment( array( 'post_title' => 'HS disposable media month deletion', 'post_date' => '1996-04-01 12:00:00', 'post_status' => 'inherit' ), false, 0, true );
	hs_media_months_expect( ! is_wp_error( $ids[1] ), 'second attachment creation' );
	hs_media_months_render();
	hs_media_months_render();
	wp_delete_attachment( $ids[1], true );
	hs_media_months_expect( null === HS_Admin_Media_Months_Cache::months( null ), 'deletion invalidation' );
	hs_media_months_expect( serialize( hs_media_months_native()['months'] ) === serialize( hs_media_months_render()['months'] ), 'deleted month differs' );

	wp_using_ext_object_cache( false );
	hs_media_months_expect( null === HS_Admin_Media_Months_Cache::months( null ), 'nonpersistent fallback' );
	echo "Native WordPress media months: PASS\n";
} finally {
	foreach ( $ids as $id ) {
		if ( is_int( $id ) && $id > 0 ) {
			wp_delete_post( $id, true );
		}
	}
	HS_Admin_Media_Months_Cache::invalidate();
	wp_using_ext_object_cache( $original_cache );
}
