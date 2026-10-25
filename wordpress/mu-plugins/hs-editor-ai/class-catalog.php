<?php
/**
 * Reuse the tooltip's exact card IDs and morphology-aware dictionaries.
 *
 * @package Manacost
 */

namespace Manacost\EditorAi;

defined( 'ABSPATH' ) || exit;

/** Candidate catalogs remain server-side and are bounded in prompts. */
final class Catalog {
	/**
	 * Read both installed catalogs without outbound calls.
	 *
	 * @return list<array{tag:string,id:string,name:string}>
	 */
	public static function all(): array {
		$result  = array();
		$loaders = array(
			'hs_card' => 'hs_smart_tooltip_get_search_catalog',
			'hs_bg'   => 'hs_smart_tooltip_get_bgs_search_catalog',
		);
		foreach ( $loaders as $tag => $loader ) {
			if ( ! function_exists( $loader ) ) {
				continue;
			}
			foreach ( $loader() as $entry ) {
				if ( is_array( $entry ) && is_string( $entry['id'] ?? null ) && is_string( $entry['name'] ?? null ) && preg_match( '/^[A-Za-z0-9_]+$/D', $entry['id'] ) ) {
					$result[] = array(
						'tag'  => $tag,
						'id'   => $entry['id'],
						'name' => $entry['name'],
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Match Russian word stems to avoid transmitting the complete dictionaries.
	 *
	 * @param string                                        $source Selected words.
	 * @param list<array{tag:string,id:string,name:string}> $cards Complete catalogs.
	 * @return list<array{tag:string,id:string,name:string}>
	 */
	public static function candidates( string $source, array $cards ): array {
		$normalized = str_replace( 'ё', 'е', mb_strtolower( $source ) );
		preg_match_all( '/[\p{L}]{4,}/u', $normalized, $matches );
		$roots = array();
		foreach ( $matches[0] as $word ) {
			$roots[ mb_substr( $word, 0, 4 ) ] = true;
		}
		$ranked = array(
			'hs_card' => array(),
			'hs_bg'   => array(),
		);
		foreach ( $cards as $card ) {
			$name  = str_replace( 'ё', 'е', mb_strtolower( $card['name'] ) );
			$score = str_contains( $normalized, $name ) ? 1000 : 0;
			preg_match_all( '/[\p{L}]{4,}/u', $name, $names );
			foreach ( $names[0] as $word ) {
				$score += isset( $roots[ mb_substr( $word, 0, 4 ) ] ) ? 1 : 0;
			}
			if ( $score > 0 && isset( $ranked[ $card['tag'] ] ) ) {
				$ranked[ $card['tag'] ][] = array(
					'score' => $score,
					'card'  => $card,
				);
			}
		}
		$result = array();
		foreach ( $ranked as $group ) {
			usort( $group, static fn( array $left, array $right ): int => $right['score'] <=> $left['score'] );
			foreach ( array_slice( $group, 0, 100 ) as $candidate ) {
				$result[] = $candidate['card'];
			}
		}
		return $result;
	}
}
