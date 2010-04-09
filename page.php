<?php

/**
 * page.php
 * Displays a custom page for the website.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$incoming = collect_vars($_GET, array('pagename' => MIXED));
extract($incoming);

if ( empty($pagename) ) {
	cccs_message(WARNING_MESSAGE, $lang['Error_no_page_name'], __LINE__, __FILE__);
}

$sql = "SELECT * FROM `" . CUSTOM_PAGE . "` cp 
		WHERE cp.page_name = '" . $pagename . "'";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

$page = $db->getarray($result) or cccs_message(WARNING_MESSAGE, sprintf($lang['Error_failed_custom_page'], $page_name));

$db->freeresult($result);

// Test to see if the page is private or not
if ( $page['page_isprivate'] == 1 ) {
	can_view(APPLICANT);
}
	
// Test to see if the page is a main page
if ( $page['page_parent_id'] == 0 ) {
	if ( $page['page_isvisible'] == 0 ) {
		cccs_message(WARNING_MESSAGE, sprintf($lang['Error_hidden_page'], $pagename), __LINE__, __FILE__);
	}
	
	// Make the content for the main section
	$content_title = make_title($page['page_title'], false);
	$content_subtitle = make_title($page['page_subtitle'], true);

	if ( $page['page_useshtml'] == 0 ) {
		$content = parse_page($page['page_text']);
	} else {
		$content = $page['page_text'];
	}
	
	// Then get all of the sub pages and prepare them for printage
	$sql = "SELECT * FROM `" . CUSTOM_PAGE . "` cp 
			WHERE cp.page_parent_id = '" . $page['page_id'] . "'
				AND cp.page_isvisible = '1'
			ORDER BY cp.page_sortorder ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $sub_page = $db->getarray($result) ) {
		if ( $sub_page['page_useshtml'] == 0 ) {
			$sub_page_content = parse_page($sub_page['page_text']);
		} else {
			$sub_page_content = $sub_page['page_text'];
		}
		
		$t->set_template( load_template('content_small_title') );
		$t->set_vars( array(
			'TITLE' => $sub_page['page_name'],
			'CONTENT_TITLE' => $sub_page['page_title']
			)
		);
		$sub_page_title = $t->parse($dbconfig['show_template_name']);
		
		$content .= $sub_page_title . $sub_page_content;
	}
	
	$db->freeresult($result);
	
	// Now we collect the information from the database for filling out this page
	$pagination[] = array(NULL, $page['page_title']);
	$content_pagination = make_pagination($pagination);
} else {	// We are viewing an individual page
	// Pagination here will be a little bit more complicated
	// Now we collect the information from the database for filling out this page
	$sql = "SELECT * FROM `" . CUSTOM_PAGE . "` cp
			WHERE cp.page_id = '" . $page['page_parent_id'] . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$parent = $db->getarray($result) or cccs_message(WARNING_MESSAGE, sprintf($lang['Error_failed_custom_page'], $pagename) );

	$pagination[] = array('page.php?pagename=' . $parent['page_name'], $parent['page_title']);
	$pagination[] = array(NULL, $page['page_title']);
	$content_pagination = make_pagination($pagination);
		
	$content_title = make_title($page['page_title'], false);
	if ( !empty($page['page_subtitle']) ) {
		$content_subtitle = make_title($page['page_subtitle'], true);
	}
	
	if ( $page['page_useshtml'] == 0 ) {
		$content = parse_page($page['page_text']);
	} else {
		$content = $page['page_text'];
	}
	
	$db->freeresult($result);
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