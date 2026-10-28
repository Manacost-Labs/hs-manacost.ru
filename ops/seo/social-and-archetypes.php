<?php
/**
 * Enables AIOSEO social previews and names the archetype index page.
 *
 * AIOSEO settings and page titles are production data, so this runner applies
 * the reviewed values through the supported AIOSEO and WordPress APIs, as the
 * PHP-FPM user:
 *
 *   sudo -u koloda wp --path=<site> --user=<admin> eval-file ops/seo/social-and-archetypes.php \
 *     mode=dry_run|apply|rollback [backup=/var/backups/hs-manacost-seo/<file>.json]
 *
 * apply stores the previous and applied values in a new backup file first and
 * changes only fields that differ. rollback restores a field only while it
 * still holds the applied value, so later editorial edits survive. dry_run
 * prints the plan without writing. Page caches are not purged here: clear the
 * WP Rocket cache afterwards so cached pages pick up the new tags.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plans, applies and rolls back one bounded SEO settings change.
 */
final class HS_Seo_Social_And_Archetypes {
	public const ARCHETYPES_PATH        = 'archetypes';
	public const ARCHETYPES_TITLE       = 'Архетипы колод Hearthstone';
	public const ARCHETYPES_DESCRIPTION = 'Все архетипы колод Hearthstone: поиск по классу и режиму, число колод и ключевые карты каждого архетипа.';

	/**
	 * AIOSEO Lite social options owned by this runner, as network.name.
	 */
	private const SOCIAL_KEYS = array(
		'facebook.enable',
		'facebook.defaultImageSourcePosts',
		'facebook.defaultImagePosts',
		'twitter.enable',
		'twitter.useOgData',
	);

	/**
	 * Parses key=value arguments.
	 *
	 * @param array<int, string> $args Raw WP-CLI arguments.
	 * @return array{mode: string, backup: string}
	 */
	public static function options( array $args ): array {
		$options = array(
			'mode'   => 'dry_run',
			'backup' => '',
		);
		foreach ( $args as $arg ) {
			list( $key, $value ) = array_pad( explode( '=', (string) $arg, 2 ), 2, '' );
			if ( array_key_exists( $key, $options ) ) {
				$options[ $key ] = $value;
			}
		}
		if ( ! in_array( $options['mode'], array( 'dry_run', 'apply', 'rollback' ), true ) ) {
			WP_CLI::error( 'mode must be dry_run, apply or rollback.' );
		}
		if ( 'dry_run' !== $options['mode'] && '' === $options['backup'] ) {
			WP_CLI::error( 'backup=<file> is required for apply and rollback.' );
		}
		return $options;
	}

	/**
	 * Returns the current values of every field this runner owns.
	 *
	 * @return array<string, mixed>
	 */
	public static function snapshot(): array {
		$values = array();
		foreach ( self::SOCIAL_KEYS as $key ) {
			list( $network, $name ) = explode( '.', $key, 2 );
			$value                  = aioseo()->options->social->{$network}->general->{$name};
			$values[ $key ]         = is_bool( $value ) ? $value : (string) $value;
		}
		$page                             = self::archetypes_page();
		$values['archetypes.id']          = $page->ID;
		$values['archetypes.title']       = $page->post_title;
		$values['archetypes.description'] = (string) \AIOSEO\Plugin\Common\Models\Post::getPost( $page->ID )->description;
		return $values;
	}

	/**
	 * Returns the reviewed target values; editorial values are kept when set.
	 *
	 * @param array<string, mixed> $current Current snapshot.
	 * @return array<string, mixed>
	 */
	public static function target( array $current ): array {
		$target = array_merge(
			$current,
			array(
				'facebook.enable'                  => true,
				'facebook.defaultImageSourcePosts' => 'featured',
				'twitter.enable'                   => true,
				'twitter.useOgData'                => true,
			)
		);
		if ( '' === $current['facebook.defaultImagePosts'] ) {
			$target['facebook.defaultImagePosts'] = (string) get_site_icon_url( 512 );
		}
		if ( '' === trim( (string) $current['archetypes.title'] ) ) {
			$target['archetypes.title'] = self::ARCHETYPES_TITLE;
		}
		if ( '' === trim( (string) $current['archetypes.description'] ) ) {
			$target['archetypes.description'] = self::ARCHETYPES_DESCRIPTION;
		}
		return $target;
	}

	/**
	 * Writes the given values through AIOSEO and WordPress APIs.
	 *
	 * @param array<string, mixed> $values Snapshot-shaped values.
	 * @return void
	 */
	public static function write( array $values ): void {
		foreach ( self::SOCIAL_KEYS as $key ) {
			list( $network, $name ) = explode( '.', $key, 2 );
			// AIOSEO option groups are cursors, so every access spells the full path.
			aioseo()->options->social->{$network}->general->{$name} = $values[ $key ];
		}
		// Persist now rather than at shutdown, so a later failure cannot half-apply silently.
		aioseo()->options->save( true );

		$page_id = (int) $values['archetypes.id'];
		if ( get_post_field( 'post_title', $page_id ) !== $values['archetypes.title'] ) {
			$updated = wp_update_post(
				array(
					'ID'         => $page_id,
					'post_title' => (string) $values['archetypes.title'],
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				WP_CLI::error( $updated->get_error_message() );
			}
		}
		$meta = \AIOSEO\Plugin\Common\Models\Post::getPost( $page_id );
		if ( (string) $meta->description !== $values['archetypes.description'] ) {
			$meta->post_id     = $page_id;
			$meta->description = (string) $values['archetypes.description'];
			$meta->save();
		}
		clean_post_cache( $page_id );
	}

	/**
	 * Builds the values a rollback restores from a backup.
	 *
	 * @param array<string, mixed> $current Current snapshot.
	 * @param mixed                $backup  Decoded backup file.
	 * @return array{target: array<string, mixed>, kept: array<int, string>}
	 */
	public static function rollback_target( array $current, $backup ): array {
		$keys = array_keys( $current );
		if ( ! is_array( $backup ) || ! isset( $backup['before'], $backup['after'] )
			|| ! is_array( $backup['before'] ) || ! is_array( $backup['after'] )
			|| array_keys( $backup['before'] ) !== $keys || array_keys( $backup['after'] ) !== $keys
			|| $backup['before']['archetypes.id'] !== $current['archetypes.id'] ) {
			WP_CLI::error( 'backup file is missing, incomplete or for another page.' );
		}
		$target = $current;
		$kept   = array();
		foreach ( $keys as $key ) {
			if ( $current[ $key ] === $backup['after'][ $key ] ) {
				$target[ $key ] = $backup['before'][ $key ];
			} elseif ( $backup['before'][ $key ] !== $backup['after'][ $key ] ) {
				$kept[] = $key;
			}
		}
		return array(
			'target' => $target,
			'kept'   => $kept,
		);
	}

	/**
	 * Runs one mode and prints a JSON report.
	 *
	 * @param array{mode: string, backup: string} $options Parsed options.
	 * @return void
	 */
	public static function run( array $options ): void {
		$current = self::snapshot();
		$kept    = array();
		if ( 'rollback' === $options['mode'] ) {
			$raw    = is_readable( $options['backup'] ) ? file_get_contents( $options['backup'] ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$plan   = self::rollback_target( $current, is_string( $raw ) ? json_decode( $raw, true ) : null );
			$target = $plan['target'];
			$kept   = $plan['kept'];
		} else {
			$target = self::target( $current );
		}

		$changes = array();
		foreach ( $target as $key => $value ) {
			if ( $current[ $key ] !== $value ) {
				$changes[ $key ] = array(
					'from' => $current[ $key ],
					'to'   => $value,
				);
			}
		}

		if ( 'apply' === $options['mode'] && $changes ) {
			$backup = wp_json_encode(
				array(
					'before' => $current,
					'after'  => $target,
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);
			if ( file_exists( $options['backup'] ) || false === file_put_contents( $options['backup'], (string) $backup ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				WP_CLI::error( 'backup file exists or cannot be written; choose a new path.' );
			}
		}
		if ( 'dry_run' !== $options['mode'] && $changes ) {
			self::write( $target );
			$after = self::snapshot();
			if ( $after !== $target ) {
				WP_CLI::error( 'stored values differ from the plan: ' . wp_json_encode( array_keys( array_diff_assoc( array_map( 'wp_json_encode', $target ), array_map( 'wp_json_encode', $after ) ) ) ) );
			}
		}

		WP_CLI::line(
			(string) wp_json_encode(
				array(
					'mode'    => $options['mode'],
					'changes' => (object) $changes,
					'kept'    => $kept,
					'written' => 'dry_run' !== $options['mode'] && $changes,
				),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	}

	/**
	 * Returns the archetype index page or stops.
	 *
	 * @return WP_Post
	 */
	private static function archetypes_page(): WP_Post {
		$page = get_page_by_path( self::ARCHETYPES_PATH );
		if ( ! $page instanceof WP_Post || ! has_shortcode( $page->post_content, 'hs_deck_archetypes' ) ) {
			WP_CLI::error( 'the /archetypes/ index page was not found.' );
		}
		return $page;
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	if ( ! function_exists( 'aioseo' ) ) {
		WP_CLI::error( 'AIOSEO is not active.' );
	}
	HS_Seo_Social_And_Archetypes::run( HS_Seo_Social_And_Archetypes::options( $args ?? array() ) );
}
