<?php

/**
 * message.php
 * Internal messaging center forefront.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_personal_message'], false);
	
// See if they are an applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

// Pagination changes for each page, so update the $pagination array here
$pagination[] = array('usercp.php', $lang['Control_panel']);	
$pagination[] = array('message.php', $lang['Title_personal_message']);

$incoming = collect_vars($_REQUEST, array('do' => MIXED, 'messageid' => INT));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'viewmessage' ) {
		$content_subtitle = make_title($lang['Control_panel_view_message'], true);
		
		$pagination[] = array(NULL, $lang['Control_panel_view_message']);
		
		if ( $messageid <= 0 || !is_numeric($messageid) ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$sql = "SELECT * FROM `" . MESSAGE . "` m
				LEFT JOIN `" . MESSAGE_TEXT . "` mt
					ON m.message_id = mt.text_message_id
				WHERE m.message_id = '" . $messageid . "' AND mt.text_content IS NOT NULL";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		
		$m = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
		
		if ( $m['message_to_user_id'] == $usercache['user_id'] || $m['message_from_user_id'] == $usercache['user_id'] ) {
			$sql = "UPDATE `" . MESSAGE . "` 
					SET message_type = '1' 
					WHERE message_id = '" . $messageid . "'
						AND message_to_user_id = '" . $usercache['user_id'] . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
			
			$message_text = $m['text_content'];
			$message_text = parse_links($message_text);
			$message_text = parse_page($message_text);
			
			// If a message comes from a 0 ID, it means the admin
			if ( $m['message_from_user_id'] == 0 ) {
				$message_from = $lang['Titles'][1];
			} else {
				$message_from = get_user_real_name($m['message_from_user_id']);
			}

			$t->set_template( load_template('control_panel_personal_message') );
			$t->set_vars( array(
				'L_VIEW_MESSAGE' => $lang['Control_panel_view_message'],
				'L_FROM' => $lang['Control_panel_from'],
				'L_SUBJECT' => $lang['Control_panel_subject'],
				'L_MESSAGE' => $lang['Message'],
				'L_REPLY' => $lang['Control_panel_reply_message'],
				'MESSAGE_FROM' => $message_from,
				'MESSAGE_SUBJECT' => stripslashes($m['message_subject']),
				'MESSAGE_TEXT' => $message_text,
				'MESSAGE_ID' => $m['message_id']
				)
			);
			$content = $t->parse($dbconfig['show_template_name']);
		}
		
		unset($message_text, $m);
	} else {
		// Show the user their message center
		$sql = "SELECT * FROM `" . MESSAGE . "` m
				LEFT JOIN `" . MESSAGE_TEXT . "` mt
					ON m.message_id = mt.text_message_id
				WHERE mt.text_content IS NOT NULL
					AND mt.text_type = '0'
					AND m.message_to_user_id = '" . $usercache['user_id'] . "' 
				ORDER BY m.message_date DESC";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		
		// First get all message in their Inbox
		while ( $m = $db->getarray($result) ) {
			$message_subject = stripslashes($m['message_subject']);
			if ( $m['message_type'] == 0 ) {
				$message_new = $dbconfig['new_message_color'];
			} else {
				$message_new = NULL;
			}
			
			// If a message comes from a 0 ID, it means the admin
			if ( $m['message_from_user_id'] == 0 ) {
				$message_from = $lang['Titles'][1];
			} else {
				$message_from = get_user_real_name($m['message_from_user_id']);
			}
			
			$t->set_template( load_template('control_panel_personal_message_list_item') );
			$t->set_vars( array(
				'MESSAGE_NEW' => $message_new,
				'MESSAGE_FROM' => $message_from,
				'MESSAGE_ID' => $m['message_id'],
				'TEXT_ID' => $m['text_id'],
				'MESSAGE_SUBJECT' => $message_subject,
				'MESSAGE_DATE' => date($dbconfig['date_format'], $m['message_date'])
				)
			);
			$message_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('control_panel_personal_message_list') );
		$t->set_vars( array(
			'L_PERSONAL_MESSAGES' => $lang['Control_panel_personal_messages'],
			'L_FROM' => $lang['Control_panel_from'],
			'L_SUBJECT' => $lang['Control_panel_subject'],
			'L_DATE' => $lang['Date'],
			'L_DELETE' => $lang['Delete'],
			'L_TOGGLE_CHECKBOXES' => $lang['Control_panel_toggle_checkboxes'],
			'L_DELETE_MESSAGES' => $lang['Control_panel_delete_messages'],
			'L_NEW_MESSAGE' => $lang['Control_panel_new_message'],
			'MESSAGE_LIST' => $message_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		$content .= '<br />';
		
		$message_list = NULL;
		
		// And now get all of the messages that they
		// have sent.
		$sql = "SELECT * FROM `" . MESSAGE . "` m
				LEFT JOIN `" . MESSAGE_TEXT . "` mt
					ON m.message_id = mt.text_message_id
				WHERE mt.text_content IS NOT NULL
					AND mt.text_type = '1'
					AND m.message_from_user_id = '" . $usercache['user_id'] . "' 
				ORDER BY m.message_date DESC";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		
		while ( $m = $db->getarray($result) ) {
			$message_subject = stripslashes($m['message_subject']);
			
			if ( $m['message_to_user_id'] == 0 ) {
				$message_to = $lang['Titles'][1];
			} else {
				$message_to = get_user_real_name($m['message_to_user_id']);
			}
			
			$t->set_template( load_template('control_panel_personal_message_list_sent_item') );
			$t->set_vars( array(
				'MESSAGE_TO' => $message_to,
				'MESSAGE_ID' => $m['message_id'],
				'TEXT_ID' => $m['text_id'],
				'MESSAGE_SUBJECT' => $message_subject,
				'MESSAGE_DATE' => date($dbconfig['date_format'], $m['message_date'])
				)
			);
			$message_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('control_panel_personal_message_list_sent') );
		$t->set_vars( array(
			'L_PERSONAL_MESSAGES' => $lang['Control_panel_sent_messages'],
			'L_TO' => $lang['To'],
			'L_SUBJECT' => $lang['Control_panel_subject'],
			'L_DATE' => $lang['Date'],
			'MESSAGE_LIST' => $message_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'deletemessages' ) {
		// ********************************
		// GIANT RED FLAG!
		// ENSURE WHEN DELETING A MESSAGE, IT ACTUALLY
		// BELONGS TO THE USER DELETING IT
		// ********************************
		$content_subtitle = make_title($lang['Control_panel_delete_messages'], true);
		
		$pagination[] = array(NULL, $lang['Control_panel_delete_messages']);
		
		for ( $i=0; $i<count($_POST['textid']); $i++ ) {
			$textid = intval( trim($_POST['textid'][$i]) );
		
			$sql = "DELETE FROM `" . MESSAGE_TEXT . "` 
					WHERE text_id = '" . $textid . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		}
		
		$content = make_content($lang['Control_panel_messages_deleted']);
	} elseif ( $do == 'newmessage' ) {
		$content_subtitle = make_title($lang['Control_panel_new_message'], true);
		$pagination[] = array(NULL, $lang['Control_panel_new_message']);
		
		// List of users to send messages to
		$users = get_user_list();
		
		$ids = array();
		$names = array();
		
		array_unshift($ids, NULL);
		array_unshift($names, NULL);
		
		for ( $i=0; $i<count($users); $i++ ) {
			$ids[] = $users[$i]['user_id'];
			$names[] = ucfirst( strtolower($users[$i]['user_first_name']) ) . ' ' . ucfirst( strtolower($users[$i]['user_last_name']) );
		}
		
		$message_user_list = make_drop_down('message_to_user_id', $ids, $names, NULL, NULL, 'id="volunteer_list"');
		
		unset($users, $ids, $names);

		$ids = array();
		$names = array();
		
		array_unshift($ids, NULL);
		array_unshift($names, NULL);
		
		get_regions($ids, $names);

		$message_region_list = make_drop_down('message_region_id', $ids, $names, NULL, NULL, 'id="regions"');
	
		$t->set_template( load_template('control_panel_personal_message_form') );
		$t->set_vars( array(
			'L_SEND_A_MESSAGE' => $lang['Control_panel_send_a_message'],
			'L_TO' => $lang['To'],
			'L_SINGLE_VOLUNTEER' => $lang['Control_panel_single_volunteer'],
			'L_ALL_ADMINISTRATORS' => $lang['Control_panel_all_administrators'],
			'L_VOLUNTEERS_IN_A_REGION' => $lang['Control_panel_volunteers_in_region'],
			'L_ALL_REGIONAL_DIRECTORS' => $lang['Control_panel_all_regional_directors'],
			'L_ALL_VOLUNTEERS' => $lang['Control_panel_all_volunteers'],
			'L_SUBJECT' => $lang['Subject'],
			'L_MESSAGE' => $lang['Message'],
			'L_SEND_MESSAGE' => $lang['Control_panel_send_message'],
			'MESSAGE_FROM_USER_ID' => $usercache['user_id'],
			'MESSAGE_TO_LIST' => $message_user_list,
			'MESSAGE_REGION_LIST' => $message_region_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($message_region_list, $ids, $names, $message_user_list);
	} elseif ( $do == 'reply' ) {
		$content_subtitle = make_title($lang['Control_panel_reply_message'], true);
		$pagination[] = array(NULL, $lang['Control_panel_reply_message']);
	
		if ( !is_numeric($messageid) || $messageid <= 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
		}
		
		$sql = "SELECT * FROM `" . MESSAGE . "` m
				LEFT JOIN `" . MESSAGE_TEXT . "` mt
					ON m.message_id = mt.text_message_id
				WHERE m.message_id = '" . $messageid . "'";
		
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
		
		$m = $db->getarray($result);
		
		if ( $m['message_from_user_id'] == 0 ) {
			$message_to_name = $lang['Titles'][1];
		} else {
			$message_to_name = get_user_real_name($m['message_from_user_id']);
		}
		
		$message_text = preg_replace("/^(.)/m", "&gt; \\0", $m['text_content']);
		$message_text = $lang['Control_panel_original_message'] . sprintf($lang['Control_panel_user_wrote'], $message_from_name) . "\n" . $message_text;
		
		$t->set_template( load_template('control_panel_personal_message_reply_form') );
		$t->set_vars( array(
			'L_REPLY_MESSAGE' => $lang['Control_panel_reply_message'],
			'L_TO' => $lang['To'],
			'L_FROM' => $lang['Control_panel_from'],
			'L_SUBJECT' => $lang['Subject'],
			'L_MESSAGE' => $lang['Message'],
			'L_REPLY' => $lang['Control_panel_reply_message'],
			'MESSAGE_TO' => $message_to_name,
			'MESSAGE_SUBJECT' => stripslashes($m['message_subject']),
			'MESSAGE_TEXT' => $message_text,
			'MESSAGE_ID' => $message_id,
			'MESSAGE_FROM_USER_ID' => $m['message_to_user_id'],
			'MESSAGE_TO_USER_ID' => $m['message_from_user_id']
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'sendmessage' ) {
		$content_subtitle = make_title($lang['Control_panel_message_sent'], true);
		$pagination[] = array(NULL, $lang['Control_panel_message_sent']);
		
		$incoming = collect_vars($_POST, array('message_from_user_id' => INT, 'message_to_user_id' => INT, 'message_region_id' => INT, 'message_to_administrator' => MIXED, 'message_to_regional_director' => MIXED, 'message_to_volunteer' => MIXED, 'message_subject' => MIXED, 'message_text' => MIXED));
		extract($incoming);
		
		// See if we're sending a message to RD's
		if ( $message_to_regional_director == 'regional_directors' ) {
			$sql = "SELECT u.user_id FROM `" . USER . "` u 
					WHERE u.user_type = '" . REGIONAL_DIRECTOR . "' 
						AND u.user_authorized = '1'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
			
			while ( $u = $db->getarray($result) ) {
				send_message($message_from_user_id, $u['user_id'], CCCSTIME, $message_subject, $message_text) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
			}
			
			$db->freeresult($result);
		}
		
		// We wanna send a message to all volunteers
		if ( $message_to_volunteer == 'volunteers' ) {
			$sql = "SELECT u.user_id FROM `" . USER . "` u 
					WHERE u.user_type = '" . VOLUNTEER . "' OR u.user_type = '" . VOLUNTEER_STAFF . "' 
						AND u.user_authorized = '1'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
			
			while ( $u = $db->getarray($result) ) {
				send_message($message_from_user_id, $u['user_id'], CCCSTIME, $message_subject, $message_text) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
			}
			
			$db->freeresult($result);
		}
		
		// And all Admins.
		if ( $message_to_administrator == 'administrators' ) {
			$sql = "SELECT u.user_id FROM `" . USER . "` u 
					WHERE u.user_type = '" . ADMINISTRATOR . "'
						AND u.user_authorized = '1'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
			
			while ( $u = $db->getarray($result) ) {
				send_message($message_from_user_id, $u['user_id'], CCCSTIME, $message_subject, $message_text) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
			}
			
			$db->freeresult($result);
		}

		// And all members of a region, this should be NULL
		// if $message_to_volunteer == 'volunteers'
		if ( is_numeric($message_region_id) ) {
			$sql = "SELECT u.user_id FROM `" . USER . "` u 
					WHERE u.user_region_id = '" . $message_region_id . "' 
						AND u.user_type = '" . VOLUNTEER . "'
						AND u.user_authorized = '1'";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dbquery(), $sql);
			
			while ( $u = $db->getarray($result) ) {
				if ( $message_to_user_id != $u['user_id'] ) {
					send_message($message_from_user_id, $u['user_id'], CCCSTIME, $message_subject, $message_text) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
				}
			}
			
			$db->freeresult($result);
		}
		
		// And finally send a message to a specific user.
		if ( is_numeric($message_to_user_id) && $message_to_user_id > 0 ) {
			send_message($message_from_user_id, $message_to_user_id, CCCSTIME, $message_subject, $message_text) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message'], __LINE__, __FILE__);
		}
		
		$content = make_content($lang['Control_panel_message_sent_thank_you']);
	} elseif ( $do == 'replymessage' ) {
		$content_subtitle = make_title($lang['Control_panel_message_sent'], true);
		$pagination[] = array(NULL, $lang['Control_panel_message_sent']);
		
		$incoming = collect_vars($_POST, array('message_subject' => MIXED, 'message_text' => MIXED, 'message_from_user_id' => INT, 'message_to_user_id' => INT));
		extract($incoming);
		
		if ( $message_to_user_id == 0 ) {
			cccs_message(WARNING_CRITICAL, $lang['Error_no_message_administrator']);
		}

		send_message($message_from_user_id, $message_to_user_id, CCCSTIME, $message_subject, $message_text, $messageid) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_message']);
		
		$content = make_content($lang['Control_panel_message_sent_thank_you']);
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