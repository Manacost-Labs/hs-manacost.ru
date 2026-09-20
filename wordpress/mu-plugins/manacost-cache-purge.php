<?php
/**
 * Plugin Name: Manacost Cache Purge
 * Description: Adds an admin-bar button to purge WordPress, cache plugins, local cache folders, object cache, OPcache and Cloudflare.
 * Version: 1.1.8
 * Author: Manacost
 *
 * @package ManacostCachePurge
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/manacost-cache-purge/opcache.php';
require_once __DIR__ . '/manacost-cache-purge/runtime.php';
require_once __DIR__ . '/manacost-cache-purge/theme-options.php';

/**
 * Coordinates bounded cache invalidation for public Manacost content.
 */
final class Manacost_Cache_Purge {
	use Manacost_Cache_Purge_Runtime;
	use Manacost_Cache_Purge_Theme_Options;

	private const ACTION                             = 'manacost_purge_cache';
	private const NONCE                              = 'manacost_purge_cache_nonce';
	private const CRON_HOOK                          = 'manacost_cache_purge_cron';
	private const ASYNC_PURGE_HOOK                   = 'manacost_cache_async_purge';
	private const CRON_RECURRENCE                    = 'manacost_every_12_hours';
	private const LAST_RESULTS_OPTION                = 'manacost_cache_purge_last_results';
	private const AUTO_PURGE_THROTTLE_TRANSIENT      = 'manacost_cache_auto_purge_throttle';
	private const AUTO_PURGE_THROTTLE_SECONDS        = 30;
	private const DECK_META_PURGE_THROTTLE_TRANSIENT = 'manacost_cache_deck_meta_purge_throttle';
	private const DECK_META_PURGE_THROTTLE_SECONDS   = 120;

	/**
	 * Registers the cache invalidation, admin and frontend hooks.
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_button' ), 100 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_purge_request' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_notice' ) );
		add_action( 'wp_footer', array( __CLASS__, 'show_frontend_notice' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'ensure_cron_scheduled' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_purge' ) );
		add_action( self::ASYNC_PURGE_HOOK, array( __CLASS__, 'run_async_purge_hook' ), 10, 1 );
		add_action( 'save_post_hs_deck', array( __CLASS__, 'purge_decks_listing_cache' ), 100, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'purge_decks_listing_cache_on_delete' ), 100 );
		add_action( 'added_post_meta', array( __CLASS__, 'purge_after_deck_meta_change' ), 200, 4 );
		add_action( 'updated_post_meta', array( __CLASS__, 'purge_after_deck_meta_change' ), 200, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'purge_after_deck_meta_change' ), 200, 4 );
		add_action( 'save_post', array( __CLASS__, 'purge_after_content_change' ), 200, 3 );
		add_action( 'post_updated', array( __CLASS__, 'purge_after_post_update' ), 200, 3 );
		add_action( 'transition_post_status', array( __CLASS__, 'purge_after_status_transition' ), 200, 3 );
		add_action( 'wp_after_insert_post', array( __CLASS__, 'purge_after_inserted_post' ), 200, 4 );
		add_action( 'deleted_post', array( __CLASS__, 'purge_after_post_delete' ), 200, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'purge_after_post_id_change' ), 200 );
		add_action( 'untrashed_post', array( __CLASS__, 'purge_after_post_id_change' ), 200 );
		add_action( 'wp_update_nav_menu', array( __CLASS__, 'purge_after_menu_change' ), 200 );
		add_action( 'customize_save_after', array( __CLASS__, 'purge_after_theme_change' ), 200 );
		add_action( 'switch_theme', array( __CLASS__, 'purge_after_theme_change' ), 200 );
		add_action( 'update_option_td_011', array( __CLASS__, 'purge_after_newspaper_options_change' ), 200, 3 );

		if ( self::feature_enabled( 'MANACOST_PERF_ENABLED', true ) ) {
			add_action( 'init', array( __CLASS__, 'remove_frontend_core_style_hooks' ), 1 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'optimize_frontend_assets' ), 100 );
			add_action( 'wp_print_styles', array( __CLASS__, 'optimize_frontend_assets' ), 1000 );
			add_filter( 'wp_resource_hints', array( __CLASS__, 'add_resource_hints' ), 10, 2 );
		}
	}

	/**
	 * Determines whether the current request is the uncached decks listing.
	 *
	 * @return bool Whether the request targets the decks listing.
	 */
	private static function current_request_is_top_decks_page(): bool {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$path        = '/' . trim( $path, '/' ) . '/';

		return '/hearthstone-top-decks/' === $path;
	}

	/**
	 * Sets WordPress cache bypass constants for the decks listing when possible.
	 *
	 * @return void
	 */
	private static function maybe_disable_top_decks_page_cache(): void {
		if ( ! self::current_request_is_top_decks_page() ) {
			return;
		}

		foreach ( array( 'DONOTCACHEPAGE', 'DONOTCDN', 'DONOTCACHEOBJECT' ) as $constant ) {
			if ( ! defined( $constant ) ) {
				define( $constant, true );
			}
		}
	}

	/**
	 * Sends no-store response headers for the decks listing.
	 *
	 * @return void
	 */
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

	/**
	 * Purges the decks listing after a deck is saved.
	 *
	 * @param int      $post_id Deck post ID.
	 * @param ?WP_Post $post    Saved post when available.
	 * @param bool     $update  Whether this is an update.
	 * @return void
	 */
	public static function purge_decks_listing_cache( int $post_id = 0, ?WP_Post $post = null, bool $update = false ): void {
		unset( $update );

		if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
			return;
		}

		if ( $post && 'hs_deck' !== $post->post_type ) {
			return;
		}

		self::purge_decks_listing_cache_files();
	}

	/**
	 * Purges the decks listing after a deck is deleted.
	 *
	 * @param int $post_id Deleted post ID.
	 * @return void
	 */
	public static function purge_decks_listing_cache_on_delete( int $post_id ): void {
		if ( 'hs_deck' !== get_post_type( $post_id ) ) {
			return;
		}

		self::purge_decks_listing_cache_files();
	}

	/**
	 * Clears the known decks-listing cache entries once per request.
	 *
	 * @return void
	 */
	private static function purge_decks_listing_cache_files(): void {
		static $ran = false;

		if ( $ran ) {
			return;
		}
		$ran = true;

		$url = home_url( '/hearthstone-top-decks/' );

		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files( array( $url, trailingslashit( $url ) ) );
		}

		foreach ( array( 'hs-manacost.ru', 'www.hs-manacost.ru' ) as $host ) {
			$path = WP_CONTENT_DIR . '/cache/wp-rocket/' . $host . '/hearthstone-top-decks';
			if ( is_dir( $path ) ) {
				self::delete_path_contents( $path );
			}
		}
	}

	/**
	 * Reads a boolean feature constant with a safe fallback.
	 *
	 * @param string $constant Constant name.
	 * @param bool   $fallback Value used when the constant is not defined.
	 * @return bool Whether the feature is enabled.
	 */
	private static function feature_enabled( string $constant, bool $fallback ): bool {
		if ( ! defined( $constant ) ) {
			return $fallback;
		}

		$value = constant( $constant );

		if ( is_bool( $value ) ) {
			return $value;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'false', 'off', 'no' ), true );
	}

	/**
	 * Adds the cache-purge recurrence to WordPress cron schedules.
	 *
	 * @param array<string, array{interval:int,display:string}> $schedules Registered cron schedules.
	 * @return array<string, array{interval:int,display:string}> Updated schedules.
	 */
	public static function add_cron_schedule( array $schedules ): array {
		$schedules[ self::CRON_RECURRENCE ] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => 'Every 12 hours (Manacost Cache)',
		);

		return $schedules;
	}

	/**
	 * Schedules the periodic cache purge when it is not already registered.
	 *
	 * @return void
	 */
	public static function ensure_cron_scheduled(): void {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time() + 300, self::CRON_RECURRENCE, self::CRON_HOOK );
	}

	/**
	 * Runs an asynchronous purge for a sanitized source family.
	 *
	 * Content lifecycle prefixes are reserved for content events and never code changes.
	 *
	 * @param string $source Purge origin.
	 * @return array<int, array{name:string,status:string,message:string}> Purge results.
	 */
	public static function run_async_purge( string $source = 'auto' ): array {
		if ( wp_installing() ) {
			return array();
		}

		return self::run_purge_and_store_results( 'auto:' . sanitize_key( $source ) );
	}

	/**
	 * Removes WordPress core style hooks on the canonical public site.
	 *
	 * @return void
	 */
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

	/**
	 * Removes selected core frontend style assets on the canonical public site.
	 *
	 * @return void
	 */
	public static function optimize_frontend_assets(): void {
		if ( is_admin() || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return;
		}

		$handles = array(
			'wp-block-library',
			'wp-block-library-theme',
			'wc-block-style',
			'global-styles',
			'classic-theme-styles',
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}

		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	}

	/**
	 * Add only connection hints. This does not delay, defer or rewrite JavaScript.
	 *
	 * @param array<int, string|array<string, string>> $urls          Existing resource hints.
	 * @param string                                   $relation_type Requested hint relation.
	 * @return array<int, string|array<string, string>> Filtered resource hints.
	 */
	public static function add_resource_hints( array $urls, string $relation_type ): array {
		if ( 'preconnect' !== $relation_type || wp_parse_url( home_url(), PHP_URL_HOST ) !== 'hs-manacost.ru' ) {
			return $urls;
		}

		if ( self::feature_enabled( 'MANACOST_DEFER_THIRD_PARTY_ENABLED', true ) ) {
			return $urls;
		}

		$urls[] = array(
			'href'        => 'https://pagead2.googlesyndication.com',
			'crossorigin' => 'anonymous',
		);
		$urls[] = array(
			'href'        => 'https://fundingchoicesmessages.google.com',
			'crossorigin' => 'anonymous',
		);

		return $urls;
	}

	/**
	 * Schedules a purge after an eligible public post is saved.
	 *
	 * @param int     $post_id Saved post ID.
	 * @param WP_Post $post    Saved post.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
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

	/**
	 * Schedules a purge after a public post materially changes.
	 *
	 * @param int     $post_id     Updated post ID.
	 * @param WP_Post $post_after  Post after the update.
	 * @param WP_Post $post_before Post before the update.
	 * @return void
	 */
	public static function purge_after_post_update( int $post_id, WP_Post $post_after, WP_Post $post_before ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::post_type_affects_public_cache( $post_after ) ) {
			return;
		}

		if ( ! self::post_status_affects_public_cache( $post_after->post_status ) && ! self::post_status_affects_public_cache( $post_before->post_status ) ) {
			return;
		}

		self::run_automatic_purge( 'updated_' . $post_after->post_type );
	}

	/**
	 * Schedules a purge after a relevant public-status transition.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post       Transitioned post.
	 * @return void
	 */
	public static function purge_after_status_transition( string $new_status, string $old_status, WP_Post $post ): void {
		if ( $new_status === $old_status || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		if ( ! self::post_status_affects_public_cache( $new_status ) && ! self::post_status_affects_public_cache( $old_status ) ) {
			return;
		}

		self::run_automatic_purge( 'status_' . $post->post_type . '_' . $old_status . '_to_' . $new_status );
	}

	/**
	 * Schedules a purge after WordPress inserts an eligible post.
	 *
	 * @param int      $post_id     Inserted post ID.
	 * @param WP_Post  $post        Inserted post.
	 * @param bool     $update      Whether this is an update.
	 * @param ?WP_Post $post_before Previous post value.
	 * @return void
	 */
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

	/**
	 * Schedules a purge after an eligible post is deleted.
	 *
	 * @param int      $post_id Deleted post ID.
	 * @param ?WP_Post $post    Deleted post when available.
	 * @return void
	 */
	public static function purge_after_post_delete( int $post_id, ?WP_Post $post = null ): void {
		if ( ! $post instanceof WP_Post || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		self::run_automatic_purge( 'delete_' . $post->post_type );
	}

	/**
	 * Schedules a purge after a post is trashed or restored.
	 *
	 * @param int $post_id Changed post ID.
	 * @return void
	 */
	public static function purge_after_post_id_change( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::post_type_affects_public_cache( $post ) ) {
			return;
		}

		self::run_automatic_purge( 'status_' . $post->post_type );
	}

	/**
	 * Schedules a purge after navigation changes.
	 *
	 * @return void
	 */
	public static function purge_after_menu_change(): void {
		self::run_automatic_purge( 'menu' );
	}

	/**
	 * Determines whether a post type has public cached output.
	 *
	 * @param WP_Post $post Post to inspect.
	 * @return bool Whether the post type affects public cache.
	 */
	private static function post_type_affects_public_cache( WP_Post $post ): bool {
		$excluded = array(
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
		);

		return ! in_array( $post->post_type, $excluded, true );
	}

	/**
	 * Determines whether a post status has public cached output.
	 *
	 * @param string $status Post status.
	 * @return bool Whether the status affects public cache.
	 */
	private static function post_status_affects_public_cache( string $status ): bool {
		return in_array( $status, array( 'publish', 'future' ), true );
	}

	/**
	 * Schedules a purge after cache-relevant deck metadata changes.
	 *
	 * @param mixed  $meta_id    Metadata row identifier.
	 * @param int    $post_id    Related post ID.
	 * @param string $meta_key   Changed metadata key.
	 * @param mixed  $meta_value Changed metadata value.
	 * @return void
	 */
	public static function purge_after_deck_meta_change( $meta_id, int $post_id, string $meta_key = '', $meta_value = null ): void {
		unset( $meta_id, $meta_value );

		$post_id = absint( $post_id );
		if ( ! $post_id || get_post_type( $post_id ) !== 'hs_deck' ) {
			return;
		}

		if ( '' !== $meta_key && ! self::deck_meta_key_affects_public_cache( $meta_key ) ) {
			return;
		}

		self::run_deck_meta_purge();
	}

	/**
	 * Determines whether a deck metadata key changes public output.
	 *
	 * @param string $meta_key Metadata key.
	 * @return bool Whether the key affects public cache.
	 */
	private static function deck_meta_key_affects_public_cache( string $meta_key ): bool {
		if ( strpos( $meta_key, '_deck_' ) === 0 ) {
			return true;
		}

		return in_array(
			$meta_key,
			array(
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
			),
			true
		);
	}

	/**
	 * Runs the throttled decks metadata purge.
	 *
	 * @return void
	 */
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
			array(
				array(
					'name'    => 'Deck listing cache',
					'status'  => 'ok',
					'message' => 'local listing cache cleaned; reverse proxy HTML expires by short TTL',
				),
			),
			'auto:hs_deck_meta_light'
		);
	}

	/**
	 * Schedules one throttled automatic cache purge.
	 *
	 * @param string $source               Automatic purge origin.
	 * @param bool   $defer_if_throttled Whether a throttled change must be queued for later.
	 * @return void
	 */
	private static function run_automatic_purge( string $source, bool $defer_if_throttled = false ): void {
		if ( wp_installing() || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		if ( get_transient( self::AUTO_PURGE_THROTTLE_TRANSIENT ) ) {
			if ( $defer_if_throttled ) {
				self::schedule_deferred_automatic_purge();
			}
			return;
		}

		set_transient( self::AUTO_PURGE_THROTTLE_TRANSIENT, 1, self::AUTO_PURGE_THROTTLE_SECONDS );

		$source    = sanitize_key( $source );
		$scheduled = false;

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ASYNC_PURGE_HOOK, array( $source ), 'manacost-cache' ) ) {
				$scheduled = true;
			} else {
				$scheduled = (bool) as_enqueue_async_action( self::ASYNC_PURGE_HOOK, array( $source ), 'manacost-cache', true );
			}
		}

		if ( ! $scheduled ) {
			$scheduled = wp_schedule_single_event( time() + 5, self::ASYNC_PURGE_HOOK, array( $source ) );
		}

		if ( ! $scheduled && ! wp_next_scheduled( self::ASYNC_PURGE_HOOK, array( $source ) ) ) {
			self::run_async_purge( $source );
			return;
		}

		self::store_purge_results(
			array(
				array(
					'name'    => 'Automatic purge',
					'status'  => 'ok',
					'message' => 'scheduled async purge: ' . $source,
				),
			),
			'auto:' . $source . ':queued'
		);
	}

	/**
	 * Runs a purge and persists its bounded diagnostic result.
	 *
	 * @param string $source Purge origin.
	 * @return array<int, array{name:string,status:string,message:string}> Purge results.
	 */
	private static function run_purge_and_store_results( string $source ): array {
		$results = self::purge_all( $source );

		self::store_purge_results( $results, $source );

		return $results;
	}

	/**
	 * Adds the privileged cache-purge control to the WordPress admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar Admin bar instance.
	 * @return void
	 */
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
			array(
				'id'    => 'manacost-cache-purge',
				'title' => 'Manacost Cache',
				'href'  => $url,
				'meta'  => array(
					'title' => 'Purge all Manacost caches',
				),
			)
		);
	}

	/**
	 * Verifies and handles a privileged manual purge request.
	 *
	 * @return void
	 */
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
			array(
				'manacost_cache_purge'  => empty( $failed ) ? 'ok' : 'partial',
				'manacost_cache_notice' => $key,
				'manacost_cache_nonce'  => wp_create_nonce( self::ACTION ),
			),
			self::notice_redirect_url()
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Renders the privileged wp-admin purge status notice.
	 *
	 * @return void
	 */
	public static function show_notice(): void {
		$request = self::valid_notice_request();
		if ( null === $request ) {
			return;
		}

		$status = $request['status'];
		$class  = 'ok' === $status ? 'notice-success' : 'notice-warning';
		$items  = self::notice_results( $request['key'] );

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>Manacost Cache:</strong> ' . esc_html( self::notice_title( $status ) ) . '</p>';

		if ( $items ) {
			echo '<ul style="margin-left:18px;list-style:disc;">';
			foreach ( $items as $item ) {
				$name        = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : 'cache';
				$message     = isset( $item['message'] ) ? sanitize_text_field( $item['message'] ) : '';
				$item_status = isset( $item['status'] ) ? sanitize_key( $item['status'] ) : 'ok';
				echo '<li><code>' . esc_html( $item_status ) . '</code> ' . esc_html( $name . ( $message ? ': ' . $message : '' ) ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * Renders the privileged frontend purge status notice.
	 *
	 * @return void
	 */
	public static function show_frontend_notice(): void {
		$request = self::valid_notice_request();
		if ( is_admin() || null === $request ) {
			return;
		}

		$status = $request['status'];
		$items  = self::notice_results( $request['key'] );
		$ok     = 'ok' === $status;

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
				$label       = 'ok' === $item_status ? 'OK' : 'Ошибка';
				echo '<li><strong>' . esc_html( $label ) . '</strong> ' . esc_html( $name . ( $message ? ': ' . $message : '' ) ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * Returns the status-title copy for a completed purge.
	 *
	 * @param string $status Purge status.
	 * @return string Notice title.
	 */
	private static function notice_title( string $status ): string {
		if ( 'ok' === $status ) {
			return 'Целевые кэши очищены: WordPress, плагины кэша, OPcache и Cloudflare. Глобальный object cache не сбрасывался.';
		}

		return 'Очистка выполнена частично. Проверьте пункты ниже.';
	}

	/**
	 * Stores a short-lived notice payload and returns its lookup key.
	 *
	 * @param array<int, array{name:string,status:string,message:string}> $results Purge results.
	 * @return string Notice lookup key.
	 */
	private static function save_notice_results( array $results ): string {
		$key = get_current_user_id() . '_' . wp_generate_uuid4();
		set_transient( 'manacost_cache_purge_notice_' . $key, $results, 10 * MINUTE_IN_SECONDS );

		return $key;
	}

	/**
	 * Reads the current purge notice payload from a transient owned by the current user.
	 *
	 * @param string $key Current-user transient lookup key.
	 * @return array<int, array{name:string,status:string,message:string}> Notice results.
	 */
	private static function notice_results( string $key ): array {
		$items = array();

		$saved = get_transient( 'manacost_cache_purge_notice_' . $key );
		if ( is_array( $saved ) ) {
			$items = $saved;
		}

		return $items;
	}

	/**
	 * Returns the best redirect URL after a verified manual purge.
	 *
	 * @return string Safe redirect URL.
	 */
	private static function notice_redirect_url(): string {
		$referer = wp_get_referer();

		return $referer ? $referer : admin_url();
	}

	/**
	 * Returns verified, sanitized parameters for a signed result notice.
	 *
	 * @return array{key:string,status:string}|null Notice data, or null for an invalid request.
	 */
	private static function valid_notice_request(): ?array {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['manacost_cache_purge'], $_GET['manacost_cache_notice'], $_GET['manacost_cache_nonce'] ) ) {
			return null;
		}

		$status = sanitize_key( wp_unslash( $_GET['manacost_cache_purge'] ) );
		$key    = sanitize_text_field( wp_unslash( $_GET['manacost_cache_notice'] ) );
		$nonce  = sanitize_text_field( wp_unslash( $_GET['manacost_cache_nonce'] ) );

		if ( '' === $status || '' === $key || ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			return null;
		}

		return array(
			'key'    => $key,
			'status' => $status,
		);
	}

	/**
	 * Filters failed cache-purge result entries.
	 *
	 * @param array<int, array{name:string,status:string,message:string}> $results Purge results.
	 * @return array<int, array{name:string,status:string,message:string}> Failed results.
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
	 * Persists a bounded summary of the latest purge.
	 *
	 * @param array<int, array{name:string,status:string,message:string}> $results Purge results.
	 * @param string                                                      $source  Purge origin.
	 * @return void
	 */
	private static function store_purge_results( array $results, string $source ): void {
		update_option(
			self::LAST_RESULTS_OPTION,
			array(
				'ran_at'  => gmdate( 'c' ),
				'source'  => $source,
				'failed'  => count( self::failed_results( $results ) ),
				'results' => $results,
			),
			false
		);
	}

	/**
	 * Runs every supported cache invalidation step.
	 *
	 * @param string $source Purge origin.
	 * @return array<int, array{name:string,status:string,message:string}> Purge results.
	 */
	private static function purge_all( string $source = 'manual' ): array {
		$results = array();

		self::run_step( $results, 'WP Rocket', array( __CLASS__, 'purge_wp_rocket' ) );
		self::run_step( $results, 'W3 Total Cache', array( __CLASS__, 'purge_w3_total_cache' ) );
		self::run_step( $results, 'Autoptimize', array( __CLASS__, 'purge_autoptimize' ) );
		self::run_step( $results, 'Perfmatters', array( __CLASS__, 'purge_perfmatters' ) );
		self::run_step( $results, 'Known local cache folders', array( __CLASS__, 'purge_local_cache_folders' ) );
		self::run_step( $results, 'WordPress object cache', array( __CLASS__, 'purge_object_cache' ) );
		if ( \Manacost\CachePurge\should_reset_opcache( $source ) ) {
			self::run_step( $results, 'PHP OPcache', '\\Manacost\\CachePurge\\reset_opcache' );
		}
		self::run_step( $results, 'Reverse proxy cache', array( __CLASS__, 'purge_reverse_proxy_cache' ) );
		self::run_step( $results, 'Cloudflare', array( __CLASS__, 'purge_cloudflare' ) );

		return $results;
	}

	/**
	 * Appends one bounded cache-invalidation result.
	 *
	 * @param array<int, array{name:string,status:string,message:string}> $results  Purge results.
	 * @param string                                                      $name     Step name.
	 * @param callable():string                                           $callback Step callback.
	 * @return void
	 */
	private static function run_step( array &$results, string $name, callable $callback ): void {
		try {
			$message   = (string) call_user_func( $callback );
			$results[] = array(
				'name'    => $name,
				'status'  => 'ok',
				'message' => $message,
			);
		} catch ( Throwable $error ) {
			$results[] = array(
				'name'    => $name,
				'status'  => 'failed',
				'message' => $error->getMessage(),
			);
		}
	}

	/**
	 * Clears WP Rocket caches when its API is present.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_wp_rocket(): string {
		$ran = array();

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

	/**
	 * Clears W3 Total Cache when its API is present.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_w3_total_cache(): string {
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all( array( 'ui_action' => 'manacost_admin_bar' ) );
			return 'flush_all';
		}

		return 'not active';
	}

	/**
	 * Clears Autoptimize when its API is present.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_autoptimize(): string {
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			autoptimizeCache::clearall();
			return 'clearall';
		}

		return 'not active';
	}

	/**
	 * Calls the supported Perfmatters cache hooks.
	 *
	 * @return string Step diagnostic.
	 */
	private static function purge_perfmatters(): string {
		do_action( 'perfmatters_clear_cache' );
		do_action( 'perfmatters_clear_used_css' );

		return 'hooks fired';
	}
}

Manacost_Cache_Purge::boot();
