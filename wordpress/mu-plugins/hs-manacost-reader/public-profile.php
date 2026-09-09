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
	return '<section class="mc-reader-ui mc-public-profile" data-mc-public-profile data-reader-id="' . esc_attr( $id ) . '" aria-labelledby="mc-public-profile-title">'
		. '<a class="mc-public-profile__back" href="/">' . esc_html__( 'К материалам', 'hs-manacost-reader' ) . '</a><p data-public-profile-status role="status" aria-live="polite">' . esc_html__( 'Загружаем профиль…', 'hs-manacost-reader' ) . '</p>'
		. '<div data-public-profile-content hidden><img data-public-profile-avatar alt="" width="96" height="96" hidden><span data-public-profile-placeholder aria-hidden="true">М</span><p class="mc-public-profile__eyebrow">Читатель Манакоста</p><h1 id="mc-public-profile-title"><span data-public-profile-name></span><span class="mc-public-profile__author-mark mc-public-profile__author-mark--twitch" data-public-profile-twitch-mark role="img" aria-label="Автор ведёт Twitch" title="Автор ведёт Twitch" hidden><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5.25 3.5h13.5v12.75H13.5L10 19.75v-3.5H5.25V3.5Z"></path><path d="M10 8v4M14 8v4"></path></svg></span><span class="mc-public-profile__author-mark mc-public-profile__author-mark--youtube" data-public-profile-youtube-mark role="img" aria-label="Автор ведёт YouTube" title="Автор ведёт YouTube" hidden><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M21.3 7.1a2.77 2.77 0 0 0-1.95-1.96C17.63 4.67 12 4.67 12 4.67s-5.63 0-7.35.47A2.77 2.77 0 0 0 2.7 7.1C2.23 8.82 2.23 12 2.23 12s0 3.18.47 4.9a2.77 2.77 0 0 0 1.95 1.96c1.72.47 7.35.47 7.35.47s5.63 0 7.35-.47a2.77 2.77 0 0 0 1.95-1.96c.47-1.72.47-4.9.47-4.9s0-3.18-.47-4.9Z"></path><path d="m10 15.5 5-3.5-5-3.5v7Z"></path></svg></span><span class="mc-public-profile__author-mark mc-public-profile__author-mark--paid" data-public-profile-paid role="img" aria-label="Платный подписчик" title="Платный подписчик" hidden><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m4 8 4.25 3.25L12 5l3.75 6.25L20 8l-1.7 10H5.7L4 8Z"></path><path d="M6.25 20h11.5"></path></svg></span></h1><p data-public-profile-class></p><p data-public-profile-bio></p><nav class="mc-public-profile__socials" data-public-profile-socials aria-label="Ссылки читателя" hidden><p>Где найти</p><a data-public-profile-twitch target="_blank" rel="noopener noreferrer" hidden>Twitch</a><a data-public-profile-youtube target="_blank" rel="noopener noreferrer" hidden>YouTube</a></nav></div></section>';
}
