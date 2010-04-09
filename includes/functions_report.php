<?php

/**
 * functions_report.php
 * Contains commonly used functions across the website. See each function 
 * of interest for a more in depth explanation of what it does.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

/**
 * Creates the volunteer report showing all volunteers in a region with their
 * total hours taught.
 *
 * @param	int		the region ID to get the volunteers from
 * @param	bool	whether or not to print the in printable form or "nice" form
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 * @global	object	the global template handle
 * @global	array	the global database configuration
 *
 * @return	string	the complete HTML report
*/
function create_volunteer_report($report_region_id, $report_start_date, $report_end_date, $sort_type, $sort_field, $print_report = false) {
	global $db, $lang, $t, $dbconfig;
	
	$report_sort_field = $sort_field;
	
	if ( $sort_field == 'name' ) {
		$sort_field = "u.user_last_name";
	} elseif ( $sort_field == 'hours' ) {
		$sort_field = "num_hours";
	}
	
	$sort_type = strtoupper($sort_type);
	
	if ( $report_region_id == 0 ) {
		$sql = "SELECT SUM(h.hour_count) AS num_hours, u.user_id, u.user_type, u.user_name, u.user_first_name, 
					u.user_last_name, u.user_email, u.user_volunteer_type, u.user_job_company,
					u.user_language_is_bilingual, u.user_language_spanish, u.user_language_other, u.user_language_other_language
				FROM `" . USER . "` u
				LEFT JOIN `" . HOUR . "` h
					ON u.user_id = h.hour_user_id
				WHERE u.user_authorized = '1'
					AND u.user_type < '" . APPLICANT . "'
				GROUP BY u.user_id
				ORDER BY " . $sort_field . " " . $sort_type;
	} else {
		$sql = "SELECT SUM(h.hour_count) AS num_hours, u.user_id, u.user_type, u.user_name, u.user_first_name, 
					u.user_last_name, u.user_email, u.user_volunteer_type, u.user_job_company,
					u.user_language_is_bilingual, u.user_language_spanish, u.user_language_other, u.user_language_other_language
				FROM `" . USER . "` u
				LEFT JOIN `" . HOUR . "` h
					ON u.user_id = h.hour_user_id
				WHERE u.user_region_id = '" . $report_region_id . "'
					AND u.user_authorized = '1'
					AND u.user_type < '" . APPLICANT . "'
				GROUP BY u.user_id
				ORDER BY " . $sort_field . " " . $sort_type;
	}

	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$user_photo = NULL;
	$total_hours = 0;
	$total_volunteers = 0;
	
	$rds = array();
	$vss = array();
	$vs = array();
	
	while ( $user = $db->getarray($result) ) {
		if ( $user['user_type'] == REGIONAL_DIRECTOR ) {
			array_push($rds, $user);
		} elseif ( $user['user_type'] == VOLUNTEER_STAFF || $user['user_type'] == ADMINISTRATOR ) {
			array_push($vss, $user);
		} else {
			array_push($vs, $user);
		}
	}
	
	$template_name = ( $print_report == false ) ? 'report_volunteers_report_item' : 'report_volunteers_report_item_print';
	
	$rd_list = make_volunteer_report_bit($template_name, $rds, $report_start_date, $report_end_date);
	$vs_list = make_volunteer_report_bit($template_name, $vss, $report_start_date, $report_end_date);
	$v_list = make_volunteer_report_bit($template_name, $vs, $report_start_date, $report_end_date);
	
	if ( $report_region_id == 0 ) {
		$region_name = $lang['Regions_all_regions'];
	} else {
		$region_name = get_region_name($report_region_id);
	}
	
	if ( $print_report == false ) {
		$t->set_template( load_template('report_volunteers_report') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Report_volunteers'],
			'L_ID' => $lang['Id'],
			'L_NAME' => $lang['Name'],
			'L_EMAIL' => $lang['Email'],
			'L_COMPANY' => $lang['Company'],
			'L_LANGUAGE' => $lang['Languages'],
			'L_HOURS' => $lang['Control_panel_hours'],
			'L_PRINT_REPORT' => $lang['Report_print'],
			'L_TOTAL' => $lang['Report_total'],
			'L_TO' => $lang['To'],
			'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
			'REPORT_REGION_NAME' => $region_name,
			'REPORT_START_DATE_TEXT' => date($dbconfig['date_format'], $report_start_date),
			'REPORT_END_DATE_TEXT' => date($dbconfig['date_format'], $report_end_date),
			'REPORT_START_DATE' => $report_start_date,
			'REPORT_END_DATE' => $report_end_date,
			'REPORT_LIST' => $v_list[0],
			'REPORT_REGION_ID' => $report_region_id,
			'REGION_ID' => $report_region_id,
			'TOTAL_VOLUNTEERS' => count($vs),
			'TOTAL_HOURS' => $v_list[1],
			'SORT_TYPE' => $sort_type,
			'SORT_FIELD' => $report_sort_field
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$t->set_template( load_template('report_volunteers_report') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Titles'][3],
			'REPORT_LIST' => $vs_list[0],
			'TOTAL_VOLUNTEERS' => count($vss),
			'TOTAL_HOURS' => $vs_list[1]
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
		$t->set_template( load_template('report_volunteers_report') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Titles'][2],
			'REPORT_LIST' => $rd_list[0],
			'TOTAL_VOLUNTEERS' => count($rds),
			'TOTAL_HOURS' => $rd_list[1]
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
	} elseif ( $print_report == true ) {
		$t->set_template( load_template('report_volunteers_report_print') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Report_volunteers'],
			'L_ID' => $lang['Id'],
			'L_NAME' => $lang['Name'],
			'L_EMAIL' => $lang['Email'],
			'L_COMPANY' => $lang['Company'],
			'L_TOTAL' => $lang['Report_total'],
			'L_TO' => $lang['To'],
			'REPORT_LIST' => $v_list[0],
			'REPORT_START_DATE' => date($dbconfig['date_format'], $report_start_date),
			'REPORT_END_DATE' => date($dbconfig['date_format'], $report_end_date),
			'REPORT_REGION' => $region_name,
			'TOTAL_VOLUNTEERS' => count($vs),
			'TOTAL_HOURS' => $v_list[1]
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$t->set_template( load_template('report_volunteers_report_print') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Titles'][3],
			'REPORT_LIST' => $vs_list[0],
			'TOTAL_VOLUNTEERS' => count($vss),
			'TOTAL_HOURS' => $vs_list[1]
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
		$t->set_template( load_template('report_volunteers_report_print') );
		$t->set_vars( array(
			'L_REPORT_TITLE' => $lang['Titles'][2],
			'REPORT_LIST' => $rd_list[0],
			'TOTAL_VOLUNTEERS' => count($rds),
			'TOTAL_HOURS' => $rd_list[1]
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	}
	
	$db->freeresult($result);
	
	return $content;
}

function make_volunteer_report_bit($template, $bit, $start_date, $end_date) {
	global $t, $lang, $dbconfig;
	
	for ( $i=0; $i<count($bit); $i++ ) {
		$user_language = NULL;
		if ( $bit[$i]['user_language_spanish'] == 1 ) {
			$user_language = $lang['Spanish'] . ( !empty($bit[$i]['user_language_other_language']) ? ', ' : NULL);
		}
		
		if ( ($bit[$i]['user_language_is_bilingual'] == 1 || $bit[$i]['user_language_other'] == 1) && !empty($bit[$i]['user_language_other_language']) ) {
			$user_language .= $bit[$i]['user_language_other_language'];
		}
		
		$user_num_hours = get_user_hours_date($bit[$i]['user_id'], $start_date, $end_date);
		$t->set_template( load_template($template) );
		$t->set_vars( array(
			'USER_ID' => $bit[$i]['user_id'],
			'USER_FIRST_NAME' => $bit[$i]['user_first_name'],
			'USER_LAST_NAME' => $bit[$i]['user_last_name'] . ( $bit[$i]['user_type'] == ADMINISTRATOR ? ' (A)' : NULL ),
			'USER_EMAIL' => $bit[$i]['user_email'],
			'USER_LANGUAGE' => $user_language,
			'USER_COMPANY' => $bit[$i]['user_job_company'],
			'USER_NUM_HOURS' => $user_num_hours
			)
		);
		$report_list .= $t->parse($dbconfig['show_template_name']);
		
		$total_hours += $user_num_hours;
		$total_volunteers++;
		
		$user_photo = NULL;
	}

	return array($report_list, $total_hours);
}


/**
 * Creates the NFCC report showing all volunteers in a region with their
 * total hours taught.
 *
 * @param	int		the Unix timestamp start date of events
 * @param	int		the Unix timestamp end date of events
 * @param	int		the ID of the region
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 * @global	object	the global template handle
 * @global	array	the global database configuration
 *
 * @return	string	the complete HTML report
*/
function create_nfcc_report($report_start_date, $report_end_date, $report_region_id, $print_report = false) {
	global $db, $lang, $t, $dbconfig;
	
	$audience_type_num_presentations_total = 0;
	$audience_type_total_attendance_total = 0;
	$primary_focus_num_presentations_total = 0;
	$primary_focus_num_presentations_total = 0;

	$region_sql = NULL;
	if ( $report_region_id != 0 ) {
		$region_sql = "AND e.event_region_id = '" . $report_region_id . "'";
	}
	
	$item_template = ( $print_report == false ? 'report_nfcc_report_item' : 'report_nfcc_report_print_item' );
	
	// Uncomment the #AND r.response_event_status = '1' if we don't want to include cancelled programs
	// One thing I've noticed is that RD's aren't authorizing people for events, thus, events expire
	// and their evaluation is filled out, but no one is getting credit for them! Thats why I'm only
	// selecting authorized events. What's frightening is the amount of evaluated programs that have
	// grants attached to them. Just comment out the Assignment stuff and you'll see how many more programs
	// are added into the total (along with the grant and billed totals).
	$sql = "SELECT r.response_id, r.response_attendance, r.response_grant_amount, r.response_billed_amount, r.response_event_status, ra.audience_name, COUNT(r.response_id) AS total_presentations, SUM(r.response_attendance) AS total_attendance, 
				SUM(r.response_grant_amount) AS total_grants, SUM(r.response_billed_amount) AS total_billed 
			FROM `" . RESPONSE . "` r, 
			`" . RESPONSE_AUDIENCE . "` ra 
			LEFT JOIN `" . EVENT . "` e 
				ON r.response_event_id = e.event_id 
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			WHERE r.response_completed = '1' 
				#AND r.response_event_status = '1'
				AND e.event_authorized = '1' 
				AND a.assignment_authorized = '1'
				" . $region_sql . " 
				AND e.event_start_date >= '" . $report_start_date . "' 
				AND e.event_end_date <= '" . $report_end_date . "' 
				AND r.response_audience = ra.audience_id 
			GROUP BY r.response_audience";
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$bg = 1;
	while ( $a = $db->getarray($result) ) {
		$t->set_template( load_template($item_template) );
		$t->set_vars( array(
			'BACKGROUND_COLOR' => ( $bg == 1 ? $dbconfig['style_dark_background'] : $dbconfig['style_light_background']),
			'REPORT_TYPE' => $a['audience_name'],
			'REPORT_NUM_PRESENTATIONS' => (empty($a['total_presentations']) ? 0 : $a['total_presentations']),
			'REPORT_TOTAL_ATTENDANCE' => (empty($a['total_attendance']) ? 0 : $a['total_attendance'])
			)
		);
		$report_list_audience_types .= $t->parse($dbconfig['show_template_name']);
	
		$audience_type_num_presentations_total += $a['total_presentations'];
		$audience_type_total_attendance_total += $a['total_attendance'];
		
		$audience_type_total_grant += $a['total_grants'];
		$audience_type_total_billed += $a['total_billed'];
		
		// Switch the background colors
		$bg = ( $bg == 1 ? 2 : 1);
	}
	
	$db->freeresult($result);

	// Uncomment the #AND r.response_event_status = '1' if we don't want to include cancelled programs
	$sql = "SELECT r.response_id, r.response_attendance, r.response_grant_amount, r.response_billed_amount, r.response_event_status, rf.focus_name, COUNT(r.response_id) AS total_presentations, SUM(r.response_attendance) AS total_attendance 
			FROM `" . RESPONSE . "` r, 
			`" . RESPONSE_FOCUS . "` rf 
			LEFT JOIN `" . EVENT . "` e 
				ON r.response_event_id = e.event_id
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			WHERE r.response_completed = '1' 
				#AND r.response_event_status = '1' 
				AND e.event_authorized = '1' 
				AND a.assignment_authorized = '1'
				" . $region_sql . " 
				AND e.event_start_date >= '" . $report_start_date . "' 
				AND e.event_end_date <= '" . $report_end_date . "' 
				AND r.response_primary_focus = rf.focus_id 
			GROUP BY r.response_primary_focus";

	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);	

	while ( $f = $db->getarray($result) ) {
		$t->set_template( load_template($item_template) );
		$t->set_vars( array(
			'BACKGROUND_COLOR' => ( $bg == 1 ? $dbconfig['style_dark_background'] : $dbconfig['style_light_background']),
			'REPORT_TYPE' => $f['focus_name'],
			'REPORT_NUM_PRESENTATIONS' => (empty($f['total_presentations']) ? 0 : $f['total_presentations']),
			'REPORT_TOTAL_ATTENDANCE' => (empty($f['total_attendance']) ? 0 : $f['total_attendance'])
			)
		);
		$report_list_primary_focus .= $t->parse($dbconfig['show_template_name']);
	
		$primary_focus_num_presentations_total += $f['total_presentations'];
		$primary_focus_total_attendance_total += $f['total_attendance'];

		// Switch the background colors
		$bg = ( $bg == 1 ? 2 : 1);
	}
	
	$db->freeresult($result);
	
	if ( $report_region_id == 0 ) {
		$region_name = $lang['Regions_all_regions'];
	} else {
		$region_name = get_region_name($report_region_id);
	}	
	
	if ( $print_report == false ) {
		$t->set_template( load_template('report_nfcc_report') );
		$t->set_vars( array(
			'L_REPORT_NFCC_REPORT_AUDIENCE_TYPES' => $lang['Report_nfcc_report_audience_types'],
			'L_REPORT_NFCC_REPORT_PRIMARY_FOCUS' => $lang['Report_nfcc_report_primary_focus'],
			'L_AUDIENCE_TYPE' => $lang['Report_audience_type'],
			'L_NUM_PRESENTATIONS' => $lang['Report_num_presentations'],
			'L_TOTAL_ATTENDANCE' => $lang['Report_total_attendance'],
			'L_PRIMARY_FOCUS' => $lang['Report_primary_focus'],
			'L_TOTAL' => $lang['Report_total'],
			'L_TOTAL_GRANT' => $lang['Report_total_grant'],
			'L_TOTAL_BILLED' => $lang['Report_total_billed'],
			'L_PRINT_REPORT' => $lang['Report_print'],
			'REPORT_LIST_AUDIENCE_TYPES' => $report_list_audience_types,
			'REPORT_LIST_PRIMARY_FOCUS' => $report_list_primary_focus,
			'REPORT_AUDIENCE_TYPE_NUM_PRESENTATIONS_TOTAL' => $audience_type_num_presentations_total,
			'REPORT_AUDIENCE_TYPE_TOTAL_ATTENDANCE_TOTAL' => $audience_type_total_attendance_total,
			'REPORT_PRIMARY_FOCUS_NUM_PRESENTATIONS_TOTAL' => $primary_focus_num_presentations_total,
			'REPORT_PRIMARY_FOCUS_TOTAL_ATTENDANCE_TOTAL' => $primary_focus_total_attendance_total,
			'REPORT_AUDIENCE_TYPE_GRANT_TOTAL' => number_format($audience_type_total_grant, 2),
			'REPORT_AUDIENCE_TYPE_BILLED_TOTAL' => number_format($audience_type_total_billed, 2),
			'REPORT_START_DATE' => $report_start_date,
			'REPORT_END_DATE' => $report_end_date,
			'REPORT_REGION_ID' => $report_region_id
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} else {
		$t->set_template( load_template('report_nfcc_report_print') );
		$t->set_vars( array(
			'L_REPORT_NFCC_REPORT_AUDIENCE_TYPES' => $lang['Report_nfcc_report_audience_types'],
			'L_REPORT_NFCC_REPORT_PRIMARY_FOCUS' => $lang['Report_nfcc_report_primary_focus'],
			'L_TO' => $lang['To'],
			'L_AUDIENCE_TYPE' => $lang['Report_audience_type'],
			'L_NUM_PRESENTATIONS' => $lang['Report_num_presentations'],
			'L_TOTAL_ATTENDANCE' => $lang['Report_total_attendance'],
			'L_PRIMARY_FOCUS' => $lang['Report_primary_focus'],
			'L_TOTAL' => $lang['Report_total'],
			'L_TOTAL_GRANT' => $lang['Report_total_grant'],
			'L_TOTAL_BILLED' => $lang['Report_total_billed'],
			'REPORT_LIST_AUDIENCE_TYPES' => $report_list_audience_types,
			'REPORT_LIST_PRIMARY_FOCUS' => $report_list_primary_focus,
			'REPORT_AUDIENCE_TYPE_NUM_PRESENTATIONS_TOTAL' => $audience_type_num_presentations_total,
			'REPORT_AUDIENCE_TYPE_TOTAL_ATTENDANCE_TOTAL' => $audience_type_total_attendance_total,
			'REPORT_PRIMARY_FOCUS_NUM_PRESENTATIONS_TOTAL' => $primary_focus_num_presentations_total,
			'REPORT_PRIMARY_FOCUS_TOTAL_ATTENDANCE_TOTAL' => $primary_focus_total_attendance_total,
			'REPORT_AUDIENCE_TYPE_GRANT_TOTAL' => number_format($audience_type_total_grant, 2),
			'REPORT_AUDIENCE_TYPE_BILLED_TOTAL' => number_format($audience_type_total_billed, 2),
			'REPORT_REGION_NAME' => $region_name,
			'REPORT_START_DATE' => date($dbconfig['date_format'], $report_start_date ),
			'REPORT_END_DATE' => date($dbconfig['date_format'], $report_end_date )
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	}
	
	return $content;
}

/**
 * Creates the Workshop Stat report showing all events with appropriate data.
 *
 * @param	string	the status of events they want to see
 * @param	int		the Unix timestamp start date of events
 * @param	int		the Unix timestamp end date of events
 * @param	int		the ID of the users to get the events of
 * @param	string	the type of volunteers to get, volunteer or staff
 * @param	int		the the title of the events to get
 * @param	int		the ID of the region
 * @param	string	whether to sort ASC or DESC
 * @param	string	the field to sort by
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 *
 * @return	string	the complete HTML report
*/
function create_workshop_stat_report($report_status, $report_start_date, $report_end_date, $report_revenue_type, $report_volunteer_id, $report_volunteer_type, $report_event_title, $report_region_id, $sort_type, $sort_field, $print_report = false) {
	global $db, $t, $lang, $dbconfig;
	
	$sql_report_status = NULL;
	$sql_report_volunteer = NULL;
	$sql_report_title = NULL;
	$sql_report_region = NULL;
	$sql_report_volunteer_type = NULL;
	$sql_report_revenue_type = NULL;
	
	if ( $report_status == 0 ) {
		$sql_report_status = "WHERE 1";
	} else {
		$report_status = ( $report_status == 1 ? $report_status : 0 );
		$sql_report_status = "WHERE r.response_event_status = '" . $report_status . "'";
	}
	
	if ( $report_volunteer_id == 'All' ) {
		$sql_report_volunteer = "AND a.assignment_authorized = '1'";
	} else {
		$sql_report_volunteer = "AND a.assignment_user_id = '" . $report_volunteer_id . "' AND a.assignment_authorized = '1' AND u.user_id = '" . $report_volunteer_id . "'";
	}
	
	if ( $report_event_title == 0 ) {
		$sql_report_title = NULL;
	} else {
		$sql_report_title = "AND e.event_program_id = '" . $report_event_title . "'";
	}
	
	if ( $report_region_id == 0 ) {
		$sql_report_region = NULL;
	} else {
		$sql_report_region = "AND e.event_region_id = '" . $report_region_id . "' AND r.response_region_id = '" . $report_region_id . "'";
	}
	
	if ( $report_volunteer_type == 'All' ) {
		$sql_report_volunteer_type = NULL;
	} else {
		if ( $report_volunteer_type == $lang['Control_panel_volunteer_types'][0] ) {
			$sql_report_volunteer_type = "AND u.user_type = '" . VOLUNTEER . "'";
		} elseif ( $report_volunteer_type == $lang['Control_panel_volunteer_types'][1] ) {
			$sql_report_volunteer_type = "AND u.user_type = '" . VOLUNTEER_STAFF . "'";
		} elseif ( $report_volunteer_type == $lang['Control_panel_volunteer_types'][2] ) {
			$sql_report_volunteer_type = "AND u.user_type <= '" . REGIONAL_DIRECTOR . "'";
		}
	}
	
	if ( $report_revenue_type == 'All' ) {
		$sql_report_revenue_type = NULL;
	} else {
		if ( $report_revenue_type == $lang['Report_revenue_types'][0] ) {
			$sql_report_revenue_type = "AND r.response_free_event = '1' AND r.response_billed = '0' AND r.response_grant_id = '0'";
		} elseif ( $report_revenue_type == $lang['Report_revenue_types'][1] ) {
			$sql_report_revenue_type = "AND r.response_billed = '1'";
		} elseif ( $report_revenue_type == $lang['Report_revenue_types'][2] ) {
			$sql_report_revenue_type = "AND r.response_grant_id > '0' AND r.response_grant_amount > '0'";
		}
	}
	
	$report_sort_field = $sort_field;
	
	$sort_field = 'e.event_start_date';

	$sort_type = strtoupper($sort_type);
	
	$sql = "SELECT r.response_id, r.response_event_id, r.response_region_id, r.response_completed, r.response_event_status, r.response_audience, r.response_primary_focus, r.response_subject, r.response_attendance, r.response_registered, r.response_billed, r.response_billed_amount, r.response_grant_id, r.response_grant_amount, e.event_id, e.event_program_id, e.event_contact_organization, e.event_start_date, e.event_end_date, p.*, a.*, u.user_id, u.user_first_name, u.user_last_name, u.user_type FROM `" . RESPONSE . "` r
			LEFT JOIN `" . EVENT . "` e
				ON r.response_event_id = e.event_id
			LEFT JOIN `" . PROGRAM . "` p 
				ON e.event_program_id = p.program_id
			LEFT JOIN `" . ASSIGNMENT . "` a
				ON e.event_id = a.assignment_event_id
			LEFT JOIN `" . USER . "` u
				ON a.assignment_user_id = u.user_id	
			" . $sql_report_status . "
				AND e.event_start_date >= '" . $report_start_date . "'
				AND e.event_end_date <= '" . $report_end_date . "'
			" . $sql_report_volunteer . "
			" . $sql_report_volunteer_type . "
			" . $sql_report_revenue_type . "
			" . $sql_report_title . "
			" . $sql_report_region ."
			AND r.response_completed = '1'
			AND e.event_complete = '1'
			GROUP BY r.response_id
			ORDER BY " . $sort_field . " " . $sort_type;
	//$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	echo $sql;
	
	$report_total_meetings = 0;
	$report_total_attendance = 0;
	$report_total_billing = 0;
	$report_total_donation = 0;
	$invoice_link = NULL;

	$template_name = ( $print_report == false ? 'report_workshop_stat_report_item' : 'report_workshop_stat_report_print_item');
	while ( $row = $db->getarray($result) ) {
		if ( $row['user_type'] == REGIONAL_DIRECTOR ) {
			$report_volunteer_type = '(S)';
		} elseif ( $row['user_type'] == VOLUNTEER_STAFF || $row['user_type'] == ADMINISTRATOR ) {
			$report_volunteer_type = '(VS)';
		} else {
			$report_volunteer_type = '(V)';
		}
		
		if ( $report_revenue_type == $lang['Report_revenue_types'][2] ) {
			if ( $row['response_grant_id'] > 0 ) {
				$row_organization = get_grant_organization($row['response_grant_id']);
			}
		} else {
			$row_organization = $row['event_contact_organization'];
		}
		
		// This *must* come before the setting of the item template.
		// If not, then once the item template is set, and make_link() is 
		// called, the currently loaded template is forked out of
		// and everything screws up.
		if ( $row['response_billed_amount'] > 0 ) {
			$invoice_link = make_link('invoice.php?do=invoice&amp;type=billed&amp;eventid=' . $row['event_id'], '(INV B)', 'target="_blank"');
		} 
		
		if ( $row['response_grant_id'] > 0 && $row['response_grant_amount'] > 0 ) {
			$invoice_link .= ' ' . make_link('invoice.php?do=invoice&amp;type=grant&amp;eventid=' . $row['event_id'], '(INV G)', 'target="_blank"');
		}
	
		if ( $row['response_region_id'] > 0 ) {
			$region_name = get_region_name($row['response_region_id']);
		} else {
			$region_name = $lang['Global'];
		}
		
		$response_attendance = intval($row['response_attendance']);
		$response_registered = intval($row['response_registered']);
		
		$report_percentage = round( ($response_attendance / $response_registered) * 100, 0);
		
		$t->set_template( load_template($template_name) );
		$t->set_vars( array(
			'REPORT_EVENT_ID' => $row['event_id'],
			'REPORT_TITLE' => stripslashes($row['program_name']),
			'REPORT_INVOICE_LINK' => $invoice_link,
			'REPORT_DATE' => date($dbconfig['date_format'], $row['event_start_date']),
			'REPORT_VOLUNTEER' => $row['user_first_name'] . ' ' . $row['user_last_name'],
			'REPORT_VOLUNTEER_TYPE' => $report_volunteer_type,
			'REPORT_ORGANIZATION' => $row_organization,
			'REPORT_REGION' => $region_name,
			'REPORT_ATTENDANCE' => $response_attendance,
			'REPORT_REGISTERED' => $response_registered,
			'REPORT_PERCENTAGE' => $report_percentage,
			'REPORT_BILLED' => number_format($row['response_billed_amount'], 2),
			'REPORT_DONATION' => number_format($row['response_grant_amount'], 2),
			'REPORT_USER_ID' => $row['user_id']
			)
		);
		$report_list .= $t->parse($dbconfig['show_template_name']);
		$report_total_meetings++;
		$report_total_attendance += $row['response_attendance'];
		$report_total_billing += $row['response_billed_amount'];
		$report_total_donation += $row['response_grant_amount'];
		
		$invoice_link = NULL;
	}
	
	if ( $report_region_id == 0 ) {
		$region_name = $lang['Regions_all_regions'];
	} else {
		$region_name = get_region_name($report_region_id);
	}
	
	if ( $report_revenue_type == 'Grant' ) {
		$organization = $lang['Grant'] . ' ' . $lang['Organization'];
	} else {
		$organization = $lang['Organization'];
	}
	
	if ( $print_report == false ) {
		$t->set_template( load_template('report_workshop_stat_report') );
		$t->set_vars( array(
			'L_WORKSHOP_STAT_REPORT' => $lang['Report_workshop_stat_report'],
			'L_TITLE' => $lang['Title'],
			'L_DATE' => $lang['Date'],
			'L_VOLUNTEER' => $lang['Report_volunteer'],
			'L_ORGANIZATION' => $organization,
			'L_ATTENDANCE' => $lang['Report_attendance'],
			'L_BILLED' => $lang['Billed'],
			'L_DONATION' => $lang['Donation'],
			'L_TOTAL_MEETINGS' => $lang['Report_total_meetings'],
			'L_PRINT_REPORT' => $lang['Report_print'],
			'REPORT_LIST' => $report_list,
			'REPORT_TOTAL_MEETINGS' => $report_total_meetings,
			'REPORT_TOTAL_ATTENDANCE' => $report_total_attendance,
			'REPORT_TOTAL_BILLING' => number_format($report_total_billing, 2),
			'REPORT_TOTAL_DONATION' => number_format($report_total_donation, 2),
			'REPORT_REGION_ID' => $report_region_id,
			'REPORT_STATUS' => $report_status,
			'REPORT_START_DATE' => $report_start_date,
			'REPORT_END_DATE' => $report_end_date,
			'REPORT_REVENUE_TYPE' => $report_revenue_type,
			'REPORT_VOLUNTEER_ID' => $report_volunteer_id,
			'REPORT_VOLUNTEER_TYPE' => $report_volunteer_type,
			'REPORT_EVENT_TITLE' => $report_event_title,
			'SORT_TYPE' => $sort_type,
			'SORT_FIELD' => $report_sort_field
			)
		);
		$report = $t->parse($dbconfig['show_template_name']);
	} else {
		$t->set_template( load_template('report_workshop_stat_report_print') );
		$t->set_vars( array(
			'L_WORKSHOP_STAT_REPORT' => $lang['Report_workshop_stat_report'],
			'L_ID' => $lang['Id'],
			'L_TITLE' => $lang['Title'],
			'L_DATE' => $lang['Date'],
			'L_VOLUNTEER' => $lang['Report_volunteer'],
			'L_ORGANIZATION' => $organization,
			'L_REGION' => $lang['Region'],
			'L_ATTENDANCE' => $lang['Report_attendance'],
			'L_BILLED' => $lang['Billed'],
			'L_DONATION' => $lang['Donation'],
			'L_TOTAL_MEETINGS' => $lang['Report_total_meetings'],
			'L_FROM' => $lang['Control_panel_from'],
			'REPORT_LIST' => $report_list,
			'REPORT_TOTAL_MEETINGS' => $report_total_meetings,
			'REPORT_TOTAL_ATTENDANCE' => $report_total_attendance,
			'REPORT_TOTAL_BILLING' => number_format($report_total_billing, 2),
			'REPORT_TOTAL_DONATION' => number_format($report_total_donation, 2),
			'REGION_NAME' => $region_name,
			'REPORT_DATE' => date($dbconfig['date_format'], CCCSTIME ),
			'REPORT_START_DATE' => date($dbconfig['date_format'], $report_start_date),
			'REPORT_END_DATE' => date($dbconfig['date_format'], $report_end_date)
			)
		);
		$report = $t->parse($dbconfig['show_template_name']);
	}
		
	return $report;
}

/**
 * Creates a list of accounting reports which is contains the first five questions
 * of the post-event evaluation and all of the event data.
 *
 * @param	int		the start date to start getting events
 * @param	int		the end date to finish getting events
 * @param	int		the ID of the region to get the events of
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 *
 * @return	string	the list of accounting reports
*/
function create_accounting_report($report_start_date, $report_end_date, $report_region_id, $print_report = false) {
	global $db, $t, $lang, $dbconfig;
	
	$region_sql = NULL;
	if ( $report_region_id != 0 ) {
		$region_sql = "AND e.event_region_id = '" . $report_region_id . "'";
	}
	
	$sql = "SELECT * FROM `" . EVENT . "` e
			LEFT JOIN `" . RESPONSE . "` r
				ON e.event_id = r.response_event_id
			WHERE e.event_authorized = '1'
				AND r.response_event_status = '1'
				AND r.response_completed = '1'
				AND e.event_start_date >= '" . $report_start_date . "'
				AND e.event_end_date <= '" . $report_end_date . "'
				AND r.response_grant_id > '0'
				AND r.response_grant_amount > '0'
				" . $region_sql . "
			ORDER BY e.event_region_id ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $event = $db->getarray($result) ) {
		if ( $print_report == false ) {
			$t->set_template( load_template('report_accounting_report_item') );
		} else {
			$t->set_template( load_template('report_accounting_report_print_item') );
		}
		
		$event_public = ( $event['event_public'] == 1 ) ? 'public' : 'private';
		$event_region = get_region_name($event['event_region_id']);
		
		if ( $event['response_grant_id'] != 0 || !empty($event['response_grant_amount']) ) {
			$grant_name = get_grant_name($event['response_grant_id']);
			$grant_amount = intval($event['response_grant_amount']);
		} else {
			$grant_name = $lang['Control_panel_no_grant_provided'];
			$grant_amount = 0;
		}
		
		$t->set_vars( array(
			'EVENT_PUBLIC' => $event_public,
			'EVENT_ID' => $event['event_id'],
			'EVENT_ORGANIZATION' => $event['event_contact_organization'],
			'EVENT_DATE' => date($dbconfig['date_format'], $event['event_start_date']),
			'EVENT_REGION' => $event_region,
			'EVENT_GRANT_TITLE' => $grant_name,
			'EVENT_GRANT_AMOUNT' => $grant_amount,
			'EVENT_NOTES' => $event['event_notes']
			)
		);
		$accounting_report .= $t->parse($dbconfig['show_template_name']);
	}
	
	$db->freeresult($result);
	
	if ( $print_report == false ) {
		$t->set_template( load_template('report_accounting_report') );
		$t->set_vars( array(
			'L_ACCOUNTING_REPORT' => $lang['Report_accounting_report'],
			'L_ID' => $lang['Id'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_REGION' => $lang['Region'],
			'L_GRANT_TITLE' => $lang['Grant'],
			'L_AMOUNT' => $lang['Report_grant_amount'],
			'L_PRINT_REPORT' => $lang['Report_print'],
			'L_TO' => $lang['To'],
			'ACCOUNTING_REPORT' => $accounting_report,
			'REPORT_START_DATE_TEXT' => date($dbconfig['date_format'], $report_start_date),
			'REPORT_END_DATE_TEXT' => date($dbconfig['date_format'], $report_end_date),
			'REPORT_START_DATE' => $report_start_date,
			'REPORT_END_DATE' => $report_end_date,
			'REPORT_REGION_ID' => $report_region_id
			)
		);
	} else {
		$t->set_template( load_template('report_accounting_report_print') );
		$t->set_vars( array(
			'L_ACCOUNTING_REPORT' => $lang['Report_accounting_report'],
			'L_ID' => $lang['Id'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_DATE' => $lang['Date'],
			'L_REGION' => $lang['Region'],
			'L_GRANT_TITLE' => $lang['Grant'],
			'L_AMOUNT' => $lang['Report_grant_amount'],
			'L_NOTES' => $lang['Notes'],
			'L_TO' => $lang['To'],
			'REPORT_DATE' => date($dbconfig['date_format'], CCCSTIME),
			'REPORT_START_DATE' => date($dbconfig['date_format'], $report_start_date),
			'REPORT_END_DATE' => date($dbconfig['date_format'], $report_end_date),
			'ACCOUNTING_REPORT' => $accounting_report
			)
		);
	}
	
	$report = $t->parse($dbconfig['show_template_name']);
	
	return $report;
}

/**
 * Creates an accounting report which is contains the first five questions
 * of the post-event evaluation and all of the event data.
 *
 * @param	int		the ID of the event to create the report on
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 *
 * @return	string	the accounting report
*/
function create_final_accounting_report($event_id, $print_report = false) {
	global $db, $t, $lang, $dbconfig;
	
	// This query *will* return one row only
	$sql = "SELECT e.*, r.*, p.program_id, p.program_name
			FROM `" . EVENT . "` e, `" . RESPONSE . "` r, `" . PROGRAM . "` p
			WHERE e.event_id = '" . $event_id . "' 
				AND r.response_event_id = '" . $event_id . "'
				AND e.event_program_id = p.program_id";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$e = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);

	$db->freeresult($result);
	
	$region_name = get_region_name($e['event_region_id']);
	
	// For some reason, they up and delete grants. Thus, this whole page
	// explodes if they try to come to the page and the grant has been
	// deleted. Thus, this check to see if the grant actually exists
	$sql = "SELECT * FROM `" . GRANT . "` g 
			WHERE g.grant_id = '" . $e['response_grant_id'] . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( !($g = $db->getarray($result)) ) {
		$e['response_grant_id'] = 0;
	}
	
	if ( $e['response_grant_id'] != 0 ) {
		$grant_name = get_grant_name($e['response_grant_id']);
	} else {
		$grant_name = $lang['Control_panel_no_grant_provided'];
	}
	
	$t->set_template( load_template('report_final_accounting_report') );
	$t->set_vars( array(
		'L_REPORT_TITLE' => $lang['Report_accounting_report'],
		'L_EVENT_INFORMATION' => $lang['Event_information'],
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
		'L_FAX_NUMBER' => $lang['Event_fax_number'],
		'L_PRESENTATION_INFORMATION' => $lang['Event_presentation_information'],
		'L_PROGRAM_TITLE' => $lang['Event_program_title'],
		'L_EVENT_LOCATION' => $lang['Event_location'],
		'L_EVENT_ADDRESS' => $lang['Event_address'],
		'L_EVENT_CITY' => $lang['Event_city'],
		'L_EVENT_STATE' => $lang['Event_state'],
		'L_EVENT_ZIP_CODE' => $lang['Event_zip_code'],
		'L_LOCATION_PHONE_NUMBER' => $lang['Event_phone_number'],
		'L_PROJECTION_EQUIPMENT' => $lang['Event_projection_equipment'],
		'L_ANTICIPATED_AUDIENCE' => $lang['Event_anticipated_audience'],
		'L_EVENT_REGION' => $lang['Event_region'],
		'L_QUESTION1' => $lang['Program_tracking_question1'],
		'L_QUESTION2' => $lang['Program_tracking_question2'],
		'L_QUESTION3' => $lang['Program_tracking_question3'],
		'L_QUESTION4' => $lang['Program_tracking_question4'],
		'L_QUESTION5' => $lang['Program_tracking_question5'],
		'L_QUESTION6' => $lang['Program_tracking_question6'],
		'L_QUESTION8' => $lang['Program_tracking_question8'],
		'L_QUESTION9' => $lang['Program_tracking_question9'],
		'L_PRINT_REPORT' => $lang['Report_print_report'],
		'REPORT_DATE' => date($dbconfig['date_format'], CCCSTIME ),
		'REGION_ID' => $region_id,
		'EVENT_ID' => $event_id,
		'EVENT_PROGRAM_TITLE' => stripslashes($e['program_name']),
		'EVENT_USER_NOTES' => $e['event_user_notes'],
		'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
		'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
		'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
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
		'EVENT_NOTES' => $e['event_notes'],
		'EVENT_PROJECTION_EQUIPMENT' => $e['event_projection_equipment'],
		'EVENT_ANTICIPATED_AUDIENCE' => $e['event_anticipated_audience'],
		'EVENT_REGION' => $region_name,
		'QUESTION1_VALUE' => yes_no($e['response_question1']),
		'QUESTION2_VALUE' => $e['response_question2'],
		'QUESTION3_VALUE' => yes_no($e['response_question3']),
		'QUESTION4_VALUE' => $grant_name,
		'QUESTION5_VALUE' => $e['response_question5'],
		'QUESTION6_VALUE' => yes_no($e['response_free_workshop']),
		'QUESTION8_VALUE' => yes_no($e['response_unrestricted_contributor']),
		'QUESTION9_VALUE' => yes_no($e['response_restricted_grant'])
		)
	);

	if ( $print_report == true ) {
		$t->set_vars( array(
			'L_PRINT_REPORT' => NULL
			)
		);
	} elseif ( $print_report == false ) {
		$t->set_vars( array(
			'L_PRINT_REPORT' => $lang['Report_print']
			)
		);
	}
	
	return $t->parse($dbconfig['show_template_name']);
}

/**
 * Creates a report (non printable) that shows all events for *this* month
 * for the user using their region ID.
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 * @global	array	the users information
 *
 * @return	string	the monthly report
*/
function create_monthly_report($report_region_id) {
	global $db, $t, $lang, $dbconfig, $usercache;

	$content = NULL;
	$e = array();
	$events_complete = array();
	$events_incomplete = array();
	$events_future = array();
		
	$today = CCCSTIME;
	$first_of_month = mktime(0, 0, 0, date('n', $today), 1, date('Y', $today) );
	$first_of_today = mktime(0, 0, 0, date('n', $today), date('j', $today), date('Y', $today) );
	$end_of_month = mktime(23, 59, 59, date('n', $today), date('t', $today), date('Y', $today) );
	
	//$regionid = $usercache['user_region_id'];
	$regionid = $report_region_id;
	
	$sql = "SELECT e.event_id, e.event_program_id, e.event_start_date, e.event_end_date, e.event_public,
					e.event_contact_email, e.event_contact_organization, p.program_name
			FROM `" . EVENT . "` e
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_start_date >= '" . $first_of_month . "' 
				AND e.event_end_date <= '" . $today . "' 
				AND e.event_region_id = '" . $regionid . "'
			ORDER BY e.event_start_date ASC";
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$event_data = NULL;
	$u = array();
	while ( $e = $db->getarray($result) ) {
		$u = get_assigned_user($e['event_id']);
		$r = check_event_completed($e['event_id']);
		
		$event_complete = ( $r == true ? $lang['Event_completed'] : $lang['Event_incomplete'] );
		$event_public = ( $e['event_public'] == 1 ? 'public' : 'private');
		$user_first_name = ( empty($u) ? $lang['No_volunteer_assigned'] : $u['user_first_name'] );
		
		$t->set_template( load_template('report_monthly_report_past_item') );
		$t->set_vars( array(
			'EVENT_PUBLIC' => $event_public,
			'EVENT_ID' => $e['event_id'],
			'EVENT_TITLE' => stripslashes($e['program_name']),
			'EVENT_USER_FIRST_NAME' => $user_first_name,
			'EVENT_USER_LAST_NAME' => $u['user_last_name'],
			'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'EVENT_COMPLETE' => $event_complete
			)
		);
		$event_data .= $t->parse($dbconfig['show_template_name']);
		$e = array();
	}
	
	$db->freeresult($result);
	
	$t->set_template( load_template('report_monthly_report_past') );
	$t->set_vars( array(
		'L_REPORT_TITLE' => $lang['Report_past_events'],
		'L_ID' => $lang['Id'],
		'L_EVENT' => $lang['Event'],
		'L_USER' => $lang['Volunteer'],
		'L_DATE' => $lang['Date'],
		'L_COMPLETED' => $lang['Completed'],
		'REPORT_DATE' => date($dbconfig['date_format'], $today),
		'REPORT_DATA' => $event_data
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);
	
	unset($event_data);
	
	$sql = "SELECT e.event_id, e.event_program_id, e.event_start_date, e.event_end_date, 
					e.event_public, e.event_contact_email, e.event_contact_organization,
					e.event_start_date, e.event_end_date, p.program_name
			FROM `" . EVENT . "` e 
			LEFT JOIN `" . PROGRAM . "` p
				ON e.event_program_id = p.program_id
			WHERE e.event_start_date >= '" . $first_of_today . "' 
				AND e.event_end_date <= '" . $end_of_month . "' 
				AND e.event_region_id = '" . $regionid . "'
			ORDER BY e.event_start_date ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	while ( $e = $db->getarray($result) ) {
		// Ok, explanation here:
		// The above query will select events from the start of TODAY
		// to the end of the month. However, that isn't always a 100%
		// correct data set because if an event occurred entirely earlier
		// today, it will be represented here. Thus, for events that span
		// a previous time TODAY and a future time TODAY, they will not
		// be pulled if we just had $today as the e.event_start_date in the
		// query. Thus, we pull all events from the beginning of TODAY
		// to the end of the month and check to see if they haven't finished
		// yet, meaning they span sometime between an earlier time and a future
		// time.
		
		//print date('G', $e['event_end_date']) . ' ' . date('G', $today) . '<br />';
		if ( date('j', $e['event_end_date']) > date('j', $today) ) {
			$u = get_assigned_user($e['event_id']);
			
			$event_volunteer = ( empty($u) ? $lang['No_volunteer_assigned'] : $u['user_first_name'] . ' ' . $u['user_last_name'] );
			$event_public = ( $e['event_public'] == 1 ? 'public' : 'private');
			
			$t->set_template( load_template('report_monthly_report_future_item') );
			$t->set_vars( array(
				'EVENT_PUBLIC' => $event_public,
				'EVENT_ID' => $e['event_id'],
				'EVENT_TITLE' => stripslashes($e['program_name']),
				'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
				'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
				'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
				'EVENT_VOLUNTEER' => $event_volunteer
				)
			);
			$event_data .= $t->parse($dbconfig['show_template_name']);
			
			$u = array();
		}
	}
	
	$t->set_template( load_template('report_monthly_report_future') );
	$t->set_vars( array(
		'L_REPORT_TITLE' => $lang['Report_future_events'],
		'L_ID' => $lang['Id'],
		'L_EVENT' => $lang['Event'],
		'L_DATE' => $lang['Date'],
		'L_TIME' => $lang['Time'],
		'L_USER' => $lang['Volunteer'],
		'L_PRINT_REPORT' => $lang['Report_print'],
		'REPORT_DATE' => date($dbconfig['date_format'], $today),
		'REPORT_DATA' => $event_data,
		'REGION_ID' => $report_region_id
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
	
	$db->freeresult($result);
	
	unset($event_data);
	
	return $content;
}


/**
 * Creates a report (printable) that shows all events for *this* month
 * for the user using their region ID.
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 * @global	array	the users information
 *
 * @return	string	the printable monthly report
*/
function create_printable_monthly_report($report_region_id) {
	global $db, $t, $lang, $dbconfig, $usercache;
	
	$content = NULL;

	$region_id = $report_region_id;//$usercache['user_region_id'];

	$month = date('n', CCCSTIME);
	$year = date('Y', CCCSTIME);

	$first_day = mktime(0,0,0, $month, 1, $year);
	$numdays = date("t", $first_day);
	
	$info = getdate($first_day);
	$today = date("j", CCCSTIME);
	
	$weekday = $info['wday'];
	$day = 1;
	
	if ($weekday > 0) {
		for ($i=0; $i<$weekday; $i++) {
			$t->set_template( load_template('report_calendar_day_empty') );
			$t->set_vars( array(NULL) );
			$week .= $t->parse($dbconfig['show_template_name']);
		}
	}
	
	$events = array();
	while ($day <= $numdays) {
		$events = array();
		if ($weekday == 7) {
			$t->set_template( load_template('report_calendar_day_of_week') );
			$t->set_vars( array(
				'CALENDAR_DAY_OF_WEEK' => $week
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
			$weekday = 0;
			unset($week);
		}

		$start_of_day = mktime(0, 0, 0, intval($month), intval($day), intval($year));
		$end_of_day = mktime(23, 59, 59, intval($month), intval($day), intval($year));
		
		$events = get_events_per_day($region_id, $start_of_day, $end_of_day);
		
		for ( $i=0; $i<count($events); $i++ ) {
			$r = check_event_completed($events[$i]['event_id']);
			
			$event_complete = ( $r == true ? 'C' : 'I' );
			$event_public = ( $events[$i]['event_public'] == 1 ? 'public' : 'private' );
			
			$t->set_template( load_template('report_calendar_event_item') );
			$t->set_vars( array(
				'EVENT_ID' => $events[$i]['event_id'],
				'EVENT_PUBLIC' => $event_public,
				'EVENT_TITLE' => stripslashes($events[$i]['program_name']),
				'EVENT_COMPLETE' => $event_complete
				)
			);
			$day_content .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('report_calendar_day') );
		$t->set_vars( array(
			'CALENDAR_DAY' => $day,
			'CALENDAR_CONTENT' => $day_content
			)
		);
		$week .= $t->parse($dbconfig['show_template_name']);
		
		$day++;
		$weekday++;
		unset($day_content);
	}

	if ($weekday != 7) {
		for ($i=0; $i<(7-$weekday); $i++) {
			$t->set_template( load_template('report_calendar_day_empty') );
			$t->set_vars( array(NULL) );
			$week .= $t->parse($dbconfig['show_template_name']);
		}
	}
	
	$t->set_template( load_template('report_calendar_day_of_week') );
	$t->set_vars( array(
		'CALENDAR_DAY_OF_WEEK' => $week
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
	
	$t->set_template( load_template('report_calendar') );
	$t->set_vars( array(
		'L_SUNDAY' => $lang['Sunday'],
		'L_MONDAY' => $lang['Monday'],
		'L_TUESDAY' => $lang['Tuesday'],
		'L_WEDNESDAY' => $lang['Wednesday'],
		'L_THURSDAY' => $lang['Thursday'],
		'L_FRIDAY' => $lang['Friday'],
		'L_SATURDAY' => $lang['Saturday'],
		'CALENDAR_MONTH' => date('F', CCCSTIME),
		'CALENDAR_YEAR' => date('Y', CCCSTIME),
		'CALENDAR_CONTENT' => $content
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);
	
	return $content;
}

/**
 * Creates a report that shows how many Volunteer
 * hours there have beeen for a selected
 * region and date range.
 *
 * @global	object	the global database handle
 * @global	object	the global template handle
 * @global	array	the currently loaded language array
 * @global	array	the global database configuration
 *
 * @return	string	the number of hours in a sentence ready for printing
*/
function create_hourly_report($report_region_id, $report_start_date, $report_end_date) {
	global $db, $t, $lang, $dbconfig;
	
	$region_name = $lang['Regions_all_regions'];
	
	$sql = "SELECT * FROM `" . EVENT . "` e 
			LEFT JOIN `" . ASSIGNMENT . "` a 
				ON e.event_id = a.assignment_event_id 
			LEFT JOIN `" . USER . "` u 
				ON a.assignment_user_id = u.user_id 
			WHERE a.assignment_authorized = '1' 
				AND e.event_start_date >= '" . $report_start_date . "'
				AND e.event_end_date <= '" . $report_end_date . "'
				AND e.event_authorized = '1' 
				AND u.user_type = '" . VOLUNTEER . "'";
	if ( $report_region_id != 0 ) {
		$sql .= " AND e.event_region_id = '" . $report_region_id . "'";
		$region_name = get_region_name($report_region_id);
	}
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $e = $db->getarray($result) ) {
		$event_hours = date('G', $e['event_end_date']) - date('G', $e['event_start_date']);
		
		if ( $event_hours <= 0 ) {
			$event_hours = 1;
		}
		
		$sum += $event_hours;
	}
	
	$db->freeresult($result);
	
	$start_date = date($dbconfig['date_format'], $report_start_date);
	$end_date = date($dbconfig['date_format'], $report_end_date);
		
	$content = make_content( sprintf($lang['Report_total_hours'], $start_date, $end_date, $region_name, $sum) );
	
	return $content;
}

// This report isn't event used anymore, I don't think!
function create_quality_assurance_report($report_region_id, $report_volunteer_id, $report_start_date, $report_end_date) {
	global $db, $t, $lang, $dbconfig;
	
	// So we don't have to worry about putting an AND in or not, 
	// just always assume this and start with an AND
	$sql_start = "WHERE 1";
	
	if ( $report_region_id == 0 ) {
		$sql_report_region = NULL;
		$region_name = $lang['Regions_all_regions'];
	} else {
		$sql_report_region = "AND u.user_region_id = '" . $report_region_id . "'";
		$region_name = get_region_name($report_region_id);
	}
	
	if ( $report_volunteer_id == 'All' ) {
		$sql_report_user = NULL;
	} else {
		$sql_report_user = "AND u.user_id = '" . $report_volunteer_id . "'";
	}
	
	$report_partially_completed = 0;
	
	$sql = "SELECT u.user_id, u.user_region_id, 
				u.user_first_name, u.user_last_name
			FROM `" . USER . "` u 
			" . $sql_start . "
			" . $sql_report_region . "
			" . $sql_report_user;
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	$response = array();
	
	while ( $u = $db->getarray($result) ) {
		$user_id = $u['user_id'];
		
		if ( is_numeric($user_id) && $user_id > 0 ) {
			$response = get_partially_completed_response_count($user_id, $report_start_date, $report_end_date);
		}
				
		$t->set_template( load_template('report_quality_assurance_report_item') );
		$t->set_vars( array(
			'REPORT_USER_ID' => $user_id,
			'REPORT_EVENT_VOLUNTEER_NAME' => $u['user_first_name'] . ' ' . $u['user_last_name'],
			'REPORT_RESPONSE_COUNT' => $response[0],
			'REPORT_PARTIALLY_COMPLETED' => $response[1],
			'REPORT_REGION' => ( $report_region_id > 0 ? $region_name : get_region_name($u['user_region_id']) )
			)
		);
		$report_list .= $t->parse($dbconfig['show_template_name']);
		
		$report_partially_completed = 0;
	}
		
	$db->freeresult($result);

	
	$t->set_template( load_template('report_quality_assurance_report') );
	$t->set_vars( array(
		'L_QUALITY_ASSURANCE_REPORT' => $lang['Report_quality_assurance_report'],
		'L_TO' => $lang['To'],
		'L_ID' => $lang['Id'],
		'L_NAME' => $lang['Name'],
		'L_RESPONSE_COUNT' => $lang['Report_response_count'],
		'L_PARTIALLY_COMPLETED_INFORMATION' => $lang['Report_partially_completed_information'],
		'L_REGION' => $lang['Region'],
		'REPORT_REGION' => $region_name,
		'REPORT_START_DATE' => date($dbconfig['date_format'], $report_start_date),
		'REPORT_END_DATE' => date($dbconfig['date_format'], $report_end_date),
		'REPORT_LIST' => $report_list
		)
	);
	
	return $t->parse($dbconfig['show_template_name']);
}

// Fairly intensive function mainly because of the array manipulation and such.
// Use sparingly
function get_partially_completed_response_count($user_id, $report_start_date, $report_end_date) {
	global $db, $lang;
	
	$partially_complete = 0;
	
	$sql = "SELECT r.response_data FROM `" . RESPONSE . "` r 
			LEFT JOIN `" . EVENT . "` e 
				ON r.response_event_id = e.event_id 
			LEFT JOIN `" . ASSIGNMENT . "` a 
				ON e.event_id = a.assignment_event_id 
			WHERE a.assignment_user_id = '" . $user_id . "' 
				AND a.assignment_authorized = '1' 
				AND e.event_start_date >= '" . $report_start_date . "' 
				AND e.event_end_date <= '" . $report_end_date . "' 
				AND r.response_completed = '1'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$num_responses = $db->numrows($result);
	$r = $db->getarray($result);
	
	$data = array();
	$data = unserialize($r['response_data']);
	
	for ( $i=0; $i<count($data); $i++ ) {
		$response = unserialize($data[$i]);
		
		$sum = array_sum($response);

		if ( $sum > 0 ) {
			$partially_complete++;
			break;
		}
	}
	
	$db->freeresult($result);
	
	return array($num_responses, $partially_complete);
}


function create_income_report($report_region_id, $report_date, $print_report = false) {
	global $db, $t, $lang, $dbconfig;
	
	if ( $report_region_id == 0 ) {
		$sql_region = "WHERE 1";
	} else {
		$sql_region = "WHERE i.income_region_id = '" . $report_region_id . "'";
	}
	
	$sql = "SELECT * FROM `" . INCOME . "` i 
			" . $sql_region . "
			AND i.income_date >= '" . $report_date . "'";
	
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $i = $db->getarray($result) ) {
		if ( $i['income_region_id'] == 0 ) {
			$income_region = $lang['Global'];
		} else {
			$income_region = get_region_name($i['income_region_id']);
		}
			
		$t->set_template( load_template('report_income_report_item') );
		$t->set_vars( array(
			'INCOME_ID' => $i['income_id'],
			'INCOME_ORGANIZATION' => $i['income_organization'],
			'INCOME_DATE' => date($dbconfig['date_format'], $i['income_date']),
			'INCOME_AMOUNT' => number_format($i['income_amount'], 2),
			'INCOME_REGION' => $income_region
			)
		);
		$income_list .= $t->parse($dbconfig['show_template_name']);
	}
	
	$t->set_template( load_template('report_income_report') );
	$t->set_vars( array(
		'L_INCOME_REPORT' => $lang['Report_income'],
		'L_REPORTING_INCOME_AFTER' => $lang['Report_reporting_income_after'],
		'L_ID' => $lang['Id'],
		'L_ORGANIZATION' => $lang['Control_panel_organization'],
		'L_DATE' => $lang['Control_panel_date'],
		'L_AMOUNT' => $lang['Control_panel_income_amount'],
		'L_REGION' => $lang['Control_panel_region'],
		'REPORT_DATE_TEXT' => date($dbconfig['date_format'], $report_date),
		'INCOME_LIST' => $income_list
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
	
	unset($income_list);
	
	return $content;
}

?>