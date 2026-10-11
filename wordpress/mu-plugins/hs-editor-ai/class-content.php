<?php
/**
 * Validate text-only suggestions before assembling the unchanged document.
 *
 * @package Manacost
 */

namespace Manacost\EditorAi;

defined( 'ABSPATH' ) || exit;

/** Immutable markup and mode-specific editorial guards. */
final class Content {

	/**
	 * Split markup, shortcodes and links away from editable prose.
	 *
	 * @param string $source Original editor fragment.
	 * @return list<string>
	 */
	public static function parts( string $source ): array {
		$protected = '\[[a-zA-Z_][^\]]*\](?:.*?\[/[a-zA-Z_][^\]]*\])?|<(?:script|style|pre|code|iframe)\b[^>]*>.*?</(?:script|style|pre|code|iframe)>|<!--.*?-->|<[^>]*>|&(?:\#\d+|\#x[0-9a-f]+|[a-z][a-z0-9]+);|https?://[^\s<>]+';
		// Capture just the complete protected match, not its internal groups.
		$parts = preg_split( '~(' . $protected . ')~isu', $source, -1, PREG_SPLIT_DELIM_CAPTURE );
		return is_array( $parts ) ? $parts : array( $source );
	}

	/**
	 * Return only mutable text nodes, retaining stable indexes.
	 *
	 * @param string $source Original fragment.
	 * @return list<array{segment:int,text:string}>
	 */
	public static function segments( string $source ): array {
		$result = array();
		foreach ( self::parts( $source ) as $index => $part ) {
			if ( self::editable( $part ) ) {
				$result[] = array(
					'segment' => $index,
					'text'    => $part,
				);
			}
		}
		return $result;
	}

	/**
	 * Whether a split part is ordinary prose.
	 *
	 * @param string $text Split part.
	 * @return bool
	 */
	private static function editable( string $text ): bool {
		return '' !== trim( $text ) && ! preg_match( '~^(?:<|\[|&|https?://)~i', $text );
	}

	/**
	 * Apply the entire suggestion atomically; never accept provider markup.
	 *
	 * @param string                                        $source Original editor content.
	 * @param array<mixed>                                  $edits Model suggestions.
	 * @param string                                        $mode Selected operation.
	 * @param list<array{tag:string,id:string,name:string}> $cards Confirmed catalog.
	 * @return string|\WP_Error
	 */
	public static function apply( string $source, array $edits, string $mode, array $cards = array() ) {
		$parts = self::parts( $source );
		$seen  = array();
		if ( count( $edits ) > 500 || ! in_array( $mode, array( 'cards', 'editor', 'proofread' ), true ) ) {
			return self::invalid();
		}
		foreach ( $edits as $edit ) {
			if ( ! is_array( $edit ) || ! is_int( $edit['segment'] ?? null ) || ! is_string( $edit['replacement'] ?? null ) ) {
				return self::invalid();
			}
			$index       = $edit['segment'];
			$replacement = $edit['replacement'];
			if ( isset( $seen[ $index ] ) || ! isset( $parts[ $index ] ) || ! self::editable( $parts[ $index ] ) || strlen( $replacement ) > 80000 ) {
				return self::invalid();
			}
			$original = $parts[ $index ];
			if ( 'cards' === $mode ) {
				if ( ! self::card_edit( $original, $replacement, $cards ) ) {
					return self::invalid();
				}
			} elseif ( ! self::prose_edit( $original, $replacement, $mode ) ) {
				return self::invalid();
			}
			$parts[ $index ] = 'cards' === $mode ? $replacement : htmlspecialchars( $replacement, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$seen[ $index ]  = true;
		}
		return implode( '', $parts );
	}

	/**
	 * Require exact reversibility and confirmed card/format pairs.
	 *
	 * @param string                                        $original Original words.
	 * @param string                                        $replacement Suggested shortcodes.
	 * @param list<array{tag:string,id:string,name:string}> $cards Confirmed catalog.
	 * @return bool
	 */
	private static function card_edit( string $original, string $replacement, array $cards ): bool {
		$allowed = array();
		foreach ( $cards as $card ) {
			$allowed[ $card['tag'] . ':' . $card['id'] ] = true;
		}
		$valid    = true;
		$reversed = preg_replace_callback(
			'~\[(hs_card|hs_bg) id="([A-Za-z0-9_]+)"\]([^<>\[\]\r\n]+)\[/\1\]~u',
			static function ( array $capture ) use ( $allowed, &$valid ): string {
				$valid = $valid && isset( $allowed[ $capture[1] . ':' . $capture[2] ] );
				return $capture[3];
			},
			$replacement
		);
		return $valid && $reversed === $original;
	}

	/**
	 * Preserve numbers and disallow HTML/shortcodes in generated prose.
	 *
	 * @param string $original Original words.
	 * @param string $replacement Candidate words.
	 * @param string $mode Editing depth.
	 * @return bool
	 */
	private static function prose_edit( string $original, string $replacement, string $mode ): bool {
		if ( '' === trim( $replacement ) || preg_match( '/[<>\[\]]/u', $replacement ) ) {
			return false;
		}
		preg_match_all( '/\d+(?:[.,\/]\d+)*%?/u', $original, $before );
		preg_match_all( '/\d+(?:[.,\/]\d+)*%?/u', $replacement, $after );
		if ( $before[0] !== $after[0] ) {
			return false;
		}
		return 'proofread' !== $mode || self::minor_words( $original, $replacement );
	}

	/**
	 * Proofreading keeps word order and permits only a single-letter typo.
	 *
	 * @param string $original Original words.
	 * @param string $replacement Candidate words.
	 * @return bool
	 */
	private static function minor_words( string $original, string $replacement ): bool {
		preg_match_all( '/[\p{L}\p{N}]+/u', $original, $before );
		preg_match_all( '/[\p{L}\p{N}]+/u', $replacement, $after );
		if ( count( $before[0] ) !== count( $after[0] ) ) {
			return false;
		}
		foreach ( $before[0] as $index => $word ) {
			if ( ! self::minor_word( mb_strtolower( $word ), mb_strtolower( $after[0][ $index ] ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * One Unicode letter inserted, removed, substituted or transposed.
	 *
	 * @param string $before Original token.
	 * @param string $after Corrected token.
	 * @return bool
	 */
	private static function minor_word( string $before, string $after ): bool {
		if ( $before === $after ) {
			return true;
		}
		$left  = mb_str_split( $before );
		$right = mb_str_split( $after );
		while ( $left && $right && $left[0] === $right[0] ) {
			array_shift( $left );
			array_shift( $right );
		}
		while ( $left && $right && end( $left ) === end( $right ) ) {
			array_pop( $left );
			array_pop( $right );
		}
		return 1 >= max( count( $left ), count( $right ) ) || ( 2 === count( $left ) && array_reverse( $right ) === $left );
	}

	/**
	 * Redacted integrity failure.
	 *
	 * @return \WP_Error
	 */
	private static function invalid(): \WP_Error {
		return new \WP_Error( 'editor_ai_unsafe', __( 'Предложение меняет защищённые данные или выходит за выбранный режим. Исходный текст сохранён.', 'manacost' ) );
	}
}
