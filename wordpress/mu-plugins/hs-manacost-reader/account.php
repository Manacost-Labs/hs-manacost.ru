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
	if ( function_exists( 'hs_reader_public_profile_request' ) && hs_reader_public_profile_request() ) {
		$id = hs_reader_public_profile_id();
		return '' !== $id ? hs_reader_public_profile_shell( $id )
			: '<section class="mc-public-profile"><h1>Профиль недоступен</h1><a href="/">К материалам</a></section>';
	}
	$defaults = array(
		'me_endpoint'      => '/reader-api/v1/me',
		'profile_endpoint' => '/reader-api/v1/profile',
		'avatar_endpoint'  => '/reader-api/v1/profile/avatar',
		'logout_endpoint'  => '/reader-auth/logout',
		'login_endpoint'   => '/reader-auth/start?returnTo=%2Faccount%2F',
	);
	$public   = array();
	foreach ( $defaults as $key => $fallback ) {
		$value = isset( $config[ $key ] ) && is_string( $config[ $key ] ) ? $config[ $key ] : $fallback;
		if ( 0 !== strpos( $value, '/' ) || 0 === strpos( $value, '//' ) || false !== strpos( $value, '\\' ) || preg_match( '/[\x00-\x1F\x7F]/', $value ) ) {
			$value = $fallback;
		}
		$public[ $key ] = $value;
	}
	$comments_note = function_exists( 'hs_reader_comments_enabled' ) && hs_reader_comments_enabled()
		? 'Комментарии публикуются сразу после вашего согласия. Имя, аватар, профиль и выбранный класс будут видны другим читателям.'
		: 'Комментарии сейчас недоступны.';
	return '<section class="mc-reader" aria-label="Кабинет читателя" data-mc-reader-root'
		. ' data-me-endpoint="' . esc_attr( $public['me_endpoint'] ) . '"'
		. ' data-profile-endpoint="' . esc_attr( $public['profile_endpoint'] ) . '"'
		. ' data-avatar-endpoint="' . esc_attr( $public['avatar_endpoint'] ) . '"'
		. ' data-class-icon-base="/wp-content/mu-plugins/hs-manacost-reader/class-icons/"'
		. ' data-logout-endpoint="' . esc_attr( $public['logout_endpoint'] ) . '"'
		. ' data-login-endpoint="' . esc_attr( $public['login_endpoint'] ) . '">'
		. '<div class="mc-reader__shell"><header class="mc-reader__masthead">'
		. '<h1 class="mc-reader__eyebrow">' . esc_html__( 'Личный кабинет', 'hs-manacost-reader' ) . '</h1>'
		. '<nav class="mc-reader__tabs" aria-label="Разделы кабинета"><a class="mc-reader__tab" href="#mc-reader-profile-title" aria-current="page">Профиль</a>'
		. '<span class="mc-reader__tab" aria-disabled="true">Сохранённые статьи <small>недоступны</small></span>'
		. '<span class="mc-reader__tab" aria-disabled="true">Мои комментарии <small>скоро</small></span></nav></header>'
		. '<p class="mc-reader__status" data-reader-status role="status" aria-live="polite">Проверяем вход…</p>'
		. '<div class="mc-reader__actions" data-reader-actions></div>'
		. '<section class="mc-reader__profile" aria-labelledby="mc-reader-profile-title" data-reader-profile-overview hidden>'
		. '<div class="mc-reader__identity-wrap"><div class="mc-reader__avatar" role="img" aria-label="Фото профиля">'
		. '<img data-reader-avatar-image alt="" hidden><span data-reader-avatar-placeholder aria-hidden="true">М</span></div>'
		. '<div class="mc-reader__identity-copy"><h2 id="mc-reader-profile-title" class="mc-reader__identity" data-reader-identity></h2>'
		. '<p class="mc-reader__bio" data-reader-preview-bio></p><p class="mc-reader__draft-note" data-reader-preview-label hidden role="status" aria-live="polite"></p><button class="mc-reader__button mc-reader__button--quiet" data-reader-open-editor type="button">Изменить профиль</button></div></div>'
		. '<aside class="mc-reader__class-mark" aria-label="Любимый класс"><img class="mc-reader__crest" data-reader-class-crest alt="" width="82" height="82" hidden><div><p>Любимый класс</p><strong data-reader-preview-class></strong></div></aside></section>'
		. '<section class="mc-reader__saved" aria-labelledby="mc-reader-saved-title"><h2 id="mc-reader-saved-title">Сохранённые статьи</h2><p>Закладки пока недоступны.</p></section>'
		. '<form class="mc-reader__workspace" data-reader-profile-editor hidden aria-labelledby="mc-reader-editor-title">'
		. '<div class="mc-reader__editor-head"><h2 id="mc-reader-editor-title">Редактирование профиля</h2><button class="mc-reader__button mc-reader__button--quiet" data-reader-cancel-editor type="button">← Назад</button></div>'
		. '<div class="mc-reader__editor-grid"><section class="mc-reader__preview" aria-label="Фото профиля">'
		. '<div class="mc-reader__avatar-controls"><label for="mc-reader-avatar">Фото профиля</label>'
		. '<input id="mc-reader-avatar" data-reader-avatar-input type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="mc-reader-avatar-help">'
		. '<p id="mc-reader-avatar-help" class="mc-reader__help">JPEG, PNG или WebP, до 4 МБ.</p>'
		. '<button class="mc-reader__text-button" data-reader-remove-avatar type="button" hidden>Удалить фото</button></div></section>'
		. '<div class="mc-reader__fields" aria-labelledby="mc-reader-fields-title"><h3 id="mc-reader-fields-title">Данные профиля</h3>'
		. '<label class="mc-reader__field" for="mc-reader-display-name"><span>Имя в Манакосте</span>'
		. '<input id="mc-reader-display-name" data-reader-display-name type="text" autocomplete="nickname" required aria-describedby="mc-reader-name-count"></label>'
		. '<span id="mc-reader-name-count" class="mc-reader__counter" data-reader-name-count>0 / 40</span>'
		. '<label class="mc-reader__field" for="mc-reader-bio"><span>О себе</span>'
		. '<textarea id="mc-reader-bio" data-reader-bio rows="5" aria-describedby="mc-reader-bio-count"></textarea></label>'
		. '<span id="mc-reader-bio-count" class="mc-reader__counter" data-reader-bio-count>0 / 280</span>'
		. '<label class="mc-reader__field" for="mc-reader-favorite-class"><span>Любимый класс</span>'
		. '<select id="mc-reader-favorite-class" data-reader-favorite-class><option value="">Не выбран</option>'
		. '<option value="death-knight">Рыцарь смерти</option><option value="demon-hunter">Охотник на демонов</option>'
		. '<option value="druid">Друид</option><option value="hunter">Охотник</option><option value="mage">Маг</option>'
		. '<option value="paladin">Паладин</option><option value="priest">Жрец</option><option value="rogue">Разбойник</option>'
		. '<option value="shaman">Шаман</option><option value="warlock">Чернокнижник</option><option value="warrior">Воин</option></select></label>'
		. '<div class="mc-reader__form-actions"><button class="mc-reader__button mc-reader__button--primary" data-reader-save-profile type="submit">Сохранить изменения</button>'
		. '<button class="mc-reader__button mc-reader__button--secondary" data-reader-retry-profile type="button" hidden>Повторить</button>'
		. '<button class="mc-reader__button mc-reader__button--secondary" data-reader-reload-version type="button" hidden>Обновить версию</button></div>'
		. '<p class="mc-reader__editor-status" data-reader-editor-status role="status" aria-live="polite"></p>'
		. '<p class="mc-reader__note">Профиль Манакоста не изменяет профиль HearthPulse. ' . esc_html( $comments_note ) . '</p>'
		. '</div></div></form><footer class="mc-reader__footer"><a class="mc-reader__back" href="/">'
		. '<span aria-hidden="true">←</span> ' . esc_html__( 'Вернуться к материалам', 'hs-manacost-reader' ) . '</a></footer></div></section>';
}
