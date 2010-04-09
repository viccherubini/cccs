<?php

/**
 * unauthorized.php
 * Creates the unauthorized report.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

can_view(REGIONAL_DIRECTOR);

// Select all people from the register queue
// who have not been fullfilled, and have not
// even been authorized.
$sql = "SELECT rq.queue_id, rq.queue_event_id, rq.queue_authorized, 
			rq.queue_first_name, rq.queue_last_name, rq.queue_address_one, 
			rq.queue_address_two, rq.queue_city, rq.queue_state, rq.queue_zip_code,
			rq.queue_fullfilled, rq.queue_bankruptcy_number, e.event_id, e.event_program_id, e.event_start_date 
		FROM `" . REGISTER_QUEUE . "` rq 
		LEFT JOIN `" . EVENT . "` e 
			ON rq.queue_event_id = e.event_id
		WHERE rq.queue_authorized IN('0') 
			AND rq.queue_fullfilled IN('0')
			AND e.event_program_id IN('" . PROGRAM_BE_PHONE . "', '" . PROGRAM_BE_WEBEX . "')";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

//$program_language = $lang['Event_audience'][9];	// Fancy for 'English'
$date_string = date('M d Y', CCCSTIME);	// Have to use a custom one to get rid of commas

$fullfillment_date = date('mdY', CCCSTIME);
$filename = 'unauthorized' . $fullfillment_date . '.csv';

$fh = fopen($filename, 'w');
fwrite($fh, "First Name,Last Name,Address 1,Address 2,City,State,Zip Code,Bankruptcy Number,Current Date,Language\n");

while ( $rq = $db->getarray($result) ) {
	array_walk($rq, 'remove_commas');
	extract($rq);
	
	// Test for Media/Priority Mail
	if ( ( CCCSTIME + $time_to_send ) < $event_start_date ) {
		$mail_type = $lang['Media_mail'];
	} else {
		$mail_type = $lang['Priority_mail'];
	}
	
	$program_language = $rq['event_language'];
	
	$line = $queue_first_name . ',' . $queue_last_name . ',' . $queue_address_one . ',';
	$line .= $queue_address_two . ',' . $queue_city . ',' . $queue_state . ',';
	$line .= $queue_zip_code . ',' . $queue_bankruptcy_number . ',' . $date_string . ',' . $program_language;
	
	fwrite($fh, $line . "\n");
	unset($line);
}

fclose($fh);

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

readfile($filename);
unlink($filename);

$db->freeresult($result);

// Callback function for string replacing
function remove_commas(&$item, $key) {
	$item = str_replace(',', '', $item);
}

include $root_path . 'includes/page_exit.php';
?>