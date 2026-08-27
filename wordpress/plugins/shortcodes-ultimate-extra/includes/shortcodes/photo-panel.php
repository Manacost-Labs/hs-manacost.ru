<?php

su_add_shortcode(
	array(
		'id'       => 'photo_panel',
		'name'     => __( 'Photo panel', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box',
		'content'  => __( 'Panel content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Colorful box with image', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/photo_panel.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/photo_panel.svg',
		'callback' => 'su_shortcode_photo_panel',
		'atts'     => array(
			'background' => array(
				'type'    => 'color',
				'default' => '#ffffff',
				'name'    => __( 'Background', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel background color', 'shortcodes-ultimate-extra' ),
			),
			'color'      => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel text color', 'shortcodes-ultimate-extra' ),
			),
			'border'     => array(
				'type'    => 'border',
				'default' => '1px solid #cccccc',
				'name'    => __( 'Border', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border', 'shortcodes-ultimate-extra' ),
			),
			'shadow'     => array(
				'type'    => 'shadow',
				'default' => '0 1px 2px #eeeeee',
				'name'    => __( 'Shadow', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel shadow', 'shortcodes-ultimate-extra' ),
			),
			'radius'     => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 60,
				'step'    => 1,
				'default' => 0,
				'name'    => __( 'Border radius', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border radius (px)', 'shortcodes-ultimate-extra' ),
			),
			'text_align' => array(
				'type'    => 'select',
				'default' => 'left',
				'values'  => array(
					'left'   => __( 'Left', 'shortcodes-ultimate-extra' ),
					'center' => __( 'Center', 'shortcodes-ultimate-extra' ),
					'right'  => __( 'Right', 'shortcodes-ultimate-extra' ),
				),
				'name'    => __( 'Text align', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Text alignment for panel content', 'shortcodes-ultimate-extra' ),
			),
			'photo'      => array(
				'type'    => 'upload',
				'default' => 'http://lorempixel.com/400/300/food/',
				'name'    => __( 'Photo', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select the photo for this panel', 'shortcodes-ultimate-extra' ),
			),
			'alt'        => array(
				'default' => '',
				'name'    => __( 'Alternative image text', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Alternative image text (alt attribute)', 'shortcodes-ultimate-extra' ),
			),
			'url'        => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can type here any hyperlink to make this panel clickable', 'shortcodes-ultimate-extra' ),
			),
			'target'     => array(
				'type'    => 'select',
				'default' => 'self',
				'name'    => __( 'Link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose link target', 'shortcodes-ultimate-extra' ),
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

function su_shortcode_photo_panel( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'background' => '#ffffff',
			'color'      => '#333333',
			'shadow'     => '0 1px 2px #eeeeee',
			'border'     => '1px solid #cccccc',
			'radius'     => '0',
			'text_align' => 'left',
			'photo'      => 'http://lorempixel.com/400/300/food/' . wp_rand( 0, 10 ) . '/',
			'alt'        => '',
			'url'        => '',
			'target'     => 'self',
			'class'      => '',
		),
		$atts,
		'photo_panel'
	);
	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	$return = '<div class="sue-photo-panel' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" data-target="' . sanitize_key( $atts['target'] ) . '" style="background-color:' . $atts['background'] . ';color:' . $atts['color'] . ';border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;-webkit-border-radius:' . $atts['radius'] . 'px;box-shadow:' . $atts['shadow'] . ';-moz-box-shadow:' . $atts['shadow'] . ';-webkit-box-shadow:' . $atts['shadow'] . ';border:' . $atts['border'] . '"><div class="sue-photo-panel-head"><img src="' . $atts['photo'] . '" alt="' . $atts['alt'] . '" style="-webkit-border-top-left-radius:' . $atts['radius'] . 'px;-webkit-border-top-right-radius:' . $atts['radius'] . 'px;-moz-border-radius-topleft:' . $atts['radius'] . 'px;-moz-border-radius-topright:' . $atts['radius'] . 'px;border-top-left-radius:' . $atts['radius'] . 'px;border-top-right-radius:' . $atts['radius'] . 'px;" /></div><div class="sue-photo-panel-content sue-content-wrap" style="text-align:' . $atts['text_align'] . '">' . do_shortcode( $content ) . '</div></div>';
	return $return;
}
