<?php

/**
 * calendar.php
 * One massive file that allows a Volunteer, RD, or Admin to manage all
 * events and other calendar items to run the site.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

// See if they are an applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

$incoming = collect_vars($_REQUEST, array('do' => MIXED, 'eventid' => INT, 'regionid' => INT, 'viewevents' => MIXED));
extract($incoming);

// Ensure we're given a region ID
if ( !is_numeric($regionid) || $regionid <= 0 ) {
	$regionid = $usercache['user_region_id'];

	if ( !is_numeric($regionid) || $regionid <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
	}
}

// Simple base pagination
$pagination[] = array('page.php?pagename=cmmv_business_center', $lang['CMMV_business_center']);
$pagination[] = array('calendar.php?regionid=' . $regionid, $lang['Calendar']);

$content_title = make_title($lang['Calendar'], false);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'viewevent' ) {
		// Ensure $eventid isn't bad data
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$pagination[] = array(NULL, $lang['View_event']);
		
		$e = array();
		$e = get_event_data($eventid);
		
		$region_name = NULL;
		$region_name = get_region_name($e['event_region_id']);
		
		// Check to see if the currently logged in user is registered
		// for this event. 
		$user_registered = false;
		$user_registered = user_registered_event($usercache['user_id'], $eventid);
		
		// Find out what buttons to make for this form
		$register_button = NULL;	// Register to teach this event
		$duplicate_button = NULL;	// Duplicate this event
		$register_user_form = NULL;	// Register a person to attend this event
		
		// See if the user can register for this event.
		// Ensures the event does not already have a Volunteer
		// to teach it and that the currently logged in 
		// user hasn't requested to teach the event.
		if ( !check_event_authorized($e['event_id']) ) {
			if ( $user_registered == false && $e['event_public'] == 1 ) {
				$register_button = make_input_box(SUBMIT, NULL, $lang['Event_register'], 'class="btn" onclick="set_action(\'registerevent\')"');
			}
		}
		
		// Check to see if the user logged in can do some special actions 
		// such as duplicate the event or register a user for the event.
		if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
			if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $e['event_region_id'] ) 
				|| $usercache['user_type'] == ADMINISTRATOR ) {
				$duplicate_button = make_input_box(SUBMIT, NULL, $lang['Event_duplicate'], 'class="btn" onclick="set_action(\'duplicateevent\')"');
				
				$view_roster_link = " | " . make_link('roster.php?eventid=' . $e['event_id'] . '&amp;regionid=' . $e['event_region_id'], $lang['Event_view_roster']);
			}
			
			// Now, check to see if there is an evaluation for this event.
			// If so, add additional fields to the view event template
			$sql = "SELECT * FROM `" . RESPONSE . "` r 
					WHERE r.response_event_id = '" . $eventid . "' AND r.response_completed = '1'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			// There is an evaluation
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

				// Show the additional evaluation information
				$t->set_template( load_template('calendar_admin_view_event_additional') );
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
			
			// Finally, add the Register a Person for this event form. This is for
			// events that are in the past can allow
			$t->set_template( load_template('calendar_admin_register_user') );
			$t->set_vars( array(
				'L_REGISTER' => $lang['Click_to_register'],
				'REGION_ID' => $regionid,
				'EVENT_ID' => $eventid
				)
			);
			$register_user_form = $t->parse($dbconfig['show_template_name']);
				
		}
		
		// Show all of the event data
		$t->set_template( load_template('calendar_all_view_event', false) );
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
			'L_CONTACTS_PHONE_NUMBER' => $lang['Request_contact_phone_number'],
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
			'L_PRINT_EVENT' => $lang['Print_event'],
			'REGION_ID' => $regionid,
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
			'EVENT_CONTACTS_PHONE_NUMBER' => $e['event_contacts_phone_number'],
			'EVENT_NOTES' => $e['event_notes'],
			'EVENT_PROJECTION_EQUIPMENT' => $e['event_projection_equipment'],
			'EVENT_ANTICIPATED_AUDIENCE' => $e['event_anticipated_audience'],
			'EVENT_REGION' => $region_name,
			'EVENT_ADDITIONAL_INFORMATION' => $event_additional_information,
			'FORM_REGISTER_EVENT' => $register_button,
			'FORM_ADMIN_DUPLICATE_EVENT' => $duplicate_button,
			'FORM_REGISTER_USER_FORM' => $register_user_form,
			'VIEW_ROSTER_LINK' => $view_roster_link
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($e, $region_name, $register_button, $duplicate_button, $event_additional_information);
	} elseif ( $do == 'viewvolunteers' ) {
		// Ensure $eventid isn't bad data since it comes from GET
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$e = array();
		$e = get_event_data($eventid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array(NULL, $lang['View_volunteers']);
		
		// Whether or not this is an authorized list
		// If this is true, then we are showing only the user who is assigned
		// to the event.
		$authorized = false;
		
		// Get all of the registered but not authorized Volunteers for this event
		$users = array();
		$users = get_registered_users($eventid);
		
		$volunteer_list = NULL;	// List of all of the unauthorized Volunteers to teach the event
		$volunteer_list_single = NULL;	// This event is authorized and this is the single Volunteer to teach it
		$user_list = NULL;
				
		$calendar_title = $lang['Calendar_unassigned_users'];
		$calendar_action = $lang['Calendar_assign_user'];
		
		$form_action = 'authorizeuser';
		
		$uc = count($users);
		for ( $i=0; $i<$uc; $i++ ) {
			// Allow the RD/Admin to authorize this person, otherwise, if
			// the event has already occurred, can't authorize the Volunteer
			if ( $e['event_end_date'] > CCCSTIME ) {
				$register_button = make_input_box(RADIO_BUTTON, 'update_user_id', $users[$i]['user_id']);	
			}
			
			// If the Volunteer isn't already authorized, allow the
			// the RD/Admin to authorize them
			if ( $users[$i]['assignment_authorized'] == 0 ) {
				$t->set_template( load_template('calendar_admin_assign_users_item') );
				$t->set_vars( array(
					'USER_ID' => $users[$i]['user_id'],
					'USER_FIRST_NAME' => $users[$i]['user_first_name'],
					'USER_LAST_NAME' => $users[$i]['user_last_name'],
					'USER_EMAIL' => $users[$i]['user_email'],
					'USER_REGISTER_BUTTON' => $register_button
					)
				);
				$volunteer_list .= $t->parse($dbconfig['show_template_name']);
			} elseif ( $users[$i]['assignment_authorized'] == 1 ) {
				// Gets rid of people who have registered, but not authorized/assigned
				// This way, only this section of code is needed for
				// authorized events/past events so that the one and only
				// authorized Volunteer is shown for this event.
				unset($volunteer_list);
				
				$t->set_template( load_template('calendar_admin_assign_users_item') );
				$t->set_vars( array(
					'USER_ID' => $users[$i]['user_id'],
					'USER_FIRST_NAME' => $users[$i]['user_first_name'],
					'USER_LAST_NAME' => $users[$i]['user_last_name'],
					'USER_EMAIL' => $users[$i]['user_email'],
					'USER_REGISTER_BUTTON' => $register_button
					)
				);
				$volunteer_list_single = $t->parse($dbconfig['show_template_name']);
				
				$calendar_title = $lang['Calendar_assigned_users'];
				$calendar_action = $lang['Calendar_unassign_user'];
				
				// Whether or not there are any authorized Volunteers
				// for this event, in which case, there are.
				$authorized = true;
				
				$form_action = 'unregisterevent';
			}
			
			unset($register_button);
		}
		
		// Get a list of users from this region to assign to the event		
		if ( $authorized == false ) {
			$users = get_user_list($regionid);
			
			$ids = array('');
			$names = array('');
			
			$uc = count($users);
			for ( $i=0; $i<$uc; $i++ ) {
				$ids[] = $users[$i]['user_id'];
				$names[] = $users[$i]['user_first_name'] . ' ' . $users[$i]['user_last_name'] . ' (' . $users[$i]['user_email'] . ')';
			}
			
			$user_list = make_drop_down('user_assign_id', $ids, $names);
		}

		$volunteer_list = (empty($volunteer_list_single) ? $volunteer_list : $volunteer_list_single);

		$t->set_template( load_template('calendar_admin_assign_users') );
		$t->set_vars( array(
			'L_TITLE_NAME' => $calendar_title,
			'L_ID' => $lang['Calendar_id'],
			'L_USER_NAME' => $lang['Calendar_user_name'],
			'L_ACTION' => $calendar_action,
			'L_PERFORM_ACTION' => $lang['Calendar_perform_action'],
			'USER_ITEM_LIST' => $volunteer_list,
			'USER_VOLUNTEER_LIST' => $user_list,
			'ACTION' => $form_action,
			'EVENT_ID' => $eventid,
			'REGION_ID' => $regionid
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($e, $volunteer_list, $user_list, $volunteer_list_single);
	} elseif ( $do == 'viewrequest' ) {
		// The user is viewing a request (must be an RD or Admin)
		$pagination[] = array(NULL, $lang['View_request']);
		
		// First ensure this event is not already authorized... big bug otherwise!
		$e = array();
		$e = get_event_data($eventid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		if ( $e['event_authorized'] == 1 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_event_already_authorized']);
		}
		
		// Get all the users available
		$users = get_user_list($usercache['user_region_id']);
		
		$ids = array('');
		$names = array('');

		$uc = count($users);
		for ( $i=0; $i<$uc; $i++ ) {
			$ids[] = $users[$i]['user_id'];
			$names[] = $users[$i]['user_first_name'] . ' ' . $users[$i]['user_last_name'] . ' (' . $users[$i]['user_email'] . ')';
		}

		$user_list = make_drop_down('event_user_id', $ids, $names);

		$e = array();
		$e = get_event_data($eventid);

		if ( is_numeric($e['event_region_id']) && $e['event_region_id'] > 0 ) {
			$region_name = get_region_name($e['event_region_id']);
		} else {
			$region_name = $lang['Global'];
		}
		
		$t->set_template( load_template('calendar_admin_authorize_request', false) );
		$t->set_vars( array(
			'L_CONTACT_INFORMATION' => $lang['Event_contact_information'],
			'L_EVENT_ID' => $lang['Event_event_id'],
			'L_ORGANIZATION' => $lang['Event_organization'],
			'L_NAME' => $lang['Event_requesters_name'],
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
			'L_EVENT_LOCATION' => $lang['Event_location'],
			'L_EVENT_ADDRESS' => $lang['Event_address'],
			'L_EVENT_CITY' => $lang['Event_city'],
			'L_EVENT_STATE' => $lang['Event_state'],
			'L_EVENT_ZIP_CODE' => $lang['Event_zip_code'],
			'L_EVENT_DATE' => $lang['Event_date'],
			'L_EVENT_TIME' => $lang['Event_time'],
			'L_LOCATION_PHONE_NUMBER' => $lang['Event_phone_number'],
			'L_NOTES' => $lang['Event_notes'],
			'L_PROJECTION_EQUIPMENT' => $lang['Event_projection_equipment'],
			'L_ANTICIPATED_AUDIENCE' => $lang['Event_anticipated_audience'],
			'L_EVENT_REGION' => $lang['Event_region'],
			'L_AUTHORIZE_EVENT_HELP' => $lang['Event_authorize_event_help'],
			'L_ACTIONS_FOR_REQUEST' => $lang['Event_actions_for_request'],
			'L_MAPQUEST_URL' => $lang['Event_mapquest_driving_directions'],
			'L_ASSIGN_VOLUNTEER' => $lang['Calendar_assign_user'],
			'L_AGENCY_SPECIFIC' => $lang['Event_agency_specific'],
			'L_AUTHORIZE_REQUEST' => $lang['Event_authorize_request'],
			'L_DECLINE_REQUEST' => $lang['Event_decline_request'],
			'L_ASK_A_QUESTION' => $lang['Event_ask_a_question'],
			'EVENT_ID' => $e['event_id'],
			'EVENT_CONTACT_ORGANIZATION' => $e['event_contact_organization'],
			'EVENT_YOUR_NAME' => $e['event_your_name'],
			'EVENT_CONTACT' => $e['event_contact_name'],
			'EVENT_EMAIL_ADDRESS' => $e['event_contact_email'],
			'EVENT_CONTACT_ADDRESS' => $e['event_contact_address'],
			'EVENT_CONTACT_CITY' => $e['event_contact_city'],
			'EVENT_CONTACT_STATE' => $e['event_contact_state'],
			'EVENT_CONTACT_ZIP_CODE' => $e['event_contact_zip_code'],
			'EVENT_CONTACT_PHONE_NUMBER' => $e['event_contact_phone_number'],
			'EVENT_CONTACT_FAX_NUMBER' => $e['event_contact_fax_number'],
			'EVENT_PROGRAM_TITLE' => stripslashes($e['program_name']),
			'EVENT_LOCATION' => $e['event_location'],
			'EVENT_LOCATION_ADDRESS' => $e['event_location_address'],
			'EVENT_LOCATION_CITY' => $e['event_location_city'],
			'EVENT_LOCATION_STATE' => $e['event_location_state'],
			'EVENT_LOCATION_ZIP_CODE' => $e['event_location_zip_code'],
			'EVENT_LOCATION_PHONE_NUMBER' => $e['event_location_phone_number'],
			'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
			'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
			'EVENT_NOTES' => $e['event_notes'],
			'EVENT_PROJECTION_EQUIPMENT' => $e['event_projection_equipment'],
			'EVENT_ANTICIPATED_AUDIENCE' => $e['event_anticipated_audience'],
			'EVENT_REGION' => $region_name,
			'EVENT_VOLUNTEER_LIST' => $user_list,
			'REGION_ID' => $regionid
			)
		);

		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'editevent' ) {
		$pagination[] = array(NULL, $lang['Edit_event']);
		
		// Ensure $eventid isn't bad data since it comes from GET
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$e = array();
		$e = get_event_data($eventid);

		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$p_ids = array();
		$p_names = array();
		array_unshift($p_ids, NULL);
		array_unshift($p_names, NULL);
		get_programs($p_ids, $p_names);
		
		$possible_languages = array($lang['Event_audience'][9], $lang['Event_audience'][10]);
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names);
		$region_name = get_region_name($e['event_region_id']);
				
		$t_keys = array();
		$t_values = array();
		make_time_array($t_keys, $t_values);
		
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);
		
		$program_title_list = make_drop_down('event_program_id', $p_ids, $p_names, $e['event_program_id'], NULL, 'id="required"' );
		$program_language_list = make_drop_down('event_language', $possible_languages, $possible_languages, $e['event_language'], NULL, 'id="required"');
		$projection_equipment_list = make_drop_down('event_projection_equipment', $lang['Request_projection_equipments'], $lang['Request_projection_equipments'], $e['event_projection_equipment'] );
		$region_list = make_drop_down('event_region_id', $r_ids, $r_names, $e['event_region_id'] );
		$program_type_list = make_drop_down('event_public', array(0,1), $lang['Request_program_types'], $e['event_public'] );

		$start_time_list = make_drop_down('event_start_time', $t_keys, $t_values, date('G', $e['event_start_date']) );
		$end_time_list = make_drop_down('event_end_time', $t_keys, $t_values, date('G', $e['event_end_date']) );
		$time_zone_list = make_drop_down('event_time_zone', $lang['Time_zones'], $lang['Time_zones'], $e['event_time_zone'], NULL, 'id="required"' );
		
		$month_list = make_drop_down('event_month', $m_keys, $m_values, date('n', $e['event_start_date']) );
		$day_list = make_drop_down('event_day', make_day_array(), make_day_array(), date('j', $e['event_start_date']) );
		$year_list = make_drop_down('event_year', make_year_array(), make_year_array(), date('Y', $e['event_start_date']) );
	
		$event_start_time_middle_list = make_drop_down('event_start_time_middle', array(0, 30), array(':0', ':30'), date('i', $e['event_start_date']) );
		$event_end_time_middle_list = make_drop_down('event_end_time_middle', array(0, 30), array(':0', ':30'), date('i', $e['event_end_date']) );
	
		$event_istradeshow_checked = ( $e['event_istradeshow'] == 1 ? 'checked="checked"' : NULL );
		
		$t->set_template( load_template('calendar_admin_edit_event_form', false) );
		$t->set_vars( array(
			'L_CONTACT_INFORMATION' => $lang['Event_contact_information'],
			'L_PROVIDE_CONTACT_INFORMATION' => $lang['Event_provide_contact_information'],
			'L_PRESENTATION_INFORMATION' => $lang['Event_presentation_information'],
			'L_PROVIDE_PRESENTATION_INFORMATION' => $lang['Event_provide_presentation_information'],
			'L_ORGANIZATION' => $lang['Event_organization'],
			'L_YOUR_NAME' => $lang['Event_requesters_name'],
			'L_CONTACT' => $lang['Event_contact'],
			'L_EMAIL_ADDRESS' => $lang['Event_email_address'],
			'L_YOUR_ADDRESS' => $lang['Event_address'],
			'L_YOUR_CITY' => $lang['Event_city'],
			'L_YOUR_STATE' => $lang['Event_state'],
			'L_YOUR_ZIP_CODE' => $lang['Event_zip_code'],
			'L_PHONE_NUMBER' => $lang['Event_phone_number'],
			'L_FAX_NUMBER' => $lang['Event_fax_number'],
			'L_MAPQUEST' => $lang['Event_mapquest_driving_directions'],
			'L_PROGRAM_TITLE' => $lang['Event_program_title'],
			'L_PROGRAM_LANGUAGE' => $lang['Request_program_language'],
			'L_IS_TRADESHOW' => $lang['Request_program_is_tradeshow'],
			'L_EVENT_LOCATION' => $lang['Event_location'],
			'L_EVENT_ADDRESS' => $lang['Event_address'],
			'L_EVENT_CITY' => $lang['Event_city'],
			'L_EVENT_STATE' => $lang['Event_state'],
			'L_EVENT_ZIP_CODE' => $lang['Event_zip_code'],
			'L_LOCATION_PHONE_NUMBER' => 'adf',
			'L_EVENT_DATE' => $lang['Event_date'],
			'L_EVENT_START_TIME' => $lang['Event_start_time'],
			'L_EVENT_END_TIME' => $lang['Event_end_time'],
			'L_TIME_ZONE' => $lang['Request_event_time_zone'],
			'L_LOCATION_PHONE_NUMBER' => $lang['Event_phone_number'],
			'L_NOTES' => $lang['Event_notes'],
			'L_PROJECTION_EQUIPMENT' => $lang['Event_projection_equipment'],
			'L_ANTICIPATED_AUDIENCE' => $lang['Event_anticipated_audience'],
			'L_EVENT_REGION' => $lang['Event_region'],
			'L_PROGRAM_TYPE' => $lang['Event_program_type'],
			'L_REQUEST_PROGRAM' => $lang['Event_program'],
			'L_UPDATE_EVENT' => $lang['Event_update_event'],
			'EVENT_ID' => $eventid,
			'REGION_ID' => $regionid,
			'EVENT_CONTACT_ORGANIZATION' => $e['event_contact_organization'],
			'EVENT_CONTACT_NAME' => $e['event_contact_name'],
			'EVENT_YOUR_NAME' => $e['event_your_name'],
			'EVENT_CONTACT_EMAIL' => $e['event_contact_email'],
			'EVENT_CONTACT_ADDRESS' => $e['event_contact_address'],
			'EVENT_CONTACT_CITY' => $e['event_contact_city'],
			'EVENT_CONTACT_STATE' => $e['event_contact_state'],
			'EVENT_CONTACT_ZIP_CODE' => $e['event_contact_zip_code'],
			'EVENT_CONTACT_PHONE_NUMBER' => $e['event_contact_phone_number'],
			'EVENT_CONTACT_FAX_NUMBER' => $e['event_contact_fax_number'],
			'EVENT_DRIVING_DIRECTIONS' => $e['event_driving_directions'],
			'EVENT_PROGRAM_TITLE_LIST' => $program_title_list,
			'EVENT_LANGUAGE_LIST' => $program_language_list,
			'EVENT_ISTRADESHOW_CHECKED' => $event_istradeshow_checked,
			'EVENT_LOCATION' => $e['event_location'],
			'EVENT_LOCATION_ADDRESS' => $e['event_location_address'],
			'EVENT_LOCATION_CITY' => $e['event_location_city'],
			'EVENT_LOCATION_STATE' => $e['event_location_state'],
			'EVENT_LOCATION_ZIP_CODE' => $e['event_location_zip_code'],
			'EVENT_LOCATION_PHONE_NUMBER' => $e['event_location_phone_number'],
			'EVENT_DATE_MONTH_LIST' => $month_list,
			'EVENT_DATE_DAY_LIST' => $day_list,
			'EVENT_DATE_YEAR_LIST' => $year_list,
			'EVENT_START_TIME_LIST' => $start_time_list,
			'EVENT_END_TIME_LIST' => $end_time_list,
			'EVENT_TIME_ZONE_LIST' => $time_zone_list,
			'EVENT_NOTES' => $e['event_notes'],
			'EVENT_PROJECTION_EQUIPMENT_LIST' => $projection_equipment_list,
			'EVENT_ANTICIPATED_AUDIENCE' => $e['event_anticipated_audience'],
			'EVENT_REGION_LIST' => $region_list,
			'EVENT_PROGRAM_TYPE_LIST' => $program_type_list,
			'EVENT_START_TIME_MIDDLE_LIST' => $event_start_time_middle_list,
			'EVENT_END_TIME_MIDDLE_LIST' => $event_end_time_middle_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'deleteevent' ) {
		$pagination[] = array(NULL, $lang['Delete_event']);
		
		// Ensure $eventid isn't bad data since it comes from GET
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}

		$e = array();
		$e = get_event_data($eventid);
	
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		
		// First, delete all of the assignments, authorized or not, assigned with this event
		$sql = "DELETE FROM `" . ASSIGNMENT . "` 
				WHERE assignment_event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Delete the event hours
		$sql = "DELETE FROM `" . HOUR . "` 
				WHERE hour_event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Delete the event response
		$sql = "DELETE FROM `" . RESPONSE . "` 
				WHERE response_event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Delete from the registration queue
		$sql = "DELETE FROM `" . REGISTER_QUEUE . "` 
				WHERE queue_event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Now we can delete the event
		$sql = "DELETE FROM `" . EVENT . "` 
				WHERE event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
		$content = make_content($lang['Calendar_deleted_event']);
	} elseif ( $do == 'pastevents' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $regionid ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array(NULL, $lang['View_past_events']);

		$events = get_past_calendar($regionid);
		$ec = count($events);
		for ( $i=0; $i<$ec; $i++ ) {
			$event_public = ( $events[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;

			$t->set_template( load_template('calendar_admin_event') );
			$t->set_vars( array(
				'L_VIEW' => $lang['Calendar_view'],
				'L_EDIT' => $lang['Calendar_edit'],
				'L_DELETE' => $lang['Calendar_delete'],
				'EVENT_PUBLIC' => $event_public,
				'EVENT_ID' => $events[$i]['event_id'],
				'EVENT_TITLE' => $events[$i]['program_name'],
				'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
				'EVENT_ORGANIZATION' => $events[$i]['event_contact_organization'],
				'REGION_ID' => $regionid
				)
			);
			$event_item_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('calendar_admin') );
		$t->set_vars( array(
			'L_ID' => $lang['Calendar_id'],
			'L_EVENT' => $lang['Calendar_event'],
			'L_DATE' => $lang['Calendar_date'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_VOLUNTEERS' => $lang['Calendar_volunteers'],
			'L_EDIT' => $lang['Calendar_edit'],
			'L_DELETE' => $lang['Calendar_delete'],
			'L_CALENDAR_NAME' => $lang['Calendar_past_events'],
			'EVENT_ITEM_LIST' => $event_item_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'unregisterevent' ) {
		// This section is for anyone (Volunteers, generically), saying
		// they are no longer interested in teaching an event.
		
		$pagination[] = array(NULL, $lang['Unregister_event']);
		
		$event_user_id = $usercache['user_id'];
		
		// Ensure $eventid isn't bad data since it comes from GET
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		// Ensure $event_user_id isn't bad data
		if ( !is_numeric($event_user_id) || $event_user_id <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$e = array();
		$e = get_event_data($eventid);
		
		// Can't unregister from an event that already occurred.
		if ( $e['event_end_date'] < CCCSTIME ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_cant_unassign_user']);
		}
		
		$sql = "DELETE FROM `" . ASSIGNMENT . "` 
				WHERE assignment_event_id = '" . $eventid . "' 
					AND assignment_user_id = '" . $event_user_id . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Calendar_unregister_event_thank_you']);
	} else {
		// No $do set, so show the user the calendar depending on what type
		// of user they are.
		
		$region = get_region_data($regionid);
		$calendar_name = get_calendar_name($region['region_calendar_id']);

		$content_title = make_title($calendar_name, false);

		// Set up the initial event pagination
		$numdays = collect_viewevents($viewevents);
		$view_events_list = make_drop_down('viewevents', $lang['View_events_future_name'], $lang['View_events_future'], $viewevents);
		
		// If the user is an Admin or RD viewing their region
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $regionid ) 
			|| $usercache['user_type'] == ADMINISTRATOR ) {
			// First order, show all requests
			$requests = array();
			$requests = get_request_data($regionid);
			
			$event_item_list = NULL;

			// Print all of the requests to the screen			
			$rc = count($requests);
			for ( $i=0; $i<$rc; $i++ ) {
				$event_public = ( $requests[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('calendar_admin_request_item') );
				$t->set_vars( array(
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $requests[$i]['event_id'],
					'EVENT_CONTACT_ORGANIZATION' => $requests[$i]['event_contact_organization'],
					'EVENT_DATE' => date($dbconfig['date_format'], $requests[$i]['event_start_date']),
					'EVENT_START_TIME' => date($dbconfig['time_format'], $requests[$i]['event_start_date']),
					'EVENT_END_TIME' => date($dbconfig['time_format'], $requests[$i]['event_end_date']),
					'EVENT_LOCATION' => $requests[$i]['event_location'],
					'REGION_ID' => $regionid
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}
			
			// Parse the Request Calendar
			$t->set_template( load_template('calendar_admin_request') );
			$t->set_vars( array(
				'L_ID' => $lang['Calendar_id'],
				'L_ORGANIZATION' => $lang['Calendar_organization'],
				'L_DATE' => $lang['Calendar_date'],
				'L_TIME' => $lang['Calendar_time'],
				'L_LOCATION' => $lang['Calendar_location'],
				'L_CURRENT_REQUESTS' => $lang['Calendar_current_requests'],
				'EVENT_ITEM_LIST' => $event_item_list
				)
			);
			$content = $t->parse($dbconfig['show_template_name']);
			
			$content .= '<br />';
			
			$event_item_list = NULL;
						
			// Then show assigned events
			$events = array();
			$events = get_assigned_events($regionid, $numdays);
			
			// Parse the Assigned Events Calendar
			$ec = count($events);
			for ( $i=0; $i<$ec; $i++ ) { 
				$event_public = ( $events[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('calendar_admin_event') );
				$t->set_vars( array(
					'L_VIEW' => $lang['Calendar_view'],
					'L_EDIT' => $lang['Calendar_edit'],
					'L_DELETE' => $lang['Calendar_delete'],
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $events[$i]['event_id'],
					'EVENT_TITLE' => $events[$i]['program_name'],
					'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
					'EVENT_ORGANIZATION' => stripslashes($events[$i]['event_contact_organization']),
					'REGION_ID' => $regionid
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}
			
			$t->set_template( load_template('calendar_admin') );
			$t->set_vars( array(
				'L_ID' => $lang['Calendar_id'],
				'L_EVENT' => $lang['Calendar_event'],
				'L_DATE' => $lang['Calendar_date'],
				'L_ORGANIZATION' => $lang['Organization'],
				'L_VOLUNTEERS' => $lang['Calendar_volunteers'],
				'L_EDIT' => $lang['Calendar_edit'],
				'L_DELETE' => $lang['Calendar_delete'],
				'L_CALENDAR_NAME' => $lang['Calendar_assigned_events'],
				'EVENT_ITEM_LIST' => $event_item_list,
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
			
			$content .= '<br />';
			
			// Then show unassigned events
			$event_item_list = NULL;
			
			$events = array();
			$events = get_unassigned_events($regionid, $numdays);
			
			// Finally, parse the Unassigned Events Calendar
			$ec = count($events);
			for ( $i=0; $i<$ec; $i++ ) { 
				$event_public = ( $events[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('calendar_admin_event') );
				$t->set_vars( array(
					'L_VIEW' => $lang['Calendar_view'],
					'L_EDIT' => $lang['Calendar_edit'],
					'L_DELETE' => $lang['Calendar_delete'],
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $events[$i]['event_id'],
					'EVENT_TITLE' => $events[$i]['program_name'],
					'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
					'EVENT_ORGANIZATION' => stripslashes($events[$i]['event_contact_organization']),
					'REGION_ID' => $regionid
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}
			
			$t->set_template( load_template('calendar_admin') );
			$t->set_vars( array(
				'L_ID' => $lang['Calendar_id'],
				'L_EVENT' => $lang['Calendar_event'],
				'L_DATE' => $lang['Calendar_date'],
				'L_TIME' => $lang['Calendar_time'],
				'L_VOLUNTEERS' => $lang['Calendar_volunteers'],
				'L_EDIT' => $lang['Calendar_edit'],
				'L_DELETE' => $lang['Calendar_delete'],
				'L_CALENDAR_NAME' => $lang['Calendar_unassigned_events'],
				'EVENT_ITEM_LIST' => $event_item_list,
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		} elseif ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $regionid ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			// Show them a regular calendar if they are a RD not viewing their region, OR they are a volunteer or Volunteer Staff
			$events = array();
			$events = get_volunteer_events($regionid, $numdays);
			
			$event_item_list = NULL;
			
			// The volunteer calendar requires both a public and private
			// event template because of the Signup/Register link
			// 5.11.04 - Of course, now I find out that both public and 
			// private events can be registered for.. *sigh*
			// Wow, that was a long time ago (12.20.2005)
			$ec = count($events);
			for ( $i=0; $i<$ec; $i++ ) {
				$event_public = ( $events[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('calendar_volunteer_event') );
				$t->set_vars( array(
					'L_REGISTER' => $lang['Calendar_register'],
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $events[$i]['event_id'],
					'EVENT_TITLE' => stripslashes($events[$i]['program_name']),
					'EVENT_ORGANIZATION' => $events[$i]['event_contact_organization'],
					'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
					'REGION_ID' => $regionid
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}
			
			$t->set_template( load_template('calendar_volunteer') );
			$t->set_vars( array(
				'L_ID' => $lang['Calendar_id'],
				'L_EVENT' => $lang['Calendar_event'],
				'L_DATE' => $lang['Calendar_date'],
				'L_ORGANIZATION' => $lang['Organization'],
				'L_REGISTER' => $lang['Calendar_register'],
				'CALENDAR_NAME' => $calendar_name,
				'EVENT_ITEM_LIST' => $event_item_list
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('event_pagination_form') );
		$t->set_vars( array(
			'L_VIEW_EVENTS_FROM' => $lang['View_events_from'],
			'L_SHOW_EVENTS' => $lang['Show_events'],
			'PAGINATION_URL' => PAGE_CALENDAR,
			'REGION_ID' => $regionid,
			'VIEW_EVENTS_LIST' => $view_events_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
				
		// And finally, make the key
		$content .= make_calendar_key($regionid);
		
		unset($events, $event_item_list, $region, $view_event_list);
	}
}

// Take care of all of the Calendar actions
if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	// Ensure $eventid isn't bad data since it comes from POST
	if ( !is_numeric($eventid) || $eventid <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
	}

	$e = array();
	$e = get_event_data($eventid);
	
	if ( $do == 'registerevent' ) {
		$pagination[] = array(NULL, $lang['Register_event']);
	
		// Make sure this event isn't already authorized
		if ( check_event_authorized($eventid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_register']);
		}
		
		// Make sure the user hasn't registered for this event already
		if ( user_registered_event($usercache['user_id'], $eventid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_register']);
		}
		
		// The user_id for this event comes from the session since the
		// person clicked Register For This Event
		$sql = "INSERT INTO `" . ASSIGNMENT . "`(assignment_id, assignment_event_id, assignment_user_id, assignment_authorized)
				VALUES(NULL, '" . $eventid . "', '" . $usercache['user_id'] . "', '0')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $dbconfig['send_email'] == 1 ) {
			$e = array();
			$e = get_event_data($eventid);
			$event_program_title = $e['program_name'];
			
			// Now find all Region Directors of the region they selected and email them
			$region_directors = array();
			$region_directors = get_region_directors($regionid);
			
			// Email all of the directors
			$rdc = count($regional_directors);
			for ( $i=0; $i<$rdc; $i++) {
				$email_text = sprintf($lang['Email_new_registration_message'], $event_program_title, $eventid, $eventid, $regionid);
				
				send_email($region_directors[$i]['user_email'], $lang['Email_new_registration_subject'], $email_text);
			}
		}
		
		$content = make_content($lang['Calendar_register_event_thank_you']);
	} elseif ( $do == 'authorizerequest' ) {
		// Ensure $eventid isn't bad data since it comes from POST
		//if ( !is_numeric($eventid) || $eventid <= 0 ) {
		//	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		//}
		
		//$e = array();
		//$e = get_event_data($eventid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array(NULL, $lang['Authorize_event']);
		
		$event_authorized = 1;
		
		$incoming = collect_vars($_POST, array('event_user_notes' => MIXED, 'event_driving_directions' => MIXED, 'event_user_id' => INT, 'event_agency_specific' => INT));
		extract($incoming);
		
		$sql = "UPDATE `" . EVENT . "` SET 
				event_authorized = '" . $event_authorized . "', 
				event_agency_specific = '" . $event_agency_specific . "',
				event_driving_directions = '" . $event_driving_directions . "',
				event_user_notes = '" . $event_user_notes . "'
			WHERE event_id = '" . $eventid . "' 
				AND event_region_id = '" . $regionid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Get all of the information on the new event
		//$e = array();
		//$e = get_event_data($eventid);
		
		// Email the requester, telling them their event has been authorized
		$name = ( empty($e['event_contact_name']) ? $e['event_your_name'] : $e['event_contact_name'] );
		if ( empty($e['event_contact_name']) ) {
			$name = $e['event_your_name'];
		} else {
			$name = $e['event_contact_name'];
		}
		
		if ( $dbconfig['send_email'] == 1 ) {
			send_email($e['event_contact_email'], $lang['Email_event_authorized_subject'], sprintf($lang['Email_event_authorized_message'], $name, $e['program_name']) );
		}
		
		if ( !empty($event_user_id) ) {
			// They want to assign a user
			$sql = "INSERT INTO `" . ASSIGNMENT . "` 
					VALUES(NULL, '" . $eventid . "', '" . $event_user_id . "', '1')";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			// Email the user telling them they have been registered and authorized for an event
			$u = array();
			$u = get_user_data($event_user_id);
			
			$email_text = sprintf($lang['Email_user_register_message'], $u['user_first_name'] . ' ' . $u['user_last_name'], $eventid);
			
			if ( $dbconfig['send_email'] == 1 ) {
				send_email($u['user_email'], $lang['Email_user_register_subject'], $email_text);
			}
		}
		
		// All done
		$content = make_content($lang['Calendar_authorize_event']);
	} elseif ( $do == 'declinerequest' ) {
		// Ensure $eventid isn't bad data since it comes from POST
		//if ( !is_numeric($eventid) || $eventid <= 0 ) {
		//	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		//}
		
		//$e = array();
		//$e = get_event_data($eventid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
	
		// Declining a request deletes it from the database
		$pagination[] = array(NULL, $lang['Decline_event']);
		
		$sql = "DELETE FROM `" . EVENT . "` 
				WHERE event_id = '" . $eventid . "' 
					AND event_region_id = '" . $regionid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		if ( $dbconfig['send_email'] == 1 ) {
			send_email($e['event_contact_email'], $lang['Email_event_declined_subject'], sprintf($lang['Email_event_declined_message'], $e['event_contact_name']) );
		}
		
		// All done
		$content = make_content($lang['Calendar_declined_event']);
	} elseif ( $do == 'askquestion' ) {
		// Ensure $eventid isn't bad data since it comes from POST
		//if ( !is_numeric($eventid) || $eventid <= 0 ) {
		//	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		//}
		
		//$e = array();
		//$e = get_event_data($eventid);
			
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		// Request is still not authorized, but a new question has been added to it
		$pagination[] = array(NULL, $lang['Ask_question']);
				
		$incoming = collect_vars($_POST, array('event_question' => MIXED));
		extract($incoming);
		
		$sql = "UPDATE `" . EVENT . "` 
				SET event_question = '" . $event_question . "' 
				WHERE event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Get the event data and email the requester
		if ( $dbconfig['send_email'] == 1 ) {
			// User must be admin or RD to perform this action, thus, you can just use their email address
			$email_text = sprintf($lang['Email_event_question_message'], $e['event_contact_name'], stripslashes($event_question) );
	
			send_email($e['event_contact_email'], $lang['Email_event_question_subject'], $email_text, "Reply-To: " . $usercache['user_email']);
		}
		
		// All done
		$content = make_content($lang['Calendar_question_asked']);
	} elseif ( $do == 'editevent' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		// Simply updates the information about an event
		// Keep in mind, if the RD moves the event out of their
		// region, they no longer have manage properties of it
		$content_subtitle = make_title($lang['Edit_event'], true);
		$pagination[] = array(NULL, $lang['Edit_event']);
		
		$incoming = collect_vars($_POST, array('event_contact_organization' => MIXED, 'event_your_name' => MIXED, 'event_contact_name' => MIXED, 'event_contact_email' => MIXED, 'event_contact_address' => MIXED, 'event_contact_city' => MIXED, 'event_contact_state' => MIXED, 'event_contact_zip_code' => MIXED, 'event_contact_phone_number' => MIXED, 'event_contact_fax_number' => MIXED, 'event_driving_directions' => MIXED, 'event_program_id' => INT, 'event_language' => MIXED, 'event_istradeshow' => INT, 'event_location' => MIXED, 'event_location_address' => MIXED, 'event_location_city' => MIXED, 'event_location_state' => MIXED, 'event_location_zip_code' => MIXED, 'event_location_phone_number' => MIXED, 'event_month' => INT, 'event_day' => INT, 'event_year' => INT, 'event_start_time' => MIXED, 'event_start_time_middle' => MIXED, 'event_end_time' => MIXED, 'event_end_time_middle' => MIXED, 'event_time_zone' => MIXED, 'event_notes' => MIXED, 'event_projection_equipment' => MIXED, 'event_anticipated_audience' => MIXED, 'event_region_id' => INT, 'event_id' => INT, 'region_id' => INT, 'event_public' => INT));
		extract($incoming);

		$event_start_date = mktime($event_start_time, $event_start_time_middle, 0, $event_month, $event_day, $event_year);
		$event_end_date = mktime($event_end_time, $event_end_time_middle, 0, $event_month, $event_day, $event_year);
		
		// Do all of the checking of values
		if ( !validate_email($event_contact_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		if ( !validate_phone_number($event_contact_phone_number) || !validate_phone_number($event_contact_fax_number) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
		}
		
		if ( $event_program_id != PROGRAM_TRADESHOW ) {
			$event_istradeshow = 0;
		} else {
			$event_istradeshow = 1;
		}
		
		// Get the Calendar ID depending on the Region ID
		$region = get_region_data($event_region_id);
		$calendar_id = $region['region_calendar_id'];
		
		// MASSIVE....
		$sql = "UPDATE `" . EVENT . "` SET
					event_calendar_id = '" . $calendar_id . "',
					event_region_id = '" . $event_region_id . "', event_public = '" . $event_public . "',
					event_program_id = '" . $event_program_id . "', 
					event_language = '" . $event_language . "',
					event_istradeshow = '" . $event_istradeshow . "',
					event_contact_organization = '" . $event_contact_organization . "', 
					event_your_name = '" . $event_your_name . "',
					event_contact_name = '" . $event_contact_name . "', event_contact_email = '" . $event_contact_email . "',
					event_contact_address = '" . $event_contact_address . "', 
					event_contact_city = '" . $event_contact_city . "', event_contact_state = '" . strtoupper($event_contact_state) . "',
					event_contact_zip_code = '" . $event_contact_zip_code . "', 
					event_contact_phone_number = '" . $event_contact_phone_number . "', 
					event_contact_fax_number = '" . $event_contact_fax_number . "',
					event_location = '" . $event_location . "',
					event_location_address = '" . $event_location_address . "',
					event_location_city = '" . $event_location_city . "',
					event_location_state = '" . strtoupper($event_location_state) . "',
					event_location_zip_code = '" . $event_location_zip_code . "',
					event_location_phone_number = '" . $event_location_phone_number . "',
					event_start_date = '" . $event_start_date . "', event_end_date = '" . $event_end_date . "',
					event_time_zone = '" . $event_time_zone . "',
					event_notes = '" . $event_notes . "',
					event_projection_equipment = '" . $event_projection_equipment . "',
					event_anticipated_audience = '" . $event_anticipated_audience . "',
					event_driving_directions = '" . $event_driving_directions . "'
				WHERE event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Email all of the people assigned to this event
		// If there is an authorized user, just email him,
		// Else, all people who have registered for this event
		if ( $dbconfig['send_email'] == 1 ) {
			$assignments = array();
			$assignments = get_registered_users($eventid);
			
			$single_email = false;
			$single_address = NULL;
			$u = array();
			
			// See if we are dispatching one email or multiple
			if ( !check_event_authorized($eventid) ) {
				$ac = count($assignments);
				for ( $i=0; $i<$ac; $i++ ) {
					$u = get_user_data($assignments[$i]['assignment_user_id']);
					if ( $assignments[$i]['assignment_authorized'] == 1 ) {
						$single_email = true;
						$single_address = $u['user_email'];
						$email_text = sprintf($lang['Email_event_updated_message'], $u['user_first_name'] . ' ' . $u['user_last_name'], $eventid);
					}
				}
			}
	
			// See if we need to dispatch one email, or multiple ones
			if ( $single_email == true ) {
				send_email($single_address, $lang['Email_event_updated_subject'], $lang['Email_event_updated_message']);
			} else {
				$ac = count($assignments);
				for ( $i=0; $i<$ac; $i++ ) {
					$u = get_user_data($assignments[$i]['assignment_user_id']);
					$email_text = sprintf($lang['Email_event_updated_message'], $u['user_first_name'] . ' ' . $u['user_last_name'], $eventid);
	
					send_email($u['user_email'], $lang['Email_event_updated_subject'], $email_text);
				}
			}
		}
		
		// Tell the user everything went correct
		$content = make_content($lang['Calendar_event_updated']);
		
		unset($sql, $assignments);
	} elseif ( $do == 'duplicateevent' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array(NULL, $lang['Duplicate_event']);
		$content_subtitle = make_title($lang['Duplicate_event'], true);
				
		// Make the query happy since this
		// data is coming from the database, its
		// not trustable (as compared to from POST or GET
		// where it's sanitized on each page load)
		while ( list($k, $v) = each($e) ) {
			$e[$k] = addslashes( $v );
		}
		@reset($e);
		
		// When duplicating the event, ensure that
		// the event is incomplete by default.
		$event_complete = 0;
		
		$sql = "INSERT INTO `" . EVENT . "`(event_id, event_calendar_id, event_region_id, 
					event_authorized, event_public, event_agency_specific, event_closed, event_program_id, 
					event_language,
					event_istradeshow, event_your_name, event_contact_organization, event_contact_name, 
					event_contact_email, event_contact_address, event_contact_city, event_contact_state, 
					event_contact_zip_code, event_contact_phone_number, event_contact_fax_number, event_location, 
					event_location_address, event_location_city, event_location_state, event_location_zip_code, 
					event_location_phone_number, event_contacts_phone_number, event_start_date, event_end_date, 
					event_time_zone, event_notes, event_projection_equipment, event_anticipated_audience, 
					event_driving_directions, event_user_notes, event_question, event_complete)
			VALUES (NULL, '" . $e['event_calendar_id'] . "', '" . $e['event_region_id'] . "', '" . $e['event_authorized'] . "',
					'" . $e['event_public'] . "', '" . $e['event_agency_specific'] . "', '" . $e['event_closed'] . "', '" . $e['event_program_id'] . "',
					'" . $e['event_language'] . "',
					'" . $e['event_istradeshow'] . "', '" . $e['event_your_name'] . "', '" . $e['event_contact_organization'] . "', '" . $e['event_contact_name'] . "', 
					'" . $e['event_contact_email'] . "', '" . $e['event_contact_address'] . "', '" . $e['event_contact_city'] . "', 
					'" . $e['event_contact_state'] . "', '" . $e['event_contact_zip_code'] . "', '" . $e['event_contact_phone_number'] . "', 
					'" . $e['event_contact_fax_number'] . "', '" . $e['event_location'] . "', '" . $e['event_location_address'] . "', 
					'" . $e['event_location_city'] . "', '" . $e['event_location_state'] . "', '" . $e['event_location_zip_code'] . "', 
					'" . $e['event_location_phone_number'] . "', '" . $e['event_contacts_phone_number'] . "', '" . $e['event_start_date'] . "', 
					'" . $e['event_end_date'] . "', '" . $e['event_time_zone'] . "', '" . $e['event_notes'] . "', '" . $e['event_projection_equipment'] . "', 
					'" . $e['event_anticipated_audience'] . "', '" . $e['event_driving_directions'] . "', 
					'" . $e['event_user_notes'] . "', '" . $e['event_question'] . "', '" . $event_complete . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		$content = make_content($lang['Calendar_duplicate_event_thank_you']);
		
		unset($sql);
	} elseif ( $do == 'authorizeuser' ) {
		//if ( !is_numeric($eventid) || $eventid <= 0 ) {
		//	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		//}
		
		//$e = array();
		//$e = get_event_data($eventid):
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		// This section assigns a specific Volunteer to an event
		// and must come from an RD or Admin.
		
		$incoming = collect_vars($_POST, array('user_assign_id' => INT, 'update_user_id' => INT));
		extract($incoming);
		
		$pagination[] = array(NULL, $lang['Authorize_user']);
		
		if ( is_numeric($user_assign_id) && $user_assign_id > 0 ) {
			if ( !is_numeric($user_assign_id) || $user_assign_id <= 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$sql = "INSERT INTO `" . ASSIGNMENT . "` 
					VALUES(NULL, '" . $eventid . "', '" . $user_assign_id . "',
							'1')";
		} elseif ( is_numeric($update_user_id) && $update_user_id > 0 ) {
			if ( !is_numeric($update_user_id) || $update_user_id <= 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}

			$sql = "UPDATE `" . ASSIGNMENT . "` SET
						assignment_authorized = '1'
					WHERE assignment_event_id = '" . $eventid . "'
						AND assignment_user_id = '" . $update_user_id . "'";
		}
		
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		
		$sql = "DELETE FROM `" . ASSIGNMENT . "` WHERE assignment_authorized = '0' AND assignment_event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$user_id_to_use = ( !empty($update_user_id) ? $update_user_id : $user_assign_id );
		
		if ( $dbconfig['send_email'] == 1 ) {
			$user = array();
			$user = get_user_data($user_id_to_use);
		
			$email_text = sprintf($lang['Email_user_assigned_message'], $user['user_first_name'] . ' ' . $user['user_last_name'], $eventid);
		
			send_email($user['user_email'], $lang['Email_user_assigned_subject'], $email_text);
		}
				
		$content = make_content($lang['Calendar_user_assigned_thank_you']);
		
		unset($email_text, $user);
	} elseif ( $do == 'unregisterevent' ) {
		// Ensure $eventid isn't bad data
		//if ( !is_numeric($eventid) || $eventid <= 0 ) {
		//	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		//}
		
		//$e = array();
		//$e = get_event_data($eventid);
		
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		// Since this comes from POST, it means its coming from an RD
		// or Admin, and thus, the user must have those permissions
		// to unregister a Volunteer from an event.
		
		$pagination[] = array(NULL, $lang['Unregister_event']);
	
		$incoming = collect_vars($_POST, array('update_user_id' => INT));
		extract($incoming);
		
		$event_user_id = ( empty($update_user_id) ? $usercache['user_id'] : $update_user_id);
				
		// Ensure $event_user_id isn't bad data
		if ( !is_numeric($event_user_id) || $event_user_id <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$sql = "DELETE FROM `" . ASSIGNMENT . "` 
				WHERE assignment_event_id = '" . $eventid . "' 
					AND assignment_user_id = '" . $event_user_id . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Calendar_unregister_event_thank_you']);
	}
	
	unset($e);
}

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