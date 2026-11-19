<?php
/**
 * Background cache warm-up: saving a post and cache misses only schedule it, never fetch.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/** Warms the provider cache for the unpinned changes of one post, off the request path. */
final class HS_Card_Changes_Warm {
	/** Register the cron callback and the save hook. */
	public static function register(): void {
		add_action( 'hs_card_changes_warm', array( self::class, 'run' ), 10, 1 );
		add_action( 'save_post', array( self::class, 'on_save' ), 20, 2 );
	}

	/**
	 * Schedule a warm-up when a saved article contains changes that still rely on the provider.
	 *
	 * @param int     $post_id Saved post.
	 * @param WP_Post $post Saved post object.
	 */
	public static function on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! in_array( $post->post_status, array( 'publish', 'draft', 'pending', 'future', 'private' ), true ) ) {
			return;
		}
		$requests = HS_Card_Changes_Render::requests( $post->post_content, $post_id );
		if ( $requests ) {
			HS_Card_Changes_Source::queue( $requests[0], $post_id );
		}
	}

	/**
	 * Cron callback: one deduplicated job per post, up to four parallel requests, one shared time budget.
	 *
	 * @param int $post_id Post to warm.
	 */
	public static function run( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			$requests = HS_Card_Changes_Render::requests( $post->post_content, $post_id );
			$before   = self::signature( $requests );
			HS_Card_Changes_Source::refresh( $requests );
			if ( $requests && self::signature( $requests ) !== $before ) {
				// The page cache may hold HTML rendered before the warm-up; WP Rocket purges the post on this hook.
				clean_post_cache( $post_id );
			}
		}
		HS_Card_Changes_Source::dequeue_post( $post_id );
	}

	/**
	 * Fingerprint of the cached resolutions of a post, to purge its page only when something changed.
	 *
	 * @param list<array{0:string,1:string,2:string}> $requests Mode, card and date triples.
	 */
	private static function signature( array $requests ): string {
		$resolutions = array();
		foreach ( $requests as $request ) {
			$resolutions[] = HS_Card_Changes_Cache::entry( ...$request )['resolution'] ?? null;
		}
		return hash( 'sha256', (string) wp_json_encode( $resolutions ) );
	}
}
