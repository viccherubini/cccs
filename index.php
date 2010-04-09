<?php

/**
 * index.php
 * Start page for all of the website.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_welcome'], false);
$content_subtitle = make_title($lang['Subtitle_welcome'], true);

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Welcome']);
$content_pagination = make_pagination($pagination);

$r_ids = array();
$r_names = array();
get_regions($r_ids, $r_names);

// Shift off the first element to remove Bankrupcty Education (this is a little hack)
array_shift($r_ids);
array_shift($r_names);

array_unshift($r_ids, 0);
array_unshift($r_names, $lang['Home_select_a_region']);

$region_list = make_drop_down('regionid', $r_ids, $r_names, NULL, 'onchange="this.form.submit()"', 'id="regionlist"');

$welcome_text = parse_page($lang['Home_welcome_text']);

// Get the article list
$sql = "SELECT * FROM `" . ARTICLE . "` a 
		WHERE a.article_expire_date > '" . CCCSTIME . "' 
			ORDER BY a.article_expire_date ASC";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
while ( $a = $db->getarray($result) ) {
	
	$article_link = make_link(stripslashes($a['article_url']), stripslashes($a['article_title']) );
	
	$t->set_template( load_template('list_item') );
	$t->set_vars( array(
		'LIST_ITEM' => $article_link
		)
	);
	$article_list .= $t->parse($dbconfig['show_template_name']);
}
$db->freeresult($result);

// Select a random DYK
$sql = "SELECT d.dyk_text FROM `" . DID_YOU_KNOW . "` d 
		ORDER BY RAND() LIMIT 1";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

$dyk = $db->getarray($result);
$db->freeresult($result);

$t->set_template( load_template('list_item') );
$t->set_vars( array(
	'LIST_ITEM' => $dyk['dyk_text']
	)
);
$did_you_know_list = $t->parse($dbconfig['show_template_name']);

// Welcome template rather than that static welcome page from custom_pages
$t->set_template( load_template('welcome') );
$t->set_vars( array(
	'L_UPCOMING_CLASSES' => $lang['Home_upcoming_classes'],
	'L_UPCOMING_CLASSES_TEXT' => $lang['Home_upcoming_classes_text'],
	'L_BANKRUPTCY_EDUCATION' => $lang['Menu_bankruptcy_education_courses'],
	'L_BANKRUPTCY_EDUCATION_PROGRAMS' => $lang['Bankruptcy_education_courses'],
	'L_VIEW_PAGE' => $lang['Home_view_page'],
	'L_YOUR_PRESENTATION' => $lang['Home_your_presentation'],
	'L_REQUEST_PRESENTATION' => $lang['Home_request_presentation'],
	'L_VOLUNTEER_PROGRAMS' => $lang['Home_volunteer_programs'],
	'L_VOLUNTEER_PROGRAMS_TEXT' => $lang['Home_volunteer_programs_text'],
	'L_FINANCIAL_RESOURCES' => $lang['Home_financial_resources'],
	'L_ARTICLES' => $lang['Home_articles'],
	'L_WEBSITE_RESOURCES' => $lang['Home_website_resources'],
	'L_UMC_QUIZ' => $lang['Home_umc_quiz'],
	'L_UYCR_QUIZ' => $lang['Home_uycr_quiz'],
	'L_DID_YOU_KNOW' => $lang['Home_did_you_know'],
	'L_WELCOME_CFE' => $lang['Title_welcome'],
	'L_WELCOME_TEXT' => $welcome_text,
	'REGION_LIST' => $region_list,
	'ARTICLE_LIST' => $article_list,
	'DID_YOU_KNOW_LIST' => $did_you_know_list
	)
);
$content = $t->parse($dbconfig['show_template_name']);

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