<?php

if ( !defined('IN_CCCS') ) {
	exit;
}

// Now that the user is logged in, check all the session stuff
if ( !empty($usercache) && $_SESSION['user_logged_in'] == TRUE ) {
	$sql = "SELECT * FROM `" . SESSION . "` s 
			WHERE s.session_user_id = '" . $usercache['user_id'] . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// If somehow they managed to get a session started
	// without anything being added to the database,
	// add it now
	if ( $db->numrows($result) == 0 ) {
		$sql = "INSERT INTO `" . SESSION . "`(session_id, session_user_id, session_date)
				VALUES(NULL, '" . $usercache['user_id'] . "', '" . CCCSTIME . "');";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	} elseif ( $db->numrows($result) == 1 ) {
		$s = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
	}
	
	// If the session is outdated, destroy it
	if ( ((int)$s['session_date'] + (int)$dbconfig['session_time']) < CCCSTIME ) {
		$sql = "DELETE FROM `" . SESSION . "` 
				WHERE session_user_id = '" . $usercache['user_id'] . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		unset($usercache);
		
		$_SESSION['user_logged_in'] = false;
		$_SESSION = array();
		session_destroy();
	} else {
		$sql = "UPDATE `" . SESSION . "` SET session_date = '" . CCCSTIME . "' 
				WHERE session_user_id = '" . $usercache['user_id'] . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	}
	
	// And finally (for real), do the most logged in
	$sql = "SELECT * FROM `" . SESSION . "` s";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( $db->numrows($result) > $dbconfig['most_logged_in'] ) {
		$sql = "UPDATE `" . CONFIG . "` 
				SET config_value = '" . $db->numrows($result) . "' 
				WHERE config_name = 'most_logged_in'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$sql = "UPDATE `" . CONFIG . "` 
				SET config_value = '" . CCCSTIME . "' 
				WHERE config_name = 'most_logged_in_date'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	}
}

// Finally clear all dead sessions
$sql = "DELETE FROM `" . SESSION . "` 
		WHERE (session_date+" . (int)$dbconfig['session_time'] . ") < '" . CCCSTIME . "'";
$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

?>