<?php

/**
 * usercp.php
 * The area where users can come to update and change information about themselves. Different
 * User Types are taken into consideration.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

//include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_control_panel'], false);
	
// See if they are an Applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

// Always want this, so make it global
$pagination[] = array('usercp.php', $lang['Control_panel']);

$incoming = collect_vars($_REQUEST, array('do' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'editprofile' ) {
		$pagination[] = array(NULL, $lang['Control_panel_update_information']);
		$content_subtitle = make_title($lang['Control_panel_update_information'], true);
		
		// Get *all* of the information about a user
		$u = array();
		$u = get_user_data();

		// Get region ID's and names for the Region List	
		$r_ids = array();
		$r_names = array();
		$r_name = NULL;
		get_regions($r_ids, $r_names, false);
				
		$user_title_list = make_drop_down('cp_user_title', $lang['Control_panel_user_titles'], $lang['Control_panel_user_titles'], $u['user_title']);
		$user_region_list = make_drop_down('cp_user_region_id', $r_ids, $r_names, $u['user_region_id']);
		
		// Ok, lets load this puppy
		$t->set_template( load_template('control_panel_edit_profile_form', false) );
		$t->set_vars( array(
			'L_UPDATE_INFORMATION' => $lang['Control_panel_update_information'],
			'L_LOGIN_INFORMATION' => $lang['Control_panel_login_information'],
			'L_USER_ID' => $lang['Control_panel_user_id'],
			'L_USER_TITLE' => $lang['Control_panel_user_title'],
			'L_CMMV_POSITION' => $lang['Control_panel_cmmv_position'],
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
			'L_COMPANY' => $lang['Control_panel_company'],
			'L_JOB_TITLE' => $lang['Control_panel_job_title'],
			'L_CURRENTLY_CMMV' => $lang['Control_panel_is_cmmv'],
			'L_CMMV_DATE_CERTIFICATION' => $lang['Control_panel_cmmv_date_certification'],
			'L_TIMES_TO_TEACH' => $lang['Control_panel_times_to_teach'],
			'L_IS_BILINGUAL' => $lang['Control_panel_is_bilingual'],
			'L_IS_BILINGUAL_SPANISH' => $lang['Control_panel_is_bilingual_spanish'],
			'L_IS_BILINGUAL_OTHER' => $lang['Control_panel_is_bilingual_other'],
			'L_OTHER_LANGUAGE' => $lang['Control_panel_other_language'],
			'L_WHAT_COMMUNITY' => $lang['Control_panel_what_community'],
			'L_COMMUNITY_TEACH' => $lang['Control_panel_teach_in_community'],
			'L_ADMINISTRATIVE_DUTIES' => $lang['Control_panel_administrative_duties'],
			'L_CLERICAL_DUTIES' => $lang['Control_panel_clerical_duties'],
			'L_BIOGRAPHY' => $lang['Control_panel_biography'],
			'L_DATE_AUTHORIZED' => $lang['Control_panel_date_authorized'],
			'L_AUTHORIZED_BY' => $lang['Control_panel_authorized_by'],
			'L_LOGIN' => $lang['Control_panel_login'],
			'L_NEW_PASSWORD' => $lang['Control_panel_new_password'],
			'L_REPEAT_NEW_PASSWORD' => $lang['Control_panel_repeat_new_password'],
			'L_SUBMIT_INFORMATION' => $lang['Control_panel_submit_information'],
			'USER_ID' => $u['user_id'],
			'USER_TITLE_LIST' => $user_title_list,
			'USER_TYPE' => $lang['Titles'][ ($u['user_type']/100) ],
			'USER_FIRST_NAME' => $u['user_first_name'],
			'USER_LAST_NAME' => $u['user_last_name'],
			'USER_LOCATION_HOME_ADDRESS_ONE' => $u['user_location_home_address_one'],
			'USER_LOCATION_HOME_ADDRESS_TWO' => $u['user_location_home_address_two'],
			'USER_LOCATION_CITY' => $u['user_location_city'],
			'USER_LOCATION_STATE' => $u['user_location_state'],
			'USER_LOCATION_ZIP_CODE' => $u['user_location_zip_code'],
			'USER_LOCATION_IS_HOME_CHECKED' => ($u['user_location_is_home'] == 1 ? 'checked="checked"' : NULL),
			'USER_PHONE_NUMBER_WORK' => $u['user_phone_number_work'],
			'USER_PHONE_NUMBER_HOME' => $u['user_phone_number_home'],
			'USER_PHONE_NUMBER_CELL' => $u['user_phone_number_cell'],
			'USER_PHONE_NUMBER_FAX' => $u['user_phone_number_fax'],
			'USER_EMAIL' => $u['user_email'],
			'USER_REGION_LIST' => $user_region_list,
			'USER_JOB_COMPANY' => $u['user_job_company'],
			'USER_JOB_TITLE' => $u['user_job_title'],
			'USER_CMMV_CERTIFICATION_DATE' => $u['user_cmmv_certification_date'],
			'USER_CURRENTLY_CMMV_CHECKED' => ($u['user_is_cmmv'] == 1 ? 'checked="checked"' : NULL),
			'USER_AVAILABLE_TIME' => $u['user_available_time'],
			'USER_LANGUAGE_IS_BILINGUAL_CHECKED' => ($u['user_language_is_bilingual'] == 1 ? 'checked="checked"' : NULL),
			'USER_LANGUAGE_SPANISH_CHECKED' => ($u['user_language_spanish'] == 1 ? 'checked="checked"' : NULL),
			'USER_LANGUAGE_OTHER_CHECKED' => ($u['user_language_other'] == 1 ? 'checked="checked"' : NULL),
			'USER_LANGUAGE_OTHER_LANGUAGE' => $u['user_language_other_language'],
			'USER_AVAILABLE_COMMUNITY' => $u['user_available_community'],
			'USER_COMMUNITY_TEACH_CHECKED' => ($u['user_available_teach_community'] == 1 ? 'checked="checked"' : NULL),
			'USER_ADMINISTRATIVE_DUTIES_CHECKED' => ($u['user_available_administrative_duties'] == 1 ? 'checked="checked"' : NULL),
			'USER_CLERICAL_DUTIES_CHECKED' => ($u['user_available_clerical_duties'] == 1 ? 'checked="checked"' : NULL),
			'USER_BIOGRAPHY' => $u['user_biography'],
			'USER_DATE_AUTHORIZED' => date($dbconfig['date_format'], $u['user_register_date']),
			'USER_AUTHORIZED_BY' => get_user_real_name($u['user_authorized_id']),
			'USER_NAME' => $u['user_name']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'editstyle' ) {
		$pagination[] = array(NULL, $lang['Control_panel_change_style']);
		$content_subtitle = make_title($lang['Control_panel_change_style'], true);
		
		$user_style = array();
		$user_style = load_style();
		
		$style_color_list = make_drop_down('style_main_color', $site_colors, $site_colors_names, $user_style['user_style_color']);
		$style_font_list = make_drop_down('style_main_font', $site_fonts, $site_fonts, $user_style['user_style_font']);
		$style_font_size_list = make_drop_down('style_main_font_size', $site_font_sizes, $site_font_sizes, $user_style['user_style_font_size']);
		
		$t->set_template( load_template('control_panel_edit_style_form') );
		$t->set_vars( array(
			'L_CHANGE_STYLE' => $lang['Control_panel_change_style'],
			'L_SITE_COLOR' => $lang['Control_panel_site_color'],
			'L_SITE_FONT' => $lang['Control_panel_site_font'],
			'L_SITE_FONT_SIZE' => $lang['Control_panel_site_font_size'],
			'STYLE_COLOR_LIST' => $style_color_list,
			'STYLE_FONT_LIST' => $style_font_list,
			'STYLE_FONT_SIZE_LIST' => $style_font_size_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($style_color_list, $style_font_list, $style_font_size_list);
	} elseif ( $do == 'pastevents' ) {
		$pagination[] = array(NULL, $lang['Control_panel_see_past_events']);
		$content_subtitle = make_title($lang['Control_panel_see_past_events'], true);

		// If the user id is not set, use the session data
		if ( empty($cp_user_id) ) {
			$userid = $usercache['user_id'];
		}
		
		$events = array();
		$events = get_event_history($userid);
		
		for ( $i=0; $i<count($events); $i++ ) {
			$event_public = ( $events[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
			
			$t->set_template( load_template('control_panel_events_item') );
			$t->set_vars( array(
				'EVENT_PUBLIC' => $event_public,
				'EVENT_ID' => $events[$i]['event_id'],
				'REGION_ID' => $events[$i]['event_region_id'],
				'EVENT_TITLE' => stripslashes($events[$i]['program_name']),
				'EVENT_ORGANIZATION' => $events[$i]['event_contact_organization'],
				'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
				'EVENT_NUM_HOURS' => $events[$i]['hour_count']
				)
			);
			$event_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('control_panel_events') );
		$t->set_vars( array(
			'L_COMPLETED_EVENTS' => $lang['Control_panel_completed_events'],
			'L_ID' => $lang['Id'],
			'L_TITLE' => $lang['Title'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_DATE' => $lang['Date'],
			'L_NUM_HOURS' => $lang['Control_panel_hours'],
			'EVENT_LIST' => $event_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);

		unset($event_list, $events);
	} elseif ( $do == 'viewprofile' ) {
		$pagination[] = array(NULL, $lang['Control_panel_user_information']);
		$content_subtitle = make_title($lang['Control_panel_user_information'], true);
		
		$incoming = collect_vars($_GET, array('userid' => INT));
		extract($incoming);
		
		if ( !is_numeric($userid) || $userid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		$u = get_user_data($userid);
		
		$user_unauthorize_link = NULL;
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $u['user_region_id'] ) || $usercache['user_type'] == ADMINISTRATOR ) {
			$user_unauthorize_link = make_link('usercp.php?do=deleteuser&amp;userid=' . $userid, $lang['Control_panel_unauthorize_user']);
		}
		
		$user_photo = NULL;
		if ( is_numeric($u['user_photo_id']) && $u['user_photo_id'] > 0 ) {
			$user_photo = load_image($u['user_photo_id']);
			$t->set_template( load_template('control_panel_user_information_photo') );
			$t->set_vars( array(
				'L_PHOTO' => $lang['Photo'],
				'USER_PHOTO' => $user_photo
				)
			);
			$user_photo = $t->parse($dbconfig['show_template_name']);
		}
		
		$real_name = NULL;
		if ( is_numeric($u['user_authorized_id']) && $u['user_authorized_id'] > 0 ) {
			$real_name = get_user_real_name($u['user_authorized_id']);
		}
		
		// Ok, lets load this puppy
		$t->set_template( load_template('control_panel_user_information', false) );
		$t->set_vars( array(
			'L_AUTHORIZE_USER' => $lang['Control_panel_user_information'],
			'L_USER_ID' => $lang['Control_panel_user_id'],
			'L_USER_TITLE' => $lang['Control_panel_user_title'],
			'L_CMMV_POSITION' => $lang['Control_panel_cmmv_position'],
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
			'L_COMPANY' => $lang['Control_panel_company'],
			'L_JOB_TITLE' => $lang['Control_panel_job_title'],
			'L_CURRENTLY_CMMV' => $lang['Control_panel_is_cmmv'],
			'L_CMMV_CERTIFICATION_DATE' => $lang['Control_panel_cmmv_date_certification'],
			'L_TIMES_TO_TEACH' => $lang['Control_panel_times_to_teach'],
			'L_IS_BILINGUAL' => $lang['Control_panel_is_bilingual'],
			'L_IS_BILINGUAL_SPANISH' => $lang['Control_panel_is_bilingual_spanish'],
			'L_IS_BILINGUAL_OTHER' => $lang['Control_panel_is_bilingual_other'],
			'L_OTHER_LANGUAGE' => $lang['Control_panel_other_language'],
			'L_WHAT_COMMUNITY' => $lang['Control_panel_what_community'],
			'L_TEACH_IN_COMMUNITY' => $lang['Control_panel_teach_in_community'],
			'L_TEACH_IN_AGENCY' => $lang['Control_panel_teach_in_agency'],
			'L_LOGIN' => $lang['Control_panel_login'],
			'L_SUBMIT_INFORMATION' => $lang['Control_panel_submit_information'],
			'L_DATE_AUTHORIZED' => $lang['Control_panel_date_authorized'],
			'L_AUTHORIZED_BY' => $lang['Control_panel_authorized_by'],
			'L_AUTHORIZE' => $lang['Control_panel_authorize'],
			'L_DECLINE' => $lang['Control_panel_decline'],
			'L_BIOGRAPHY' => $lang['Control_panel_biography'],
			'USER_ID' => $u['user_id'],
			'USER_TYPE' => $lang['Titles'][ ($u['user_type']/100) ],
			'USER_TITLE' => $u['user_title'],
			'USER_FIRST_NAME' => $u['user_first_name'],
			'USER_LAST_NAME' => $u['user_last_name'],
			'USER_LOCATION_HOME_ADDRESS_ONE' => $u['user_location_home_address_one'],
			'USER_LOCATION_HOME_ADDRESS_TWO' => $u['user_location_home_address_two'],
			'USER_LOCATION_CITY' => $u['user_location_city'],
			'USER_LOCATION_STATE' => $u['user_location_state'],
			'USER_LOCATION_ZIP_CODE' => $u['user_location_zip_code'],
			'USER_LOCATION_IS_HOME' => yes_no($u['user_location_is_home']),
			'USER_PHONE_NUMBER_WORK' => $u['user_phone_number_work'],
			'USER_PHONE_NUMBER_HOME' => $u['user_phone_number_home'],
			'USER_PHONE_NUMBER_CELL' => $u['user_phone_number_cell'],
			'USER_PHONE_NUMBER_FAX' => $u['user_phone_number_fax'],
			'USER_EMAIL' => $u['user_email'],
			'USER_REGION' => get_region_name($u['user_region_id']),
			'USER_JOB_COMPANY' => $u['user_job_company'],
			'USER_JOB_TITLE' => $u['user_job_title'],
			'USER_IS_CMMV' => yes_no($u['user_is_cmmv']),
			'USER_CMMV_CERTIFICATION_DATE' => $u['user_cmmv_certification_date'],
			'USER_LANGUAGE_IS_BILINGUAL' => yes_no($u['user_language_is_bilingual']),
			'USER_LANGUAGE_SPANISH' => yes_no($u['user_language_spanish']),
			'USER_LANGUAGE_OTHER' => yes_no($u['user_language_other']),
			'USER_LANGUAGE_OTHER_LANGUAGE' => $u['user_language_other_language'],
			'USER_AVAILABLE_TIME' => $u['user_available_time'],
			'USER_LANGUAGE_OTHER_LANGUAGE' => $u['user_language_other_language'],
			'USER_AVAILABLE_COMMUNITY' => $u['user_available_community'],
			'USER_AVAILABLE_TEACH_COMMUNITY' => yes_no($u['user_available_teach_community']),
			'USER_DATE_AUTHORIZED' => date($dbconfig['date_format'], $u['user_register_date']),
			'USER_AUTHORIZED_BY' => $real_name,
			'USER_NAME' => $u['user_name'],
			'USER_BIOGRAPHY' => $u['user_biography'],
			'USER_PHOTO' => $user_photo,
			'USER_UNAUTHORIZE_LINK' => $user_unauthorize_link
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($u, $user_unauthorize_link);
	} elseif ( $do == 'trackhours' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Control_panel_track_hours']);
		$content_subtitle = make_title($lang['Control_panel_track_hours'], true);
		
		// Get all completed events in the past by Volunteers of this Region
		$events = array();
		$events = get_past_events($usercache['user_region_id']);
		$event_item_list = NULL;
		
		$event_hours = 0;
		
		for ( $i=0; $i<count($events); $i++ ) {
			$event_hours = date('G', $events[$i]['event_end_date']) - date('G', $events[$i]['event_start_date']);
			
			if ( $event_hours <= 0 ) {
				$event_hours = 1;
			}
			
			$t->set_template( load_template('control_panel_track_hours_item') );
			$t->set_vars( array(
				'EVENT_ID' => $events[$i]['event_id'],
				'EVENT_USER_ID' => $events[$i]['assignment_user_id'],
				'EVENT_TITLE' => stripslashes($events[$i]['program_name']),
				'EVENT_VOLUNTEER' => $events[$i]['user_first_name']  . ' ' . $events[$i]['user_last_name'],
				'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
				'EVENT_HOURS' => $event_hours
				)
			);
			$event_item_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		// Get the total hours for the user
		$hours = get_user_hours($usercache['user_id']);
		$hours = ( empty($hours) ? 0 : $hours );
		
		$t->set_template( load_template('control_panel_track_hours_form') );
		$t->set_vars( array(
			'L_TRACK_HOURS_MESSAGE' => sprintf($lang['Control_panel_track_hours_message'], $hours),
			'L_TRACK_HOURS' => $lang['Control_panel_track_hours'],
			'L_ID' => $lang['Control_panel_id'],
			'L_EVENT' => $lang['Control_panel_event'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_VOLUNTEER' => $lang['Volunteer'],
			'L_HOURS' => $lang['Control_panel_hours'],
			'EVENT_ITEM_LIST' => $event_item_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($event_item_list, $events);
	} elseif ( $do == 'reviewusers' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Control_panel_review_applications']);
		$content_subtitle = make_title($lang['Control_panel_review_applications'], true);
		
		// Handle all of the sorting
		$incoming = collect_vars($_GET, array('sort' => MIXED, 'sort_field' => MIXED));
		extract($incoming);
	
		// Default sort to Ascending if some bogus data is entered
		$sort = ( $sort != 'asc' && $sort != 'desc' ) ? 'asc' : $sort;
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'u.user_region_id ASC';
		} else {
			if ( $sort_field == 'email' ) {
				$sort = 'u.user_email ' . strtoupper($sort);
			} elseif ( $sort_field == 'region' ) {
				$sort = 'u.user_region_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'name' ) {
				$sort = 'u.user_last_name ' . strtoupper($sort);
			}
		}

		// If they are an RD, only show them applicants from their region
		if ( $usercache['user_type'] == REGIONAL_DIRECTOR ) {
			$sql = "SELECT * FROM `" . USER . "` u WHERE u.user_type = '" . APPLICANT . "'
						AND u.user_region_id = '" . $usercache['user_region_id'] . "' 
						AND u.user_authorized = '0'
					ORDER BY " . $sort;
		} elseif ( $usercache['user_type'] == ADMINISTRATOR ) {
			// If they are an Admin, show them all of the applicants
			$sql = "SELECT * FROM `" . USER . "` u WHERE u.user_type = '" . APPLICANT . "'
						AND u.user_authorized = '0'
					ORDER BY " . $sort;
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Compile the list of all of the applicants
		$application_item_list = NULL;
		while ( $applicant = $db->getarray($result) ) {
			$applicant['user_region_id'] = ( $applicant['user_region_id'] == 0 ) ? 1 : $applicant['user_region_id'];

			$t->set_template( load_template('control_panel_authorize_users_form_item') );
			$t->set_vars( array(
				'USER_ID' => $applicant['user_id'],
				'USER_NAME' => $applicant['user_first_name'] . ' ' . $applicant['user_last_name'],
				'USER_EMAIL' => $applicant['user_email'],
				'USER_REGION' => get_region_name($applicant['user_region_id']),
				'USER_ACTION' => 'authorizeuser'
				)
			);
			$application_item_list .= $t->parse($dbconfig['show_template_name']);
		}

		$db->freeresult($result);
		
		// Show the list of all of the applicants
		$t->set_template( load_template('control_panel_authorize_users_form') );
		$t->set_vars( array(
			// I can't, for the life of me, figure out why I have to put this here
			'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
			'L_REVIEW_NEW_APPLICATIONS' => $lang['Control_panel_review_applications'],
			'L_ID' => $lang['Control_panel_id'],
			'L_NAME' => $lang['Control_panel_name'],
			'L_EMAIL' => $lang['Email_address'],
			'L_REGION' => $lang['Region'],
			'APPLICATION_ITEM_LIST' => $application_item_list,
			'ACTION' => 'reviewusers'
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$db->freeresult($result);
		
		unset($application_item_list, $applicant);
	} elseif ( $do == 'authorizeuser' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Control_panel_authorize_user']);
		$content_subtitle = make_title($lang['Control_panel_authorize_user'], true);
		
		$incoming = collect_vars($_GET, array('userid' => INT));
		extract($incoming);
		
		// Always sanitize!
		if ( !is_numeric($userid) || $userid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_no_id']);
		}
		
		// Get the user/applicant
		$sql = "SELECT * FROM `" . USER . "` u 
				WHERE u.user_id = '" . $userid . "'
					AND u.user_type = '" . APPLICANT . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$a = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_applicant']);
		
		$db->freeresult($result);
		
		$user_volunteer_type_list = make_drop_down('user_volunteer_type', $lang['Control_panel_volunteer_types'], $lang['Control_panel_volunteer_types']);

		$ids = array();
		$names = array();
		get_regions($ids, $names);

		$user_region_list = make_drop_down('user_region_id', $ids, $names, $a['user_region_id']);
		
		// Ok, lets load this puppy
		$t->set_template( load_template('control_panel_authorize_user_form', false) );
		$t->set_vars( array(
			'L_AUTHORIZE_USER' => $lang['Control_panel_authorize_user'],
			'L_USER_ID' => $lang['Control_panel_user_id'],
			'L_USER_TITLE' => $lang['Control_panel_user_title'],
			'L_CMMV_POSITION' => $lang['Control_panel_cmmv_position'],
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
			'L_COMPANY' => $lang['Control_panel_company'],
			'L_JOB_TITLE' => $lang['Control_panel_job_title'],
			'L_CURRENTLY_CMMV' => $lang['Control_panel_is_cmmv'],
			'L_CMMV_CERTIFICATION_DATE' => $lang['Control_panel_cmmv_date_certification'],
			'L_TIMES_TO_TEACH' => $lang['Control_panel_times_to_teach'],
			'L_IS_BILINGUAL' => $lang['Control_panel_is_bilingual'],
			'L_IS_BILINGUAL_SPANISH' => $lang['Control_panel_is_bilingual_spanish'],
			'L_IS_BILINGUAL_OTHER' => $lang['Control_panel_is_bilingual_other'],
			'L_OTHER_LANGUAGE' => $lang['Control_panel_other_language'],
			'L_WHAT_COMMUNITY' => $lang['Control_panel_what_community'],
			'L_TEACH_IN_COMMUNITY' => $lang['Control_panel_teach_in_community'],
			'L_TEACH_IN_AGENCY' => $lang['Control_panel_teach_in_agency'],
			'L_LOGIN' => $lang['Control_panel_login'],
			'L_NEW_PASSWORD' => $lang['Control_panel_new_password'],
			'L_REPEAT_NEW_PASSWORD' => $lang['Control_panel_repeat_new_password'],
			'L_SUBMIT_INFORMATION' => $lang['Control_panel_submit_information'],
			'L_VOLUNTEER_TYPE' => $lang['Control_panel_volunteer_type'],
			'L_UPDATE' => $lang['Control_panel_update_applicant'],
			'L_AUTHORIZE' => $lang['Control_panel_authorize'],
			'L_DECLINE' => $lang['Control_panel_decline'],
			'USER_ID' => $a['user_id'],
			'USER_TYPE' => $lang['Titles'][ ($a['user_type']/100) ],
			'USER_TITLE' => $a['user_title'],
			'USER_FIRST_NAME' => $a['user_first_name'],
			'USER_LAST_NAME' => $a['user_last_name'],
			'USER_LOCATION_HOME_ADDRESS_ONE' => $a['user_location_home_address_one'],
			'USER_LOCATION_HOME_ADDRESS_TWO' => $a['user_location_home_address_two'],
			'USER_LOCATION_CITY' => $a['user_location_city'],
			'USER_LOCATION_STATE' => $a['user_location_state'],
			'USER_LOCATION_ZIP_CODE' => $a['user_location_zip_code'],
			'USER_LOCATION_IS_HOME' => yes_no($a['user_location_is_home']),
			'USER_PHONE_NUMBER_WORK' => $a['user_phone_number_work'],
			'USER_PHONE_NUMBER_HOME' => $a['user_phone_number_home'],
			'USER_PHONE_NUMBER_CELL' => $a['user_phone_number_cell'],
			'USER_PHONE_NUMBER_FAX' => $a['user_phone_number_fax'],
			'USER_EMAIL' => $a['user_email'],
			'USER_REGION_LIST' => $user_region_list,
			'USER_JOB_COMPANY' => $a['user_job_company'],
			'USER_JOB_TITLE' => $a['user_job_title'],
			'USER_IS_CMMV' => yes_no($a['user_is_cmmv']),
			'USER_CMMV_CERTIFICATION_DATE' => $a['user_cmmv_certification_date'],
			'USER_LANGUAGE_IS_BILINGUAL' => yes_no($a['user_language_is_bilingual']),
			'USER_LANGUAGE_SPANISH' => yes_no($a['user_language_spanish']),
			'USER_LANGUAGE_OTHER' => yes_no($a['user_language_other']),
			'USER_LANGUAGE_OTHER_LANGUAGE' => $a['user_language_other_language'],
			'USER_AVAILABLE_TIME' => $a['user_available_time'],
			'USER_LANGUAGE_OTHER_LANGUAGE' => $a['user_language_other_language'],
			'USER_AVAILABLE_COMMUNITY' => $a['user_available_community'],
			'USER_AVAILABLE_TEACH_COMMUNITY' => yes_no($a['user_available_teach_community']),
			'USER_NAME' => $a['user_name'],
			'USER_VOLUNTEER_TYPE_LIST' => $user_volunteer_type_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($user_volunteer_type_list, $a, $user_region_list);
	} elseif ( $do == 'editusers' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Edit_users']);
		$content_subtitle = make_title($lang['Title_edit_users'], true);
	
		// Handle all of the sorting
		$incoming = collect_vars($_GET, array('sort' => MIXED, 'sort_field' => MIXED));
		extract($incoming);
	
		// Default sort to Ascending if some bogus data is entered
		$sort = ( $sort != 'asc' && $sort != 'desc' ) ? 'asc' : $sort;
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'u.user_region_id ASC';
		} else {
			if ( $sort_field == 'email' ) {
				$sort = 'u.user_email ' . strtoupper($sort);
			} elseif ( $sort_field == 'region' ) {
				$sort = 'u.user_region_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'name' ) {
				$sort = 'u.user_last_name ' . strtoupper($sort);
			}
		}
		
		if ( $usercache['user_type'] == REGIONAL_DIRECTOR ) {
			$sql = "SELECT * FROM `" . USER . "` u
					WHERE u.user_type < '" . APPLICANT . "'
						AND u.user_type > '" . ADMINISTRATOR . "'
						AND u.user_authorized = '1'
						AND u.user_region_id = '" . $usercache['user_region_id'] . "'
					ORDER BY " . $sort;
		} elseif ( $usercache['user_type'] == ADMINISTRATOR ) {
			$sql = "SELECT * FROM `" . USER . "` u
					WHERE u.user_type < '" . APPLICANT . "'
						AND u.user_type > '" . ADMINISTRATOR . "'
						AND u.user_authorized = '1'
					ORDER BY " . $sort;
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Compile the list of all of the users
		$user_item_list = NULL;
		while ( $u = $db->getarray($result) ) {
			$t->set_template( load_template('control_panel_authorize_users_form_item') );
			$t->set_vars( array(
				'USER_ID' => $u['user_id'],
				'USER_NAME' => $u['user_first_name'] . ' ' . $u['user_last_name'],
				'USER_EMAIL' => $u['user_email'],
				'USER_REGION' => get_region_name($u['user_region_id']),
				'USER_ACTION' => 'edituser'
				)
			);
			$user_item_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		// Show the list of all of the applicants
		$t->set_template( load_template('control_panel_authorize_users_form') );
		$t->set_vars( array(
			'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
			'L_REVIEW_NEW_APPLICATIONS' => $lang['Control_panel_edit_users'],
			'L_ID' => $lang['Control_panel_id'],
			'L_NAME' => $lang['Control_panel_name'],
			'L_EMAIL' => $lang['Email_address'],
			'L_REGION' => $lang['Region'],
			'APPLICATION_ITEM_LIST' => $user_item_list,
			'ACTION' => $do
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$db->freeresult($result);
		
		unset($user_item_list, $u);
	} elseif ( $do == 'edituser' ) {
		$pagination[] = array(NULL, $lang['Edit_users']);
		$content_subtitle = make_title($lang['Title_edit_users'], true);
		
		$incoming = collect_vars($_GET, array('userid' => INT));
		extract($incoming);
		
		if ( !is_numeric($userid) || $userid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$user = array();
		$user = get_abbreviated_user_data($userid);

		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $user['user_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names);
		
		if ( $user['user_type'] == VOLUNTEER ) {
			$this_user_type = 0;
		} elseif ( $user['user_type'] == VOLUNTEER_STAFF ) {
			$this_user_type = 1;
		} elseif ( $user['user_type'] == REGIONAL_DIRECTOR ) {
		 	$this_user_type = 2;
		}
		
		$edit_user_type = make_drop_down('edit_user_type', $lang['Control_panel_volunteer_types'], $lang['Control_panel_volunteer_types'], $lang['Control_panel_volunteer_types'][$this_user_type]);
		$edit_user_region_list = make_drop_down('edit_user_region_id', $r_ids, $r_names, $user['user_region_id']);
		
		$t->set_template( load_template('control_panel_edit_user_form') );
		$t->set_vars( array(
			'L_EDIT_USER' => $lang['Edit_user'],
			'L_USER_ID' => $lang['Control_panel_user_id'],
			'L_USER_NAME' => $lang['Control_panel_name'],
			'L_USER_TYPE' => $lang['Control_panel_user_type'],
			'L_USER_EMAIL' => $lang['Email_address'],
			'L_REGION' => $lang['Region'],
			'USER_REGION_LIST' => $edit_user_region_list,
			'USER_NAME' => $user['user_name'],
			'USER_ID' => $user['user_id'],
			'USER_EMAIL' => $user['user_email'],
			'USER_TYPE_LIST' => $edit_user_type
			)
		);
		
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'deleteusers' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Delete_users']);
		$content_subtitle = make_title($lang['Title_delete_users'], true);
		
		// Handle all of the sorting
		$incoming = collect_vars($_GET, array('sort' => MIXED, 'sort_field' => MIXED));
		extract($incoming);
	
		// Default sort to Ascending if some bogus data is entered
		$sort = ( $sort != 'asc' && $sort != 'desc' ) ? 'asc' : $sort;
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'u.user_last_name ASC';
		} else {
			if ( $sort_field == 'name' ) {
				$sort = 'u.user_last_name ' . strtoupper($sort);
			} elseif ( $sort_field == 'id' ) {
				$sort = 'u.user_id ' . strtoupper($sort);
			}
		}
			
		if ( $usercache['user_type'] == REGIONAL_DIRECTOR ) {
			$sql = "SELECT * FROM `" . USER . "` u
					WHERE u.user_type < '" . APPLICANT . "'
						AND u.user_type > '" . REGIONAL_DIRECTOR . "'
						AND u.user_authorized = '1'
						AND u.user_region_id = '" . $usercache['user_region_id'] . "'
					ORDER BY " . $sort;
		} elseif ( $usercache['user_type'] == ADMINISTRATOR ) {
			$sql = "SELECT * FROM `" . USER . "` u
					WHERE u.user_type < '" . APPLICANT . "'
						AND u.user_type > '" . ADMINISTRATOR . "'
						AND u.user_authorized = '1'
					ORDER BY " . $sort;
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Compile the list of all of the applicants
		$delete_item_list = NULL;
		while ( $applicant = $db->getarray($result) ) {
			$t->set_template( load_template('control_panel_delete_users_form_item') );
			$t->set_vars( array(
				'USER_ID' => $applicant['user_id'],
				'USER_NAME' => $applicant['user_first_name'] . ' ' . $applicant['user_last_name'],
				'USER_COMPANY' => $applicant['user_job_company'],
				'USER_EMAIL' => $applicant['user_email'],
				'USER_ACTION' => 'edit_user'
				)
			);
			$delete_item_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		// Show the list of all of the applicants
		$t->set_template( load_template('control_panel_delete_users_form') );
		$t->set_vars( array(
			'L_DELETE_USERS' => $lang['Title_delete_users'],
			'L_ID' => $lang['Control_panel_id'],
			'L_NAME' => $lang['Control_panel_name'],
			'L_DELETE' => $lang['Delete'],
			'L_EMAIL_ADDRESS' => $lang['Control_panel_email_address'],
			'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
			'DELETE_ITEM_LIST' => $delete_item_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$db->freeresult($result);
		
		unset($delete_item_list);
	} elseif ( $do == 'deleteuser' ) {
		$pagination[] = array(NULL, $lang['Control_panel_unauthorize_user']);
		$content_subtitle = make_title($lang['Control_panel_unauthorize_user'], true);
		
		$incoming = collect_vars($_GET, array('userid' => INT));
		extract($incoming);
		
		if ( !is_numeric($userid) || $userid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
				
		$u = get_user_data($userid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $u['user_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$sql = "UPDATE `" . USER . "` 
				SET user_type = '" . APPLICANT . "',
					user_authorized = '0'
				WHERE user_id = '" . $userid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Tell the user that their information was updated
		$content = make_content($lang['Control_panel_user_unauthorized']);
	} else {
		// Finally, if the action sent is NULL or bogus, just
		// route them here and show them the main CP page.
		$content_subtitle = make_title($lang['Subtitle_control_panel'], true);
		
		$t->set_template( load_template('control_panel_welcome_text') );
		$t->set_vars( array(
			'WELCOME_TEXT' => sprintf($lang['Control_panel_welcome'], $usercache['user_first_name'], $usercache['user_last_name']),
			'STATS_TEXT' => $lang['Control_panel_website_launch']
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
		// See if we do the new message thing.
		$sql = "SELECT * FROM `" . MESSAGE . "` m
				LEFT JOIN `" . MESSAGE_TEXT . "` mt
					ON m.message_id = mt.text_message_id
				WHERE mt.text_content IS NOT NULL
					AND mt.text_type = '0'
					AND m.message_type = '0'
					AND m.message_to_user_id = '" . $usercache['user_id'] . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result) ) {
			$new_message = true;
			$new_message_count = $db->numrows($result);
		}
		$db->freeresult($result);
		
		// Determine what type of options to show them
		if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
			$t->set_template( load_template('control_panel_admin_menu') );
			$t->set_vars( array(
				'L_UPDATE_INFORMATION' => $lang['Control_panel_update_information'],
				'L_CHANGE_STYLE' => $lang['Control_panel_change_style'],
				'L_TRACK_VOLUNTEER_HOURS' => $lang['Control_panel_track_hours'],
				'L_SEE_PAST_EVENTS' => $lang['Control_panel_see_past_events'],
				'L_REVIEW_APPLICATIONS' => $lang['Control_panel_review_applications'],
				'L_MANAGE_USERS' => $lang['Control_panel_manage_users'],
				'L_EDIT_USER' => $lang['Control_panel_edit_user'],
				'L_DELETE_USER' => $lang['Control_panel_delete_user'],
				'L_ADD_GRANT' => $lang['Control_panel_add_grant'],
				'L_ADD_INCOME' => $lang['Control_panel_add_income'],
				'L_PERSONAL_MESSAGE_CENTER' => ( $new_message ? sprintf($lang['Control_panel_personal_message_center_new'], $new_message_count) : $lang['Control_panel_personal_message_center']),
				'L_CREATE_REPORT' => $lang['Control_panel_create_report'],
				'L_ADD_PROGRAM' => $lang['Control_panel_add_program'],
				'L_CALENDAR_ASSIGNMENTS' => $lang['Control_panel_calendar_assignments'],
				'L_PENDING_EVENTS' => $lang['Control_panel_pending_events'],
				'L_COMPLETED_EVENTS_EVALS_PENDING' => $lang['Control_panel_completed_events_evals_pending'],
				'L_COMPLETED_EVENTS' => $lang['Control_panel_completed_events'],
				'USER_REGION_ID' => $usercache['user_region_id']
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
			
			// Get a list of the users currently logged in
			$sql = "SELECT u.user_id, u.user_type, u.user_first_name, u.user_last_name, s.* FROM `" . SESSION . "` s 
					INNER JOIN `" . USER . "` u 
						ON s.session_user_id = u.user_id
					ORDER BY u.user_id ASC";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			$i = 0;
			$numrows = $db->numrows($result);
			while ( $u = $db->getarray($result) ) {
				$i++;
				
				$user_real_name = $u['user_first_name'] . ' ' . $u['user_last_name'];
				if ( $u['user_type'] == ADMINISTRATOR ) {
					$user_real_name = '<strong>' . $user_real_name . '</strong>';
				} elseif ( $u['user_type'] == REGIONAL_DIRECTOR ) {
					$user_real_name = '<em>' . $user_real_name . '</em>';
				}
				
				$user_list .= make_link('usercp.php?do=viewprofile&userid=' . $u['user_id'], $user_real_name);
				if ( $i != $numrows ) {
					$user_list .= ', ';
				}
			}
			
			$t->set_template( load_template('control_panel_logged_in_users') );
			$t->set_vars( array(
				'L_LOGGED_IN_USERS' => $lang['Control_panel_logged_in_users'],
				'L_MOST_LOGGED_IN' => $lang['Control_panel_most_logged_in'],
				'L_ON' => $lang['On'],
				'LOGGED_IN_USERS_LIST' => $user_list,
				'MOST_LOGGED_IN' => $dbconfig['most_logged_in'],
				'MOST_LOGGED_IN_DATE' => date($dbconfig['date_format'], $dbconfig['most_logged_in_date'])
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		} elseif ( $usercache['user_type'] == VOLUNTEER || $usercache['user_type'] == VOLUNTEER_STAFF ) {
			$t->set_template( load_template('control_panel_volunteer_menu') );
			$t->set_vars( array(
				'L_UPDATE_INFORMATION' => $lang['Control_panel_update_information'],
				'L_CHANGE_STYLE' => $lang['Control_panel_change_style'],
				'L_SEE_PAST_EVENTS' => $lang['Control_panel_see_past_events'],
				'L_PERSONAL_MESSAGE_CENTER' => ( $new_message ? sprintf($lang['Control_panel_personal_message_center_new'], $new_message_count) : $lang['Control_panel_personal_message_center']),
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		}
		
		$events = get_user_events($usercache['user_id']);
		$content .= make_pending_events_calendar($events);

		unset($event_item_list, $u);
		
		$page = load_page('search_event_by_id');
		$content .= $page['page_text'];
		
		$t->set_template( load_template('event_search_form') );
		$t->set_vars( array(
			'L_SEARCH' => $lang['Search'],
			'L_SEARCH_TEXT' => $lang['Event_search'],
			'REGION_ID' => $regionid
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	}
}

/// Start accepting real input from the user.
if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'editprofile' ) {
		$pagination[] = array('usercp.php?do=editprofile', $lang['Control_panel_update_information']);
		$pagination[] = array(NULL, $lang['Information_updated']);
		
		$content_subtitle = make_title($lang['Information_updated'], true);
		
		// The reason everything is named cp_### is because the MMI
		// server has register globals turned on, so the session
		// information will get confused with the POST information
		// thus screwing everything up royally.
		$incoming = collect_vars($_POST, array('cp_user_password_one' => MIXED, 'cp_user_password_two' => MIXED));
		extract($incoming);
		
		// We already know the user id of the user we are editing, its in $usercache
		$cp_user_id = $usercache['user_id'];
		
		if ( !empty($cp_user_password_one) && !empty($cp_user_password_two) ) {
			// The Javascript ensures that we already have matching passwords of appropriate
			// lengths, so it's safe to assume that we can insert them into the database.
			// However, being the cautious programmer that I am, I am going to check anyway.
			// I sleep better at night that way.
			if ( $cp_user_password_one != $cp_user_password_two ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_password_not_match']);
			}
			
			if ( ($cp_user_password_one == $cp_user_password_two) && (strlen($cp_user_password_one) < 6 ) ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_password_too_short']);
			}
			
			$cp_user_password = md5($cp_user_password_one);
			
			// Do the ole' update-a-roo
			$sql = "UPDATE `" . USER . "` SET 
						user_password = '" . $cp_user_password . "' 
					WHERE user_id = '" . $cp_user_id . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		}
		
		$incoming = collect_vars($_POST, array('cp_user_title' => MIXED, 'cp_user_first_name' => MIXED, 'cp_user_last_name' => MIXED, 'cp_user_location_home_address_one' => MIXED, 'cp_user_location_home_address_two' => MIXED, 'cp_user_location_city' => MIXED, 'cp_user_location_state' => MIXED, 'cp_user_location_zip_code' => MIXED, 'cp_user_location_is_home' => INT, 'cp_user_phone_number_work' => MIXED, 'cp_user_phone_number_home' => MIXED, 'cp_user_phone_number_cell' => MIXED, 'cp_user_phone_number_fax' => MIXED, 'cp_user_email_address' => MIXED, 'cp_user_region_id' => INT, 'cp_user_job_company' => MIXED, 'cp_user_job_title' => MIXED, 'cp_user_is_cmmv' => INT, 'cp_user_cmmv_certification_date' => MIXED, 'cp_user_available_time' => MIXED, 'cp_user_language_other_language' => MIXED, 'cp_user_available_community' => MIXED, 'cp_user_biography' => MIXED, 'cp_user_language_is_bilingual' => INT, 'cp_user_language_spanish' => INT, 'cp_user_language_other' => INT, 'cp_user_available_teach_community' => INT, 'cp_user_available_administrative_duties' => INT, 'cp_user_available_clerical_duties' => INT));
		extract($incoming);
		
		// Validate the email address given
		if ( !validate_email($cp_user_email_address) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		// Validate the phone numbers given
		// We don't need to check if they are empty here or 
		// not because the validate_phone_number() function
		// does that for us and since phone numbers aren't required,
		// it just returns true if they're empty.
		if ( !validate_phone_number($cp_user_phone_number_work) || !validate_phone_number($cp_user_phone_number_home) || !validate_phone_number($cp_user_phone_number_cell) || !validate_phone_number($cp_user_phone_number_fax) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
		}
	
		// Make the state name capitalized
		$cp_user_location_state = strtoupper($cp_user_location_state);
		
		// For the checkboxes/radio buttons, when unchecked, they submit nothing, so we
		// check for that and give them a 0 so the database doesn't complain.
		$cp_user_location_is_home = (empty($cp_user_location_is_home) ? 0 : 1);
		$cp_user_language_is_bilingual = (empty($cp_user_language_is_bilingual) ? 0 : 1);
		$cp_user_language_spanish = (empty($cp_user_language_spanish) ? 0 : 1);
		$cp_user_language_other = (empty($cp_user_language_other) ? 0 : 1);
		
		$sql = "UPDATE `" . USER . "` SET
					user_region_id = '" . $cp_user_region_id . "', user_email = '" . $cp_user_email_address . "',
					user_title = '" . $cp_user_title . "', user_first_name = '" . $cp_user_first_name . "',
					user_last_name = '" . $cp_user_last_name . "',
					user_location_home_address_one = '" . $cp_user_location_home_address_one . "',
					user_location_home_address_two = '" . $cp_user_location_home_address_two . "',
					user_location_city = '" . $cp_user_location_city . "',
					user_location_state = '" . $cp_user_location_state . "',
					user_location_zip_code = '" . $cp_user_location_zip_code . "',
					user_location_is_home = '" . $cp_user_location_is_home . "',
					user_phone_number_work = '" . $cp_user_phone_number_work . "',
					user_phone_number_home = '" . $cp_user_phone_number_home . "',
					user_phone_number_cell = '" . $cp_user_phone_number_cell . "',
					user_phone_number_fax = '" . $cp_user_phone_number_fax . "',
					user_job_title = '" . $cp_user_job_title . "', user_job_company = '" . $cp_user_job_company . "',
					user_is_cmmv = '" . $cp_user_is_cmmv . "', user_cmmv_certification_date = '" . $cp_user_cmmv_certification_date . "',
					user_language_is_bilingual = '" . $cp_user_language_is_bilingual . "',
					user_language_spanish = '" . $cp_user_language_spanish . "',
					user_language_other = '" . $cp_user_language_other . "',
					user_language_other_language = '" . $cp_user_language_other_language . "',
					user_available_time = '" . $cp_user_available_time . "',
					user_available_community = '" . $cp_user_available_community . "',
					user_available_teach_community = '" . $cp_user_available_teach_community . "',
					user_available_administrative_duties = '" . $cp_user_available_administrative_duties . "',
					user_available_clerical_duties = '" . $cp_user_available_clerical_duties . "',
					user_biography = '" . $cp_user_biography . "'
				WHERE user_id = '" . $cp_user_id . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		// Tell the user that their information was updated
		$content = make_content($lang['Control_panel_updated_information']);
	} elseif ( $do == 'editstyle' ) {
		$pagination[] = array('usercp.php?do=editstyle', $lang['Control_panel_change_style']);
		$pagination[] = array(NULL, $lang['Style_updated']);
		
		$content_subtitle = make_title($lang['Style_updated'], true);
		
		$incoming = collect_vars($_POST, array('style_main_color' => MIXED, 'style_main_font' => MIXED, 'style_main_font_size' => MIXED));
		extract($incoming);
		
		$sql = "UPDATE `" . USER . "` SET
					user_style_color = '" . $style_main_color . "',
					user_style_font = '" . $style_main_font . "',
					user_style_font_size = '" . $style_main_font_size . "'
				WHERE user_id = '" . $usercache['user_id'] . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Tell the user that their style was updated
		$content = make_content($lang['Control_panel_updated_style']);
	} elseif ( $do == 'trackhours' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Control_panel_track_hours']);
		$content_subtitle = make_title($lang['Control_panel_track_hours'], true);
		
		while ( $eventid = key($_POST['eventid']) ) {
			// $eventid is the event_id and $_POST['eventid'][$eventid][$event_user_id]
			// is the number of hours
			
			$event_user_id = key($_POST['eventid'][$eventid]);
			$num_hours = ( !empty($_POST['eventid'][$eventid][$event_user_id]) ) ? intval($_POST['eventid'][$eventid][$event_user_id]) : 0;
			
			if ( $num_hours > 0 ) {
				$sql = "INSERT INTO `" . HOUR . "` 
						VALUES('', '" . $eventid . "', '" . $event_user_id . "', 
								'" . $num_hours . "')";
				$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			}
			
			next($_POST['eventid']);
		}
		
		// Tell the user that their style was updated
		$content = make_content($lang['Control_panel_event_hours_thank_you']);
	} elseif ( $do == 'authorizeuser' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Control_panel_authorize_user']);
		$content_subtitle = make_title($lang['Control_panel_authorize_user'], true);
		
		// Make the site location variable for the email text
		$site_location = $site_protocol . $site_url . $site_basedir;

		$incoming = collect_vars($_POST, array('minordo' => MIXED, 'userid' => INT, 'useremail' => MIXED, 'user_region_id' => INT, 'user_volunteer_type' => MIXED));
		extract($incoming);
		
		$user_real_name = get_user_real_name($userid);
		
		// Not thrilled about this right here...
		if ( $user_volunteer_type == $lang['Control_panel_volunteer_types'][0] ) {
			$user_volunteer_type = 0;
			$user_type = VOLUNTEER;
		} elseif ( $user_volunteer_type == $lang['Control_panel_volunteer_types'][1] ) {
			$user_volunteer_type = 0;
			$user_type = VOLUNTEER_STAFF;
		} elseif ( $user_volunteer_type == $lang['Control_panel_volunteer_types'][2] ) {
			$user_volunteer_type = 1;
			$user_type = REGIONAL_DIRECTOR;
		}
		
		if ( $minordo == 'authorize' ) {
			$sql = "UPDATE `" . USER . "` 
					SET user_authorized = '1', user_authorized_id = '" . $usercache['user_id'] . "', 
						user_type = '" . $user_type . "', user_volunteer_type = '" . $user_volunteer_type . "',
						user_region_id = '" . $user_region_id . "'
					WHERE user_id = '" . $userid . "'";
			$email_subject = $lang['Email_user_authorized_subject'];
			$email_text = sprintf($lang['Email_user_authorized_message'], $user_real_name, $site_location);
			
			// Perform the query
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

			// Tell the applicant of their authorization or denial
			if ( $dbconfig['send_email'] == 1 ) {
				send_email($useremail, $email_subject, $email_text);
			}
			
			// Tell the RD or admin thanks for filling out the form
			$content = make_content($lang['Control_panel_authorize_user_thank_you']);
		} elseif ( $minordo == 'decline' ) {
			$sql = "UPDATE `" . USER . "` 
					SET user_authorized = '0', user_type = '" . DENIED . "' 
					WHERE user_id = '" . $userid . "'";
			$email_subject = $lang['Email_user_declined_subject'];
			$email_text = $lang['Email_user_declined_message'];
			
			// Perform the query
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			// Tell the applicant of their authorization or denial
			if ( $dbconfig['send_email'] == 1 ) {
				send_email($useremail, $email_subject, $email_text);
			}
		
			// Tell the RD or admin thanks for filling out the form
			$content = make_content($lang['Control_panel_authorize_user_thank_you']);
		} elseif ( $minordo == 'update' ) {
			$sql = "UPDATE `" . USER . "` 
					SET user_region_id = '" . $user_region_id . "'
					WHERE user_id = '" . $userid . "'";
			
			// Perform the query
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			if ( $dbconfig['send_email'] == 1 ) {
				// Now find all Region Directors of the region they selected and email them
				// telling them there is a new Volunteer in their region
				$region_directors = array();
				$region_directors = get_region_directors($user_region_id);
				
				// Make the site location variable for the email text
				$site_location = $site_protocol . $site_url . $site_basedir;
				
				// Email all of the directors
				for ( $i=0; $i<count($region_directors); $i++) {
					$user_region_director = ($region_directors[$i]['user_first_name'] . ' ' . $region_directors[$i]['user_last_name']);
					$email_text = sprintf($lang['Email_new_user_message'], $user_region_director, $user_real_name, $applicant_id, $site_location);
	
					send_email($region_directors[$i]['user_email'], $lang['Email_new_user_subject'], $email_text);
				}
			}
			
			// Tell the RD or admin thanks for filling out the form
			$content = make_content($lang['Control_panel_update_user_thank_you']);
		}
	} elseif ( $do == 'edituser' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Edit_user']);
		$content_subtitle = make_title($lang['Edit_user'], true);
		
		$incoming = collect_vars($_POST, array('user_name_edit' => MIXED, 'edit_user_id' => INT, 'edit_user_name' => MIXED, 'edit_user_region_id' => INT, 'edit_user_type' => MIXED, 'edit_user_email' => MIXED));
		extract($incoming);
		
		if ( $user_name_edit != $edit_user_name ) {
			// First check to see if the username exists
			if ( check_username_exists($edit_user_name) ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_username_exists']);
			}
		}
		
		// Now check the email address
		if ( !validate_email($edit_user_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		if ( $edit_user_type == $lang['Control_panel_volunteer_types'][0] ) {
			$this_user_type = VOLUNTEER;
			$this_user_volunteer_type = 0;
		} elseif ( $edit_user_type == $lang['Control_panel_volunteer_types'][1] ) {
			$this_user_type = VOLUNTEER_STAFF;
			$this_user_volunteer_type = 0;
		} elseif ( $edit_user_type == $lang['Control_panel_volunteer_types'][2] ) {
			$this_user_type = REGIONAL_DIRECTOR;
			$this_user_volunteer_type = 1;
		}
		
		$sql = "UPDATE `" . USER . "` SET user_name = '" . $edit_user_name . "',
					user_type = '" . $this_user_type . "', user_email = '" . $edit_user_email . "',
					user_region_id = '" . $edit_user_region_id . "',
					user_volunteer_type = '" . $this_user_volunteer_type . "'
				WHERE user_id = '" . $edit_user_id . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_user_edited']);
	} elseif ( $do == 'deleteusers' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Delete_users']);
		$content_subtitle = make_title($lang['Title_delete_users'], true);
		
		// Users are never really deleted, they're just DENIED! :)
		for ( $i=0; $i<count($_POST['userid']); $i++) {
			$sql = "UPDATE `" . USER . "` 
					SET user_authorized = '0', user_type = '" . DENIED . "', user_authorized_id = NULL
					WHERE user_id = '" . intval($_POST['userid'][$i]) . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		}
		
		$content = make_content($lang['Control_panel_user_deleted']);
	}
}

include $root_path . 'includes/page_header.php';

// After the $pagination array has been updated, make the actual pagination
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