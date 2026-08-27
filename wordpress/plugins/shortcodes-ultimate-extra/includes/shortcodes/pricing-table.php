<?php

su_add_shortcode(
	array(
		'id'             => 'pricing_table',
		'name'           => __( 'Pricing table', 'shortcodes-ultimate-extra' ),
		'type'           => 'wrap',
		'group'          => 'extra box',
		'required_child' => 'plan',
		'desc'           => __( 'Wrapper for pricing plans', 'shortcodes-ultimate-extra' ),
		'icon'           => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/pricing_table.svg',
		'image'          => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/pricing_table.svg',
		'callback'       => 'su_shortcode_pricing_table',
		'atts'           => array(
			'max_width' => array(
				'default' => 'none',
				'name'    => __( 'Maximum width', 'shortcodes-ultimate' ),
				'desc'    => sprintf(
					'%1$s<br>%2$s: %3$s',
					__( 'The maximum width of the table. CSS units are allowed.', 'shortcodes-ultimate' ),
					__( 'Examples', 'shortcodes-ultimate' ),
					'<b%value>none</b>, <b%value>300px</b>, <b%value>40em</b>, <b%value>90%</b>, <b%value>90vw</b>'
				),
			),
			'class'     => array(
				'default' => '',
				'name'    => __( 'Class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
			),
		),
		'content'        => array(
			'id'     => 'plan',
			'number' => 3,
			'nested' => true,
		),
	)
);

su_add_shortcode(
	array(
		'id'              => 'plan',
		'name'            => __( 'Pricing plan', 'shortcodes-ultimate-extra' ),
		'type'            => 'wrap',
		'group'           => 'extra box',
		'required_parent' => 'pricing_table',
		'desc'            => __( 'Single pricing plan', 'shortcodes-ultimate-extra' ),
		'icon'            => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/plan.svg',
		'image'           => plugin_dir_url( dirname( __FILE__ ) ) . 'images/shortcodes/plan.svg',
		'callback'        => 'su_shortcode_plan',
		'atts'            => array(
			'name'           => array(
				'default' => '',
				'name'    => __( 'Plan name', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s: <b_>%s</b>, <b_>%s</b>, <b_>%s</b>', __( 'Type here the name of this pricing plan', 'shortcodes-ultimate-extra' ), __( 'Example values', 'shortcodes-ultimate-extra' ), __( 'Starter', 'shortcodes-ultimate-extra' ), __( 'Business', 'shortcodes-ultimate-extra' ), __( 'Professional', 'shortcodes-ultimate-extra' ) ),
			),
			'price'          => array(
				'default' => '',
				'name'    => __( 'Price', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s: <b_>%s</b>, <b_>%s</b>, <b_>%s</b>', __( 'Specify the price for this plan (without currency).', 'shortcodes-ultimate-extra' ), __( 'Example values', 'shortcodes-ultimate-extra' ), __( 'Free', 'shortcodes-ultimate-extra' ), '10', '29' ),
			),
			'before'         => array(
				'default' => '',
				'name'    => __( 'Before price', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s<br>%s: %s', __( 'This text will be shown right before plan price.', 'shortcodes-ultimate-extra' ), __( 'It is a good place to add currency.', 'shortcodes-ultimate-extra' ), __( 'Example values', 'shortcodes-ultimate-extra' ), '<b_>$</b>, <b_>€</b>, <b_>¥</b>, <b_>euro</b>, <b_>dollars</b>' ),
			),
			'after'          => array(
				'default' => '',
				'name'    => __( 'After price', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s<br>%s: %s', __( 'This text will be shown right after plan price.', 'shortcodes-ultimate-extra' ), __( 'It is a good place to add currency.', 'shortcodes-ultimate-extra' ), __( 'Example values', 'shortcodes-ultimate-extra' ), '<b_>$</b>, <b_>€</b>, <b_>¥</b>, <b_>euro</b>, <b_>dollars</b>' ),
			),
			'period'         => array(
				'default' => '',
				'name'    => __( 'Period', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s: <b_>%s</b>, <b_>%s</b>, <b_>%s</b>', __( 'Specify plan period. Leave this field empty to hide this text.', 'shortcodes-ultimate-extra' ), __( 'Example values', 'shortcodes-ultimate-extra' ), __( 'weekly', 'shortcodes-ultimate-extra' ), __( 'per month', 'shortcodes-ultimate-extra' ), __( '1 year', 'shortcodes-ultimate-extra' ) ),
			),
			'featured'       => array(
				'type'    => 'bool',
				'default' => 'no',
				'name'    => __( 'Featured', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Show this plan as featured', 'shortcodes-ultimate-extra' ),
			),
			'background'     => array(
				'type'    => 'color',
				'default' => '#f9f9f9',
				'name'    => __( 'Background color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'This color will be applied to the pricing plan head (plan name, price and period area)', 'shortcodes-ultimate-extra' ),
			),
			'color'          => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Text color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'This color will be applied to the pricing plan head (plan name, price and period area)', 'shortcodes-ultimate-extra' ),
			),
			'border'         => array(
				'type'    => 'color',
				'default' => '#eeeeee',
				'name'    => __( 'Border color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pick an border color for this plan', 'shortcodes-ultimate-extra' ),
			),
			'shadow'         => array(
				'type'    => 'shadow',
				'default' => '0px 0px 25px #eeeeee',
				'name'    => __( 'Featured plan shadow', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Adjust box shadow value. Shadow will be only applied to the featured plans', 'shortcodes-ultimate-extra' ),
			),
			'icon'           => array(
				'type'    => 'icon',
				'default' => '',
				'name'    => __( 'Icon', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'You can add an icon to each pricing plan. Leave this field empty to hide icon', 'shortcodes-ultimate-extra' ),
			),
			'icon_color'     => array(
				'type'    => 'color',
				'default' => '#333333',
				'name'    => __( 'Icon color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Pick an icon color. This color will only be applied to the built-in icons', 'shortcodes-ultimate-extra' ),
			),
			'icon_size'      => array(
				'type'    => 'slider',
				'min'     => 8,
				'max'     => 256,
				'step'    => 8,
				'default' => 48,
				'name'    => __( 'Icon size', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Specify icon size (px)', 'shortcodes-ultimate-extra' ),
			),
			'btn_url'        => array(
				'default' => '',
				'name'    => __( 'Button URL', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Enter here the URL for button', 'shortcodes-ultimate-extra' ),
			),
			'btn_target'     => array(
				'type'    => 'select',
				'default' => 'self',
				'name'    => __( 'Button link target', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose button link target', 'shortcodes-ultimate-extra' ),
				'values'  => array(
					'self'  => __( 'Open link in same window/tab', 'shortcodes-ultimate-extra' ),
					'blank' => __( 'Open link in new window/tab', 'shortcodes-ultimate-extra' ),
				),
			),
			'btn_text'       => array(
				'default' => '',
				'name'    => __( 'Button text', 'shortcodes-ultimate-extra' ),
				'desc'    => sprintf( '%s<br>%s: <b_>%s</b>', __( 'Enter here the text for button.', 'shortcodes-ultimate-extra' ), __( 'Example value', 'shortcodes-ultimate-extra' ), __( 'Sign Up', 'shortcodes-ultimate-extra' ) ),
			),
			'btn_background' => array(
				'type'    => 'color',
				'default' => '#999999',
				'name'    => __( 'Button background color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose button background color', 'shortcodes-ultimate-extra' ),
			),
			'btn_color'      => array(
				'type'    => 'color',
				'default' => '#ffffff',
				'name'    => __( 'Button text color', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Choose button text color', 'shortcodes-ultimate-extra' ),
			),
			'class'          => array(
				'type'    => 'extra_css_class',
				'name'    => __( 'Extra CSS class', 'shortcodes-ultimate-extra' ),
				'desc'    => __( 'Additional CSS class name(s) separated by space(s)', 'shortcodes-ultimate-extra' ),
				'default' => '',
			),
		),
		'content'         => sprintf(
			'<ul>%2$s<li>%1$s 1</li>%2$s<li>%1$s 2</li>%2$s<li>%1$s 3</li>%2$s</ul>',
			_x( 'Option', 'Pricing plan option', 'shortcodes-ultimate-extra' ),
			"\n"
		),
	)
);

function su_shortcode_pricing_table( $atts = null, $content = null ) {

	$atts = su_parse_shortcode_atts( 'pricing_table', $atts );

	$content = trim( do_shortcode( $content ) );

	if ( empty( $content ) ) {

		return su_error_message(
			'Pricing Table',
			__( 'You need to add pricing plans to this pricing table. Use shortcode Pricing plan to do that.', 'shortcodes-ultimate-extra' )
		);

	}

	if ( is_numeric( $atts['max_width'] ) ) {
		$atts['max_width'] .= 'px';
	}

	return sprintf(
		'<div class="sue-pricing-table%s" style="max-width:%s">%s</div>',
		su_get_css_class( $atts ),
		esc_attr( $atts['max_width'] ),
		$content
	);

}

function su_shortcode_plan( $atts = null, $content = null ) {

	$atts = su_parse_shortcode_atts(
		'plan',
		$atts,
		array( 'custom_btn' => '' )
	);

	if ( $atts['icon'] ) {

		$atts['icon'] = sprintf(
			'<div class="sue-plan-icon">%s</div>',
			su_html_icon(
				array(
					'icon'       => $atts['icon'],
					'size'       => $atts['icon_size'],
					'color'      => $atts['icon_color'],
					'enqueue-fa' => true,
				)
			)
		);

	}

	if ( $atts['before'] ) {
		$atts['before'] = '<span class="sue-plan-price-before">' . $atts['before'] . '</span>';
	}
	if ( $atts['after'] ) {
		$atts['after'] = '<span class="sue-plan-price-after">' . $atts['after'] . '</span>';
	}
	if ( $atts['period'] ) {
		$atts['period'] = '<div class="sue-plan-period">' . $atts['period'] . '</div>';
	}
	if ( 'yes' === $atts['featured'] ) {
		$atts['class'] .= ' sue-plan-featured';
	}

	$atts['shadow'] = 'yes' === $atts['featured']
		? ';box-shadow:' . $atts['shadow']
		: '';

	su_query_asset( 'css', 'shortcodes-ultimate-extra' );
	su_query_asset( 'js', 'jquery' );
	su_query_asset( 'js', 'shortcodes-ultimate-extra' );

	$allowed_options_tags = apply_filters(
		'su/extra/pricing_plan/options_tags',
		'<div><br><ul><li><a><b><strong><i><em><span><img><h1><h2><h3><h4><h5><h6>'
	);

	$content = trim( strip_tags( do_shortcode( $content ), $allowed_options_tags ) );

	$button = $atts['btn_text'] && $atts['btn_url'] ? '<a href="' . $atts['btn_url'] . '" class="sue-plan-button" style="background-color:' . $atts['btn_background'] . ';color:' . $atts['btn_color'] . ';border:2px solid ' . $atts['btn_background'] . '" target="_' . $atts['btn_target'] . '">' . $atts['btn_text'] . '</a>' : '';

	$footer = $button ? '<div class="sue-plan-footer" style="background-color:' . $atts['background'] . ';color:' . $atts['btn_background'] . ';border-color:' . su_adjust_brightness( $atts['border'], 40 ) . '">' . $button . '</div>' : '';

	if ( $atts['custom_btn'] ) {

		$footer = sprintf(
			'<div class="sue-plan-footer" style="background-color:%s;border-color:%s">%s</div>',
			$atts['background'],
			$atts['border'],
			su_do_attribute( $atts['custom_btn'] )
		);

	}

	return '<div class="sue-plan' . su_get_css_class( $atts ) . '" style="border-color:' . $atts['border'] . $atts['shadow'] . '"><div class="sue-plan-head" style="background-color:' . $atts['background'] . ';color:' . $atts['color'] . ';border-color:' . su_adjust_brightness( $atts['border'], 40 ) . '"><div class="sue-plan-name">' . $atts['name'] . '</div><div class="sue-plan-price">' . $atts['before'] . '<span class="sue-plan-price-value">' . $atts['price'] . '</span>' . $atts['after'] . '</div>' . $atts['period'] . $atts['icon'] . '</div><div class="sue-plan-options">' . $content . '</div>' . $footer . '</div>';

}
