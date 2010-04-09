<?php

/**
 * register.php
 * Allows a new potential volunteer to register for the website.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

$incoming = collect_vars($_REQUEST, array('do' => MIXED));
extract($incoming);

// Redirect them before including page_header so we don't get any errors
if ( $do == 'decline' ) {
	header('Location: ' . PAGE_INDEX);
}

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_register'], false);
$content_subtitle = NULL;

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Register']);

if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	cccs_message(WARNING_MESSAGE, $lang['Error_user_logged_in']);
}

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'accept' ) {
		// Get region ID's and names for the Region List	
		$r_ids = array();
		$r_names = array();
				
		get_regions($r_ids, $r_names, false);
	
		array_unshift($r_ids, NULL);
		array_unshift($r_names, NULL);
		
		// Make the drop down lists
		$user_title_list = make_drop_down('user_title', $lang['Control_panel_user_titles'], $lang['Control_panel_user_titles']);
		$user_region_list = make_drop_down('user_region_id', $r_ids, $r_names, NULL, NULL, 'id="required"');
		
		$content = make_content($lang['Register_text']);
		
		$t->set_template( load_template('register_form') );
		$t->set_vars( array(
			'L_CONTACT_INFORMATION' => $lang['Register_contact_information'],
			'L_TITLE' => $lang['Control_panel_user_title'],
			'L_FIRST_NAME' => $lang['Control_panel_first_name'],
			'L_LAST_NAME' => $lang['Control_panel_last_name'],
			'L_MAILING_ADDRESS_ONE' => $lang['Control_panel_mailing_address_one'],
			'L_MAILING_ADDRESS_TWO' => $lang['Control_panel_mailing_address_two'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_IS_HOME_ADDRESS' => $lang['Control_panel_is_home_address'],
			'L_WORK_PHONE' => $lang['Control_panel_work_phone'],
			'L_HOME_PHONE' => $lang['Control_panel_home_phone'],
			'L_CELL_PHONE' => $lang['Control_panel_cell_phone'],
			'L_FAX_NUMBER' => $lang['Control_panel_fax_number'],
			'L_EMAIL_ADDRESS' => $lang['Control_panel_email_address'],
			'L_REGION' => $lang['Control_panel_region'],
			'L_JOB_TITLE' => $lang['Control_panel_job_title'],
			'L_CURRENTLY_CMMV' => $lang['Control_panel_is_cmmv'],
			'L_CMMV_DATE_CERTIFICATION' => $lang['Control_panel_cmmv_date_certification'],
			'L_COMPANY' => $lang['Control_panel_company'],
			'L_IS_BILINGUAL' => $lang['Control_panel_is_bilingual'],
			'L_IS_BILINGUAL_SPANISH' => $lang['Control_panel_is_bilingual_spanish'],
			'L_IS_BILINGUAL_OTHER' => $lang['Control_panel_is_bilingual_other'],
			'L_IS_BILINGUAL_OTHER_LANGUAGE' => $lang['Control_panel_other_language'],
			'L_CONVIENENT_COMMUNITY' => $lang['Control_panel_what_community'],
			'L_CONVIENENT_TIME' => $lang['Control_panel_times_to_teach'],
			'L_COMMUNITY_TEACH' => $lang['Control_panel_teach_in_community'],
			'L_ADMINISTRATIVE_DUTIES' => $lang['Control_panel_administrative_duties'],
			'L_CLERICAL_DUTIES' => $lang['Control_panel_clerical_duties'],
			'L_LOGIN_INFORMATION' => $lang['Register_login_information'],
			'L_LOGIN' => $lang['Control_panel_login'],
			'L_PASSWORD' => $lang['Control_panel_password'],
			'L_REPEAT_PASSWORD' => $lang['Control_panel_repeat_password'],
			'L_CREATE_USER' => $lang['Register_create_user'],
			'L_YES' => $lang['Yes'],
			'L_NO' => $lang['No'],
			'USER_TITLE_LIST' => $user_title_list,
			'USER_REGION_LIST' => $user_region_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	} else {
		$t->set_template( load_template('privacy_policy_form') );
		$t->set_vars( array(
			'L_PRIVACY_POLICY' => $lang['Privacy_policy'],
			'L_ACCEPT' => $lang['Accept'],
			'L_DECLINE' => $lang['Decline']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	}
}

// Register the user
if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'register' ) {
		// Initial variables
		$user_authorized = 0;
		$user_authorized_id = 0;
		$user_register_date = CCCSTIME;
		$user_language = ENGLISH;
		$user_biography = NULL;
		$user_volunteer_type = 0;

		$user_style_color = $dbconfig['style_color'];	
		$user_style_font = $dbconfig['style_font'];
		$user_style_font_size = $dbconfig['style_font_size'];
	
		$incoming = collect_vars($_POST, array('user_title' => MIXED, 'user_first_name' => MIXED, 'user_last_name' => MIXED, 'user_location_home_address_one' => MIXED, 'user_location_home_address_two' => MIXED, 'user_location_city' => MIXED, 'user_location_state' => MIXED, 'user_location_zip_code' => MIXED, 'user_phone_number_work' => MIXED, 'user_phone_number_home' => MIXED, 'user_phone_number_cell' => MIXED, 'user_phone_number_fax' => MIXED, 'user_email' => MIXED, 'user_region_id' => INT, 'user_job_title' => MIXED, 'user_cmmv_certification_date' => MIXED, 'user_job_company' => MIXED, 'user_language_other_language' => MIXED, 'user_available_community' => MIXED, 'user_available_time' => MIXED, 'user_name' => MIXED, 'user_password_one' => MIXED, 'user_password_two' => MIXED, 'user_location_is_home' => INT, 'user_is_cmmv' => INT, 'user_language_is_bilingual' => INT, 'user_language_spanish' => INT, 'user_language_other' => INT, 'user_available_teach_community' => INT, 'user_available_administrative_duties' => INT, 'user_available_clerical_duties' => INT));
		extract($incoming);
	
		// Make sure a user can register for the region they selected
		$region = get_region_data($user_region_id);
		if ( $region['region_allow_registration'] == 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_no_register_for_region']);
		}
		
		// First check to see if the username exists
		if ( check_username_exists($user_name) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_username_exists']);
		}
			
		// Validate the email address given
		if ( !validate_email($user_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		// Now make sure the email address doesn't exist
		if ( check_email_exists($user_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_email_exists']);
		}
		
		// Validate the phone numbers given
		if ( !validate_phone_number($user_phone_number_work) || !validate_phone_number($user_phone_number_home) || 
			!validate_phone_number($user_phone_number_cell) || !validate_phone_number($user_phone_number_fax) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
		}
		
		$user_password = md5($user_password_one);
		
		// Ok, it looks like the user is alright to insert
		$sql = "INSERT INTO `" . USER . "`
				VALUES ( '', '" . $user_region_id . "', '" . APPLICANT . "',
						'" . $user_authorized . "', '" . $user_authorized_id . "', '" . $user_register_date . "', '" . $user_name . "',
						'" . $user_password . "', '" . $user_email . "', '" . $user_title . "', 
						'" . $user_first_name . "', '" . $user_last_name . "', 
						'" . $user_location_home_address_one . "', '" . $user_location_home_address_two . "',
						'" . $user_location_city . "', '" . $user_location_state . "', '" . $user_location_zip_code . "',
						'" . $user_location_is_home . "', '" . $user_phone_number_work . "', '" . $user_phone_number_home . "',
						'" . $user_phone_number_cell . "', '" . $user_phone_number_fax . "', '" . $user_job_title . "',
						'" . $user_job_company . "', '". $user_is_cmmv . "', '" . $user_cmmv_certification_date . "',
						'" . $user_language . "', '" . $user_language_is_bilingual . "',
						'" . $user_language_spanish . "', '" . $user_language_other . "', 
						'" . $user_language_other_language . "', '" . $user_available_time . "', 
						'" . $user_available_community . "', '" . $user_available_teach_community . "', '" . $user_available_administrative_duties . "',
						'" . $user_available_clerical_duties . "', '" . $user_biography . "', '" . $user_volunteer_type . "',
						'" . $user_style_color . "', '" . $user_style_font . "', '" . $user_style_font_size . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Get the ID of the newly inserted ID
		$new_user_id = $db->insertid();
	
		if ( $dbconfig['send_email'] == 1 ) {	
			// Now find all Region Directors of the region they selected and email them
			$region_directors = array();
			$region_directors = get_region_directors($user_region_id);
			
			// Make the site location variable for the email text
			$site_location = $site_protocol . $site_url . $site_basedir;
			
			// The full name of the user who just registered
			$user_full_name = $user_first_name . ' ' . $user_last_name;
			
			// Email all of the directors
			for ( $i=0; $i<count($region_directors); $i++) {
				$user_region_director = ($region_directors[$i]['user_first_name'] . ' ' . $region_directors[$i]['user_last_name']);
				$email_text = sprintf($lang['Email_new_user_message'], $user_region_director, $user_full_name, $new_user_id, $site_location);
				
				send_email($region_directors[$i]['user_email'], $lang['Email_new_user_subject'], $email_text);
			}
		}
		
		// Tell the user about the newly inserted Event
		$content = make_content($lang['Register_thank_you']);
	}
}

$content_pagination = make_pagination($pagination);

$t->set_template( load_template('main_body_content') );
$t->set_vars( array(
	'REGION_LIST' => $global_region_list,
	'CONTENT_PAGINATION' => $content_pagination,
	'CONTENT_TITLE' => $content_title,
	'CONTENT_SUBTITLE' => $content_subtitle,
	'CONTENT' => $content
	)
);
$main_body_content = $t->parse($dbconfig['show_template_name']);

include $root_path . 'includes/page_footer.php';

include $root_path . 'includes/page_exit.php';

?>