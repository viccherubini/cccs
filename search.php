<?php

/**
 * search.php
 * Search events by their ID's
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_search'], false);

// See if they are an Applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

$incoming = collect_vars($_REQUEST, array('search_event_id' => INT));
extract($incoming);

// Sanitize $search_event_id
if ( !is_numeric($search_event_id) || $search_event_id <= 0 ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
}

$sql = "SELECT * FROM `" . EVENT . "` e 
		WHERE e.event_id = '" . $search_event_id . "'";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

if ( $db->numrows($result) == 0 ) {
	$content = make_content($lang['Error_no_search_results']);
} elseif ( $db->numrows($result) == 1 ) {
	// There will not be more than one result returned.
	$event = $db->getarray($result);
		
	$t->set_template( load_template('link') );
	$t->set_vars( array(
		'LINK_URL' => 'calendar.php?do=viewevent&amp;regionid=' . $event['event_region_id'] . '&amp;eventid=' . $event['event_id'],
		'LINK_CLASS' => NULL,
		'LINK_TEXT' => $lang['Search_follow_link']
		)
	);
	$link = $t->parse($dbconfig['show_template_name']);
	
	$region_name = get_region_name($event['event_region_id']);
	$found_it = sprintf($lang['Search_found_it'], $region_name);
	
	$content = make_content($found_it . $link);
}

$pagination[] = array('page.php?pagename=cmmv_business_center', $lang['CMMV_business_center']);
$pagination[] = array(NULL, $lang['Title_search']);			
$content_pagination = make_pagination($pagination);

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