<?php

/**
 * functions_grant.php
 * Contains commonly used functions for grants and income items.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

/**
 * Makes an array of grants returned by reference.
 *
 * @param	int		the ID of the region to get the grants from
 * @param	array	the ID's of the grants
 * @param	array	the organizations of the grants
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	boolean	always returns true
*/
function make_grant_list($regionid, &$grant_ids, &$grant_organizations, $include_zero = false) {
	global $db, $lang;
	
	$grant_ids = array();
	$grant_organizations = array();
	
	$sql = "SELECT * FROM `" . GRANT . "` g
			WHERE g.grant_region_id = '" . $regionid . "' 
				OR g.grant_region_id = '0'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$grant_ids[] = NULL;
	$grant_organizations[] = NULL;
	
	while ( $grant = $db->getarray($result) ) {
		$grant_balance = get_grant_balance($grant['grant_id']);
		if ( $grant_balance > 0 || $include_zero == true ) {
			$grant_organizations[] = $grant['grant_organization'] . ' ($' . $grant_balance . ')';
			$grant_ids[] = $grant['grant_id'];
		}
	}
	
	return true;
}

/**
 * Returns the grant organization by grant ID.
 *
 * @param	int		the ID of the grant to get the organization of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	string	the grant organization name
*/
function get_grant_organization($grantid) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . GRANT . "` g
			WHERE g.grant_id = '" . $grantid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$grant = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data'], __LINE__, __FILE__);
	
	return stripslashes($grant['grant_organization']);
}

/**
 * Returns the balance of the grant.
 *
 * @param	int		the grant ID to get the balance of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @param	float	the balance of the grant
*/
function get_grant_balance($grantid) {
	global $db, $lang;
	
	$sql = "SELECT SUM(response_grant_amount) AS grant_sum FROM `" . RESPONSE . "` r
			WHERE r.response_grant_id = '" . $grantid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$sum = $db->getarray($result);
	$db->freeresult($result);

	$sql = "SELECT g.grant_amount FROM `" . GRANT . "` g 
			WHERE g.grant_id = '" . $grantid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$grant = $db->getarray($result);
	$db->freeresult($result);
	
	$grant_sum = $sum['grant_sum'];
	
	if ( empty($grant_sum) ) {
		$grant_sum = 0;
	}
	
	$balance = ($grant['grant_amount'] - $grant_sum);
	
	return $balance;
}

/**
 * Returns the name of a grant by grant ID
 *
 * @param	int		the ID of the grant to get the name of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	string	the name of the grant, not escaped or slash-stripped
*/
function get_grant_name($grant_id) {
	global $db, $lang;
	
	if ( empty($grant_id) || $grant_id <= 0 ) {
		return false;
	}
	
	$sql = "SELECT g.grant_organization FROM `" . GRANT . "` g 
			WHERE g.grant_id = '" . $grant_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( !($grant = $db->getarray($result)) ) { return false; }
	
	$db->freeresult($result);
	
	return $grant['grant_organization'];
}

/**
 * Returns an array of all grant information.
 *
 * @param	int		the ID of the grant to get the data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	array	the grant data array
*/
function get_grant_data($grant_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . GRANT . "` g 
			WHERE g.grant_id = '" . $grant_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$grant = $db->getarray($result);

	$db->freeresult($result);
	
	return $grant;
}

/**
 * Returns true if the current logged in user can view the grant, false otherwise.
 *
 * @param	int		the ID of the grant to validate
 *
 * @global	object	the global database handle
 *
 * @return	boolean	true if user can view grant, false otherwise
*/
function check_grant_permission($grant_id) {
	global $db, $usercache;

	$grant_info = get_grant_data($grant_id);

	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		return true;
	} else {
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $grant_info['grant_region_id'] ) 
			|| ( $grant_info['grant_region_id'] == 0 ) ) {
			return true;
		}
	}

	unset($grant_info);
	
	return false;
}

/**
 * Returns true if the grant has been successfully updated, error out otherwise
 *
 * @param	int		the ID of the grant to duplicate
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	boolean	true if the grant has been successfully duplicated
*/
function duplicate_grant($grant_id) {
	global $db, $lang;

	$sql = "SELECT * FROM `" . GRANT . "` g 
			WHERE g.grant_id = '" . $grant_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	$grant = $db->getarray($result);
	$db->freeresult($result);
	
	while ( list($k, $v) = each($grant) ) {
		$grant[$k] = addslashes( $v );
	}
	
	$sql = "INSERT INTO `" . GRANT . "`
				VALUES ( '', '" . $grant['grant_start_date'] . "', '" . $grant['grant_end_date'] . "', '" . $grant['grant_amount'] . "',
						'" . $grant['grant_region_id'] . "', '" . $grant['grant_type'] . "', '" . $grant['grant_level'] . "',
						'" . $grant['grant_purpose'] . "', '" . $grant['grant_notes'] . "', '" . $grant['grant_contact'] . "', 
						'" . $grant['grant_organization'] . "', '" . $grant['grant_address'] . "', '" . $grant['grant_city'] . "', 
						'" . $grant['grant_state'] . "', '" . $grant['grant_zip_code'] . "', '" . $grant['grant_phone_number'] . "', 
						'" . $grant['grant_invoice'] . "')";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	return true;
}

/**
 * Returns an array of all income information.
 *
 * @param	int		the ID of the income item to get the data of
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	array	the income data array
*/
function get_income_data($income_id) {
	global $db, $lang;
	
	if ( !is_numeric($income_id) || $income_id <= 0 ) {
		return false;
	}
	
	$sql = "SELECT * FROM `" . INCOME . "` i 
			WHERE i.income_id = '" . $income_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
	
	$income = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
	
	$db->freeresult($result);
	
	return $income;
}

/**
 * Returns true if the current logged in user can view the income item, false otherwise.
 *
 * @param	int		the ID of the income to validate
 *
 * @global	object	the global database handle
 *
 * @return	boolean	true if user can view grant, false otherwise
*/
function check_income_permission($income_id) {
	global $db, $usercache;

	$income_info = get_income_data($income_id);

	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		return true;
	} else {
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $income_info['income_region_id'] ) 
			|| ( $income_info['income_region_id'] == 0 ) ) {
			return true;
		}
	}

	unset($income_info);
	
	return false;
}

/**
 * Returns true if the income has been successfully updated, error out otherwise
 *
 * @param	int		the ID of the income item to duplicate
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	boolean	true if the income item has been successfully duplicated
*/
function duplicate_income($income_id) {
	global $db, $lang;
	
	$sql = "SELECT * FROM `" . INCOME . "` i 
			WHERE i.income_id = '" . $income_id . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	$income = $db->getarray($result);
	$db->freeresult($result);
	
	while ( list($k, $v) = each($income) ) {
		$income[$k] = addslashes( $v );
	}
	extract($income);
	
	$sql = "INSERT INTO `" . INCOME . "`
			VALUES ( '', '" . $income_date . "', '" . $income_amount . "', '" . $income_region_id . "',
					'" . $income_notes . "', '" . $income_contact . "',
					'" . $income_organization . "',
					'" . $income_address . "', '" . $income_city . "', '" . strtoupper($income_state) . "', 
					'" . $income_zip_code . "', '" . $income_phone_number . "')";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	return true;
}

?>