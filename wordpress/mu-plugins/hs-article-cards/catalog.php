<?php
/**
 * Public article metadata from the local WordPress and Koloda's public REST API.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catalog adapters, with no copying of article bodies or protected content.
 *
 * @phpstan-import-type Article from HS_Article_Cards_Render
 */
final class HS_Article_Cards_Catalog {
	/**
	 * Identify the local catalog on either independently deployed WordPress site.
	 *
	 * @param string $source Catalog identifier.
	 */
	public static function is_remote( string $source ): bool {
		$host  = wp_parse_url( home_url(), PHP_URL_HOST );
		$local = in_array( $host, array( 'kolodahearthstone.com', 'test.kolodahearthstone.com', 'kolodahearthstone.ru' ), true ) ? 'koloda' : 'manacost';
		if ( defined( 'HS_ARTICLE_CARDS_LOCAL_SOURCE' ) && in_array( HS_ARTICLE_CARDS_LOCAL_SOURCE, array( 'manacost', 'koloda' ), true ) ) {
			$local = HS_ARTICLE_CARDS_LOCAL_SOURCE;
		}
		return $source !== $local;
	}

	/** Public remote fields, including links needed by WordPress embedding. */
	private const FIELDS = 'id,date,link,title,featured_media,categories,_links,_embedded';

	/**
	 * List one page of published articles.
	 *
	 * @param string $source manacost or koloda.
	 * @param string $search Public search terms.
	 * @param int    $category Category ID, or zero.
	 * @param int    $page One-based page.
	 * @return array{items:list<Article>,pages:int}|WP_Error
	 */
	public static function posts( string $source, string $search, int $category, int $page ) {
		if ( self::is_remote( $source ) ) {
			$query = array(
				'per_page' => 10,
				'page'     => $page,
				'search'   => $search,
				'_fields'  => self::FIELDS,
				'_embed'   => 'wp:featuredmedia',
			);
			if ( $category > 0 ) {
				$query['categories'] = $category;
			}
			$result = HS_Article_Cards_Remote::get( 'posts', $query, $source );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$items = array();
			foreach ( $result['items'] as $raw ) {
				$item = self::remote_article( $raw, $source );
				if ( is_wp_error( $item ) ) {
					return $item;
				}
				$items[] = $item;
			}
			return array(
				'items' => $items,
				'pages' => $result['pages'],
			);
		}
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'has_password'        => false,
				'posts_per_page'      => 10,
				'paged'               => $page,
				's'                   => $search,
				'cat'                 => $category,
				'ignore_sticky_posts' => true,
				'orderby'             => 'date',
				'order'               => 'DESC',
			)
		);
		$items = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$items[] = self::local_article( $post );
			}
		}
		return array(
			'items' => $items,
			'pages' => max( 1, (int) $query->max_num_pages ),
		);
	}

	/**
	 * Read a selected article again on the server before creating HTML.
	 *
	 * @param string $source Source.
	 * @param int    $id Public article ID.
	 * @return Article|WP_Error
	 */
	public static function article( string $source, int $id ) {
		if ( self::is_remote( $source ) ) {
			$result = HS_Article_Cards_Remote::get(
				'posts/' . $id,
				array(
					'_fields' => self::FIELDS,
					'_embed'  => 'wp:featuredmedia',
				),
				$source
			);
			return is_wp_error( $result ) ? $result : self::remote_article( $result['items'][0] ?? array(), $source );
		}
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
			return self::invalid_article();
		}
		return self::local_article( $post );
	}

	/**
	 * Paginate categories as well: do not silently drop a provider's second page.
	 *
	 * @param string $source Source.
	 * @param int    $page Page number.
	 * @return array{items:list<array{id:int,name:string}>,pages:int}|WP_Error
	 */
	public static function categories( string $source, int $page ) {
		if ( self::is_remote( $source ) ) {
			$result = HS_Article_Cards_Remote::get(
				'categories',
				array(
					'per_page'   => 100,
					'page'       => $page,
					'hide_empty' => 'true',
					'_fields'    => 'id,name',
				),
				$source
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$items = array();
			foreach ( $result['items'] as $term ) {
				if ( ! isset( $term['name'] ) || ! is_string( $term['name'] ) ) {
					return self::invalid_article();
				}
				$items[] = array(
					'id'   => (int) $term['id'],
					'name' => self::plain_text( $term['name'] ),
				);
			}
			return array(
				'items' => $items,
				'pages' => $result['pages'],
			);
		}
		$terms = get_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'number'     => 100,
				'offset'     => ( $page - 1 ) * 100,
			)
		);
		$total = wp_count_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $terms ) || is_wp_error( $total ) ) {
			return new WP_Error( 'article_cards_categories', __( 'Не удалось загрузить категории. Повторите запрос.', 'hs-article-cards' ) );
		}
		$items = array();
		foreach ( $terms as $term ) {
			$items[] = array(
				'id'   => $term->term_id,
				'name' => self::plain_text( $term->name ),
			);
		}
		return array(
			'items' => $items,
			'pages' => max( 1, (int) ceil( (int) $total / 100 ) ),
		);
	}

	/**
	 * Use existing attachment APIs so S3/offload filters retain ownership.
	 *
	 * @param WP_Post $post Published local article.
	 * @return Article
	 */
	private static function local_article( WP_Post $post ): array {
		return array(
			'id'         => $post->ID,
			'title'      => self::plain_text( get_the_title( $post ) ),
			'url'        => (string) get_permalink( $post ),
			'image'      => (string) get_the_post_thumbnail_url( $post, 'medium_large' ),
			'date'       => (string) get_the_date( 'Y-m-d', $post ),
			'date_label' => (string) get_the_date( 'j F Y', $post ),
		);
	}

	/**
	 * Normalize only public metadata; remote HTML is never returned to the browser.
	 *
	 * @param array<string,mixed> $raw REST article.
	 * @param string              $source Provider identifier.
	 * @return Article|WP_Error
	 */
	private static function remote_article( array $raw, string $source ) {
		$title = $raw['title']['rendered'] ?? null;
		$date  = $raw['date'] ?? null;
		$url   = $raw['link'] ?? null;
		if ( ! isset( $raw['id'] ) || ! is_int( $raw['id'] ) || ! is_string( $title ) || ! is_string( $date ) || ! is_string( $url ) || ! self::provider_url( $url, $source ) ) {
			return self::invalid_article();
		}
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s', $date, new DateTimeZone( 'UTC' ) );
		if ( false === $parsed || $parsed->format( 'Y-m-d\TH:i:s' ) !== $date ) {
			return self::invalid_article();
		}
		$date_label = wp_date( 'j F Y', $parsed->getTimestamp(), new DateTimeZone( 'UTC' ) );
		if ( false === $date_label ) {
			return self::invalid_article();
		}
		$media = $raw['_embedded']['wp:featuredmedia'][0] ?? array();
		$image = $media['media_details']['sizes']['medium_large']['source_url'] ?? $media['source_url'] ?? '';
		return array(
			'id'         => $raw['id'],
			'title'      => self::plain_text( $title ),
			'url'        => $url,
			'image'      => is_string( $image ) && self::provider_url( $image, $source ) ? $image : '',
			'date'       => $parsed->format( 'Y-m-d' ),
			'date_label' => $date_label,
		);
	}

	/**
	 * Reject credentials, ports and arbitrary third-party image/link hosts.
	 *
	 * @param string $url Remote URL.
	 * @param string $source Provider identifier.
	 * @return bool
	 */
	private static function provider_url( string $url, string $source ): bool {
		$parts = wp_parse_url( $url );
		return is_array( $parts ) && 'https' === ( $parts['scheme'] ?? '' ) && HS_Article_Cards_Remote::host( $source ) === ( $parts['host'] ?? '' )
			&& ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && ! isset( $parts['port'] );
	}

	/**
	 * Decode entities and strip markup before any DOM/HTML consumer.
	 *
	 * @param string $value Display text.
	 * @return string
	 */
	private static function plain_text( string $value ): string {
		return mb_substr( wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ), 0, 500 );
	}

	/**
	 * Safe metadata validation failure.
	 *
	 * @return WP_Error
	 */
	private static function invalid_article(): WP_Error {
		return new WP_Error( 'article_cards_invalid_article', __( 'Статья недоступна или её данные изменились. Обновите результаты поиска.', 'hs-article-cards' ) );
	}
}
