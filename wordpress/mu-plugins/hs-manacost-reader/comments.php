<?php
/**
 * Public, identity-free discussion shell.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/platform-icons.php';

/** Render a cache-safe shell; profile data arrives only from the independent BFF. */
function hs_reader_comments_shell(): string {
	$post_id   = (int) get_the_ID();
	$permalink = get_permalink( $post_id );
	$path      = is_string( $permalink ) ? wp_parse_url( $permalink, PHP_URL_PATH ) : null;
	$path      = is_string( $path ) && str_starts_with( $path, '/' ) && ! str_starts_with( $path, '//' ) ? $path : '/';
	$login     = '/reader-auth/start?returnTo=' . rawurlencode( $path . '#reader-comments' );
	return '<section id="reader-comments" class="mc-reader-ui mc-comments" data-mc-comments data-post-id="' . esc_attr( (string) $post_id ) . '" data-default-avatar-url="' . esc_attr( hs_manacost_reader_default_avatar_url() ) . '" aria-labelledby="reader-comments-title">'
		. '<template data-comments-twitch-icon>' . hs_reader_platform_icon( 'twitch' ) . '</template><template data-comments-youtube-icon>' . hs_reader_platform_icon( 'youtube' ) . '</template>'
		. '<header class="mc-comments__header"><p class="mc-comments__eyebrow">Обсуждение</p><h2 id="reader-comments-title">' . esc_html__( 'Комментарии', 'hs-manacost-reader' ) . '</h2></header>'
		. '<p class="mc-comments__status" data-comments-status data-loading="true" role="status" aria-live="polite">' . esc_html__( 'Загружаем комментарии…', 'hs-manacost-reader' ) . '</p>'
		. '<div class="mc-comments__list" data-comments-list></div><form class="mc-comments__composer" data-comments-form hidden>'
		. '<div class="mc-comments__composer-heading"><div class="mc-comments__composer-identity"><h3>' . esc_html__( 'Написать комментарий', 'hs-manacost-reader' ) . '</h3><div class="mc-comments__me" data-comments-me></div></div><a class="mc-ui-button mc-ui-button--text mc-comments__profile-link" href="/account/" aria-label="' . esc_attr( esc_html__( 'Изменить профиль', 'hs-manacost-reader' ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 5.5 4 4M4 20l3.4-.7L19 7.7a1.4 1.4 0 0 0 0-2l-.7-.7a1.4 1.4 0 0 0-2 0L4.7 16.6 4 20Z"/></svg><span>' . esc_html__( 'Профиль', 'hs-manacost-reader' ) . '</span></a></div>'
		. '<div class="mc-comments__reply-context"><p data-comments-reply hidden></p><button class="mc-ui-button mc-ui-button--text" type="button" data-comments-cancel hidden>' . esc_html__( 'Отменить ответ', 'hs-manacost-reader' ) . '</button></div>'
		. '<label class="mc-ui-label" for="reader-comment-body">' . esc_html__( 'Комментарий', 'hs-manacost-reader' ) . '</label><textarea class="mc-ui-control" id="reader-comment-body" data-comments-body rows="4" minlength="2" maxlength="1000" aria-describedby="reader-comment-count" placeholder="Поделитесь мнением или задайте вопрос" required></textarea>'
		. '<div class="mc-comments__field-meta"><span class="mc-ui-help" id="reader-comment-count" data-comments-count>0 / 1000</span></div>'
		. '<div class="mc-comments__attachment" data-comments-attachment><input id="reader-comment-attachment" data-comments-attachment-input type="file" accept="image/jpeg,image/png,image/webp" hidden><button class="mc-ui-button mc-ui-button--secondary mc-comments__attachment-picker" type="button" data-comments-attachment-picker aria-label="' . esc_attr( esc_html__( 'Прикрепить изображение', 'hs-manacost-reader' ) ) . '" aria-describedby="reader-comment-attachment-help" title="' . esc_attr( esc_html__( 'Прикрепить изображение', 'hs-manacost-reader' ) ) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5h14v14H5zM7.5 15.5l3-3 2.2 2.2 1.8-1.8 2.5 2.6M9 9h.01"/></svg></button><span id="reader-comment-attachment-help" class="mc-comments__attachment-picker-label">' . esc_html__( 'JPEG, PNG или WebP до 4 МБ. Можно вставить скриншот из буфера.', 'hs-manacost-reader' ) . '</span><div class="mc-comments__attachment-preview" data-comments-attachment-preview hidden><img data-comments-attachment-image alt="Предпросмотр вложения"><div><p data-comments-attachment-status></p><button class="mc-ui-button mc-ui-button--text" type="button" data-comments-attachment-remove>' . esc_html__( 'Убрать изображение', 'hs-manacost-reader' ) . '</button></div></div></div>'
		. '<div class="mc-comments__composer-footer"><button class="mc-ui-button" type="submit" data-comments-submit>' . esc_html__( 'Опубликовать', 'hs-manacost-reader' ) . '</button></div>'
		. '<div class="mc-comments__profile-sync"><p class="mc-comments__profile-notice" data-comments-profile-notice hidden>' . esc_html__( 'В прошлых комментариях осталось старое имя, фото или значки.', 'hs-manacost-reader' ) . '</p><button class="mc-ui-button mc-ui-button--secondary" type="button" data-comments-refresh-profile hidden>' . esc_html__( 'Обновить данные', 'hs-manacost-reader' ) . '</button></div></form>'
		. '<button class="mc-ui-button" type="button" data-comments-retry hidden>' . esc_html__( 'Повторить отправку', 'hs-manacost-reader' ) . '</button>'
			. '<p class="mc-comments__login" data-comments-login hidden><a href="' . esc_attr( $login ) . '">' . esc_html__( 'Войти через HearthPulse', 'hs-manacost-reader' ) . '</a></p></section>';
}
