<?php

/**
 * region.php
 * Each region has a page associated with it, and this creates
 * those pages. Editable by RD's of that Region and Admins.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$incoming = collect_vars($_REQUEST, array('regionid' => INT, 'do' => MIXED, 'viewevents' => MIXED));
extract($incoming);

if ( $regionid <= 0 || !is_numeric($regionid) ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
}

$pagination[] = array(PAGE_REGION . '?regionid=' . $regionid, $lang['View_region']);

$r = get_region_data($regionid);

// UPDATE THIS! 12.21.2005
//if ( $r['region_image_id'] > 0 ) {
//	$header_image = load_image($r['region_image_id']);
//} else {
//	$header_image = $r['region_name'];
//}

// Load the regions image
if ( !empty($r['region_image']) ) {
	$t->set_template( load_template('image') );
	$t->set_vars( array(
		'IMAGE_SOURCE' => stripslashes($r['region_image']),
		'IMAGE_DESCRIPTION' => $r['region_name'],
		)
	);
	$region_image = $t->parse($dbconfig['show_template_name']);
	
	$content_title = make_title($region_image, false);
	
	unset($region_image);
} else {

}

$calendar_link = make_link("#" . $regionid . '_calendar', $lang['Click_to_go_to_calendar']) . '<br /><br />';
$content .= $calendar_link;

//$content_title = make_title($header_image, false);
$content_subtitle = NULL;

// If they are just viewing this page without any actions
if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'editpage' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $regionid ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array(NULL, $lang['Region_edit']);
		
		$sql = "SELECT * FROM `" . REGION_PAGE . "` rp 
				WHERE rp.page_region_id = '" . $regionid . "'
					AND rp.page_isvisible = '1'
				ORDER BY rp.page_sortorder ASC";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$page_text_id = 0;
		
		// Make the form for each section so it can be updated or deleted.
		while ( $p = $db->getarray($result) ) {
			$delete_checkbox = make_input_box(CHECKBOX, 'page_delete[]', $p['page_id']);
			
			$t->set_template( load_template('region_page_item_edit') );
			$t->set_vars( array(
				'L_DELETE' => $lang['Delete'],
				'PAGE_ID' => $p['page_id'],
				'CONTENT_TITLE' => trim($p['page_title']),
				'CONTENT' => trim($p['page_text']),
				'DELETE_CHECKBOX' => $delete_checkbox,
				'PAGE_TEXT_ID' => $page_text_id
				)
			);
			$edit_region_form .= $t->parse($dbconfig['show_template_name']);
			
			$page_text_id++;
		}
		
		$delete_checkbox = NULL;
		
		$add_all_pages = NULL;
		$add_all_checkbox = NULL;
		if ( $usercache['user_type'] == ADMINISTRATOR ) {
			$add_all_pages = $lang['All_pages'];
			$add_all_checkbox = make_input_box(CHECKBOX, 'page_add_all', 'all');
		}
		
		
		// Add another blank form
		$t->set_template( load_template('region_page_item_edit') );
		$t->set_vars( array(
			'L_DELETE' => $add_all_pages,
			'PAGE_ID' => 'all',
			'CONTENT_TITLE' => NULL,
			'CONTENT' => NULL,
			'DELETE_CHECKBOX' => $add_all_checkbox,
			'PAGE_TEXT_ID' => $page_text_id
			)
		);
		$edit_region_form .= $t->parse($dbconfig['show_template_name']);
		
		$t->set_template( load_template('region_page_form') );
		$t->set_vars( array(
			'L_SAVE_REGION_PAGE' => $lang['Save_region_page'],
			'REGION_ID' => $regionid,
			'EDIT_REGION_FORM' => $edit_region_form
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($edit_region_form, $add_all_checkbox, $add_all_pages, $delete_checkbox);
	} else {
		$numdays = collect_viewevents($viewevents);
		$view_events_list = make_drop_down('viewevents', $lang['View_events_future_name'], $lang['View_events_future'], $viewevents);
		
		$sql = "SELECT * FROM `" . REGION_PAGE . "` rp 
				WHERE rp.page_region_id = '" . $regionid . "'
					AND rp.page_isvisible = '1'
				ORDER BY rp.page_sortorder ASC";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		while ( $p = $db->getarray($result) ) {
			$page_text = parse_page($p['page_text']);
		
			$t->set_template( load_template('region_page_item') );
			$t->set_vars( array(
				'TITLE' => $p['page_name'],
				'CONTENT_TITLE' => trim($p['page_title']),
				'CONTENT' => $page_text
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		}
		
		$db->freeresult($result);
				
		$content .= make_public_calendar($regionid, $numdays);
		
		$t->set_template( load_template('event_pagination_form') );
		$t->set_vars( array(
			'L_VIEW_EVENTS_FROM' => $lang['View_events_from'],
			'L_SHOW_EVENTS' => $lang['Show_events'],
			'PAGINATION_URL' => PAGE_REGION,
			'REGION_ID' => $regionid,
			'VIEW_EVENTS_LIST' => $view_events_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
				
		// Show the edit button if the user is an Admin or RD from this region.	
		if ( $usercache['user_type'] == ADMINISTRATOR || ( $usercache['user_type'] == REGIONAL_DIRECTOR && $regionid == $usercache['user_region_id'] ) ) {
			$t->set_template( load_template('region_page_admin_edit_form') );
			$t->set_vars( array(
				'REGION_ID' => $regionid,
				'L_EDIT' => $lang['Edit_region_page']
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
		}
	}
}

// Let them edit the page
if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'editpage' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $regionid ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$content_subtitle = make_title($lang['Region_updated'], true);
		
		$pagination[] = array(NULL, $lang['Region_updated']);
				
		$incoming = collect_vars($_POST, array('page_add_all' => MIXED));
		extract($incoming);

		// First delete all of the pages that need deleting
		$pc = count($_POST['page_delete']);
		for ( $i=0; $i<$pc; $i++ ) {
			$page_id = $_POST['page_delete'][$i];
			
			$sql = "DELETE FROM `" . REGION_PAGE . "` 
					WHERE page_id = '" . $page_id . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		}
		
		// Now update the other region pages
		for ( $i=0; $i<count($_POST['page_title']); $i++ ) {
			$page_id = intval($_POST['pageid'][$i]);
			
			$page_title = trim($_POST['page_title'][$i]);
			$page_text = trim($_POST['page_text'][$i]);
			
			$page_name = strtolower( str_replace(' ', '_', $page_title) );
			
			if ( is_numeric($page_id) && $page_id > 0 ) {
				$sql = "UPDATE `" . REGION_PAGE . "` SET page_name = '" . $page_name . "', 
							page_title = '" . $page_title . "', page_text = '" . $page_text . "' 
						WHERE page_id = '" . $page_id . "'";
				$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			} else {
				if ( !empty($page_title) && !empty($page_text) ) {
					// If there needs to be a region page inserted into all regions
					if ( !empty($page_add_all) && $page_add_all == 'all' ) {
						$r_ids = array();
						$r_names = array();
						get_regions($r_ids, $r_names, false);
					
						$rc = count($r_ids);
						for ( $i=0; $i<$rc; $i++ ) {
							$sql = "INSERT INTO `" . REGION_PAGE . "`(page_id, page_region_id, page_name, page_title, page_text, 
									page_isvisible, page_useshtml, page_sortorder) 
								VALUES(NULL, '" . $r_ids[$i] . "', '" . $page_name . "', '" . $page_title. "', '" . $page_text . "', '1', '0', '0')";
							$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
						}
					} else {
						// Only insert this region page into this region
						$sql = "INSERT INTO `" . REGION_PAGE . "`(page_id, page_region_id, page_name, page_title, page_text, 
									page_isvisible, page_useshtml, page_sortorder) 
								VALUES(NULL, '" . $regionid . "', '" . $page_name . "', '" . $page_title. "', '" . $page_text . "', '1', '0', '0')";
						$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
					}
				}
			}
		}

		$content = make_content($lang['Region_page_updated']);
		
		unset($page_name, $page_title, $page_text);
	}
}

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