<?php
/**
 * Shortcodes and native markup for Hearthstone card balance changes.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Card item validation, markup and shortcode generation.
 *
 * @phpstan-type Item array{card:string,mode:string,date:string,name:string,before:string,after:string,plates:?list<array<string,string>>}
 * @phpstan-type Options array{columns:string,size:string,captions:string}
 */
final class HS_Card_Changes_Render {
	public const VERSION = '1.0.0';

	private const IMAGE_BASE = 'https://api.kolodahearthstone.com/uploads/card-history-media/';
	private const MAX_ITEMS  = 24;

	/**
	 * Content hashes (first 12 hex of SHA-256) of the shipped assets; a test keeps them in step with the files.
	 */
	private const ASSET_VERSIONS = array(
		'card-changes.css' => 'ce890deb9a4a',
		'editor.css'       => '6384378114f9',
		'editor.js'        => '098bc16da27d',
		'dialog-focus.js'  => '5785aaa87b14',
	);

	/**
	 * Changes rendered in the current post; every shortcode, nested or not, counts against one cap.
	 *
	 * @var int
	 */
	private static int $count = 0;

	/** Register the public shortcodes and the content-scoped assets. */
	public static function register(): void {
		add_shortcode( 'hs_card_changes', array( self::class, 'container' ) );
		add_shortcode( 'hs_card_change', array( self::class, 'single' ) );
		add_filter( 'the_content', array( self::class, 'begin_content' ), 8 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/** Load the stylesheet only on singular documents that use the shortcodes. */
	public static function enqueue(): void {
		$post = get_queried_object();
		if ( is_singular() && $post instanceof WP_Post && str_contains( $post->post_content, '[hs_card_change' ) ) {
			self::assets();
		}
	}

	/** Enqueue the public stylesheet; also used by the editor dialog preview. */
	public static function assets(): void {
		wp_enqueue_style( 'hs-card-changes', plugins_url( 'card-changes.css', __FILE__ ), array(), self::asset_version( 'card-changes.css' ) );
	}

	/**
	 * Cache-busting version of a shipped asset: its content hash, not the plugin version.
	 *
	 * @param string $asset Asset basename.
	 */
	public static function asset_version( string $asset ): string {
		return self::ASSET_VERSIONS[ $asset ] ?? 'missing';
	}

	/**
	 * A new content pass starts counting changes from zero.
	 *
	 * @param string $content Post content.
	 */
	public static function begin_content( string $content ): string {
		self::$count = 0;
		return $content;
	}

	/** One change may be rendered while the per-post cap has not been reached. */
	private static function claim(): bool {
		return ++self::$count <= self::MAX_ITEMS;
	}

	/**
	 * Provider refresh requests of the unpinned changes of a post, for the background warm-up.
	 *
	 * @param string $content Post content.
	 * @param int    $post_id Post ID, so default dates match the page render.
	 * @return list<array{0:string,1:string,2:string}>
	 */
	public static function requests( string $content, int $post_id ): array {
		$requests = array();
		foreach ( array_slice( self::children( $content ), 0, self::MAX_ITEMS ) as $atts ) {
			$item = self::item( $atts, $post_id );
			if ( null !== $item && ! self::pinned( $item ) ) {
				$requests[] = array( $item['mode'], $item['card'], $item['date'] );
			}
		}
		return $requests;
	}

	/**
	 * Where a cache miss may schedule its background refresh: only the main query of a singular view.
	 * Feeds, REST, archives, widgets and previews of other queries only read the cache.
	 */
	private static function queue_target(): ?int {
		if ( is_main_query() && is_singular() && in_the_loop() && ! is_feed() && ! wp_is_json_request() ) {
			return (int) get_queried_object_id();
		}
		return null;
	}

	/**
	 * Container: `[hs_card_changes columns="2" size="m" captions="no"]` with nested changes.
	 *
	 * @param array<string,string>|string $atts Container attributes.
	 * @param string|null                 $content Enclosed content; only nested change shortcodes are read.
	 */
	public static function container( $atts, $content = '' ): string {
		$atts  = is_array( $atts ) ? $atts : array();
		$items = array();
		foreach ( self::children( (string) $content ) as $child ) {
			$item = self::item( $child );
			if ( null !== $item && self::claim() ) {
				$items[] = $item;
			}
		}
		return self::html( $items, self::options( $atts ) );
	}

	/**
	 * Single change: `[hs_card_change card="BE_036" date="2026-11-03"]`.
	 *
	 * @param array<string,string>|string $atts Attributes.
	 */
	public static function single( $atts ): string {
		$atts            = is_array( $atts ) ? $atts : array();
		$item            = self::item( $atts );
		$opts            = self::options( $atts );
		$opts['columns'] = '1';
		return null === $item || ! self::claim() ? '' : self::html( array( $item ), $opts );
	}

	/**
	 * Attributes of every nested change shortcode, ignoring editor wrapper markup.
	 *
	 * @param string $content Enclosed or full post content.
	 * @return list<array<string,string>>
	 */
	private static function children( string $content ): array {
		$found = array();
		if ( preg_match_all( '/' . get_shortcode_regex( array( 'hs_card_change' ) ) . '/', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$parsed = '[' === $match[1] ? '' : shortcode_parse_atts( $match[3] );
				if ( is_array( $parsed ) ) {
					$found[] = array_map( 'strval', $parsed );
				}
			}
		}
		return $found;
	}

	/**
	 * Closed presentation options.
	 *
	 * @param array<string,string> $atts Raw attributes.
	 * @phpstan-return Options
	 * @return array<string,string>
	 */
	public static function options( array $atts ): array {
		$columns  = $atts['columns'] ?? '2';
		$size     = $atts['size'] ?? 'm';
		$captions = strtolower( $atts['captions'] ?? 'no' );
		return array(
			'columns'  => in_array( $columns, array( '1', '2', '3' ), true ) ? $columns : '2',
			'size'     => in_array( $size, array( 's', 'm', 'l' ), true ) ? $size : 'm',
			'captions' => in_array( $captions, array( 'yes', 'true', '1' ), true ) ? 'yes' : 'no',
		);
	}

	/**
	 * Validate one change. Returns null when the card identifier is unusable.
	 *
	 * @param array<string,string> $atts Raw attributes.
	 * @param int|null             $post_id Post the default date comes from; the current post when null.
	 * @phpstan-return Item|null
	 * @return array<string,mixed>|null
	 */
	public static function item( array $atts, ?int $post_id = null ): ?array {
		$card = trim( $atts['card'] ?? '' );
		if ( ! HS_Card_Changes_Pairs::valid_card( $card ) ) {
			return null;
		}
		$mode = $atts['mode'] ?? '';
		$mode = in_array( $mode, HS_Card_Changes_Pairs::modes(), true ) ? $mode : HS_Card_Changes_Pairs::guess_mode( $card );
		$date = HS_Card_Changes_Pairs::normalize_date( $atts['date'] ?? '' );
		return array(
			'card'   => $card,
			'mode'   => $mode,
			'date'   => '' !== $date ? $date : self::default_date( $post_id ?? (int) get_the_ID() ),
			'name'   => HS_Card_Changes_Pairs::clean_name( $atts['name'] ?? '' ),
			'before' => self::hash( $atts['before'] ?? '' ),
			'after'  => self::hash( $atts['after'] ?? '' ),
			'plates' => HS_Card_Changes_Pairs::parse_plates( $atts['plates'] ?? '', $mode ),
		);
	}

	/**
	 * Public URL of an immutable archive image.
	 *
	 * @param string $hash Validated archive hash.
	 */
	public static function image_url( string $hash ): string {
		return self::IMAGE_BASE . $hash . '.png';
	}

	/**
	 * Validate a change composed in the editor dialog. Nothing from the browser is trusted as markup.
	 *
	 * @param array<mixed> $row Browser fields.
	 * @phpstan-return Item|null
	 * @return array<string,mixed>|null
	 */
	public static function item_from_editor( array $row ): ?array {
		$text = static fn( string $field ): string => is_string( $row[ $field ] ?? null ) ? $row[ $field ] : '';
		$item = self::item(
			array(
				'card'   => $text( 'card' ),
				'mode'   => $text( 'mode' ),
				'date'   => $text( 'date' ),
				'name'   => $text( 'name' ),
				'before' => $text( 'before' ),
				'after'  => $text( 'after' ),
			)
		);
		if ( null === $item || '' === HS_Card_Changes_Pairs::normalize_date( $text( 'date' ) ) ) {
			return null;
		}
		$plates = array();
		foreach ( is_array( $row['plates'] ?? null ) ? array_slice( $row['plates'], 0, 8 ) : array() as $raw ) {
			$plate = is_array( $raw ) ? HS_Card_Changes_Pairs::plate_from_editor( $raw, $item['mode'] ) : null;
			if ( null === $plate ) {
				return null;
			}
			$plates[] = $plate;
		}
		$item['plates'] = $plates;
		return $item;
	}

	/**
	 * Only immutable archive hashes are accepted as image references.
	 *
	 * @param string $value Candidate hash.
	 */
	private static function hash( string $value ): string {
		return 1 === preg_match( '/^[a-f0-9]{64}$/D', $value ) ? $value : '';
	}

	/**
	 * A change whose images and plates are stored in the shortcode needs no provider.
	 *
	 * @param array<string,mixed> $item Validated item.
	 */
	private static function pinned( array $item ): bool {
		return '' !== $item['after'] && null !== $item['plates'];
	}

	/**
	 * UTC publication moment of the article. Drafts have none: the start of the current UTC day is a
	 * stable fallback, so the cache key repeats instead of changing every second.
	 *
	 * @param int $post_id Post ID or 0.
	 */
	private static function default_date( int $post_id ): string {
		$date = $post_id > 0 ? get_post_field( 'post_date_gmt', $post_id ) : '';
		$norm = HS_Card_Changes_Pairs::normalize_date( is_string( $date ) ? $date : '' );
		return '' !== $norm ? $norm : gmdate( 'Y-m-d 00:00:00' );
	}

	/**
	 * Complete block markup.
	 *
	 * @param list<array<string,mixed>> $items Validated items.
	 * @param array<string,string>      $options Closed presentation options.
	 * @param bool                      $pinned_only Never consult the provider cache: render only what the items carry.
	 * @phpstan-param list<Item> $items
	 * @phpstan-param Options $options
	 */
	public static function html( array $items, array $options, bool $pinned_only = false ): string {
		if ( ! $items ) {
			return '';
		}
		self::assets();
		$classes = 'hs-cc hs-cc--cols-' . $options['columns'] . ' hs-cc--size-' . $options['size'] . ( 'yes' === $options['captions'] ? ' hs-cc--captions' : '' );
		$html    = '';
		foreach ( $items as $item ) {
			$html .= self::item_html( $item, $options['captions'], $pinned_only );
		}
		return '<div class="' . esc_attr( $classes ) . '">' . $html . '</div>';
	}

	/**
	 * One change: card before, arrow with plates, card after.
	 *
	 * @param array<string,mixed> $item Validated item.
	 * @param string              $captions "yes" to label the cards.
	 * @param bool                $pinned_only Do not consult the provider cache.
	 * @phpstan-param Item $item
	 */
	private static function item_html( array $item, string $captions, bool $pinned_only ): string {
		$resolved = self::resolved( $item, $pinned_only );
		$name     = '' !== $resolved['name'] ? $resolved['name'] : $item['card'];
		$plates   = '';
		foreach ( $resolved['plates'] as $plate ) {
			$plates .= self::plate_html( $plate );
		}
		if ( null === $resolved['after'] ) {
			return '<figure class="hs-cc__item hs-cc__item--text"><span class="hs-cc__name">' . esc_html( $name ) . '</span>' . ( '' !== $plates ? '<span class="hs-cc__mid hs-cc__mid--row">' . $plates . '</span>' : '' ) . '</figure>';
		}
		$mode   = HS_Card_Changes_Pairs::mode( $item['mode'] );
		$pair   = null !== $resolved['before'];
		$label  = $name . ( '' !== self::summary( $resolved['plates'] ) ? ': ' . self::summary( $resolved['plates'] ) : '' ) . '. ' . ( $pair ? 'Открыть карты до и после изменения' : 'Открыть карту' );
		$middle = '<span class="hs-cc__mid' . ( $pair ? '' : ' hs-cc__mid--row' ) . '">' . ( $pair ? self::arrow() : '' ) . $plates . '</span>';
		$cards  = $pair
			? self::card( $resolved['before'], $name . ' до изменения', 'До', $mode, $captions ) . $middle . self::card( $resolved['after'], $name . ' после изменения', 'После', $mode, $captions )
			: self::card( $resolved['after'], $name, '', $mode, '' ) . $middle;
		return '<figure class="hs-cc__item' . ( $pair ? '' : ' hs-cc__item--single' ) . '"><a class="hs-cc__open" href="' . esc_url( self::image_url( $resolved['after'] ) ) . '" data-hs-lightbox-pair data-hs-lightbox-title="' . esc_attr( $name ) . '" aria-haspopup="dialog" aria-label="' . esc_attr( $label ) . '">' . $cards . '</a></figure>';
	}

	/**
	 * Images from the shortcode when pinned, otherwise from the cached provider resolution (never the network).
	 *
	 * @param array<string,mixed> $item Validated item.
	 * @param bool                $pinned_only Skip the cache and show plates only for unpinned items.
	 * @phpstan-param Item $item
	 * @return array{name:string,before:?string,after:?string,plates:list<array<string,string>>}
	 */
	private static function resolved( array $item, bool $pinned_only ): array {
		if ( self::pinned( $item ) ) {
			return array(
				'name'   => $item['name'],
				'before' => '' !== $item['before'] ? $item['before'] : null,
				'after'  => $item['after'],
				'plates' => $item['plates'],
			);
		}
		$found = $pinned_only ? HS_Card_Changes_Cache::unavailable() : HS_Card_Changes_Source::lookup( $item['mode'], $item['card'], $item['date'], self::queue_target() );
		return array(
			'name'   => '' !== $item['name'] ? $item['name'] : $found['name'],
			'before' => $found['before']['hash'] ?? null,
			'after'  => $found['after']['hash'] ?? null,
			'plates' => $item['plates'] ?? $found['plates'],
		);
	}

	/**
	 * One card image on the transparent canvas.
	 *
	 * @param string                                                             $hash Archive hash.
	 * @param string                                                             $alt Meaningful alternative text.
	 * @param string                                                             $caption Visible caption text.
	 * @param array{entity:string,media:string,name:string,width:int,height:int} $mode Mode geometry.
	 * @param string                                                             $captions "yes" to show captions.
	 */
	private static function card( string $hash, string $alt, string $caption, array $mode, string $captions ): string {
		$cap = 'yes' === $captions && '' !== $caption ? '<span class="hs-cc__cap" aria-hidden="true">' . esc_html( $caption ) . '</span>' : '';
		return '<span class="hs-cc__side">' . $cap . '<img class="hs-cc__card" src="' . esc_url( self::image_url( $hash ) ) . '" alt="' . esc_attr( $alt ) . '" width="' . (int) $mode['width'] . '" height="' . (int) $mode['height'] . '" loading="lazy" decoding="async"' . ( '' !== $caption ? ' data-hs-label="' . esc_attr( $caption ) . '"' : '' ) . ' /></span>';
	}

	/** Decorative arrow between the cards. */
	private static function arrow(): string {
		return '<svg class="hs-cc__arrow" viewBox="0 0 34 20" aria-hidden="true" focusable="false"><path d="M2 10h27M21 3l8 7-8 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}

	/**
	 * A plate: label with the struck-through old value and the new one.
	 *
	 * @param array<string,string> $plate Validated plate.
	 */
	private static function plate_html( array $plate ): string {
		$value = '';
		if ( '' !== $plate['old'] ) {
			$value = '<span class="hs-cc__val"><span class="hs-cc__sr">было </span><s>' . esc_html( $plate['old'] ) . '</s> <span aria-hidden="true">→</span> <span class="hs-cc__sr">стало </span><b>' . esc_html( $plate['new'] ) . '</b></span>';
		}
		$tone = in_array( $plate['tone'], HS_Card_Changes_Pairs::TONES, true ) ? $plate['tone'] : 'neutral';
		return '<span class="hs-cc__plate hs-cc__plate--' . esc_attr( $tone ) . ( '' === $value ? ' hs-cc__plate--text' : '' ) . '"><span class="hs-cc__label">' . esc_html( $plate['label'] ) . '</span>' . $value . '</span>';
	}

	/**
	 * Spoken summary of plates for the link's accessible name.
	 *
	 * @param list<array<string,string>> $plates Plates.
	 */
	private static function summary( array $plates ): string {
		$parts = array();
		foreach ( $plates as $plate ) {
			$parts[] = '' !== $plate['old'] ? $plate['label'] . ' ' . $plate['old'] . ' → ' . $plate['new'] : $plate['label'] . ' изменён';
		}
		return implode( ', ', $parts );
	}

	/**
	 * Canonical shortcode text for validated items; never contains browser-supplied markup.
	 *
	 * @param list<array<string,mixed>> $items Validated items.
	 * @param array<string,string>      $options Closed presentation options.
	 * @phpstan-param list<Item> $items
	 * @phpstan-param Options $options
	 */
	public static function build_shortcode( array $items, array $options ): string {
		// Defaults stay implicit so the text editors see is short.
		$shared = 'yes' === $options['captions'] ? array( 'captions' => 'yes' ) : array();
		$lines  = array();
		foreach ( $items as $item ) {
			$attrs   = array(
				'card'   => $item['card'],
				'mode'   => $item['mode'],
				'name'   => $item['name'],
				'date'   => substr( $item['date'], 0, 16 ),
				'before' => $item['before'],
				'after'  => $item['after'],
				'plates' => HS_Card_Changes_Pairs::serialize_plates( $item['plates'] ?? array() ),
			);
			$lines[] = '[hs_card_change' . self::attrs( $attrs + ( 1 === count( $items ) ? array( 'size' => $options['size'] ) + $shared : array() ) ) . ']';
		}
		if ( 1 === count( $lines ) ) {
			return $lines[0];
		}
		return '[hs_card_changes' . self::attrs(
			array(
				'columns' => $options['columns'],
				'size'    => $options['size'],
			) + $shared
		) . "]\n" . implode( "\n", $lines ) . "\n[/hs_card_changes]";
	}

	/**
	 * Serialize attributes; validated values never contain quotes or brackets.
	 *
	 * @param array<string,string> $attrs Attribute map.
	 */
	private static function attrs( array $attrs ): string {
		$out = '';
		foreach ( $attrs as $key => $value ) {
			if ( '' !== $value ) {
				$out .= ' ' . $key . '="' . str_replace( array( '"', '[', ']' ), '', $value ) . '"';
			}
		}
		return $out;
	}
}
