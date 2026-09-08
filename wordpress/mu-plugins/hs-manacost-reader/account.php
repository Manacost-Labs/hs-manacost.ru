<?php
/**
 * Cache-safe account shell; loader owns hooks and no WP identity is read.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render anonymous public markup only.
 *
 * @param array<string, mixed> $config Public shortcode attributes.
 */
function hs_manacost_reader_account_shell( array $config = array() ): string {
	$defaults = array(
		'me_endpoint'     => '/reader-api/v1/me',
		'logout_endpoint' => '/reader-auth/logout',
		'login_endpoint'  => '/reader-auth/start?returnTo=%2Faccount%2F',
	);
	$public   = array();
	foreach ( $defaults as $key => $fallback ) {
		$value = isset( $config[ $key ] ) && is_string( $config[ $key ] ) ? $config[ $key ] : $fallback;
		if ( 0 !== strpos( $value, '/' ) || 0 === strpos( $value, '//' ) || false !== strpos( $value, '\\' ) || preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
			$value = $fallback;
		}
		$public[ $key ] = $value;
	}
	return '<section class="mc-reader" aria-label="Кабинет читателя" data-mc-reader-root'
		. ' data-me-endpoint="' . esc_attr( $public['me_endpoint'] ) . '"'
		. ' data-logout-endpoint="' . esc_attr( $public['logout_endpoint'] ) . '"'
		. ' data-login-endpoint="' . esc_attr( $public['login_endpoint'] ) . '">'
		. '<div class="mc-reader__panel"><header class="mc-reader__header">'
		. '<p class="mc-reader__eyebrow">HearthPulse</p><h2 class="mc-reader__title">Кабинет читателя</h2>'
		. '<p class="mc-reader__intro">Ваш профиль Манакоста со входом через HearthPulse.</p></header>'
		. '<section class="mc-reader__profile" aria-labelledby="mc-reader-profile-title"><h3 id="mc-reader-profile-title">Профиль</h3>'
		. '<p class="mc-reader__status" data-reader-status role="status" aria-live="polite">Проверяем вход…</p>'
		. '<div class="mc-reader__identity" data-reader-identity hidden></div><div class="mc-reader__actions" data-reader-actions></div></section>'
		. '<section class="mc-reader__saved" aria-labelledby="mc-reader-saved-title"><h3 id="mc-reader-saved-title">Сохранённые статьи</h3>'
		. '<p>Сохранение статей появится здесь в следующем обновлении.</p></section></div></section>';
}
