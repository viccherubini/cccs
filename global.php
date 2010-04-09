<?php

/**
 * global.php
 * This file is included on each page and contains all common functions 
 * and initalizations to make the site work.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

session_start();

define('CCCSTIME', time(), false);

//header("Expires: Mon, 25 Aug 1984 01:20:00 GMT");
//header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

error_reporting(E_PARSE | E_ERROR);
set_magic_quotes_runtime(0);

// Idea taken from phpBB
if ( !get_magic_quotes_gpc() ) {
	if ( is_array($_GET) ) {
		while ( list($k, $v) = each($_GET) ) {
			$_GET[$k] = addslashes( htmlspecialchars($v) );
		}
		@reset($_GET);
	}
	
	if ( is_array($_POST) ) {
		while ( list($k, $v) = each($_POST) ) {
			if ( is_array($_POST[$k]) ) {
				while ( list($k2, $v2) = each($_POST[$k]) ) {
					if ( is_array($_POST[$k][$k2]) ) {
						while ( list($k3, $v3) = each($_POST[$k][$k2]) ) {
							$_POST[$k][$k2][$k3] = addslashes( htmlspecialchars($v3) );
						}
						@reset($_POST[$k][$k2]);
					} else {
						$_POST[$k][$k2] = addslashes( htmlspecialchars($v2) );
					}
				}
				@reset($_POST[$k]);
			} else {
				$_POST[$k] = addslashes( htmlspecialchars($v) );
			}
		}
		@reset($_POST);
	}
}

include $root_path . 'config.php';

include $root_path . 'includes/db.php';
include $root_path . 'includes/template.php';

include $root_path . 'includes/constants.php';
include $root_path . 'includes/functions.php';
include $root_path . 'includes/functions_calendar.php';
include $root_path . 'includes/functions_user.php';
include $root_path . 'includes/functions_report.php';
include $root_path . 'includes/functions_grant.php';

// Connect to the database
$db = new db($db_hostname, $db_database, $db_username, $db_password);
if ( ($db->connect()) < 0 ) {
	cccs_message(ERROR_CRITICAL, $lang['Error_connection'], __LINE__, __FILE__, $db->dberror(), NULL);
}

// Start the template engine
$t = new template();

// common arrays
$dbconfig = array();
$pagination = array();
$usercache = array();
$incoming = array();
$templatecache = array();

if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	$usercache = update_session();
} else {
	$usercache['user_type'] = APPLICANT;
	$_SESSION['user_logged_in'] = false;
}

// Manage the template cache
// Only store the last 25 templates to
// avoid this thing getting too big
if ( count($templatecache) > 25 ) {
	array_shift($templatecache);
}

$sql = "SELECT * FROM `" . CONFIG . "` c 
		ORDER BY c.config_sortorder ASC";
$result = $db->dbquery($sql) or cccs_message(ERROR_CRITICAL, $lang['Error_configuration'], __LINE__, __FILE__, $db->dberror(), $sql);

while ( $config = $db->getarray($result) ) {
	$dbconfig[ $config['config_name'] ] = trim( stripslashes($config['config_value']) );
}

// Include the default language
if ( !empty($dbconfig['default_language']) ) {
	include $root_path . 'language/' . $dbconfig['default_language'] . '.php';
} else {
	include $root_path . 'language/en.php';
}

// Very tight security, prevent cURL attacks and such.
// Only allow POST methods from *this* website. GET has
// to be allowed to come from anywhere of course.
$server_name = $site_url . $site_basedir;
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SERVER['HTTP_REFERER']) ) {
	if ( strpos($_SERVER['HTTP_REFERER'], $server_name) === false ) {
		cccs_message(ERROR_CRITICAL, $lang['Error_default']);
	}
}

// Manage sessions
include $root_path . 'includes/session.php';

// Send out messages
include $root_path . 'includes/messages.php';

// Track the users stats if they are logged in and its turned on
if ( $dbconfig['track_stats'] == true && $_SESSION['user_logged_in'] == true ) {
	track_user_stats();
}

// See if the site is down for repairs or not
if ( trim($dbconfig['site_down']) == 1 ) {
	if ( empty($usercache) || $usercache['user_type'] > ADMINISTRATOR ) {
		cccs_message(ERROR_CRITICAL, $lang['Error_site_down']);
	}
}

?>