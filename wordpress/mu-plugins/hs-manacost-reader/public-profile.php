<?php
/**
 * Public reader-profile shell.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/platform-icons.php';

/**
 * Render an anonymous shell without looking up a WordPress reader.
 *
 * @param string $id Opaque public profile UUID.
 */
function hs_reader_public_profile_shell( string $id ): string {
	if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id ) ) {
		return '';
	}
	$socials = '';
	foreach (
		array(
			'twitch'  => 'Twitch',
			'youtube' => 'YouTube',
		) as $service => $label
	) {
		$socials .= '<a class="mc-public-profile__social mc-public-profile__social--' . esc_attr( $service ) . '" data-public-profile-' . esc_attr( $service ) . ' target="_blank" rel="noopener noreferrer" hidden>'
			. hs_reader_platform_icon( $service ) . '<span>' . esc_html( $label ) . '</span></a>';
	}
	$advertisement = class_exists( 'Manacost_Rsya_Inline_Banner' ) ? Manacost_Rsya_Inline_Banner::render_public_profile_banner() : '';
	return '<section class="mc-reader-ui mc-public-profile" data-mc-public-profile data-reader-id="' . esc_attr( $id ) . '" data-default-avatar-url="' . esc_attr( hs_manacost_reader_default_avatar_url() ) . '" data-class-icon-base="/wp-content/mu-plugins/hs-manacost-reader/class-icons/" aria-labelledby="mc-public-profile-title">'
		. '<a class="mc-public-profile__back" href="/">' . esc_html__( 'К материалам', 'hs-manacost-reader' ) . '</a><p data-public-profile-status role="status" aria-live="polite">' . esc_html__( 'Загружаем профиль…', 'hs-manacost-reader' ) . '</p>'
		. '<div data-public-profile-content hidden><img data-public-profile-avatar alt="" width="96" height="96" hidden><span data-public-profile-placeholder aria-hidden="true">М</span><p class="mc-public-profile__eyebrow">Читатель Манакоста</p>'
		. '<h1 id="mc-public-profile-title"><span data-public-profile-name></span><span class="mc-public-profile__author-mark mc-public-profile__author-mark--administrator" data-public-profile-administrator hidden>Администратор</span>'
		. '<span class="mc-public-profile__author-mark mc-public-profile__author-mark--paid" data-public-profile-paid role="img" aria-label="Платный подписчик" title="Платный подписчик" hidden><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m4 8 4.25 3.25L12 5l3.75 6.25L20 8l-1.7 10H5.7L4 8Z"></path><path d="M6.25 20h11.5"></path></svg><span>Платный подписчик</span></span></h1>'
		. '<p data-public-profile-bio></p><aside class="mc-public-profile__class" data-public-profile-class-row aria-label="Любимый класс" hidden><img data-public-profile-class-crest alt="" width="38" height="38" hidden><span><small>Любимый класс</small><strong data-public-profile-class></strong></span></aside><nav class="mc-public-profile__socials" data-public-profile-socials aria-label="Ссылки читателя" hidden><p>Где найти</p>'
		. $socials . '</nav></div>' . $advertisement . '</section>';
}
