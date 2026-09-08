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
		. '<header class="mc-reader__header">'
		. '<p class="mc-reader__eyebrow">' . esc_html__( 'Манакост / HearthPulse', 'hs-manacost-reader' ) . '</p>'
		. '<h1 class="mc-reader__title">Кабинет читателя</h1>'
		. '<p class="mc-reader__intro">Ваш профиль Манакоста со входом через HearthPulse.</p></header>'
		. '<div class="mc-reader__panel"><section class="mc-reader__profile" aria-labelledby="mc-reader-profile-title">'
		. '<div class="mc-reader__section-heading"><span class="mc-reader__mark" aria-hidden="true">'
		. '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg></span>'
		. '<h2 id="mc-reader-profile-title">Профиль</h2></div>'
		. '<div class="mc-reader__identity" data-reader-identity hidden></div>'
		. '<p class="mc-reader__status" data-reader-status role="status" aria-live="polite">Проверяем вход…</p>'
		. '<div class="mc-reader__actions" data-reader-actions></div>'
		. '<p class="mc-reader__note">' . esc_html__( 'Один аккаунт для Манакоста и HearthPulse.', 'hs-manacost-reader' ) . '</p></section>'
		. '<section class="mc-reader__saved" aria-labelledby="mc-reader-saved-title">'
		. '<div class="mc-reader__section-heading"><span class="mc-reader__mark mc-reader__mark--gold" aria-hidden="true">'
		. '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 3h12v18l-6-4-6 4V3Z"/></svg></span>'
		. '<h2 id="mc-reader-saved-title">Сохранённые статьи</h2></div>'
		. '<div class="mc-reader__saved-copy"><p class="mc-reader__availability">' . esc_html__( 'В разработке', 'hs-manacost-reader' ) . '</p>'
		. '<p class="mc-reader__empty">Сохранение статей появится здесь в следующем обновлении.</p></div>'
		. '</section></div><footer class="mc-reader__footer"><a class="mc-reader__back" href="/">'
		. '<span aria-hidden="true">←</span> ' . esc_html__( 'Вернуться к материалам', 'hs-manacost-reader' ) . '</a></footer></section>';
}
