<?php
/**
 * Plugin Name: HS Local Image Optimizer
 * Description: Creates validated local WebP/AVIF sidecars after WordPress finishes image sub-sizes.
 * Version: 0.1.0
 * Author: Manacost
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

$hs_local_image_optimizer_src = __DIR__ . '/hs-local-image-optimizer/src/';
if ( ! is_dir( $hs_local_image_optimizer_src ) ) {
	// Repository layout used by the isolated test suite.
	$hs_local_image_optimizer_src = dirname( __DIR__ ) . '/src/';
}
$hs_local_image_optimizer_files = array(
	'ProfileSelector.php',
	'AttachmentFiles.php',
	'CandidatePublisher.php',
	'class-hs-local-image-encoder-command.php',
	'class-hs-local-image-process-runner.php',
	'class-hs-local-image-worker.php',
	'AdminStats.php',
	'class-hs-local-image-admin-media-ui.php',
);
foreach ( $hs_local_image_optimizer_files as $hs_local_image_optimizer_file ) {
	if ( ! is_readable( $hs_local_image_optimizer_src . $hs_local_image_optimizer_file ) ) {
		// A deployment copies this file before its source directory. Stay
		// inactive for that moment instead of failing every request.
		unset( $hs_local_image_optimizer_src, $hs_local_image_optimizer_files, $hs_local_image_optimizer_file );
		return;
	}
}
foreach ( $hs_local_image_optimizer_files as $hs_local_image_optimizer_file ) {
	require_once $hs_local_image_optimizer_src . $hs_local_image_optimizer_file;
}
unset( $hs_local_image_optimizer_src, $hs_local_image_optimizer_files, $hs_local_image_optimizer_file );

/**
 * Connects the local image pipeline to WordPress media lifecycle hooks.
 */
final class HS_Local_Image_Optimizer_WordPress {
	/**
	 * Attachments waiting to be queued after the response.
	 *
	 * @var array<int, int>
	 */
	private static array $pending_attachments = array();

	private const HOOK           = 'hs_local_image_optimizer_process_attachment';
	private const RECOVERY_HOOK  = 'hs_local_image_optimizer_requeue_lost';
	private const GROUP          = 'hs-local-image-optimizer';
	private const LOCK_OPTION    = 'hs_local_image_optimizer_worker_lock';
	private const QUEUED_META    = '_hs_local_image_optimizer_queued_at';
	private const FINISHED_META  = '_hs_local_image_optimizer_finished_at';
	private const RESULT_META    = '_hs_local_image_optimizer_result';
	private const PIPELINE_META  = '_hs_local_image_optimizer_pipeline';
	private const REQUEUES_META  = '_hs_local_image_optimizer_requeues';
	private const ENABLED_OPTION = 'hs_local_image_optimizer_enabled';
	private const PIPELINE       = 'quality-v1';
	private const LOCK_TTL       = 900;
	private const MAX_ATTEMPTS   = 5;
	private const RETRY_DELAY    = 60;

	// Waiting for the single worker is not a failure: a burst of uploads is
	// allowed to queue for this long, well beyond LOCK_TTL, before giving up.
	private const BUSY_DELAY    = 30;
	private const MAX_BUSY_WAIT = 7200;

	// A job whose process was killed leaves no result. The recovery sweep
	// re-queues such attachments a bounded number of times.
	private const LOST_AFTER     = 900;
	private const LOST_WINDOW    = 604800;
	private const MAX_REQUEUES   = 3;
	private const RECOVERY_BATCH = 20;

	/**
	 * Register WordPress integration hooks.
	 */
	public static function boot(): void {
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'queue_after_metadata' ), 99, 3 );
		add_action( 'shutdown', array( __CLASS__, 'flush_pending_queue' ), 20 );
		add_action( self::HOOK, array( __CLASS__, 'process_attachment' ), 10, 2 );
		add_action( 'delete_attachment', array( __CLASS__, 'delete_sidecars' ), 5, 1 );
		add_action( 'init', array( __CLASS__, 'schedule_recovery' ) );
		add_action( self::RECOVERY_HOOK, array( __CLASS__, 'requeue_lost_attachments' ) );

		// Imagify must not process the same attachment when the local pipeline is active.
		add_filter( 'imagify_auto_optimize_attachment', array( __CLASS__, 'filter_imagify_auto_optimize' ), 1, 3 );
		add_filter( 'imagify_auto_optimize_optimized_attachment', array( __CLASS__, 'filter_imagify_auto_optimize' ), 1, 3 );

		if ( is_admin() ) {
			HS_Local_Image_Admin_Media_UI::boot();
		}
	}

	/**
	 * Collect an attachment after WordPress creates its metadata.
	 *
	 * @param mixed  $metadata Attachment metadata.
	 * @param int    $attachment_id Attachment post ID.
	 * @param string $context Metadata generation context.
	 *
	 * @return mixed
	 */
	public static function queue_after_metadata( $metadata, int $attachment_id, string $context = 'create' ) {
		$should_queue = is_array( $metadata ) && ! empty( $metadata ) && self::is_enabled_for( $attachment_id );
		if ( $should_queue ) {
			$should_queue = (bool) apply_filters(
				'hs_local_image_optimizer_should_queue',
				true,
				$metadata,
				$attachment_id,
				$context
			);
		}

		if ( $should_queue ) {
			self::$pending_attachments[ $attachment_id ] = $attachment_id;
		}

		return $metadata;
	}

	/**
	 * Queue every attachment collected during the request.
	 */
	public static function flush_pending_queue(): void {
		$attachment_ids            = self::$pending_attachments;
		self::$pending_attachments = array();

		foreach ( $attachment_ids as $attachment_id ) {
			self::queue_attachment( $attachment_id );
		}
	}

	/**
	 * Disable Imagify when the local optimizer owns an attachment.
	 *
	 * @param mixed $optimize Existing optimization decision.
	 * @param int   $attachment_id Attachment post ID.
	 * @param mixed $metadata Attachment metadata supplied by Imagify.
	 *
	 * @return bool
	 */
	public static function filter_imagify_auto_optimize( $optimize, int $attachment_id, $metadata = array() ): bool {
		unset( $metadata );

		return self::is_enabled_for( $attachment_id ) && self::is_supported_attachment( $attachment_id )
			? false
			: (bool) $optimize;
	}

	/**
	 * Queue one attachment without collapsing burst-upload jobs.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param int $attempt Retry number.
	 */
	public static function queue_attachment( int $attachment_id, int $attempt = 0 ): void {
		if ( ! self::is_enabled_for( $attachment_id ) || ! self::is_supported_attachment( $attachment_id ) ) {
			return;
		}

		update_post_meta( $attachment_id, self::QUEUED_META, time() );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			// Action Scheduler's database-store uniqueness is scoped to hook and
			// group, not callback arguments. Keeping this non-unique is required so
			// burst uploads do not collapse different attachment IDs into one job.
			as_enqueue_async_action( self::HOOK, array( $attachment_id, $attempt ), self::GROUP, false );
			self::log( "queued id={$attachment_id} attempt={$attempt} scheduler=action_scheduler" );
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK, array( $attachment_id, $attempt ) ) ) {
			wp_schedule_single_event( time() + 1, self::HOOK, array( $attachment_id, $attempt ) );
			self::log( "queued id={$attachment_id} attempt={$attempt} scheduler=wp_cron" );
		}
	}

	/**
	 * Keep the hourly recovery sweep scheduled while the optimizer is enabled.
	 */
	public static function schedule_recovery(): void {
		if ( self::is_enabled_for( 0 ) && ! wp_next_scheduled( self::RECOVERY_HOOK ) ) {
			wp_schedule_event( time() + self::LOST_AFTER, 'hourly', self::RECOVERY_HOOK );
		}
	}

	/**
	 * Re-queue attachments whose job disappeared without a result, for example
	 * because its PHP process was killed. Runs only while the queue is idle, so
	 * a job that is merely waiting is never duplicated.
	 */
	public static function requeue_lost_attachments(): void {
		global $wpdb;

		if ( ! self::is_enabled_for( 0 ) || self::has_pending_jobs() ) {
			return;
		}

		$now = time();
		// No ORDER BY: sorting by meta_id makes MariaDB walk the whole postmeta
		// primary key instead of the few rows of this meta key.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded hourly maintenance read of live queue state.
		$attachment_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT queued.post_id
				FROM {$wpdb->postmeta} queued
				LEFT JOIN {$wpdb->postmeta} finished
					ON finished.post_id = queued.post_id AND finished.meta_key = %s
				WHERE queued.meta_key = %s
					AND queued.meta_value + 0 BETWEEN %d AND %d
					AND ( finished.meta_id IS NULL OR finished.meta_value + 0 < queued.meta_value + 0 )
				LIMIT %d",
				self::FINISHED_META,
				self::QUEUED_META,
				$now - self::LOST_WINDOW,
				$now - self::LOST_AFTER,
				self::RECOVERY_BATCH
			)
		);

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			$requeues      = (int) get_post_meta( $attachment_id, self::REQUEUES_META, true );
			if ( $requeues >= self::MAX_REQUEUES ) {
				self::finish(
					$attachment_id,
					array(
						'status' => 'failed_retries',
						'reason' => 'job_lost',
					)
				);
				continue;
			}

			update_post_meta( $attachment_id, self::REQUEUES_META, $requeues + 1 );
			self::log( 'requeue id=' . $attachment_id . ' reason=job_lost' );
			self::queue_attachment( $attachment_id );
		}
	}

	/**
	 * Create optimized sidecars for one attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param int $attempt Retry number.
	 */
	public static function process_attachment( int $attachment_id, int $attempt = 0 ): void {
		if ( ! self::is_enabled_for( $attachment_id ) || ! self::is_supported_attachment( $attachment_id ) ) {
			return;
		}

		$lock_token = self::acquire_lock();
		if ( null === $lock_token ) {
			self::wait_for_worker( $attachment_id, $attempt );
			return;
		}

		try {
			$attached_file = get_attached_file( $attachment_id, true );
			$metadata      = wp_get_attachment_metadata( $attachment_id );
			if ( ! is_string( $attached_file ) || ! is_array( $metadata ) ) {
				self::finish( $attachment_id, array( 'status' => 'failed_attachment_data' ) );
				return;
			}

			$parent_id        = (int) wp_get_post_parent_id( $attachment_id );
			$parent_post_type = $parent_id > 0 ? (string) get_post_type( $parent_id ) : '';
			$worker           = new HS_Local_Image_Worker();
			$results          = array();
			$sources          = array();
			$files            = HS_Local_Image_Attachment_Files::collect( $attached_file, $metadata );
			if ( empty( $files ) ) {
				self::retry( $attachment_id, $attempt, 'no_eligible_source_files' );
				return;
			}

			foreach ( $files as $file ) {
				$source_file = apply_filters( 'hs_local_image_optimizer_source_file', $file, $attachment_id );
				if ( is_string( $source_file ) && $source_file === $file ) {
					$sources[] = $file;
				}
			}

			// One batch per attachment: the worker encodes its files in parallel.
			$processed = $worker->process_many( $sources, array( 'post_type' => $parent_post_type ) );
			foreach ( $files as $file ) {
				$results[ basename( $file ) ] = $processed[ $file ] ?? array( 'status' => 'failed_source_path' );
			}
			if ( self::has_processing_failure( $results ) ) {
				self::retry( $attachment_id, $attempt, 'processing_failed', $results );
				return;
			}

			self::finish(
				$attachment_id,
				array(
					'status'   => 'processed',
					'pipeline' => self::PIPELINE,
					'files'    => $results,
				)
			);
		} catch ( Throwable $exception ) {
			self::retry( $attachment_id, $attempt, 'processing_exception' );
		} finally {
			self::release_lock( $lock_token );
		}
	}

	/**
	 * Delete generated sidecars when WordPress deletes an attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 */
	public static function delete_sidecars( int $attachment_id ): void {
		$attached_file = get_attached_file( $attachment_id, true );
		$metadata      = wp_get_attachment_metadata( $attachment_id );
		if ( ! is_string( $attached_file ) || ! is_array( $metadata ) ) {
			return;
		}

		foreach ( HS_Local_Image_Attachment_Files::collect( $attached_file, $metadata ) as $file ) {
			foreach ( array( $file . '.webp', $file . '.avif' ) as $sidecar ) {
				if ( is_file( $sidecar ) ) {
					wp_delete_file( $sidecar );
				}
			}
			$candidates = glob( $file . '.*.hs-local-*.tmp.*' );
			foreach ( is_array( $candidates ) ? $candidates : array() as $candidate ) {
				wp_delete_file( $candidate );
			}
		}
	}

	/**
	 * Determine whether local optimization is enabled for an attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return bool
	 */
	private static function is_enabled_for( int $attachment_id ): bool {
		if ( defined( 'HS_LOCAL_IMAGE_OPTIMIZER_ENABLED' ) && true === HS_LOCAL_IMAGE_OPTIMIZER_ENABLED ) {
			return true;
		}
		if ( 'yes' === get_option( self::ENABLED_OPTION, 'no' ) ) {
			return true;
		}

		return defined( 'HS_LOCAL_IMAGE_OPTIMIZER_CANARY_ATTACHMENT_ID' )
			&& 0 < $attachment_id
			&& (int) HS_LOCAL_IMAGE_OPTIMIZER_CANARY_ATTACHMENT_ID === $attachment_id;
	}

	/**
	 * Determine whether an attachment can be optimized.
	 *
	 * @param int $attachment_id Attachment post ID.
	 *
	 * @return bool
	 */
	private static function is_supported_attachment( int $attachment_id ): bool {
		if ( 0 >= $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		$mime_type = (string) get_post_mime_type( $attachment_id );
		if ( in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			return true;
		}
		if ( 'image/webp' !== $mime_type ) {
			return false;
		}

		/**
		 * Core adds original_image when converting/scaling; the upstream stub omits it.
		 *
		 * @var array<string, mixed>|false $metadata
		 */
		$metadata       = wp_get_attachment_metadata( $attachment_id );
		$original_image = is_array( $metadata ) && isset( $metadata['original_image'] ) ? (string) $metadata['original_image'] : '';
		$extension      = strtolower( pathinfo( $original_image, PATHINFO_EXTENSION ) );

		return in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true );
	}

	/**
	 * A complete attachment is successful only when every source file avoided a
	 * worker or encoder failure. Individual failure details stay in the worker
	 * result; retry state is persisted by retry() after its bounded attempts.
	 *
	 * @param array<string, array<string, mixed>> $results Per-file worker results.
	 */
	private static function has_processing_failure( array $results ): bool {
		foreach ( $results as $result ) {
			if ( self::result_contains_failed_status( $result ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Find a failure in a worker result, including individual encoder results.
	 *
	 * @param array<string, mixed> $result Worker result or nested encoder result.
	 */
	private static function result_contains_failed_status( array $result ): bool {
		if ( isset( $result['status'] ) && is_string( $result['status'] ) && str_starts_with( $result['status'], 'failed_' ) ) {
			return true;
		}

		foreach ( $result as $value ) {
			if ( is_array( $value ) && self::result_contains_failed_status( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Acquire the single-worker lock, replacing an expired lock when needed.
	 *
	 * @return string|null
	 */
	private static function acquire_lock(): ?string {
		$token   = wp_generate_uuid4();
		$payload = array(
			'token'      => $token,
			'created_at' => time(),
		);
		if ( add_option( self::LOCK_OPTION, $payload, '', false ) ) {
			return $token;
		}

		$current = get_option( self::LOCK_OPTION, array() );
		if ( ! is_array( $current ) || ( time() - (int) ( $current['created_at'] ?? 0 ) ) <= self::LOCK_TTL ) {
			return null;
		}

		delete_option( self::LOCK_OPTION );
		return add_option( self::LOCK_OPTION, $payload, '', false ) ? $token : null;
	}

	/**
	 * Release the worker lock owned by this process.
	 *
	 * @param string $token Lock ownership token.
	 */
	private static function release_lock( string $token ): void {
		$current = get_option( self::LOCK_OPTION, array() );
		if ( is_array( $current ) && hash_equals( (string) ( $current['token'] ?? '' ), $token ) ) {
			delete_option( self::LOCK_OPTION );
		}
	}

	/**
	 * Whether any optimizer job is still scheduled or the worker is running.
	 *
	 * @return bool
	 */
	private static function has_pending_jobs(): bool {
		if ( false !== get_option( self::LOCK_OPTION, false ) ) {
			return true;
		}

		if ( function_exists( 'as_next_scheduled_action' ) ) {
			return false !== as_next_scheduled_action( self::HOOK, null, self::GROUP );
		}

		foreach ( (array) _get_cron_array() as $hooks ) {
			if ( isset( $hooks[ self::HOOK ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Keep a job queued while another attachment holds the worker. Waiting
	 * does not consume the failure budget; the same attempt is rescheduled
	 * until MAX_BUSY_WAIT has passed since the attachment was queued.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param int $attempt Current retry number.
	 */
	private static function wait_for_worker( int $attachment_id, int $attempt ): void {
		$queued_at = (int) get_post_meta( $attachment_id, self::QUEUED_META, true );
		if ( $queued_at <= 0 ) {
			$queued_at = time();
			update_post_meta( $attachment_id, self::QUEUED_META, $queued_at );
		}

		if ( ( time() - $queued_at ) > self::MAX_BUSY_WAIT ) {
			self::finish(
				$attachment_id,
				array(
					'status' => 'failed_retries',
					'reason' => 'worker_busy',
				)
			);
			return;
		}

		self::log( 'wait id=' . $attachment_id . ' attempt=' . $attempt . ' reason=worker_busy' );
		self::schedule( $attachment_id, $attempt, self::BUSY_DELAY );
	}

	/**
	 * Reschedule work after a processing failure.
	 *
	 * @param int                  $attachment_id Attachment post ID.
	 * @param int                  $attempt Current retry number.
	 * @param string               $reason Retry reason stored on final failure.
	 * @param array<string, mixed> $files File results retained on exhausted retries.
	 */
	private static function retry( int $attachment_id, int $attempt, string $reason, array $files = array() ): void {
		$next_attempt = $attempt + 1;
		self::log( 'retry id=' . $attachment_id . ' attempt=' . $next_attempt . ' reason=' . $reason );
		if ( $next_attempt >= self::MAX_ATTEMPTS ) {
			self::finish(
				$attachment_id,
				array(
					'status' => 'failed_retries',
					'reason' => $reason,
					'files'  => $files,
				)
			);
			return;
		}

		self::schedule( $attachment_id, $next_attempt, self::RETRY_DELAY );
	}

	/**
	 * Schedule a delayed job for one attachment.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param int $attempt Retry number passed to the job.
	 * @param int $delay Delay in seconds.
	 */
	private static function schedule( int $attachment_id, int $attempt, int $delay ): void {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Non-unique for the same reason as queue_attachment().
			as_schedule_single_action( time() + $delay, self::HOOK, array( $attachment_id, $attempt ), self::GROUP, false );
			return;
		}

		wp_schedule_single_event( time() + $delay, self::HOOK, array( $attachment_id, $attempt ) );
	}

	/**
	 * Persist the final optimizer result.
	 *
	 * @param int                  $attachment_id Attachment post ID.
	 * @param array<string, mixed> $result Optimizer result.
	 */
	private static function finish( int $attachment_id, array $result ): void {
		update_post_meta( $attachment_id, self::FINISHED_META, time() );
		update_post_meta( $attachment_id, self::PIPELINE_META, self::PIPELINE );
		update_post_meta( $attachment_id, self::RESULT_META, wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) );
		if ( 'processed' === ( $result['status'] ?? '' ) ) {
			delete_post_meta( $attachment_id, self::REQUEUES_META );
			// Sidecars just changed: refresh the sizes the media library shows.
			HS_Local_Image_Admin_Media_UI::refresh_stats( $attachment_id );
		}
		self::log( 'finish id=' . $attachment_id . ' status=' . (string) ( $result['status'] ?? 'unknown' ) );
	}

	/**
	 * Publish a diagnostic event without writing synchronously to disk.
	 *
	 * @param string $message Diagnostic message.
	 */
	private static function log( string $message ): void {
		do_action( 'hs_local_image_optimizer_log', $message );
	}
}

HS_Local_Image_Optimizer_WordPress::boot();
