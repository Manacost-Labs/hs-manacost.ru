<?php 



/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - start phase 1
*/
global $wpdb;
$disable_wizard = $wpdb->get_var( "SELECT value FROM tds_options WHERE name = 'disable_wizard'");
if ( empty($disable_wizard) ) {

td_demo_subscription::add_account_details( array(
		'company_name' => 'Demo Company',
		'billing_cui' => '75864589',
		'billing_j' => '10/120/2021',
		'billing_address' => '2656 Farm Meadow Drive',
		'billing_city' => 'Tucson',
		'billing_country' => 'Arizona',
		'billing_email' => 'yourcompany@website.com',
		'billing_bank_account' => 'NL43INGB4186520410',
		'billing_post_code' => '85712',
		'billing_vat_number' => '75864589',
		'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
	)
);

td_demo_subscription::add_payment_bank( array(
		'account_name' => 'Alpha Bank Account',
		'account_number' => '123456',
		'bank_name' => 'Alpha Bank',
		'routing_number' => '123456',
		'iban' => 'NL43INGB4186520410',
		'bic_swift' => '123456',
		'description' => 'Make your payment directly into our bank account. Please use your Subscription ID as the payment reference. Your subscription will be activated when the funds are cleared in our account.',
		'instruction' => 'Payment method instructions go here.',
		'is_active' => '1',
		'options' => 'a:1:{s:15:"td_demo_content";i:1;}',
	)
);

td_demo_subscription::add_option( array(
		'name' => 'td_demo_content',
		'value' => '1',
	)
);

}

$plan_5964d354266ef63_id = td_demo_subscription::add_plan( array(
		'name' => '??',
		'price' => '16.68',
		'months_in_cycle' => '1',
		'trial_days' => '30',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"2664d354266ef59";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_364d354266f08a_id = td_demo_subscription::add_plan( array(
		'name' => '?? ?? ?? ??',
		'price' => '24.99',
		'months_in_cycle' => '12',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"8364d354266f086";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$plan_2764d354266f14a_id = td_demo_subscription::add_plan( array(
		'name' => '?? ???',
		'price' => '29.99',
		'months_in_cycle' => '1',
		'trial_days' => '0',
		'is_free' => '0',
		'options' => 'a:2:{s:15:"td_demo_content";i:1;s:9:"unique_id";s:15:"9664d354266f147";}',
		'publishing_limits' => 'a:0:{}',
		'automatic_delistings' => 'a:0:{}',
	)
);

$page_1064d354266f5ac_id = td_demo_content::add_page( array(
	'title' => 'Checkout - korean_news_insight',
	'file' => 'tds_checkout.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'payment_page_id',
	'value' => $page_1064d354266f5ac_id,
	)
);

$page_2664d354267119a_id = td_demo_content::add_page( array(
	'title' => 'My Account - korean_news_insight',
	'file' => 'tds_my_account.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'my_account_page_id',
	'value' => $page_2664d354267119a_id,
	)
);

$page_3664d354267238b_id = td_demo_content::add_page( array(
	'title' => 'Login/Register - korean_news_insight',
	'file' => 'tds_login_register.txt',
));

td_demo_subscription::add_option( array(
	'name' => 'create_account_page_id',
	'value' => $page_3664d354267238b_id,
	)
);

td_demo_subscription::add_option( array(
	'name' => 'go_wizard',
	'value' => '1',
	)
);

td_demo_subscription::add_option( array(
	'name' => 'wizard_company_complete',
	'value' => '1',
	)
);

td_demo_subscription::add_option( array(
	'name' => 'wizard_payments_complete',
	'value' => '1',
	)
);

td_demo_subscription::add_option( array(
	'name' => 'wizard_plans_complete',
	'value' => '1',
	)
);

td_demo_subscription::add_option( array(
	'name' => 'wizard_locker_complete',
	'value' => '1',
	)
);

td_demo_subscription::add_option( array(
	'name' => 'disable_wizard',
	'value' => '1',
	)
);


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 1
*/

/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/
/*  ---------------------------------------------------------------------------- 
	PAGES
*/
$page_5264d35426b940b_id = td_demo_content::add_page( array(
    'title' => '모달 가입',
    'file' => 'modal_sign_up.txt',
    'demo_unique_id' => '1964d35426b9411',
));

$page_2064d35426ba7f3_id = td_demo_content::add_page( array(
    'title' => '모달 홈페이지',
    'file' => 'modal_homepage.txt',
    'demo_unique_id' => '7764d35426ba7f8',
));

$page_4464d35426b8573_id = td_demo_content::add_page( array(
	'title' => '모달 모바일 메뉴',
	'file' => 'modal_mobile_menu.txt',
	'demo_unique_id' => '1164d35426b8578',
));

$page_2964d35426bb77e_id = td_demo_content::add_page( array(
	'title' => '요금제 전환 마법사',
	'file' => 'tds_switching_plans_wizard.txt',
	'demo_unique_id' => '5664d35426bb783',
));

$page_6464d35426bd8b7_id = td_demo_content::add_page( array(
	'title' => '집',
	'file' => 'Home.txt',
	'homepage' => true,
	'demo_unique_id' => '4264d35426bd8bb',
));


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - start phase 2
*/

/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTIONS
*/
// add locker
$post_1864d35426bee00_id = td_demo_content::add_post( array(
	'post_type' => 'tds_locker',
	'title' => 'Wizard Locker (default)',
	'file' => '',
	'categories_id_array' => [],
			'tds_locker_settings' => array(
				'tds_title' => '이 콘텐츠는 구독자 전용입니다.',
				'tds_message' => '이 콘텐츠를 잠금 해제하려면 구독하세요.',
				'tds_submit_btn_text' => '잠금 해제 구독',
				'tds_pp_msg' => '<a href=\"#\">이용약관</a> 및 <a href=\"#\">개인정보 보호정책</a>에 따라 내 데이터를 처리하는 데 동의합니다.',
				'tds_locker_cf_1_name' => 'Custom field 1',
				'tds_locker_cf_2_name' => 'Custom field 2',
				'tds_locker_cf_3_name' => 'Custom field 3',
			),
	'tds_payable' => 'paid_subscription',
	'tds_paid_subs_page_id' => $page_2964d35426bb77e_id,
	'tds_paid_subs_plan_ids' => [$plan_5964d354266ef63_id,$plan_364d354266f08a_id,$plan_2764d354266f14a_id],
			'tds_locker_styles' => array(
				'all_tds_border' => '2',
				'all_tds_border_color' => 'rgba(0,0,0,0.29)',
				'tds_title_color' => '#000000',
				'tds_submit_btn_text_color' => '#ffffff',
				'tds_submit_btn_text_color_h' => '#ffffff',
				'tds_submit_btn_bg_color' => '#170df9',
				'tds_submit_btn_bg_color_h' => '#ef0c0c',
				'tds_after_btn_text_color' => '#0a0a0a',
				'tds_pp_checked_color' => '#170df9',
				'tds_pp_check_bg' => '#ffffff',
				'tds_pp_check_bg_f' => '#ffffff',
				'tds_pp_check_border_color' => 'rgba(0,0,0,0.29)',
				'tds_pp_check_border_color_f' => '#170df9',
				'tds_pp_msg_color' => '#000000',
				'tds_pp_msg_links_color' => '#170df9',
				'tds_pp_msg_links_color_h' => '#ef0c0c',
				'tds_general_font_family' => 'global-font-1_global',
				'tds_title_font_family' => 'global-font-1_global',
				'tds_message_font_family' => 'global-font-1_global',
				'tds_submit_btn_text_font_family' => 'global-font-1_global',
				'tds_after_btn_text_font_family' => 'global-font-1_global',
				'tds_pp_msg_font_family' => 'global-font-1_global',
			),
	)
);

// add post meta for default locker
td_demo_content::add_locker_meta( array(
	'tds_locker_id' => (int) get_option( 'tds_default_locker_id' ),
	'tds_locker_meta' => array(
			'tds_locker_settings' => array(
				'tds_title' => 'This Content Is Only For Subscribers',
				'tds_message' => 'Please subscribe to unlock this content. Enter your email to get access.',
				'tds_input_placeholder' => 'Please enter your email address.',
				'tds_submit_btn_text' => 'Subscribe to unlock',
				'tds_after_btn_text' => 'Your email address is 100% safe from spam!',
				'tds_pp_msg' => 'I consent to processing of my data according to <a href=\"#\">Terms of Use</a> & <a href=\"#\">Privacy Policy</a>',
			),
		)
	)
);

td_util::update_option('tds_demo_options', 'a:1:{s:5:"plans";a:3:{i:0;a:2:{s:9:"unique_id";s:15:"2664d354266ef59";s:4:"name";s:2:"??";}i:1;a:2:{s:9:"unique_id";s:15:"8364d354266f086";s:4:"name";s:11:"?? ?? ?? ??";}i:2;a:2:{s:9:"unique_id";s:15:"9664d354266f147";s:4:"name";s:6:"?? ???";}}}');


/*  ---------------------------------------------------------------------------- 
	SUBSCRIPTION - end phase 2
*/


/*  ---------------------------------------------------------------------------- 
	PRODUCTS
*/

/*  ---------------------------------------------------------------------------- 
	TAXONOMIES
*/

/*  ---------------------------------------------------------------------------- 
	CPTs
*/

/*  ---------------------------------------------------------------------------- 
	MENUS
*/




/*  ---------------------------------------------------------------------------- 
	CLOUD TEMPLATES
*/
$template_5964d354271566f_id = td_demo_content::add_cloud_template( array(
	'title' => 'Tag Template',
	'file' => 'tag_cloud_template.txt',
	'template_type' => 'tag',
));

td_demo_misc::update_global_tag_template( 'tdb_template_' . $template_5964d354271566f_id );


$template_8964d35427165da_id = td_demo_content::add_cloud_template( array(
	'title' => 'Date Template',
	'file' => 'date_cloud_template.txt',
	'template_type' => 'date',
));

td_demo_misc::update_global_date_template( 'tdb_template_' . $template_8964d35427165da_id );


$template_4664d3542717623_id = td_demo_content::add_cloud_template( array(
	'title' => 'Search Template',
	'file' => 'search_cloud_template.txt',
	'template_type' => 'search',
));

td_demo_misc::update_global_search_template( 'tdb_template_' . $template_4664d3542717623_id );


$template_6664d3542718441_id = td_demo_content::add_cloud_template( array(
	'title' => 'Author Template',
	'file' => 'author_cloud_template.txt',
	'template_type' => 'author',
));

td_demo_misc::update_global_author_template( 'tdb_template_' . $template_6664d3542718441_id );


$template_5964d35427191f0_id = td_demo_content::add_cloud_template( array(
	'title' => 'Category Template',
	'file' => 'cat_cloud_template.txt',
	'template_type' => 'category',
));

td_demo_misc::update_global_category_template( 'tdb_template_' . $template_5964d35427191f0_id );


$template_6964d3542719fa4_id = td_demo_content::add_cloud_template( array(
	'title' => 'Single Template',
	'file' => 'post_cloud_template.txt',
	'template_type' => 'single',
));

td_util::update_option( 'td_default_site_post_template', 'tdb_template_' . $template_6964d3542719fa4_id );


$template_964d354271ae84_id = td_demo_content::add_cloud_template( array(
	'title' => '404 Template',
	'file' => '404_cloud_template.txt',
	'template_type' => '404',
));

td_demo_misc::update_global_404_template( 'tdb_template_' . $template_964d354271ae84_id );


$template_8164d354271c12d_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Global',
	'file' => 'header_template_global_cloud_template.txt',
	'template_type' => 'header',
));

td_demo_misc::update_global_header_template( 'tdb_template_' . $template_8164d354271c12d_id );


$template_9964d354271cbea_id = td_demo_content::add_cloud_template( array(
	'title' => 'Footer Template',
	'file' => 'footer_template_cloud_template.txt',
	'template_type' => 'footer',
));

td_demo_misc::update_global_footer_template( 'tdb_template_' . $template_9964d354271cbea_id );


$template_1864d354271dd62_id = td_demo_content::add_cloud_template( array(
	'title' => 'Header Template - Homepage',
	'file' => 'header_template_homepage_cloud_template.txt',
	'template_type' => 'header',
));


/*  ---------------------------------------------------------------------------- 
	GENERAL SETTINGS
*/
td_demo_misc::update_background('', false);

td_demo_misc::update_background_mobile('');

td_demo_misc::update_background_login('');

td_demo_misc::update_background_header('');

td_demo_misc::update_background_footer('');

td_demo_misc::update_footer_text('');

td_demo_misc::update_logo(array('normal' => '','retina' => '','mobile' => '',));

td_demo_misc::update_footer_logo(array('normal' => '','retina' => '',));

td_demo_misc::add_social_buttons(array());

$generated_css = td_css_generator(); 
if ( function_exists('tdsp_css_generator') ) { 
	$generated_css .= tdsp_css_generator();
}
td_util::update_option( 'tds_user_compile_css', $generated_css );

// cloud templates metas
td_demo_content::update_meta( $template_964d354271ae84_id, 'tdc_header_template_id', $template_8164d354271c12d_id );

td_demo_content::update_meta( $template_8164d354271c12d_id, 'tdc_header_template_id', $template_8164d354271c12d_id );

td_demo_content::update_meta( $template_9964d354271cbea_id, 'tdc_footer_template_id', $template_9964d354271cbea_id );

td_demo_content::update_meta( $template_1864d354271dd62_id, 'tdc_header_template_id', $template_1864d354271dd62_id );

// pages metas
td_demo_content::update_meta( $page_6464d35426bd8b7_id, 'tdc_header_template_id', $template_1864d354271dd62_id );
