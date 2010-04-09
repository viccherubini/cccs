<?php

/**
 * courses.php
 * All of the courses offered currently in the database. This is editable
 * by administrators only!
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

$incoming = collect_vars($_REQUEST, array('do' => MIXED ));
extract($incoming);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$sql = "SELECT * FROM `" . PROGRAM . "` p WHERE 1 ORDER BY p.program_sortorder ASC";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		$use_template = 'courses_offered_item_edit';
	} else {
		$use_template = 'courses_offered_item_content';	// this template probably has no reason to exist
	}
	
	while ( $program = $db->getarray($result) ) {
		$delete_checkbox = make_input_box(CHECKBOX, 'course_delete[]', $program['program_id']);
		
		$t->set_template( load_template($use_template) );
		$t->set_vars( array(
			'L_DELETE' => $lang['Delete'],
			'COURSE_ID' => $program['program_id'],
			'CONTENT_TITLE' => $program['program_name'],
			'DELETE_CHECKBOX' => $delete_checkbox,
			'CONTENT' => $program['program_description']
			)
		);
		$courses_form .= $t->parse($dbconfig['show_template_name']);
	}
	
	if ( $usercache['user_type'] == ADMINISTRATOR ) {
		$t->set_template( load_template('courses_offered_form') );
		$t->set_vars( array(
			'L_UPDATE_COURSES' => $lang['Update_courses'],
			'EDIT_COURSES_FORM' => $courses_form
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
	} else {
		//$t->set_template( load_template('courses_offered_form') );
		$content .= $courses_form;
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'editcourses' ) {
		// First take care of the deletions!
		for ( $i=0; $i<count($_POST['course_delete']); $i++ ) {
			$sql = "UPDATE `" . EVENT . "` SET event_program_id = ";
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