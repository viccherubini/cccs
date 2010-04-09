<?php

/**
 * page_header.php
 * This file is included on every page and contains the code to
 * set up common attributes of the website such as the stylesheet,
 * header graphic, menu, and login area.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	$style = load_style();
	
	if ( empty($style) ) {
		$style_font = $dbconfig['style_font'];
		$style_font_size = $dbconfig['style_font_size'];
		$style_color = $dbconfig['style_color'];
	} else {
		$style_font = $style['user_style_font'];
		$style_font_size = $style['user_style_font_size'];
		$style_color = $style['user_style_color'];
	}
} else {
	$style_font = $dbconfig['style_font'];
	$style_font_size = $dbconfig['style_font_size'];
	$style_color = $dbconfig['style_color'];
}

// Load up and print the stylesheet and starting HTML tags
$t->set_template( load_template('overall_header') );
$t->set_vars( array(
	'CONFIG_SITE_TITLE' => $dbconfig['site_title'],
	'CONFIG_STYLE_FONT' => $style_font,
	'CONFIG_STYLE_FONT_SIZE' => $style_font_size,
	'CONFIG_STYLE_COLOR' => $style_color,
	'CONFIG_STYLE_PUBLIC_EVENT' => $dbconfig['style_public_event'],
	'CONFIG_STYLE_PRIVATE_EVENT' => $dbconfig['style_private_event'],
	'PAGE_NAME' => $page_name,
	)
);
print $t->parse($dbconfig['show_template_name']);

// Make the main header graphic
$t->set_template( load_template('main_header') );
$t->set_vars( array(
	'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
	'CONFIG_TITLE_IMAGE' => $dbconfig['title_image'],
	'L_WELCOME_TEXT' => $lang['Welcome_to_cfe']
	)
);
$main_header = $t->parse($dbconfig['show_template_name']);

// Determine what type of navigation to show the user
if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	// They are logged in, thus show them the Control Panel template
	$t->set_template( load_template('logged_in_form') );
	$t->set_vars( array(
		'L_CONTROL_PANEL' => $lang['Control_panel'],
		'L_LOGGED_IN' => $lang['Logged_in'],
		'L_LOGOUT' => $lang['Logout']
		)
	);
	$login_form = $t->parse($dbconfig['show_template_name']);
} else {
	// They are not logged in, show them the login form
	$t->set_template( load_template('login_form') );
	$t->set_vars( array(
		'L_LOGIN' => $lang['Login'],
		'L_USERNAME' => $lang['Username'],
		'L_PASSWORD' => $lang['Password'],
		'L_RETRIEVE_PASSWORD' => $lang['Retrieve_password'],
		'L_CLICK_TO_REGISTER' => $lang['Click_to_register'],
		'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory']
		)
	);
	$login_form = $t->parse($dbconfig['show_template_name']);
}

// Create the navigation bar and sponsors list, it's always going to be there
$sponsor_list = NULL;
$company_list = NULL;
$sql = "SELECT * FROM `" . SPONSOR . "` s 
		WHERE s.sponsor_isvisible = '1' 
		ORDER BY s.sponsor_sortorder ASC";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_sponsor'], __LINE__, __FILE__, $db->dberror(), $sql);

while ( $sponsor = $db->getarray($result) ) {
	// make $sponsor_list here
	$t->set_template( load_template('image_plain') );
	$t->set_vars( array(
		'IMAGE_LOCATION' => $sponsor['sponsor_image'],
		'IMAGE_DESCRIPTION' => $sponsor['sponsor_name'],
		'IMAGE_STYLE' => NULL
		)
	);
	
	if ( $sponsor['sponsor_iscompany'] == 1 ) {
		$company_list .= $t->parse();
	} else {
		$sponsor_list .= $t->parse();
	}
}

$db->freeresult($result);

$sponsor_list = make_content($sponsor_list);
$company_list = make_content($company_list);

$t->set_template( load_template('main_body_navigation') );
$t->set_vars( array(
	'L_SITE_NAVIGATION' => $lang['Site_navigation'],
	'L_MENU_HOME' => $lang['Menu_home'],
	'L_MENU_ABOUT_US' => $lang['Menu_about_us'],
	'L_MENU_EDUCATION' => $lang['Menu_education'],
	'L_MENU_ONLINE_COURSES' => $lang['Menu_online_courses'],
	'L_MENU_FINANCIAL_EDUCATION_RESOURCES' => $lang['Menu_financial_education_resources'],
	'L_MENU_DONATION' => $lang['Menu_donation'],
	'L_MENU_VOLUNTEER_PROGRAM' => $lang['Menu_volunteer_program'],
	'L_MENU_DEBTOR_EDUCATION_COURSES' => $lang['Menu_bankruptcy_education_courses'],
	'L_MENU_SITEMAP' => $lang['Menu_sitemap'],
	'L_MENU_CMMV_BUSINESS_CENTER' => $lang['Menu_cmmv_business_center'],
	'L_MENU_CMMV_CERTIFICATION' => $lang['Menu_cmmv_certification'],
	'L_MAKE_A_DIFFERENCE' => $lang['Make_a_difference'],
	'L_OUR_COMPANIES' => $lang['Our_companies'],
	'L_SPONSORS' => $lang['Sponsors'],
	'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
	'LOGIN' => $login_form,
	'COMPANY_LIST' => $company_list,
	'SPONSOR_LIST' => $sponsor_list
	)
);
$main_body_navigation = $t->parse($dbconfig['show_template_name']);

/*
$global_region_list = NULL;
$region_ids = array();
$region_names = array();
get_regions($region_ids, $region_names);

$breaker = 1;
$break_at = 6;

$num_ids = count($region_ids);
for ( $i=0; $i<$num_ids; $i++ ) {
	$t->set_template( load_template('link') );
	$t->set_vars( array(
		'LINK_URL' => 'region.php?regionid=' . $region_ids[$i],
		'LINK_CLASS' => NULL,
		'LINK_TEXT' => $region_names[$i]
		)
	);
	
	if ( ($i == $num_ids-1) || ( $breaker % $break_at == 0 ) ) {
		$global_region_list .= $t->parse($dbconfig['show_template_name']);
	} else {
		$global_region_list .= $t->parse($dbconfig['show_template_name']) . ' - ';
	}
	
	if ( $breaker % $break_at == 0 ) {
		$global_region_list .= '<br />';
		$breaker = 1;
	}
	$breaker++;
}
*/

// this must be the last line of the file
define('CCCS_PAGE_HEADER', true, false);
?>