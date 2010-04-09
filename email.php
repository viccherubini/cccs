<?php

/**
 * email.php
 * Allows one logged in user to send email to another user.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_send_email'], false);

// See if they are an applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

$incoming = collect_vars($_POST, array('do' => MIXED));
extract($incoming);

// They are sending an email
if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$pagination[] = array(NULL, $lang['Send_email']);
	
	$incoming = collect_vars($_GET, array('userid' => INT));
	extract($incoming);
	
	$to_user_id = $userid;
	
	// Good ID's check
	if ( !is_numeric($to_user_id) || $to_user_id <= 0 ) {
		cccs_message(WARNING_CRITICAL, $lang['Error_no_id']);
	}
	
	$to_user = array();
	$to_user = get_user_data($to_user_id);
	
	if ( empty($usercache['user_id']) ) {
		$from_user_id = 0;
	} else {
		$from_user_id = $usercache['user_id'];
	}
	
	$t->set_template( load_template('email_form') );
	$t->set_vars( array(
		'L_SEND_EMAIL' => $lang['Send_email'],
		'L_TO' => $lang['Email_to'],
		'L_SUBJECT' => $lang['Email_subject'],
		'L_MESSAGE' => $lang['Email_message'],
		'EMAIL_TO_USER_ID' => $to_user_id,
		'EMAIL_FROM_USER_ID' => $from_user_id,
		'EMAIL_TO_USER_NAME' => $to_user['user_first_name'] . ' ' . $to_user['user_last_name'] . ' ( ' . $to_user['user_name'] . ' )'
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'sendemail' ) {
		$incoming = collect_vars($_POST, array('email_to_user_id' => INT, 'email_from_user_id' => INT, 'email_subject' => MIXED, 'email_message' => MIXED));
		extract($incoming);
		
		// Update the pagination
		$pagination[] = array('email.php?userid=' . $email_to_user_id, $lang['Send_email']);
		$pagination[] = array(NULL, $lang['Send_email_sent']);
			
		// First, keep a track of the email in the database.
		$sql = "INSERT INTO `" . EMAIL . "` 
				VALUES( '', '" . $email_from_user_id . "', '" . $email_to_user_id . "', 
						'" . $email_subject . "', '" . $email_message . "', '" . CCCSTIME . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Now send the email
		$to_user = array();
		$from_user = array();
		$to_user = get_user_data($email_to_user_id);
		$from_user = get_user_data($email_from_user_id);		// Should match $_SESSION['user_id']
		
		$actual_message = NULL;
		$actual_message = sprintf($lang['Email_from_form_prefix'], $from_user['user_first_name'] . ' ' . $from_user['user_last_name']);
		$actual_message .= $email_message;
	
		if ( $dbconfig['send_email'] == 1 ) {
			send_email($to_user['user_email'], $email_subject, $actual_message, 'From: ' . $from_user['user_email']);
		}
		
		// Tell the user thank you for sending an email
		$content = make_content($lang['Email_sent_thank_you']);
	}
}

unset($actual_message, $to_user, $from_user, $email_subject);

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