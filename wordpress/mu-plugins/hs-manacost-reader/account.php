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
	return '<section class="mc-reader" aria-label="Кабинет читателя" data-mc-reader-root'
		. ' data-me-endpoint="' . esc_attr( $public['me_endpoint'] ) . '"'
		. ' data-profile-endpoint="' . esc_attr( $public['profile_endpoint'] ) . '"'
		. ' data-avatar-endpoint="' . esc_attr( $public['avatar_endpoint'] ) . '"'
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
		. '<p class="mc-reader__status" data-reader-status role="status" aria-live="polite">Проверяем вход…</p>'
		. '<div class="mc-reader__actions" data-reader-actions></div>'
		. '<form class="mc-reader__workspace" data-reader-profile-editor hidden>'
		. '<section class="mc-reader__preview" aria-labelledby="mc-reader-preview-title">'
		. '<div class="mc-reader__preview-heading"><h3 id="mc-reader-preview-title">Ваш профиль</h3>'
		. '<p class="mc-reader__preview-label" data-reader-preview-label hidden>Предпросмотр</p></div>'
		. '<div class="mc-reader__avatar" role="img" aria-label="Фото профиля">'
		. '<img data-reader-avatar-image alt="" hidden><span data-reader-avatar-placeholder aria-hidden="true">М</span></div>'
		. '<strong class="mc-reader__identity" data-reader-identity></strong>'
		. '<span class="mc-reader__class" data-reader-preview-class></span>'
		. '<p class="mc-reader__bio" data-reader-preview-bio></p>'
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
		. '<p class="mc-reader__note">Профиль Манакоста не изменяет профиль HearthPulse. Комментарии пока недоступны.</p>'
		. '</div></form></section>'
		. '<section class="mc-reader__saved" aria-labelledby="mc-reader-saved-title">'
		. '<div class="mc-reader__section-heading"><span class="mc-reader__mark mc-reader__mark--gold" aria-hidden="true">'
		. '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 3h12v18l-6-4-6 4V3Z"/></svg></span>'
		. '<h2 id="mc-reader-saved-title">Сохранённые статьи</h2></div>'
		. '<div class="mc-reader__saved-copy"><p class="mc-reader__availability">' . esc_html__( 'В разработке', 'hs-manacost-reader' ) . '</p>'
		. '<p class="mc-reader__empty">Сохранение статей появится здесь в следующем обновлении.</p></div>'
		. '</section></div><footer class="mc-reader__footer"><a class="mc-reader__back" href="/">'
		. '<span aria-hidden="true">←</span> ' . esc_html__( 'Вернуться к материалам', 'hs-manacost-reader' ) . '</a></footer></section>';
}
