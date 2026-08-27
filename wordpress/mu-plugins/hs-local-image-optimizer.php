<?php
/**
 * Plugin Name: HS Local Image Optimizer
 * Description: Creates validated local WebP/AVIF sidecars after WordPress finishes image sub-sizes.
 * Version: 0.1.0
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

$hs_local_image_optimizer_src = __DIR__ . '/hs-local-image-optimizer/src/';
if ( ! is_dir( $hs_local_image_optimizer_src ) ) {
	// Repository layout used by the isolated test suite.
	$hs_local_image_optimizer_src = dirname( __DIR__ ) . '/src/';
}
foreach (
	[
		'ProfileSelector.php',
		'AttachmentFiles.php',
		'CandidatePublisher.php',
		'EncoderCommand.php',
		'ProcessRunner.php',
		'ImageWorker.php',
		'AdminStats.php',
		'AdminMediaUi.php',
	] as $hs_local_image_optimizer_file
) {
	require_once $hs_local_image_optimizer_src . $hs_local_image_optimizer_file;
}
unset( $hs_local_image_optimizer_src, $hs_local_image_optimizer_file );

final class HS_Local_Image_Optimizer_WordPress {
	/** @var array<int, int> */
	private static array $pending_attachments = [];

	private const HOOK            = 'hs_local_image_optimizer_process_attachment';
	private const GROUP           = 'hs-local-image-optimizer';
	private const LOCK_OPTION     = 'hs_local_image_optimizer_worker_lock';
	private const QUEUED_META     = '_hs_local_image_optimizer_queued_at';
	private const FINISHED_META   = '_hs_local_image_optimizer_finished_at';
	private const RESULT_META     = '_hs_local_image_optimizer_result';
	private const PIPELINE_META   = '_hs_local_image_optimizer_pipeline';
	private const ENABLED_OPTION  = 'hs_local_image_optimizer_enabled';
	private const PIPELINE        = 'quality-v1';
	private const LOCK_TTL        = 900;
	private const MAX_ATTEMPTS    = 5;

	public static function boot(): void {
		add_filter( 'wp_generate_attachment_metadata', [ __CLASS__, 'queue_after_metadata' ], 99, 2 );
		add_action( 'shutdown', [ __CLASS__, 'flush_pending_queue' ], 20 );
		add_action( self::HOOK, [ __CLASS__, 'process_attachment' ], 10, 2 );
		add_action( 'delete_attachment', [ __CLASS__, 'delete_sidecars' ], 5, 1 );

		// Imagify must not process the same attachment when the local pipeline is active.
		add_filter( 'imagify_auto_optimize_attachment', [ __CLASS__, 'filter_imagify_auto_optimize' ], 1, 3 );
		add_filter( 'imagify_auto_optimize_optimized_attachment', [ __CLASS__, 'filter_imagify_auto_optimize' ], 1, 3 );

		if ( is_admin() ) {
			HS_Local_Image_Admin_Media_UI::boot();
		}
	}

	public static function queue_after_metadata( $metadata, int $attachment_id ) {
		if ( is_array( $metadata ) && ! empty( $metadata ) && self::is_enabled_for( $attachment_id ) ) {
			self::$pending_attachments[ $attachment_id ] = $attachment_id;
		}

		return $metadata;
	}

	public static function flush_pending_queue(): void {
		$attachment_ids           = self::$pending_attachments;
		self::$pending_attachments = [];

		foreach ( $attachment_ids as $attachment_id ) {
			self::queue_attachment( $attachment_id );
		}
	}

	public static function filter_imagify_auto_optimize( $optimize, int $attachment_id, $metadata = [] ): bool {
		return self::is_enabled_for( $attachment_id ) && self::is_supported_attachment( $attachment_id )
			? false
			: (bool) $optimize;
	}

	public static function queue_attachment( int $attachment_id, int $attempt = 0 ): void {
		if ( ! self::is_enabled_for( $attachment_id ) || ! self::is_supported_attachment( $attachment_id ) ) {
			return;
		}

		update_post_meta( $attachment_id, self::QUEUED_META, time() );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, [ $attachment_id, $attempt ], self::GROUP, true );
			self::log( "queued id={$attachment_id} attempt={$attempt} scheduler=action_scheduler" );
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK, [ $attachment_id, $attempt ] ) ) {
			wp_schedule_single_event( time() + 1, self::HOOK, [ $attachment_id, $attempt ] );
			self::log( "queued id={$attachment_id} attempt={$attempt} scheduler=wp_cron" );
		}
	}

	public static function process_attachment( int $attachment_id, int $attempt = 0 ): void {
		if ( ! self::is_enabled_for( $attachment_id ) || ! self::is_supported_attachment( $attachment_id ) ) {
			return;
		}

		$lock_token = self::acquire_lock();
		if ( null === $lock_token ) {
			self::retry( $attachment_id, $attempt, 'worker_busy' );
			return;
		}

		try {
			$attached_file = get_attached_file( $attachment_id, true );
			$metadata      = wp_get_attachment_metadata( $attachment_id );
			if ( ! is_string( $attached_file ) || ! is_array( $metadata ) ) {
				self::finish( $attachment_id, [ 'status' => 'failed_attachment_data' ] );
				return;
			}

			$parent_id        = (int) wp_get_post_parent_id( $attachment_id );
			$parent_post_type = $parent_id > 0 ? (string) get_post_type( $parent_id ) : '';
			$worker           = new HS_Local_Image_Worker();
			$results          = [];

			foreach ( HS_Local_Image_Attachment_Files::collect( $attached_file, $metadata ) as $file ) {
				$results[ basename( $file ) ] = $worker->process(
					$file,
					[
						'post_type' => $parent_post_type,
					]
				);
			}

			self::finish(
				$attachment_id,
				[
					'status'   => 'processed',
					'pipeline' => self::PIPELINE,
					'files'    => $results,
				]
			);
		} catch ( Throwable $exception ) {
			self::finish(
				$attachment_id,
				[
					'status'  => 'failed_exception',
					'message' => substr( $exception->getMessage(), 0, 500 ),
				]
			);
		} finally {
			self::release_lock( $lock_token );
		}
	}

	public static function delete_sidecars( int $attachment_id ): void {
		$attached_file = get_attached_file( $attachment_id, true );
		$metadata      = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_string( $attached_file ) || ! is_array( $metadata ) ) {
			return;
		}

		foreach ( HS_Local_Image_Attachment_Files::collect( $attached_file, $metadata ) as $file ) {
			foreach ( [ $file . '.webp', $file . '.avif' ] as $sidecar ) {
				if ( is_file( $sidecar ) ) {
					@unlink( $sidecar );
				}
			}
			foreach ( glob( $file . '.*.hs-local-*.tmp.*' ) ?: [] as $candidate ) {
				@unlink( $candidate );
			}
		}
	}

	private static function is_enabled_for( int $attachment_id ): bool {
		if ( defined( 'HS_LOCAL_IMAGE_OPTIMIZER_ENABLED' ) && true === HS_LOCAL_IMAGE_OPTIMIZER_ENABLED ) {
			return true;
		}
		if ( 'yes' === get_option( self::ENABLED_OPTION, 'no' ) ) {
			return true;
		}

		return defined( 'HS_LOCAL_IMAGE_OPTIMIZER_CANARY_ATTACHMENT_ID' )
			&& $attachment_id > 0
			&& $attachment_id === (int) HS_LOCAL_IMAGE_OPTIMIZER_CANARY_ATTACHMENT_ID;
	}

	private static function is_supported_attachment( int $attachment_id ): bool {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		return in_array( (string) get_post_mime_type( $attachment_id ), [ 'image/jpeg', 'image/png' ], true );
	}

	private static function acquire_lock(): ?string {
		$token   = wp_generate_uuid4();
		$payload = [ 'token' => $token, 'created_at' => time() ];
		if ( add_option( self::LOCK_OPTION, $payload, '', false ) ) {
			return $token;
		}

		$current = get_option( self::LOCK_OPTION, [] );
		if ( ! is_array( $current ) || ( time() - (int) ( $current['created_at'] ?? 0 ) ) <= self::LOCK_TTL ) {
			return null;
		}

		delete_option( self::LOCK_OPTION );
		return add_option( self::LOCK_OPTION, $payload, '', false ) ? $token : null;
	}

	private static function release_lock( string $token ): void {
		$current = get_option( self::LOCK_OPTION, [] );
		if ( is_array( $current ) && hash_equals( (string) ( $current['token'] ?? '' ), $token ) ) {
			delete_option( self::LOCK_OPTION );
		}
	}

	private static function retry( int $attachment_id, int $attempt, string $reason ): void {
		$next_attempt = $attempt + 1;
		if ( $next_attempt >= self::MAX_ATTEMPTS ) {
			self::finish( $attachment_id, [ 'status' => 'failed_retries', 'reason' => $reason ] );
			return;
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 60, self::HOOK, [ $attachment_id, $next_attempt ], self::GROUP, true );
			return;
		}

		wp_schedule_single_event( time() + 60, self::HOOK, [ $attachment_id, $next_attempt ] );
	}

	/** @param array<string, mixed> $result */
	private static function finish( int $attachment_id, array $result ): void {
		update_post_meta( $attachment_id, self::FINISHED_META, time() );
		update_post_meta( $attachment_id, self::PIPELINE_META, self::PIPELINE );
		update_post_meta( $attachment_id, self::RESULT_META, wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) );
		self::log( 'finish id=' . $attachment_id . ' status=' . (string) ( $result['status'] ?? 'unknown' ) );
	}

	private static function log( string $message ): void {
		error_log( '[hs-local-image-optimizer] ' . $message );
	}
}

HS_Local_Image_Optimizer_WordPress::boot();
