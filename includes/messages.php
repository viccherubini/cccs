<?php

/**
 * messages.php
 * Sends a message on a specified date
 * when necessary.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

// Load the messages into an array
$messages = array();

// This \r is a thing for Windows...
//$dbconfig['messages'] = str_replace("\r", "", $dbconfig['messages']);

// This outputs the array structure
$messages = unserialize($dbconfig['messages']);

$today = date('j', CCCSTIME );

for ( $i=0; $i<count($messages); $i++ ) {
	if ( $today == $messages[$i]['date'] && $messages[$i]['sent'] == 0 ) {
		// Send out the message if its today
		$sql = "SELECT u.user_id FROM `" . USER . "` u 
				WHERE u.user_type <= '" . REGIONAL_DIRECTOR . "'
					AND u.user_authorized = '1'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
		while ( $u = $db->getarray($result) ) {
			send_message($messages[$i]['message_from'], $u['user_id'], CCCSTIME, addslashes($messages[$i]['subject']), addslashes($messages[$i]['message']) ) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
		}

		$db->freeresult($result);
		$messages[$i]['sent'] = 1;
	}
}

// Update the messages_sent thing if its the next day
for ( $i=0; $i<count($messages); $i++ ) {
	if ( $messages[$i]['sent'] == 1 && $messages[$i]['date'] != $today ) {
		$messages[$i]['sent'] = 0;
	}
}
$m = serialize($messages);

$sql = "UPDATE `" . CONFIG . "` 
		SET config_value = '" . addslashes($m) . "' 
		WHERE config_name = 'messages'";
$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

?>