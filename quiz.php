<?php

/**
 * quiz.php
 * Allows the user to take the quizes to test their knowledge.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_quiz'], false);

$incoming = collect_vars($_REQUEST, array('quizid' => INT, 'do' => MIXED));
extract($incoming);

// Now we collect the information from the database for filling out this page
$pagination[] = array('page.php?pagename=financial_education_resources', $lang['Menu_financial_education_resources']);
$pagination[] = array('quiz.php?quizid=' . $quizid, $lang['Quiz']);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	if ( !is_numeric($quizid) || $quizid <= 0 ) {
		cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
	}
	
	$sql = "SELECT * FROM `" . QUIZ . "` q
			LEFT JOIN `" . QUIZ_QUESTION . "` qq
				ON q.quiz_id = qq.question_quiz_id
			WHERE q.quiz_id = '" . $quizid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$question_number = 1;
	while ( $question = $db->getarray($result) ) {
		$t->set_template( load_template('quiz_question_item') );
		$t->set_vars( array(
			'L_YES' => $lang['Yes'],
			'L_NO' => $lang['No'],
			'QUESTION_NUMBER' => $question_number,
			'QUESTION_TEXT' => stripslashes($question['question_text']),
			'QUESTION_ID' => $question['question_id']
			)
		);
		$question_list .= $t->parse($dbconfig['show_template_name']);
		$question_number++;
	}
	
	$t->set_template( load_template('quiz') );
	$t->set_vars( array(
		'L_CHECK_QUIZ' => $lang['Quiz_check_quiz'],
		'QUIZ_ID' => $quizid,
		'QUESTION_LIST' => $question_list
		)
	);
	$content = $t->parse($dbconfig['show_template_name']);

	// Get the quiz name for the subtitle
	$sql = "SELECT * FROM `" . QUIZ . "` q 
			WHERE q.quiz_id = '" . $quizid . "'";
	$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
	
	$quiz = $db->getarray($result);
	
	$content_subtitle = make_title($quiz['quiz_name'], true);
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'checkquiz' ) {
		$pagination[] = array(NULL, $lang['Check_quiz']);
		
		$sql = "SELECT * FROM `" . QUIZ_QUESTION . "` qq
				WHERE qq.question_quiz_id = '" . $quizid . "'
				ORDER BY qq.question_id ASC";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$question_number = 1;
		$num_correct = 0;
		
		
		while ( $question = $db->getarray($result) ) {
			list($k, $v) = each($_POST['question_id']);
			
			if ( $question['question_answer'] == $v ) {
				$num_correct++;
			}
			
			$t->set_template( load_template('quiz_question_answer_item') );
			$t->set_vars( array(
				'L_YOU_ANSWERED' => $lang['Quiz_you_answered'],
				'L_CORRECT_ANSWER' => $lang['Quiz_correct_answer'],
				'L_REASON' => $lang['Quiz_reason'],
				'QUESTION_NUMBER' => $question_number,
				'QUESTION_TEXT' => stripslashes($question['question_text']),
				'QUESTION_THEIR_ANSWER' => yes_no($v),
				'QUESTION_CORRECT_ANSWER' => yes_no($question['question_answer']),
				'QUESTION_REASON' => $question['question_reason']
				)
			);
			$question_list .= $t->parse($dbconfig['show_template_name']);
			$question_number++;
		}
		
		$content = make_content($question_list);
		
		$content_subtitle = make_title($num_correct . '/' . ($question_number-1), false);
	}
}

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