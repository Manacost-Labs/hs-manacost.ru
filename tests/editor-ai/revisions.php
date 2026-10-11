<?php
/** Saved suggestions remain recoverable through the actual revision API. */
if ( 'local' !== wp_get_environment_type() || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Disposable WordPress required.' );
}
wp_set_current_user( 1 );
$post_id = (int) get_option( 'hs_editor_ai_test_post' );
$before = get_post_field( 'post_content', $post_id );
$revision = wp_save_post_revision( $post_id );
if ( ! is_int( $revision ) || $revision <= 0 ) {
	$revisions = wp_get_post_revisions( $post_id );
	$revision = array_key_first( $revisions );
}
if ( ! is_int( $revision ) || get_post_field( 'post_content', $revision ) !== $before ) {
	throw new RuntimeException( 'Revision snapshot does not match saved text.' );
}
wp_update_post( array( 'ID' => $post_id, 'post_content' => '<p>Следующая редакторская версия.</p>' ) );
$restored = wp_restore_post_revision( $revision );
if ( $restored !== $post_id || get_post_field( 'post_content', $post_id ) !== $before ) {
	throw new RuntimeException( 'Revision restore lost the accepted suggestion.' );
}
echo "PASS accepted suggestion restored from native revision\n";
// Publication is a real test, but its fixture must not alter later home snapshots.
if ( ! wp_delete_post( $post_id, true ) ) {
	throw new RuntimeException( 'Could not remove the disposable publication fixture.' );
}
delete_option( 'hs_editor_ai_test_post' );
echo "PASS disposable article removed before subsequent visual checks\n";
