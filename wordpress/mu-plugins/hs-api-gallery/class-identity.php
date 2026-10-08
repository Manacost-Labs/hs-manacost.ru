<?php
/**
 * Resolve a minimal voter identity.
 *
 * @package Manacost
 */

namespace Manacost\ApiGallery;

defined( 'ABSPATH' ) || exit;

/** Resolve a minimal voter identity. */
final class Identity {
	private const COOKIE = 'hs_gallery_voter';

	/**
	 * Resolve a minimal voter identity.
	 *
	 * @param bool $create Create only following an explicit vote.
	 *
	 * @return string Pseudonymous storage key or empty string.
	 */
	public static function key( bool $create = false ): string {
		if ( is_user_logged_in() ) {
			return self::user_key( get_current_user_id() );
		}
		$cookie = isset( $_COOKIE[ self::COOKIE ] ) && is_string( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		$parts  = explode( '.', $cookie );
		$token  = $parts[0];
		$valid  = preg_match( '/^[a-f0-9]{32}$/D', $token ) && hash_equals( self::signature( $token ), $parts[1] ?? '' );
		if ( ! $valid && ! $create ) {
			return '';
		}
		if ( ! $valid ) {
			$token = bin2hex( random_bytes( 16 ) );
			setcookie(
				self::COOKIE,
				$token . '.' . self::signature( $token ),
				array(
					'expires'  => time() + YEAR_IN_SECONDS,
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				),
			);
		}
		return self::signature( 'anonymous:' . $token );
	}

	/**
	 * Derive a pseudonym for a WordPress account.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string Storage key.
	 */
	public static function user_key( int $user_id ): string {
		return self::signature( 'user:' . $user_id );
	}

	/**
	 * Sign an opaque identity.
	 *
	 * @param string $value Pseudonym seed.
	 * @return string HMAC.
	 */
	private static function signature( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	/**
	 * Verify browser origin explicitly as well as the action nonce.
	 *
	 * @return bool Same-origin request.
	 */
	public static function same_origin(): bool {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) && is_string( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$host   = isset( $_SERVER['HTTP_HOST'] ) && is_string( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$parts  = wp_parse_url( $origin );
		if ( ! is_array( $parts ) || ! isset( $parts['host'], $parts['scheme'] ) ) {
			return false;
		}
		$expected = $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		return hash_equals( $host, $expected ) && ( is_ssl() ? 'https' : 'http' ) === $parts['scheme'];
	}
}
