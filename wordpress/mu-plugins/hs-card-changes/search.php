<?php
/**
 * Card search for the editor dialog: the hs-tooltip dictionary first, the card archive as the second source.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Merges two sources by Russian name. The dictionary is local and instant; the archive
 * (api.kolodahearthstone.com) knows cards newer than the dictionary, such as new Battlegrounds seasons.
 */
final class HS_Card_Changes_Search {
	private const PER_MODE  = 12;
	private const CACHE_TTL = 10 * MINUTE_IN_SECONDS;
	private const PATHS     = array(
		'constructed' => '/api/v1/constructed-cards',
		'bg'          => '/api/v1/cards',
	);

	/**
	 * Search both sources.
	 *
	 * @param string   $term Russian name or part of it.
	 * @param string   $kind "all", "constructed" or "bg".
	 * @param callable $archive_allowed Called only when the archive must be queried; false means rate limited.
	 * @phpstan-param callable():bool $archive_allowed
	 * @return array{items:list<array<string,string>>,archive:string,dictionary:bool}
	 */
	public static function run( string $term, string $kind, callable $archive_allowed ): array {
		$term  = trim( $term );
		$modes = 'all' === $kind ? array( 'constructed', 'bg' ) : array( $kind );
		if ( mb_strlen( $term ) < 2 ) {
			return array(
				'items'      => array(),
				'archive'    => 'skipped',
				'dictionary' => function_exists( 'hs_smart_tooltip_run_search' ),
			);
		}
		$dictionary = array();
		foreach ( $modes as $mode ) {
			$dictionary[ $mode ] = self::dictionary( $term, $mode );
		}
		$archive = self::archive( $term, $modes, $archive_allowed );
		$items   = array();
		foreach ( $modes as $mode ) {
			$items = array_merge( $items, self::merge( $dictionary[ $mode ] ?? array(), $archive['rows'][ $mode ] ?? array() ) );
		}
		return array(
			'items'      => $items,
			'archive'    => $archive['state'],
			'dictionary' => function_exists( 'hs_smart_tooltip_run_search' ),
		);
	}

	/**
	 * The local hs-tooltip dictionary; empty when that plugin is not active.
	 *
	 * @param string $term Search text.
	 * @param string $mode "constructed" or "bg".
	 * @return list<array<string,string>>
	 */
	private static function dictionary( string $term, string $mode ): array {
		if ( ! function_exists( 'hs_smart_tooltip_get_search_catalog' ) || ! function_exists( 'hs_smart_tooltip_get_bgs_search_catalog' ) || ! function_exists( 'hs_smart_tooltip_run_search' ) ) {
			return array();
		}
		$catalog = 'bg' === $mode ? hs_smart_tooltip_get_bgs_search_catalog() : hs_smart_tooltip_get_search_catalog();
		$rows    = array();
		foreach ( hs_smart_tooltip_run_search( $catalog, $term, self::PER_MODE ) as $entry ) {
			$row = self::row( $mode, $entry['id'] ?? null, $entry['name'] ?? null, $entry['img'] ?? null, 'dictionary' );
			if ( null !== $row ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Archive rows per mode: cached lists first, then one parallel provider request for the missing modes.
	 *
	 * @param string   $term Search text.
	 * @param string[] $modes Requested modes.
	 * @param callable $allowed Rate limit gate.
	 * @phpstan-param callable():bool $allowed
	 * @return array{rows:array<string,list<array<string,string>>>,state:string}
	 */
	private static function archive( string $term, array $modes, callable $allowed ): array {
		$rows    = array();
		$missing = array();
		foreach ( $modes as $mode ) {
			$cached = get_transient( self::key( $mode, $term ) );
			if ( is_array( $cached ) ) {
				$rows[ $mode ] = self::clean( $cached );
			} else {
				$missing[ $mode ] = self::PATHS[ $mode ] . '?' . http_build_query(
					array(
						'q'        => $term,
						'per_page' => self::PER_MODE,
					)
				);
			}
		}
		if ( ! $missing ) {
			return array(
				'rows'  => $rows,
				'state' => 'cached',
			);
		}
		if ( ! $allowed() ) {
			return array(
				'rows'  => $rows,
				'state' => 'limited',
			);
		}
		$failed = false;
		foreach ( HS_Card_Changes_Source::get_lists( $missing ) as $mode => $list ) {
			if ( null === $list ) {
				$failed = true;
				continue;
			}
			$rows[ $mode ] = self::parse_list( $list, $mode );
			set_transient( self::key( $mode, $term ), $rows[ $mode ], self::CACHE_TTL );
		}
		return array(
			'rows'  => $rows,
			'state' => $failed ? 'unavailable' : 'ok',
		);
	}

	/**
	 * Validated rows of one archive list.
	 *
	 * @param array<int,mixed> $rows Provider rows.
	 * @param string           $mode Card mode.
	 * @return list<array<string,string>>
	 */
	private static function parse_list( array $rows, string $mode ): array {
		$parsed = array();
		foreach ( $rows as $entry ) {
			$name = is_array( $entry ) && is_array( $entry['name'] ?? null ) ? ( $entry['name']['ru'] ?? $entry['name']['en'] ?? null ) : null;
			$row  = is_array( $entry ) ? self::row( $mode, $entry['card_id'] ?? null, $name, self::thumbnail_of( $entry ), 'archive' ) : null;
			if ( null !== $row ) {
				$parsed[] = $row;
			}
		}
		return $parsed;
	}

	/**
	 * Cache key of one archive query.
	 *
	 * @param string $mode Card mode.
	 * @param string $term Search text.
	 */
	private static function key( string $mode, string $term ): string {
		return 'hs_cc_search_' . hash( 'sha256', $mode . '|' . mb_strtolower( $term ) );
	}

	/**
	 * Re-validate cached rows, which cross a trust boundary.
	 *
	 * @param array<mixed> $cached Cached rows.
	 * @return list<array<string,string>>
	 */
	private static function clean( array $cached ): array {
		$rows = array();
		foreach ( $cached as $row ) {
			$clean = is_array( $row ) ? self::row( (string) ( $row['mode'] ?? '' ), $row['id'] ?? null, $row['name'] ?? null, $row['img'] ?? null, 'archive' ) : null;
			if ( null !== $clean ) {
				$rows[] = $clean;
			}
		}
		return $rows;
	}

	/**
	 * Best small image of an archive card: the tile, else the card render.
	 *
	 * @param array<mixed> $entry Archive row.
	 * @return mixed
	 */
	private static function thumbnail_of( array $entry ) {
		$images = is_array( $entry['images'] ?? null ) ? $entry['images'] : array();
		return $images['tile'] ?? $images['card'] ?? null;
	}

	/**
	 * One validated result row, or null for an unusable one.
	 *
	 * @param string $mode Card mode.
	 * @param mixed  $id Card identifier.
	 * @param mixed  $name Russian name.
	 * @param mixed  $image Thumbnail URL.
	 * @param string $source "dictionary" or "archive".
	 * @return array<string,string>|null
	 */
	private static function row( string $mode, $id, $name, $image, string $source ): ?array {
		if ( ! in_array( $mode, HS_Card_Changes_Pairs::modes(), true ) || ! is_string( $id ) || ! HS_Card_Changes_Pairs::valid_card( $id ) || ! is_string( $name ) ) {
			return null;
		}
		$name = HS_Card_Changes_Pairs::clean_name( $name );
		return '' === $name ? null : array(
			'id'     => $id,
			'mode'   => $mode,
			'name'   => $name,
			'img'    => self::thumbnail( $image ),
			'source' => $source,
		);
	}

	/**
	 * Thumbnails are shown only from HTTPS: the archive host's uploads or the dictionary's own image hosts.
	 *
	 * @param mixed $url Candidate URL.
	 */
	private static function thumbnail( $url ): string {
		if ( ! is_string( $url ) || ! str_starts_with( $url, 'https://' ) || strlen( $url ) > 300 ) {
			return '';
		}
		return esc_url_raw( $url );
	}

	/**
	 * Dictionary rows first (fast, familiar order), then archive-only cards; one row per card.
	 *
	 * @param list<array<string,string>> $dictionary Dictionary rows.
	 * @param list<array<string,string>> $archive Archive rows.
	 * @return list<array<string,string>>
	 */
	private static function merge( array $dictionary, array $archive ): array {
		$seen   = array();
		$merged = array();
		foreach ( array_merge( $dictionary, $archive ) as $row ) {
			if ( ! isset( $seen[ $row['mode'] . '|' . $row['id'] ] ) && count( $merged ) < 2 * self::PER_MODE ) {
				$seen[ $row['mode'] . '|' . $row['id'] ] = true;
				$merged[]                                = $row;
			}
		}
		return $merged;
	}
}
