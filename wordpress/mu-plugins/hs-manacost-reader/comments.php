<?php
/**
 * Public, identity-free discussion shell.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Render a cache-safe shell; profile data arrives only from the independent BFF. */
function hs_reader_comments_shell(): string {
	$post_id   = (int) get_the_ID();
	$permalink = get_permalink( $post_id );
	$path      = is_string( $permalink ) ? wp_parse_url( $permalink, PHP_URL_PATH ) : null;
	$path      = is_string( $path ) && str_starts_with( $path, '/' ) && ! str_starts_with( $path, '//' ) ? $path : '/';
	$login     = '/reader-auth/start?returnTo=' . rawurlencode( $path . '#reader-comments' );
	return '<section id="reader-comments" class="mc-comments" data-mc-comments data-post-id="' . esc_attr( (string) $post_id ) . '" aria-labelledby="reader-comments-title">'
		. '<header class="mc-comments__header"><p class="mc-comments__eyebrow">Обсуждение</p><h2 id="reader-comments-title">' . esc_html__( 'Комментарии', 'hs-manacost-reader' ) . '</h2></header>'
		. '<p class="mc-comments__status" data-comments-status role="status" aria-live="polite">' . esc_html__( 'Загружаем комментарии…', 'hs-manacost-reader' ) . '</p>'
		. '<div class="mc-comments__list" data-comments-list></div><form class="mc-comments__composer" data-comments-form hidden>'
		. '<p data-comments-reply hidden></p><button type="button" data-comments-cancel hidden>' . esc_html__( 'Отменить ответ', 'hs-manacost-reader' ) . '</button>'
		. '<label for="reader-comment-body">' . esc_html__( 'Комментарий', 'hs-manacost-reader' ) . '</label><textarea id="reader-comment-body" data-comments-body rows="5" minlength="2" maxlength="1000" required></textarea>'
		. '<label class="mc-comments__consent"><input type="checkbox" data-comments-consent required> ' . esc_html__( 'Согласен(на) с публикацией комментария: имени, аватара, любимого класса, описания профиля и статуса подписчика.', 'hs-manacost-reader' ) . '</label>'
		. '<button type="submit" data-comments-submit>' . esc_html__( 'Отправить комментарий', 'hs-manacost-reader' ) . '</button></form>'
		. '<button type="button" data-comments-retry hidden>' . esc_html__( 'Повторить отправку', 'hs-manacost-reader' ) . '</button>'
		. '<details class="mc-comments__data" data-comments-data hidden><summary>' . esc_html__( 'Мои данные в комментариях', 'hs-manacost-reader' ) . '</summary><p>' . esc_html__( 'Здесь можно выгрузить свои комментарии или удалить их вместе с публичным профилем. Кабинет читателя останется без изменений.', 'hs-manacost-reader' ) . '</p><button type="button" data-comments-export>' . esc_html__( 'Скачать мои комментарии', 'hs-manacost-reader' ) . '</button><button type="button" data-comments-erase>' . esc_html__( 'Удалить мои комментарии и публичный профиль', 'hs-manacost-reader' ) . '</button></details>'
		. '<p class="mc-comments__login" data-comments-login hidden><a href="' . esc_attr( $login ) . '">' . esc_html__( 'Войти через HearthPulse', 'hs-manacost-reader' ) . '</a></p></section>';
}
