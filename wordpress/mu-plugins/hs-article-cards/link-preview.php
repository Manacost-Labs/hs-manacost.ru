<?php
/**
 * Intent-loaded previews of public article links. Never fetch during rendering.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/** Signed public references bound to a published, unprotected document. */
final class HS_Article_Link_Preview {
	/** Register read-only behavior. */
	public static function register(): void {
		add_filter( 'the_content', array( self::class, 'content' ), 30 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_action( 'rest_api_init', array( self::class, 'route' ) );
	}

	/** Public runtime switch without a stored setting. */
	private static function enabled(): bool {
		return ! defined( 'HS_ARTICLE_LINK_PREVIEW_ENABLED' ) || HS_ARTICLE_LINK_PREVIEW_ENABLED;
	}

	/** Only singular article bodies receive the enhancement. */
	public static function enqueue(): void {
		if ( ! self::enabled() || ! is_singular( array( 'post', 'page' ) ) ) {
			return;
		}
		wp_enqueue_style( 'hs-link-preview', plugins_url( 'link-preview.css', __FILE__ ), array(), '1.3.0' );
		wp_enqueue_script( 'hs-link-preview', plugins_url( 'link-preview.js', __FILE__ ), array(), '1.3.0', true );
		wp_localize_script( 'hs-link-preview', 'hsLinkPreview', array( 'endpoint' => rest_url( 'hs-article-cards/v1/preview' ) ) );
	}

	/**
	 * Decorate the current rendered article, never its stored representation.
	 *
	 * @param string $content Rendered body.
	 */
	public static function content( string $content ): string {
		$post = get_post();
		if ( ! self::enabled() || ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() || ! $post instanceof WP_Post ) {
			return $content;
		}
		return self::markup( $content, $post );
	}

	/**
	 * Mark eligible anchors without changing their href, target or text.
	 *
	 * @param string  $content Body HTML.
	 * @param WP_Post $post Owning document.
	 */
	public static function markup( string $content, WP_Post $post ): string {
		$tags = new WP_HTML_Tag_Processor( $content );
		while ( $tags->next_tag( array( 'tag_name' => 'A' ) ) ) {
			$href = $tags->get_attribute( 'href' );
			if ( is_string( $href ) && '' !== $href && ! str_starts_with( $href, '#' ) && ! preg_match( '/^[a-z][a-z0-9+.-]*:/i', $href ) ) {
				$base = get_permalink( $post );
				$href = WP_Http::make_absolute_url( $href, $base ? $base : home_url( '/' ) );
			}
			$classes = $tags->get_attribute( 'class' );
			if ( ! is_string( $href ) || ! self::url( $href ) || null !== $tags->get_attribute( 'download' )
				|| ( is_string( $classes ) && preg_match( '/(?:hs-card-tooltip|hs-article-card__link|hs-v-social)/', $classes ) ) ) {
				continue;
			}
			$tags->set_attribute( 'data-hs-preview', (string) $post->ID );
			$tags->set_attribute( 'data-hs-preview-url', $href );
			if ( self::public_post( $post ) ) {
				$tags->set_attribute( 'data-hs-signature', self::signature( $post, $href ) );
			}
		}
		return $tags->get_updated_html();
	}

	/**
	 * Pure URL shape check; DNS validation happens only on an intentional request.
	 *
	 * @param string $url Candidate page.
	 */
	private static function url( string $url ): bool {
		$parts = wp_parse_url( $url );
		return strlen( $url ) <= 2048 && is_array( $parts ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true )
			&& ! empty( $parts['host'] ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && ! isset( $parts['port'] );
	}

	/**
	 * Only public content can authorize remote reads.
	 *
	 * @param WP_Post $post Document.
	 */
	private static function public_post( WP_Post $post ): bool {
		return 'publish' === $post->post_status && '' === $post->post_password && in_array( $post->post_type, array( 'post', 'page' ), true );
	}

	/**
	 * Invalidate references when an article changes; never expose the signing key.
	 *
	 * @param WP_Post $post Public document.
	 * @param string  $url Exact href.
	 */
	public static function signature( WP_Post $post, string $url ): string {
		return hash_hmac( 'sha256', $post->ID . '|' . $post->post_modified_gmt . '|' . $url, wp_salt( 'auth' ) );
	}

	/** Public read-only route, constrained to signed references. */
	public static function route(): void {
		register_rest_route(
			'hs-article-cards/v1',
			'/preview',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'metadata' ),
				'permission_callback' => array( self::class, 'permission' ),
				'args'                => array(
					'post'      => array(
						'type'     => 'integer',
						'minimum'  => 1,
						'required' => true,
					),
					'url'       => array(
						'type'      => 'string',
						'maxLength' => 2048,
						'required'  => true,
					),
					'signature' => array(
						'type'     => 'string',
						'pattern'  => '^[a-f0-9]{64}$',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * No arbitrary URL proxy and no access to drafts or password-protected posts.
	 *
	 * @param WP_REST_Request $request Validated REST input.
	 * @return bool|WP_Error
	 */
	public static function permission( WP_REST_Request $request ) {
		$post = get_post( (int) $request->get_param( 'post' ) );
		$url  = $request->get_param( 'url' );
		$sig  = $request->get_param( 'signature' );
		if ( self::enabled() && $post instanceof WP_Post && self::public_post( $post ) && is_string( $url ) && self::url( $url )
			&& is_string( $sig ) && hash_equals( self::signature( $post, $url ), $sig ) ) {
			return true;
		}
		return new WP_Error( 'hs_preview_unavailable', __( 'Предпросмотр недоступен.', 'hs-article-cards' ), array( 'status' => 403 ) );
	}

	/**
	 * Bounded metadata request with positive/negative caching and a request budget.
	 *
	 * @param WP_REST_Request $request Authorized request.
	 * @return array{title:string,description:string,image:string}|WP_Error
	 */
	public static function metadata( WP_REST_Request $request ) {
		$permission = self::permission( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}
		$url      = (string) $request->get_param( 'url' );
		$key      = 'hs_link_preview_' . hash( 'sha256', $url );
		$cached   = get_transient( $key );
		$fallback = array(
			'title'       => (string) wp_parse_url( $url, PHP_URL_HOST ),
			'description' => '',
			'image'       => '',
		);
		if ( is_array( $cached ) && isset( $cached['title'], $cached['description'], $cached['image'] )
			&& is_string( $cached['title'] ) && is_string( $cached['description'] ) && is_string( $cached['image'] ) ) {
			return array(
				'title'       => $cached['title'],
				'description' => $cached['description'],
				'image'       => $cached['image'],
			);
		}
		// A short negative entry also prevents repeated failed hovers from retrying.
		set_transient( $key, $fallback, 5 * MINUTE_IN_SECONDS );
		$budget_key = 'hs_link_preview_budget_' . (string) floor( time() / 60 );
		$used       = (int) get_transient( $budget_key );
		if ( $used >= 30 ) {
			return $fallback;
		}
		set_transient( $budget_key, $used + 1, 2 * MINUTE_IN_SECONDS );
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 4,
				'redirection'         => 0,
				'limit_response_size' => 262145,
				'headers'             => array( 'Accept' => 'text/html' ),
				'cookies'             => array(),
			)
		);
		$body     = wp_remote_retrieve_body( $response );
		$type     = wp_remote_retrieve_header( $response, 'content-type' );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response )
			|| ! is_string( $type ) || ! preg_match( '#^text/html(?:\s*;|$)#i', $type ) ) {
			do_action( 'hs_article_link_preview_result', 'fallback' );
			return $fallback;
		}
		$result = self::parse( substr( $body, 0, 262144 ), $fallback );
		set_transient( $key, $result, 12 * HOUR_IN_SECONDS );
		do_action( 'hs_article_link_preview_result', 'success' );
		return $result;
	}

	/**
	 * Extract public text/image metadata; never return target page HTML.
	 *
	 * @param string                                              $body Bounded HTML.
	 * @param array{title:string,description:string,image:string} $result Fallback.
	 * @return array{title:string,description:string,image:string}
	 */
	private static function parse( string $body, array $result ): array {
		if ( preg_match( '#<title[^>]*>(.*?)</title>#is', $body, $match ) ) {
			$result['title'] = self::text( $match[1], 200 );
		}
		$tags = new WP_HTML_Tag_Processor( $body );
		while ( $tags->next_tag( array( 'tag_name' => 'META' ) ) ) {
			$name  = $tags->get_attribute( 'property' ) ?? $tags->get_attribute( 'name' );
			$value = $tags->get_attribute( 'content' );
			if ( ! is_string( $value ) ) {
				continue;
			}
			if ( 'og:title' === $name ) {
				$result['title'] = self::text( $value, 200 );
			} elseif ( in_array( $name, array( 'og:description', 'description' ), true ) ) {
				$result['description'] = self::text( $value, 300 );
			} elseif ( 'og:image' === $name && self::url( $value ) && wp_http_validate_url( $value ) ) {
				$result['image'] = esc_url_raw( $value );
			}
		}
		return $result;
	}

	/**
	 * Decode and bound a plain metadata label.
	 *
	 * @param string $value Untrusted value.
	 * @param int    $limit Length limit.
	 */
	private static function text( string $value, int $limit ): string {
		return mb_substr( wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), 0, $limit );
	}
}
