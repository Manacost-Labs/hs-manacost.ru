<?php
/**
 * Runs image encoder processes without a shell.
 *
 * @package Manacost
 */

declare(strict_types=1);

/**
 * Executes encoder commands with bounded concurrency and per-process timeouts.
 *
 * @phpstan-type ProcessResult array{status: string, exit_code: int, stdout: string, stderr: string}
 * @phpstan-type RunningJob array{process: resource, pipes: array<int, resource>, stdout: string, stderr: string, started_at: int|float}
 */
final class HS_Local_Image_Process_Runner {
	private const NICE_BINARY       = '/usr/bin/nice';
	private const POLL_MICROSECONDS = 10000;

	/**
	 * Run one encoder command.
	 *
	 * @param array<int, string> $command         Encoder argv.
	 * @param int                $timeout_seconds Wall-clock limit of the process.
	 * @param int                $niceness        CPU priority reduction, 0 to keep the caller priority.
	 *
	 * @return ProcessResult
	 */
	public static function run( array $command, int $timeout_seconds = 180, int $niceness = 0 ): array {
		return self::run_many( array( 'command' => $command ), 1, $timeout_seconds, $niceness )['command'];
	}

	/**
	 * Run encoder commands with bounded concurrency. Every command keeps its
	 * own timeout and result, so one slow or broken file cannot fail the rest.
	 * A positive niceness keeps encoders below web requests in CPU priority.
	 *
	 * @param array<string, array<int, string>> $commands        Encoder argv lists by caller key.
	 * @param int                               $concurrency     Processes allowed to run at once.
	 * @param int                               $timeout_seconds Wall-clock limit of each process.
	 * @param int                               $niceness        CPU priority reduction, 0 to keep the caller priority.
	 *
	 * @return array<string, ProcessResult>
	 */
	public static function run_many( array $commands, int $concurrency = 1, int $timeout_seconds = 180, int $niceness = 0 ): array {
		$results             = array();
		$running             = array();
		$order               = array_keys( $commands );
		$concurrency         = max( 1, $concurrency );
		$timeout_nanoseconds = max( 1, $timeout_seconds ) * 1000000000;

		while ( array() !== $commands || array() !== $running ) {
			$free_slots = $concurrency - count( $running );
			foreach ( array_slice( $commands, 0, max( 0, $free_slots ), true ) as $key => $command ) {
				unset( $commands[ $key ] );
				$job = self::start( $command, $niceness );
				if ( is_string( $job ) ) {
					$results[ $key ] = 'failed_start' === $job
						? self::result( $job, 126, '', 'Unable to start encoder process.' )
						: self::result( $job, 127, '', 'Encoder binary is not executable.' );
					continue;
				}
				$running[ $key ] = $job;
			}

			foreach ( $running as $key => $job ) {
				$job['stdout'] .= (string) stream_get_contents( $job['pipes'][1] );
				$job['stderr'] .= (string) stream_get_contents( $job['pipes'][2] );
				$status         = proc_get_status( $job['process'] );

				if ( ! $status['running'] ) {
					$results[ $key ] = self::finish( $job, (int) $status['exitcode'], false );
					unset( $running[ $key ] );
					continue;
				}

				if ( ( hrtime( true ) - $job['started_at'] ) >= $timeout_nanoseconds ) {
					self::terminate( $job['process'] );
					$results[ $key ] = self::finish( $job, 124, true );
					unset( $running[ $key ] );
					continue;
				}

				$running[ $key ] = $job;
			}

			if ( array() !== $running ) {
				usleep( self::POLL_MICROSECONDS );
			}
		}

		$ordered = array();
		foreach ( $order as $key ) {
			$ordered[ $key ] = $results[ $key ];
		}

		return $ordered;
	}

	/**
	 * Start one process.
	 *
	 * @param array<int, string> $command  Encoder argv.
	 * @param int                $niceness CPU priority reduction.
	 *
	 * @return RunningJob|string A running job, or the failure status when the process could not start.
	 */
	private static function start( array $command, int $niceness ) {
		if ( array() === $command || ! is_executable( $command[0] ) ) {
			return 'failed_missing_binary';
		}

		if ( $niceness > 0 && is_executable( self::NICE_BINARY ) ) {
			array_unshift( $command, self::NICE_BINARY, '-n', (string) min( 19, $niceness ) );
		}

		$pipes = array();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Encoders are fixed binaries started from an argv list, never through a shell.
		$process = proc_open(
			array_values( $command ),
			array(
				0 => array( 'pipe', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$pipes,
			null,
			null,
			array( 'bypass_shell' => true )
		);

		if ( ! is_resource( $process ) ) {
			return 'failed_start';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Process pipe, not a file.
		fclose( $pipes[0] );
		stream_set_blocking( $pipes[1], false );
		stream_set_blocking( $pipes[2], false );

		return array(
			'process'    => $process,
			'pipes'      => $pipes,
			'stdout'     => '',
			'stderr'     => '',
			'started_at' => hrtime( true ),
		);
	}

	/**
	 * Stop a process that exceeded its timeout.
	 *
	 * @param resource $process Process handle.
	 */
	private static function terminate( $process ): void {
		proc_terminate( $process, 15 );
		usleep( 200000 );

		if ( proc_get_status( $process )['running'] ) {
			proc_terminate( $process, 9 );
		}
	}

	/**
	 * Collect the remaining output of a stopped process and close it.
	 *
	 * @param array{process: resource, pipes: array<int, resource>, stdout: string, stderr: string, started_at: int|float} $job       Job returned by start().
	 * @param int                                                                                                          $exit_code Exit code reported while polling.
	 * @param bool                                                                                                         $timed_out Whether the process was stopped by its timeout.
	 *
	 * @return ProcessResult
	 */
	private static function finish( array $job, int $exit_code, bool $timed_out ): array {
		$stdout = $job['stdout'] . (string) stream_get_contents( $job['pipes'][1] );
		$stderr = $job['stderr'] . (string) stream_get_contents( $job['pipes'][2] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Process pipe, not a file.
		fclose( $job['pipes'][1] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Process pipe, not a file.
		fclose( $job['pipes'][2] );

		$close_code = proc_close( $job['process'] );
		if ( $timed_out ) {
			return self::result( 'failed_timeout', 124, $stdout, $stderr );
		}
		if ( $exit_code < 0 ) {
			$exit_code = $close_code;
		}

		return self::result( 0 === $exit_code ? 'success' : 'failed_exit', $exit_code, $stdout, $stderr );
	}

	/**
	 * Build a process result.
	 *
	 * @param string $status    Result status.
	 * @param int    $exit_code Process exit code.
	 * @param string $stdout    Captured standard output.
	 * @param string $stderr    Captured standard error.
	 *
	 * @return ProcessResult
	 */
	private static function result( string $status, int $exit_code, string $stdout, string $stderr ): array {
		return array(
			'status'    => $status,
			'exit_code' => $exit_code,
			'stdout'    => substr( $stdout, -4096 ),
			'stderr'    => substr( $stderr, -4096 ),
		);
	}
}
