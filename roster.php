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

// Make the page pretty! This is because the page header isn't
// included in this page because it unecessarily prints out
// code we don't need
include $root_path . 'includes/page_printheader.php';

$incoming = collect_vars($_REQUEST, array('eventid' => INT, 'regionid' => INT));
extract($incoming);

// Ensure we're given a region id
if ( !is_numeric($eventid) || $eventid <= 0 ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
}

if ( !is_numeric($regionid) || $regionid <= 0 ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
}

// The following people can see this event's roster:
// All Admins, RD's from this region, and the volunteer
// who is scheduled to teach this event. Pretty tricky, huh?
$e = array();
$e = get_event_data($eventid);

$volunteer_can_view = false;
$sql = "SELECT * FROM `" . ASSIGNMENT . "` a 
		WHERE a.assignment_event_id = '" . $eventid . "' 
			AND a.assignment_user_id = '" . $usercache['user_id'] . "' 
			AND a.assignment_authorized = '1'";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

if ( $db->numrows($result) == 1 
	|| ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $e['event_region_id'] ) 
	|| $usercache['user_type'] == ADMINISTRATOR ) {
	$volunteer_can_view = true;
}

$event_title = $e['program_name'];

if ( $volunteer_can_view == false ) {	
	cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
}

$total_registrations = 0;

$sql = "SELECT * FROM `" . REGISTER_QUEUE . "` rq 
		WHERE rq.queue_event_id = '" . $eventid . "' 
			AND rq.queue_authorized = '1'";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

while ( $queue = $db->getarray($result) ) {
	$t->set_template( load_template('event_roster_item') );
	$t->set_vars( array(
		'QUEUE_ID' => $queue['queue_id'],
		'QUEUE_FIRST_NAME' => $queue['queue_first_name'],
		'QUEUE_LAST_NAME' => $queue['queue_last_name'],
		'QUEUE_ADDRESS_ONE' => $queue['queue_address_one'],
		'QUEUE_ADDRESS_TWO' => $queue['queue_address_two'],
		'QUEUE_CITY' => $queue['queue_city'],
		'QUEUE_STATE' => $queue['queue_state'],
		'QUEUE_ZIP_CODE' => $queue['queue_zip_code'],
		'QUEUE_BANKRUPTCY_NUMBER' => $queue['queue_bankruptcy_number']
		)
	);
	$event_roster_list .= $t->parse($dbconfig['show_template_name']);
	$total_registrations++;
}

$t->set_template( load_template('event_roster') );
$t->set_vars( array(
	'L_ROSTER' => $lang['Event_roster'],
	'L_ID' => $lang['Id'],
	'L_NAME' => $lang['Name'],
	'L_ADDRESS' => $lang['Address'],
	'L_CITY' => $lang['City'],
	'L_STATE' => $lang['State'],
	'L_BANKRUPTCY_NUMBER' => $lang['Control_panel_bankruptcy_filing_number'],
	'L_SIGNATURE' => $lang['Event_signature'],
	'L_TOTAL' => $lang['Report_total'],
	'EVENT_NAME' => $event_title,
	'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
	'EVENT_ROSTER_LIST' => $event_roster_list,
	'TOTAL_REGISTRATIONS' => $total_registrations
	)
);
$content = $t->parse($dbconfig['show_template_name']);

unset($e);

$t->set_template( load_template('overall_body') );
$t->set_vars( array(
	'BODY_CONTENT' => $content,
	'BODY_COPYRIGHT' => NULL
	)
);
print $t->parse($dbconfig['show_template_name']);
		
include $root_path . 'includes/page_exit.php';

?>