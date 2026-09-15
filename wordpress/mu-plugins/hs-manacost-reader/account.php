<?php
/**
 * Cache-safe account shell; loader owns hooks and no WP identity is read.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/platform-icons.php';

/**
 * Return a small, decorative UI icon from the account's own asset-free set.
 *
 * Text remains in every control, so the SVG never carries its accessible name.
 *
 * @param string $name Icon identifier.
 */
function hs_manacost_reader_account_icon( string $name ): string {
	if ( in_array( $name, array( 'twitch', 'youtube' ), true ) ) {
		return hs_reader_platform_icon( $name );
	}
	$icons = array(
		'account' => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><circle cx="12" cy="8" r="3.25"></circle><path d="M5.25 20c.72-3.16 3.18-5 6.75-5s6.03 1.84 6.75 5"></path></svg>',
		'chevron' => '<svg class="mc-reader__chevron" viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="m4 6 4 4 4-4"></path></svg>',
		'edit'    => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m4 16.75-.75 4 4-.75L19 8.25a2.83 2.83 0 0 0-4-4L3.25 16.75Z"></path><path d="m13.75 5.5 4.75 4.75"></path></svg>',
		'close'   => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>',
		'camera'  => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 7.5h3l1.25-2h7.5L17 7.5h3A1.5 1.5 0 0 1 21.5 9v9A1.5 1.5 0 0 1 20 19.5H4A1.5 1.5 0 0 1 2.5 18V9A1.5 1.5 0 0 1 4 7.5Z"></path><circle cx="12" cy="13.25" r="3.25"></circle></svg>',
		'trash'   => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4.5 7.5h15M9 7.5v-2h6v2M7 7.5l.75 12h8.5l.75-12M10 11v5M14 11v5"></path></svg>',
		'check'   => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m5 12.5 4.25 4.25L19.5 6.5"></path></svg>',
		'retry'   => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M19.5 8.5V4.75L22 7.25A8 8 0 1 0 20 17.5"></path></svg>',
		'crown'   => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m4 8 4.25 3.25L12 5l3.75 6.25L20 8l-1.7 10H5.7L4 8Z"></path><path d="M6.25 20h11.5"></path></svg>',
		'back'    => '<svg class="mc-reader__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg>',
	);

	return $icons[ $name ] ?? '';
}

/**
 * Render a compact, consent-based social link after the reader name.
 *
 * The profile editor toggles these static, accessible marks; profile input is
 * never concatenated into HTML.
 *
 * @param string $service Consent-based platform identifier.
 */
function hs_manacost_reader_author_mark( string $service ): string {
	$labels = array(
		'twitch'  => 'Открыть Twitch-канал',
		'youtube' => 'Открыть YouTube-канал',
	);
	if ( ! isset( $labels[ $service ] ) ) {
		return '';
	}
	$title = 'youtube' === $service ? 'YouTube' : 'Twitch';
	return '<a class="mc-reader__author-mark mc-reader__author-mark--' . esc_attr( $service ) . '" data-reader-' . esc_attr( $service ) . '-mark href="#" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $labels[ $service ] ) . '" title="' . esc_attr( $labels[ $service ] ) . '" hidden>' . hs_manacost_reader_account_icon( $service ) . '<span>' . esc_html( $title ) . '</span></a>';
}

/**
 * Render anonymous public markup only.
 *
 * @param array<string, mixed> $config Public shortcode attributes.
 */
function hs_manacost_reader_account_shell( array $config = array() ): string {
	if ( ! function_exists( 'hs_manacost_reader_is_account_request' ) || ! hs_manacost_reader_is_account_request() ) {
		return '';
	}
	if ( function_exists( 'hs_reader_public_profile_request' ) && hs_reader_public_profile_request() ) {
		$id = hs_reader_public_profile_id();
		return '' !== $id ? hs_reader_public_profile_shell( $id )
			: '<section class="mc-reader-ui mc-public-profile"><h1>Профиль недоступен</h1><a class="mc-public-profile__back" href="/">К материалам</a></section>';
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
	$comments_enabled = function_exists( 'hs_reader_comments_enabled' ) && hs_reader_comments_enabled();
	$comments_note    = $comments_enabled
		? 'Комментарии публикуются сразу. Обновить данные автора в уже написанных комментариях можно здесь отдельным действием.'
		: 'Комментарии сейчас недоступны.';
	$default_avatar   = function_exists( 'hs_manacost_reader_default_avatar_url' ) ? hs_manacost_reader_default_avatar_url() : '';
	return '<section class="mc-reader mc-reader-ui" aria-label="Кабинет читателя" data-mc-reader-root'
		. ' data-me-endpoint="' . esc_attr( $public['me_endpoint'] ) . '"'
		. ' data-profile-endpoint="' . esc_attr( $public['profile_endpoint'] ) . '"'
		. ' data-avatar-endpoint="' . esc_attr( $public['avatar_endpoint'] ) . '"'
		. ' data-default-avatar-url="' . esc_attr( $default_avatar ) . '"'
		. ' data-class-icon-base="/wp-content/mu-plugins/hs-manacost-reader/class-icons/"'
		. ' data-logout-endpoint="' . esc_attr( $public['logout_endpoint'] ) . '"'
		. ' data-login-endpoint="' . esc_attr( $public['login_endpoint'] ) . '">'
		. '<div class="mc-reader__shell"><header class="mc-reader__masthead"><div class="mc-reader__title-group">'
		. '<p class="mc-reader__masthead-kicker">Профиль Манакоста</p><h1 class="mc-reader__eyebrow">Личный кабинет</h1></div>'
		. '<details class="mc-reader__account-menu" data-reader-account-menu hidden><summary>' . hs_manacost_reader_account_icon( 'account' ) . 'Аккаунт' . hs_manacost_reader_account_icon( 'chevron' ) . '</summary><div class="mc-reader__account-actions" data-reader-account-actions></div></details></header>'
		. '<p class="mc-reader__status" data-reader-status role="status" aria-live="polite">Проверяем вход…</p>'
		. '<div class="mc-reader__actions" data-reader-actions></div>'
		. '<section class="mc-reader__profile" aria-labelledby="mc-reader-profile-title" data-reader-profile-overview hidden>'
		. '<p class="mc-reader__profile-kicker">Ваш профиль</p>'
		. '<div class="mc-reader__identity-wrap"><div class="mc-reader__avatar" role="img" aria-label="Фото профиля">'
		. '<img data-reader-avatar-image alt="" hidden><span data-reader-avatar-placeholder aria-hidden="true">М</span></div>'
		. '<div class="mc-reader__identity-copy"><div class="mc-reader__identity-line"><h2 id="mc-reader-profile-title" class="mc-reader__identity" data-reader-identity></h2><span class="mc-reader__paid-badge" data-reader-paid role="img" aria-label="Платный подписчик" title="Платный подписчик" hidden>' . hs_manacost_reader_account_icon( 'crown' ) . '<span>Платный подписчик</span></span><span class="mc-reader__admin-badge" data-reader-administrator hidden>Администратор</span>' . hs_manacost_reader_author_mark( 'twitch' ) . hs_manacost_reader_author_mark( 'youtube' ) . '</div>'
		. '<p class="mc-reader__bio" data-reader-preview-bio></p><div class="mc-reader__profile-controls"><aside class="mc-reader__class-mark" aria-label="Любимый класс"><img class="mc-reader__crest" data-reader-class-crest alt="" width="36" height="36" hidden><div><p>Любимый класс</p><strong data-reader-preview-class></strong></div></aside><button class="mc-reader__button mc-ui-button mc-ui-button--secondary" data-reader-open-editor type="button">' . hs_manacost_reader_account_icon( 'edit' ) . '<span>Изменить профиль</span></button></div><p class="mc-reader__draft-note" data-reader-preview-label hidden role="status" aria-live="polite"></p></div></div></section>'
			. '<section class="mc-reader__favorites" aria-labelledby="mc-reader-favorites-title" data-reader-favorites hidden><header class="mc-reader__favorites-head"><div><p class="mc-reader__profile-kicker">Личная подборка</p><h2 id="mc-reader-favorites-title">Сохранённые статьи</h2><p class="mc-ui-help">Материалы, к которым вы хотите вернуться.</p></div></header><span class="mc-reader__favorites-sentinel" data-reader-favorites-sentinel aria-hidden="true"></span><p class="mc-reader__favorites-status" data-reader-favorites-status role="status" aria-live="polite"></p><ul class="mc-reader__favorites-list" data-reader-favorites-list></ul><button class="mc-reader__button mc-ui-button mc-ui-button--secondary" data-reader-favorites-more type="button" hidden>Показать ещё</button></section>'
			. '<section class="mc-reader__community-data" aria-labelledby="mc-reader-community-data-title" data-reader-community-data hidden><header><p class="mc-reader__profile-kicker">Управление данными</p><h2 id="mc-reader-community-data-title">Данные обсуждений</h2><p class="mc-ui-help">Здесь можно скачать свои комментарии или удалить их вместе с публичным профилем. Кабинет читателя останется без изменений.</p></header><div class="mc-reader__community-data-actions"><button class="mc-reader__button mc-ui-button mc-ui-button--secondary" type="button" data-reader-comments-export>Скачать мои комментарии</button><button class="mc-reader__button mc-ui-button mc-ui-button--text" type="button" data-reader-comments-erase>Удалить комментарии и публичный профиль</button></div><p class="mc-reader__community-data-status" data-reader-community-data-status role="status" aria-live="polite"></p></section>'
		. '<form class="mc-reader__workspace" data-reader-profile-editor hidden aria-labelledby="mc-reader-editor-title">'
		. '<div class="mc-reader__editor-head"><div><p class="mc-reader__editor-kicker">Профиль читателя</p><h2 id="mc-reader-editor-title">Настройки профиля</h2><p class="mc-ui-help">Так вас увидят в обсуждениях Манакоста.</p></div><button class="mc-reader__button mc-ui-button mc-ui-button--text" data-reader-cancel-editor type="button">' . hs_manacost_reader_account_icon( 'close' ) . '<span>Закрыть</span></button></div>'
		. '<p class="mc-reader__editor-status" data-reader-editor-status role="status" aria-live="polite"></p>'
		. '<div class="mc-reader__editor-grid"><section class="mc-reader__preview" aria-label="Фото профиля">'
		. '<span class="mc-ui-label">Фото профиля</span><div class="mc-reader__avatar mc-reader__avatar--editor" data-reader-editor-avatar role="img" aria-label="Предпросмотр фото">'
		. '<img data-reader-editor-avatar-image alt="" hidden><span data-reader-editor-avatar-placeholder aria-hidden="true">М</span></div>'
		. '<div class="mc-reader__avatar-controls"><label class="mc-reader__upload mc-ui-button mc-ui-button--secondary" for="mc-reader-avatar">' . hs_manacost_reader_account_icon( 'camera' ) . '<span>Выбрать фото</span>'
		. '<input id="mc-reader-avatar" data-reader-avatar-input type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="mc-reader-avatar-help"></label>'
		. '<p id="mc-reader-avatar-help" class="mc-reader__help mc-ui-help">JPEG, PNG или WebP до 4 МБ. Фото сохраняется сразу.</p>'
		. '<button class="mc-reader__text-button mc-ui-button mc-ui-button--text" data-reader-remove-avatar type="button" hidden>' . hs_manacost_reader_account_icon( 'trash' ) . '<span>Удалить фото</span></button></div></section>'
		. '<div class="mc-reader__fields" aria-label="Данные профиля">'
		. '<label class="mc-reader__field" for="mc-reader-display-name"><span class="mc-ui-label">Имя в Манакосте</span>'
		. '<input class="mc-ui-control" id="mc-reader-display-name" data-reader-display-name type="text" autocomplete="nickname" required aria-describedby="mc-reader-name-count"></label>'
		. '<span id="mc-reader-name-count" class="mc-reader__counter" data-reader-name-count>0 / 40</span>'
		. '<label class="mc-reader__field" for="mc-reader-bio"><span class="mc-ui-label">О себе</span>'
		. '<textarea class="mc-ui-control" id="mc-reader-bio" data-reader-bio rows="3" aria-describedby="mc-reader-bio-count"></textarea></label>'
		. '<span id="mc-reader-bio-count" class="mc-reader__counter" data-reader-bio-count>0 / 280</span>'
		. '<fieldset class="mc-reader__social-fields"><legend class="mc-ui-label">Где меня найти</legend><p class="mc-reader__social-help mc-ui-help" data-reader-social-help>Необязательно. Ссылки появятся в обсуждениях после обновления профиля ниже.</p><div class="mc-reader__social-grid">'
		. '<label class="mc-reader__field" for="mc-reader-twitch"><span class="mc-reader__social-label">' . hs_manacost_reader_account_icon( 'twitch' ) . '<span>Twitch</span></span><input class="mc-ui-control" id="mc-reader-twitch" data-reader-twitch type="url" inputmode="url" autocomplete="url" placeholder="https://twitch.tv/your_channel"></label>'
		. '<label class="mc-reader__field" for="mc-reader-youtube"><span class="mc-reader__social-label">' . hs_manacost_reader_account_icon( 'youtube' ) . '<span>YouTube</span></span><input class="mc-ui-control" id="mc-reader-youtube" data-reader-youtube type="url" inputmode="url" autocomplete="url" placeholder="https://youtube.com/@your_channel" aria-describedby="mc-reader-youtube-help"></label>'
		. '</div><p id="mc-reader-youtube-help" class="mc-reader__social-help mc-ui-help">YouTube: канал вида @имя или /channel/UC…</p></fieldset>'
		. '<label class="mc-reader__field" for="mc-reader-favorite-class"><span class="mc-ui-label">Любимый класс</span>'
		. '<select class="mc-ui-control" id="mc-reader-favorite-class" data-reader-favorite-class><option value="">Не выбран</option>'
		. '<option value="death-knight">Рыцарь смерти</option><option value="demon-hunter">Охотник на демонов</option>'
		. '<option value="druid">Друид</option><option value="hunter">Охотник</option><option value="mage">Маг</option>'
		. '<option value="paladin">Паладин</option><option value="priest">Жрец</option><option value="rogue">Разбойник</option>'
		. '<option value="shaman">Шаман</option><option value="warlock">Чернокнижник</option><option value="warrior">Воин</option></select></label>'
		. '<div class="mc-reader__form-actions"><button class="mc-reader__button mc-ui-button" data-reader-save-profile type="submit">' . hs_manacost_reader_account_icon( 'check' ) . '<span>Сохранить изменения</span></button>'
		. '<button class="mc-reader__button mc-ui-button mc-ui-button--secondary" data-reader-retry-profile type="button" hidden>' . hs_manacost_reader_account_icon( 'retry' ) . '<span>Повторить</span></button>'
		. '<button class="mc-reader__button mc-ui-button mc-ui-button--secondary" data-reader-reload-version type="button" hidden>' . hs_manacost_reader_account_icon( 'retry' ) . '<span>Обновить версию</span></button></div>'
		. '<section class="mc-reader__publication" aria-labelledby="mc-reader-publication-title"' . ( $comments_enabled ? '' : ' hidden' ) . '><h3 id="mc-reader-publication-title">Профиль в комментариях</h3>'
		. '<p class="mc-ui-help">Уже оставляли комментарии? Обновите имя, фото, описание, любимый класс и ссылки Twitch / YouTube сразу во всех своих комментариях и публичном профиле. Новый комментарий писать не нужно.</p>'
		. '<p class="mc-ui-help" data-reader-publication-help>Сначала сохраните изменения, затем обновите данные в комментариях.</p>'
		. '<button class="mc-reader__button mc-ui-button mc-ui-button--secondary" data-reader-publish-profile type="button" disabled>' . hs_manacost_reader_account_icon( 'retry' ) . '<span>Обновить в комментариях</span></button></section>'
		. '<p class="mc-reader__note">Профиль Манакоста не изменяет профиль HearthPulse. ' . esc_html( $comments_note ) . '</p>'
		. '</div></div></form><footer class="mc-reader__footer"><a class="mc-reader__back" href="/">'
		. hs_manacost_reader_account_icon( 'back' ) . '<span>' . esc_html__( 'Вернуться к материалам', 'hs-manacost-reader' ) . '</span></a></footer></div></section>';
}
