<?php
/**
 * Cache layer of resolved card changes: entries, fetch locks and shape validation.
 *
 * @package HSManacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Transient-backed store. Values cross a trust boundary, so every read is re-validated.
 *
 * @phpstan-import-type Resolution from HS_Card_Changes_Pairs
 */
final class HS_Card_Changes_Cache {
	private const STORE_TTL     = 30 * DAY_IN_SECONDS;
	private const FRESH_SINGLE  = 10 * MINUTE_IN_SECONDS;
	private const FRESH_SETTLED = 12 * HOUR_IN_SECONDS;
	private const FRESH_NONE    = HOUR_IN_SECONDS;
	private const LOCK_TTL      = 60;

	/**
	 * Cache key of one resolution.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 */
	public static function key( string $mode, string $card, string $date ): string {
		return hash( 'sha256', $mode . '|' . $card . '|' . $date );
	}

	/**
	 * Cache entry of one resolution: the value plus whether it is still fresh.
	 *
	 * Every entry is kept for a long time and served stale while a background refresh runs;
	 * only a complete pair never goes stale.
	 *
	 * @param string $mode Card mode.
	 * @param string $card Card identifier.
	 * @param string $date Normalized UTC date.
	 * @phpstan-return array{resolution:Resolution,fresh:bool}|null
	 * @return array<string,mixed>|null
	 */
	public static function entry( string $mode, string $card, string $date ): ?array {
		$stored = get_transient( 'hs_cc_pair_' . self::key( $mode, $card, $date ) );
		if ( ! is_array( $stored ) || ! is_int( $stored['until'] ?? null ) ) {
			return null;
		}
		$resolution = self::valid( $stored['value'] ?? null );
		return null === $resolution ? null : array(
			'resolution' => $resolution,
			'fresh'      => $stored['until'] > time(),
		);
	}

	/**
	 * Cached values cross a trust boundary too; re-check the shape.
	 *
	 * @param mixed $value Cached candidate.
	 * @phpstan-return Resolution|null
	 * @return array<string,mixed>|null
	 */
	private static function valid( $value ): ?array {
		if ( ! is_array( $value ) || ! in_array( $value['state'] ?? '', array( 'pair', 'single', 'none' ), true ) || ! is_string( $value['name'] ?? null ) || ! is_array( $value['plates'] ?? null ) ) {
			return null;
		}
		$sides = array();
		foreach ( array( 'before', 'after' ) as $side ) {
			$row = $value[ $side ] ?? null;
			if ( null === $row ) {
				$sides[ $side ] = null;
				continue;
			}
			if ( ! is_array( $row ) || ! is_string( $row['hash'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $row['hash'] ) || ! is_string( $row['at'] ?? null ) ) {
				return null;
			}
			$sides[ $side ] = array(
				'hash' => $row['hash'],
				'at'   => $row['at'],
			);
		}
		$plates = array();
		foreach ( $value['plates'] as $plate ) {
			$clean = is_array( $plate ) ? self::plate( $plate ) : null;
			if ( null === $clean ) {
				return null;
			}
			$plates[] = $clean;
		}
		return array(
			'state'  => (string) $value['state'],
			'name'   => $value['name'],
			'before' => $sides['before'],
			'after'  => $sides['after'],
			'plates' => $plates,
		);
	}

	/**
	 * A cached plate with only string fields.
	 *
	 * @param array<mixed> $plate Cached plate.
	 * @return array{key:string,label:string,old:string,new:string,tone:string}|null
	 */
	private static function plate( array $plate ): ?array {
		foreach ( array( 'key', 'label', 'old', 'new', 'tone' ) as $field ) {
			if ( ! is_string( $plate[ $field ] ?? null ) ) {
				return null;
			}
		}
		return array(
			'key'   => $plate['key'],
			'label' => $plate['label'],
			'old'   => $plate['old'],
			'new'   => $plate['new'],
			'tone'  => $plate['tone'],
		);
	}

	/**
	 * Store a resolution. Only a complete pair is immutable; the rest is refreshed in the background once stale.
	 *
	 * @param array{0:string,1:string,2:string} $request Mode, card and date.
	 * @param array<string,mixed>               $resolution Resolution.
	 */
	public static function store( array $request, array $resolution ): void {
		$until = 'pair' === $resolution['state'] ? PHP_INT_MAX : time() + self::freshness( (string) $resolution['state'], $request[2] );
		set_transient(
			'hs_cc_pair_' . self::key( ...$request ),
			array(
				'value' => $resolution,
				'until' => $until,
			),
			self::STORE_TTL
		);
	}

	/**
	 * Seconds a pair-less result stays fresh. Days after the patch date the archive has caught up,
	 * so a missing pair is not worth re-asking every few minutes.
	 *
	 * @param string $state Resolution state.
	 * @param string $date Normalized UTC date.
	 */
	private static function freshness( string $state, string $date ): int {
		if ( 'none' === $state ) {
			return self::FRESH_NONE;
		}
		$at = strtotime( $date . ' UTC' );
		return false !== $at && $at < time() - 2 * DAY_IN_SECONDS ? self::FRESH_SETTLED : self::FRESH_SINGLE;
	}

	/**
	 * Result when the provider is down and nothing was cached: render plates without images.
	 *
	 * @phpstan-return Resolution
	 * @return array<string,mixed>
	 */
	public static function unavailable(): array {
		return array(
			'state'  => 'unavailable',
			'name'   => '',
			'before' => null,
			'after'  => null,
			'plates' => array(),
		);
	}
	/**
	 * Take the per-key fetch lock. Atomic with an external object cache, best effort otherwise.
	 *
	 * @param string $key Cache key of the resolution.
	 */
	public static function lock( string $key ): bool {
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_add( 'hs_cc_lock_' . $key, 1, 'hs_card_changes', self::LOCK_TTL );
		}
		if ( false !== get_transient( 'hs_cc_lock_' . $key ) ) {
			return false;
		}
		set_transient( 'hs_cc_lock_' . $key, 1, self::LOCK_TTL );
		return true;
	}

	/**
	 * Release a fetch lock.
	 *
	 * @param string $key Cache key of the resolution.
	 */
	public static function unlock( string $key ): void {
		if ( wp_using_ext_object_cache() ) {
			wp_cache_delete( 'hs_cc_lock_' . $key, 'hs_card_changes' );
			return;
		}
		delete_transient( 'hs_cc_lock_' . $key );
	}
}
