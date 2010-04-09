<?php

if ( !defined('IN_CCCS') ) {
	exit;
}

// This loads the main_body DIV and sets the navigation
// from the variable from page_header.php and the
// content variable from whatever page is loading this page
$t->set_template( load_template('main_body') );
$t->set_vars( array(
	'MAIN_BODY_NAVIGATION' => $main_body_navigation,
	'MAIN_BODY_CONTENT' => $main_body_content
	)
);
$main_content = $t->parse($dbconfig['show_template_name']);


// Takes the above content and puts it into the overall_main
// or main DIV along with the header variable from
// page_header.php
$t->set_template( load_template('overall_main') );
$t->set_vars( array(
	'MAIN_HEADER' => $main_header,
	'MAIN_CONTENT' => $main_content
	)
);
$body_content = $t->parse($dbconfig['show_template_name']);

// Load the copyright template, pretty simple
$t->set_template( load_template('copyright') );
$t->set_vars( array(
	'L_EMAIL_CENTERS' => $lang['Email_centers'],
	'L_HOUSTON_PHONE' => $lang['Houston_phone'],
	'L_HOUSTON_ADDRESS' => $lang['Houston_address'],
	'L_MMI' => $lang['MMI'],
	'L_PRIVACY_USAGE' => $lang['Privacy_and_usage'],
	'L_CREATED_BY' => $lang['Created_by'],
	'L_EPIC_SOFTWARE_GROUP' => $lang['Epic_software_group'],
	'CONFIG_ADMIN_EMAIL' => $dbconfig['admin_email'],
	'CONFIG_VERSION' => $dbconfig['site_version'],
	'COPYRIGHT_YEAR' => date("Y", CCCSTIME)
	)
);
$body_copyright = $t->parse($dbconfig['show_template_name']);

$t->set_template( load_template('overall_body') );
$t->set_vars( array(
	'BODY_CONTENT' => $body_content,
	'BODY_COPYRIGHT' => $body_copyright
	)
);
print $t->parse($dbconfig['show_template_name']);

//print $db->getquerylist();

// This must be the last line of the page
define('CCCS_PAGE_FOOTER', true, false);
?>