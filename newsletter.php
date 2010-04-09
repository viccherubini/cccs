<?php

/**
 * newsletter.php
 * The TEAMWORK newsletter page.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';
include $root_path . 'includes/functions_newsletter.php';

include $root_path . 'includes/page_header.php';

$content_title = NULL;
$content_subtitle = NULL;

$pagination[] = array('newsletter.php', $lang['Newsletter']);

$incoming = collect_vars($_REQUEST, array('do' => MIXED));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( $do == 'add' ) {
		can_view(REGIONAL_DIRECTOR);
		
		$pagination[] = array(NULL, $lang['Newsletter_add']);

		$content = manage_newsletter(NEWSLETTER_ADD);
	} elseif ( $do == 'edit' ) {
		can_view(REGIONAL_DIRECTOR);
		
		$pagination[] = array(NULL, $lang['Newsletter_edit']);
			
		$incoming = collect_vars($_GET, array('newsletterid' => INT));
		extract($incoming);
		
		$content = manage_newsletter($newsletterid);
	} else {
		$content = display_newsletter(NEWSLETTER_LATEST);
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'search' ) {
		$pagination[] = array(NULL, $lang['Newsletter_search_results']);
		
		$incoming = collect_vars($_POST, array('newsletterid' => INT, 'search_query' => MIXED));
		extract($incoming);
		
		// Add search newsletter stuff here
		if ( empty($search_query) ) {
			$content = display_newsletter($newsletterid);
		}
	} elseif ( $do == 'upload' ) {
		can_view(REGIONAL_DIRECTOR);
		
		$content_title = make_title($lang['Newsletter_added'], false);
		//$content_subtitle = make_title($lang['Subtitle_welcome'], true);

		$pagination[] = array(NULL, $lang['Newsletter_added']);
		
		$incoming = collect_vars($_POST, array('newsletter_issue' => INT, 'newsletter_volume' => INT, 'newsletter_mmi_in_motion' => MIXED, 'newsletter_did_you_know' => MIXED, 'newsletter_volunteer_voices' => MIXED, 'newsletter_spotlight' => MIXED));
		extract($incoming);
		
		if ( empty($newsletter_issue) || empty($newsletter_volume) ) { 
			cccs_message(WARNING_MESSAGE, $lang['Error_need_issue_and_volume']);
		}
		
		$inames = array();		
		$inames = upload_newsletter_images($newsletter_volume, $newsletter_issue);

		// Check to see which images were inserted
		$n = array($newsletter_mmi_in_motion, $newsletter_did_you_know, $newsletter_volunteer_voices, $newsletter_spotlight);
		
		for ( $i=0; $i<count($n); $i++ ) {
			//if ( !empty($inames[$i]) ) {
				$n[$i] = ( !empty($inames[$i]) ) ? '[IMG]' . $inames[$i] . "[/IMG]\n" . $n[$i] : $n[$i];
				
			//} else {
				
			//}
			
			$n[$i] = clean_text($n[$i], false);
		}
		
		
		// Insert the actual newsletter
		$sql = "INSERT INTO `" . NEWSLETTER . "` 
					(newsletter_id, newsletter_issue, newsletter_volume, newsletter_date, newsletter_mmi_in_motion, 
					newsletter_did_you_know, newsletter_volunteer_voices, newsletter_spotlight)
				VALUES ('', '" . $newsletter_issue . "', '" . $newsletter_volume . "', '" . CCCSTIME . "', '" . $n[0] . "',
						'" . $n[1] . "', '" . $n[2] . "', '" . $n[3] . "')";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Newsletter_added_thank_you']);
	} elseif ( $do == 'edit' ) {
		can_view(REGIONAL_DIRECTOR);
		
		$content_title = make_title($lang['Newsletter_updated'], false);
		
		$pagination[] = array(NULL, $lang['Newsletter_updated']);
	
		$incoming = collect_vars($_POST, array('newsletterid' => INT, 'newsletter_issue' => INT, 'newsletter_volume' => INT, 'newsletter_mmi_in_motion' => MIXED, 'newsletter_did_you_know' => MIXED, 'newsletter_volunteer_voices' => MIXED, 'newsletter_spotlight' => MIXED));
		extract($incoming);
		
		$sql = "UPDATE `" . NEWSLETTER . "` SET
					newsletter_issue = '" . $newsletter_issue . "', newsletter_volume = '" . $newsletter_volume . "',
					newsletter_mmi_in_motion = '" . $newsletter_mmi_in_motion . "',
					newsletter_did_you_know = '" . $newsletter_did_you_know . "',
					newsletter_volunteer_voices = '" . $newsletter_volunteer_voices . "',
					newsletter_spotlight = '" . $newsletter_spotlight . "'
				WHERE newsletter_id = '" . $newsletterid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Newsletter_edited_thank_you']);
	}
}

// Now we collect the information from the database for filling out this page
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