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
		'description' => '저희 은행 계좌로 직접 결제하세요. 구독 신분증를 결제 참조로 사용하십시오. 귀하의 구독은 저희 계정에서 자금이 정리되면 활성화됩니다.',
		'instruction' => '결제 방법 안내는 여기로 이동합니다.',
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
		'name' => '학생',
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
		'name' => '연간',
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
		'name' => '월간 간행물',
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
	CATEGORIES
*/
$cat_8764d35426770ed_id = td_demo_category::add_category(array(
	'category_name' => 'Featured',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_4164d3542677197_id = td_demo_category::add_category(array(
	'category_name' => 'Uncategorized',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_4164d3542677244_id = td_demo_category::add_category(array(
	'category_name' => '건강 및 웰빙',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_9264d35426772ef_id = td_demo_category::add_category(array(
	'category_name' => '기술',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_6464d3542677399_id = td_demo_category::add_category(array(
	'category_name' => '스포츠',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_9764d354267743e_id = td_demo_category::add_category(array(
	'category_name' => '여행 및 레저',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_3464d35426774e5_id = td_demo_category::add_category(array(
	'category_name' => '오락',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_3264d3542677589_id = td_demo_category::add_category(array(
	'category_name' => '인공 지능',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_8964d3542677680_id = td_demo_category::add_category(array(
	'category_name' => '재원',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));

$cat_2064d354267774f_id = td_demo_category::add_category(array(
	'category_name' => '정치',
	'parent_id' => 0,
	'category_template' => '',
	'top_posts_style' => '',
	'description' => '',
	'background_td_pic_id' => '',
	'boxed_layout' => 'hide',
	'sidebar_id' => '',
	'tdc_layout' => '', //THE MODULE ID 1 2 3 NO NAME JUST ID 
	'tdc_sidebar_pos' => '', //sidebar_left, sidebar_right, no_sidebar 
));


/*  ---------------------------------------------------------------------------- 
	 CLOUD TEMPLATES(MODULES)
*/

/*  ---------------------------------------------------------------------------- 
	ATTACHMENTS
*/
$menu_td_demo_header_menu_id = td_demo_menus::create_menu('td-demo-header-menu', '');
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
	POSTS
*/
$post_6364d35426c6591_id = td_demo_content::add_post( array(
	'title' => '한국은 모범적인 AI 전환을 주도하고 있습니다. 방법은 다음과 같습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_7964d35426c870d_id = td_demo_content::add_post( array(
	'title' => 'Google 검색어는 국가에서 인공 지능을 사용하는 방법에 대해 많은 것을 말할 수 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'tds_lock_content' => '1',
	'tds_locker' => $post_1864d35426bee00_id,
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_9364d35426ca2e6_id = td_demo_content::add_post( array(
	'title' => '인간에 가까운 외모와 4개 국어를 구사하는 AI 기반 K팝 걸그룹',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_1464d35426cb679_id = td_demo_content::add_post( array(
	'title' => '북한 AI·AI 관련 사업 발굴 중',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_8064d35426cc7be_id = td_demo_content::add_post( array(
	'title' => '2027년까지 세계 3대 AI 강국 중 하나',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_4864d35426ce1cb_id = td_demo_content::add_post( array(
	'title' => '한국 대 북한의 단축키 인공 지능',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_3264d3542677589_id,),
));

$post_4464d35426cf43f_id = td_demo_content::add_post( array(
	'title' => '자연 기반 테라피를 통해 방문자가 숲의 차분하고 활력을 주는 분위기에 몰입할 수 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_5164d35426d0376_id = td_demo_content::add_post( array(
	'title' => '한방센터에서는 한방, 침, 부항 등의 한방치료를 제공하고 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_2064d35426d1244_id = td_demo_content::add_post( array(
	'title' => '치료적 특성과 회춘 효과에 대한 전통 찜질방의 이점',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_3564d35426d26af_id = td_demo_content::add_post( array(
	'title' => '정신 건강 문제에 초점을 맞추고 정신 건강에 대한 인식 제고',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_6164d35426d35d9_id = td_demo_content::add_post( array(
	'title' => '한국은 인구 고령화와 관련된 문제에 직면해 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_5264d35426d44cb_id = td_demo_content::add_post( array(
	'title' => '한국의료체계는 예방의학과 건강증진을 중시한다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_4164d3542677244_id,),
));

$post_2964d35426d5308_id = td_demo_content::add_post( array(
	'title' => '서울 페스타가 2023년 서울시민 축제로 돌아옵니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_9664d35426d6548_id = td_demo_content::add_post( array(
	'title' => '한국 여행은 즐거운 경험이 될 수 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_3764d35426d7672_id = td_demo_content::add_post( array(
	'title' => '불국사와 같은 고대 무덤, 사원 및 유적을 탐험하십시오',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_1164d35426d855d_id = td_demo_content::add_post( array(
	'title' => '비무장지대(DMZ) 투어를 고려해보세요',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_4264d35426d92f5_id = td_demo_content::add_post( array(
	'title' => '첫 번째 조언: 누군가의 집에 들어갈 때 신발을 벗는 것이 관례입니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_8264d35426da123_id = td_demo_content::add_post( array(
	'title' => '한국 대통령이 부르는 \"아메리칸 파이\"',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_7964d35426db024_id = td_demo_content::add_post( array(
	'title' => '한국은 훌륭한 대중교통 시스템을 갖추고 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_9764d354267743e_id,),
));

$post_1964d35426dc489_id = td_demo_content::add_post( array(
	'title' => '대한민국은 민주공화국 체제로 운영되고 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'tds_locker' => '165',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_2764d35426dd42b_id = td_demo_content::add_post( array(
	'title' => '육아휴직 정책과 여성의 일과 삶의 균형에 대한 의견이 다를 수 있음',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_6064d35426dea3c_id = td_demo_content::add_post( array(
	'title' => '한국 사회는 성역할에 대해 더 전통적이거나 보수적인 관점을 가질 수 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_564d35426df9bc_id = td_demo_content::add_post( array(
	'title' => '남북관계: 남북관계 관련 뉴스',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_4964d35426e095f_id = td_demo_content::add_post( array(
	'title' => '제1회 루마니아-한국 포럼이 화요일 부쿠레슈티에서 개최됩니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_2064d354267774f_id,),
));

$post_1764d35426e1bc3_id = td_demo_content::add_post( array(
	'title' => '토트넘, 한국과 계약 성사로 손흥민 완전 아시안 게임 방출',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_4964d35426e2d09_id = td_demo_content::add_post( array(
	'title' => 'MLB, 한국서 2024시즌 개막',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_9264d35426e3c35_id = td_demo_content::add_post( array(
	'title' => '오징어 게임: 우리는 어떤 향수를 불러일으키는 게임을 보게 될까요?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_7664d35426e4b3d_id = td_demo_content::add_post( array(
	'title' => '한국의 전통 놀이인 윷놀이(윷놀이)',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_8164d35426e5be2_id = td_demo_content::add_post( array(
	'title' => '인터뷰: 책으로 무술을 배울 수 있을까?',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_3764d35426e70c1_id = td_demo_content::add_post( array(
	'title' => '추석씨름대회 우승자 속보',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_6464d3542677399_id,),
));

$post_1464d35426e7fbf_id = td_demo_content::add_post( array(
	'title' => '가장 독특한 한국 전통춤인 부채춤은 무속 의례에서 비롯되었습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_8764d35426e8fa5_id = td_demo_content::add_post( array(
	'title' => '한국의 전통 놀이는 여전히 문화 축제와 행사에서 연주되고 즐깁니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_7864d35426ea1fb_id = td_demo_content::add_post( array(
	'title' => 'K-Dance CAMP, 올여름 런던으로 돌아온다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_1464d35426eb318_id = td_demo_content::add_post( array(
	'title' => '방탄소년단은 의심할 여지 없이 가장 인기 있는 K-Pop 그룹입니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_6164d35426ec310_id = td_demo_content::add_post( array(
	'title' => '전통 음악(국악) 춤 연극(판소리)은 계속해서 한국 문화의 소중한 측면입니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_1364d35426ed2b8_id = td_demo_content::add_post( array(
	'title' => 'K-Drama는 전 세계적으로 엄청난 인기를 얻었습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_3464d35426774e5_id,),
));

$post_2664d35426ee485_id = td_demo_content::add_post( array(
	'title' => '한국은행 금리결정 관련 소식',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_8464d35426ef415_id = td_demo_content::add_post( array(
	'title' => '대한민국 부동산 시장이 뜨거운 관심을 받고 있습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_7764d35426f02f6_id = td_demo_content::add_post( array(
	'title' => '삼성 현대 LG 및 SK 그룹은 투자자와 대중의 면밀한 모니터링을 받았습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_9',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_6764d35426f124e_id = td_demo_content::add_post( array(
	'title' => '한국의 주요 은행 금융기관 및 이들의 활동에 관한 뉴스',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_10',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_2964d35426f2280_id = td_demo_content::add_post( array(
	'title' => 'ATM을 노리는 북한 해커, 미국 정부에 경고',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_1',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_6864d35426f3405_id = td_demo_content::add_post( array(
	'title' => '한국 언론은 KOSPI 실적에 대한 업데이트를 자주 제공했습니다t',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_2',
	'categories_id_array' => array($cat_8964d3542677680_id,),
));

$post_4264d354270019c_id = td_demo_content::add_post( array(
	'title' => '한국은 5G 기술 채택 및 배포의 최전선에 있었습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_3',
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));

$post_964d3542701168_id = td_demo_content::add_post( array(
	'title' => '카카오톡은 한국의 대표적인 커뮤니케이션 플랫폼 중 하나입니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_4',
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));

$post_5864d3542702301_id = td_demo_content::add_post( array(
	'title' => '녹색기술 재생에너지사업 지속가능발전에 대한 한국의 노력이 주목을 받았습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_5',
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));

$post_2764d35427033c5_id = td_demo_content::add_post( array(
	'title' => '한국의 기술 대기업 삼성전자가 최신 플래그십 스마트폰 출시를 발표했습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_6',
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));

$post_8564d3542704305_id = td_demo_content::add_post( array(
	'title' => '정부 스타트업 육성 위해 민관합동펀드 1조원 조성',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_7',
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));

$post_1464d35427052f2_id = td_demo_content::add_post( array(
	'title' => '서울은 세계의 선도적인 디지털 도시 이자 세계의 기술 수도 로 선정되었습니다',
	'file' => 'post_default.txt',
	'featured_image_td_id' => 'td_pic_8',
	'tds_lock_content' => '1',
	'tds_locker' => $post_1864d35426bee00_id,
	'categories_id_array' => array($cat_9264d35426772ef_id,),
));


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
$menu_td_demo_footer_menu_id = td_demo_menus::create_menu('td-demo-footer-menu', ''); 


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_link( array(
	'title' => '케이 기술',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_link( array(
	'title' => '<span style="color:red;">케이 스포츠</span>',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_link( array(
	'title' => '<span style="color:blue;">케이  인공 지능</span>',
	'add_to_menu_id' => $menu_td_demo_footer_menu_id,
	'url' => '#',
	'parent_id' => ''
));


/*  ---------------------------------------------------------------------------- 
	MENUS ITEMS
*/
$menu_item_0_id = td_demo_menus::add_category( array(
	'title' => '기술',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_9264d35426772ef_id,
	'parent_id' => ''
));

$menu_item_1_id = td_demo_menus::add_category( array(
	'title' => '재원',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_8964d3542677680_id,
	'parent_id' => ''
));

$menu_item_2_id = td_demo_menus::add_category( array(
	'title' => '오락',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_3464d35426774e5_id,
	'parent_id' => ''
));

$menu_item_3_id = td_demo_menus::add_category( array(
	'title' => '스포츠',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_6464d3542677399_id,
	'parent_id' => ''
));

$menu_item_4_id = td_demo_menus::add_category( array(
	'title' => '정치',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_2064d354267774f_id,
	'parent_id' => ''
));

$menu_item_5_id = td_demo_menus::add_category( array(
	'title' => '여행 및 레저',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_9764d354267743e_id,
	'parent_id' => ''
));

$menu_item_6_id = td_demo_menus::add_category( array(
	'title' => '건강 및 웰빙',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_4164d3542677244_id,
	'parent_id' => ''
));

$menu_item_7_id = td_demo_menus::add_category( array(
	'title' => '인공 지능',
	'add_to_menu_id' => $menu_td_demo_header_menu_id,
	'category_id' => $cat_3264d3542677589_id,
	'parent_id' => ''
));


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
