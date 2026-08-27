<?php

su_add_shortcode(
	array(
		'id'       => 'member',
		'name'     => __( 'Member', 'shortcodes-ultimate-extra' ),
		'type'     => 'wrap',
		'group'    => 'extra box content',
		'content'  => __( 'Type here some info about this team member', 'shortcodes-ultimate-extra' ),
		'desc'     => __( 'Team member', 'shortcodes-ultimate-extra' ),
		'icon'     => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/member.svg',
		'image'    => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/member.svg',
		'callback' => 'su_shortcode_member',
		'atts'     => array(
			'background'   => array(
				'type'    => 'color',
				'default' => '#ffffff',
				'name'    => __( 'Background', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel background color', 'shortcodes-ultimate-extra' ),
			),
			'color'        => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel text color', 'shortcodes-ultimate-extra' ),
			),
			'border'       => array(
				'type'    => 'border',
				'default' => '1px solid #cccccc',
				'name'    => __( 'Border', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border', 'shortcodes-ultimate-extra' ),
			),
			'shadow'       => array(
				'type'    => 'shadow',
				'default' => '0 1px 2px #eeeeee',
				'name'    => __( 'Shadow', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel shadow', 'shortcodes-ultimate-extra' ),
			),
			'radius'       => array(
				'type'    => 'slider',
				'min'     => 0,
				'max'     => 60,
				'step'    => 1,
				'default' => 0,
				'name'    => __( 'Border radius', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Panel border radius (px)', 'shortcodes-ultimate-extra' ),
			),
			'text_align'   => array(
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
			'photo'        => array(
				'type'    => 'upload',
				'default' => 'http://lorempixel.com/400/300/business/',
				'name'    => __( 'Photo', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Select the photo for this member', 'shortcodes-ultimate-extra' ),
			),
			'name'         => array(
				'default' => __( 'John Doe', 'shortcodes-ultimate-extra' ),
				'name'    => __( 'Name', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Member name', 'shortcodes-ultimate-extra' ),
			),
			'role'         => array(
				'default' => __( 'Designer', 'shortcodes-ultimate-extra' ),
				'name'    => __( 'Role', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Member role', 'shortcodes-ultimate-extra' ),
			),
			'icon_1'       => array(
				'type'    => 'icon',
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d', 'shortcodes-ultimate-extra' ), 1 ),
				'desc'    => __( 'Select social icon for this member', 'shortcodes-ultimate-extra' ),
			),
			'icon_1_url'   => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d URL', 'shortcodes-ultimate-extra' ), 1 ),
				'desc'    => __( 'Enter here social profile URL', 'shortcodes-ultimate-extra' ),
			),
			'icon_1_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d color', 'shortcodes-ultimate-extra' ), 1 ),
				'desc'    => __( 'Choose color for this icon. This color will only be applied to the built-in icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_1_title' => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d title', 'shortcodes-ultimate-extra' ), 1 ),
				'desc'    => __( 'This text will be shown as icon tooltip', 'shortcodes-ultimate-extra' ),
			),
			'icon_2'       => array(
				'type'    => 'icon',
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d', 'shortcodes-ultimate-extra' ), 2 ),
				'desc'    => __( 'Select social icon for this member', 'shortcodes-ultimate-extra' ),
			),
			'icon_2_url'   => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d URL', 'shortcodes-ultimate-extra' ), 2 ),
				'desc'    => __( 'Enter here social profile URL', 'shortcodes-ultimate-extra' ),
			),
			'icon_2_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d color', 'shortcodes-ultimate-extra' ), 2 ),
				'desc'    => __( 'Choose color for this icon. This color will only be applied to the built-in icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_2_title' => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d title', 'shortcodes-ultimate-extra' ), 2 ),
				'desc'    => __( 'This text will be shown as icon tooltip', 'shortcodes-ultimate-extra' ),
			),
			'icon_3'       => array(
				'type'    => 'icon',
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d', 'shortcodes-ultimate-extra' ), 3 ),
				'desc'    => __( 'Select social icon for this member', 'shortcodes-ultimate-extra' ),
			),
			'icon_3_url'   => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d URL', 'shortcodes-ultimate-extra' ), 3 ),
				'desc'    => __( 'Enter here social profile URL', 'shortcodes-ultimate-extra' ),
			),
			'icon_3_color' => array(
				'type'    => 'color',
				'default' => '#333333',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d color', 'shortcodes-ultimate-extra' ), 3 ),
				'desc'    => __( 'Choose color for this icon. This color will only be applied to the built-in icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_3_title' => array(
				'default' => '',
				// Translators: %d – icon number
				'name'    => sprintf( __( 'Icon %d title', 'shortcodes-ultimate-extra' ), 3 ),
				'desc'    => __( 'This text will be shown as icon tooltip', 'shortcodes-ultimate-extra' ),
			),
			'url'          => array(
				'default' => '',
				'name'    => __( 'URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can type here any hyperlink to make this panel clickable', 'shortcodes-ultimate-extra' ),
			),
			'class'        => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
	)
);

function su_shortcode_member( $atts = null, $content = null ) {
	$atts = shortcode_atts(
		array(
			'background'   => '#ffffff',
			'color'        => '#333333',
			'shadow'       => '0 1px 2px #eeeeee',
			'border'       => '1px solid #cccccc',
			'radius'       => '0',
			'text_align'   => 'left',
			'photo'        => 'http://lorempixel.com/400/300/business/' . wp_rand( 0, 10 ) . '/',
			'name'         => __( 'John Doe', 'shortcodes-ultimate-extra' ),
			'role'         => __( 'Designer', 'shortcodes-ultimate-extra' ),
			'icon_1'       => '',
			'icon_1_url'   => '',
			'icon_1_color' => '#333333',
			'icon_1_title' => '',
			'icon_2'       => '',
			'icon_2_url'   => '',
			'icon_2_color' => '#333333',
			'icon_2_title' => '',
			'icon_3'       => '',
			'icon_3_url'   => '',
			'icon_3_color' => '#333333',
			'icon_3_title' => '',
			'url'          => '',
			'class'        => '',
		),
		$atts,
		'member'
	);
	if ( $atts['url'] ) {
		$atts['class'] .= ' sue-panel-clickable';
		su_query_asset( 'js', 'shortcodes-ultimate-extra' );
	}
	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	$icons = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		if ( ! $atts[ 'icon_' . $i ] || ! $atts[ 'icon_' . $i . '_url' ] ) {
			continue;
		}
		if ( strpos( $atts[ 'icon_' . $i ], 'icon:' ) !== false ) {
			$icon = '<i class="sui sui-' . trim( str_replace( 'icon:', '', $atts[ 'icon_' . $i ] ) ) . '" style="color:' . $atts[ 'icon_' . $i . '_color' ] . '"></i>';
			su_query_asset( 'css', 'su-icons' );
		} else {
			$icon = '<img src="' . $atts[ 'icon_' . $i ] . '" width="16" height="16" alt="" />';
		}
		$icons[] = '<a href="' . $atts[ 'icon_' . $i . '_url' ] . '" title="' . $atts[ 'icon_' . $i . '_title' ] . '" class="sue-memeber-icon" target="_blank">' . $icon . '</a>';
	}
	$icons = ( count( $icons ) ) ? '<div class="sue-member-icons" style="text-align:' . $atts['text_align'] . ';border-top:' . $atts['border'] . '">' . implode( '', $icons ) . '</div>' : '';
	return '<div class="sue-member' . su_get_css_class( $atts ) . '" data-url="' . $atts['url'] . '" style="background-color:' . $atts['background'] . ';color:' . $atts['color'] . ';border-radius:' . $atts['radius'] . 'px;-moz-border-radius:' . $atts['radius'] . 'px;-webkit-border-radius:' . $atts['radius'] . 'px;box-shadow:' . $atts['shadow'] . ';-moz-box-shadow:' . $atts['shadow'] . ';-webkit-box-shadow:' . $atts['shadow'] . ';border:' . $atts['border'] . '"><div class="sue-member-photo"><img src="' . $atts['photo'] . '" alt="" style="-webkit-border-top-left-radius:' . $atts['radius'] . 'px;-webkit-border-top-right-radius:' . $atts['radius'] . 'px;-moz-border-radius-topleft:' . $atts['radius'] . 'px;-moz-border-radius-topright:' . $atts['radius'] . 'px;border-top-left-radius:' . $atts['radius'] . 'px;border-top-right-radius:' . $atts['radius'] . 'px;" /></div><div class="sue-member-info" style="text-align:' . $atts['text_align'] . '"><span class="sue-member-name">' . $atts['name'] . '</span><span class="sue-member-role">' . $atts['role'] . '</span><div class="sue-member-desc sue-content-wrap">' . do_shortcode( $content ) . '</div></div>' . $icons . '</div>';
}
