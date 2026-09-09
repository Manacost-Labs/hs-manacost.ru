<?php
/**
 * Public reader-profile shell.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render an anonymous shell without looking up a WordPress reader.
 *
 * @param string $id Opaque public profile UUID.
 */
function hs_reader_public_profile_shell( string $id ): string {
	if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id ) ) {
		return '';
	}
	return '<section class="mc-public-profile" data-mc-public-profile data-reader-id="' . esc_attr( $id ) . '" aria-labelledby="mc-public-profile-title">'
		. '<a class="mc-public-profile__back" href="/">' . esc_html__( 'К материалам', 'hs-manacost-reader' ) . '</a><p data-public-profile-status role="status" aria-live="polite">' . esc_html__( 'Загружаем профиль…', 'hs-manacost-reader' ) . '</p>'
		. '<div data-public-profile-content hidden><img data-public-profile-avatar alt="" width="96" height="96" hidden><span data-public-profile-placeholder aria-hidden="true">М</span><p class="mc-public-profile__eyebrow">Читатель Манакоста</p><h1 id="mc-public-profile-title" data-public-profile-name></h1><p data-public-profile-paid hidden title="Платный подписчик">' . esc_html__( 'Платный подписчик', 'hs-manacost-reader' ) . '</p><p data-public-profile-class></p><p data-public-profile-bio></p></div></section>';
}
