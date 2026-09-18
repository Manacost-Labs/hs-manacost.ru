<?php
/**
 * Plugin Name: Manacost Boosty Icon
 * Description: Keeps the Boosty social mark centered and consistent with the white social icon theme.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the homepage link to the official Telegram channel.
 *
 * @return string
 */
function manacost_telegram_news_strip_markup(): string {
	return <<<'HTML'
<aside class="manacost-telegram-news" aria-label="Новости Manacost в Telegram">
	<a class="manacost-telegram-news__link" href="https://t.me/manacost_ru" target="_blank" rel="noopener noreferrer" aria-label="Открыть канал Manacost в Telegram">
		<svg class="manacost-telegram-news__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21.8 3.4 18.5 20c-.2 1.2-1 1.5-1.9.9l-5.4-4-2.6 2.5c-.3.3-.5.5-1 .5l.4-5.5 10.1-9.1c.4-.4-.1-.6-.7-.2L4.9 13 1 11.8c-1.1-.3-1.1-1.1.2-1.6L20.1 3c.9-.3 1.9.2 1.7.4Z"/></svg>
		<span>Актуальные и быстрые новости в Telegram</span>
	</a>
</aside>
HTML;
}

/**
 * Inserts the Telegram strip directly before Newspaper's main-content wrapper.
 *
 * @param string $html Rendered public-page markup.
 * @return string
 */
function manacost_inject_telegram_news_strip( string $html ): string {
	$pattern  = '/(<div\b[^>]*\bclass=(["\'])[^"\']*\btd-main-content-wrap\b[^"\']*\2[^>]*>)/i';
	$injected = preg_replace( $pattern, manacost_telegram_news_strip_markup() . '$1', $html, 1 );

	return is_string( $injected ) ? $injected : $html;
}

/**
 * Starts homepage-only output buffering after WordPress has resolved the query.
 *
 * @return void
 */
function manacost_start_telegram_news_strip_buffer(): void {
	if ( is_admin() || is_feed() || is_preview() || ! is_front_page() ) {
		return;
	}

	ob_start( 'manacost_inject_telegram_news_strip' );
}

/**
 * Prints styles for the homepage Telegram news strip.
 *
 * @return void
 */
function manacost_render_telegram_news_strip_style(): void {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}
	?>
	<style id="manacost-telegram-news-strip-style">
		.manacost-telegram-news {
			background: #2a77bf;
			color: #fff;
		}

		.manacost-telegram-news__link {
			align-items: center;
			color: inherit;
			display: flex;
			font-family: Arial, "Helvetica Neue", sans-serif;
			font-size: 15px;
			font-weight: 700;
			gap: 9px;
			justify-content: center;
			line-height: 1.3;
			min-height: 44px;
			padding: 6px 20px;
			text-align: center;
			text-decoration: none;
		}

		.manacost-telegram-news__link:hover {
			background: #1f679f;
			color: #fff;
		}

		.manacost-telegram-news__link:focus-visible {
			outline: 3px solid #fff;
			outline-offset: -3px;
		}

		.manacost-telegram-news__icon {
			fill: currentColor;
			flex: 0 0 18px;
			height: 18px;
			width: 18px;
		}

		@media (max-width: 767px) {
			.manacost-telegram-news__link {
				font-size: 13px;
				gap: 7px;
				padding-inline: 14px;
			}
		}
	</style>
	<?php
}

add_action( 'template_redirect', 'manacost_start_telegram_news_strip_buffer', 0 );
add_action( 'wp_head', 'manacost_render_telegram_news_strip_style', 100 );

/**
 * Replaces the icon-font glyph with the official Boosty mark while preserving
 * Newspaper's existing social-link markup and hover behavior.
 *
 * @return void
 */
function manacost_render_boosty_icon_style(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="manacost-boosty-social-icon">
		.td-social-icon-wrap a[href*="boosty.to"] .td-icon-boosty {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 24px;
			height: 24px;
			line-height: 1;
			font-size: 0;
			vertical-align: middle;
		}

		.td-social-icon-wrap a[href*="boosty.to"] .td-icon-boosty::before {
			content: "";
			display: block;
			flex: 0 0 16px;
			width: 16px;
			height: 20px;
			background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='80 45 165 205'%3E%3Cpath fill='%23fff' d='M87.5,163.9L120.2,51h50.1l-10.1,35c-.1.2-.2.4-.3.6L133.3,179h24.8c-10.4,25.9-18.5,46.2-24.3,60.9-45.8-.5-58.6-33.3-47.4-72.1M133.9,240l60.4-86.9h-25.6l22.3-55.7c38.2,4,56.2,34.1,45.6,70.5-11.3,39.1-57.2,72.1-101.8,72.1h-.9z'/%3E%3C/svg%3E") center / contain no-repeat;
		}
	</style>
	<?php
}

add_action( 'wp_head', 'manacost_render_boosty_icon_style', 100 );
