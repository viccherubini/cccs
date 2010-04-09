<?php

/**
 * grant.php
 * The control panel page was becoming unruly with all of that grant stuff,
 * and since grants are such a big part of the site, I moved them to this page.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Control_panel_add_grant'], false);

$pagination[] = array('usercp.php', $lang['Control_panel']);
$pagination[] = array('grant.php', $lang['Control_panel_add_grant']);	
	
// See if they are a volunteer staff and somehow managed to get a session set.
// If so, tell them and log them out
can_view(VOLUNTEER_STAFF);

$incoming = collect_vars($_REQUEST, array('do' => MIXED, 'grantid' => INT, 'regionid' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	// Everyone (thats logged in) can view a grant!
	if ( $do == 'viewgrant' ) {
		$pagination[] = array(NULL, $lang['View_grant']);
		$content_subtitle = make_title($lang['Title_view_grant'], true);

		if ( $grantid <= 0 || !is_numeric($grantid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !( check_grant_permission($grantid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}

		$grant = get_grant_data($grantid);
		
		if ( empty($grant) ) {
			cccs_message(WARNING_CRITICAL, $lang['Control_panel_no_grant_provided']);
		}
		
		if ( $grant['grant_region_id'] == 0 ) {
			$grant_region = $lang['Global'];
		} else {
			$grant_region = get_region_name($grant['grant_region_id']);
		}
		
		$t->set_template( load_template('control_panel_view_grant', false) );
		$t->set_vars( array(
			'L_VIEW_GRANT' => $lang['Control_panel_view_grant'],
			'L_GRANT_ID' => $lang['Control_panel_grant_id'],
			'L_START_DATE' => $lang['Control_panel_start_date'],
			'L_END_DATE' => $lang['Control_panel_end_date'],
			'L_GRANT_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_GRANT_REGION' => $lang['Control_panel_region'],
			'L_RESTRICTED' => $lang['Control_panel_grant_type'],
			'L_PURPOSE' => $lang['Control_panel_purpose'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_INVOICE' => $lang['Control_panel_invoice'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_grant_contact'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_DELETE_GRANT' => $lang['Control_panel_delete_grant'],
			'L_EDIT_GRANT' => $lang['Control_panel_edit_grant'],
			'L_DUPLICATE_GRANT' => $lang['Control_panel_duplicate_grant'],
			'GRANT_ID' => $grant['grant_id'],
			'GRANT_START_DATE' => date($dbconfig['date_format'], $grant['grant_start_date']),
			'GRANT_END_DATE' => date($dbconfig['date_format'], $grant['grant_end_date']),
			'GRANT_AMOUNT' => number_format($grant['grant_amount']),
			'GRANT_REGION' => $grant_region,
			'GRANT_TYPE' => $lang['Control_panel_grant_types'][ $grant['grant_type'] ],
			'GRANT_PURPOSE' => $grant['grant_purpose'],
			'GRANT_NOTES' => $grant['grant_notes'],
			'GRANT_INVOICE' => yes_no($grant['grant_invoice']),
			'GRANT_CONTACT' => $grant['grant_contact'],
			'GRANT_ORGANIZATION' => $grant['grant_organization'],
			'GRANT_ADDRESS' => $grant['grant_address'],
			'GRANT_CITY' => $grant['grant_city'],
			'GRANT_STATE' => $grant['grant_state'],
			'GRANT_ZIP_CODE' => $grant['grant_zip_code'],
			'GRANT_PHONE_NUMBER' => $grant['grant_phone_number']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		// And now we're gonna do the grant history, this should be fun
		$sql = "SELECT r.response_event_id, r.response_region_id, r.response_audience, 
					r.response_attendance, r.response_grant_amount, 
						e.event_program_id, e.event_start_date, ra.audience_name, p.program_name
				FROM `" . RESPONSE . "` r, `" . EVENT . "` e, `" . PROGRAM . "` p, `" . RESPONSE_AUDIENCE . "` ra
				WHERE r.response_grant_id = '" . $grantid . "'
					AND e.event_program_id = p.program_id
					AND r.response_event_id = e.event_id
					AND r.response_audience = ra.audience_id";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$grant_total_attendance = 0;
		$grant_total_programs = 0;
		$grant_total_amount = 0;
		
		while ( $grant = $db->getarray($result) ) {
			$t->set_template( load_template('control_panel_grant_history_item'));
			$t->set_vars( array(
				'GRANT_EVENT_ID' => $grant['response_event_id'],
				'GRANT_EVENT_DATE' => date($dbconfig['date_format'], $grant['event_start_date']),
				'GRANT_EVENT_AUDIENCE' => $grant['audience_name'],
				'GRANT_EVENT_ATTENDANCE' => number_format($grant['response_attendance'], 0),
				'GRANT_EVENT_TITLE' => $grant['program_name'],
				'GRANT_EVENT_REGION_ID' => $grant['response_region_id'],
				'GRANT_AMOUNT' => number_format($grant['response_grant_amount'], 2)
				)
			);
			$grant_history_list .= $t->parse($dbconfig['show_template_name']);
			
			$grant_total_attendance += intval($grant['response_attendance']);
			$grant_total_programs++;
			$grant_total_amount += (float)$grant['response_grant_amount'];
		}
		
		$t->set_template( load_template('control_panel_grant_history', false) );
		$t->set_vars( array(
			'L_GRANT_HISTORY' => $lang['Control_panel_grant_history'],
			'L_EVENT_ID' => $lang['Id'],
			'L_DATE' => $lang['Date'],
			'L_AUDIENCE' => $lang['Report_audience'],
			'L_ATTENDANCE' => $lang['Report_attendance'],
			'L_EVENT_TITLE' => $lang['Request_program_title'],
			'L_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_TOTALS' => $lang['Totals'],
			'L_PROGRAMS' => $lang['Programs'],
			'GRANT_HISTORY_LIST' => $grant_history_list,
			'GRANT_TOTAL_ATTENDANCE' => $grant_total_attendance,
			'GRANT_TOTAL_PROGRAMS' => $grant_total_programs,
			'GRANT_TOTAL_AMOUNT' => number_format($grant_total_amount, 2)
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
		unset($grant_history_list, $grant_total_attendance, $grant_total_programs, $grant_total_amount);
	} elseif ( $do == 'deletegrant' ) {
		$pagination[] = array(NULL, $lang['Delete_grant']);
		$content_subtitle = make_title($lang['Title_delete_grant'], true);
		
		if ( $grantid <= 0 || !is_numeric($grantid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}

		if ( !( check_grant_permission($grantid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}

		// Alright, this gets kinda hairy. When a grant is deleted,
		// make sure to update the references in the Response table
		// to that grant. This'll prevent mess ups when they delete a 
		// grant for whatever reason.
		$sql = "UPDATE `" . RESPONSE . "` SET response_grant_id = '0', response_grant_amount = '0'
				WHERE response_grant_id = '" . $grantid . "'";
		$db->dbquery($sql);

		$sql = "DELETE FROM `" . GRANT . "` 
				WHERE grant_id = '" . $grantid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_grant_deleted']);
	} elseif ( $do == 'editgrant' ) {
		$pagination[] = array(NULL, $lang['Edit_grant']);
		$content_subtitle = make_title($lang['Title_edit_grant'], true);
				
		if ( $grantid <= 0 || !is_numeric($grantid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !( check_grant_permission($grantid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}
		
		$grant = get_grant_data($grantid);
		
		$grant_balance = 0;
		$grant_balance = get_grant_balance($grantid);
		
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names);
	
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);

		array_push($r_ids, 0);
		array_push($r_names, $lang['Global']);
		$grant_region_list = make_drop_down('grant_region_id', $r_ids, $r_names, $grant['grant_region_id']);

		$month_start_list = make_drop_down('grant_start_month', $m_keys, $m_values, date("n", $grant['grant_start_date']), NULL, 'id="start_month"');
		$day_start_list = make_drop_down('grant_start_day', make_day_array(), make_day_array(), date("j", $grant['grant_start_date']), NULL, 'id="start_day"');
		$year_start_list = make_drop_down('grant_start_year', make_year_array(), make_year_array(), date("Y", $grant['grant_start_date']), NULL, 'id="start_year"');
		
		$month_end_list = make_drop_down('grant_end_month', $m_keys, $m_values, date("n", $grant['grant_end_date']), NULL, 'id="end_month"');
		$day_end_list = make_drop_down('grant_end_day', make_day_array(), make_day_array(), date("j", $grant['grant_end_date']), NULL, 'id="end_day"');
		$year_end_list = make_drop_down('grant_end_year', make_year_array(), make_year_array(), date("Y", $grant['grant_end_date']), NULL, 'id="end_year"');
		
		$type_list = make_drop_down('grant_type', array(0, 1), $lang['Control_panel_grant_types'], $grant['grant_type']);
		
		$grant_purpose_list = make_drop_down('grant_purpose', $lang['Control_panel_grant_purposes'], $lang['Control_panel_grant_purposes'], $grant['grant_purpose']);
		
		$grant_invoice_checked = ( $grant['grant_invoice'] == 1 ? 'checked="checked"' : NULL);
		
		$t->set_template( load_template('control_panel_edit_grant_form', false) );
		$t->set_vars( array(
			'L_ADD_GRANT' => $lang['Control_panel_add_grant'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_START_DATE' => $lang['Control_panel_start_date'],
			'L_END_DATE' => $lang['Control_panel_end_date'],
			'L_GRANT_REGION' => $lang['Control_panel_region'],
			'L_GRANT_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_RESTRICTED' => $lang['Control_panel_restricted'],
			'L_PURPOSE' => $lang['Control_panel_purpose'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_INVOICE' => $lang['Control_panel_invoice'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_contact_person'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_ADD_GRANT_BUTTON' => $lang['Control_panel_add_grant_button'],
			'L_EXISTING_GRANTS' => $lang['Control_panel_existing_grants'],
			'L_ID' => $lang['Control_panel_id'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_TYPE' => $lang['Control_panel_grant_type'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_BALANCE' => $lang['Control_panel_grant_balance'],
			'L_CONTACT' => $lang['Control_panel_grant_contact'],
			'L_REGION' => $lang['Control_panel_region'],
			'L_EDIT_GRANT_BUTTON' => $lang['Control_panel_edit_grant'],
			'GRANT_ID' => $grantid,
			'GRANT_MONTH_START_LIST' => $month_start_list,
			'GRANT_DAY_START_LIST' => $day_start_list,
			'GRANT_YEAR_START_LIST' => $year_start_list,
			'GRANT_MONTH_END_LIST' => $month_end_list,
			'GRANT_DAY_END_LIST' => $day_end_list,
			'GRANT_YEAR_END_LIST' => $year_end_list,
			'GRANT_RESTRICTED' => $type_list,
			'GRANT_REGION_LIST' => $grant_region_list,
			'GRANT_BALANCE' => $grant_balance,
			'GRANT_AMOUNT' => $grant['grant_amount'],
			'GRANT_PURPOSE_LIST' => $grant_purpose_list,
			'GRANT_NOTES' => $grant['grant_notes'],
			'GRANT_INVOICE_CHECKED' => $grant_invoice_checked,
			'GRANT_CONTACT' => $grant['grant_contact'],
			'GRANT_ORGANIZATION' => $grant['grant_organization'],
			'GRANT_ADDRESS' => $grant['grant_address'],
			'GRANT_CITY' => $grant['grant_city'],
			'GRANT_STATE' => $grant['grant_state'],
			'GRANT_PHONE_NUMBER' => $grant['grant_phone_number'],
			'GRANT_ZIP_CODE' => $grant['grant_zip_code']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'duplicategrant' ) {
		$pagination[] = array(NULL, $lang['Duplicate_grant']);
		$content_subtitle = make_title($lang['Title_duplicate_grant'], true);

		if ( !is_numeric($grantid) || $grantid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		if ( !( check_grant_permission($grantid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}
		
		duplicate_grant($grantid);
		
		$content = make_content($lang['Control_panel_grant_duplicated']);
	} else {
		$content_subtitle = make_title($lang['Control_panel_add_grant'], true);
	
		$r_ids = array();
		$r_names = array();
		get_regions($r_ids, $r_names, false);
	
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);

		$month_start_list = make_drop_down('grant_start_month', $m_keys, $m_values, NULL, NULL, 'id="start_month"');
		$day_start_list = make_drop_down('grant_start_day', make_day_array(), make_day_array(), NULL, NULL, 'id="start_day"');
		$year_start_list = make_drop_down('grant_start_year', make_year_array(), make_year_array(), NULL, NULL, 'id="start_year"');
			
		$month_end_list = make_drop_down('grant_end_month', $m_keys, $m_values, date('n', CCCSTIME), NULL, 'id="end_month"');
		$day_end_list = make_drop_down('grant_end_day', make_day_array(), make_day_array(), date('j', CCCSTIME), NULL, 'id="end_day"');
		$year_end_list = make_drop_down('grant_end_year', make_year_array(), make_year_array(), date('Y', CCCSTIME), NULL, 'id="end_year"');
	
		$type_list = make_drop_down('grant_type', array(0, 1), $lang['Control_panel_grant_types']);
		
		$grant_purpose_list = make_drop_down('grant_purpose', $lang['Control_panel_grant_purposes'], $lang['Control_panel_grant_purposes']);
		
		// Handle all of the sorting
		$incoming = collect_vars($_GET, array('sort' => MIXED, 'sort_field' => MIXED));
		extract($incoming);
		
		if ( $sort != 'asc' && $sort != 'desc' ) { $sort = 'asc'; }
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'g.grant_region_id ASC';
		} else {
			if ( $sort_field == 'id' ) {
				$sort = 'g.grant_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'region' ) {
				$sort = 'g.grant_region_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'organization' ) {
				$sort = 'g.grant_organization ' . strtoupper($sort);
			}
		}
		
		$grant_list = NULL;
		$use_default = true;
		if ( !empty($regionid) ) {
			if ( $regionid == 'viewall' && $usercache['user_type'] == ADMINISTRATOR ) {
				$sql = "SELECT * FROM `" . GRANT . "` g ORDER BY " . $sort;
				$use_default = false;
			} elseif ( is_numeric($regionid) ) {
				$regionid = intval($regionid);
				
				if ( $usercache['user_type'] == ADMINISTRATOR || ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $regionid ) ) {
					$sql = "SELECT * FROM `" . GRANT . "` g 
						WHERE g.grant_region_id = '" . $regionid . "' 
							OR g.grant_region_id = '0' ORDER BY " . $sort;
					$use_default = false;
				}
			}
		}
		
		// Prevent default or screwy cases
		if ( $use_default == true ) {
			$sql = "SELECT * FROM `" . GRANT . "` g 
					WHERE g.grant_region_id = '" . $usercache['user_region_id'] . "' 
						OR g.grant_region_id = '0' ORDER BY " . $sort;
		}
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$grant_amount_total = 0;
		$grant_balance_total = 0;
		
		$total_restricted = 0;
		$total_unrestricted = 0;
		$grant_total_restricted = 0;
		$grant_total_unrestricted = 0;
		
		while ( ($grant = $db->getarray($result)) ) {
			$grant_balance = get_grant_balance($grant['grant_id']);
			
			$grant_balance_total += $grant_balance;
			$grant_amount_total += $grant['grant_amount'];
			
			if ( $grant['grant_region_id'] == 0 ) {
				$grant_region = $lang['Global'];
			} else {
				$grant_region = get_region_name($grant['grant_region_id']);
			}
			
			if ( $grant['grant_type'] == 0 ) {
				$total_restricted++;
				$grant_total_restricted += $grant['grant_amount'];
			} else {
				$total_unrestricted++;
				$grant_total_unrestricted += $grant['grant_amount'];
			}
			
			$t->set_template( load_template('control_panel_add_grant_form_item') );
			$t->set_vars( array(
				'GRANT_ID' => $grant['grant_id'],
				'GRANT_ORGANIZATION' => $grant['grant_organization'],
				'GRANT_TYPE' => substr($lang['Control_panel_grant_types'][ $grant['grant_type'] ], 0, 1),
				'GRANT_PURPOSE' => $grant['grant_purpose'],
				'GRANT_AMOUNT' => number_format($grant['grant_amount']),
				'GRANT_BALANCE' => number_format( $grant_balance ),
				'GRANT_CONTACT' => $grant['grant_contact'],
				'GRANT_REGION' => $grant_region
				)
			);
			$grant_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$db->freeresult($result);
		
		/**
		 * Explanation of this little nugget: it counts the funds from
		 * the beginning of an entire year. For example, if a grant starts
		 * sometime in 2004 and ends in 2005, the funds are counted as part
		 * of the 2004 year. However, if the grant starts in 2005 and ends in
		 * 2005 as well, the funds are counted as part of the 2005 year. 
		 * One small caveat: when an RD is viewing this page, the global 
		 * grants are not included in the subtotals because of a happening
		 * with the SQL statement and the OR g.grant_region_id = '0'. However,
		 * when an Admin is viewing the page, the global grants are included.
		*/
		
		$this_year = (date('Y', CCCSTIME)+1);
		$years = make_year_array();
		
		for ( $i=0; $i<count($years); $i++ ) {
			$year_start = mktime(0, 0, 0, 1, 1, $years[$i]);
			$year_end = mktime(0, 0, 0, 1, 1, $years[$i+1]);
			
			// Don't even bother to include years that we haven't gotten to yet
			// as grants aren't recieved until the year starts. This will cut down on
			// a few SQL queries
			if ( $years[$i] <= $this_year ) {
				// Give the user the appropriate SQL statement
				if ( $usercache['user_type'] == ADMINISTRATOR ) {
					$sql = "SELECT SUM(g.grant_amount) AS grant_subtotal FROM `" . GRANT . "` g 
							WHERE g.grant_start_date >= '" . $year_start . "'
							AND g.grant_start_date < '" . $year_end . "'";
				} else {
					$sql = "SELECT SUM(g.grant_amount) AS grant_subtotal FROM `" . GRANT . "` g 
							WHERE g.grant_start_date >= '" . $year_start . "' AND g.grant_start_date < '" . $year_end . "'
								AND g.grant_region_id = '" . $usercache['user_region_id'] . "'";
				}
				
				$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
				
				$grant = $db->getarray($result);
				$grant_subtotal = $grant['grant_subtotal'];
				
				if ( $grant_subtotal > 0 ) {
					$t->set_template( load_template('control_panel_add_grant_form_subtotal_item') );
					$t->set_vars( array(
						'GRANT_YEAR' => $years[$i],
						'GRANT_SUBTOTAL_AMOUNT' => number_format($grant_subtotal)
						)
					);
					
					$grant_subtotal_list .= $t->parse($dbconfig['show_template_name']);
				}
			}
			$grant_subtotal = 0;
		}
		
		$db->freeresult($result);
		
		if ( $usercache['user_type'] == ADMINISTRATOR ) {
			array_unshift($r_ids, 'viewall');
			array_unshift($r_names, $lang['Control_panel_view_all_grants']);
			$grant_region_list = make_drop_down('regionid', $r_ids, $r_names);
		
			$t->set_template( load_template('control_panel_view_grant_form') );
			$t->set_vars( array(
				'L_VIEW_GRANT_BY_REGION' => $lang['Control_panel_view_grant_by_region'],
				'L_VIEW_GRANTS' => $lang['Control_panel_view_grants'],
				'REGION_LIST' => $grant_region_list
				)
			);
			$view_grant_form = $t->parse($dbconfig['show_template_name']);
			
			// Get rid of the view all grants option
			array_shift($r_ids);
			array_shift($r_names);
		}
	
		array_push($r_ids, 0);
		array_push($r_names, $lang['Global']);
		$grant_region_list = make_drop_down('grant_region_id', $r_ids, $r_names, $usercache['user_region_id']);
		
		$regionid = ( empty($regionid) ? 0 : $regionid );
		
		$t->set_template( load_template('control_panel_add_grant_form', false) );
		$t->set_vars( array(
			'L_ADD_GRANT' => $lang['Control_panel_add_grant'],
			'L_DATE' => $lang['Control_panel_date'],
			'L_START_DATE' => $lang['Control_panel_start_date'],
			'L_END_DATE' => $lang['Control_panel_end_date'],
			'L_GRANT_REGION' => $lang['Control_panel_region'],
			'L_GRANT_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_RESTRICTED' => $lang['Control_panel_restricted'],
			'L_PURPOSE' => $lang['Control_panel_purpose'],
			'L_NOTES' => $lang['Control_panel_notes'],
			'L_INVOICE' => $lang['Control_panel_invoice'],
			'L_CONTACT_INFORMATION' => $lang['Control_panel_contact_information'],
			'L_CONTACT_PERSON' => $lang['Control_panel_contact_person'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_ADDRESS' => $lang['Control_panel_address'],
			'L_CITY' => $lang['Control_panel_city'],
			'L_STATE' => $lang['Control_panel_state'],
			'L_ZIP_CODE' => $lang['Control_panel_zip_code'],
			'L_PHONE_NUMBER' => $lang['Control_panel_phone_number'],
			'L_GRANT_SUBTOTAL_CAVEAT' => $lang['Control_panel_grant_subtotal_caveat'],
			'L_GRANT_SUBTEXT' => $lang['Control_panel_grant_subtext'],
			'L_GRANT_SUBTOTALS' => $lang['Control_panel_grant_subtotals'],					
			'L_YEAR' => $lang['Control_panel_year'],
			'L_ADD_GRANT_BUTTON' => $lang['Control_panel_add_grant_button'],
			'L_EXISTING_GRANTS' => $lang['Control_panel_existing_grants'],
			'L_ID' => $lang['Control_panel_id'],
			'L_ORGANIZATION' => $lang['Control_panel_organization'],
			'L_TYPE' => $lang['Control_panel_grant_type'],
			'L_AMOUNT' => $lang['Control_panel_grant_amount'],
			'L_BALANCE' => $lang['Control_panel_grant_balance'],
			'L_CONTACT' => $lang['Control_panel_grant_contact'],
			'L_REGION' => $lang['Control_panel_region'],
			'L_TOTAL' => $lang['Report_total'],
			'L_TOTAL_RESTRICTED' => $lang['Control_panel_total_restricted'],
			'L_TOTAL_UNRESTRICTED' => $lang['Control_panel_total_unrestricted'],
			'GRANT_PURPOSE_LIST' => $grant_purpose_list,
			'GRANT_MONTH_START_LIST' => $month_start_list,
			'GRANT_DAY_START_LIST' => $day_start_list,
			'GRANT_YEAR_START_LIST' => $year_start_list,
			'GRANT_MONTH_END_LIST' => $month_end_list,
			'GRANT_DAY_END_LIST' => $day_end_list,
			'GRANT_YEAR_END_LIST' => $year_end_list,
			'GRANT_RESTRICTED' => $type_list,
			'GRANT_REGION_LIST' => $grant_region_list,
			'GRANT_AMOUNT_TOTAL' => number_format($grant_amount_total),
			'GRANT_BALANCE_TOTAL' => number_format($grant_balance_total),
			'GRANT_TOTAL_RESTRICTED' => number_format($grant_total_restricted),
			'GRANT_TOTAL_UNRESTRICTED' => number_format($grant_total_unrestricted),
			'TOTAL_RESTRICTED' => $total_restricted,
			'TOTAL_UNRESTRICTED' => $total_unrestricted,
			'VIEW_GRANT_FORM' => $view_grant_form,
			'GRANT_LIST' => $grant_list,
			'GRANT_SUBTOTAL_LIST' => $grant_subtotal_list,
			'REGION_ID' => $regionid
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	// Anytime we're coming to the POST part of this
	// page, its either to add a grant or update one,
	// and thus, there's no reason to duplicate
	// all of this code on each of those sections.
	$incoming = collect_vars($_POST, array('grant_start_month' => INT, 'grant_start_day' => INT, 'grant_start_year' => INT, 'grant_end_month' => INT, 'grant_end_day' => INT, 'grant_end_year' => INT, 'grant_amount' => MIXED, 'grant_region_id' => INT, 'grant_purpose' => MIXED, 'grant_notes' => MIXED, 'grant_invoice' => INT, 'grant_contact' => MIXED, 'grant_organization' => MIXED, 'grant_address' => MIXED, 'grant_city' => MIXED, 'grant_state' => MIXED, 'grant_zip_code' => MIXED, 'grant_phone_number' => MIXED, 'grant_type' => INT));
	extract($incoming);
	
	$grant_start = mktime(0, 0, 0, $grant_start_month, $grant_start_day, $grant_start_year);
	$grant_end = mktime(0, 0, 0, $grant_end_month, $grant_end_day, $grant_end_year);
		
	$grant_amount = str_replace(",", "", $grant_amount);
	$grant_amount = str_replace("$", "", $grant_amount);
	
	if ( !validate_phone_number($grant_phone_number) ) {
		cccs_message(WARNING_MESSAGE, $lang['Error_bad_phone_number']);
	}
		
	if ( $do == 'addgrant' ) {
		$pagination[] = array(NULL, $lang['Add_grant']);
		$content_subtitle = make_title($lang['Control_panel_add_grant'], true);

		if ( $usercache['user_type'] == ADMINISTRATOR || ($usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $grant_region_id ) ) {
			$sql = "INSERT INTO `" . GRANT . "`
					VALUES ( '', '" . $grant_start . "', '" . $grant_end . "', '" . $grant_amount . "',
							'" . $grant_region_id . "', '" . $grant_type . "', 'recieved',
							'" . $grant_purpose . "',
							'" . $grant_notes . "', '" . $grant_contact . "', '" . $grant_organization . "', 
							'" . $grant_address . "', '" . $grant_city . "', '" . $grant_state . "', 
							'" . $grant_zip_code . "', '" . $grant_phone_number . "', '" . $grant_invoice . "')";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			$content = make_content($lang['Control_panel_add_grant_thank_you']);
		} else {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}
	} elseif ( $do == 'editgrant' ) {
		$pagination[] = array(NULL, $lang['Edit_grant']);
		$content_subtitle = make_title($lang['Control_panel_add_grant'], true);
		
		if ( !( check_grant_permission($grantid) ) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_cant_view_grant']);
		}
		
		$sql = "UPDATE `" . GRANT . "` SET 
					grant_start_date = '" . $grant_start . "', grant_end_date = '" . $grant_end . "',
					grant_amount = '" . $grant_amount . "', grant_region_id = '" . $grant_region_id . "',
					grant_type = '" . $grant_type . "', grant_purpose = '" . $grant_purpose . "', grant_notes = '" . $grant_notes . "',
					grant_contact = '" . $grant_contact . "', grant_organization = '" . $grant_organization . "',
					grant_address = '" . $grant_address . "', grant_city = '" . $grant_city . "',
					grant_state = '" . $grant_state . "', grant_zip_code = '" . $grant_zip_code . "',
					grant_phone_number = '" . $grant_phone_number . "', grant_invoice = '" . $grant_invoice . "'
				WHERE grant_id = '" . $grantid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Control_panel_grant_edited']);
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