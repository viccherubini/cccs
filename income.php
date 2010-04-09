<?php

/**
 * income.php
 * Manages income the site recieves, very similar to Grants
 * with their own history.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Income'], false);

$pagination[] = array('usercp.php', $lang['Control_panel']);	
$pagination[] = array('income.php', $lang['Income']);	

// See if they are a volunteer staff and somehow managed to get a session set.
// If so, tell them and log them out
can_view(VOLUNTEER_STAFF);

$incoming = collect_vars($_REQUEST, array('do' => MIXED, 'incomeid' => INT, 'regionid' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'viewincome' ) {
		$content_subtitle = make_title($lang['Control_panel_view_income'], true);
		
		$pagination[] = array(NULL, $lang['Control_panel_view_income']);

		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		if ( !check_income_permission($incomeid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		// Get the income item
		$income = get_income_data($incomeid);
		
		if ( $income['income_region_id'] == 0 ) {
			$income_region = $lang['Global'];
		} else {
			$income_region = get_region_name($income['income_region_id']);
		}
		
		// Get the income history
		$sql = "SELECT * FROM `" . INCOME_HISTORY . "` ih 
				WHERE ih.history_income_id = '" . $incomeid . "'";
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		while ( $ih = $db->getarray($result) ) {
			$t->set_template( load_template('control_panel_income_history_item') );
			$t->set_vars( array(
				'INCOME_HISTORY_DATE' => date($dbconfig['date_format'], $ih['history_date']),
				'INCOME_HISTORY_DESCRIPTION' => $ih['history_description'],
				'INCOME_HISTORY_AMOUNT' => number_format($ih['history_amount'], 2)
				)
			);
			$history_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);
			
		$month_list = make_drop_down('history_month', $m_keys, $m_values);
		$day_list = make_drop_down('history_day', make_day_array(), make_day_array() );
		$year_list = make_drop_down('history_year', make_year_array(), make_year_array() );
			
		$t->set_template( load_template('control_panel_view_income', false) );
		$t->set_vars( array(
			'L_VIEW_INCOME' => $lang['Control_panel_view_income'],
			'L_ID' => $lang['Id'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_INCOME_AMOUNT' => $lang['Control_panel_income_amount'],
			'L_INCOME_REGION' => $lang['Control_panel_region'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_contact_person'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_INCOME_HISTORY' => $lang['Control_panel_income_history'],
			'L_DATE' => $lang['Date'],
			'L_DESCRIPTION' => $lang['Invoice_description'],
			'L_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_ADD_HISTORY_ITEM' => $lang['Control_panel_add_history_item'],
			'L_EDIT_INCOME' => $lang['Control_panel_edit_income'],
			'L_DELETE_INCOME' => $lang['Control_panel_delete_income'],
			'L_DUPLICATE_INCOME' => $lang['Control_panel_duplicate_income'],
			'INCOME_ID' => $income['income_id'],
			'INCOME_DATE' => date($dbconfig['date_format'], $income['income_date']),
			'INCOME_AMOUNT' => number_format($income['income_amount'], 2),
			'INCOME_REGION' => $income_region,
			'INCOME_NOTES' => $income['income_notes'],
			'INCOME_CONTACT' => $income['income_contact'],
			'INCOME_ORGANIZATION' => $income['income_organization'],
			'INCOME_ADDRESS' => $income['income_address'],
			'INCOME_CITY' => $income['income_city'],
			'INCOME_STATE' => strtoupper($income['income_state']),
			'INCOME_ZIP_CODE' => $income['income_zip_code'],
			'INCOME_PHONE_NUMBER' => $income['income_phone_number'],
			'INCOME_HISTORY_LIST' => $history_list,
			'MONTH_LIST' => $month_list,
			'DAY_LIST' => $day_list,
			'YEAR_LIST' => $year_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'editincome' ) {
		$content_subtitle = make_title($lang['Control_panel_edit_income'], true);
		$pagination[] = array(NULL, $lang['Control_panel_edit_income']);
		
		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		if ( !check_income_permission($incomeid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		// Get the income item
		$income = get_income_data($incomeid);
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names, false);
	
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);
	
		array_push($r_ids, 0);
		array_push($r_names, $lang['Global']);
		$income_region_list = make_drop_down('income_region_id', $r_ids, $r_names, $income['income_region_id']);
		
		$month_list = make_drop_down('income_month', $m_keys, $m_values, date('n', $income['income_date']) );
		$day_list = make_drop_down('income_day', make_day_array(), make_day_array(), date('j', $income['income_date']) );
		$year_list = make_drop_down('income_year', make_year_array(), make_year_array(), date('Y', $income['income_date']) );
		
		$t->set_template( load_template('control_panel_edit_income_form', false) );
		$t->set_vars( array(
			'L_ADD_INCOME' => $lang['Control_panel_edit_income'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_INCOME_REGION' => $lang['Control_panel_region'],
			'L_INCOME_AMOUNT' => $lang['Control_panel_income_amount'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_contact_person'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_ADD_INCOME_BUTTON' => $lang['Control_panel_edit_income'],
			'INCOME_ID' => $incomeid,
			'INCOME_MONTH_LIST' => $month_list,
			'INCOME_DAY_LIST' => $day_list,
			'INCOME_YEAR_LIST' => $year_list,
			'INCOME_REGION_LIST' => $income_region_list,
			'INCOME_AMOUNT' => $income['income_amount'],
			'INCOME_NOTES' => stripslashes($income['income_notes']),
			'INCOME_CONTACT' => stripslashes($income['income_contact']),
			'INCOME_ORGANIZATION' => stripslashes($income['income_organization']),
			'INCOME_ADDRESS' => stripslashes($income['income_address']),
			'INCOME_CITY' => stripslashes($income['income_city']),
			'INCOME_STATE' => strtoupper(stripslashes($income['income_state'])),
			'INCOME_ZIP_CODE' => stripslashes($income['income_zip_code']),
			'INCOME_PHONE_NUMBER' => stripslashes($income['income_phone_number'])
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'deleteincome' ) {
		$content_subtitle = make_title($lang['Control_panel_edit_income'], true);
		$pagination[] = array(NULL, $lang['Control_panel_edit_income']);
		
		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
				
		if ( !(check_income_permission($incomeid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		$sql = "DELETE FROM `" . INCOME . "` 
				WHERE income_id = '" . $incomeid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_delete_income_thank_you']);
	} elseif ( $do == 'duplicateincome' ) {
		$content_subtitle = make_title($lang['Control_panel_edit_income'], true);
		$pagination[] = array(NULL, $lang['Control_panel_edit_income']);

		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		if ( !check_income_permission($incomeid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		duplicate_income($incomeid);
		
		$content = make_content($lang['Control_panel_duplicate_income_thank_you']);
	} else {
		// Just show all of the Income items.
		$content_subtitle = make_title($lang['Control_panel_add_income'], true);
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names, false);
	
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);

		$month_list = make_drop_down('income_month', $m_keys, $m_values );
		$day_list = make_drop_down('income_day', make_day_array(), make_day_array() );
		$year_list = make_drop_down('income_year', make_year_array(), make_year_array() );
		
		// Handle all of the sorting
		$incoming = collect_vars($_GET, array('sort' => MIXED, 'sort_field' => MIXED));
		extract($incoming);
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'i.income_region_id ASC';
		} else {
			if ( $sort_field == 'id' ) {
				$sort = 'i.income_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'region' ) {
				$sort = 'i.income_region_id ' . strtoupper($sort);
			}
		}
	
		$sql = "SELECT * FROM `" . INCOME . "` i 
					WHERE i.income_region_id = '" . $usercache['user_region_id'] . "' 
						OR i.income_region_id = '0' ORDER BY " . $sort;
		
		if ( !empty($regionid) ) {
			if ( $regionid == 'viewall' && $usercache['user_type'] == ADMINISTRATOR ) {
				$sql = "SELECT * FROM `" . INCOME . "` i ORDER BY " . $sort;
				$use_default = false;
			} elseif ( is_numeric($regionid) ) {
				$regionid = intval($regionid);
				
				if ( $usercache['user_type'] == ADMINISTRATOR || ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $regionid ) ) {
					$sql = "SELECT * FROM `" . INCOME . "` i 
						WHERE i.income_region_id = '" . $regionid . "' 
							OR i.income_region_id = '0' ORDER BY " . $sort;
					$use_default = false;
				}
			}
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		
		while ( $income = $db->getarray($result) ) {
			if ( $income['income_region_id'] == 0 ) {
				$income_region = $lang['Global'];
			} else {
				$income_region = get_region_name($income['income_region_id']);
			}
			
			$t->set_template( load_template('control_panel_income_list_item') );
			$t->set_vars( array(
				'INCOME_ID' => $income['income_id'],
				'INCOME_ORGANIZATION' => stripslashes($income['income_organization']),
				'INCOME_DATE' => date($dbconfig['date_format'], $income['income_date']),
				'INCOME_AMOUNT' => number_format($income['income_amount'], 2),
				'INCOME_REGION' => $income_region
				)
			);
			$income_list .= $t->parse($dbconfig['show_template_name']);
		}

		if ( $usercache['user_type'] == ADMINISTRATOR ) {
			array_unshift($r_ids, 'viewall');
			array_unshift($r_names, $lang['Control_panel_view_all_income']);
			$income_region_list = make_drop_down('regionid', $r_ids, $r_names, $regionid);
		
			$t->set_template( load_template('control_panel_view_income_form') );
			$t->set_vars( array(
				'L_VIEW_INCOME_BY_REGION' => $lang['Control_panel_view_income_by_region'],
				'L_VIEW_INCOME' => $lang['Control_panel_view_income'],
				'REGION_LIST' => $income_region_list
				)
			);
			$view_income_form = $t->parse($dbconfig['show_template_name']);
			
			// Get rid of the view all income option
			array_shift($r_ids);
			array_shift($r_names);
		}
		
		array_push($r_ids, 0);
		array_push($r_names, $lang['Global']);
		$income_region_list = make_drop_down('income_region_id', $r_ids, $r_names, $usercache['user_region_id']);
		
		$regionid = ( empty($regionid) ? 0 : $regionid );
		
		$t->set_template( load_template('control_panel_add_income_form', false) );
		$t->set_vars( array(
			'L_ADD_INCOME' => $lang['Control_panel_add_income'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_INCOME_REGION' => $lang['Control_panel_region'],
			'L_INCOME_AMOUNT' => $lang['Control_panel_income_amount'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_contact_person'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_ADD_INCOME_BUTTON' => $lang['Control_panel_add_income_button'],
			'L_EXISTING_INCOMES' => $lang['Control_panel_existing_income'],
			'L_ID' => $lang['Id'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_AMOUNT' => $lang['Control_panel_income_amount'],
			'L_REGION' => $lang['Control_panel_region'],
			'INCOME_ACTION' => 'addincome',
			'INCOME_ID' => NULL,
			'INCOME_MONTH_LIST' => $month_list,
			'INCOME_DAY_LIST' => $day_list,
			'INCOME_YEAR_LIST' => $year_list,
			'INCOME_REGION_LIST' => $income_region_list,
			'INCOME_LIST' => $income_list,
			'VIEW_INCOME_FORM' => $view_income_form,
			'REGION_ID' => $regionid
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	// As with the Grants, anytime a POST request is made,
	// its either to add income, edit income, or add an
	// income history item which recieves all of these values.
	$incoming = collect_vars($_POST, array('income_month' => INT, 'income_day' => INT, 'income_year' => INT, 'income_amount' => MIXED, 'income_region_id' => INT, 'income_notes' => MIXED, 'income_contact' => MIXED, 'income_organization' => MIXED, 'income_address' => MIXED, 'income_city' => MIXED, 'income_state' => MIXED, 'income_zip_code' => MIXED, 'income_phone_number' => MIXED));
	extract($incoming);
		
	$income_date = mktime(0, 0, 0, $income_month, $income_day, $income_year);
		
	$income_amount = str_replace(",", "", $income_amount);
	$income_amount = str_replace("$", "", $income_amount);
	
	// No phone number is required when adding income
	// history, and thus this step is skipped when
	// adding income history.
	if ( !validate_phone_number($income_phone_number) ) {
		cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
	}
		
	if ( $do == 'addincome' ) {
		$pagination[] = array(NULL, $lang['Control_panel_add_income']);
		
		if ( $usercache['user_type'] == ADMINISTRATOR || ($usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $income_region_id ) ) {
			$sql = "INSERT INTO `" . INCOME . "`
					VALUES ( '', '" . $income_date . "', '" . $income_amount . "', '" . $income_region_id . "',
							'" . $income_notes . "', '" . $income_contact . "',
							'" . $income_organization . "',
							'" . $income_address . "', '" . $income_city . "', '" . strtoupper($income_state) . "', 
							'" . $income_zip_code . "', '" . $income_phone_number . "')";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			$content = make_content($lang['Control_panel_add_income_thank_you']);
		} else {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
	} elseif ( $do == 'editincome' ) {
		$pagination[] = array('income.php?do=viewincome&amp;incomeid=' . $incomeid, $lang['Control_panel_view_income']);
		$pagination[] = array(NULL, $lang['Control_panel_edit_income']);
		
		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !( check_income_permission($incomeid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		$sql = "UPDATE `" . INCOME . "` SET income_date = '" . $income_date . "', income_amount = '" . $income_amount . "',
					income_region_id = '" . $income_region_id . "', income_notes = '" . $income_notes . "',
					income_contact = '" . $income_contact . "', income_organization = '" . $income_organization . "',
					income_address = '" . $income_address . "', income_city = '" . $income_city . "',
					income_state = '" . $income_state . "', income_zip_code = '" . $income_zip_code . "',
					income_phone_number = '" . $income_phone_number . "'
				WHERE income_id = '" . $incomeid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_edit_income_thank_you']);
	} elseif ( $do == 'addhistory' ) {
		$pagination[] = array('income.php?do=viewincome&amp;incomeid=' . $incomeid, $lang['Control_panel_view_income']);
		$pagination[] = array(NULL, $lang['Control_panel_history_added']);
		
		// Collect these variables seprately here
		$incoming = collect_vars($_POST, array('history_month' => INT, 'history_day' => INT, 'history_year' => INT, 'history_description' => MIXED, 'history_amount' => MIXED));
		extract($incoming);
		
		$history_date = mktime(0, 0, 0, $history_month, $history_day, $history_year);
		
		$history_amount = str_replace(",", "", $history_amount);
		$history_amount = str_replace("$", "", $history_amount);
		
		// Make sure we've got an ID
		if ( !is_numeric($incomeid) || $incomeid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !( check_income_permission($incomeid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_income']);
		}
		
		$sql = "INSERT INTO `" . INCOME_HISTORY . "`
				VALUES(NULL, '" . $incomeid . "', '" . $history_date . "', '" . $history_description . "', '" . $history_amount . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_hstory_income_thank_you']);
	}
}


// After the $pagination array has been updated, make the actual pagination
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