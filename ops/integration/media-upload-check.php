<?php
/** Verify only the disposable browser-created attachment IDs. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type()
    || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
    throw new RuntimeException( 'Disposable local WordPress is required.' );
}
$report = json_decode( file_get_contents( ABSPATH . '.integration/media-upload-report.json' ), true, 512, JSON_THROW_ON_ERROR );
if ( 'disposable-integration' !== ( $report['environment'] ?? '' )
    || count( $report['uploads'] ?? array() ) !== 2
    || count( array_unique( array_column( $report['uploads'], 'id' ) ) ) !== 2 ) {
    throw new RuntimeException( 'Expected two browser uploads.' );
}
foreach ( $report['uploads'] as $upload ) {
    $id = (int) $upload['id'];
    $file = get_attached_file( $id );
    if ( 'attachment' !== get_post_type( $id ) || ! is_string( $file )
        || ! preg_match( '/^hs-http-upload(?:-[0-9]+)?\.png$/', basename( $file ) )
        || (int) wp_get_post_parent_id( $id ) !== (int) $report['draft_id'] ) {
        throw new RuntimeException( 'Attachment is not owned by this browser fixture.' );
    }
    $metadata = wp_get_attachment_metadata( $id );
    $sizes = array_keys( $metadata['sizes'] ?? array() );
    sort( $sizes );
    if ( $sizes !== array( 'medium', 'medium_large', 'thumbnail' ) ) {
        throw new RuntimeException( 'Upload did not defer non-preview image sizes.' );
    }
    if ( ! wp_next_scheduled( 'hs_media_upload_accelerator_generate_subsizes', array( $id, 0 ) ) ) {
        throw new RuntimeException( 'Deferred size job is missing.' );
    }
    if ( hash_file( 'sha256', get_attached_file( $id ) ) !== $upload['sha256'] ) {
        throw new RuntimeException( 'Stored original checksum differs.' );
    }
}
$draft = get_post( (int) $report['draft_id'] );
if ( ! $draft || 'draft' !== $draft->post_status
    || 'Disposable HTTP media upload regression' !== $draft->post_title ) {
    throw new RuntimeException( 'Browser draft was not persisted.' );
}
foreach ( $report['uploads'] as $upload ) {
    if ( ! str_contains( $draft->post_content, 'wp-image-' . (int) $upload['id'] ) ) {
        throw new RuntimeException( 'Saved content lost the inserted image.' );
    }
}
echo "Media metadata, queue, original hashes and stored draft: OK\n";

// Every ownership and integrity check above must pass before any cleanup.
// Keep existing visual/performance fixtures unchanged for the following suite.
foreach ( $report['uploads'] as $upload ) {
    $id = (int) $upload['id'];
    $file = get_attached_file( $id );
    $cleared = wp_clear_scheduled_hook( 'hs_media_upload_accelerator_generate_subsizes', array( $id, 0 ), true );
    if ( is_wp_error( $cleared ) || $cleared < 1
        || wp_next_scheduled( 'hs_media_upload_accelerator_generate_subsizes', array( $id, 0 ) ) ) {
        throw new RuntimeException( 'Disposable deferred event cleanup failed.' );
    }
    if ( ! wp_delete_attachment( $id, true ) || get_post( $id ) ) {
        throw new RuntimeException( 'Disposable attachment cleanup failed.' );
    }
    if ( file_exists( $file ) ) {
        throw new RuntimeException( 'Disposable original file cleanup failed.' );
    }
}
if ( ! wp_delete_post( $draft->ID, true ) || get_post( $draft->ID ) ) {
    throw new RuntimeException( 'Disposable draft cleanup failed.' );
}
echo "Only the two browser attachments and their draft were removed from the disposable database.\n";
