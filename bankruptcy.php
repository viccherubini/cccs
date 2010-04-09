<?php

/**
 * bankruptcy.php
 * Shows all of the Bankruptcy Education (BE) programs
 * for regions so people can register for them.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_bankruptcy_education_courses'], false);

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Bankruptcy_education_courses']);
$content_pagination = make_pagination($pagination);

$t->set_template( load_template('bankruptcy_courses_description') );
$t->set_vars( array() );
$content .= $t->parse($dbconfig['show_template_name']);

$incoming = collect_vars($_REQUEST, array('regionid' => INT, 'viewevents' => MIXED));
extract($incoming);

if ( empty($regionid) ) {
	$regionid = 'viewall';
}

$numdays = collect_viewevents($viewevents);
$one_day = 60 * 60 * 24;

if ( $numdays == 'allevents' ) {
	$sql_date = "AND e.event_end_date > '" . CCCSTIME . "'";
} else {
	$end_date = CCCSTIME + ( $one_day * $numdays);
	$sql_date = "AND e.event_start_date >= '" . CCCSTIME . "' AND e.event_end_date <= '" . $end_date . "' ";
}

$sql_region = NULL;
if ( !empty($regionid) ) {
	if ( $regionid == 'viewall' ) {
		$sql_region = NULL;
	} elseif ( is_numeric($regionid) ) {
		$regionid = intval($regionid);
		
		$sql_region = "AND e.event_region_id = '" . $regionid . "'";
	}
}

// Alright, now some fun begins!
// Select all of the BE program types
$sql = "SELECT * FROM `" . PROGRAM . "` p 
		WHERE p.program_include_bankruptcy IN(1) ORDER BY p.program_sortorder DESC";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
$i=0;
while ( $p = $db->getarray($result) ) {
	
	// Select all of the BE programs that are public,
	// from a specific date range.
	$sql = "SELECT e.*, r.region_name FROM `" . EVENT . "` e, `" . REGION . "` r
		WHERE e.event_program_id IN('" . $p['program_id'] . "') 
			AND e.event_complete = '0'
			AND e.event_region_id = r.region_id 
			AND e.event_public IN(1)
			" . $sql_date . "
			" . $sql_region . "
			ORDER BY e.event_start_date ASC";
	$event_result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

	while ( $e = $db->getarray($event_result) ) {
		$event_closed = ( $e['event_closed'] == 1 ? $dbconfig['style_closed_event'] : $dbconfig['style_event_public'] );
		
		$t->set_template( load_template('bankruptcy_event_list_item') );
		$t->set_vars( array(
			'L_REGISTER' => $lang['Register'],
			'EVENT_CLOSED' => $event_closed,
			'EVENT_ID' => $e['event_id'],
			'EVENT_REGION_ID' => $e['event_region_id'],
			'EVENT_ORGANIZATION' => stripslashes($e['event_contact_organization']),
			'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
			'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
			'EVENT_REGION' => $e['region_name']
			)
		);
		$event_list .= $t->parse($dbconfig['show_template_name']);
	}
	
	$t->set_template( load_template('bankruptcy_event_list') );
	$t->set_vars( array(
		'L_BANKRUPTCY_TIMES' => $lang['Bankruptcy_time_zone'][$i],
		'L_ID' => $lang['Id'],
		'L_ORGANIZATION' => $lang['Organization'],
		'L_DATE' => $lang['Date'],
		'L_TIME' => $lang['Time'],
		'L_REGION' => $lang['Region'],
		'L_REGISTER' => $lang['Register'],
		'PROGRAM_DESCRIPTION' => $p['program_description'],
		'EVENT_PROGRAM_TITLE' => stripslashes($p['program_name']),
		'EVENT_LIST' => $event_list
		)
	);
	$content .= $t->parse($dbconfig['show_template_name']);
	
	unset($event_list);
	$db->freeresult($event_result);
	$i++;
}

// The View Events From and By Region forms.
// These are specific to this page
$view_events_list = make_drop_down('viewevents', $lang['View_events_future_name'], $lang['View_events_future'], $viewevents);

$r_ids = array();
$r_names = array();
get_regions($r_ids, $r_names);

array_unshift($r_ids, 'viewall');
array_unshift($r_names, $lang['Bankruptcy_view_all_events']);
$income_region_list = make_drop_down('regionid', $r_ids, $r_names, $regionid);

$t->set_template( load_template('bankruptcy_view_event_form') );
$t->set_vars( array(
	'L_VIEW_EVENTS_FROM' => $lang['View_events_from'],
	'L_SHOW_EVENTS' => $lang['Show_events'],
	'L_VIEW_EVENTS_BY_REGION' => $lang['Bankruptcy_view_events_by_region'],
	'REGION_LIST' => $income_region_list,
	'PAGINATION_URL' => PAGE_BANKRUPTCY,
	'REGION_ID' => $regionid,
	'VIEW_EVENTS_LIST' => $view_events_list
	)
);
$content .= $t->parse($dbconfig['show_template_name']);

// If they are an admin, make a Fullfillment link
if ( $usercache['user_type'] == ADMINISTRATOR ) {
	$content .= make_link(PAGE_FULLFILLMENT, $lang['Create_fulfillment_file']);
	$content .= ' | ';
	$content .= make_link(PAGE_UNAUTHORIZED, $lang['Create_non_registered_file']);
	$content .= '<br /><br />';
}

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