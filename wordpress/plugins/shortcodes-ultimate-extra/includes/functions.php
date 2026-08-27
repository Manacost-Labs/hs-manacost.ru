<?php

if ( ! function_exists( 'su_get_css_class' ) ) {

	/**
	 * Extract CSS class name(s) from shortcode $atts and prepend with a space.
	 *
	 * @since  1.5.13
	 * @param array   $atts Shortcode atts.
	 * @return string       Extra CSS class(es) prepended by a space.
	 */
	function su_get_css_class( $atts ) {
		return $atts['class'] ? ' ' . trim( $atts['class'] ) : '';
	}

}
