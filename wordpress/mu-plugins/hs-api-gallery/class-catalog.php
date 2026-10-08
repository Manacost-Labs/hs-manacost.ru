<?php
/**
 * List supported provider libraries.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** List supported provider libraries. */
final class Catalog {
	/**
	 * List supported provider libraries.
	 *
	 * @return array<string,string> Editor labels by library slug.
	 */
	public static function libraries(): array {
		return array(
			'constructed-cards' => __( 'Карты Hearthstone', 'manacost' ),
			'cards'             => __( 'Карты Полей сражений', 'manacost' ),
			'heroes'            => __( 'Герои Полей сражений', 'manacost' ),
			'hero-skins'        => __( 'Облики героев', 'manacost' ),
			'pets'              => __( 'Питомцы', 'manacost' ),
			'coins'             => __( 'Монетки', 'manacost' ),
			'timewarped-cards'  => __( 'Хрономальные карты', 'manacost' ),
			'diamond-cards'     => __( 'Алмазные карты', 'manacost' ),
			'anomalies'         => __( 'Аномалии', 'manacost' ),
			'quests'            => __( 'Задания', 'manacost' ),
			'dark-gifts'        => __( 'Тёмные дары', 'manacost' ),
			'darkmoon-prizes'   => __( 'Призы Новолуния', 'manacost' ),
			'rewards'           => __( 'Награды', 'manacost' ),
			'trinkets'          => __( 'Аксессуары', 'manacost' ),
		);
	}

	/**
	 * Only documented public image hosts; the downloader also blocks private IPs.
	 *
	 * @param string $url Candidate image URL.
	 * @return string Valid URL or empty string.
	 */
	public static function image_url( string $url ): string {
		if ( str_starts_with( $url, '/uploads/' ) ) {
			$url = 'https://api.kolodahearthstone.com' . $url;
		}
		$parts = wp_parse_url( $url );
		$hosts = array( 'api.kolodahearthstone.com', 'hearthstone.wiki.gg', 'art.hearthstonejson.com', 'd15f34w2p8l1cc.cloudfront.net' );
		if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( $parts['host'] ?? '', $hosts, true ) ) {
			return '';
		}
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) || strlen( $url ) > 2048 ) {
			return '';
		}
		return $url;
	}

	/**
	 * Normalize only names, IDs and static image variants, never provider markup.
	 *
	 * @param array<string,mixed> $row Provider object.
	 * @return array{id:string,name:string,images:array<string,string>}
	 */
	public static function normalize( array $row ): array {
		$id     = $row['card_id'] ?? $row['base_card']['card_id'] ?? '';
		$id     = is_string( $id ) ? $id : '';
		$name   = self::display_name( $row, $id );
		$images = array();
		$source = is_array( $row['images'] ?? null ) ? $row['images'] : array();
		foreach ( array( 'card', 'hero', 'static', 'diamond', 'art', 'full_art', 'golden', 'signature', 'framed', 'horizontal', 'crop', 'wiki' ) as $variant ) {
			$url = is_string( $source[ $variant ] ?? null ) ? self::image_url( $source[ $variant ] ) : '';
			if ( $url ) {
				$images[ $variant ] = $url;
			}
		}
		return array(
			'id'     => $id,
			'name'   => strlen( $name ) > 400 ? $id : $name,
			'images' => $images,
		);
	}

	/**
	 * Resolve the localized name across cards, cosmetic coins and pet variants.
	 *
	 * @param array<string,mixed> $row Provider object.
	 * @param string              $fallback Validated object ID.
	 * @return string Plain display name.
	 */
	private static function display_name( array $row, string $fallback ): string {
		$names = $row['name'] ?? array();
		if ( is_string( $names ) ) {
			return wp_strip_all_tags( $names );
		}
		foreach ( array( 'ru', 'coin_en', 'en', 'card_ru' ) as $language ) {
			if ( is_array( $names ) && is_string( $names[ $language ] ?? null ) && $names[ $language ] ) {
				return wp_strip_all_tags( $names[ $language ] );
			}
		}
		$name = $row['variant']['name'] ?? $fallback;
		return is_string( $name ) ? wp_strip_all_tags( $name ) : $fallback;
	}

	/**
	 * Fetch one page. Search is local: provider q currently returns HTTP 500.
	 *
	 * @param string $library Known library.
	 * @param int    $page Page number.
	 *
	 * @param string $format Constructed format.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function page( string $library, int $page, string $format ) {
		if ( ! isset( self::libraries()[ $library ] ) || $page < 1 || $page > 200 || ! class_exists( '\Manacost_Koloda_API' ) ) {
			return new \WP_Error( 'gallery_catalog', __( 'Библиотека недоступна.', 'manacost' ) );
		}
		$query = array(
			'page'     => $page,
			'per_page' => in_array( $library, array( 'heroes', 'hero-skins', 'coins', 'timewarped-cards' ), true ) ? 50 : 100,
		);
		if ( 'constructed-cards' === $library && in_array( $format, array( 'standard', 'wild' ), true ) ) {
			$query['format'] = $format;
		}
		$result = \Manacost_Koloda_API::get( '/api/v1/' . $library, $query );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$data = $result['data'];
		if ( ! is_array( $data['data'] ?? null ) || ! is_array( $data['pagination'] ?? null ) ) {
			return new \WP_Error( 'gallery_schema', __( 'API вернул неподдерживаемый ответ.', 'manacost' ) );
		}
		$rows = array();
		foreach ( array_slice( $data['data'], 0, 100 ) as $row ) {
			$item = is_array( $row ) ? self::normalize( $row ) : null;
			if ( $item && preg_match( '/^[A-Za-z0-9_-]{1,120}$/D', $item['id'] ) && $item['images'] ) {
				$rows[] = $item;
			}
		}
		return array(
			'items'    => $rows,
			'has_next' => ! empty( $data['pagination']['has_next'] ),
			'stale'    => $result['stale'],
		);
	}

	/**
	 * Resolve the chosen image server-side, never accept a client-supplied URL.
	 *
	 * @param string $library Known library.
	 * @param string $id Provider ID.
	 *
	 * @param string $variant Static image variant.
	 * @return array{library:string,id:string,name:string,url:string}|\WP_Error
	 */
	public static function resolve( string $library, string $id, string $variant ) {
		if ( ! isset( self::libraries()[ $library ] ) || ! preg_match( '/^[A-Za-z0-9_-]{1,120}$/D', $id ) || ! class_exists( '\Manacost_Koloda_API' ) ) {
			return new \WP_Error( 'gallery_selection', __( 'Некорректный объект.', 'manacost' ) );
		}
		$result = \Manacost_Koloda_API::get( '/api/v1/' . $library . '/' . $id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$row  = $result['data']['data'] ?? $result['data'];
		$item = is_array( $row ) ? self::normalize( $row ) : null;
		if ( ! $item || $id !== $item['id'] || empty( $item['images'][ $variant ] ) ) {
			return new \WP_Error( 'gallery_image', __( 'Это изображение недоступно.', 'manacost' ) );
		}
		return array(
			'library' => $library,
			'id'      => $id,
			'name'    => $item['name'],
			'url'     => $item['images'][ $variant ],
		);
	}
}
