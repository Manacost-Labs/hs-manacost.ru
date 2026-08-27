<?php

su_add_shortcode(
	array(
		'id'             => 'content_slider',
		'name'           => __( 'Content slider', 'shortcodes-ultimate-extra' ),
		'type'           => 'wrap',
		'group'          => 'extra gallery',
		'required_child' => 'content_slide',
		'desc'           => __( 'Advanced responsive content slider', 'shortcodes-ultimate-extra' ),
		'icon'           => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/content_slider.svg',
		'image'          => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/content_slider.svg',
		'callback'       => 'su_shortcode_content_slider',
		'atts'           => array(
			'style'      => array(
				'type'    => 'select',
				'default' => 'default',
				'name'    => __( 'Style', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose slider skin', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'default' => __( 'Default', 'shortcodes-ultimate-extra' ),
					'dark'    => __( 'Dark', 'shortcodes-ultimate-extra' ),
					'light'   => __( 'Light', 'shortcodes-ultimate-extra' ),
				),
			),
			'effect'     => array(
				'type'    => 'select',
				'default' => 'slide',
				'name'    => __( 'Effect', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose transition animation', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'slide'     => __( 'Slide', 'shortcodes-ultimate-extra' ),
					'fade'      => __( 'Fade', 'shortcodes-ultimate-extra' ),
					'fadeUp'    => __( 'Fade Up', 'shortcodes-ultimate-extra' ),
					'backSlide' => __( 'Back Slide', 'shortcodes-ultimate-extra' ),
					'goDown'    => __( 'Go Down', 'shortcodes-ultimate-extra' ),
				),
			),
			'arrows'     => array(
				'type'    => 'select',
				'default' => 'yes',
				'name'    => __( 'Show arrows', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show left/right arrows navigation', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'no'    => __( 'Never', 'shortcodes-ultimate-extra' ),
					'hover' => __( 'On hover', 'shortcodes-ultimate-extra' ),
					'yes'   => __( 'Always', 'shortcodes-ultimate-extra' ),
				),
			),
			'pages'      => array(
				'type'    => 'select',
				'default' => 'no',
				'name'    => __( 'Show pages', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show pagination navigation', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'no'    => __( 'Never', 'shortcodes-ultimate-extra' ),
					'hover' => __( 'On hover', 'shortcodes-ultimate-extra' ),
					'yes'   => __( 'Always', 'shortcodes-ultimate-extra' ),
				),
			),
			'autoplay'   => array(
				'type'    => 'slider',
				'default' => '5',
				'min'     => '0',
				'max'     => '60',
				'step'    => '0.5',
				'name'    => __( 'Autoplay', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Specify autoplay interval (seconds). Set to 0 to disable autoplay', 'shortcodes-ultimate-extra' ),
			),
			'autoheight' => array(
				'type'    => 'bool',
				'default' => 'yes',
				'name'    => __( 'Autoheight', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Adjust slider height after each slide change', 'shortcodes-ultimate-extra' ),
			),
			'class'      => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
		'content'        => array(
			'id'     => 'content_slide',
			'number' => 3,
			'nested' => true,
		),
	)
);

su_add_shortcode(
	array(
		'id'              => 'content_slide',
		'name'            => __( 'Content slide', 'shortcodes-ultimate-extra' ),
		'type'            => 'wrap',
		'group'           => 'extra gallery',
		'required_parent' => 'content_slider',
		'desc'            => __( 'Single content slide', 'shortcodes-ultimate-extra' ),
		'icon'            => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/content_slide.svg',
		'image'           => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/content_slide.svg',
		'callback'        => 'su_shortcode_content_slide',
		'atts'            => array(
			'class' => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
		'content'         => __( 'Slide content', 'shortcodes-ultimate-extra' ),
	)
);

function su_shortcode_content_slider( $atts = null, $content = null ) {

	$atts = shortcode_atts(
		array(
			'style'      => 'default',
			'effect'     => 'slide',
			'arrows'     => 'yes',
			'pages'      => 'no',
			'autoplay'   => '5',
			'autoheight' => 'yes',
			'speed'      => '0.5',
			'class'      => '',
		),
		$atts,
		'content_slider'
	);

	su_query_asset( 'css', 'animate' );
	su_query_asset( 'css', 'owl-carousel' );
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	su_query_asset( 'js', 'jquery' );
	su_query_asset( 'js', 'owl-carousel' );
	su_query_asset( 'js', 'shortcodes-ultimate-extra' );

	$atts['autoplay'] = is_numeric( $atts['autoplay'] ) ? $atts['autoplay'] * 1000 : 0;
	$atts['speed']    = is_numeric( $atts['speed'] ) ? $atts['speed'] * 1000 : 0;

	$effects = array(
		'slide'     => array( 'false', 'false' ),
		'fade'      => array( 'fadeIn', 'fadeOut' ),
		'fadeUp'    => array( 'zoomIn', 'zoomOut' ),
		'backSlide' => array( 'zoomInRight', 'zoomOutLeft' ),
		'goDown'    => array( 'slideInDown', 'zoomOutDown' ),
	);

	if ( ! isset( $effects[ $atts['effect'] ] ) ) {
		$atts['effect'] = 'slide';
	}

	return sprintf(
		'<div class="sue-content-slider sue-content-slider-arrows-%1$s sue-content-slider-pages-%2$s sue-content-slider-style-%3$s %4$s owl-carousel" data-animatein="%5$s" data-animateout="%6$s" data-autoplay="%7$s" data-speed="%8$s" data-arrows="%9$s" data-pages="%10$s" data-autoheight="%11$s">%12$s</div>',
		$atts['arrows'],
		$atts['pages'],
		$atts['style'],
		su_get_css_class( $atts ),
		$effects[ $atts['effect'] ][0],
		$effects[ $atts['effect'] ][1],
		$atts['autoplay'],
		$atts['speed'],
		'yes' === $atts['arrows'] || 'hover' === $atts['arrows'] ? 'true' : 'false',
		'yes' === $atts['pages'] || 'hover' === $atts['pages'] ? 'true' : 'false',
		esc_attr( $atts['autoheight'] ),
		do_shortcode( $content )
	);

}

function su_shortcode_content_slide( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'class' => '',
		),
		$atts,
		'content_slide'
	);
	return '<div class="sue-content-slide sue-clearfix sue-content-wrap' . su_get_css_class( $atts ) . '">' . do_shortcode( $content ) . '</div>';
}
