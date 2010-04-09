<?php

/**
 * printevent.php
 * Shows a printable version of an event's data.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

// See if they are an applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

// Make the page pretty! This is because the page header isn't
// included in this page because it unecessarily prints out
// code we don't need

include $root_path . 'includes/page_printheader.php';

$incoming = collect_vars($_REQUEST, array('eventid' => INT));
extract($incoming);

// Ensure we're given a region id
if ( !is_numeric($eventid) || $eventid <= 0 ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
}

$e = array();
$e = get_event_data($eventid);

$region_name = NULL;
$region_name = get_region_name($e['event_region_id']);
		
if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
	// Now, check to see if there is a response for this event.
	// If so, add additional fields to the view event template
	$sql = "SELECT * FROM `" . RESPONSE . "` r 
			WHERE r.response_event_id = '" . $eventid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Bingo
	if ( $db->numrows($result) == 1 ) {
		$response = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
		
		$grantid = $response['response_grant_id'];
		
		// For some reason, they up and delete grants. Thus, this whole page
		// explodes if they try to come to the page and the grant has been
		// deleted. Thus, this check to see if the grant actually exists
		$g = get_grant_data($grantid);
		if ( empty($g) ) {
			$grantid = 0;
		}
		
		if ( $grantid != 0 ) {
			$grant_name = get_grant_name($grantid);
		} else {
			$grant_name = $lang['Control_panel_no_grant_provided'];
		}
		
		$t->set_template( load_template('calendar_admin_view_event_additional_print') );
		$t->set_vars( array(
			'L_FINANCE_INFORMATION' => $lang['Calendar_finance_information'],
			'L_BILLED_WORKSHOP' => $lang['Program_tracking_workshop_billed'],
			'L_HOW_MUCH_MONEY' => $lang['Program_tracking_how_much_billed'],
			'L_WHICH_GRANT' => $lang['Program_tracking_who_grant'],
			'L_GRANT_AMOUNT' => $lang['Program_tracking_how_much_grant'],
			'EVENT_RESPONSE_WORKSHOP_BILLED' => yes_no($response['response_billed']),
			'EVENT_RESPONSE_WORKSHOP_BILLED_AMOUNT' => number_format($response['response_billed_amount'], 2),
			'EVENT_RESPONSE_WORKSHOP_GRANT_NAME' => $grant_name,
			'EVENT_RESPONSE_WORKSHOP_GRANT_AMOUNT' => number_format($response['response_grant_amount'], 2)
			)
		);
		$event_additional_information = $t->parse($dbconfig['show_template_name']);
	}
}

$t->set_template( load_template('calendar_all_view_event_print') );
$t->set_vars( array(
	'L_EVENT_ID' => $lang['Event_event_id'],
	'L_NOTES' => $lang['Event_notes'],
	'L_DATE' => $lang['Event_date'],
	'L_TIME' => $lang['Event_time'],
	'L_LOCATION' => $lang['Event_location'],
	'L_AGENCY_SPECIFIC' => $lang['Event_agency_specific'],
	'L_DRIVING_DIRECTIONS' => $lang['Event_driving_directions'],
	'L_MAPQUEST_DRIVING_DIRECTIONS' => $lang['Event_mapquest_driving_directions'],
	'L_CONTACT_INFORMATION' => $lang['Event_contact_information'],
	'L_ORGANIZATION' => $lang['Event_organization'],
	'L_REQUESTERS_NAME' => $lang['Event_requesters_name'],
	'L_CONTACT' => $lang['Event_contact'],
	'L_EMAIL_ADDRESS' => $lang['Event_email_address'],
	'L_ADDRESS' => $lang['Event_address'],
	'L_CITY' => $lang['Event_city'],
	'L_STATE' => $lang['Event_state'],
	'L_ZIP_CODE' => $lang['Event_zip_code'],
	'L_PHONE_NUMBER' => $lang['Event_phone_number'],
	'L_FAX_NUMBER' => $lang['Event_fax_number'],
	'L_PRESENTATION_INFORMATION' => $lang['Event_presentation_information'],
	'L_PROGRAM_TITLE' => $lang['Event_program_title'],
	'L_PROGRAM_LANGUAGE' => $lang['Request_program_language'],
	'L_EVENT_LOCATION' => $lang['Event_location'],
	'L_EVENT_ADDRESS' => $lang['Event_address'],
	'L_EVENT_CITY' => $lang['Event_city'],
	'L_EVENT_STATE' => $lang['Event_state'],
	'L_EVENT_ZIP_CODE' => $lang['Event_zip_code'],
	'L_LOCATION_PHONE_NUMBER' => $lang['Event_phone_number'],
	'L_PROJECTION_EQUIPMENT' => $lang['Event_projection_equipment'],
	'L_ANTICIPATED_AUDIENCE' => $lang['Event_anticipated_audience'],
	'L_EVENT_REGION' => $lang['Event_region'],
	'REPORT_DATE' => date($dbconfig['date_format'], $e['event_start_date'] ),
	'EVENT_ID' => $eventid,
	'EVENT_PROGRAM_TITLE' => $e['program_name'],
	'EVENT_LANGUAGE' => $e['event_language'],
	'EVENT_USER_NOTES' => $e['event_user_notes'],
	'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
	'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
	'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
	'EVENT_TIME_ZONE' => $e['event_time_zone'],
	'EVENT_LOCATION' => $e['event_location'],
	'EVENT_AGENCY_SPECIFIC' => ($e['event_agency_specific'] == 1 ? $lang['Yes'] : $lang['No']),
	'EVENT_DRIVING_DIRECTIONS' => $e['event_driving_directions'],
	'EVENT_CONTACT_ORGANIZATION' => $e['event_contact_organization'],
	'EVENT_YOUR_NAME' => $e['event_your_name'],
	'EVENT_CONTACT' => $e['event_contact_name'],
	'EVENT_EMAIL_ADDRESS' => $e['event_contact_email'],
	'EVENT_CONTACT_ADDRESS' => $e['event_contact_address'],
	'EVENT_CONTACT_STATE' => $e['event_contact_state'],
	'EVENT_CONTACT_CITY' => $e['event_contact_city'],
	'EVENT_CONTACT_STATE' => $e['event_contact_state'],
	'EVENT_CONTACT_ZIP_CODE' => $e['event_contact_zip_code'],
	'EVENT_CONTACT_PHONE_NUMBER' => $e['event_contact_phone_number'],
	'EVENT_CONTACT_FAX_NUMBER' => $e['event_contact_fax_number'],
	'EVENT_LOCATION_ADDRESS' => $e['event_location_address'],
	'EVENT_LOCATION_CITY' => $e['event_location_city'],
	'EVENT_LOCATION_STATE' => $e['event_location_state'],
	'EVENT_LOCATION_ZIP_CODE' => $e['event_location_zip_code'],
	'EVENT_LOCATION_PHONE_NUMBER' => $e['event_location_phone_number'],
	'EVENT_NOTES' => $e['event_notes'],
	'EVENT_PROJECTION_EQUIPMENT' => $e['event_projection_equipment'],
	'EVENT_ANTICIPATED_AUDIENCE' => $e['event_anticipated_audience'],
	'EVENT_REGION' => $region_name,
	'EVENT_ADDITIONAL_INFORMATION' => $event_additional_information,
	)
);
$content = $t->parse($dbconfig['show_template_name']);

unset($e, $region_name, $register_button, $duplicate_button, $event_additional_information);

$t->set_template( load_template('overall_body') );
$t->set_vars( array(
	'BODY_CONTENT' => $content,
	'BODY_COPYRIGHT' => NULL
	)
);
print $t->parse($dbconfig['show_template_name']);
		
include $root_path . 'includes/page_exit.php';

?>