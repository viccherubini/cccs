<?php

/**
 * functions.php
 * Contains commonly used functions across the website. See each function 
 * of interest for a more in depth explanation of what it does.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}


function get_newsletter_data($n_type) {
	global $db, $lang;
	
	if ( empty($n_type) ) {
		return false;
	}
	
	$newsletter = array();
	
	// See which type of query we're running
	if ( is_numeric($n_type) && $n_type > 0 ) {
		$sql = "SELECT * FROM `" . NEWSLETTER . "` n 
				WHERE n.newsletter_id = '" . $n_type . "'
					ORDER BY n.newsletter_date DESC";
	} elseif ( $n_type == NEWSLETTER_LATEST ) {
		$sql = "SELECT * FROM `" . NEWSLETTER . "` n 
				ORDER BY n.newsletter_date DESC LIMIT 0,1";
	}

	// We're always fetching only one newsletter at a time,
	// so it doesn't matter what the query is, it'll
	// only fetch one newsletter.
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	// Sometimes no newsletter is available, but
	// we don't want to print a huge error message
	// saying this, so even if this result returns
	// null, let the display_newsletter() function
	// worry about it.
	$newsletter = $db->getarray($result);
	
	return $newsletter;
}


function display_newsletter($n_type) {
	global $t, $usercache, $dbconfig, $lang;
	
	if ( empty($n_type) ) {
		return false;
	}
	
	$edit_actions = NULL;
	
	$n = array();
	$n = get_newsletter_data($n_type);
	
	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		$t->set_template( load_template('newsletter_manage_action_links') );
		$t->set_vars( array(
			'L_ADD_NEWSLETTER' => $lang['Newsletter_add'],
			'L_EDIT_NEWSLETTER' => $lang['Newsletter_edit'],
			'NEWSLETTER_ID' => $n['newsletter_id']
			)
		);
		$edit_actions = $t->parse($dbconfig['show_template_name']);
	}
	
	if ( empty($n) ) {
		$t->set_template( load_template('content') );
		$t->set_vars( array(
			'CONTENT' => $lang['Newsletter_no_newsletter'] . '<br />' . $edit_actions
			)
		);
	} else {
		$nl_id = array();
		$nl = array();
		get_newsletter_list($nl_id, $nl);
		
		$colspan = 1;
		$mmi_in_motion_title = NULL;
		$did_you_know_title = NULL;
		
		$newsletter_list = NULL;
		$newsletter_list = make_drop_down('newsletterid', $nl_id, $nl);
		
		unset($nl_id, $nl);

		reset($n);
		
		$n['newsletter_mmi_in_motion'] = parse_page($n['newsletter_mmi_in_motion']);
		$n['newsletter_did_you_know'] = parse_page($n['newsletter_did_you_know']);
		$n['newsletter_volunteer_voices'] = parse_page($n['newsletter_volunteer_voices']);
		$n['newsletter_spotlight'] = parse_page($n['newsletter_spotlight']);
		
		extract($n);
				
		// This next 4 if statements checks to see if a section is empty or not.
		// If it's empty, no reason to print it out
		if ( !empty($newsletter_mmi_in_motion) ) {
			$colspan = ( empty($newsletter_did_you_know) ? 2 : $colspan );

			$mmi_in_motion_title = make_newsletter_title($lang['Newsletter_mmi_in_motion'], $colspan);
			$newsletter_mmi_in_motion = make_newsletter_section($newsletter_mmi_in_motion, $colspan);
		}
		
		if ( !empty($newsletter_did_you_know) ) {
			$colspan = ( empty($newsletter_mmi_in_motion) ? 2 : $colspan );
				
			$did_you_know_title = make_newsletter_title($lang['Newsletter_did_you_know'], $colspan);
			$newsletter_did_you_know = make_newsletter_section($newsletter_did_you_know, $colspan);
		}
		
		if ( !empty($newsletter_volunteer_voices) ) {
			$t->set_template( load_template('newsletter_volunteer_voices') );
			$t->set_vars( array(
				'L_VOLUNTEER_VOICES' => $lang['Newsletter_volunteer_voices'],
				'NEWSLETTER_VOLUNTEER_VOICES' => $newsletter_volunteer_voices
				)
			);
			$newsletter_volunteer_voices = $t->parse($dbconfig['show_template_name']);
		}
		
		if ( !empty($newsletter_spotlight) ) {
			$t->set_template( load_template('newsletter_spotlight') );
			$t->set_vars( array(
				'L_SPOTLIGHT' => $lang['Newsletter_spotlight'],
				'NEWSLETTER_SPOTLIGHT' => $newsletter_spotlight
				)
			);
			$newsletter_spotlight = $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('newsletter') );
		$t->set_vars( array(
			'L_VOLUME' => $lang['Newsletter_volume'],
			'L_ISSUE' => $lang['Newsletter_issue'],
			'L_TEAMWORK' => $lang['Newsletter_teamwork'],
			'L_FINANCIAL_LITERACY' => $lang['Newsletter_financial_literacy'],
			'L_NEWSLETTER_TEXT' => $lang['Newsletter_text'],
			'L_MORE_NEWSLETTER_TEXT' => $lang['Newsletter_more_text'],
			'L_NEWSLETTER_ARCHIVES' => $lang['Newsletter_archives'],
			'L_PAST_NEWSLETTERS' => $lang['Newsletter_past_newsletters'],
			'L_SEARCH_ARCHIVES' => $lang['Newsletter_search_archives'],
			'L_SEARCH_NEWSLETTER' => $lang['Newsletter_search_newsletter'],
			'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
			'NEWSLETTER_VOLUME' => $n['newsletter_volume'],
			'NEWSLETTER_ISSUE' => $n['newsletter_issue'],
			'MMI_IN_MOTION_TITLE' => $mmi_in_motion_title,
			'DID_YOU_KNOW_TITLE' => $did_you_know_title,
			'NEWSLETTER_MMI_IN_MOTION' => $newsletter_mmi_in_motion,
			'NEWSLETTER_DID_YOU_KNOW' => $newsletter_did_you_know,
			'NEWSLETTER_VOLUNTEER_VOICES' => $newsletter_volunteer_voices,
			'NEWSLETTER_SPOTLIGHT' => $newsletter_spotlight,
			'NEWSLETTER_LIST' => $newsletter_list,
			'EDIT_NEWSLETTER_ADMIN_ACTIONS' => $edit_actions
			)
		);
	}
	
	$content = $t->parse($dbconfig['show_template_name']);
	
	return $content;
}


/**
 * Returns list of newsletters by reference.
 *
 * @param	array	the IDs of the newsletters
 * @param	array	the names of the newsletters
 *
 * @global	object	the global database handle
 * @global	array	the currently loaded language array
 *
 * @return	bool	always returns true
*/
function get_newsletter_list(&$nl_ids, &$nl_names) {
	global $db, $lang;
	
	$nl_ids = array();
	$nl_names = array();
	
	$sql = "SELECT * FROM `" . NEWSLETTER . "` n 
			ORDER BY n.newsletter_date DESC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	while ( $news = $db->getarray($result) ) {
		$nl_ids[] = $news['newsletter_id'];
		$nl_names[] = $lang['Newsletter_volume'] . ' ' . $news['newsletter_volume'] . ' ' . $lang['Newsletter_issue'] . ' ' . $news['newsletter_issue'];
	}
	
	$db->freeresult($result);
	
	return true;
}


function manage_newsletter($n_type) {
	global $t, $dbconfig, $lang;
	
	// These values are the same regarless if we're adding or editing a newsletter
	$t->set_template( load_template('newsletter_manage_form') );
	$t->set_vars( array(
		'L_VOLUME' => $lang['Newsletter_volume'],
		'L_ISSUE' => $lang['Newsletter_issue'],
		'L_TEAMWORK' => $lang['Newsletter_teamwork'],
		'L_FINANCIAL_LITERACY' => $lang['Newsletter_financial_literacy'],
		'L_NEWSLETTER_TEXT' => $lang['Newsletter_text'],
		'L_MMI_IN_MOTION' => $lang['Newsletter_mmi_in_motion'],
		'L_DID_YOU_KNOW' => $lang['Newsletter_did_you_know'],
		'L_VOLUNTEER_VOICES' => $lang['Newsletter_volunteer_voices'],
		'L_SPOTLIGHT' => $lang['Newsletter_spotlight'],
		'L_MORE_NEWSLETTER_TEXT' => $lang['Newsletter_more_text'],
		'L_NEWSLETTER_ARCHIVES' => $lang['Newsletter_archives'],
		'L_PAST_NEWSLETTERS' => $lang['Newsletter_past_newsletters'],
		'L_SEARCH_ARCHIVES' => $lang['Newsletter_search_archives'],
		'L_SEARCH_NEWSLETTER' => $lang['Newsletter_search_newsletter'],
		'CONFIG_IMAGE_DIRECTORY' => $dbconfig['image_directory'],
		)
	);
		
	if ( $n_type == NEWSLETTER_ADD ) {
		$t->set_vars( array(
			'L_MANAGE_NEWSLETTER' => $lang['Newsletter_add'],
			'NEWSLETTER_ID' => NULL,
			'NEWSLETTER_ACTION' => 'upload'
			)
		);
	} elseif ( is_numeric($n_type) && $n_type > 0 ) {
		$n = get_newsletter_data($n_type);
		
		$t->set_vars( array(
			'L_MANAGE_NEWSLETTER' => $lang['Newsletter_edit'],
			'NEWSLETTER_ID' => $n_type,
			'NEWSLETTER_VOLUME' => intval($n['newsletter_volume']),
			'NEWSLETTER_ISSUE' => intval($n['newsletter_issue']),
			'NEWSLETTER_MMI_IN_MOTION' => stripslashes($n['newsletter_mmi_in_motion']),
			'NEWSLETTER_DID_YOU_KNOW' => stripslashes($n['newsletter_did_you_know']),
			'NEWSLETTER_VOLUNTEER_VOICES' => stripslashes($n['newsletter_volunteer_voices']),
			'NEWSLETTER_SPOTLIGHT' => stripslashes($n['newsletter_spotlight']),
			'NEWSLETTER_ACTION' => 'edit'
			)
		);
	}
	
	$content = $t->parse($dbconfig['show_template_name']);
	
	return $content;
}


function upload_newsletter_images($newsletter_volume, $newsletter_issue) {
	global $db, $lang, $_FILES, $dbconfig;
	
	$image_names = array();
	
	// Upload the images
	if ( !empty($_FILES) ) {
		$new_dir = 'newsletter_' . $newsletter_volume . '_' . $newsletter_issue;
		mkdir('images/newsletter/' . $new_dir);
		while ( $img_key = key($_FILES) ) {
			$image_id = 0;
			
			$tmp_name = $_FILES[$img_key]['tmp_name'];
			
			if ( !empty($tmp_name) ) {
				$name = $_FILES[$img_key]['name'];
				$type = $_FILES[$img_key]['type'];
			
				// Ensure the things being uploaded are actually images
				if ( empty($tmp_name) || strpos($type, 'image') === false ) {
					cccs_message(WARNING_MESSAGE, $lang['Error_bad_file_type']);
				}
				
				// Make the name and description for the image, which is the key_volume#_issue#
				$image_parts = explode('.', $name);
				
				$extension = $image_parts[ count($image_parts) - 1 ];
				$image_name = $img_key . '_' . $newsletter_volume . '_' . $newsletter_issue . '.' . $extension;
				
				// Fail quietly when doing this
				@move_uploaded_file($tmp_name, 'images/newsletter/' . $new_dir . '/' . $image_name);
			}
			
			if ( !empty($image_name) ) {
				array_push($image_names, 'images/newsletter/' . $new_dir . '/' . $image_name);
			} else {
				array_push($image_names, NULL);
			}
							
			unset($tmp_name, $name, $type, $image_name);
			
			next($_FILES);
		}
	}
	
	
	return $image_names;
}


function strip_img_tags($newsletter_section) {
	return preg_replace("/\[IMG \d+\]/", '', $newsletter_section);
}

function make_newsletter_title($title_text, $colspan) {
	global $t, $dbconfig;
	
	$t->set_template( load_template('newsletter_title') );
	$t->set_vars( array(
		'NEWSLETTER_TITLE' => $title_text,
		'NEWSLETTER_COLSPAN' => $colspan
		)
	);
	$newsletter_title = $t->parse($dbconfig['show_template_name']);
	
	return $newsletter_title;
}

function make_newsletter_section($section_text, $colspan) {
	global $t, $dbconfig;
	
	$t->set_template( load_template('newsletter_section') );
	$t->set_vars( array(
		'NEWSLETTER_SECTION' => $section_text,
		'NEWSLETTER_COLSPAN' => $colspan
		)
	);
	$newsletter_section = $t->parse($dbconfig['show_template_name']);
	
	return $newsletter_section;
}

?>