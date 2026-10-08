<?php
/**
 * Register authorized media and public rating endpoints.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Register authorized media and public rating endpoints. */
final class Ajax {
	/**
	 * Register authorized media and public rating endpoints.
	 */
	public static function register(): void {
		add_action( 'wp_ajax_hs_api_gallery_catalog', array( self::class, 'catalog' ) );
		add_action( 'wp_ajax_hs_api_gallery_import', array( self::class, 'import' ) );
		add_action( 'wp_ajax_hs_api_gallery_state', array( self::class, 'state' ) );
		add_action( 'wp_ajax_nopriv_hs_api_gallery_state', array( self::class, 'state' ) );
		add_action( 'wp_ajax_hs_api_gallery_vote', array( self::class, 'vote' ) );
		add_action( 'wp_ajax_nopriv_hs_api_gallery_vote', array( self::class, 'vote' ) );
	}

	/**
	 * Fetch a normalized page only for an authorized article author.
	 */
	public static function catalog(): void {
		self::authorize_editor();
		self::respond( Catalog::page( self::text( 'library' ), self::number( 'page' ), self::text( 'format' ) ) );
	}

	/**
	 * Download one image; each successful request can safely be retried.
	 */
	public static function import(): void {
		$post_id = self::authorize_editor();
		$result  = Importer::import( $post_id, self::text( 'library' ), self::text( 'object_id' ), self::text( 'variant' ) );
		self::respond( is_wp_error( $result ) ? $result : array( 'attachment_id' => $result ) );
	}

	/**
	 * Read current aggregate and own scores, plus a fresh action nonce.
	 */
	public static function state(): void {
		$post_id  = self::number( 'post_id' );
		$eligible = Gallery::eligible( $post_id );
		if ( ! $eligible ) {
			wp_send_json_error( array( 'message' => __( 'Оценки этой галереи недоступны.', 'manacost' ) ), 404 );
		}
		$rows   = Votes::summaries( $post_id, Identity::key() );
		$scores = array();
		foreach ( $eligible as $id ) {
			$scores[ $id ] = $rows[ $id ] ?? array(
				'average' => 0,
				'count'   => 0,
				'mine'    => 0,
			);
		}
		self::respond(
			array(
				'nonce'  => wp_create_nonce( 'hs_gallery_vote_' . $post_id ),
				'scores' => $scores,
			)
		);
	}

	/**
	 * One atomic score per article/image/voter; zero removes an existing score.
	 */
	public static function vote(): void {
		$post_id = self::number( 'post_id' );
		$image   = self::number( 'image_id' );
		$score   = self::number( 'score' );
		check_ajax_referer( 'hs_gallery_vote_' . $post_id, 'nonce' );
		if ( ! Identity::same_origin() || ! in_array( $image, Gallery::eligible( $post_id ), true ) || $score > 5 ) {
			wp_send_json_error( array( 'message' => __( 'Оценка не принята. Обновите страницу.', 'manacost' ) ), 403 );
		}
		$key = Identity::key( $score > 0 );
		if ( ! $key ) {
			wp_send_json_error( array( 'message' => __( 'Сначала поставьте оценку.', 'manacost' ) ), 400 );
		}
		$rate = 'hs_gallery_rate_' . hash( 'sha256', $key . ':' . $post_id );
		$last = get_transient( $rate );
		if ( $last && microtime( true ) - (float) $last < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Подождите секунду перед следующей оценкой.', 'manacost' ) ), 429 );
		}
		set_transient( $rate, microtime( true ), 3 );
		if ( ! Votes::save( $post_id, $image, $key, $score ) ) {
			wp_send_json_error( array( 'message' => __( 'Оценка не сохранилась. Повторите попытку.', 'manacost' ) ), 503 );
		}
		do_action( 'hs_api_gallery_vote_saved', $post_id, $image );
		self::respond( Votes::summary( $post_id, $image, $key ) );
	}

	/**
	 * Authorize uploads to a specific article.
	 *
	 * @return int Authorized post ID.
	 */
	private static function authorize_editor(): int {
		check_ajax_referer( 'hs_api_gallery_editor', 'nonce' );
		$post_id = self::number( 'post_id' );
		$post    = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) || ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Нет прав для создания галереи в этой статье.', 'manacost' ) ), 403 );
		}
		return $post_id;
	}

	/**
	 * Read an explicit, scalar AJAX parameter.
	 *
	 * @param string $key Explicit POST field.
	 * @return string Sanitized field.
	 */
	private static function text( string $key ): string {
		// Nonces and capabilities belong to the write handlers; public state is read-only.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Each mutation validates its action-specific nonce before writing.
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Validate an integer AJAX parameter.
	 *
	 * @param string $key Integer POST field.
	 * @return int Strict nonnegative integer.
	 */
	private static function number( string $key ): int {
		$value = self::text( $key );
		if ( ! preg_match( '/^[0-9]{1,10}$/D', $value ) ) {
			wp_send_json_error( array( 'message' => __( 'Некорректные параметры запроса.', 'manacost' ) ), 400 );
		}
		return (int) $value;
	}

	/**
	 * Return a non-cacheable JSON response.
	 *
	 * @param mixed $result Response value or safe error.
	 */
	private static function respond( $result ): void {
		nocache_headers();
		if ( is_wp_error( $result ) ) {
			$code    = $result->get_error_code();
			$message = is_string( $code ) && str_starts_with( $code, 'koloda_' ) ? __( 'API временно недоступен. Выбор сохранён — повторите попытку позже.', 'manacost' ) : $result->get_error_message();
			wp_send_json_error(
				array(
					'message' => $message,
					'code'    => $code,
				),
				502
			);
		}
		wp_send_json_success( $result );
	}
}
