<?php
/**
 * Creates WebP and AVIF sidecars next to source images.
 *
 * @package Manacost
 */

declare(strict_types=1);

/**
 * Encodes sidecars while preserving every source image byte-for-byte.
 *
 * @phpstan-type Encode array{candidate: string, command: list<string>}
 * @phpstan-type Profile array{name: string, webp: array{enabled: bool, quality: int, lossless: bool}, avif: array{enabled: bool, quantizer: int}, minimum_saving_percent: int}
 * @phpstan-type Plan array{profile: Profile, webp?: Encode, avif?: Encode}
 */
final class HS_Local_Image_Worker {
	/**
	 * Configure encoder binaries and resource limits.
	 *
	 * @param string $cwebp_binary    Path to cwebp.
	 * @param string $avifenc_binary  Path to avifenc.
	 * @param int    $timeout_seconds Wall-clock limit of one encoder process.
	 * @param int    $concurrency     Encoder processes allowed to run at once.
	 * @param int    $niceness        CPU priority reduction applied to encoders.
	 */
	public function __construct(
		private readonly string $cwebp_binary = '/usr/bin/cwebp',
		private readonly string $avifenc_binary = '/usr/bin/avifenc',
		private readonly int $timeout_seconds = 180,
		private readonly int $concurrency = 4,
		private readonly int $niceness = 10
	) {
	}

	/**
	 * Encode the sidecars of one source.
	 *
	 * @param string                                                                        $source  Source image path.
	 * @param array{mime?: string, filename?: string, post_type?: string, has_alpha?: bool} $context Attachment context used to select the quality profile.
	 *
	 * @return array<string, mixed>
	 */
	public function process( string $source, array $context = array() ): array {
		return $this->process_many( array( $source ), $context )[ $source ];
	}

	/**
	 * Encode the sidecars of several sources with bounded concurrency.
	 * Encoders run in parallel at low CPU priority; candidates are validated
	 * and published afterwards, WebP first, because a published WebP is the
	 * size baseline an AVIF candidate has to beat.
	 *
	 * @param array<int, string>                                                            $sources Source image paths.
	 * @param array{mime?: string, filename?: string, post_type?: string, has_alpha?: bool} $context Attachment context used to select the quality profile.
	 *
	 * @return array<string, array<string, mixed>> Results keyed by source path.
	 */
	public function process_many( array $sources, array $context = array() ): array {
		$results = array();
		$plans   = array();
		foreach ( array_unique( $sources ) as $source ) {
			$plan = $this->plan( $source, $context );
			if ( is_string( $plan ) ) {
				$results[ $source ] = array( 'status' => $plan );
				continue;
			}

			$plans[ $source ]   = $plan;
			$results[ $source ] = array(
				'status'  => 'processed',
				'profile' => $plan['profile']['name'],
			);
		}

		// AVIF encodes are the slowest, so they are started first.
		$commands = array();
		foreach ( array( 'avif', 'webp' ) as $format ) {
			foreach ( $plans as $source => $plan ) {
				if ( isset( $plan[ $format ] ) ) {
					$commands[ $format . ':' . $source ] = $plan[ $format ]['command'];
				}
			}
		}

		$processes = HS_Local_Image_Process_Runner::run_many(
			$commands,
			$this->concurrency,
			$this->timeout_seconds,
			$this->niceness
		);

		foreach ( $plans as $source => $plan ) {
			$minimum_saving             = $plan['profile']['minimum_saving_percent'];
			$webp_destination           = $source . '.webp';
			$results[ $source ]['webp'] = $this->publish(
				$source,
				$webp_destination,
				$plan['webp'] ?? null,
				$processes[ 'webp:' . $source ] ?? null,
				$minimum_saving,
				null
			);

			if ( ! $plan['profile']['avif']['enabled'] ) {
				$results[ $source ]['avif'] = array( 'status' => 'skipped_profile' );
				continue;
			}

			$results[ $source ]['avif'] = $this->publish(
				$source,
				$source . '.avif',
				$plan['avif'] ?? null,
				$processes[ 'avif:' . $source ] ?? null,
				$minimum_saving,
				is_readable( $webp_destination ) ? $webp_destination : $source
			);
		}

		return $results;
	}

	/**
	 * Validate one source and prepare the encoder commands it still needs.
	 *
	 * @param string                                                                        $source  Source image path.
	 * @param array{mime?: string, filename?: string, post_type?: string, has_alpha?: bool} $context Attachment context used to select the quality profile.
	 *
	 * @return Plan|string A plan, or the final status when the source cannot be processed.
	 */
	private function plan( string $source, array $context ) {
		if ( ! is_file( $source ) || ! is_readable( $source ) ) {
			return 'failed_missing_source';
		}

		$extension = strtolower( pathinfo( $source, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
			return 'skipped_unsupported_source';
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A corrupt upload is an expected result, not a PHP warning.
		$image_info = @getimagesize( $source );
		if ( false === $image_info ) {
			return 'failed_invalid_source';
		}

		$context['mime']     = (string) ( $context['mime'] ?? $image_info['mime'] );
		$context['filename'] = (string) ( $context['filename'] ?? $source );
		if ( 'png' === $extension && ! array_key_exists( 'has_alpha', $context ) ) {
			$context['has_alpha'] = $this->png_has_alpha( $source );
		}

		$profile = HS_Local_Image_Profile_Selector::select( $context );
		$plan    = array( 'profile' => $profile );

		$webp_destination = $source . '.webp';
		if ( ! $this->is_current( $source, $webp_destination ) ) {
			$candidate    = $this->candidate_path( $webp_destination );
			$plan['webp'] = array(
				'candidate' => $candidate,
				'command'   => HS_Local_Image_Encoder_Command::webp(
					$this->cwebp_binary,
					$source,
					$candidate,
					$profile['webp']
				),
			);
		}

		$avif_destination = $source . '.avif';
		if ( $profile['avif']['enabled'] && ! $this->is_current( $source, $avif_destination ) ) {
			$candidate    = $this->candidate_path( $avif_destination );
			$plan['avif'] = array(
				'candidate' => $candidate,
				'command'   => HS_Local_Image_Encoder_Command::avif(
					$this->avifenc_binary,
					$source,
					$candidate,
					array(
						'quantizer' => $profile['avif']['quantizer'],
						'jobs'      => 4,
						'speed'     => 4,
					)
				),
			);
		}

		return $plan;
	}

	/**
	 * Validate and publish one encoded candidate.
	 *
	 * @param string                    $source                 Source image path.
	 * @param string                    $destination            Sidecar path.
	 * @param Encode|null               $encode                 Planned encode, null when the sidecar is already current.
	 * @param array<string, mixed>|null $process                Result of the encoder process.
	 * @param int                       $minimum_saving_percent Saving a candidate has to reach.
	 * @param string|null               $baseline               File whose size the candidate has to beat instead of the source.
	 *
	 * @return array<string, mixed>
	 */
	private function publish(
		string $source,
		string $destination,
		?array $encode,
		?array $process,
		int $minimum_saving_percent,
		?string $baseline
	): array {
		if ( null === $encode ) {
			return array( 'status' => 'skipped_up_to_date' );
		}

		if ( null === $process || 'success' !== $process['status'] ) {
			$this->discard( $encode['candidate'] );
			return $process ?? array( 'status' => 'failed_start' );
		}

		return HS_Local_Image_Candidate_Publisher::publish(
			$source,
			$encode['candidate'],
			$destination,
			$minimum_saving_percent,
			$baseline
		);
	}

	/**
	 * Whether a sidecar is at least as new as its source.
	 *
	 * @param string $source      Source image path.
	 * @param string $destination Sidecar path.
	 */
	private function is_current( string $source, string $destination ): bool {
		return is_file( $destination )
			&& (int) filesize( $destination ) > 0
			&& (int) filemtime( $destination ) >= (int) filemtime( $source );
	}

	/**
	 * Build a unique temporary path next to the sidecar.
	 *
	 * @param string $destination Sidecar path.
	 */
	private function candidate_path( string $destination ): string {
		return $destination . '.hs-local-' . bin2hex( random_bytes( 6 ) ) . '.tmp.' . pathinfo( $destination, PATHINFO_EXTENSION );
	}

	/**
	 * Remove an unpublished candidate.
	 *
	 * @param string $path Candidate path.
	 */
	private function discard( string $path ): void {
		if ( is_file( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- The worker also runs outside WordPress, where wp_delete_file() is unavailable.
			unlink( $path );
		}
	}

	/**
	 * Detect transparency in a PNG file without decoding its pixels.
	 *
	 * @param string $source PNG path.
	 */
	private function png_has_alpha( string $source ): bool {
		// phpcs:disable WordPress.WP.AlternativeFunctions -- Bounded header read of a local file; WP_Filesystem cannot seek.
		$handle = fopen( $source, 'rb' );
		if ( false === $handle ) {
			return false;
		}

		try {
			$header = (string) fread( $handle, 26 );
			if ( strlen( $header ) < 26 || 'PNG' !== substr( $header, 1, 3 ) ) {
				return false;
			}

			$color_type = ord( $header[25] );
			if ( 4 === $color_type || 6 === $color_type ) {
				return true;
			}

			// Indexed and grayscale PNG files can carry transparency in a
			// tRNS chunk even though the IHDR color type has no alpha channel.
			fseek( $handle, 8 );
			while ( ! feof( $handle ) ) {
				$length_bytes = (string) fread( $handle, 4 );
				$type         = (string) fread( $handle, 4 );
				if ( 4 !== strlen( $length_bytes ) || 4 !== strlen( $type ) ) {
					break;
				}

				$unpacked = unpack( 'Nlength', $length_bytes );
				$length   = is_array( $unpacked ) ? (int) $unpacked['length'] : 0;
				if ( 'tRNS' === $type ) {
					return true;
				}
				if ( 'IDAT' === $type || 'IEND' === $type || $length > 16777216 ) {
					break;
				}

				fseek( $handle, $length + 4, SEEK_CUR );
			}

			return false;
		} finally {
			fclose( $handle );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}
}
