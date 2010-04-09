<?php

/**
 * functions_user.php
 * Contains commonly used functions for Users.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

/**
 * Decides if the logged in user can view the page or not depending
 * on the lowest level that can not view the page.
 *
 * @param	int		the lowest level that can not view the page
 * 
 * @global	array	the users information
 * @global	array	the currently loaded language
 *
 * @return	true	always returns true
*/
function can_view($lowest_level) {
	global $usercache, $lang;
	
	if ( ($usercache['user_type'] >= $lowest_level || $usercache['user_authorized'] == 0 ) 
		|| empty($_SESSION) || $_SESSION['user_logged_in'] == false ) {

		$_SESSION['user_logged_in'] = false;
		$_SESSION = array();
		unset($usercache);
		session_unset();
		session_destroy();
		
		cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
	}
	
	return true;
}

/**
 * Returns the number of hours the user has taught and recorded.
 *
 * @param	int		the user id of the hours to get
 * 
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	int		the number of hours for the user
*/
function get_user_hours($userid) {
	global  $db, $lang;
		
	$sql = "SELECT SUM(hour_count) AS hour_count 
			FROM `" . HOUR . "` h 
			WHERE h.hour_user_id = '" . $userid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$data = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
	
	$db->freeresult($result);
	
	return ( empty($data['hour_count']) ? 0 : $data['hour_count']);
}

/**
 * Returns the number of hours the user has taught and recorded from a start date to end date.
 *
 * @param	int		the user id of the hours to get
 * 
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	int		the number of hours for the user
*/
function get_user_hours_date($userid, $start_date, $end_date) {
	global $db, $lang;
	
	$sql = "SELECT SUM(hour_count) AS hour_count 
			FROM `" . HOUR . "` h 
			LEFT JOIN `" . EVENT . "` e 
				ON h.hour_event_id = e.event_id 
			WHERE h.hour_user_id = '" . $userid . "' 
				AND e.event_start_date >= '" . $start_date . "' 
				AND e.event_end_date <= '" . $end_date . "'";

	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$data = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
	
	$db->freeresult($result);
	
	return ( empty($data['hour_count']) ? 0 : $data['hour_count']);
}

/**
 * Gets all events from a user that they have been authorized to and have already completed.
 *
 * @param	int		the user id to get the events for
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	all of the events that match the query
*/
function get_past_events($user_region_id) {
	global $db, $lang;
	
	$sql = "SELECT *, u.user_first_name, u.user_last_name FROM `" . EVENT . "` e 
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			LEFT JOIN `" . HOUR . "` h
				ON h.hour_event_id = e.event_id
			LEFT JOIN `" . USER . "` u
				ON a.assignment_user_id = u.user_id
			WHERE e.event_region_id = '" . $user_region_id . "'
				AND e.event_end_date <= '" . CCCSTIME . "'
				AND a.assignment_authorized = '1'
				AND h.hour_id IS NULL
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	while ( $event = $db->getarray($result) ) {
		$events[] = $event;
	}
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns all events that have responses and hours for the user
 *
 * @param	int		the user ID to get the event history for
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the event history of the user, most likely, this won't be 
 *					too large of an array
*/
function get_event_history($user_id) {
	global $db, $lang;
	
	// Massive, in a Trevor-like voice
	$sql = "SELECT * FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p ON
				e.event_program_id = p.program_id
			LEFT JOIN `" . HOUR . "` h
				ON e.event_id = h.hour_event_id
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			WHERE e.event_authorized = '1'
				AND a.assignment_authorized = '1'
				AND a.assignment_user_id = '" . $user_id . "'
				AND h.hour_user_id = '" . $user_id . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	
	while ( $event = $db->getarray($result) ) {
		$events[] = $event;
	}
	
	return $events;
}


/**
 * Returns the events that the user has already filled out the hours for.
 *
 * @param	int		the user id to get the hours of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	all of the events with corresponding hours
*/
function get_event_hours($user_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . HOUR . "` h
			LEFT JOIN `" . EVENT . "` e
				ON h.hour_event_id = e.event_id
			WHERE h.hour_user_id = '" . $user_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$events = array();
	while ( $event = $db->getarray($result) ) {
		$events[] = $event;
	}
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Selects ALL information about a user.
 * If $this_user_id is null (default), then the session is used
 *
 * @param	int		the user id to get the information of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the user information array
*/
function get_user_data($this_user_id = NULL) {
	global $db, $lang, $usercache;
	
	$use_user_id = (empty($this_user_id) ? $usercache['user_id'] : $this_user_id);
	
	$sql = "SELECT * FROM `" . USER . "` u 
			WHERE u.user_id = '" . $use_user_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);	
	
	$user = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_user']);
		
	$db->freeresult($result);
	
	return $user;
}

/**
 * Checks to see if a username exists.
 *
 * @param	string	the username to check
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	bool	returns true if username exists, false otherwise
*/
function check_username_exists($user_name) {
	global $db, $lang;
	
	$user_name = str_replace("\\'", "''", $user_name);
	$user_name = stripslashes($user_name);
	
	$sql = "SELECT * FROM `" . USER . "` u 
			WHERE u.user_name = '" . $user_name . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( $db->numrows($result) <= 0 ) {
		$db->freeresult($result);
		return false;
	} else {
		$db->freeresult($result);
		return true;
	}
	
	return true;
}

/**
 * Checks to see if an email exists.
 *
 * @param	string	the email address to check
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	bool	true if the email exists, false otherwise
*/
function check_email_exists($user_email) {
	global $db, $lang;
	
	$user_email = str_replace("\\'", "''", $user_email);
	$user_email = stripslashes($user_email);
		
	$sql = "SELECT * FROM `" . USER . "` u 
			WHERE u.user_email = '" . $user_email . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
	if ( $db->numrows($result) <= 0 ) {
		$db->freeresult($result);
		return false;
	} else {
		$db->freeresult($result);
		return true;
	}
	
	return true;
}

/**
 * Returns all events that the user is registered and authorized for.
 *
 * @param	int		the user id to get the events of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the array of events the user is registered for
*/
function get_user_events($user_id) {
	global $db, $lang;
	
	// Data check
	if ( !is_numeric($user_id) || $user_id <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
	}
	
	$events = array();
	
	// Massive
	$sql = "SELECT * FROM `" . EVENT . "` e 
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			WHERE a.assignment_user_id = '" . $user_id . "'
				AND a.assignment_authorized = '1'
				AND e.event_start_date >= '" . CCCSTIME . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $auth = $db->getarray($result) ) {
		$events[] = $auth;
	}
	
	$db->freeresult($result);
	
	return $events;
}

/**
 * Returns a users real name (Vic Cherubini), for example
 *
 * @param	int		the ID of the user to get the name of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	string	the user's real name
*/
function get_user_real_name($user_id) {
	global $db, $lang;
	
	// Data check
	if ( !is_numeric($user_id) || $user_id <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_bad_id'], __LINE__, __FILE__);
	}
	
	$sql = "SELECT u.user_first_name, u.user_last_name 
			FROM `" . USER . "` u 
			WHERE u.user_id = '" . $user_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$user = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data'], __LINE__, __FILE__);
	
	return ($user['user_first_name'] . ' ' . $user['user_last_name']);
}

/**
 * Returns the user ID, region ID, type, user name, and email
 * about a user.
 *
 * @param	int		the ID of the user to get information of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the abbreviated user information
*/
function get_abbreviated_user_data($user_id) {
	global $db, $lang;
	
	$sql = "SELECT u.user_id, u.user_region_id, u.user_type, u.user_name, u.user_email 
			FROM `" . USER . "` u WHERE u.user_id = '" . $user_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$user = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data'], __LINE__, __FILE__);
	
	return $user;
}

/**
 * Inserts information about a user to track their stats.
 *
 * @param	int		the ID of the user to get information of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	true	always returns true
*/
function track_user_stats() {
	global $db, $lang, $usercache;
	
	if ( empty($_SESSION) || $_SESSION['user_logged_in'] == false ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_default']);
	}
	
	$stat_user_id = $usercache['user_id'];
	$ip_address = trim($_SERVER['REMOTE_ADDR']);
	$referer = trim(addslashes($_SERVER['HTTP_REFERER']));
	$user_agent = trim(addslashes($_SERVER['HTTP_USER_AGENT']));
	$scriptname = trim(addslashes($_SERVER['SCRIPT_NAME']));
	$querystring = addslashes($_SERVER['QUERY_STRING']);
	$stat_date = CCCSTIME;
	
	$sql = "INSERT INTO `" . USER_STAT . "`(stat_id, stat_user_id, stat_date, stat_user_agent, stat_referer, stat_scriptname, stat_querystring, stat_ip)
			VALUES (NULL, '" . $stat_user_id . "', '" . $stat_date . "', '" . $user_agent . "', '" . $referer . "', '" . $scriptname . "', '" . $querystring . "',
					'" . $ip_address . "')";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	return true;
}

/**
 * Returns an array of staff members from a certain
 * region.
 *
 * @param	int		the ID of the region to get an array of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language
 *
 * @return	array	the list of staff members of that region
*/
function get_staff_members_by_region($region_id) {
	global $db, $lang;
	
	$staffs = array();
	$sql = "SELECT * FROM `" . STAFF . "` s WHERE 
				s.staff_region_id = '" . $region_id . "' 
			ORDER BY s.staff_sortorder ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $staff = $db->getarray($result) ) {
		array_push($staffs, $staff);
	}
	
	$db->freeresult($result);
	
	return $staffs;
}

?>