<?php

/**
 * event.php
 * The public calendar that everyone can see plus functions
 * to manage people signing up for programs and such.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_public_calendar'], false);
$content_subtitle = NULL;

$incoming = collect_vars($_REQUEST, array('regionid' => INT, 'eventid' => INT, 'do' => MIXED, 'viewevents' => MIXED));
extract($incoming);

if ( !is_numeric($regionid) || $regionid <= 0 ) {
	$regionid = $usercache['user_region_id'];
	
	if ( !is_numeric($regionid) || $regionid <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
	}
}

// Now we collect the information from the database for filling out this page
$pagination[] = array('event.php?regionid=' . $regionid, $lang['Public_calendar']);
$content_pagination = make_pagination($pagination);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'viewevent' ) {
		$pagination[] = array(NULL, $lang['View_event']);
		
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$e = array();
		$e = get_event_data($eventid);
		
		$t->set_template( load_template('calendar_public_view_event') );
		$t->set_vars( array(
			'L_EVENT_ID' => $lang['Event_event_id'],
			'L_DATE' => $lang['Event_date'],
			'L_TIME' => $lang['Event_time'],
			'L_LOCATION' => $lang['Event_location'],
			'L_DRIVING_DIRECTIONS' => $lang['Event_driving_directions'],
			'L_MAPQUEST_DRIVING_DIRECTIONS' => $lang['Event_mapquest_driving_directions'],
			'L_ORGANIZATION' => $lang['Event_organization'],
			'L_CONTACT' => $lang['Event_contact_name'],
			'L_ADDRESS' => $lang['Event_address'],
			'L_CITY' => $lang['Event_city'],
			'L_STATE' => $lang['Event_state'],
			'L_ZIP_CODE' => $lang['Event_zip_code'],
			'L_PHONE_NUMBER' => $lang['Event_phone_number'],
			'L_NOTES' => $lang['Event_notes'],
			'L_EVENT_REGION' => $lang['Event_region'],
			'L_EVENT_LANGUAGE' => $lang['Request_program_language'],
			'L_REGISTER' => $lang['Click_to_register'],
			'EVENT_PROGRAM_TITLE' => stripslashes($e['program_name']),
			'EVENT_ID' => $e['event_id'],
			'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
			'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
			'EVENT_TIME_ZONE' => $e['event_time_zone'],
			'EVENT_LOCATION' => $e['event_location'],
			'EVENT_DRIVING_DIRECTIONS' => $e['event_driving_directions'],
			'EVENT_CONTACT_ORGANIZATION' => $e['event_contact_organization'],
			'EVENT_CONTACT' => $e['event_contact_name'],
			'EVENT_CONTACT_ADDRESS' => $e['event_location_address'],
			'EVENT_CONTACT_CITY' => $e['event_location_city'],
			'EVENT_CONTACT_STATE' => $e['event_location_state'],
			'EVENT_CONTACT_ZIP_CODE' => $e['event_location_zip_code'],
			'EVENT_CONTACT_PHONE_NUMBER' => $e['event_contacts_phone_number'],
			'EVENT_NOTES' => $e['event_notes'],
			'EVENT_REGION' => get_region_name($e['event_region_id']),
			'EVENT_LANGUAGE' => $e['event_language'],
			'REGION_ID' => $regionid
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		// Now see if an RD or Admin is logged in and 
		// show them a list of people attached to this
		// event or not
		$volunteer_can_view = false;
		$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
				WHERE a.assignment_event_id = '" . $eventid . "' 
					AND a.assignment_user_id = '" . $usercache['user_id'] . "' 
					AND a.assignment_authorized = '1'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result) == 1 ) {
			$volunteer_can_view = true;
		}

		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $e['event_region_id'] ) 
			|| $usercache['user_type'] == ADMINISTRATOR 
			|| $volunteer_can_view == true ) {
			
			$content .= make_content($lang['Registration_explanation']);
			
			// Get a list of people registered for this event
			// No need to do a join on anything since we already
			// have $e in memory which has all of the event
			// information
			$sql = "SELECT * FROM `" . REGISTER_QUEUE . "` rq 
					WHERE rq.queue_event_id = '" . $eventid . "'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			$register_queue_list = NULL;
			$queue_action_button = NULL;
			$total_registrations = 0;
			
			while ( $rq = $db->getarray($result) ) {
				if ( intval($rq['queue_authorized']) == 0 ) {
					$t->set_template( load_template('form_checkbox') );
					$t->set_vars( array(
						'CHECKBOX_NAME' => 'queueid[]',
						'CHECKBOX_VALUE' => $rq['queue_id']
						)
					);
					$queue_action_button = $t->parse($dbconfig['show_template_name']);
				} else {
					$queue_action_button = make_link('event.php?do=unregisterevent&amp;eventid=' . $eventid . '&amp;regionid=' . $regionid . '&amp;queueid=' . $rq['queue_id'], $lang['Control_panel_unregister']);
				}
				
				$t->set_template( load_template('event_register_queue_item') );
				$t->set_vars( array(
					'L_DELETE' => $lang['Delete'],
					'QUEUE_ID' => $rq['queue_id'],
					'QUEUE_NAME' => $rq['queue_first_name'] . ' ' . $rq['queue_last_name'],
					'QUEUE_ACTION_BUTTON' => $queue_action_button
					)
				);
				$register_queue_list .= $t->parse($dbconfig['show_template_name']);
				
				$queue_action_button = NULL;
				
				$t->set_template( load_template('event_register_queue_person') );
				$t->set_vars( array(
					'L_PERSON' => $lang['Registration_person'],
					'L_EDIT' => $lang['Edit'],
					'L_ID' => $lang['Id'],
					'L_NAME' => $lang['Name'],
					'L_ADDRESS' => $lang['Address'],
					'L_CITY' => $lang['City'],
					'L_STATE' => $lang['State'],
					'L_ZIP_CODE' => $lang['Zip_code'],
					'L_WORK_PHONE' => $lang['Control_panel_work_phone'],
					'L_HOME_PHONE' => $lang['Control_panel_home_phone'],
					'L_CELL_PHONE' => $lang['Control_panel_cell_phone'],
					'L_FAX' => $lang['Control_panel_fax_number'],
					'L_EMAIL' => $lang['Control_panel_email_address'],
					'QUEUE_ID' => $rq['queue_id'],
					'QUEUE_TITLE' => $rq['queue_title'],
					'QUEUE_FIRST_NAME' => $rq['queue_first_name'],
					'QUEUE_LAST_NAME' => $rq['queue_last_name'],
					'QUEUE_ADDRESS_ONE' => $rq['queue_address_one'],
					'QUEUE_ADDRESS_TWO' => $rq['queue_address_two'],
					'QUEUE_CITY' => $rq['queue_city'],
					'QUEUE_STATE' => $rq['queue_state'],
					'QUEUE_ZIP_CODE'  => $rq['queue_zip_code'],
					'QUEUE_PHONE_WORK' => $rq['queue_phone_work'],
					'QUEUE_PHONE_HOME' => $rq['queue_phone_home'],
					'QUEUE_PHONE_CELL' => $rq['queue_phone_cell'],
					'QUEUE_PHONE_FAX' => $rq['queue_phone_fax'],
					'QUEUE_EMAIL' => substr($rq['queue_email'], 0, 20) . '...',
					'QUEUE_EMAIL_LONG' => $rq['queue_email']
					)
				);
				$content .= $t->parse($dbconfig['show_template_name']);
				$total_registrations++;
			}
			
			$alter_event_status = ( $e['event_closed'] == 1 ? $lang['Event_open_event'] : $lang['Event_close_event'] );
			
			$t->set_template( load_template('event_register_queue') );
			$t->set_vars( array(
				'L_REGISTER_QUEUE' => $lang['Title_registration_queue'],
				'L_ID' => $lang['Id'],
				'L_NAME' => $lang['Name'],
				'L_REGISTER' => $lang['Registration_register_person'],
				'L_REGISTER_PEOPLE' => $lang['Registration_register_people'],
				'L_DELETE' => $lang['Delete'],
				'L_ALTER_EVENT_STATUS' => $alter_event_status,
				'L_VIEW_ROSTER' => $lang['Event_view_roster'],
				'L_TOTAL' => $lang['Report_total'],
				'EVENT_ID' => $eventid,
				'REGION_ID' => $regionid,
				'REGISTER_QUEUE_LIST' => $register_queue_list,
				'TOTAL_REGISTRATIONS' => $total_registrations
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
			
			$db->freeresult($result);
		}
		
		unset($e);
	} elseif ( $do == 'unregisterevent' || $do == 'deletequeue' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array('event.php?do=viewevent&amp;eventid=' . $eventid . '&amp;regionid=' . $regionid, $lang['View_event']);
		$pagination[] = array(NULL, $lang['Unregister_event']);
		
		$incoming = collect_vars($_GET, array('queueid' => INT) );
		extract($incoming);
		
		$sql = "DELETE FROM `" . REGISTER_QUEUE . "` WHERE queue_id = '" . $queueid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Event_unregister_complete']);
	} elseif ( $do == 'altereventstatus' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array('event.php?do=viewevent&amp;eventid=' . $eventid . '&amp;regionid=' . $regionid, $lang['View_event']);
		$pagination[] = array(NULL, $lang['Unregister_event']);
		
		$e = array();
		$e = get_event_data($eventid);
		
		$new_event_status = ( $e['event_closed'] == 1 ? 0 : 1 );
		
		$sql = "UPDATE `" . EVENT . "` SET 
					event_closed = '" . $new_event_status . "' 
				WHERE event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Event_status_altered']);
	} elseif ( $do == 'editperson' ) {
		can_view(VOLUNTEER_STAFF);
	
		$pagination[] = array('event.php?do=viewevent&amp;eventid=' . $eventid . '&amp;regionid=' . $regionid, $lang['View_event']);
		$pagination[] = array(NULL, $lang['Edit_person']);
		
		$incoming = collect_vars($_GET, array('queueid' => INT ) );
		extract($incoming);
		
		$sql = "SELECT * FROM `" . REGISTER_QUEUE . "` rq 
				WHERE rq.queue_id = '" . $queueid . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$person = $db->getarray($result) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_data']);
		
		$user_title_list = make_drop_down('queue_title', $lang['Control_panel_user_titles'], $lang['Control_panel_user_titles'], $person['queue_title']);
		$hear_about_list = make_drop_down('queue_referral', $lang['Queue_hear_about_list'], $lang['Queue_hear_about_list'], $person['queue_referral']);
		
		$t->set_template( load_template('register_event_form') );
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
			'L_BANKRUPTCY_FILING_NUMBER' => $lang['Control_panel_bankruptcy_filing_number'],
			'L_HEAR_ABOUT' => $lang['Queue_hear_about'],
			'L_HEAR_ABOUT_OTHER' => $lang['Queue_hear_about_other'],
			'L_REGISTER' => $lang['Edit_person'],
			'FORM_ACTION' => 'editperson',
			'REGION_ID' => $regionid,
			'EVENT_ID' => $eventid,
			'QUEUE_ID' => $queueid,
			'QUEUE_FIRST_NAME' => $person['queue_first_name'],
			'QUEUE_LAST_NAME' => $person['queue_last_name'],
			'QUEUE_MAILING_ADDRESS_ONE' => $person['queue_address_one'],
			'QUEUE_MAILING_ADDRESS_TWO' => $person['queue_address_two'],
			'QUEUE_CITY' => $person['queue_city'],
			'QUEUE_STATE' => $person['queue_state'],
			'QUEUE_ZIP_CODE' => $person['queue_zip_code'],
			'QUEUE_WORK_PHONE' => $person['queue_phone_work'],
			'QUEUE_HOME_PHONE' => $person['queue_phone_home'],
			'QUEUE_CELL_PHONE' => $person['queue_phone_cell'],
			'QUEUE_FAX_NUMBER' => $person['queue_phone_fax'],
			'QUEUE_EMAIL_ADDRESS' => $person['queue_email'],
			'QUEUE_BANKRUPTCY_NUMBER' => $person['queue_bankruptcy_number'],
			'QUEUE_REFERRAL_OTHER' => $person['queue_referral_other'],
			'USER_TITLE_LIST' => $user_title_list,
			'HEAR_ABOUT_LIST' => $hear_about_list,
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'search' ) {
		can_view(VOLUNTEER_STAFF);
		
		$incoming = collect_vars($_GET, array('query' => MIXED));
		extract($incoming);
		
		$sql = "SELECT * FROM `" . REGISTER_QUEUE . "` rq WHERE 1 ";
		$query = strtolower($query);
		
		if ( is_numeric($query) ) { // Search for an ID or Zip code
			$sql .= "AND rq.queue_id IN(" . $query . ") OR rq.queue_zip_code IN(" . $query . ")";
		} else {
			if ( strlen($query) < 3 ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_search_too_short']);
			}
			
			$sql .= "AND rq.queue_first_name LIKE '%" . $query . "%' OR rq.queue_last_name LIKE '%" . $query . "%' OR rq.queue_address_one LIKE '%" . $query . "%' OR rq.queue_address_two LIKE '%" . $query . "%' OR rq.queue_phone_work LIKE '%" . $query . "%' OR rq.queue_phone_home LIKE '%" . $query . "%' OR rq.queue_phone_cell LIKE '%" . $query . "%' OR rq.queue_phone_fax LIKE '%" . $query . "%' OR rq.queue_email LIKE '%" . $query . "%'";
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$event_search_list = NULL;
		while ( $search = $db->getarray($result) ) {
			$t->set_template( load_template('event_search_results_item') );
			$t->set_vars( array(
				'L_VIEW_EVENT' => $lang['View_event'],
				'QUEUE_ID' => $search['queue_id'],
				'QUEUE_FIRST_NAME' => $search['queue_first_name'],
				'QUEUE_LAST_NAME' => $search['queue_last_name'],
				'REGION_ID' => $regionid,
				'EVENT_ID' => $search['queue_event_id']
				)
			);
			$event_search_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('event_search_results') );
		$t->set_vars( array(
			'L_SEARCH_RESULTS' => $lang['Newsletter_search_results'],
			'L_ID' => $lang['Id'],
			'L_NAME' => $lang['Name'],
			'L_VIEW_EVENT' => $lang['View_event'],
			'EVENT_SEARCH_LIST' => $event_search_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	} else {
		$numdays = collect_viewevents($viewevents);
		$view_events_list = make_drop_down('viewevents', $lang['View_events_future_name'], $lang['View_events_future'], $viewevents);
		
		$content = make_public_calendar($regionid, $numdays);
		
		$t->set_template( load_template('event_pagination_form') );
		$t->set_vars( array(
			'L_VIEW_EVENTS_FROM' => $lang['View_events_from'],
			'L_SHOW_EVENTS' => $lang['Show_events'],
			'PAGINATION_URL' => PAGE_EVENT,
			'REGION_ID' => $regionid,
			'VIEW_EVENTS_LIST' => $view_events_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	$pagination[] = array('event.php?do=viewevent&amp;eventid=' . $eventid . '&amp;regionid=' . $regionid, $lang['View_event']);
	
	if ( $do == 'register' ) {
		$content_title = make_title($lang['Register'], false);

		$pagination[] = array(NULL, $lang['Register']);
		
		$e = array();
		$e = get_event_data($eventid);
		if ( $e['event_public'] == 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_event_private_cant_register']);
		}
		
		// RD's from their region and Admins can add a person to this
		// program even if its closed.
		if ( $e['event_closed'] == 1 && 
			( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $e['event_region_id'] ) 
			&& $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_event_closed']);
		}
		
		// RD's from their region and Admins can add people to this event after its completed
		if ( $e['event_end_date'] < CCCSTIME ) {
			if ( $usercache['user_type'] > REGIONAL_DIRECTOR ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_past_date']);
			}
		}
		
		$user_title_list = make_drop_down('queue_title', $lang['Control_panel_user_titles'], $lang['Control_panel_user_titles']);
		$hear_about_list = make_drop_down('queue_referral', $lang['Queue_hear_about_list'], $lang['Queue_hear_about_list']);
		
		$bypass_fullfillment = NULL;
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $e['event_region_id'] ) 
			|| $usercache['user_type'] == ADMINISTRATOR ) {
			
			$t->set_template( load_template('register_event_bypass_fullfillment') );
			$t->set_vars( array(
				'L_BYPASS_FULLFILLMENT' => $lang['Event_bypass_fullfillment']
				)
			);
			$bypass_fullfillment = $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('register_event_form') );
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
			'L_BANKRUPTCY_FILING_NUMBER' => $lang['Control_panel_bankruptcy_filing_number'],
			'L_HEAR_ABOUT' => $lang['Queue_hear_about'],
			'L_HEAR_ABOUT_OTHER' => $lang['Queue_hear_about_other'],
			'L_REGISTER' => $lang['Click_to_register'],
			'FORM_ACTION' => 'registerevent',
			'REGION_ID' => $regionid,
			'EVENT_ID' => $eventid,
			'USER_TITLE_LIST' => $user_title_list,
			'HEAR_ABOUT_LIST' => $hear_about_list,
			'BYPASS_FULLFILLMENT' => $bypass_fullfillment
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'registerevent' ) {
		$incoming = collect_vars($_POST, array('queue_title' => MIXED, 'queue_first_name' => MIXED, 'queue_last_name' => MIXED, 'queue_address_one' => MIXED, 'queue_address_two' => MIXED, 'queue_city' => MIXED, 'queue_state' => MIXED, 'queue_zip_code' => MIXED, 'queue_phone_work' => MIXED, 'queue_phone_home' => MIXED, 'queue_phone_cell' => MIXED, 'queue_phone_fax' => MIXED, 'queue_email' => MIXED, 'queue_bankruptcy_number' => MIXED, 'queue_referral' => MIXED, 'queue_referral_other' => MIXED, 'queue_bypass_fullfillment' => INT));
		extract($incoming);

		$pagination[] = array(NULL, $lang['Register']);
		
		// Just make sure this event isn't private first
		$e = array();
		$e = get_event_data($eventid);
		if ( $e['event_public'] == 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_event_private_cant_register']);
		}
		
		if ( $e['event_closed'] == 1 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_event_closed']);
		}
		
		// RD's and Admins can add people to this event after its completed
		if ( $e['event_end_date'] < CCCSTIME && $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_past_date']);
		}
		
		// First attempt to see if they've registered for this event before
		if ( !empty($queue_phone_home) ) {
			$sql = "SELECT * FROM `" . REGISTER_QUEUE . "` rq 
					WHERE rq.queue_phone_home IN('" . $queue_phone_home . "') AND rq.queue_event_id IN(" . $eventid . ")";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			if ( $db->numrows($result) > 0 ) {
				cccs_message(WARNING_MESSAGE, $lang['Error_already_registered']);
			}
			$db->freeresult($result);
		}
		
		$queue_fullfilled = 0;
		$queue_authorized = 0;
		
		if ( $queue_bypass_fullfillment == 1 ) {
			$queue_authorized = 1;
			$queue_fullfilled = 1;
		}
		
		// If they've made it this far (long journey, huh?), they
		// can be added to this class, but not registered
		$sql = "INSERT INTO `" . REGISTER_QUEUE . "`
				VALUES (NULL, '" . $eventid . "', '" . $queue_authorized . "', '" . $queue_title . "',
						'" . $queue_first_name . "', '" . $queue_last_name . "', '" . $queue_address_one . "',
						'" . $queue_address_two . "', '" . $queue_city . "', '" . $queue_state . "',
						'" . $queue_zip_code . "', '" . $queue_phone_work . "', '" . $queue_phone_home . "',
						'" . $queue_phone_cell . "', '" . $queue_phone_fax . "', '" . $queue_email . "', 
						'" . $queue_bankruptcy_number . "',	'" . $queue_referral . "', '" . $queue_referral_other . "', 
						'" . $queue_fullfilled . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $dbconfig['send_email'] == 1 ) {
			// Now find all Region Directors of the region they selected and email them
			$region_directors = array();
			$region_directors = get_region_directors($regionid);
			
			// Email all of the directors
			for ( $i=0; $i<count($region_directors); $i++) {
				$email_text = sprintf($lang['Email_new_register_event_message'], $eventid, $eventid, $regionid);
				
				send_email($region_directors[$i]['user_email'], $lang['Email_new_register_event_subject'], $email_text);
			}
		}
		
		$content = make_content($lang['Registration_complete']);
	} elseif ( $do == 'authorize' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Registration_authorize']);
		
		// Get the event information here rather than in the loop since
		// we only need to get it once.
		$e = array();
		$e = get_event_data($eventid);
		
		$event_title = $e['program_name'];
		$event_date = date($dbconfig['date_format'], $e['event_start_date']);
		$event_time = date($dbconfig['time_format'], $e['event_start_date']) . ' ' . $e['event_time_zone'];
		$event_phone_number = $e['event_contacts_phone_number'];
			
		// Loop thru the array of people who are authorized,
		// authorize them, and then email them.
		for ( $i=0; $i<count($_POST['queueid']); $i++ ) {
			$queueid = intval($_POST['queueid'][$i]);
			
			$sql = "UPDATE `" . REGISTER_QUEUE . "` 
					SET queue_authorized = '1' 
					WHERE queue_id = '" . $queueid . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			// Email!
			if ( $dbconfig['send_email'] == 1 ) {
				$region_directors = array();
				$region_directors = get_region_directors($e['event_region_id']);
		
				$region_director_name = $region_directors[0]['user_first_name'] . ' ' . $region_directors[0]['user_last_name'];
				$region_director_phone_number = $region_directors[0]['user_phone_number_work'];

				$sql = "SELECT rq.queue_first_name, rq.queue_last_name, rq.queue_email
						FROM `" . REGISTER_QUEUE . "` rq 
						WHERE rq.queue_id = '" . $queueid . "'";
				$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
				
				$rq = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
				
				$full_name = $rq['queue_first_name'] . ' ' . $rq['queue_last_name'];
				
				$email_message = sprintf($lang['Email_register_event_message'], $full_name, $event_title, $event_date, $event_time, $region_director_name, $region_director_phone_number);
				send_email(trim($rq['queue_email']), $lang['Email_register_event_subject'], $email_message);
			}
		}
		
		$content = make_content($lang['Registration_authorize_thank_you']);
	} elseif ( $do == 'editperson' ) {
		can_view(VOLUNTEER_STAFF);
		
		$incoming = collect_vars($_POST, array('queueid' => INT, 'queue_title' => MIXED, 'queue_first_name' => MIXED, 'queue_last_name' => MIXED, 'queue_address_one' => MIXED, 'queue_address_two' => MIXED, 'queue_city' => MIXED, 'queue_state' => MIXED, 'queue_zip_code' => MIXED, 'queue_phone_work' => MIXED, 'queue_phone_home' => MIXED, 'queue_phone_cell' => MIXED, 'queue_phone_fax' => MIXED, 'queue_email' => MIXED, 'queue_bankruptcy_number' => MIXED, 'queue_referral' => MIXED, 'queue_referral_other' => MIXED));
		extract($incoming);
		
		$pagination[] = array(NULL, $lang['Edit_person']);
		
		$sql = "UPDATE `" . REGISTER_QUEUE . "` SET queue_title = '" . $queue_title . "', queue_first_name = '" . $queue_first_name . "', queue_last_name = '" . $queue_last_name . "', queue_address_one = '" . $queue_address_one . "', queue_address_two = '" . $queue_address_two . "', queue_city = '" . $queue_city . "', queue_state = '" . $queue_state . "', queue_zip_code = '" . $queue_zip_code . "', queue_phone_work = '" . $queue_phone_work . "', queue_phone_home = '" . $queue_phone_home . "', queue_phone_cell = '" . $queue_phone_cell . "', queue_phone_fax = '" . $queue_phone_fax . "', queue_email = '" . $queue_email . "', queue_bankruptcy_number = '" . $queue_bankruptcy_number . "', queue_referral = '" . $queue_referral . "', queue_referral_other = '" . $queue_referral_other . "' WHERE queue_id = '" . $queueid . "'";

		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Person_updated']);
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