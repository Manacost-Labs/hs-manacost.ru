<?php

su_add_shortcode(
	array(
		'id'       => 'icon',
		'name'     => __( 'Icon', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra content media',
		'desc'     => __( 'Fully customizable icon', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon.svg',
		'callback' => 'su_shortcode_icon',
		'atts'     => array(
			'icon'       => array(
				'type'    => 'icon',
				'default' => '',
				'name'    => __( 'Icon', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose icon shape', 'shortcodes-ultimate-extra' ),
			),
			'background' => array(
				'type'    => 'color',
				'default' => '#eeeeee',
				'name'    => __( 'Background', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon background color', 'shortcodes-ultimate-extra' ),
			),
			'color'      => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon shape color. This color be only applied to the built-in icons', 'shortcodes-ultimate-extra' ),
			),
			'text_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Text color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pick a color for icon text', 'shortcodes-ultimate-extra' ),
			),
			'size'       => array(
				'type'    => 'slider',
				'default' => '32',
				'min'     => '4',
				'max'     => '256',
				'step'    => '4',
				'name'    => __( 'Size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon size (px)', 'shortcodes-ultimate-extra' ),
			),
			'shape_size' => array(
				'type'    => 'slider',
				'default' => '16',
				'min'     => '4',
				'max'     => '256',
				'step'    => '4',
				'name'    => __( 'Shape size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Background shape size (px)', 'shortcodes-ultimate-extra' ),
			),
			'radius'     => array(
				'type'    => 'slider',
				'default' => '256',
				'min'     => '0',
				'max'     => '256',
				'step'    => '4',
				'name'    => __( 'Radius', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon background shape radius (px)', 'shortcodes-ultimate-extra' ),
			),
			'text_size'  => array(
				'type'    => 'slider',
				'default' => '16',
				'min'     => '4',
				'max'     => '80',
				'step'    => '2',
				'name'    => __( 'Text size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon text size (px)', 'shortcodes-ultimate-extra' ),
			),
			'margin'     => array(
				'default' => '0px 20px 20px 0px',
				'name'    => __( 'Margin', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s (px), [%s]', __( 'Icon margin', 'shortcodes-ultimate-extra' ), __( 'top right bottom left', 'shortcodes-ultimate-extra' ) ),
			),
			'url'        => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Icon link', 'shortcodes-ultimate-extra' ),
			),
			'target'     => array(
				'type'    => 'select',
				'default' => 'blank',
				'name'    => __( 'Link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose icon link target', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'self'  => __( 'Open link in same window/tab', 'shortcodes-ultimate-extra' ),
					'blank' => __( 'Open link in new window/tab', 'shortcodes-ultimate-extra' ),
				),
			),
			'class'      => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_icon( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'icon'       => '',
			'background' => '#eeeeee',
			'color'      => '#333333',
			'text_color' => '#333333',
			'size'       => '32',
			'shape_size' => '16',
			'radius'     => '256',
			'text_size'  => '16',
			'margin'     => '0px 20px 20px 0px',
			'url'        => '',
			'target'     => 'blank',
			'class'      => '',
		),
		$atts,
		'icon'
	);
	// Query stylesheet
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	// Prepare URL
	if ( ! $atts['url'] ) {
		// Add class
		$atts['class'] .= ' sue-icon-no-url';
		// Change URL
		$atts['url'] = 'javascript:;';
	}
	// <img> icon
	if ( strpos( $atts['icon'], '/' ) !== false ) {
		$atts['icon'] = '<img src="' . $atts['icon'] . '" alt="" width="' . $atts['size'] . '" height="' . $atts['size'] . '" style="width:' . $atts['size'] . 'px;height:' . $atts['size'] . 'px;background:' . $atts['background'] . ';-webkit-border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;border-radius:' . $atts['radius'] . 'px;padding:' . $atts['shape_size'] . 'px" />';
		// Fork-Awesome icon
	} elseif ( strpos( $atts['icon'], 'icon:' ) !== false ) {
			$atts['icon'] = '<i class="sui sui-' . trim( str_replace( 'icon:', '', $atts['icon'] ) ) . '" style="font-size:' . $atts['size'] . 'px;line-height:' . $atts['size'] . 'px;background:' . $atts['background'] . ';color:' . $atts['color'] . ';-webkit-border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;border-radius:' . $atts['radius'] . 'px;padding:' . $atts['shape_size'] . 'px"></i>';
			su_query_asset( 'css', 'su-icons' );
	}
	// Prepare text
	if ( $content ) {
		$content = '<span class="sue-icon-text">' . $content . '</span>';
	}
	// Return result
	return '<a href="' . $atts['url'] . '" class="sue-icon' . su_get_css_class( $atts ) . '" style="font-size:' . $atts['text_size'] . 'px;color:' . $atts['text_color'] . ';margin:' . $atts['margin'] . '" target="_' . $atts['target'] . '">' . $atts['icon'] . do_shortcode( $content ) . '</a>';
}
