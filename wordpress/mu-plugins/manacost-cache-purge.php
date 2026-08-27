<?php
/**
 * Plugin Name: Manacost Cache Purge
 * Description: Adds an admin-bar button to purge WordPress, cache plugins, local cache folders, object cache, OPcache and Cloudflare.
 * Version: 1.1.7
 * Author: Manacost
 */

defined( 'ABSPATH' ) || exit;

final class Manacost_Cache_Purge {
	private const ACTION = 'manacost_purge_cache';
	private const NONCE = 'manacost_purge_cache_nonce';
	private const CRON_HOOK = 'manacost_cache_purge_cron';
	private const ASYNC_PURGE_HOOK = 'manacost_cache_async_purge';
	private const CRON_RECURRENCE = 'manacost_every_12_hours';
	private const LAST_RESULTS_OPTION = 'manacost_cache_purge_last_results';
	private const AUTO_PURGE_THROTTLE_TRANSIENT = 'manacost_cache_auto_purge_throttle';
	private const AUTO_PURGE_THROTTLE_SECONDS = 30;
	private const DECK_META_PURGE_THROTTLE_TRANSIENT = 'manacost_cache_deck_meta_purge_throttle';
	private const DECK_META_PURGE_THROTTLE_SECONDS = 120;

	public static function boot(): void {
			add_action( 'admin_bar_menu', [ __CLASS__, 'add_admin_bar_button' ], 100 );
			add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle_purge_request' ] );
			add_action( 'admin_notices', [ __CLASS__, 'show_notice' ] );
			add_action( 'wp_footer', [ __CLASS__, 'show_frontend_notice' ] );
			add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] );
			add_action( 'init', [ __CLASS__, 'ensure_cron_scheduled' ] );
			add_action( self::CRON_HOOK, [ __CLASS__, 'run_scheduled_purge' ] );
			add_action( self::ASYNC_PURGE_HOOK, [ __CLASS__, 'run_async_purge' ], 10, 1 );
			add_action( 'save_post_hs_deck', [ __CLASS__, 'purge_decks_listing_cache' ], 100, 3 );
			add_action( 'before_delete_post', [ __CLASS__, 'purge_decks_listing_cache_on_delete' ], 100 );
			add_action( 'added_post_meta', [ __CLASS__, 'purge_after_deck_meta_change' ], 200, 4 );
			add_action( 'updated_post_meta', [ __CLASS__, 'purge_after_deck_meta_change' ], 200, 4 );
			add_action( 'deleted_post_meta', [ __CLASS__, 'purge_after_deck_meta_change' ], 200, 4 );
			add_action( 'save_post', [ __CLASS__, 'purge_after_content_change' ], 200, 3 );
			add_action( 'post_updated', [ __CLASS__, 'purge_after_post_update' ], 200, 3 );
			add_action( 'transition_post_status', [ __CLASS__, 'purge_after_status_transition' ], 200, 3 );
			add_action( 'wp_after_insert_post', [ __CLASS__, 'purge_after_inserted_post' ], 200, 4 );
			add_action( 'deleted_post', [ __CLASS__, 'purge_after_post_delete' ], 200, 2 );
			add_action( 'trashed_post', [ __CLASS__, 'purge_after_post_id_change' ], 200 );
			add_action( 'untrashed_post', [ __CLASS__, 'purge_after_post_id_change' ], 200 );
			add_action( 'wp_update_nav_menu', [ __CLASS__, 'purge_after_menu_change' ], 200 );
			add_action( 'customize_save_after', [ __CLASS__, 'purge_after_theme_change' ], 200 );
			add_action( 'switch_theme', [ __CLASS__, 'purge_after_theme_change' ], 200 );

		if ( self::feature_enabled( 'MANACOST_PERF_ENABLED', true ) ) {
			add_action( 'init', [ __CLASS__, 'remove_frontend_core_style_hooks' ], 1 );
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'optimize_frontend_assets' ], 100 );
			add_action( 'wp_print_styles', [ __CLASS__, 'optimize_frontend_assets' ], 1000 );
			add_action( 'template_redirect', [ __CLASS__, 'start_front_page_html_optimizer' ], -100 );
			add_filter( 'wp_resource_hints', [ __CLASS__, 'add_resource_hints' ], 10, 2 );
			add_filter( 'do_rocket_lazyload', [ __CLASS__, 'use_lazyload_only_on_mobile' ] );
			add_filter( 'rocket_buffer', [ __CLASS__, 'optimize_front_page_html' ], 120000 );
		}
	}

	private static function current_request_is_top_decks_page(): bool {
		$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
		$path = '/' . trim( $path, '/' ) . '/';

		return $path === '/hearthstone-top-decks/';
	}

	private static function maybe_disable_top_decks_page_cache(): void {
		if ( ! self::current_request_is_top_decks_page() ) {
			return;
		}

		foreach ( [ 'DONOTCACHEPAGE', 'DONOTCDN', 'DONOTCACHEOBJECT' ] as $constant ) {
			if ( ! defined( $constant ) ) {
				define( $constant, true );
			}
		}
	}

	public static function send_top_decks_no_cache_headers(): void {
		if ( is_admin() || ! self::current_request_is_top_decks_page() || headers_sent() ) {
			return;
		}

		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
		header( 'Surrogate-Control: no-store', true );
		header( 'cf-edge-cache: no-cache', true );
	}

	public static function purge_decks_listing_cache( int $post_id = 0, ?WP_Post $post = null, bool $update = false ): void {
		unset( $update );

		if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return;
		}

		if ( $post && $post->post_type !== 'hs_deck' ) {
			return;
		}

		self::purge_decks_listing_cache_files();
	}

	public static function purge_decks_listing_cache_on_delete( int $post_id ): void {
		if ( get_post_type( $post_id ) !== 'hs_deck' ) {
			return;
		}

		self::purge_decks_listing_cache_files();
	}

	private static function purge_decks_listing_cache_files(): void {
		static $ran = false;

		if ( $ran ) {
			return;
		}
		$ran = true;

		$url = home_url( '/hearthstone-top-decks/' );

		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files( [ $url, trailingslashit( $url ) ] );
		}

		foreach ( [ 'hs-manacost.ru', 'www.hs-manacost.ru' ] as $host ) {
			$path = WP_CONTENT_DIR . '/cache/wp-rocket/' . $host . '/hearthstone-top-decks';
			if ( is_dir( $path ) ) {
				self::delete_path_contents( $path );
				@rmdir( $path );
			}
		}
	}

	private static function feature_enabled( string $constant, bool $default ): bool {
		if ( ! defined( $constant ) ) {
			return $default;
		}

		$value = constant( $constant );

		if ( is_bool( $value ) ) {
			return $value;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), [ '0', 'false', 'off', 'no' ], true );
	}

	/**
	 * @param array<string, array{interval:int,display:string}> $schedules
	 * @return array<string, array{interval:int,display:string}>
	 */
	public static function add_cron_schedule( array $schedules ): array {
		$schedules[ self::CRON_RECURRENCE ] = [
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => 'Every 12 hours (Manacost Cache)',
		];

		return $schedules;
	}

	public static function ensure_cron_scheduled(): void {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time() + 300, self::CRON_RECURRENCE, self::CRON_HOOK );
	}

	public static function run_scheduled_purge(): void {
		if ( wp_installing() ) {
			return;
		}

		self::run_purge_and_store_results( 'scheduled' );
	}

	public static function run_async_purge( string $source = 'auto' ): void {
		if ( wp_installing() ) {
			return;
		}

		self::run_purge_and_store_results( 'auto:' . sanitize_key( $source ) );
	}

	public static function remove_frontend_core_style_hooks(): void {
		if ( is_admin() || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
		remove_action( 'enqueue_block_assets', 'wp_enqueue_classic_theme_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	}

	public static function optimize_frontend_assets(): void {
		if ( is_admin() || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return;
		}

		$handles = [
			'wp-block-library',
			'wp-block-library-theme',
			'wc-block-style',
			'global-styles',
			'classic-theme-styles',
		];

		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	}

	public static function start_front_page_html_optimizer(): void {
		$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );

		if (
			is_admin()
			|| is_feed()
			|| is_preview()
			|| wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru'
			|| $path !== '/'
		) {
			return;
		}

		ob_start( [ __CLASS__, 'optimize_front_page_html' ] );
	}

	public static function optimize_front_page_html( string $html ): string {
		if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return $html;
		}

		$html = self::add_responsive_first_view_assets( $html );

		$is_mobile = function_exists( 'wp_is_mobile' ) && wp_is_mobile();

		if ( ! $is_mobile ) {
			return $html;
		}

		$html = self::optimize_mobile_first_view_images( $html );

		if ( stripos( $html, 'td-thumb-css' ) === false || stripos( $html, 'background-image' ) === false ) {
			return $html;
		}

		$seen = 0;

		return preg_replace_callback(
			'/<span(?=[^>]*\\bentry-thumb\\b)(?=[^>]*\\btd-thumb-css\\b)[^>]*\\sstyle="background-image:\\s*url\\(&quot;(.*?)&quot;\\)"[^>]*><\\/span>/i',
			static function ( array $matches ) use ( &$seen ): string {
				$seen++;

				if ( $seen <= 3 ) {
					return $matches[0];
				}

				$tag = $matches[0];
				$url = esc_url_raw( html_entity_decode( $matches[1], ENT_QUOTES ) );

				if ( ! $url ) {
					return $tag;
				}

				$tag = preg_replace_callback(
					'/\\sclass="([^"]*)"/i',
					static function ( array $class_matches ): string {
						$classes = preg_split( '/\\s+/', trim( $class_matches[1] ) ) ?: [];
						if ( ! in_array( 'rocket-lazyload', $classes, true ) ) {
							$classes[] = 'rocket-lazyload';
						}

						return ' class="' . esc_attr( trim( implode( ' ', $classes ) ) ) . '"';
					},
					$tag,
					1
				);

				$tag = preg_replace(
					'/\\sstyle="background-image:\\s*url\\(&quot;.*?&quot;\\)"\\s*/i',
					' data-bg="' . esc_url( $url ) . '" style="" ',
					$tag,
					1
				);

				return $tag ?: $matches[0];
			},
			$html
		) ?: $html;
	}

	private static function optimize_mobile_first_view_images( string $html ): string {
		return $html;
	}

	private static function add_responsive_first_view_assets( string $html ): string {
		$desktop_lcp = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-1068x542.webp';
		$mobile_lcp  = 'https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-696x353.webp';

		$preload = '<link rel="preload" as="image" href="' . $desktop_lcp . '" fetchpriority="high">';
		if ( strpos( $html, $preload ) !== false ) {
			$html = str_replace(
				$preload,
				'<link rel="preload" as="image" href="' . $mobile_lcp . '" media="(max-width: 767px)" fetchpriority="high">' . "\n" .
				'<link rel="preload" as="image" href="' . $desktop_lcp . '" media="(min-width: 768px)" fetchpriority="high">',
				$html
			);
		}

		if ( strpos( $html, 'id="hs-mobile-first-view-assets"' ) !== false ) {
			return $html;
		}

		$css = '<style id="hs-mobile-first-view-assets">@media (max-width:767px){'
			. 'a[href*="budzhetnye-kolody-hearthstone-kataklizm"] .entry-thumb.td-thumb-css{background-image:url("https://hs-manacost.ru/wp-content/uploads/2026/05/budget-decks-696x353.webp")!important}'
			. 'a[href*="obzor-patcha-35-4-2"] .entry-thumb.td-thumb-css{background-image:url("https://hs-manacost.ru/wp-content/uploads/2026/05/obzor-patcha-696x353.webp")!important}'
			. '}</style>';

		return preg_replace( '/(<style id="hs-early-paint">.*?<\\/style>)/s', '$1' . "\n" . $css, $html, 1 ) ?: $html;
	}

	public static function use_lazyload_only_on_mobile( bool $enabled ): bool {
		if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return $enabled;
		}

		return function_exists( 'wp_is_mobile' ) && wp_is_mobile();
	}

	/**
	 * Add only connection hints. This does not delay, defer or rewrite JavaScript.
	 *
	 * @param array<int, string|array<string, string>> $urls
	 * @return array<int, string|array<string, string>>
	 */
	public static function add_resource_hints( array $urls, string $relation_type ): array {
		if ( $relation_type !== 'preconnect' || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return $urls;
		}

		if ( self::feature_enabled( 'MANACOST_DEFER_THIRD_PARTY_ENABLED', true ) ) {
			return $urls;
		}

		$urls[] = [
			'href'        => 'https://pagead2.googlesyndication.com',
			'crossorigin' => 'anonymous',
		];
		$urls[] = [
			'href'        => 'https://fundingchoicesmessages.google.com',
			'crossorigin' => 'anonymous',
		];

		return $urls;
	}

	public static function purge_after_content_change( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		if ( ! self::post_status_affects_public_cache( $post->post_status ) ) {
			return;
		}

		self::run_automatic_purge( 'content_' . $post->post_type );
	}

	public static function purge_after_post_update( int $post_id, WP_Post $post_after, WP_Post $post_before ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::post_type_affects_public_cache( $post_after ) ) {
			return;
		}

		if ( ! self::post_status_affects_public_cache( $post_after->post_status ) && ! self::post_status_affects_public_cache( $post_before->post_status ) ) {
			return;
		}

		self::run_automatic_purge( 'updated_' . $post_after->post_type );
	}

	public static function purge_after_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( $new_status === $old_status || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		if ( ! self::post_status_affects_public_cache( $new_status ) && ! self::post_status_affects_public_cache( $old_status ) ) {
			return;
		}

		self::run_automatic_purge( 'status_' . $post->post_type . '_' . $old_status . '_to_' . $new_status );
	}

	public static function purge_after_inserted_post( int $post_id, WP_Post $post, bool $update, ?WP_Post $post_before ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		$after_affects_public_cache  = self::post_status_affects_public_cache( $post->post_status );
		$before_affects_public_cache = $post_before instanceof WP_Post && self::post_status_affects_public_cache( $post_before->post_status );

		if ( ! $after_affects_public_cache && ! $before_affects_public_cache ) {
			return;
		}

		self::run_automatic_purge( ( $update ? 'after_update_' : 'after_publish_' ) . $post->post_type );
	}

	public static function purge_after_post_delete( int $post_id, ?WP_Post $post = null ): void {
		if ( ! $post instanceof WP_Post || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		self::run_automatic_purge( 'delete_' . $post->post_type );
	}

	public static function purge_after_post_id_change( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		self::run_automatic_purge( 'status_' . $post->post_type );
	}

	public static function purge_after_menu_change(): void {
		self::run_automatic_purge( 'menu' );
	}

	public static function purge_after_theme_change(): void {
		self::run_automatic_purge( 'theme' );
	}

	private static function post_type_affects_public_cache( WP_Post $post ): bool {
		$excluded = [
			'attachment',
			'custom_css',
			'customize_changeset',
			'nav_menu_item',
			'oembed_cache',
			'revision',
			'user_request',
			'wp_block',
			'wp_global_styles',
			'wp_navigation',
			'wp_template',
			'wp_template_part',
		];

		return ! in_array( $post->post_type, $excluded, true );
	}

	private static function post_status_affects_public_cache( string $status ): bool {
		return in_array( $status, [ 'publish', 'future' ], true );
	}

	public static function purge_after_deck_meta_change( $meta_id, int $post_id, string $meta_key = '', $meta_value = null ): void {
		unset( $meta_id, $meta_value );

		$post_id = absint( $post_id );
		if ( ! $post_id || get_post_type( $post_id ) !== 'hs_deck' ) {
			return;
		}

		if ( $meta_key !== '' && ! self::deck_meta_key_affects_public_cache( $meta_key ) ) {
			return;
		}

		self::run_deck_meta_purge();
	}

	private static function deck_meta_key_affects_public_cache( string $meta_key ): bool {
		if ( strpos( $meta_key, '_deck_' ) === 0 ) {
			return true;
		}

		return in_array(
			$meta_key,
			[
				'_custom_tags',
				'_dust_cost',
				'_rank_proof',
				'_hide_from_feed',
				'_hs_deck_archived',
				'_exclude_from_random',
				'_use_feed_shortcode',
				'_feed_shortcode',
				'_show_all_class_modes',
				'_show_announcement_single',
				'_thumbnail_id',
			],
			true
		);
	}

	private static function run_deck_meta_purge(): void {
		if ( wp_installing() || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( get_transient( self::DECK_META_PURGE_THROTTLE_TRANSIENT ) ) {
			return;
		}

		set_transient( self::DECK_META_PURGE_THROTTLE_TRANSIENT, 1, self::DECK_META_PURGE_THROTTLE_SECONDS );

		self::purge_decks_listing_cache_files();
		self::store_purge_results(
			[
				[
					'name'    => 'Deck listing cache',
					'status'  => 'ok',
					'message' => 'local listing cache cleaned; reverse proxy HTML expires by short TTL',
				],
			],
			'auto:hs_deck_meta_light'
		);
	}

	private static function run_automatic_purge( string $source ): void {
		if ( wp_installing() || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( get_transient( self::AUTO_PURGE_THROTTLE_TRANSIENT ) ) {
			return;
		}

		set_transient( self::AUTO_PURGE_THROTTLE_TRANSIENT, 1, self::AUTO_PURGE_THROTTLE_SECONDS );

		$source    = sanitize_key( $source );
		$scheduled = false;

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ASYNC_PURGE_HOOK, [ $source ], 'manacost-cache' ) ) {
				$scheduled = true;
			} else {
				$scheduled = (bool) as_enqueue_async_action( self::ASYNC_PURGE_HOOK, [ $source ], 'manacost-cache', true );
			}
		}

		if ( ! $scheduled ) {
			$scheduled = wp_schedule_single_event( time() + 5, self::ASYNC_PURGE_HOOK, [ $source ] );
		}

		if ( ! $scheduled && ! wp_next_scheduled( self::ASYNC_PURGE_HOOK, [ $source ] ) ) {
			error_log( 'Manacost Cache automatic purge could not be scheduled for ' . $source . '; running inline fallback.' );
			self::run_async_purge( $source );
			return;
		}

		self::store_purge_results(
			[
				[
					'name'    => 'Automatic purge',
					'status'  => 'ok',
					'message' => 'scheduled async purge: ' . $source,
				],
			],
			'auto:' . $source . ':queued'
		);
	}

	private static function run_purge_and_store_results( string $source ): void {
		$results = self::purge_all();
		$failed  = self::failed_results( $results );

		self::store_purge_results( $results, $source );

		if ( $failed ) {
			error_log( 'Manacost Cache purge for ' . sanitize_key( $source ) . ' finished with ' . count( $failed ) . ' failed step(s).' );
		}
	}

	public static function add_admin_bar_button( WP_Admin_Bar $admin_bar ): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION ),
			self::ACTION,
			self::NONCE
		);

		$admin_bar->add_node(
			[
				'id'    => 'manacost-cache-purge',
				'title' => 'Manacost Cache',
				'href'  => $url,
				'meta'  => [
					'title' => 'Purge all Manacost caches',
				],
			]
		);
	}

	public static function handle_purge_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'manacost-cache-purge' ), 403 );
		}

		check_admin_referer( self::ACTION, self::NONCE );

		$results = self::purge_all();
		$failed  = self::failed_results( $results );
		$key     = self::save_notice_results( $results );

		self::store_purge_results( $results, 'manual' );

		$redirect = add_query_arg(
			[
				'manacost_cache_purge' => empty( $failed ) ? 'ok' : 'partial',
				'manacost_cache_notice' => rawurlencode( $key ),
			],
			wp_get_referer() ?: admin_url()
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	public static function show_notice(): void {
		if ( empty( $_GET['manacost_cache_purge'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['manacost_cache_purge'] ) );
		$class  = $status === 'ok' ? 'notice-success' : 'notice-warning';
		$items  = self::notice_results();

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>Manacost Cache:</strong> ' . esc_html( self::notice_title( $status ) ) . '</p>';

		if ( $items ) {
			echo '<ul style="margin-left:18px;list-style:disc;">';
			foreach ( $items as $item ) {
				$name    = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : 'cache';
				$message = isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '';
				$item_status = isset( $item['status'] ) ? sanitize_key( $item['status'] ) : 'ok';
				echo '<li><code>' . esc_html( $item_status ) . '</code> ' . esc_html( $name . ( $message ? ': ' . $message : '' ) ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	public static function show_frontend_notice(): void {
		if ( is_admin() || empty( $_GET['manacost_cache_purge'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['manacost_cache_purge'] ) );
		$items  = self::notice_results();
		$ok     = $status === 'ok';

		echo '<div id="manacost-cache-front-notice" style="position:fixed;z-index:999999;top:42px;right:18px;max-width:520px;background:' . ( $ok ? '#ecfdf3' : '#fff8e5' ) . ';border:1px solid ' . ( $ok ? '#27ae60' : '#d9a441' ) . ';box-shadow:0 8px 28px rgba(0,0,0,.18);border-radius:6px;padding:14px 42px 14px 16px;color:#1d2327;font:14px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;">';
		echo '<button type="button" aria-label="Закрыть" onclick="this.parentNode.remove()" style="position:absolute;right:10px;top:8px;border:0;background:transparent;font-size:22px;line-height:1;cursor:pointer;color:#50575e;">&times;</button>';
		echo '<strong style="display:block;margin-bottom:6px;">Manacost Cache</strong>';
		echo '<div>' . esc_html( self::notice_title( $status ) ) . '</div>';

		if ( $items ) {
			echo '<ul style="margin:10px 0 0 18px;padding:0;list-style:disc;">';
			foreach ( $items as $item ) {
				$name        = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : 'cache';
				$message     = isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '';
				$item_status = isset( $item['status'] ) ? sanitize_key( $item['status'] ) : 'ok';
				$label       = $item_status === 'ok' ? 'OK' : 'Ошибка';
				echo '<li><strong>' . esc_html( $label ) . '</strong> ' . esc_html( $name . ( $message ? ': ' . $message : '' ) ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	private static function notice_title( string $status ): string {
		if ( $status === 'ok' ) {
			return 'Все кэши очищены: WordPress, плагины кэша, object cache, OPcache и Cloudflare.';
		}

		return 'Очистка выполнена частично. Проверьте пункты ниже.';
	}

	/**
	 * @param array<int, array{name:string,status:string,message:string}> $results
	 */
	private static function save_notice_results( array $results ): string {
		$key = get_current_user_id() . '_' . wp_generate_uuid4();
		set_transient( 'manacost_cache_purge_notice_' . $key, $results, 10 * MINUTE_IN_SECONDS );

		return $key;
	}

	/**
	 * @return array<int, array{name:string,status:string,message:string}>
	 */
	private static function notice_results(): array {
		$items = [];

		if ( ! empty( $_GET['manacost_cache_notice'] ) ) {
			$key   = sanitize_text_field( wp_unslash( $_GET['manacost_cache_notice'] ) );
			$saved = get_transient( 'manacost_cache_purge_notice_' . $key );
			if ( is_array( $saved ) ) {
				$items = $saved;
			}
		}

		if ( ! $items && ! empty( $_GET['manacost_cache_items'] ) ) {
			$decoded = json_decode( rawurldecode( sanitize_text_field( wp_unslash( $_GET['manacost_cache_items'] ) ) ), true );
			if ( is_array( $decoded ) ) {
				$items = $decoded;
			}
		}

		return $items;
	}

	/**
	 * @param array<int, array{name:string,status:string,message:string}> $results
	 * @return array<int, array{name:string,status:string,message:string}>
	 */
	private static function failed_results( array $results ): array {
		return array_values(
			array_filter(
				$results,
				static fn ( array $item ): bool => ( $item['status'] ?? '' ) !== 'ok'
			)
		);
	}

	/**
	 * @param array<int, array{name:string,status:string,message:string}> $results
	 */
	private static function store_purge_results( array $results, string $source ): void {
		update_option(
			self::LAST_RESULTS_OPTION,
			[
				'ran_at'  => gmdate( 'c' ),
				'source'  => $source,
				'failed'  => count( self::failed_results( $results ) ),
				'results' => $results,
			],
			false
		);
	}

	/**
	 * @return array<int, array{name:string,status:string,message:string}>
	 */
	private static function purge_all(): array {
		$results = [];

		self::run_step( $results, 'WP Rocket', [ __CLASS__, 'purge_wp_rocket' ] );
		self::run_step( $results, 'W3 Total Cache', [ __CLASS__, 'purge_w3_total_cache' ] );
		self::run_step( $results, 'Autoptimize', [ __CLASS__, 'purge_autoptimize' ] );
		self::run_step( $results, 'Perfmatters', [ __CLASS__, 'purge_perfmatters' ] );
		self::run_step( $results, 'Known local cache folders', [ __CLASS__, 'purge_local_cache_folders' ] );
		self::run_step( $results, 'WordPress object cache', [ __CLASS__, 'purge_object_cache' ] );
		self::run_step( $results, 'PHP OPcache', [ __CLASS__, 'purge_opcache' ] );
		self::run_step( $results, 'Reverse proxy cache', [ __CLASS__, 'purge_reverse_proxy_cache' ] );
		self::run_step( $results, 'Cloudflare', [ __CLASS__, 'purge_cloudflare' ] );

		return $results;
	}

	/**
	 * @param array<int, array{name:string,status:string,message:string}> $results
	 * @param callable():string $callback
	 */
	private static function run_step( array &$results, string $name, callable $callback ): void {
		try {
			$message = (string) call_user_func( $callback );
			$results[] = [
				'name'    => $name,
				'status'  => 'ok',
				'message' => $message,
			];
		} catch ( Throwable $error ) {
			$results[] = [
				'name'    => $name,
				'status'  => 'failed',
				'message' => $error->getMessage(),
			];
		}
	}

	private static function purge_wp_rocket(): string {
		$ran = [];

		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify();
			$ran[] = 'minify';
		}

		if ( function_exists( 'rocket_clean_cache_busting' ) ) {
			rocket_clean_cache_busting();
			$ran[] = 'cache-busting';
		}

		if ( function_exists( 'rocket_clean_used_css' ) ) {
			rocket_clean_used_css();
			$ran[] = 'used-css';
		}

		return $ran ? implode( ', ', $ran ) . '; page cache cleaned by local folder purge' : 'local folder purge only';
	}

	private static function purge_w3_total_cache(): string {
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all( [ 'ui_action' => 'manacost_admin_bar' ] );
			return 'flush_all';
		}

		return 'not active';
	}

	private static function purge_autoptimize(): string {
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			autoptimizeCache::clearall();
			return 'clearall';
		}

		return 'not active';
	}

	private static function purge_perfmatters(): string {
		do_action( 'perfmatters_clear_cache' );
		do_action( 'perfmatters_clear_used_css' );

		return 'hooks fired';
	}

	private static function purge_object_cache(): string {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
			return 'wp_cache_flush';
		}

		return 'not available';
	}

	private static function purge_opcache(): string {
		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
			return 'opcache_reset';
		}

		return 'not available';
	}

	private static function purge_local_cache_folders(): string {
		$cache_root = WP_CONTENT_DIR . '/cache';
		$folders    = [
			'wp-rocket',
			'min',
			'busting',
			'critical-css',
			'used-css',
			'background-css',
			'perfmatters',
			'autoptimize',
			'wpfc-minified',
			'tmp',
			'tmpWpfc',
			'page_enhanced',
		];

		$removed = 0;

		foreach ( $folders as $folder ) {
			$path = $cache_root . '/' . $folder;
			if ( is_dir( $path ) ) {
				self::delete_path_contents( $path );
				$removed++;
			}
		}

		return $removed . ' folders cleaned';
	}

	private static function purge_reverse_proxy_cache(): string {
		$config = self::reverse_proxy_config();

		if ( empty( $config['endpoints'] ) || empty( $config['token'] ) ) {
			return 'not configured';
		}

		$purged = [];
		$cluster = count( $config['endpoints'] ) > 1 ? '0' : '1';

		foreach ( $config['endpoints'] as $endpoint ) {
			$response = wp_remote_post(
				$endpoint,
				[
					'timeout'   => 20,
					'sslverify' => false,
					'headers'   => [
						'Host' => $config['host'],
					],
					'body'      => [
						'token'   => $config['token'],
						'cluster' => $cluster,
					],
				]
			);

			if ( is_wp_error( $response ) ) {
				throw new RuntimeException( $endpoint . ': ' . $response->get_error_message() );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code < 200 || $code >= 300 || empty( $body['ok'] ) ) {
				$error = is_array( $body ) && ! empty( $body['error'] ) ? (string) $body['error'] : 'unknown error';
				throw new RuntimeException( $endpoint . ': HTTP ' . $code . ', ' . $error );
			}

			$purged[] = wp_parse_url( $endpoint, PHP_URL_HOST ) . ' removed ' . (int) ( $body['removed'] ?? 0 );
		}

		return implode( '; ', $purged );
	}

	/**
	 * @return array{endpoints:array<int,string>,token:string,host:string}
	 */
	private static function reverse_proxy_config(): array {
		$domain = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$domain = preg_replace( '/^www\./', '', $domain ) ?: $domain;

		$endpoints_value = defined( 'MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS
			: (string) (
				getenv( 'MANACOST_REVERSE_PROXY_PURGE_ENDPOINTS' )
				?: get_option( 'manacost_reverse_proxy_purge_endpoints', '' )
			);

		$token = defined( 'MANACOST_REVERSE_PROXY_PURGE_TOKEN' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_TOKEN
			: (string) (
				getenv( 'MANACOST_REVERSE_PROXY_PURGE_TOKEN' )
				?: get_option( 'manacost_reverse_proxy_purge_token', '' )
			);

		$host = defined( 'MANACOST_REVERSE_PROXY_PURGE_HOST' )
			? (string) MANACOST_REVERSE_PROXY_PURGE_HOST
			: (string) (
				getenv( 'MANACOST_REVERSE_PROXY_PURGE_HOST' )
				?: get_option( 'manacost_reverse_proxy_purge_host', $domain )
			);

		$endpoints = preg_split( '/[\s,]+/', $endpoints_value ) ?: [];
		$endpoints = array_values( array_filter( array_map( 'esc_url_raw', $endpoints ) ) );

		return [
			'endpoints' => $endpoints,
			'token'     => $token,
			'host'      => $host ?: $domain,
		];
	}

	private static function delete_path_contents( string $path ): void {
		$real = realpath( $path );
		if ( ! $real || strpos( $real, realpath( WP_CONTENT_DIR . '/cache' ) ?: '', ) !== 0 ) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			$item->isDir() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() );
		}
	}

	private static function purge_cloudflare(): string {
		$config = self::cloudflare_config();

		if ( empty( $config['headers'] ) ) {
			return 'credentials not configured';
		}

		if ( empty( $config['zone_id'] ) ) {
			$config['zone_id'] = self::discover_cloudflare_zone_id( $config['domain'], $config['headers'] );
		}

		if ( empty( $config['zone_id'] ) ) {
			return 'zone id not configured';
		}

		$response = wp_remote_post(
			'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $config['zone_id'] ) . '/purge_cache',
			[
				'timeout' => 20,
				'headers' => array_merge(
					$config['headers'],
					[
						'Content-Type' => 'application/json',
					]
				),
				'body'    => wp_json_encode( [ 'purge_everything' => true ] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || empty( $body['success'] ) ) {
			$errors = [];
			if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
				foreach ( $body['errors'] as $error ) {
					if ( isset( $error['code'] ) ) {
						$errors[] = (string) $error['code'];
					}
				}
			}

			throw new RuntimeException( 'Cloudflare API failed, HTTP ' . $code . ( $errors ? ', errors: ' . implode( ',', $errors ) : '' ) );
		}

		return 'purge_everything via ' . $config['auth_type'];
	}

	/**
	 * @return array{zone_id:string,headers:array<string,string>,auth_type:string,domain:string}
	 */
	private static function cloudflare_config(): array {
		$options = get_option( 'wp_rocket_settings', [] );
		$options = is_array( $options ) ? $options : [];

		$domain = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$domain = preg_replace( '/^www\./', '', $domain ) ?: $domain;

		$env_zone_id = getenv( 'MANACOST_CLOUDFLARE_ZONE_ID' ) ?: '';
		$env_token   = getenv( 'MANACOST_CLOUDFLARE_API_TOKEN' ) ?: '';
		$env_email   = getenv( 'MANACOST_CLOUDFLARE_EMAIL' ) ?: '';
		$env_key     = getenv( 'MANACOST_CLOUDFLARE_API_KEY' ) ?: '';

		$zone_id = defined( 'MANACOST_CLOUDFLARE_ZONE_ID' )
			? (string) MANACOST_CLOUDFLARE_ZONE_ID
			: (string) (
				$env_zone_id
				?: get_option( 'manacost_cloudflare_zone_id', '' )
				?: ( $options['cloudflare_zone_id'] ?? '' )
				?: get_option( 'cloudflare_zone_id', '' )
			);

		$email = defined( 'MANACOST_CLOUDFLARE_EMAIL' )
			? (string) MANACOST_CLOUDFLARE_EMAIL
			: (string) (
				$env_email
				?: get_option( 'manacost_cloudflare_email', '' )
				?: ( $options['cloudflare_email'] ?? '' )
				?: get_option( 'cloudflare_api_email', '' )
			);

		$key = defined( 'MANACOST_CLOUDFLARE_API_KEY' )
			? (string) MANACOST_CLOUDFLARE_API_KEY
			: (string) (
				$env_key
				?: get_option( 'manacost_cloudflare_api_key', '' )
				?: ( $options['cloudflare_api_key'] ?? '' )
				?: get_option( 'cloudflare_api_key', '' )
			);

		if ( $email && $key ) {
			return [
				'zone_id'   => $zone_id,
				'headers'   => [
					'X-Auth-Email' => $email,
					'X-Auth-Key'   => $key,
				],
				'auth_type' => 'api_key',
				'domain'    => $domain,
			];
		}

		$token = defined( 'MANACOST_CLOUDFLARE_API_TOKEN' )
			? (string) MANACOST_CLOUDFLARE_API_TOKEN
			: (string) ( $env_token ?: get_option( 'manacost_cloudflare_api_token', '' ) );

		if ( $token ) {
			return [
				'zone_id'   => $zone_id,
				'headers'   => [
					'Authorization' => 'Bearer ' . $token,
				],
				'auth_type' => 'api_token',
				'domain'    => $domain,
			];
		}

		return [
			'zone_id'   => $zone_id,
			'headers'   => [],
			'auth_type' => 'none',
			'domain'    => $domain,
		];
	}

	/**
	 * @param array<string,string> $headers
	 */
	private static function discover_cloudflare_zone_id( string $domain, array $headers ): string {
		$domain = trim( strtolower( preg_replace( '/^www\./', '', $domain ) ?: $domain ) );

		if ( ! $domain ) {
			return '';
		}

		$candidates = [ $domain ];
		$parts      = explode( '.', $domain );

		while ( count( $parts ) > 2 ) {
			array_shift( $parts );
			$candidates[] = implode( '.', $parts );
		}

		$candidates = array_values( array_unique( array_filter( $candidates ) ) );

		foreach ( $candidates as $candidate ) {
			$response = wp_remote_get(
				'https://api.cloudflare.com/client/v4/zones?name=' . rawurlencode( $candidate ) . '&per_page=1',
				[
					'timeout' => 20,
					'headers' => array_merge(
						$headers,
						[
							'Content-Type' => 'application/json',
						]
					),
				]
			);

			if ( is_wp_error( $response ) ) {
				throw new RuntimeException( $response->get_error_message() );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code < 200 || $code >= 300 || empty( $body['success'] ) ) {
				$errors = [];
				if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
					foreach ( $body['errors'] as $error ) {
						if ( isset( $error['code'] ) ) {
							$errors[] = (string) $error['code'];
						}
					}
				}

				throw new RuntimeException( 'Cloudflare zone lookup failed, HTTP ' . $code . ( $errors ? ', errors: ' . implode( ',', $errors ) : '' ) );
			}

			if ( ! empty( $body['result'][0]['id'] ) ) {
				$zone_id = (string) $body['result'][0]['id'];
				update_option( 'manacost_cloudflare_zone_id', $zone_id, false );

				return $zone_id;
			}
		}

		return '';
	}
}

Manacost_Cache_Purge::boot();
