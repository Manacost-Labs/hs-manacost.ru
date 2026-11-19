<?php
/**
 * Pure card-history rules: before/after pair selection and change plates.
 *
 * No WordPress, HTTP or cache access lives here, so every rule is testable
 * against recorded provider responses.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Closed vocabulary and selection rules for card changes.
 *
 * @phpstan-type Plate array{key:string,label:string,old:string,new:string,tone:string}
 * @phpstan-type Side array{hash:string,at:string}
 * @phpstan-type Resolution array{state:string,name:string,before:?Side,after:?Side,plates:list<Plate>}
 */
final class HS_Card_Changes_Pairs {
	/** Provider rendering of a card; both formats are fixed by the history API. */
	private const MODES = array(
		'constructed' => array(
			'entity' => 'constructed_card',
			'media'  => 'image_url',
			'name'   => 'name_ru',
			'width'  => 404,
			'height' => 558,
		),
		'bg'          => array(
			'entity' => 'card',
			'media'  => 'card_image',
			'name'   => 'name',
			'width'  => 256,
			'height' => 388,
		),
	);

	/** Plate key => snapshot field, label, mode scope and which direction is better for the player. */
	private const STATS = array(
		'mana'       => array( 'mana_cost', 'Мана', 'constructed', 'lower' ),
		'attack'     => array( 'attack', 'Атака', 'both', 'higher' ),
		'health'     => array( 'health', 'Здоровье', 'both', 'higher' ),
		'durability' => array( 'durability', 'Прочность', 'constructed', 'higher' ),
		'armor'      => array( 'armor', 'Броня', 'constructed', 'higher' ),
		'tier'       => array( 'tavern_tier', 'Уровень', 'bg', '' ),
	);

	/** Value of a plate: short and free of syntax characters of the shortcode grammar. */
	public const VALUE_PATTERN = '/^[0-9A-Za-zА-Яа-яЁё+.,% -]{1,14}$/u';

	/** Free plate label. */
	public const LABEL_PATTERN = '/^[\p{L}\p{N} .,+\/%!?()-]{1,30}$/u';

	/** Allowed explicit tones. */
	public const TONES = array( 'good', 'bad', 'neutral' );

	/**
	 * Closed list of card modes.
	 *
	 * @return list<string>
	 */
	public static function modes(): array {
		return array_keys( self::MODES );
	}

	/**
	 * Provider description of a mode.
	 *
	 * @param string $mode Card mode.
	 * @return array{entity:string,media:string,name:string,width:int,height:int}
	 */
	public static function mode( string $mode ): array {
		return self::MODES[ $mode ] ?? self::MODES['constructed'];
	}

	/**
	 * Battlegrounds cards use their own identifier families; everything else is Constructed.
	 *
	 * @param string $card Card identifier.
	 */
	public static function guess_mode( string $card ): string {
		return 1 === preg_match( '/^(?:BG\d|BGS_|TB_Bacon|BaconShop)/', $card ) ? 'bg' : 'constructed';
	}

	/**
	 * Validate a card identifier.
	 *
	 * @param string $card Candidate.
	 */
	public static function valid_card( string $card ): bool {
		return 1 === preg_match( '/^[A-Za-z][A-Za-z0-9_]{1,119}$/D', $card );
	}

	/**
	 * Normalize a UTC date or date-time to "Y-m-d H:i:s", or return an empty string.
	 *
	 * @param string $input "YYYY-MM-DD", "YYYY-MM-DD HH:MM" or the datetime-local "T" form.
	 */
	public static function normalize_date( string $input ): string {
		$input = trim( $input );
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/D', $input, $m ) ) {
			return '';
		}
		$hour   = (int) ( $m[4] ?? 0 );
		$minute = (int) ( $m[5] ?? 0 );
		$second = (int) ( $m[6] ?? 0 );
		if ( ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) || $hour > 23 || $minute > 59 || $second > 59 ) {
			return '';
		}
		return sprintf( '%s-%s-%s %02d:%02d:%02d', $m[1], $m[2], $m[3], $hour, $minute, $second );
	}

	/**
	 * Archive path of the Russian render, or an empty string while it is not archived.
	 *
	 * @param mixed  $version Provider version row.
	 * @param string $mode Card mode.
	 * @return string SHA-256 of the immutable image.
	 */
	public static function image_hash( $version, string $mode ): string {
		$media = is_array( $version ) && is_array( $version['media'] ?? null ) ? ( $version['media'][ self::MODES[ $mode ]['media'] ] ?? null ) : null;
		if ( ! is_array( $media ) || 'archived' !== ( $media['status'] ?? '' ) || ! is_string( $media['archive_url'] ?? null ) ) {
			return '';
		}
		return 1 === preg_match( '#^/uploads/card-history-media/([a-f0-9]{64})\.png$#D', $media['archive_url'], $m ) ? $m[1] : '';
	}

	/**
	 * Select the before/after pair for a patch date.
	 *
	 * Before is the newest archived version observed before the date. After is the
	 * oldest archived version at or after it whose image differs from before. When
	 * there is no pair the current (newest archived) card is returned alone.
	 *
	 * @param array<int,mixed> $versions Provider versions, newest first.
	 * @param string           $mode Card mode.
	 * @param string           $date Normalized UTC date.
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	public static function pick( array $versions, string $mode, string $date ): array {
		$usable = self::usable( $versions, $mode );
		$before = null;
		foreach ( $usable as $row ) {
			if ( $row['at'] < $date ) {
				$before = $row;
				break;
			}
		}
		$after = null;
		foreach ( array_reverse( $usable ) as $row ) {
			if ( $row['at'] >= $date && null !== $before && $row['hash'] !== $before['hash'] ) {
				$after = $row;
				break;
			}
		}
		$newest = $usable[0] ?? null;
		$name   = self::name( $newest['snapshot'] ?? array(), $mode );
		if ( null !== $before && null !== $after ) {
			return array(
				'state'  => 'pair',
				'name'   => $name,
				'before' => self::side( $before ),
				'after'  => self::side( $after ),
				'plates' => self::plates( $before['snapshot'], $after['snapshot'], $mode ),
			);
		}
		return array(
			'state'  => null === $newest ? 'none' : 'single',
			'name'   => $name,
			'before' => null,
			'after'  => null === $newest ? null : self::side( $newest ),
			'plates' => array(),
		);
	}

	/**
	 * Versions with an archived image and a readable observation time, newest first.
	 *
	 * The provider lists newest first, but the order is never assumed: rows are sorted explicitly,
	 * keeping the provider's order for equal moments.
	 *
	 * @param array<int,mixed> $versions Provider versions.
	 * @param string           $mode Card mode.
	 * @return list<array{hash:string,at:string,index:int,snapshot:array<mixed>}>
	 */
	private static function usable( array $versions, string $mode ): array {
		$usable = array();
		foreach ( $versions as $index => $version ) {
			$hash = self::image_hash( $version, $mode );
			$at   = is_array( $version ) ? self::observed( $version['observed_at'] ?? null ) : '';
			if ( '' !== $hash && '' !== $at && is_array( $version ) ) {
				$usable[] = array(
					'hash'     => $hash,
					'at'       => $at,
					'index'    => $index,
					'snapshot' => is_array( $version['snapshot'] ?? null ) ? $version['snapshot'] : array(),
				);
			}
		}
		usort(
			$usable,
			static fn( array $a, array $b ): int => array( $b['at'], $a['index'] ) <=> array( $a['at'], $b['index'] )
		);
		return $usable;
	}

	/**
	 * Normalize a provider timestamp to UTC "Y-m-d H:i:s", or an empty string.
	 *
	 * Accepts "YYYY-MM-DD HH:MM:SS(.ffffff)", the "T" form, "Z" and numeric offsets; values without a zone are UTC.
	 *
	 * @param mixed $value Provider observed_at.
	 */
	public static function observed( $value ): string {
		if ( ! is_string( $value ) || 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2}(?:\.\d{1,9})?)?(?:Z|[+-]\d{2}:?\d{2})?$/D', $value ) ) {
			return '';
		}
		try {
			$utc = new DateTimeZone( 'UTC' );
			return ( new DateTimeImmutable( $value, $utc ) )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $error ) {
			return '';
		}
	}

	/**
	 * Strip an internal row to the cached side shape.
	 *
	 * @param array<string,mixed> $row Usable version.
	 * @return array{hash:string,at:string}
	 */
	private static function side( array $row ): array {
		return array(
			'hash' => (string) $row['hash'],
			'at'   => (string) $row['at'],
		);
	}

	/**
	 * Russian card name from a snapshot.
	 *
	 * @param array<mixed> $snapshot Provider snapshot.
	 * @param string       $mode Card mode.
	 */
	private static function name( array $snapshot, string $mode ): string {
		$name = $snapshot[ self::MODES[ $mode ]['name'] ] ?? '';
		return is_string( $name ) ? self::clean_name( $name ) : '';
	}

	/**
	 * Names are plain text of bounded length.
	 *
	 * @param string $name Candidate display name.
	 */
	public static function clean_name( string $name ): string {
		$name = trim( (string) preg_replace( '/[^\p{L}\p{N} .,\'!?:()\/+-]+/u', '', $name ) );
		return mb_substr( $name, 0, 80 );
	}

	/**
	 * Plates from the difference of two snapshots.
	 *
	 * @param array<mixed> $before Snapshot before.
	 * @param array<mixed> $after Snapshot after.
	 * @param string       $mode Card mode.
	 * @phpstan-return list<Plate>
	 * @return array<int,array<string,string>>
	 */
	public static function plates( array $before, array $after, string $mode ): array {
		$plates = array();
		foreach ( self::STATS as $key => $stat ) {
			if ( 'both' !== $stat[2] && $stat[2] !== $mode ) {
				continue;
			}
			$old = $before[ $stat[0] ] ?? null;
			$now = $after[ $stat[0] ] ?? null;
			if ( ! is_int( $old ) || ! is_int( $now ) || $old === $now ) {
				continue;
			}
			$plates[] = array(
				'key'   => $key,
				'label' => $stat[1],
				'old'   => (string) $old,
				'new'   => (string) $now,
				'tone'  => self::tone( $stat[3], $old, $now ),
			);
		}
		$old_text = $before['text_ru'] ?? null;
		$new_text = $after['text_ru'] ?? null;
		if ( 'constructed' === $mode && is_string( $old_text ) && is_string( $new_text ) && self::squash( $old_text ) !== self::squash( $new_text ) ) {
			$plates[] = array(
				'key'   => 'text',
				'label' => 'Текст',
				'old'   => '',
				'new'   => '',
				'tone'  => 'neutral',
			);
		}
		return $plates;
	}

	/**
	 * Whitespace differences are not card text changes.
	 *
	 * @param string $text Card text.
	 */
	private static function squash( string $text ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Direction relative to the player: green when better, red when worse.
	 *
	 * @param string $better "lower", "higher" or empty for neutral stats.
	 * @param int    $old Old value.
	 * @param int    $now New value.
	 */
	private static function tone( string $better, int $old, int $now ): string {
		if ( '' === $better ) {
			return 'neutral';
		}
		return ( $now > $old ) === ( 'higher' === $better ) ? 'good' : 'bad';
	}

	/**
	 * Label of a built-in plate key.
	 *
	 * @param string $key Plate key.
	 */
	public static function label_for( string $key ): string {
		return 'text' === $key ? 'Текст' : ( self::STATS[ $key ][1] ?? '' );
	}

	/**
	 * Built-in plate keys available for a mode, with their labels.
	 *
	 * @param string $mode Card mode.
	 * @return array<string,string>
	 */
	public static function keys_for( string $mode ): array {
		$keys = array();
		foreach ( self::STATS as $key => $stat ) {
			if ( 'both' === $stat[2] || $stat[2] === $mode ) {
				$keys[ $key ] = $stat[1];
			}
		}
		if ( 'constructed' === $mode ) {
			$keys['text'] = 'Текст';
		}
		return $keys;
	}

	/**
	 * Parse the shortcode `plates` grammar: `mana:9:10;attack:5:6|bad;text;~Своя метка:a:b`.
	 *
	 * Returns null for the "automatic" case so the caller can compute plates from history;
	 * an empty list means the editor removed every plate.
	 *
	 * @param string $raw Attribute value; "none" removes all plates.
	 * @param string $mode Card mode.
	 * @phpstan-return list<Plate>|null
	 * @return array<int,array<string,string>>|null
	 */
	public static function parse_plates( string $raw, string $mode ): ?array {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return null;
		}
		$plates = array();
		foreach ( 'none' === $raw ? array() : array_slice( explode( ';', $raw ), 0, 8 ) as $token ) {
			$plate = self::parse_token( trim( $token ), $mode );
			if ( null !== $plate ) {
				$plates[] = $plate;
			}
		}
		return $plates;
	}

	/**
	 * Parse one plate token; invalid tokens are dropped, never repaired.
	 *
	 * @param string $token Plate token.
	 * @param string $mode Card mode.
	 * @phpstan-return Plate|null
	 * @return array<string,string>|null
	 */
	private static function parse_token( string $token, string $mode ): ?array {
		$parts  = explode( '|', $token, 2 );
		$head   = explode( ':', $parts[0], 3 );
		$name   = trim( $head[0] );
		$custom = str_starts_with( $name, '~' );
		return self::make_plate(
			$custom ? 'custom' : $name,
			$custom ? trim( substr( $name, 1 ) ) : '',
			trim( $head[1] ?? '' ),
			trim( $head[2] ?? '' ),
			trim( $parts[1] ?? '' ),
			$mode
		);
	}

	/**
	 * Validate the plate an editor composed in the dialog.
	 *
	 * @param array<mixed> $raw Browser fields: key, label, old, new, tone.
	 * @param string       $mode Card mode.
	 * @phpstan-return Plate|null
	 * @return array<string,string>|null
	 */
	public static function plate_from_editor( array $raw, string $mode ): ?array {
		$text = static fn( string $field ): string => is_string( $raw[ $field ] ?? null ) ? trim( $raw[ $field ] ) : '';
		$tone = $text( 'tone' );
		return self::make_plate( $text( 'key' ), $text( 'label' ), $text( 'old' ), $text( 'new' ), 'auto' === $tone ? '' : $tone, $mode );
	}

	/**
	 * The single validation point of a plate, shared by the shortcode and the editor.
	 *
	 * @param string $key Built-in key or "custom".
	 * @param string $label Free label (custom plates only).
	 * @param string $old Old value.
	 * @param string $now New value.
	 * @param string $tone Explicit tone or empty for automatic.
	 * @param string $mode Card mode.
	 * @phpstan-return Plate|null
	 * @return array<string,string>|null
	 */
	private static function make_plate( string $key, string $label, string $old, string $now, string $tone, string $mode ): ?array {
		$custom = 'custom' === $key;
		$label  = $custom ? $label : ( isset( self::keys_for( $mode )[ $key ] ) ? self::label_for( $key ) : '' );
		if ( '' === $label || ( $custom && 1 !== preg_match( self::LABEL_PATTERN, $label ) ) || ( '' !== $tone && ! in_array( $tone, self::TONES, true ) ) ) {
			return null;
		}
		foreach ( array( $old, $now ) as $value ) {
			if ( '' !== $value && 1 !== preg_match( self::VALUE_PATTERN, $value ) ) {
				return null;
			}
		}
		if ( ( '' === $old ) !== ( '' === $now ) ) {
			return null;
		}
		return array(
			'key'   => $key,
			'label' => $label,
			'old'   => $old,
			'new'   => $now,
			'tone'  => '' !== $tone ? $tone : self::auto_tone( $key, $old, $now ),
		);
	}

	/**
	 * Automatic tone of a hand-written plate: only numeric built-in stats have one.
	 *
	 * @param string $key Plate key.
	 * @param string $old Old value.
	 * @param string $now New value.
	 */
	private static function auto_tone( string $key, string $old, string $now ): string {
		if ( ! isset( self::STATS[ $key ] ) || ! ctype_digit( $old ) || ! ctype_digit( $now ) ) {
			return 'neutral';
		}
		return self::tone( self::STATS[ $key ][3], (int) $old, (int) $now );
	}

	/**
	 * Serialize plates for the `plates` attribute.
	 *
	 * @param array<int,array<string,string>> $plates Validated plates.
	 */
	public static function serialize_plates( array $plates ): string {
		if ( ! $plates ) {
			return 'none';
		}
		$tokens = array();
		foreach ( $plates as $plate ) {
			$name  = 'custom' === $plate['key'] ? '~' . $plate['label'] : $plate['key'];
			$token = $name . ( '' !== $plate['old'] ? ':' . $plate['old'] . ':' . $plate['new'] : '' );
			$auto  = self::auto_tone( 'custom' === $plate['key'] ? '' : $plate['key'], $plate['old'], $plate['new'] );
			if ( $auto !== $plate['tone'] ) {
				$token .= '|' . $plate['tone'];
			}
			$tokens[] = $token;
		}
		return implode( ';', $tokens );
	}
}
