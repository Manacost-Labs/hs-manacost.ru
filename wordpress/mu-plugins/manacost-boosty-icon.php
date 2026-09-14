<?php
/**
 * Plugin Name: Manacost Boosty Icon
 * Description: Keeps the Boosty social mark centered and consistent with the white social icon theme.
 *
 * @package Manacost
 */

defined( 'ABSPATH' ) || exit;

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
