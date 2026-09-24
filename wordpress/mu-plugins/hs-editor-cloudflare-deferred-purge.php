<?php
/**
 * Plugin Name: HS Editor Cloudflare Deferred Purge
 * Description: Finishes WP Rocket's Cloudflare purge after the editor receives its save response.
 *
 * @package HSEditorCloudflareDeferredPurge
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps WP Rocket's local invalidation synchronous and completes its Cloudflare
 * requests in the same PHP request after FastCGI has sent the redirect.
 */
final class HS_Editor_Cloudflare_Deferred_Purge {
	private const CALLBACK_CLASS = 'WP_Rocket\\ThirdParty\\Plugins\\CDN\\Cloudflare';

	private const HOOKS = array(
		'after_rocket_clean_files' => 'purge_cloudflare_partial',
		'after_rocket_clean_home'  => 'purge_cloudflare_home',
	);

	/**
	 * WP Rocket callbacks waiting until the response is sent.
	 *
	 * @var array<int, array{0:callable,1:array<int|string,mixed>}>
	 */
	private static array $pending = array();

	/** Registers handlers for the editor and response completion. */
	public static function boot(): void {
		add_action( 'pre_post_update', array( __CLASS__, 'prepare_editor_save' ), 1, 2 );
		add_action( 'shutdown', array( __CLASS__, 'finish_after_response' ), 0 );
	}

	/**
	 * Wraps only WP Rocket's Cloudflare callbacks after core verifies the save.
	 *
	 * @param int                  $post_id Post being updated.
	 * @param array<string, mixed> $data    New post fields.
	 */
	public static function prepare_editor_save( int $post_id, array $data ): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		$request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$action         = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Core checks the nonce before pre_post_update.

		if ( ! function_exists( 'fastcgi_finish_request' )
			|| ! is_admin()
			|| ( $data['post_type'] ?? '' ) !== 'post'
			|| 'POST' !== $request_method
			|| (string) wp_parse_url( $request_uri, PHP_URL_PATH ) !== '/wp-admin/post.php'
			|| 'editpost' !== $action
			|| ! current_user_can( 'edit_post', $post_id )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		) {
			return;
		}

		foreach ( self::HOOKS as $hook => $method ) {
			$registered = self::find_callback( $hook, $method );
			if ( null === $registered || ! remove_action( $hook, $registered['callback'], $registered['priority'] ) ) {
				continue;
			}

			$callback = $registered['callback'];
			add_action(
				$hook,
				static function ( ...$args ) use ( $callback ): void {
					self::$pending[] = array( $callback, $args );
				},
				$registered['priority'],
				$registered['accepted_args']
			);
		}
	}

	/** Runs the original callbacks once the editor has received the redirect. */
	public static function finish_after_response(): void {
		if ( ! self::$pending ) {
			return;
		}

		fastcgi_finish_request();
		foreach ( self::$pending as $task ) {
			call_user_func_array( $task[0], $task[1] );
		}
		self::$pending = array();
	}

	/**
	 * Finds only the expected WP Rocket integration callback.
	 *
	 * @param string $hook WordPress action name.
	 * @param string $method Callback method name.
	 * @return array{callback:callable,priority:int,accepted_args:int}|null Registered callback.
	 */
	private static function find_callback( string $hook, string $method ): ?array {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook ) {
			return null;
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $entry ) {
				$callback = $entry['function'] ?? null;
				if ( is_array( $callback )
					&& isset( $callback[0], $callback[1] )
					&& is_object( $callback[0] )
					&& is_a( $callback[0], self::CALLBACK_CLASS )
					&& $callback[1] === $method
					&& is_callable( $callback )
				) {
					return array(
						'callback'      => $callback,
						'priority'      => (int) $priority,
						'accepted_args' => (int) ( $entry['accepted_args'] ?? 1 ),
					);
				}
			}
		}

		return null;
	}
}

HS_Editor_Cloudflare_Deferred_Purge::boot();
