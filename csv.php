<?php

/**
 * csv.php
 * Creates a CSV file for a specified region or all regions.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

// See if they are a Volunteer or lower and somehow managed to get a session set.
// If so, tell them and log them out
can_view(VOLUNTEER_STAFF);

$incoming = collect_vars($_GET, array('report_region_id' => INT, 'report_include_clients' => INT));
extract($incoming);

// I like $regionid better
$regionid = $report_region_id;

if ( $regionid == 0 ) {
	$sql_add_region = NULL;
} else {
	$sql_add_region = "AND u.user_region_id = '" . $regionid . "'";
}

$sql = "SELECT u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.user_location_home_address_one, 
			u.user_location_home_address_two, u.user_location_city, u.user_location_state,
			u.user_location_zip_code, u.user_phone_number_work, u.user_phone_number_home, 
			u.user_phone_number_cell, u.user_phone_number_fax, u.user_job_company,
			u.user_cmmv_certification_date, u.user_language_is_bilingual
		FROM `" . USER . "` u
		WHERE u.user_authorized = '1'
			" . $sql_add_region . "
			AND u.user_type = '" . VOLUNTEER . "'
		ORDER BY u.user_id ASC";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

$fh = fopen('volunteers.csv', 'w');
fwrite($fh, "User ID,First Name,Last Name,Email Address,Address One,Address Two,City,State,Zip Code,Work Phone,Home Phone,Cell Number,Fax Number,Company,Certification Date,Is Bilingual\n");

while ( $u = $db->getarray($result) ) {
	array_walk($u, 'remove_commas');
	
	$language_is_bilingual = yes_no($u['user_language_is_bilingual']);
	
	if ( !empty($u['user_id']) ) {
		$line = $u['user_id'] . ',' . $u['user_first_name'] . ',' . $u['user_last_name'] . ',' . $u['user_email'] . ',';
		$line .= $u['user_location_home_address_one'] . ',' . $u['user_location_home_address_two'] . ',';
		$line .= $u['user_location_city'] . ',' . $u['user_location_state'] . ',' . $u['user_location_zip_code'] . ',';
		$line .= $u['user_phone_number_work'] . ',' . $u['user_phone_number_home'] . ',';
		$line .= $u['user_phone_number_cell'] . ',' . $u['user_phone_number_fax'] . ',' . $u['user_job_company'] . ',';
		$line .= $u['user_cmmv_certification_date'] . ',' . $language_is_bilingual;
		
		fwrite($fh, $line . "\n");
	}
	unset($line);
}

$db->freeresult($result);


// Do the clients if included
if ( $report_include_clients == 1 ) {
	if ( $report_region_id == 0 ) {
		$sql_add_region = NULL;
	} else {
		$sql_add_region = "AND e.event_region_id = '" . $regionid . "'";
	}
	
	$sql = "SELECT e.event_id, e.event_contact_organization, e.event_contact_name, 
				e.event_contact_email, e.event_contact_address, e.event_contact_city, 
				e.event_contact_state, e.event_contact_zip_code, e.event_contact_phone_number, 
				e.event_contact_fax_number 
			FROM `" . EVENT . "` e 
			WHERE e.event_authorized = '1' 
				AND e.event_start_date <= '" . CCCSTIME . "'
				" . $sql_add_region . "
			GROUP BY e.event_contact_email
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	fwrite($fh, "\n\nOrganization,Name,Email Address,Address,City,State,Zip Code,Phone Number,Fax Number\n");
	
	while ( $e = $db->getarray($result) ) {
		array_walk($e, 'remove_commas');
		
		if ( !empty($e['event_id']) ) {
			$line = $e['event_contact_organization'] . ',' . $e['event_contact_name'] . ',';
			$line .= $e['event_contact_email'] . ',' . $e['event_contact_address'] . ',';
			$line .= $e['event_contact_city'] . ',' . $e['event_contact_state'] . ',';
			$line .= $e['event_contact_zip_code'] . ',' . $e['event_contact_phone_number'] . ',';
			$line .= $e['event_contact_fax_number'];
			
			fwrite($fh, $line . "\n");
		}
		
		unset($line);
	}
	
	$db->freeresult($result);
}

// Callback function for string replacing
function remove_commas(&$item, $key) {
	$item = str_replace(',', '', $item);
}

fclose($fh);

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="cmmv_volunteers.csv"');

readfile('volunteers.csv');
unlink('volunteers.csv');

include $root_path . 'includes/page_exit.php';
?>