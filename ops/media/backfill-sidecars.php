<?php
/**
 * Bounded WebP/AVIF sidecar backfill for historical uploads.
 *
 * Runs the installed HS Local Image Optimizer synchronously for attachments it
 * has never finished, as the PHP-FPM user:
 *
 *   sudo -u koloda wp --path=<site> eval-file ops/media/backfill-sidecars.php \
 *     years=2021-2025 limit=50 max_load=10 min_free_gb=60 max_minutes=50 [ids=1,2] [dry_run=1]
 *
 * coverage=1 prints per-year finished/processed shares instead of processing.
 *
 * Originals are never modified. The optimizer hydrates absent sources from the
 * public S3 bucket; this runner removes only those temporary local copies it
 * caused (byte-identical to S3) so the offload worker does not re-upload them
 * and local disk stays bounded. Sidecars stay local until the offload worker
 * copies them to S3.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Selects, paces and reports one bounded backfill batch.
 */
final class HS_Sidecar_Backfill {
	public const HOOK          = 'hs_local_image_optimizer_process_attachment';
	public const GROUP         = 'hs-local-image-optimizer';
	public const LOCK_OPTION   = 'hs_local_image_optimizer_worker_lock';
	public const FINISHED_META = '_hs_local_image_optimizer_finished_at';
	public const RESULT_META   = '_hs_local_image_optimizer_result';

	/**
	 * Parses key=value arguments into bounded options.
	 *
	 * @param array<int, string> $args Raw WP-CLI arguments.
	 * @return array<string, mixed>
	 */
	public static function options( array $args ): array {
		$raw = array();
		foreach ( $args as $arg ) {
			$parts = explode( '=', (string) $arg, 2 );
			if ( 2 === count( $parts ) ) {
				$raw[ $parts[0] ] = $parts[1];
			}
		}

		$years = array( 2021, 2025 );
		if ( isset( $raw['years'] ) && preg_match( '/^(20\d\d)(?:-(20\d\d))?$/', $raw['years'], $match ) ) {
			$years = array( (int) $match[1], (int) ( $match[2] ?? $match[1] ) );
		}
		if ( $years[0] > $years[1] || $years[0] < 2015 || $years[1] > 2025 ) {
			throw new InvalidArgumentException( 'years must be within 2015-2025' );
		}

		$ids = array();
		foreach ( explode( ',', (string) ( $raw['ids'] ?? '' ) ) as $id ) {
			if ( ctype_digit( trim( $id ) ) && (int) $id > 0 ) {
				$ids[] = (int) $id;
			}
		}

		return array(
			'years'       => $years,
			'ids'         => array_values( array_unique( $ids ) ),
			'limit'       => max( 1, min( 1000, (int) ( $raw['limit'] ?? 20 ) ) ),
			'max_load'    => max( 1.0, min( 64.0, (float) ( $raw['max_load'] ?? 10 ) ) ),
			'min_free_gb' => max( 10, min( 400, (int) ( $raw['min_free_gb'] ?? 60 ) ) ),
			'max_minutes' => max( 1, min( 600, (int) ( $raw['max_minutes'] ?? 30 ) ) ),
			'dry_run'     => '1' === ( $raw['dry_run'] ?? '0' ),
			'coverage'    => '1' === ( $raw['coverage'] ?? '0' ),
		);
	}

	/**
	 * Returns why processing must wait or stop now, or null when capacity allows it.
	 *
	 * @param float $load        One-minute load average.
	 * @param float $free_bytes  Free bytes on the uploads filesystem.
	 * @param bool  $lock_held   Whether the optimizer lock is held.
	 * @param int   $queue_due   Due optimizer jobs from new uploads.
	 * @param array $options     Parsed options.
	 */
	public static function blocker( float $load, float $free_bytes, bool $lock_held, int $queue_due, array $options ): ?string {
		if ( $free_bytes < $options['min_free_gb'] * 1024 ** 3 ) {
			return 'stop_disk';
		}
		if ( $queue_due > 0 || $lock_held ) {
			return 'wait_queue';
		}
		return $load > $options['max_load'] ? 'wait_load' : null;
	}

	/**
	 * Lists source files this run restored and may remove again.
	 *
	 * @param array<int, string> $absent_before Source paths missing before processing.
	 * @param string             $basedir       Real uploads directory.
	 * @param callable           $exists        File existence check.
	 * @return array<int, string>
	 */
	public static function hydrated( array $absent_before, string $basedir, callable $exists ): array {
		$prefix = rtrim( $basedir, '/' ) . '/';
		return array_values(
			array_filter(
				$absent_before,
				static fn( string $path ): bool => str_starts_with( $path, $prefix )
					&& ! str_contains( substr( $path, strlen( $prefix ) ), '..' )
					&& (bool) preg_match( '/\.(?:jpe?g|png)$/i', $path )
					&& $exists( $path )
			)
		);
	}

	/**
	 * Share of attachments the optimizer has finished, in percent.
	 *
	 * @param int $finished Attachments with a finished optimizer result.
	 * @param int $total    JPEG/PNG attachments in scope.
	 */
	public static function percent( int $finished, int $total ): float {
		return $total > 0 ? round( 100 * $finished / $total, 1 ) : 100.0;
	}

	/**
	 * Per-year coverage: total, finished and successfully processed attachments.
	 *
	 * @param wpdb  $db      Database.
	 * @param array $options Parsed options.
	 * @return array<string, mixed>
	 */
	public static function coverage( wpdb $db, array $options ): array {
		$pattern = '^(' . implode( '|', range( $options['years'][0], $options['years'][1] ) ) . ')/';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Bounded maintenance query.
		$rows   = $db->get_results(
			$db->prepare(
				"SELECT LEFT(f.meta_value, 4) AS year, COUNT(*) AS total,
					SUM(d.meta_id IS NOT NULL) AS finished, SUM(r.meta_value LIKE %s) AS processed
				FROM {$db->posts} p
				JOIN {$db->postmeta} f ON f.post_id = p.ID AND f.meta_key = '_wp_attached_file'
				LEFT JOIN {$db->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s
				LEFT JOIN {$db->postmeta} r ON r.post_id = p.ID AND r.meta_key = %s
				WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/jpeg', 'image/png')
				AND f.meta_value REGEXP %s GROUP BY year ORDER BY year",
				'{"status":"processed"%',
				self::FINISHED_META,
				self::RESULT_META,
				$pattern
			),
			ARRAY_A
		);
		$report = array( 'event' => 'coverage', 'years' => array(), 'total' => 0, 'finished' => 0, 'processed' => 0 );
		foreach ( $rows as $row ) {
			$report['years'][ $row['year'] ] = array(
				'total'     => (int) $row['total'],
				'finished'  => (int) $row['finished'],
				'processed' => (int) $row['processed'],
				'percent'   => self::percent( (int) $row['finished'], (int) $row['total'] ),
			);
			$report['total']     += (int) $row['total'];
			$report['finished']  += (int) $row['finished'];
			$report['processed'] += (int) $row['processed'];
		}
		$report['percent'] = self::percent( $report['finished'], $report['total'] );
		return $report;
	}

	/**
	 * Attachment IDs still unprocessed in the selected calendar years, newest first.
	 *
	 * @param wpdb  $db      Database.
	 * @param array $options Parsed options.
	 * @return array<int, int>
	 */
	public static function candidates( wpdb $db, array $options ): array {
		if ( $options['ids'] ) {
			$pending = array_filter( $options['ids'], static fn( int $id ): bool => '' === (string) get_post_meta( $id, self::FINISHED_META, true ) );
			return array_slice( array_values( $pending ), 0, $options['limit'] );
		}

		$pattern = '^(' . implode( '|', range( $options['years'][0], $options['years'][1] ) ) . ')/';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Bounded maintenance query.
		return array_map(
			'intval',
			$db->get_col(
				$db->prepare(
					"SELECT p.ID FROM {$db->posts} p
					JOIN {$db->postmeta} f ON f.post_id = p.ID AND f.meta_key = '_wp_attached_file'
					LEFT JOIN {$db->postmeta} d ON d.post_id = p.ID AND d.meta_key = %s
					WHERE p.post_type = 'attachment' AND p.post_mime_type IN ('image/jpeg', 'image/png')
					AND f.meta_value REGEXP %s AND d.meta_id IS NULL
					ORDER BY p.ID DESC LIMIT %d",
					self::FINISHED_META,
					$pattern,
					$options['limit']
				)
			)
		);
	}

	/**
	 * Runs one batch and prints JSON lines.
	 *
	 * @param array<int, string> $args Raw WP-CLI arguments.
	 */
	public static function run( array $args ): void {
		global $wpdb;

		if ( ! class_exists( 'HS_Local_Image_Attachment_Files' ) || ! has_action( self::HOOK ) ) {
			self::emit( array( 'event' => 'abort', 'reason' => 'optimizer_unavailable' ) );
			return;
		}

		$options = self::options( $args );
		if ( $options['coverage'] ) {
			self::emit( self::coverage( $wpdb, $options ) );
			return;
		}

		$candidates = self::candidates( $wpdb, $options );
		if ( ! $candidates ) {
			// The service wrapper disables its timer on this event.
			self::emit( array( 'event' => 'complete', 'years' => $options['years'] ) );
			return;
		}

		$basedir  = (string) realpath( wp_get_upload_dir()['basedir'] );
		$deadline = time() + 60 * $options['max_minutes'];
		$summary  = array( 'event' => 'summary', 'processed' => 0, 'source_bytes' => 0, 'best_bytes' => 0, 'removed_hydrated' => 0, 'stopped' => null );

		foreach ( $candidates as $id ) {
			$reason = $options['dry_run'] ? null : self::wait_for_capacity( $options, $basedir, $deadline );
			if ( null !== $reason ) {
				$summary['stopped'] = $reason;
				break;
			}

			$files  = self::source_files( $id );
			$absent = array_values( array_filter( $files, static fn( string $file ): bool => ! file_exists( $file ) ) );
			if ( $options['dry_run'] ) {
				self::emit( array( 'event' => 'dry_run', 'id' => $id, 'files' => count( $files ), 'absent' => count( $absent ) ) );
				continue;
			}

			$started = microtime( true );
			do_action( self::HOOK, $id, 0 );
			$report = self::measure( $files );

			$removed = 0;
			foreach ( self::hydrated( $absent, $basedir, 'file_exists' ) as $path ) {
				wp_delete_file( $path );
				$removed += file_exists( $path ) ? 0 : 1;
			}

			$result = json_decode( (string) get_post_meta( $id, self::RESULT_META, true ), true );
			self::emit(
				array(
					'event'            => 'attachment',
					'id'               => $id,
					'status'           => is_array( $result ) ? ( $result['status'] ?? 'unknown' ) : 'not_finished',
					'seconds'          => round( microtime( true ) - $started, 1 ),
					'files'            => count( $files ),
					'source_bytes'     => $report['source'],
					'best_bytes'       => $report['best'],
					'removed_hydrated' => $removed,
					'load'             => round( sys_getloadavg()[0], 2 ),
				)
			);
			++$summary['processed'];
			$summary['source_bytes']     += $report['source'];
			$summary['best_bytes']       += $report['best'];
			$summary['removed_hydrated'] += $removed;
		}

		self::emit( $summary );
	}

	/**
	 * Waits within the deadline until capacity allows another attachment.
	 *
	 * @param array  $options  Parsed options.
	 * @param string $basedir  Uploads directory.
	 * @param int    $deadline Unix time limit.
	 */
	private static function wait_for_capacity( array $options, string $basedir, int $deadline ): ?string {
		while ( true ) {
			if ( time() >= $deadline ) {
				return 'stop_time';
			}
			wp_cache_delete( self::LOCK_OPTION, 'options' );
			$blocker = self::blocker(
				(float) sys_getloadavg()[0],
				(float) disk_free_space( $basedir ),
				false !== get_option( self::LOCK_OPTION, false ),
				self::due_jobs(),
				$options
			);
			if ( null === $blocker || str_starts_with( $blocker, 'stop_' ) ) {
				return $blocker;
			}
			self::emit( array( 'event' => 'wait', 'reason' => $blocker ) );
			sleep( 30 );
		}
	}

	/**
	 * Due optimizer jobs (new uploads or their retries) that must run first.
	 *
	 * Jobs overdue by more than ten minutes mean no queue runner is active (as on
	 * staging, where WP-Cron does not run); waiting for them would never end.
	 */
	private static function due_jobs(): int {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return 0;
		}
		$count = static function ( int $until ): int {
			return count(
				as_get_scheduled_actions(
					array(
						'hook'         => self::HOOK,
						'group'        => self::GROUP,
						'status'       => array( 'pending', 'in-progress' ),
						'date'         => gmdate( 'Y-m-d H:i:s', $until ),
						'date_compare' => '<=',
						'per_page'     => -1,
					),
					'ids'
				)
			);
		};
		return self::fresh_due( $count( time() + 90 ), $count( time() - 600 ) );
	}

	/**
	 * Due jobs excluding a stale backlog.
	 *
	 * @param int $due_soon Jobs due within the next 90 seconds or earlier.
	 * @param int $stale    Jobs overdue by more than ten minutes.
	 */
	public static function fresh_due( int $due_soon, int $stale ): int {
		return max( 0, $due_soon - $stale );
	}

	/**
	 * Original and generated JPEG/PNG paths for an attachment without hydrating them.
	 *
	 * @param int $id Attachment ID.
	 * @return array<int, string>
	 */
	private static function source_files( int $id ): array {
		$file     = get_attached_file( $id, true );
		$metadata = wp_get_attachment_metadata( $id );
		return is_string( $file ) && is_array( $metadata ) ? HS_Local_Image_Attachment_Files::collect( $file, $metadata ) : array();
	}

	/**
	 * Sums source bytes and the smallest delivered variant per file.
	 *
	 * @param array<int, string> $files Source paths.
	 * @return array{source:int,best:int}
	 */
	private static function measure( array $files ): array {
		$source = 0;
		$best   = 0;
		foreach ( $files as $file ) {
			if ( ! is_file( $file ) ) {
				continue;
			}
			$size     = (int) filesize( $file );
			$smallest = $size;
			foreach ( array( '.avif', '.webp' ) as $suffix ) {
				if ( is_file( $file . $suffix ) ) {
					$smallest = min( $smallest, (int) filesize( $file . $suffix ) );
				}
			}
			$source += $size;
			$best   += $smallest;
		}
		return array( 'source' => $source, 'best' => $best );
	}

	/**
	 * Prints one JSON line.
	 *
	 * @param array<string, mixed> $event Event payload.
	 */
	private static function emit( array $event ): void {
		echo wp_json_encode( $event, JSON_UNESCAPED_SLASHES ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- CLI JSON.
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'HS_SIDECAR_BACKFILL_NO_RUN' ) ) {
	HS_Sidecar_Backfill::run( isset( $args ) && is_array( $args ) ? $args : array() );
}
