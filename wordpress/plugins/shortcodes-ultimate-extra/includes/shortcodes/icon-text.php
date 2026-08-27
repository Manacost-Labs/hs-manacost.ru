<?php

su_add_shortcode(
	array(
		'id'       => 'icon_text',
		'name'     => __( 'Text with icon', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra content',
		'content'  => __( 'Content', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Text block with customizable icon', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon_text.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/icon_text.svg',
		'callback' => 'su_shortcode_icon_text',
		'atts'     => array(
			'color'      => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Text color', 'shortcodes-ultimate-extra' ),
			),
			'icon'       => array(
				'type'    => 'icon',
				'default' => 'icon: heart',
				'name'    => __( 'Icon', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select the icon for this text block', 'shortcodes-ultimate-extra' ),
			),
			'icon_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Icon color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select icon color. This color will be aplied only to built-in icons. Does not works for uploaded icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_size'  => array(
				'type'    => 'slider',
				'min'     => 10,
				'max'     => 320,
				'step'    => 1,
				'default' => 24,
				'name'    => __( 'Icon size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select icon size (px)', 'shortcodes-ultimate-extra' ),
			),
			'url'        => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can type here any hyperlink to make this panel clickable', 'shortcodes-ultimate-extra' ),
			),
			'target'     => array(
				'type'    => 'select',
				'default' => 'blank',
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

function su_shortcode_icon_text( $atts = null, $content = null ) {
	$atts              = shortcode_atts(
		array(
			'color'      => '#333333',
			'icon'       => 'icon: heart',
			'icon_color' => '#333333',
			'icon_size'  => 24,
			'url'        => '',
			'target'     => 'self',
			'class'      => '',
		),
		$atts,
		'icon_text'
	);
	$atts['icon_size'] = intval( $atts['icon_size'] );
	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}
	if ( 'self' !== $atts['target'] ) {
		$atts['target'] = 'blank';
	}
	if ( strpos( $atts['icon'], 'icon:' ) !== false ) {
		$atts['icon'] = '<i class="sui sui-' . trim( str_replace( 'icon:', '', $atts['icon'] ) ) . '" style="font-size:' . $atts['icon_size'] . 'px;color:' . $atts['icon_color'] . '"></i>';
		su_query_asset( 'css', 'su-icons' );
	} else {
		$atts['icon'] = '<img src="' . $atts['icon'] . '" style="width:' . $atts['icon_size'] . 'px" alt="" />';
	}
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	$return = '<div class="sue-icon-text' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" data-target="' . $atts['target'] . '" style="min-height:' . ( $atts['icon_size'] + 10 ) . 'px;padding-left:' . ( $atts['icon_size'] + round( $atts['icon_size'] / 2 ) ) . 'px;color:' . $atts['color'] . '"><div class="sue-icon-text-icon" style="color:' . $atts['icon_color'] . ';font-size:' . $atts['icon_size'] . 'px;width:' . $atts['icon_size'] . 'px;height:' . $atts['icon_size'] . 'px">' . $atts['icon'] . '</div><div class="sue-icon-text-content sue-content-wrap" style="color:' . $atts['color'] . '">' . do_shortcode( $content ) . '</div><div style="clear:both;height:0"></div></div>';
	return $return;
}
