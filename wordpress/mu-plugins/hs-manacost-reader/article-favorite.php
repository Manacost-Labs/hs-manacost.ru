<?php
/**
 * Anonymous article favorite shell for the independent HearthPulse reader service.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

/** Decorative bookmark; the adjacent button text remains the accessible name. */
function hs_reader_article_favorite_icon(): string {
	return '<svg class="mc-article-favorite__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M6.5 4.5A1.5 1.5 0 0 1 8 3h8a1.5 1.5 0 0 1 1.5 1.5V21l-5.5-3.75L6.5 21V4.5Z"></path></svg>';
}

/**
 * Render only a cache-safe ID shell; article title/path never comes from browser input.
 *
 * @param int $post_id Editorial post identifier.
 * @return string
 */
function hs_reader_article_favorite_shell( int $post_id ): string {
	$article = hs_reader_favorite_article( $post_id );
	if ( true !== $article['allowed'] || ! isset( $article['path'] ) || ! is_string( $article['path'] ) ) {
		return '';
	}
	$login = '/reader-auth/start?returnTo=' . rawurlencode( $article['path'] );
	return '<aside class="mc-reader-ui mc-article-favorite" data-mc-article-favorite data-post-id="' . esc_attr( (string) $post_id ) . '" aria-label="Избранное">'
		. '<button class="mc-ui-button mc-ui-button--secondary mc-article-favorite__button" data-favorite-toggle type="button" aria-pressed="false">'
		. hs_reader_article_favorite_icon() . '<span data-favorite-label>Сохранить статью</span></button>'
		. '<a class="mc-ui-button mc-ui-button--secondary" data-favorite-login href="' . esc_attr( $login ) . '" hidden>Войти через HearthPulse</a>'
		. '<p class="mc-article-favorite__status" data-favorite-status role="status" aria-live="polite"></p></aside>';
}

/**
 * Append a favorite control only once to the main, public article body.
 *
 * @param string $content Original article content.
 * @return string
 */
function hs_reader_article_favorite_content( string $content ): string {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return hs_reader_article_favorite_shell( (int) get_the_ID() ) . $content;
}
