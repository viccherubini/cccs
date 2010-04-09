<?php

/**
 * sitemap.php
 * Automatically creates the sitemap for the site by going
 * through the Custom Page table and making a tree from the
 * Custom Page table.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_sitemap'], false);
$content_subtitle = NULL;

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Sitemap']);
$content_pagination = make_pagination($pagination);

$sql = "SELECT * FROM `" . CUSTOM_PAGE . "` cp 
		WHERE cp.page_sitemap = '1'
			AND cp.page_parent_id = '0'
			AND cp.page_isvisible = '1'
		ORDER BY cp.page_sortorder ASC";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

$sub_links = NULL;
$sitemap = NULL;

while ( $parent = $db->getarray($result) ) {
	$sql = "SELECT * FROM `" . CUSTOM_PAGE . "` cp
			WHERE cp.page_parent_id = '" . $parent['page_id'] . "'
				AND cp.page_sitemap = '1'
				AND cp.page_isvisible = '1' 
			ORDER BY cp.page_sortorder ASC";
	$result2 = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$t->set_template( load_template('link') );
	$t->set_vars( array(
		'LINK_URL' => 'page.php?pagename=' . $parent['page_name'],
		'LINK_CLASS' => 'style="font-weight: bold"',
		'LINK_TEXT' => $parent['page_title']
		)
	);
	$title_link = $t->parse($dbconfig['show_template_name']) . '<br />';

	while ( $page = $db->getarray($result2) ) {
		$t->set_template( load_template('link') );
		$t->set_vars( array(
			'LINK_URL' => 'page.php?pagename=' . $page['page_name'],
			'LINK_CLASS' => 'style="padding-left: 10px"',
			'LINK_TEXT' => $page['page_title']
			)
		);
		$sub_links .= $t->parse($dbconfig['show_template_name']) . '<br />';
	}
	
	$sitemap .= $title_link . $sub_links . '<br />';
	$sub_links = NULL;
}

$content = make_content($sitemap);

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

unset($sql, $title_link, $sub_links, $sitemap);

include $root_path . 'includes/page_footer.php';

include $root_path . 'includes/page_exit.php';

?>