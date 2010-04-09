<?php

/**
 * login.php
 * Allows a user to login and out of the website. 
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

$incoming = collect_vars($_POST, array('login' => INT, 'user_name' => MIXED, 'user_password' => MIXED));
extract($incoming);

if ( $login == 1 ) {
	if ( !empty($user_name) && !empty($user_password) ) {
		$user_password = md5($user_password);
		
		// To prevent SQL injection attacks, in the global file,
		// all GET and POST data is addslashed() and thus,
		// safe to query from the database.
		
		// Make sure the user exists
		$sql = "SELECT u.user_id, u.user_region_id, u.user_type, 
						u.user_authorized, u.user_name, u.user_password, 
						u.user_email, u.user_first_name, u.user_last_name, u.user_language 
				FROM `" . USER . "` u WHERE u.user_name = '" . $user_name . "' 
					AND u.user_password = '" . $user_password . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$user = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_user']);

		// Make sure they are authorized to be logged in
		if ( $user['user_type'] == APPLICANT || $user['user_authorized'] == 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_user_level']);
		}
		
		// It appears the user is ready to be logged in
		if ( $user_name == $user['user_name'] && $user_password == $user['user_password'] ) {
			if ( !(set_session($user)) ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_failed_session']);
			}
			
			// Update immediately so we can start to use $usercache
			// rather than $_SESSION
			// We only use $_SESSION for $_SESSION['user_logged_in']
			$usercache = update_session();
			
			// Now that the session is set, add it to the database
			// This is a little saftey procedure to ensure we don't
			// add the same user twice. It should never happen because 
			// an error should come up if they try to login again, but
			// its always nice to be cautious.
			$sql = "SELECT * FROM `" . SESSION . "` s 
					WHERE s.session_user_id = '" . $usercache['user_id'] . "'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			// They don't already have a session in the database
			if ( $db->numrows($result) == 0 ) {
				// Insert this as a new session
				$sql = "INSERT INTO `" . SESSION . "`(session_id, session_user_id, session_date)
						VALUES(NULL, '" . $usercache['user_id'] . "', '" . CCCSTIME . "');";
				$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			} else {
				// Update the old session
				$sql = "UPDATE `" . SESSION . "` SET session_date = '" . CCCSTIME . "' 
						WHERE session_user_id = '" . $usercache['user_id'] . "'";
				$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			}
		}
		
		// Redirect them to the page they came from
		$redirect_page = $_SERVER['HTTP_REFERER'];
		
		// Don't redirect them back to here...
		if ( preg_match('/login\.php/i', $redirect_page) || empty($redirect_page) ) {
			$redirect_page = PAGE_INDEX;
		}

		header('Location:' . $redirect_page);
	} else {
		cccs_message(WARNING_MESSAGE, $lang['Error_failed_user']);
	}
}

$incoming = collect_vars($_REQUEST, array('logout' => MIXED));
extract($incoming);

// Log them out of the system.
if ( !empty($logout) ) {
	$sql = "DELETE FROM `" . SESSION . "` 
			WHERE session_user_id = '" . $usercache['user_id'] . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	unset($usercache);
	
	$_SESSION['user_logged_in'] = false;
	$_SESSION = array();
	session_unset();
	session_destroy();
	
	header('Location: index.php');
}

?>