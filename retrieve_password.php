<?php

/**
 * retrieve_password.php
 * Allows the user to get a new password if they forget theirs. This
 * creates a random password for the user and then emails it to them.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_retrieve_password'], false);
$content_subtitle = NULL;

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Retrieve_password']);
$content_pagination = make_pagination($pagination);

$incoming = collect_vars($_POST, array('do' => MIXED));
extract($incoming);

// If you're logged in, chances are, you remember your password already
if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	cccs_message(WARNING_MESSAGE, $lang['Error_user_logged_in']);
}

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$t->set_template( load_template('retrieve_password_form') );
	$t->set_vars( array(
		'L_RETRIEVE_PASSWORD' => $lang['Retrieve_password'],
		'L_EMAIL_ADDRESS' => $lang['Email_address'],
		'L_CREATE_NEW_PASSWORD' => $lang['Create_new_password']
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'createpassword' ) {
		// Create a new password for the user
		$password = generate_password(8, true, NULL);
		
		$incoming = collect_vars($_POST, array('user_email' => MIXED));
		extract($incoming);
		
		$sql = "SELECT * FROM `" . USER . "` u 
				WHERE u.user_email = '" . $user_email . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$user = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
		
		$sql = "UPDATE `" . USER . "` SET 
					user_password = '" . md5($password) . "' 
				WHERE user_id = '" . $user['user_id'] . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
		// Email the user with their new password
		if ( $dbconfig['send_email'] == 1 ) {
			send_email($user['user_email'], $lang['Email_new_password_subject'], sprintf($lang['Email_new_password_message'], $password, $user['user_name']) );
		}
		
		$content = make_content($lang['Retrieve_password_text']);
	}
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