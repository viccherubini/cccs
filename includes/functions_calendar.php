<?php

/**
 * functions_calendar.php
 * Contains commonly used functions for the Calendar.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

/**
 * Returns a calendar's name by it's ID.
 *
 * @param	int		the ID of the calendar to get the name of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	string	the name of the calendar
*/
function get_calendar_name($calendar_id) {
	global $db, $lang;
	
	$sql = "SELECT c.calendar_name FROM `" . CALENDAR . "` c 
			WHERE c.calendar_id = '" . $calendar_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$calendar = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_calendar_name']);
	
	$db->freeresult($result);
	
	return $calendar['calendar_name'];
}

/**
 * Returns a calendar's name by the region it belongs to.
 *
 * @param	int		the ID of the region
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	string	the name of the calendar
*/
function get_calendar_name_by_region($region_id) {
	global $db, $lang;
	
	// Gotta do a bigger query here
	$sql = "SELECT c.calendar_name FROM `" . REGION . "` r
			LEFT JOIN `" . CALENDAR . "` c
				ON r.region_calendar_id = c.calendar_id
			WHERE r.region_id = '" . $region_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$calendar = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_calendar_name']);
	
	$db->freeresult($result);
	
	return $calendar['calendar_name'];
}

/**
 * Returns all of the data about a specific region.
 *
 * @param	int		the ID of the region to get the data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the region data array
*/
function get_region_data($regionid) {
	global $db, $lang;
	
	if ( $regionid <= 0 || !is_numeric($regionid) ) {
		return false;
	}
	
	$sql = "SELECT * FROM `" . REGION . "` r 
			WHERE r.region_id = '" . $regionid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$region = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_region']);
	
	$db->freeresult($result);
	
	return $region;
}

/**
 * Returns an array of length = # events, with
 * indecies needed to make the Volunteer Calendar.
 *
 * @param	int		the ID of the region to get the calendar of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the volunteer calendar array, in full (may be very large)
*/
function get_volunteer_events($regionid, $daterange) {
	global $db, $lang;
	
	$events = array();
	$event = array();
	
	$one_day = 60 * 60 * 24;
		
	if ( $daterange == 'allevents' ) {
		$sql_date = "AND e.event_end_date > '" . CCCSTIME . "'";
	} else {
		$end_date = CCCSTIME + ( $one_day * $daterange);
		$sql_date = "AND e.event_start_date >= '" . CCCSTIME . "' AND e.event_end_date <= '" . $end_date . "' ";
	}
	
	$sql = "SELECT e.event_id, e.event_public, e.event_program_id, 
					e.event_start_date, e.event_end_date, e.event_location,
					e.event_contact_organization, p.program_name
			FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p 
				ON e.event_program_id = p.program_id
			WHERE e.event_region_id = '" . $regionid . "'
				AND e.event_complete = '0'
				AND e.event_authorized = '1'
				AND e.event_agency_specific = '0'
				" . $sql_date . "
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Unfortunately, I could not figure out how to make this into one query, but would love
	// to find out how.
	while ( $event = $db->getarray($result) ) {
		$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
				WHERE a.assignment_event_id = '" . $event['event_id'] . "'
					AND a.assignment_authorized = '1'";
		$result2 = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result2) <= 0 ) {
			$events[] = $event;
		}
		
		$db->freeresult($result2);		
	}
	
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns an array of length = # events in the public calendar.
 * The calendar is public for everyone.
 *
 * @param	int		the ID of the region to get the calendar of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the public calendar array (can also be large)
*/
function get_public_events($regionid, $daterange) {
	global $db, $lang;
	
	$events = array();
	
	$one_day = 60 * 60 * 24;
		
	if ( $daterange == 'allevents' ) {
		$sql_date = "AND e.event_end_date > '" . CCCSTIME . "'";
	} else {
		$end_date = CCCSTIME + ( $one_day * $daterange);
		$sql_date = "AND e.event_start_date >= '" . CCCSTIME . "' AND e.event_end_date <= '" . $end_date . "' ";
	}
	
	$sql = "SELECT e.event_id, e.event_calendar_id, e.event_region_id, e.event_program_id, 
					e.event_public,	e.event_closed, e.event_contact_organization, e.event_contact_name, 
					e.event_contact_email, e.event_location_address, 
					e.event_location_city, e.event_location_state, 
					e.event_location_zip_code, e.event_location_phone_number, 
					e.event_location, e.event_start_date, 
					e.event_end_date, e.event_notes, p.program_name
			FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_public = '1'
				AND e.event_complete = '0'
				AND e.event_authorized = '1'
				AND e.event_agency_specific = '0'
				AND e.event_start_date >= '" . CCCSTIME . "'
				" . $sql_date . "
				AND e.event_region_id = '" . $regionid . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $event = $db->getarray($result) ) {
		$events[] = $event;
	}
	
	$db->freeresult($result);
	
	return $events;		
}

/**
 * Returns all "requests", which are unauthorized events, 
 * from a specific region.
 *
 * @param	int		the ID of the region to get the "request" data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the request array (can also be large)
*/
function get_request_data($regionid) {
	global $db, $lang;
	
	$requests = array();
	$request = array();
		
	$sql = "SELECT e.event_id, e.event_public, e.event_contact_organization, e.event_start_date, 
					e.event_end_date, e.event_location 
			FROM `" . EVENT . "` e
			WHERE e.event_region_id = '" . $regionid . "'
				AND e.event_authorized = '0'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $request = $db->getarray($result) ) {
		$requests[] = $request;
	}
	
	$db->freeresult($result);
	
	return $requests;
}

/**
 * Simple wrapper for get_volunteer_calendar().
 *
 * @param	int		the region ID to get the calendar for
 *
 * @return	array	see get_volunteer_calendar() return value
*/
function get_unassigned_events($regionid, $daterange) {
	return get_volunteer_events($regionid, $daterange);
}

/**
 * Returns events that have an authorized assignment in
 * a certain region.
 *
 * @param	int		the ID of the region to get the events of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the assigned events array (can be large)
*/
function get_assigned_events($regionid, $daterange) {
	global $db, $lang;
	
	$events = array();
	$event = array();
	
	$one_day = 60 * 60 * 24;
			
	if ( $daterange == 'allevents' ) {
		$sql_date = "AND e.event_end_date > '" . CCCSTIME . "'";
	} else {
		$end_date = CCCSTIME + ( $one_day * $daterange);
		$sql_date = "AND e.event_start_date >= '" . CCCSTIME . "' AND e.event_end_date <= '" . $end_date . "' ";
	}
	
	$sql = "SELECT e.event_id, e.event_public, e.event_program_id, 
					e.event_start_date, e.event_end_date, e.event_location,
					e.event_contact_organization, p.program_name
			FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_region_id = '" . $regionid . "'
				AND e.event_complete = '0'
				AND e.event_authorized = '1'
				AND e.event_agency_specific = '0'
				" . $sql_date . "
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
	// Again with the second query. 
	while ( $event = $db->getarray($result) ) {
		$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
				WHERE a.assignment_event_id = '" . $event['event_id'] . "'
					AND a.assignment_authorized = '1'";
		$result2 = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result2) == 1 ) {
			$events[] = $event;
		}
		$db->freeresult($result2);
	}

	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns ALL data about an event.
 *
 * @param	int		the ID of the event to get the data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the row array from the database 
*/
function get_event_data($eventid) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . EVENT . "` e 
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_id = '" . $eventid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$event = array();
	$event = $db->getarray($result);// or cccs_message(WARNING_MESSAGE, $lang['Error_failed_event']);

	$db->freeresult($result);

	return $event;
}


/**
 * Finds out if a user has registered, authorized or not, 
 * for the event.
 *
 * @param	int		the ID of the user
 * @param	int		the ID of the event
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	bool	true if the user is registered for the event, false otherwise
*/
function user_registered_event($this_user_id, $event_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
			WHERE a.assignment_event_id = '" . $event_id . "' 
				AND a.assignment_user_id = '" . $this_user_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( $db->numrows($result) == 1 ) {
		$db->freeresult($result);
		return true;
	}
	
	$db->freeresult($result);
	return false;
}

/**
 * Returns true if there is an authorized user for that event.
 *
 * @param	int		the ID of the event
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @param	bool	true if the event is authorized, false otherwise
*/
function check_event_authorized($event_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
			WHERE a.assignment_event_id = '" . $event_id . "' 
				AND a.assignment_authorized = '1'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( $db->numrows($result) <= 0 ) {
		return false;
	}
	
	$db->freeresult($result);
	
	return true;
}

/**
 * Returns the HTML for the calendar key.
 *
 * @global	array	the currently loaded language array
 * @global	object	the global template handle
 * @global	array	the global database configuration file
 *
 *
 * @return	string	the parsed calendar key
*/
function make_calendar_key($regionid) {
	global $lang, $t, $dbconfig, $usercache;
	
	if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
		$past_events_link = make_link('calendar.php?do=pastevents&amp;regionid=' . $regionid, $lang['See_past_events']);
	}
	
	$t->set_template( load_template('calendar_all_key') );
	$t->set_vars( array(
		'REGION_ID' => $regionid,
		'L_PUBLIC_EVENT' => $lang['Event_public'],
		'L_PRIVATE_EVENT' => $lang['Event_private'],
		'VIEW_PAST_EVENTS' => $past_events_link
		)
	);
	$calendar_key = $t->parse($dbconfig['show_template_name']);
	
	return $calendar_key;
}

/**
 * Returns all users that have registered for an event. 
 * This includes unauthorized/unassigned users as well.
 *
 * @param	int		the ID of the event to get the users of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the array of users assigned to an event (usually small)
*/
function get_registered_users($event_id) {
	global $db, $lang;
	
	$assignments = array();
	
	$sql = "SELECT a.*, u.user_id, u.user_first_name, u.user_last_name, u.user_email 
			FROM `" . ASSIGNMENT . "` a 
			LEFT JOIN `" . USER . "` u
				ON a.assignment_user_id = u.user_id
			WHERE a.assignment_event_id = '" . $event_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $assignment = $db->getarray($result) ) {
		$assignments[] = $assignment;
	}
	
	$db->freeresult($result);
	
	return $assignments;
}

/**
 * Returns all users that belong to a certain region.
 *
 * @param	int		the ID of the region to get the list of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the list of users registered to an event
*/
function get_user_list($region_id = NULL) {
	global $db, $lang;
	
	if ( !empty($region_id) ) {
		if ( !is_numeric($region_id) || $region_id <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
	}
	
	$users = array();
	
	$sql = "SELECT * FROM `" . USER . "` u 
			WHERE u.user_authorized = '1' 
				AND u.user_type <= '" . VOLUNTEER . "'";
	if ( !(empty($region_id)) ) {
		$sql .= " AND u.user_region_id = '" . $region_id . "'";
	}
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $user = $db->getarray($result) ) {
		$users[] = $user;
	}
	
	$db->freeresult($result);
	
	return $users;
}

/**
 * Deletes an event, all associated assignments, and the event
 * response if it exists.
 *
 * @param	int		the ID of the event to delete
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	true	returns true if the event is deleted
*/
function delete_event($event_id) {
	global $lang, $db;
	
	// Data check
	if ( !is_numeric($event_id) || $event_id <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
	}
	
	// First, delete all of the assignments, authorized or not, assigned with this event
	$sql = "DELETE FROM `" . ASSIGNMENT . "` 
			WHERE assignment_event_id = '" . $event_id . "'";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Delete the event hours
	$sql = "DELETE FROM `" . HOUR . "` 
			WHERE hour_event_id = '" . $event_id . "'";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Delete the event response
	$sql = "DELETE FROM `" . RESPONSE . "` 
			WHERE response_event_id = '" . $event_id . "'";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Delete from the registration queue
	$sql = "DELETE FROM `" . REGISTER_QUEUE . "` 
			WHERE queue_event_id = '" . $event_id . "'";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Now we can delete the event
	$sql = "DELETE FROM `" . EVENT . "` 
			WHERE event_id = '" . $event_id . "'";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
	return true;
}

/**
 * Returns all of the events in a region for an RD that have people
 * attached to them.
 *
 * @param	int		the region to get the events of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the array of events for that region
*/
function get_region_remainder_calendar($region_id) {
	global $db, $lang, $usercache;
	
	// Data check
	if ( !is_numeric($region_id) || $region_id <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
	}
	
	$one_day = 60 * 60 * 24;
	$time = CCCSTIME - $one_day;
	
	$sql = "SELECT * FROM `" . EVENT . "` e
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			LEFT JOIN `" . USER . "` u
				ON a.assignment_user_id = u.user_id
			WHERE a.assignment_authorized = '1'
				AND e.event_authorized = '1'
				AND e.event_end_date >= '" . $time . "'
				AND e.event_region_id = '" . $region_id . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	while ( $row = $db->getarray($result) ) {
		$events[] = $row;
	}
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns all of the events in a region that have already
 * expired.
 *
 * @param	int		the region to get the events of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the array of events for that region
*/
function get_past_calendar($region_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_authorized = '1'
				AND e.event_start_date <= '" . CCCSTIME . "'
				AND e.event_region_id = '" . $region_id . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	while ( $row = $db->getarray($result) ) {
		$events[] = $row;
	}
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns the user data for the user assigned to a specific event.
 *
 * @param	int		the event id to get the user data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the user information for that event
*/
function get_assigned_user($event_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
			LEFT JOIN `" . USER . "` u
				ON a.assignment_user_id = u.user_id
			WHERE a.assignment_event_id = '" . $event_id . "'
				AND a.assignment_authorized = '1'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$user = array();
	$user = $db->getarray($result);
	
	return $user;
}

/**
 * Checks to see if an event has been entirely 
 * completed (which means the response is done).
 *
 * @param	int		the event id to check
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	boolean	true if event is completed, false otherwise
*/
function check_event_completed($eventid) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . EVENT . "` e 
			WHERE e.event_complete = '" . $eventid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$event = array();
	$event = $db->getarray($result);
	
	if ( $event['event_complete'] == 1 ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Returns an array of events for a specified day.
 *
 * @param	int		the event id to get the user data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the array of events for that region between the two times
*/
function get_events_per_day($region_id, $start_day, $end_day) {
	global $db, $lang;
	
	$sql = "SELECT e.event_id, e.event_program_id, e.event_public, p.program_name
			FROM `" . EVENT . "` e 
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_region_id = '" . $region_id . "' 
				AND e.event_start_date >= '" . $start_day . "' 
				AND e.event_end_date <= '" . $end_day . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	while ( $e = $db->getarray($result) ) {
		array_push($events, $e);
	}
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns the number of finished evaluations for a region 
 * or all regions.
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 * @global	array	the users cache
 *
 * @return	int		the number of evaluations
*/
function get_number_finished_evals() {
	global $db, $lang, $usercache;
	
	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		// Get all the events that are completed.
		$sql = "SELECT * FROM `" . EVENT . "` e
				WHERE e.event_complete ='1'
					AND e.event_authorized = '1'";
	} else {
		// Get all the events that are completed.
		$sql = "SELECT * FROM `" . EVENT . "` e
				WHERE e.event_complete ='1'
					AND e.event_authorized = '1'
					AND e.event_region_id = '" . $usercache['user_region_id']. "'";
	}
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	return $db->numrows($result);
}

function make_pending_events_calendar($e, $use_region = false) {
	global $t, $dbconfig, $lang;
	
	if ( !is_array($e) ) {
		return false;
	}
	
	$event_item_list = NULL;
	
	for ( $i=0; $i<count($e); $i++ ) {
		$event_public = ( $e[$i]['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
		
		if ( $use_region == true ) {
			$t->set_template( load_template('control_panel_other_events_item') );	
		} elseif ( $use_region == false ) {
			$t->set_template( load_template('control_panel_registered_events_item') );
		}
		
		$t->set_vars( array(
			'L_UNREGISTER' => $lang['Calendar_unregister'],
			'EVENT_USER_FIRST_NAME' => $e[$i]['user_first_name'],
			'EVENT_USER_LAST_NAME' => $e[$i]['user_last_name'],
			'EVENT_USER_ID' => $e[$i]['user_id'],
			'EVENT_PUBLIC' => $event_public,
			'EVENT_ID' => intval($e[$i]['event_id']),
			'EVENT_REGION_ID' => intval($e[$i]['event_region_id']),
			'EVENT_TITLE' => stripslashes($e[$i]['program_name']),
			'EVENT_DATE' => date($dbconfig['date_format'], $e[$i]['event_start_date'] ),
			'EVENT_ORGANIZATION' => $e[$i]['event_contact_organization'],			
			'EVENT_LOCATION' => $e[$i]['event_location']
			)
		);
		$event_item_list .= $t->parse($dbconfig['show_template_name']);
	}

	// We also need to select the events this person is signed up for, but we'll get to that later
	$t->set_template( load_template('control_panel_registered_events') );
	$t->set_vars( array(
		'L_REGISTERED_EVENTS' => $lang['Control_panel_registered_events'],
		'L_ID' => $lang['Control_panel_id'],
		'L_EVENT' => $lang['Control_panel_event'],
		'L_DATE' => $lang['Control_panel_date'],
		'L_ORGANIZATION' => $lang['Organization'],
		'L_LOCATION' => $lang['Control_panel_location'],
		'L_UNREGISTER' => ( $use_region == true ? $lang['Volunteer'] : $lang['Control_panel_unregister']),
		'EVENT_ITEM_LIST' => $event_item_list
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);
	
	unset($event_item_list);
	
	return $content;
}

function make_public_calendar($regionid, $daterange) {
	global $t, $lang, $dbconfig;
	
	$events = array();
	$events = get_public_events($regionid, $daterange);
	
	$event_item_list = NULL;
	$event_closed = NULL;
	
	//$r = get_region_data($regionid);
	
	// Output the calendar to the screen, public events only.
	for ( $i=0; $i<count($events); $i++ ) {
		$event_closed = ( $events[$i]['event_closed'] == 1 ? $dbconfig['style_closed_event'] : $dbconfig['style_event_public'] );
		
		$t->set_template( load_template('calendar_public_event_public') );
		$t->set_vars( array(
			'EVENT_CLOSED' => $event_closed,
			'EVENT_ID' => $events[$i]['event_id'],
			'EVENT_TITLE' => stripslashes($events[$i]['program_name']),
			'EVENT_ORGANIZATION' => $events[$i]['event_contact_organization'],
			'EVENT_DATE' => date($dbconfig['date_format'], $events[$i]['event_start_date']),
			'EVENT_LOCATION' => $events[$i]['event_location'],
			'REGION_ID' => $regionid
			)
		);
		$event_item_list .= $t->parse($dbconfig['show_template_name']);
	}
	
	$t->set_template( load_template('calendar_public') );
	$t->set_vars( array(
		'L_ID' => $lang['Id'],
		'L_EVENT' => $lang['Event_program_title'],
		'L_DATE' => $lang['Event_date'],
		'L_ORGANIZATION' => $lang['Organization'],
		'L_LOCATION' => $lang['Event_location'],
		'CALENDAR_NAME' => get_calendar_name($regionid),
		'EVENT_ITEM_LIST' => $event_item_list,
		'REGION_NAME' => $regionid . '_calendar'
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
	
	unset($event_item_list, $events);
	
	return $content;
}


function get_response_data($response_id) {
	global $db, $lang;
	
	if ( !is_numeric($response_id) || $response_id <= 0 ) {
		return false;
	}
	
	$sql = "SELECT * FROM `" . RESPONSE . "` r WHERE r.response_id = '" . $response_id . "'";
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$response = array();
	$response = $db->getarray($result) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_data']);
	
	$db->freeresult($result);
	
	return $response;
}

?>