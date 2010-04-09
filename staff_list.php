<?php

/**
 * staff_list.php
 * There is a staff list associated with the website
 * which are special users who appear on the Contact
 * Us page.
 *
 * This eventually needs to be converted to just pulling
 * users from the Users table, rather than the Staff
 * List table.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_staff_list'], false);
$content_subtitle = NULL;

// Now we collect the information from the database for filling out this page
$pagination[] = array('staff_list.php', $lang['Staff_list']);

// Just show them the staff list if they are coming here from a link
$incoming = collect_vars($_REQUEST, array('do' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'adduser' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Add_staff']);
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names);
		
		array_unshift($r_ids, 0);
		array_unshift($r_names, $lang['Administration']);
		
		$region_list = make_drop_down('staff_region_id', $r_ids, $r_names, $region_id);
		
		$t->set_template( load_template('staff_manage_form') );
		$t->set_vars( array(
			'L_MANAGE_STAFF' => $lang['Add_staff'],
			'L_STAFF_REGION_LIST' => $lang['Staff_region_list'],
			'L_STAFF_NAME' => $lang['Staff_name'],
			'L_STAFF_TITLE' => $lang['Staff_title'],
			'L_STAFF_ADDRESS' => $lang['Staff_address'],
			'L_STAFF_PHONE' => $lang['Staff_phone'],
			'L_STAFF_EMAIL' => $lang['Staff_email'],
			'L_STAFF_SORTORDER' => $lang['Staff_sortorder'],
			'L_MANAGE_STAFF_MEMBER' => $lang['Save_staff_member'],
			'STAFF_ACTION' => 'addindividualuser',
			'STAFF_REGION_ID' => NULL,
			'STAFF_REGION_LIST' => $region_list,
			'STAFF_SORTORDER_LIST' => $lang['Staff_default_sortorder']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'edituser' ) {
		can_view(VOLUNTEER_STAFF);
		
		$pagination[] = array(NULL, $lang['Update_staff']);
		
		$incoming = collect_vars($_GET, array('regionid' => INT, 'staffid' => INT));
		extract($incoming);
		
		if ( $regionid < 0 || !is_numeric($regionid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
	
		if ( $staffid <= 0 || !is_numeric($staffid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
	
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $regionid ) || $usercache['user_type'] == ADMINISTRATOR ) {
			$staff = array();
			$staff = get_staff_data($regionid, $staffid);
			
			$r_ids = array();
			$r_names = array();
			get_regions($r_ids, $r_names);
			
			array_push($r_ids, 0);
			array_push($r_names, $lang['Administration']);
			
			$region_list = make_drop_down('staff_region_id', $r_ids, $r_names, $regionid);
		
			$staffs = array();
			$s_names = array($lang['First']);
			$s_ids = array(0);
			
			$staffs = get_staff_members_by_region($regionid);
			for ( $i=0; $i<count($staffs); $i++ ) {
				if ( $staffid != $staffs[$i]['staff_id'] ) {
					array_push($s_names, $lang['After'] . ' ' . $staffs[$i]['staff_name']);
					array_push($s_ids, $staffs[$i]['staff_id']);
				}
			}
			$staff_list = make_drop_down('staff_sortorder', $s_ids, $s_names);
			
			$t->set_template( load_template('staff_manage_form') );
			$t->set_vars( array(
				'L_MANAGE_STAFF' => $lang['Update_staff'],
				'L_STAFF_REGION_LIST' => $lang['Staff_region_list'],
				'L_STAFF_NAME' => $lang['Staff_name'],
				'L_STAFF_TITLE' => $lang['Staff_title'],
				'L_STAFF_ADDRESS' => $lang['Staff_address'],
				'L_STAFF_PHONE' => $lang['Staff_phone'],
				'L_STAFF_EMAIL' => $lang['Staff_email'],
				'L_STAFF_SORTORDER' => $lang['Staff_sortorder'],
				'L_MANAGE_STAFF_MEMBER' => $lang['Save_staff_member'],
				'STAFF_ACTION' => 'editindividualuser',
				'STAFF_ID' => $staffid,
				'STAFF_REGION_ID' => $regionid,
				'STAFF_REGION_LIST' => $region_list,
				'STAFF_NAME' => $staff['staff_name'],
				'STAFF_TITLE' => $staff['staff_title'],
				'STAFF_ADDRESS' => $staff['staff_address'],
				'STAFF_PHONE' => $staff['staff_phone'],
				'STAFF_EMAIL' => $staff['staff_email'],
				'STAFF_SORTORDER_LIST' => $staff_list
				)
			);
			$content = $t->parse($dbconfig['show_template_name']);
		}
	} elseif ( $do == 'deleteuser' ) {
		can_view(REGIONAL_DIRECTOR);
		
		$pagination[] = array(NULL, $lang['Delete_staff']);
		
		$incoming = collect_vars($_GET, array('staffid' => INT));
		extract($incoming);
		
		$sql = "DELETE FROM `" . STAFF . "` 
				WHERE staff_id = '" . $staffid . "'";
		
		$db->dbquery($sql) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Staff_deleted']);
	} else {
		// Start by getting all of the regions
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names);
	
		// Include the null region, or administration in this case...
		// Woulda been nice to know about this in the beginning rather than this hack. Oh well.
		array_unshift($r_ids, 0);
		array_unshift($r_names, $lang['Administration']);
		
		for ( $i=0; $i<count($r_names); $i++ ) {
			$staff = array();
			$staff = get_staff_members_by_region($r_ids[$i]);
			
			$staff_region = make_small_title(stripslashes($r_names[$i]));
			$staff_list .= $staff_region;
			
			for ( $j=0; $j<count($staff); $j++ ) {
				$staff_name = $staff[$j]['staff_name'];
				
				/**
				 * If the user is an Admin or RD, allow them to edit their staff information. If an Admin, they can
				 * edit every staff member.
				*/
				if ( (($usercache['user_type'] == REGIONAL_DIRECTOR) && ($usercache['user_region_id'] == $staff[$j]['staff_region_id'])) 
					|| $usercache['user_type'] == ADMINISTRATOR ) {
					$edit_link = make_link('staff_list.php?do=edituser&amp;regionid=' . $staff[$j]['staff_region_id'] . '&amp;staffid=' . $staff[$j]['staff_id'], $lang['Edit']);
					$staff_name = $staff[$j]['staff_name'] . ' ( ' . $edit_link . ' ';
	
					if ( $usercache['user_type'] == ADMINISTRATOR ) {
						$delete_link = make_link('staff_list.php?do=deleteuser&amp;staffid=' . $staff[$j]['staff_id'], $lang['Delete'], 'onclick="return confirm_delete();"');
						$staff_name .= ' | ' . $delete_link;
					}
	
					$staff_name .= ' ) ';
				}

				$t->set_template( load_template('staff_item') );
				$t->set_vars( array(
					'STAFF_NAME' => $staff_name . '<br />',
					'STAFF_TITLE' => $staff[$j]['staff_title'] . '<br />',
					'STAFF_ADDRESS' => nl2br($staff[$j]['staff_address']) . '<br />',
					'STAFF_PHONE' => nl2br($staff[$j]['staff_phone']) . '<br />',
					'STAFF_EMAIL' => $staff[$j]['staff_email']
					)
				);
	
				$staff_list .= $t->parse($dbconfig['show_template_name']);
			}
		}
		
		$db->freeresult($result);
		
		// If they are an administrator, allow them to add a new staff member
		if ( $usercache['user_type'] == ADMINISTRATOR ) {
			$add_link = make_link('staff_list.php?do=adduser', $lang['Add_staff_member']);
		}
		
		$content = make_content($staff_list . '<br />' . $add_link);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'editindividualuser' ) {
		$pagination[] = array(NULL, $lang['Update_staff']);
		
		$incoming = collect_vars($_POST, array('staffid' => INT, 'staff_region_id' => INT, 'staff_region_old_id' => INT, 'staff_name' => MIXED, 'staff_title' => MIXED, 'staff_address' => MIXED, 'staff_phone' => MIXED, 'staff_email' => MIXED, 'staff_sortorder' => INT));
		extract($incoming);
		
		if ( $staff_region_id < 0 || !is_numeric($staff_region_id) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
	
		if ( $staff_region_old_id < 0 || !is_numeric($staff_region_old_id) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( $staffid <= 0 || !is_numeric($staffid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !validate_email($staff_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		$sortorder_sql = NULL;
		if ( $staff_sortorder == 0 ) {
			$sortorder_sql = ", staff_sortorder = 1";
			
			// Then update all other members of their region
			// updating the sortorder by 1
			$sql = "UPDATE `" . STAFF . "` 
					SET staff_sortorder = staff_sortorder + 1 
					WHERE staff_region_id = '" . $staff_region_old_id . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		} else {
			/*
				1. find person to be sorted after's sortorder
				2. update this persons sort order to reflect theirs
			*/
			
			$sql = "SELECT s.staff_sortorder FROM `" . STAFF . "` s 
					WHERE s.staff_id = '" . $staff_sortorder . "'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			$s = $db->getarray($result);
			$sortorder_sql = ", staff_sortorder = '" . ($s['staff_sortorder'] + 1) . "'";
			$db->freeresult($result);
		}
		
		$sql = "UPDATE `" . STAFF . "` SET 
					staff_name = '" . $staff_name . "', 
					staff_title = '" . $staff_title . "', 
					staff_address = '" . $staff_address . "', 
					staff_phone = '" . $staff_phone . "', 
					staff_email = '" . $staff_email . "',
					staff_region_id = '" . $staff_region_id . "'
					" . $sortorder_sql . "
				WHERE staff_id = '" . $staffid . "' 
					AND staff_region_id = '" . $staff_region_old_id . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		$content = make_content($lang['Staff_updated']);
	} elseif ( $do == 'addindividualuser' ) {
		$pagination[] = array(NULL, $lang['Add_staff']);
		
		$incoming = collect_vars($_POST, array('staff_region_id' => INT, 'staff_name' => MIXED, 'staff_title' => MIXED, 'staff_address' => MIXED, 'staff_phone' => MIXED, 'staff_email' => MIXED));
		extract($incoming);
		
		if ( !validate_email($staff_email) ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_email']);
		}
		
		$sql = "INSERT INTO `" . STAFF . "`(staff_region_id, staff_name, staff_title, staff_address, staff_phone, staff_email) 
				VALUES ('" . $staff_region_id . "', '" . $staff_name . "', '" . $staff_title . "', '" . $staff_address . "', '" . $staff_phone . "', '" . $staff_email . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Staff_added']);
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