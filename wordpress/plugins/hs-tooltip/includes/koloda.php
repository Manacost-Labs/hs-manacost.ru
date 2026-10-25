<?php
/**
 * Supplement the local tooltip dictionary with the public Koloda card catalog.
 *
 * @package HS_Smart_Tooltip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Only card images on the provider's fixed HTTPS host.
 *
 * @param string $url Provider image URL.
 * @return string Validated image URL or empty string.
 */
function hs_smart_tooltip_koloda_image_url( string $url ): string {
	if ( str_starts_with( $url, '/uploads/' ) ) {
		$url = 'https://api.kolodahearthstone.com' . $url;
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https'
		|| ( $parts['host'] ?? '' ) !== 'api.kolodahearthstone.com'
		|| isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] )
		|| strlen( $url ) > 2048 || preg_match( '/[\x00-\x20\x7f]/', $url )
		|| ! preg_match( '#^/uploads/(?:constructed-related|cards)/[A-Za-z0-9_-]+\.(?:png|webp|jpe?g)$#D', $parts['path'] ?? '' )
	) {
		return '';
	}
	return $url;
}

/**
 * Map public provider fields into the existing tooltip entry format.
 *
 * @param array<string,mixed> $row Provider card.
 * @return array{id:string,dbf:string,entry:array{img:string,rarity:string,name:string,set_icon?:string,type?:string,techLevel?:int}}|null|WP_Error
 */
function hs_smart_tooltip_koloda_entry( array $row ) {
	$id    = $row['card_id'] ?? null;
	$name  = $row['name']['ru'] ?? null;
	$type  = $row['card_type']['slug'] ?? null;
	$set   = $row['card_set'] ?? null;
	$dbf   = $row['dbf'] ?? null;
	$image = $row['images']['card'] ?? null;
	// Koloda also lists unrevealed records with a dbf-only ID and no card type.
	if ( is_string( $id ) && preg_match( '/^blizzard:[0-9]+$/D', $id ) && null === $type ) {
		return null;
	}
	if ( ! is_string( $id ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{1,119}$/D', $id )
		|| ! is_string( $name ) || trim( $name ) === '' || strlen( $name ) > 512
		|| ! is_string( $type ) || ! is_string( $set ) || ! is_int( $dbf ) || $dbf < 1
		|| ! is_string( $image ) || hs_smart_tooltip_koloda_image_url( $image ) === ''
	) {
		return new WP_Error( 'tooltip_koloda_card' );
	}
	$entry = hs_smart_tooltip_card_to_entry(
		array(
			'id'     => $id,
			'name'   => wp_strip_all_tags( $name ),
			'type'   => $type,
			'set'    => $set,
			'dbfId'  => $dbf,
			'rarity' => $row['rarity'] ?? 'COMMON',
		),
		hs_smart_tooltip_set_icon_map()
	);
	if ( null !== $entry ) {
		$entry['entry']['img'] = hs_smart_tooltip_koloda_image_url( $image );
	}
	return $entry;
}

/**
 * Validate pagination before any card can reach the dictionary writer.
 *
 * @param array<string,mixed> $pagination Provider pagination.
 * @param int                 $page Requested page.
 * @param int                 $count Number of returned rows.
 * @return array{int,int,int}|null Total, pages and page size, or invalid metadata.
 */
function hs_smart_tooltip_koloda_pagination( array $pagination, int $page, int $count ): ?array {
	$total = $pagination['total'] ?? null;
	$pages = $pagination['total_pages'] ?? null;
	$size  = $pagination['per_page'] ?? null;
	if ( ! is_int( $total ) || $total < 1 || $total > 10000
		|| ! is_int( $pages ) || $pages < 1 || $pages > 100
		|| ! is_int( $size ) || $size < 1 || $size > 100
	) {
		return null;
	}
	if ( (int) ceil( $total / $size ) !== $pages || ( $pagination['page'] ?? null ) !== $page
		|| ( $pagination['has_next'] ?? null ) !== ( $page < $pages )
		|| min( $size, $total - ( $page - 1 ) * $size ) !== $count
	) {
		return null;
	}
	return array( $total, $pages, $size );
}

/**
 * Normalize one complete page and reject invalid or duplicated records.
 *
 * @param array<int,mixed> $rows Provider rows.
 * @param bool             $bgs Use Battlegrounds schema.
 * @return array<string,array{id:string,dbf:string,entry:array{img:string,rarity:string,name:string,set_icon?:string,type?:string,techLevel?:int}}|null>|WP_Error
 */
function hs_smart_tooltip_koloda_page_entries( array $rows, bool $bgs = false ) {
	$entries = array();
	foreach ( $rows as $row ) {
		$entry = is_array( $row ) ? ( $bgs ? hs_smart_tooltip_koloda_bgs_entry( $row ) : hs_smart_tooltip_koloda_entry( $row ) ) : new WP_Error( 'tooltip_koloda_card' );
		if ( is_wp_error( $entry ) ) {
			return $entry;
		}
		$id = $row['card_id'];
		if ( array_key_exists( $id, $entries ) ) {
			return new WP_Error( 'tooltip_koloda_duplicate' );
		}
		$entries[ $id ] = $entry;
	}
	return $entries;
}

/**
 * Read a complete fresh catalog only during manual/cron dictionary rebuilding.
 *
 * @param bool $bgs Read the Battlegrounds catalog.
 * @return array<string,array{id:string,dbf:string,entry:array{img:string,rarity:string,name:string,set_icon?:string,type?:string,techLevel?:int}}>|WP_Error
 */
function hs_smart_tooltip_koloda_catalog( bool $bgs = false ) {
	if ( ! class_exists( 'Manacost_Koloda_API' ) ) {
		return new WP_Error( 'tooltip_koloda_client' );
	}
	$deadline = microtime( true ) + 120;
	$entries  = array();
	$expected = null;
	for ( $page = 1; $page <= 100; $page++ ) {
		if ( microtime( true ) >= $deadline ) {
			return new WP_Error( 'tooltip_koloda_budget' );
		}
		$result = Manacost_Koloda_API::get(
			$bgs ? '/api/v1/cards' : '/api/v1/constructed-cards',
			array(
				'page'     => $page,
				'per_page' => 100,
			)
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! empty( $result['stale'] ) ) {
			return new WP_Error( 'tooltip_koloda_stale' );
		}
		$rows       = $result['data']['data'] ?? null;
		$pagination = $result['data']['pagination'] ?? null;
		if ( ! is_array( $rows ) || ! is_array( $pagination ) ) {
			return new WP_Error( 'tooltip_koloda_schema' );
		}
		$metadata = hs_smart_tooltip_koloda_pagination( $pagination, $page, count( $rows ) );
		if ( null === $metadata ) {
			return new WP_Error( 'tooltip_koloda_pagination' );
		}
		if ( null !== $expected && $expected !== $metadata ) {
			return new WP_Error( 'tooltip_koloda_changed' );
		}
		$expected     = $metadata;
		$page_entries = hs_smart_tooltip_koloda_page_entries( $rows, $bgs );
		if ( is_wp_error( $page_entries ) ) {
			return $page_entries;
		}
		if ( array_intersect_key( $entries, $page_entries ) ) {
			return new WP_Error( 'tooltip_koloda_duplicate' );
		}
		$entries += $page_entries;
		if ( $page === $metadata[1] ) {
			return array_filter( $entries );
		}
	}
	return new WP_Error( 'tooltip_koloda_budget' );
}

/**
 * Keep HSJSON/CORE name choices; add cards and aliases absent from that source.
 *
 * @param array{by_id:array<string|int,array<string,mixed>>,by_name:array<string,array<string,mixed>>,count:int} $dictionary Existing builder output.
 * @return array{by_id:array<string|int,array<string,mixed>>,by_name:array<string,array<string,mixed>>,count:int}|WP_Error
 */
function hs_smart_tooltip_add_koloda_cards( array $dictionary ) {
	$catalog = hs_smart_tooltip_koloda_catalog();
	if ( is_wp_error( $catalog ) ) {
		return $catalog;
	}
	foreach ( $catalog as $id => $card ) {
		if ( isset( $dictionary['by_id'][ $id ] ) ) {
			continue;
		}
		$entry                      = $card['entry'];
		$dictionary['by_id'][ $id ] = $entry;
		++$dictionary['count'];
		if ( ! isset( $dictionary['by_id'][ $card['dbf'] ] ) ) {
			$dictionary['by_id'][ $card['dbf'] ] = $entry;
		}
		$key = hs_smart_tooltip_dict_key( $entry['name'] );
		if ( '' !== $key && ! isset( $dictionary['by_name'][ $key ] ) ) {
			$dictionary['by_name'][ $key ] = $entry;
		}
	}
	return $dictionary;
}

/**
 * Normalize a Battlegrounds minion or spell using the existing BG schema.
 *
 * @param array<string,mixed> $row Provider card.
 * @return array{id:string,dbf:string,entry:array{img:string,rarity:string,name:string,type:string,techLevel:int}}|WP_Error
 */
function hs_smart_tooltip_koloda_bgs_entry( array $row ) {
	$id    = $row['card_id'] ?? null;
	$name  = $row['name']['ru'] ?? null;
	$type  = $row['card_type']['slug'] ?? null;
	$dbf   = $row['dbf'] ?? null;
	$image = $row['images']['card'] ?? null;
	$tech  = $row['tavern_tier'] ?? 0;
	if ( ! is_string( $id ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{1,119}$/D', $id )
		|| ! is_string( $name ) || trim( $name ) === '' || strlen( $name ) > 512
		|| ! in_array( $type, array( 'minion', 'spell' ), true )
		|| ! is_int( $dbf ) || $dbf < 1 || ! is_int( $tech ) || $tech < 0 || $tech > 7
		|| ! is_string( $image ) || hs_smart_tooltip_koloda_bgs_image_url( $image, $id ) === ''
	) {
		return new WP_Error( 'tooltip_koloda_bg_card' );
	}
	$rarities = array(
		1 => 'common',
		2 => 'common',
		3 => 'rare',
		4 => 'rare',
		5 => 'epic',
		6 => 'legendary',
		7 => 'legendary',
	);
	return array(
		'id'    => $id,
		'dbf'   => (string) $dbf,
		'entry' => array(
			'img'       => hs_smart_tooltip_koloda_bgs_image_url( $image, $id ),
			'rarity'    => $rarities[ $tech ] ?? 'common',
			'name'      => wp_strip_all_tags( $name ),
			'type'      => 'spell' === $type ? 'BATTLEGROUND_SPELL' : 'MINION',
			'techLevel' => $tech,
		),
	);
}

/**
 * Supplement BG IDs while preserving all existing HSJSON entries.
 *
 * @param array<string|int,array<string,mixed>> $by_id Existing BG dictionary.
 * @return array<string|int,array<string,mixed>>|WP_Error
 */
function hs_smart_tooltip_add_koloda_bgs_cards( array $by_id ) {
	$catalog = hs_smart_tooltip_koloda_catalog( true );
	if ( is_wp_error( $catalog ) ) {
		return $catalog;
	}
	foreach ( $catalog as $id => $card ) {
		if ( ! isset( $by_id[ $id ] ) ) {
			$by_id[ $id ] = $card['entry'];
		}
	}
	return $by_id;
}

/**
 * Allow the provider's exact HSJSON fallback for this BG card ID.
 *
 * @param string $url Provider image.
 * @param string $id Validated card ID.
 * @return string Approved image or empty string.
 */
function hs_smart_tooltip_koloda_bgs_image_url( string $url, string $id ): string {
	$koloda = hs_smart_tooltip_koloda_image_url( $url );
	if ( '' !== $koloda ) {
		return $koloda;
	}
	foreach ( array( '256x', '512x' ) as $size ) {
		if ( 'https://art.hearthstonejson.com/v1/bgs/latest/ruRU/' . $size . '/' . $id . '.png' === $url ) {
			return $url;
		}
	}
	return '';
}
