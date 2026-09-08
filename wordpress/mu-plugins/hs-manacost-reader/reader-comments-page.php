<?php
/**
 * Unique template for the independent discussion shell, not native WP comments.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;
if ( hs_reader_comment_article( (int) get_the_ID() )['allowed'] ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes every dynamic attribute.
	echo hs_reader_comments_shell();
}
