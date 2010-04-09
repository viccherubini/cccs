<?php

/**
 * request_program.php
 * Where a user comes to create an event. Very large file with a big form.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_request_program'], false);
$content_subtitle = NULL;

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Request_program']);
$content_pagination = make_pagination($pagination);

$incoming = collect_vars($_POST, array('do' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$p_ids = array();
	$p_names = array();
	
	// Whether or not to show a long list or short list of programs
	if ( !empty($usercache) && $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
		get_programs($p_ids, $p_names);
	} else {
		get_programs($p_ids, $p_names, true, false);
	}

	// Whether or not to show a long list or a short list of programs
	$r_ids = array();
	$r_names = array();
	if ( !empty($usercache) && $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
		get_regions($r_ids, $r_names);
	} else {
		get_regions($r_ids, $r_names, false);
	}
	
	$t_keys = array();
	$t_values = array();
	make_time_array($t_keys, $t_values);
	
	$m_keys = array();
	$m_values = array();
	make_month_array($m_keys, $m_values);
	
	// Possible language array for Spanish or English classes
	$possible_languages = array($lang['Event_audience'][9], $lang['Event_audience'][10]);
		
	// All of the array_unshift()'s you'll see below
	// are a cheap way to add a NULL value onto the
	// beginning of an array. This way, we can check to see
	// if they are NULL, and if so, colorize them!
	array_unshift($p_ids, NULL);
	array_unshift($p_names, NULL);
	$program_title_list = make_drop_down('event_program_id', $p_ids, $p_names, NULL, NULL, 'id="required"');

	$program_language_list = make_drop_down('event_language', $possible_languages, $possible_languages, NULL, NULL, 'id="required"');
	
	array_unshift($lang['Request_projection_equipments'], NULL);
	$projection_equipment_list = make_drop_down('event_projection_equipment', $lang['Request_projection_equipments'], $lang['Request_projection_equipments'], NULL, NULL, 'id="required"');
	
	array_unshift($r_ids, NULL);
	array_unshift($r_names, NULL);
	$region_list = make_drop_down('event_region_id', $r_ids, $r_names, NULL, NULL, 'id="required"');
	
	array_unshift($lang['Request_program_types'], NULL);
	$program_type_list = make_drop_down('event_public', array(NULL, 0, 1), $lang['Request_program_types'], NULL, NULL, 'id="required"');
	
	$start_time_list = make_drop_down('event_start_time', $t_keys, $t_values, date('G', CCCSTIME), NULL, 'id="required"' );
	$end_time_list = make_drop_down('event_end_time', $t_keys, $t_values, date('G', CCCSTIME), NULL, 'id="required"' );
	$time_zone_list = make_drop_down('event_time_zone', $lang['Time_zones'], $lang['Time_zones'], NULL, NULL, 'id="required"' );
	
	$month_list = make_drop_down('event_month', $m_keys, $m_values, date('n', CCCSTIME), NULL, 'id="required"' );
	$day_list = make_drop_down('event_day', make_day_array(), make_day_array(), date('j', CCCSTIME), NULL, 'id="required"' );
	$year_list = make_drop_down('event_year', make_year_array(), make_year_array(), date('Y', CCCSTIME), NULL, 'id="required"' );
	
	$event_start_time_middle_list = make_drop_down('event_start_time_middle', array(0, 30), array(':0', ':30'), NULL, 'id="required"' );
	$event_end_time_middle_list = make_drop_down('event_end_time_middle', array(0, 30), array(':0', ':30'), NULL, 'id="required"' );
	
	$click_here_to_copy = NULL;
	if ( !empty($usercache) && $usercache['user_type'] <= VOLUNTEER ) {
		$click_here_to_copy = $lang['Request_click_to_copy'];
	}
	
	$content = make_content($lang['Register_text']);
	
	$t->set_template( load_template('request_program_form') );
	$t->set_vars( array(
		'L_CONTACT_INFORMATION' => $lang['Request_contact_information'],
		'L_PROVIDE_CONTACT_INFORMATION' => $lang['Request_provide_contact_information'],
		'L_PRESENTATION_INFORMATION' => $lang['Request_presentation_information'],
		'L_PROVIDE_PRESENTATION_INFORMATION' => $lang['Request_provide_presentation_information'],
		'L_CLICK_HERE' => $click_here_to_copy,
		'L_ORGANIZATION' => $lang['Request_organization'],
		'L_YOUR_NAME' => $lang['Request_your_name'],
		'L_CONTACT' => $lang['Request_contact'],
		'L_CONTACT_PHONE_NUMBER' => $lang['Request_contact_phone_number'],
		'L_EMAIL_ADDRESS' => $lang['Request_email_address'],
		'L_YOUR_ADDRESS' => $lang['Request_your_address'],
		'L_YOUR_CITY' => $lang['Request_your_city'],
		'L_YOUR_STATE' => $lang['Request_your_state'],
		'L_YOUR_ZIP_CODE' => $lang['Request_your_zip_code'],
		'L_PHONE_NUMBER' => $lang['Request_phone_number'],
		'L_FAX_NUMBER' => $lang['Request_fax_number'],
		'L_PROGRAM_TITLE' => $lang['Request_program_title'],
		'L_PROGRAM_LANGUAGE' => $lang['Request_program_language'],
		'L_IS_TRADESHOW' => $lang['Request_program_is_tradeshow'],
		'L_EVENT_LOCATION' => $lang['Request_event_location'],
		'L_EVENT_ADDRESS' => $lang['Request_event_address'],
		'L_EVENT_CITY' => $lang['Request_event_city'],
		'L_EVENT_STATE' => $lang['Request_event_state'],
		'L_EVENT_ZIP_CODE' => $lang['Request_event_zip_code'],
		'L_LOCATION_PHONE_NUMBER' => $lang['Request_location_phone_number'],
		'L_EVENT_DATE' => $lang['Request_event_date'],
		'L_EVENT_START_TIME' => $lang['Request_event_start_time'],
		'L_EVENT_END_TIME' => $lang['Request_event_end_time'],
		'L_TIME_ZONE' => $lang['Request_event_time_zone'],
		'L_LOCATION_PHONE_NUMBER' => $lang['Request_location_phone_number'],
		'L_NOTES' => $lang['Request_notes'],
		'L_PROJECTION_EQUIPMENT' => $lang['Request_projection_equipment'],
		'L_ANTICIPATED_AUDIENCE' => $lang['Request_anticipated_audience'],
		'L_EVENT_REGION' => $lang['Request_region'],
		'L_PROGRAM_TYPE' => $lang['Request_program_type'],
		'L_REQUEST_PROGRAM' => $lang['Request_program'],
		'EVENT_PROGRAM_TITLE_LIST' => $program_title_list,
		'EVENT_LANGUAGE_LIST' => $program_language_list,
		'EVENT_DATE_MONTH_LIST' => $month_list,
		'EVENT_DATE_DAY_LIST' => $day_list,
		'EVENT_DATE_YEAR_LIST' => $year_list,
		'EVENT_START_TIME_LIST' => $start_time_list,
		'EVENT_END_TIME_LIST' => $end_time_list,
		'EVENT_TIME_ZONE_LIST' => $time_zone_list,
		'EVENT_PROJECTION_EQUIPMENT_LIST' => $projection_equipment_list,
		'EVENT_REGION_LIST' => $region_list,
		'EVENT_PROGRAM_TYPE_LIST' => $program_type_list,
		'EVENT_START_TIME_MIDDLE_LIST' => $event_start_time_middle_list,
		'EVENT_END_TIME_MIDDLE_LIST' => $event_end_time_middle_list
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'requestprogram' ) {
		$content_title = make_title($lang['Title_event_scheduling'], false);
		
		// Fill out some initial values to make the SQL query look nicer and easier to debug
		$event_authorized = 0;
		$event_agency_specific = 0;
		$event_id = NULL;
		$event_request_notes = NULL;
		$event_driving_directions = NULL;
		$event_user_notes = NULL;
		$event_question = NULL;
		$event_closed = 0;
		$event_complete = 0;
		
		$incoming = collect_vars($_POST, array('event_contact_organization' => MIXED, 'event_your_name' => MIXED, 'event_contact_email' => MIXED, 'event_contact_address' => MIXED, 'event_contact_city' => MIXED, 'event_contact_state' => MIXED, 'event_contact_zip_code' => MIXED, 'event_contact_phone_number' => MIXED, 'event_contact_fax_number' => MIXED, 'event_program_id' => INT, 'event_language' => MIXED, 'event_istradeshow' => INT, 'event_location' => MIXED, 'event_location_address' => MIXED, 'event_location_city' => MIXED, 'event_location_state' => MIXED, 'event_location_zip_code' => MIXED, 'event_location_phone_number' => MIXED, 'event_month' => INT, 'event_day' => INT, 'event_year' => INT, 'event_start_time' => MIXED, 'event_start_time_middle' => MIXED, 'event_end_time' => MIXED, 'event_end_time_middle' => MIXED, 'event_time_zone' => MIXED, 'event_contact_name' => MIXED, 'event_contacts_phone_number' => MIXED, 'event_notes' => MIXED, 'event_projection_equipment' => MIXED, 'event_anticipated_audience' => INT, 'event_region_id' => INT, 'event_public' => INT));
		extract($incoming);
		
		// Validate the phone numbers given
		if ( !validate_email($event_contact_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		// Validate the phone numbers given
		if ( !validate_phone_number($event_contact_phone_number) || !validate_phone_number($event_contact_fax_number) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
		}
		
		$event_contact_state = strtoupper($event_contact_state);
		$event_location_state = strtoupper($event_location_state);
		
		$event_start_date = mktime($event_start_time, $event_start_time_middle, 0, $event_month, $event_day, $event_year);
		$event_end_date = mktime($event_end_time, $event_end_time_middle, 0, $event_month, $event_day, $event_year);
		
		// Ensure the appropriate fields have the right values
		if ( $event_start_time > $event_end_time ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_time']);
		}
		
		// Get the Calendar ID depending on the Region ID
		$sql = "SELECT * FROM `" . REGION . "` r 
				WHERE r.region_id = '" . $event_region_id . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
		$region = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_region'], __LINE__, __FILE__);
		
		$calendar_id = $region['region_calendar_id'];
		
		$db->freeresult($result);
		
		// Find out if the user is:
		// a. Logged in
		// b. An RD or Admin
		// c. And if so, automatically authorize the event
		if ( !empty($usercache) && $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
			$event_authorized = 1;
			$event_driving_directions = MAPQUEST;	// They'll never get to this step otherwise :/
		}
		
		if ( $event_istradeshow == 1 ) {
			$event_program_id = PROGRAM_TRADESHOW;
		}
		
		// Let's build this huge query
		$sql = "INSERT INTO `" . EVENT . "`
				VALUES ( '" . $event_id . "', '" . $calendar_id . "', '" . $event_region_id . "', '" . $event_authorized . "',
						'" . $event_public . "', '" . $event_agency_specific . "', '" . $event_closed . "', '" . $event_program_id . "',
						'" . $event_language . "',
						'" . $event_istradeshow . "', '" . $event_your_name . "', '" . $event_contact_organization . "', '" . $event_contact_name . "', 
						'" . $event_contact_email . "', '" . $event_contact_address . "', '" . $event_contact_city . "', '" . $event_contact_state . "',
						'" . $event_contact_zip_code . "', '" . $event_contact_phone_number . "', '" . $event_contact_fax_number . "',
						'" . $event_location . "', '" . $event_location_address . "', '" . $event_location_city . "', 
						'" . $event_location_state . "', '" . $event_location_zip_code . "', '" . $event_location_phone_number . "',
						'" . $event_contacts_phone_number . "', '" . $event_start_date . "', '" . $event_end_date . "', 
						'" . $event_time_zone . "',
						'" . $event_notes . "', '" . $event_projection_equipment . "', '" . $event_anticipated_audience . "', 
						'" . $event_driving_directions . "', '" . $event_user_notes . "', '" . $event_question . "',
						'" . $event_complete . "'
				)";
		// Insert the new event
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
		// Get the ID of the newly inserted ID
		$new_event_id = $db->insertid();
		$fake_event_id = make_fake_event_id($new_event_id);	// Cheap-o function to make pretty ID
		
		if ( $dbconfig['send_email'] == 1 ) {	
			// Now email all of the directors alerting them of the newly created event
			$region_directors = array();
			$region_directors = get_region_directors($event_region_id);
			
			// Make the site location variable for the email text
			$site_location = $site_protocol . $site_url . $site_basedir;
			
			// Email all of the directors
			for ( $i=0; $i<count($region_directors); $i++) {
				$email_text = sprintf($lang['Email_new_request_message'], ($region_directors[$i]['user_first_name'] . ' ' . $region_directors[$i]['user_last_name']), $new_event_id, $site_location);
				send_email($region_directors[$i]['user_email'], $lang['Email_new_request_subject'], $email_text);
			}
		}
		
		// Tell the user about the newly inserted Event
		$content = make_content(sprintf($lang['Request_thank_you'], $fake_event_id));
	}
}

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