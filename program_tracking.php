<?php

/**
 * program_tracking.php
 * Takes care of all of the program tracking for post event information (evaluations).
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

include $root_path . 'includes/page_header.php';

$content_title = make_title($lang['Title_program_tracking'], false);
$pagination[] = array('program_tracking.php', $lang['Program_tracking']);

// See if they are an applicant and somehow managed to get a session set.
// If so, tell them and log them out
can_view(APPLICANT);

$incoming = collect_vars($_REQUEST, array('do' => MIXED, 'eventid' => INT, 'responseid' => INT));
extract($incoming);


if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	// Show the user Pending Events.
	// Pending Events are events that *they*
	// are assigned to that they have not 
	// yet taught.
	if ( $do == 'pendingevents' ) {
		$content_subtitle = make_title($lang['Pending_events'], true);
		$pagination[] = array(NULL, $lang['Pending_events']);
		
		$content = make_content($lang['Program_tracking_no_eval_text']);
		
		$events = get_user_events($usercache['user_id']);

		$content .= make_pending_events_calendar($events);
		
		unset($events);
	} elseif ( $do == 'completeevents' ) {
		$content_subtitle = make_title($lang['Subtitle_completed_no_eval'], true);
		$pagination[] = array(NULL, $lang['Completed_no_eval']);
		
		$content = make_content($lang['Program_tracking_eval_text']);
		
		if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
			// This query is HUGE and takes about 3-4
			// seconds to complete. I need to figure out a way
			// to make these run faster. Perhaps multiple queries? 12.20.2005
			$sql = "SELECT e.event_id, e.event_region_id, e.event_contact_organization, 
						e.event_public, e.event_end_date, e.event_start_date, a.assignment_authorized, 
						p.program_id, p.program_name 
					FROM `" . EVENT . "` e
					LEFT JOIN `" . PROGRAM . "` p
						ON e.event_program_id = p.program_id
					LEFT JOIN `" . ASSIGNMENT . "` a
						ON e.event_id = a.assignment_event_id
					WHERE e.event_authorized = '1'
						AND e.event_complete = '0'
						AND a.assignment_authorized = '1'
						AND e.event_end_date <= '" . CCCSTIME . "'
						AND e.event_region_id = '" . $usercache['user_region_id'] . "'
					ORDER BY e.event_start_date ASC";
		} else {
			$sql = "SELECT * FROM `" . EVENT . "` e
					LEFT JOIN `" . PROGRAM . "` p
						ON e.event_program_id = p.program_id
					LEFT JOIN `" . ASSIGNMENT . "` a
						ON e.event_id = a.assignment_event_id
					WHERE a.assignment_user_id = '" . $usercache['user_id'] . "'
						AND e.event_complete = '0'
						AND e.event_authorized = '1'
						AND a.assignment_authorized = '1'
						AND e.event_end_date <= '" . CCCSTIME . "'
						AND e.event_region_id = '" . $usercache['user_region_id'] . "'
					ORDER BY e.event_start_date ASC";
		}

		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		while ( $event = $db->getarray($result) ) {
			$sql = "SELECT * FROM `" . RESPONSE . "` r WHERE r.response_event_id = '" . $event['event_id'] . "'";
			$result_response = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
			if ( $db->numrows($result_response) == 0 ) {
			
				$event_public = ( $event['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('program_tracking_start_eval_item') );
				$t->set_vars( array(
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $event['event_id'],
					'EVENT_REGION_ID' => $event['event_region_id'],
					'EVENT_PROGRAM_TITLE' => stripslashes($event['program_name']),
					'EVENT_ORGANIZATION' => $event['event_contact_organization'],
					'EVENT_DATE' => date($dbconfig['date_format'], $event['event_start_date']),
					'EVENT_LOCATION' => $event['event_location']
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}
			
			$db->freeresult($result_response);
		}

		$db->freeresult($result);

		$t->set_template( load_template('program_tracking_no_eval', false) );
		$t->set_vars( array(
			'L_REGISTERED_EVENTS' => $lang['Program_tracking_no_eval'],
			'L_ID' => $lang['Program_tracking_id'],
			'L_EVENT' => $lang['Program_tracking_event'],
			'L_DATE' => $lang['Program_tracking_date'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_LOCATION' => $lang['Program_tracking_location'],
			'L_FILL_OUT_EVAL' => $lang['Program_tracking_fill_out_eval'],
			'EVENT_ITEM_LIST' => $event_item_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);

		// If the user is an RD or Admin, show them the events
		// that have been completed, but have not had their final
		// evaluations filled out.
		if ( $usercache['user_type'] <= REGIONAL_DIRECTOR ) {
			$event_item_list = NULL;

			$content .= make_content($lang['Program_tracking_finish_eval_text']);

			$sql = "SELECT * FROM `" . EVENT . "` e
					LEFT JOIN `" . PROGRAM . "` p
						ON e.event_program_id = p.program_id
					INNER JOIN `" . RESPONSE . "` r
						ON e.event_id = r.response_event_id
					WHERE e.event_authorized = '1'
						AND r.response_completed = '0'
						AND e.event_region_id = '" . $usercache['user_region_id'] . "'
					ORDER BY e.event_start_date ASC";
			$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

			while ( $event = $db->getarray($result) ) {
				$event_public = ( $event['event_public'] == 1 ) ? EVENT_PUBLIC : EVENT_PRIVATE;
				
				$t->set_template( load_template('program_tracking_finish_eval_item') );
				$t->set_vars( array(
					'EVENT_PUBLIC' => $event_public,
					'EVENT_ID' => $event['event_id'],
					'RESPONSE_ID' => $event['response_id'],
					'EVENT_REGION_ID' => $event['event_region_id'],
					'EVENT_PROGRAM_TITLE' => stripslashes($event['program_name']),
					'EVENT_ORGANIZATION' => $event['event_contact_organization'],
					'EVENT_DATE' => date($dbconfig['date_format'], $event['event_start_date']),
					'EVENT_LOCATION' => $event['event_location']
					)
				);
				$event_item_list .= $t->parse($dbconfig['show_template_name']);
			}

			$t->set_template( load_template('program_tracking_finish_eval', false) );
			$t->set_vars( array(
				'L_UNFINISHED_EVALS' => $lang['Program_tracking_unfinished_evals'],
				'L_ID' => $lang['Program_tracking_id'],
				'L_EVENT' => $lang['Program_tracking_event'],
				'L_DATE' => $lang['Program_tracking_date'],
				'L_ORGANIZATION' => $lang['Organization'],
				'L_LOCATION' => $lang['Program_tracking_location'],
				'L_FINISH_EVAL' => $lang['Program_tracking_finish_eval'],
				'EVENT_ITEM_LIST' => $event_item_list
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);

			$db->freeresult($result);
		}
		
		unset($event_item_list, $event, $sql);
	} elseif ( $do == 'finishedevents' ) {
		// This section shows the user all of the completed events,
		// meaning both phases of the evaluation have been filled out
		// completely.
		
		// This section needs to be updated to have some type of 
		// pagination/breadcrumb navigation as its getting pretty unruly.
		// 12.20.2005
		
		$content_subtitle = make_title($lang['Subtitle_completed_eval'], true);
		$pagination[] = array(NULL, $lang['Subtitle_completed_eval']);
		
		$incoming = collect_vars($_GET, array('sort_field' => MIXED, 'sort' => MIXED) );
		extract($incoming);
		
		if ( $sort != 'asc' && $sort != 'desc' ) { $sort = 'asc'; }
		
		$one_day = 60 * 60 * 24;
		
		if ( empty($sort) || empty($sort_field) ) {
			$sort = 'r.response_id ASC';
		} else {
			if ( $sort_field == 'id' ) {
				$sort = 'r.response_event_id ' . strtoupper($sort);
			} elseif ( $sort_field == 'date' ) {
				$sort = 'e.event_start_date ' . strtoupper($sort);
			}
		}
		
		$content = make_content($lang['Program_tracking_post_eval_text']);
				
		$sql = "SELECT * FROM `" . RESPONSE . "` r
				LEFT JOIN `" . EVENT . "` e
					ON r.response_event_id = e.event_id
				LEFT JOIN `" . PROGRAM . "` p 
					ON e.event_program_id = p.program_id
				WHERE r.response_completed = '1'
					AND r.response_region_id = '" . $usercache['user_region_id'] . "'
				ORDER BY " . $sort;
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		while ( $r = $db->getarray($result) ) {
			$event_public = ( $r['event_public'] == 1 ? EVENT_PUBLIC : EVENT_PRIVATE );
			
			$t->set_template( load_template('program_tracking_finish_eval_item') );
			$t->set_vars( array(
				'EVENT_PUBLIC' => $event_public,
				'EVENT_ID' => $r['event_id'],
				'EVENT_REGION_ID' => $r['event_region_id'],
				'EVENT_PROGRAM_TITLE' => stripslashes($r['program_name']),
				'EVENT_ORGANIZATION' => $r['event_contact_organization'],
				'EVENT_DATE' => date($dbconfig['date_format'], $r['event_start_date']),
				'RESPONSE_ID' => $r['response_id']
				)
			);
			$response_list .= $t->parse($dbconfig['show_template_name']);
		}
		
		$t->set_template( load_template('program_tracking_final_form', false) );
		$t->set_vars( array(
			'L_FINISHED_EVENTS' => $lang['Program_tracking_finished_events'],
			'L_ID' => $lang['Id'],
			'L_EVENT' => $lang['Event'],
			'L_ORGANIZATION' => $lang['Organization'],
			'L_DATE' => $lang['Date'],
			'L_VIEW_EVAL' => $lang['Program_tracking_view_eval'],
			'RESPONSE_LIST' => $response_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		
		unset($response_list);
	} elseif ( $do == 'starteval' ) {
		// THIS IS PHASE 1 of the POST EVENT EVALUATION
		
		$content_subtitle = make_title($lang['Post_event_info'], true);
		
		$pagination[] = array('program_tracking.php?do=completeevents', $lang['Completed_no_eval']);
		$pagination[] = array(NULL, $lang['Post_event_info']);
		
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}

		$e = array();
		$e = get_event_data($eventid);
		
		// Make sure there isn't any record of this being completed.		
		$sql = "SELECT * FROM `" . RESPONSE . "` r 
				WHERE r.response_event_id = '" . $eventid . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result) > 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_response_exists']);
		}
		
		// It's just a simple form, people
		// But a bigass one nonetheless
		$e = array();
		$e = get_event_data($eventid);
		
		// Audience types
		$ai = array();
		$av = array();
		make_response_array(RESPONSE_AUDIENCE, $ai, $av);
		
		// Primary Foci
		$fi = array();
		$fv = array();
		make_response_array(RESPONSE_FOCUS, $fi, $fv);
		
		// Program list
		$si = array();
		$sv = array();
		get_programs($si, $sv);
		
		$event_status_list = make_drop_down('response_status', array(1,0), $lang['Event_status'], 1, 'onchange="fill_zero(\'response-status\', \'response-attendance\')"', 'id="response-status"');
		$event_audience_list = make_drop_down('response_audience', $ai, $av);
		$event_primary_focus_list = make_drop_down('response_primary_focus', $fi, $fv);
		$event_subject_list = make_drop_down('response_subject', $si, $sv);
		
		$t->set_template( load_template('program_tracking_start_eval_form', false) );
		$t->set_vars( array(
			'L_PROGRAM_TRACKING' => $lang['Program_tracking'],
			'L_ID' => $lang['Program_tracking_id'],
			'L_PROGRAM_TITLE' => $lang['Program_tracking_program_title'],
			'L_DATE' => $lang['Program_tracking_date'],
			'L_TIME' => $lang['Program_tracking_time'],
			'L_LOCATION' => $lang['Program_tracking_location'],
			'L_POST_EVENT_INFORMATION' => $lang['Program_tracking_post_event_information'],
			'L_EVENT_STATUS' => $lang['Program_tracking_event_status'],
			'L_EVENT_AUDIENCE' => $lang['Program_tracking_event_audience'],
			'L_EVENT_PRIMARY_FOCUS' => $lang['Program_tracking_primary_focus'],
			'L_EVENT_SUBJECT' => $lang['Program_tracking_subject_subject'],
			'L_EVENT_ATTENDANCE' => $lang['Program_tracking_attendance'],
			'L_COMMENTS' => $lang['Program_tracking_comments'],
			'L_SAVE_POST_EVENT' => $lang['Program_tracking_save_post_event'],
			'EVENT_ID' => $e['event_id'],
			'EVENT_PROGRAM_TITLE' => $e['program_name'],
			'EVENT_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'EVENT_START_TIME' => date($dbconfig['time_format'], $e['event_start_date']),
			'EVENT_END_TIME' => date($dbconfig['time_format'], $e['event_end_date']),
			'EVENT_LOCATION' => $e['event_location'],
			'EVENT_STATUS_LIST' => $event_status_list,
			'EVENT_AUDIENCE_LIST' => $event_audience_list,
			'EVENT_PRIMARY_FOCUS_LIST' => $event_primary_focus_list,
			'EVENT_SUBJECT_LIST' => $event_subject_list,
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($e, $event_subject_list, $event_audience_list, $event_primary_focus_list);
	} elseif ( $do == 'finisheval' ) {
		// This is the phase 2 of the post event evaluation and
		// is to be filled out by RD's or Admins.
		
		if ( !is_numeric($responseid) || $responseid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		$r = get_response_data($responseid);
						
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $r['response_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$content_subtitle = make_title($lang['Program_tracking_finish_evaluation'], true);
		
		$pagination[] = array('program_tracking.php?do=completeevents', $lang['Completed_no_eval']);
		$pagination[] = array(NULL, $lang['Program_tracking_finish_evaluation']);
				
		// Get the response so we can use part in the form
		$sql = "SELECT r.*, ra.audience_name, rf.focus_name, p.program_name
				FROM `" . RESPONSE . "` r, `" . RESPONSE_AUDIENCE . "` ra, 
					`" . RESPONSE_FOCUS . "` rf, `" . PROGRAM . "` p
				WHERE r.response_id = '" . $responseid . "'
					AND r.response_completed = '0'
					AND r.response_audience = ra.audience_id
					AND r.response_primary_focus = rf.focus_id
					AND r.response_subject = p.program_id";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$r = array();
		$r = $db->getarray($result) or cccs_message(WARNING_CRITICAL, $lang['Error_response_completed']);
		
		// Grant list
		$gi = array();
		$gv = array();
		make_grant_list($r['response_region_id'], $gi, $gv);

		$grant_list = make_drop_down('response_grant_id', $gi, $gv, NULL, 'onchange="fill_zero(\'grant-list\', \'grant-amount\')"', 'id="grant-list"');
		
		// Now retrieve a list of question from the DB
		// for the second half of the evaluation.
		$sql = "SELECT rq.question_text, rq.question_answer, rq.question_type 
				FROM `" . RESPONSE_QUESTION . "` rq";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$question_id = 0;
		$question_list = NULL;
		$j = 0;
		
		// This form can get quite big...
		// All of the multiple choice questions answers
		// are stored as serialized data.
		$p = array($lang['Program_tracking_pretest'], $lang['Program_tracking_posttest']);
		while ( $q = $db->getarray($result) ) {
			$t->set_template( load_template('program_tracking_finish_eval_question') );
			$t->set_vars( array(
				'QUESTION_TEXT' => $q['question_text']
				)
			);
			$question_list .= $t->parse($dbconfig['show_template_name']);
			
			if ( $q['question_type'] == 'text' ) {
				$a = array();
				$a = unserialize($q['question_answer']);
				$ac = count($a);
				for ( $i=0; $i<$ac; $i++ ) {
					$t->set_template( load_template('program_tracking_finish_eval_question_response') );
					$t->set_vars( array(
						'QUESTION_RESPONSE' => $a[$i],
						'QUESTION_ID' => $question_id,
						'QUESTION_RESPONSE_ID' => $i,
						'QUESTION_VALUE' => NULL,
						'QUESTION_REQUIRED_STAR' => '*'
						)
					);
					$question_list .= $t->parse($dbconfig['show_template_name']);
				}
			} else {
				// The counters for the $z variable is
				// somewhat unclear. Let me explain:
				// Because of how the template is stored,
				// I wanted to create a way where only one
				// template would be used for both the pre and
				// post tests. That way, the $z variable keeps
				// track of the current 2D array index for
				// the input name. 
				// IE: response[question_num][$z]
				// And ++$z is so that $z is incremented and then
				// added to $i to keep everything in one template! :)
				$z = 0;
				$pc = count($p);
				for ( $i=0; $i<$pc; $i++ ) {
					$t->set_template( load_template('program_tracking_finish_eval_question_true_false') );
					$t->set_vars( array(
						'L_TRUE' => $lang['True'],
						'L_FALSE' => $lang['False'],
						'QUESTION_RESPONSE' => $p[$i],
						'QUESTION_ID' => $question_id,
						'QUESTION_RESPONSE_TRUE' => $i + $z,
						'QUESTION_RESPONSE_FALSE' => $i + (++$z),
						'QUESTION_VALUE' => NULL,
						)
					);
					$question_list .= $t->parse($dbconfig['show_template_name']);
				}
			}

			$question_id++;
		}
		
		$t->set_template( load_template('program_tracking_finish_eval_form', false) );
		$t->set_vars( array(
			'L_FINAL_EVALUATION' => $lang['Program_tracking_final_evaluation'],
			'L_EVENT_STATUS' => $lang['Program_tracking_event_status'],
			'L_EVENT_AUDIENCE' => $lang['Program_tracking_event_audience'],
			'L_PRIMARY_FOCUS' => $lang['Program_tracking_primary_focus'],
			'L_EVENT_SUBJECT' => $lang['Program_tracking_subject_subject'],
			'L_ATTENDANCE' => $lang['Program_tracking_attendance'],
			'L_REGISTERED' => $lang['Program_tracking_registered'],
			'L_COMMENTS' => $lang['Program_tracking_comments'],
			'L_RESPONSE_BILLED' => $lang['Program_tracking_workshop_billed'],
			'L_RESPONSE_BILLED_AMOUNT' => $lang['Program_tracking_how_much_billed'],
			'L_RESPONSE_GRANT' => $lang['Program_tracking_who_grant'],
			'L_RESPONSE_GRANT_AMOUNT' => $lang['Program_tracking_how_much_grant'],
			'L_RESPONSE_FREE_EVENT' => $lang['Program_tracking_free_event'],
			'L_RESPONSE_UNRESTRICTED_GRANT' => $lang['Program_tracking_unrestricted_grant'],
			'L_RESPONSE_RESTRICTED_GRANT' => $lang['Program_tracking_restricted_grant'],
			'L_SAVE_EVALUATION' => $lang['Program_tracking_save_evaluation'],
			'GRANT_LIST' => $grant_list,
			'RESPONSE_ID' => $responseid,
			'RESPONSE_EVENT_STATUS' => ( $r['response_event_status'] == 1 ? $lang['Event_status'][0] : $lang['Event_status'][1] ),
			'RESPONSE_EVENT_AUDIENCE' => $r['audience_name'],
			'RESPONSE_PRIMARY_FOCUS' => $r['focus_name'],
			'RESPONSE_EVENT_SUBJECT' => stripslashes($r['program_name']),
			'RESPONSE_ATTENDANCE' => $r['response_attendance'],
			'RESPONSE_REGISTERED' => $r['response_registered'],
			'RESPONSE_COMMENTS' => nl2br($r['response_comments']),
			'RESPONSE_FREE_EVENT_CHECKED' => NULL,
			'RESPONSE_UNRESTRICTED_GRANT_CHECKED' => NULL,
			'RESPONSE_RESTRICTED_GRANT_CHECED' => NULL,
			'QUESTION_LIST' => $question_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'vieweval' ) {
		// This beast is just letting the user view the finished evaluation
		// and creates those nice bar graphs. Please use this section, A LOT! :-)
		
		$content_subtitle = make_title($lang['Program_tracking_finished_evaluation'], true);
		
		$pagination[] = array('program_tracking.php?do=finishedevents', $lang['Subtitle_completed_eval']);
		$pagination[] = array(NULL, $lang['Program_tracking_finished_evaluation']);
		
		if ( !is_numeric($responseid) || $responseid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		// Get the response so we can use part in the form
		$sql = "SELECT r.*, ra.audience_name, rf.focus_name, p.program_name
				FROM `" . RESPONSE . "` r, `" . RESPONSE_AUDIENCE . "` ra, 
					`" . RESPONSE_FOCUS . "` rf, `" . PROGRAM . "` p
				WHERE r.response_id = '" . $responseid . "'
					AND r.response_completed = '1'
					AND r.response_audience = ra.audience_id
					AND r.response_primary_focus = rf.focus_id
					AND r.response_subject = p.program_id";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$r = array();
		$r = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
		
		$regionid = $r['response_region_id'];
		
		$grant_name = NULL;
		if ( $r['response_grant_id'] > 0 ) {
			$grant_name = get_grant_name($r['response_grant_id']);
		} else {
			$grant_name = $lang['Control_panel_no_grant_provided'];
		}
	
		// ALL of the response/evaluation data is stored as
		// serialized data (can you tell I like it? It rocks!)
		// The data is in a huge array. 
		// $d is an array of all of the subarrays of data that
		// needs to be unserialized and matched with a question
		// and then the values are printed out.
		$i = 0;
		$d = array();
		$d = unserialize($r['response_data']);
		
		// Now retrieve a list of question from the DB
		// for the second half of the evaluation.
		$sql = "SELECT rq.question_text, rq.question_answer, rq.question_type 
				FROM `" . RESPONSE_QUESTION . "` rq";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		$p = array($lang['Program_tracking_pretest'], $lang['Program_tracking_posttest']);
		while ( $q = $db->getarray($result) ) {
			$t->set_template( load_template('program_tracking_finish_eval_question') );
			$t->set_vars( array(
				'QUESTION_TEXT' => $q['question_text']
				)
			);
			$response_list .= $t->parse($dbconfig['show_template_name']);
			
			// This is the subarray within the $d array
			// that needs to be unserialized to be matched
			// with a question.
			$m = unserialize($d[$i]);
			
			if ( $q['question_type'] == 'text' ) {
				$a = array();
				$a = unserialize($q['question_answer']);
				
				// Find the sum of the answers
				$sum = array_sum($m);
				
				$mc = count($m);
				for ( $j=0; $j<$mc; $j++ ) {
					$width = round( ( $m[$j] / $sum ) * 100);
					
					$t->set_template( load_template('program_tracking_final_response_text') );
					$t->set_vars( array(
						'QUESTION_RESPONSE' => $a[$j],
						'PERCENT_WIDTH' => $width,
						'QUESTION_RESPONSE_VALUE' => $width
						)
					);
					$response_list .= $t->parse($dbconfig['show_template_name']);
				}
			} else {
				$true_percent = $false_percent = 0;
				
				$sum_pretest = array_sum( array($m[0], $m[1]) );
				$sum_posttest = array_sum( array($m[2], $m[3]) );
	
				$sums = array($sum_pretest, $sum_posttest);
				
				$z = 0;
				
				$pc = count($p);
				for ( $j=0; $j<$pc; $j++ ) {
					// Same thing with the $z variable here as above.
					// It's just an internal increment variable
					// that allows quick addition in only one
					// pass rather than two.
					$true_percent = round( ( $m[$j + $z ] / $sums[$j] ) * 100 );
					$false_percent = round ( ( $m[$j + (++$z) ] / $sums[$j] ) * 100 );
				
					$t->set_template( load_template('program_tracking_final_response_true_false') );
					$t->set_vars( array(
						'L_TRUE' => $lang['True'],
						'L_FALSE' => $lang['False'],
						'QUESTION_RESPONSE' => $p[$j],
						'PERCENT_TRUE_WIDTH' => $true_percent,
						'PERCENT_FALSE_WIDTH' => $false_percent,
						'QUESTION_TRUE' => $true_percent,
						'QUESTION_FALSE' => $false_percent
						)
					);
					$response_list .= $t->parse($dbconfig['show_template_name']);
				}
			}

			$i++;
		}
		
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] == $regionid ) || $usercache['user_type'] == ADMINISTRATOR ) {
			$edit_evaluation_button = make_input_box(SUBMIT, NULL, $lang['Program_tracking_edit_evaluation'], 'class="btn"');
		}
		
		$t->set_template( load_template('program_tracking_final', false) );
		$t->set_vars( array(
			'L_FINISHED_EVALUATION' => $lang['Program_tracking_finished_evaluation'],
			'L_EVENT_STATUS' => $lang['Program_tracking_event_status'],
			'L_EVENT_AUDIENCE' => $lang['Program_tracking_event_audience'],
			'L_PRIMARY_FOCUS' => $lang['Program_tracking_primary_focus'],
			'L_EVENT_SUBJECT' => $lang['Program_tracking_subject_subject'],
			'L_ATTENDANCE' => $lang['Program_tracking_attendance'],
			'L_REGISTERED' => $lang['Program_tracking_registered'],
			'L_COMMENTS' => $lang['Program_tracking_comments'],
			'L_RESPONSE_BILLED' => $lang['Program_tracking_workshop_billed'],
			'L_RESPONSE_BILLED_AMOUNT' => $lang['Program_tracking_how_much_billed'],
			'L_RESPONSE_GRANT' => $lang['Program_tracking_who_grant'],
			'L_RESPONSE_GRANT_AMOUNT' => $lang['Program_tracking_how_much_grant'],
			'L_RESPONSE_FREE_EVENT' => $lang['Program_tracking_free_event'],
			'L_RESPONSE_UNRESTRICTED_GRANT' => $lang['Program_tracking_unrestricted_grant'],
			'L_RESPONSE_RESTRICTED_GRANT' => $lang['Program_tracking_restricted_grant'],
			'RESPONSE_ID' => $responseid,
			'RESPONSE_EVENT_STATUS' => ( $r['response_event_status'] == 1 ? $lang['Event_status'][0] : $lang['Event_status'][1] ),
			'RESPONSE_EVENT_AUDIENCE' => $r['audience_name'],
			'RESPONSE_PRIMARY_FOCUS' => $r['focus_name'],
			'RESPONSE_EVENT_SUBJECT' => $r['program_name'],
			'RESPONSE_ATTENDANCE' => $r['response_attendance'],
			'RESPONSE_REGISTERED' => $r['response_registered'],
			'RESPONSE_COMMENTS' => nl2br($r['response_comments']),
			'RESPONSE_BILLED' => yes_no($r['response_billed']),
			'RESPONSE_BILLED_AMOUNT' => number_format($r['response_billed_amount'], 2),
			'RESPONSE_GRANT' => $grant_name,
			'RESPONSE_GRANT_AMOUNT' => number_format($r['response_grant_amount'], 2),
			'RESPONSE_FREE_EVENT' => yes_no($r['response_free_event']),
			'RESPONSE_UNRESTRICTED_GRANT' => yes_no($r['response_unrestricted_grant']),
			'RESPONSE_RESTRICTED_GRANT' => yes_no($r['response_restricted_grant']),
			'RESPONSE_LIST' => $response_list,
			'EDIT_EVALUATION_BUTTON' => $edit_evaluation_button
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
		
		unset($response_list, $r, $grant_name, $d, $m);
	} elseif ( $do == 'editeval' ) {
		// We want to edit an evaluation
		
		// Ensure only RD's from this region and Admins can see this
		if ( !is_numeric($responseid) || $responseid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		// This is done to retrieve the Region ID
		$r = get_response_data($responseid);
						
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $r['response_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array('program_tracking.php?do=vieweval&responseid=' . $responseid, $lang['Program_tracking_finished_evaluation']);
		$pagination[] = array(NULL, $lang['Program_tracking_edit_evaluation']);
			
		$e = get_event_data($r['response_event_id']);
		
		// Audience types
		$ai = array();
		$av = array();
		make_response_array(RESPONSE_AUDIENCE, $ai, $av);
		
		// Primary Foci
		$fi = array();
		$fv = array();
		make_response_array(RESPONSE_FOCUS, $fi, $fv);
		
		// Program list/types
		$si = array();
		$sv = array();
		get_programs($si, $sv);
		
		// Lotsa variables!!!
		// This has to do with whether or not the evaluation can be edited
		
		// Find the timestamp of the last day of the month
		$num_days = date('t', CCCSTIME);	// Number of days in the month
		$current_month = date('n', CCCSTIME);	// Month number
		$current_year = date('Y', CCCSTIME);	// Year number
		
		$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
		$last_day = mktime(59, 59, 23, $current_month, $num_days, $current_year);
		$five_days = 60 * 60 * 24 * 5;
		
		// Ok, Admins can edit evaluations at ANY TIME, however, RD's can only edit
		// evaluations 5 days AFTER the end of the month (or anytime during the
		// actual month, just before month's end).
		if ( ( ($e['event_end_date']+$five_days) > $last_day && $e['event_start_date'] > $first_day ) || ( $e['event_end_date'] < $last_day && $e['event_start_date'] > $first_day ) || $usercache['user_type'] == ADMINISTRATOR ) {
			// Make the list of grants 
			//print $r['response_grant_id'];
			$gi = array();
			$gv = array();
			make_grant_list($r['response_region_id'], $gi, $gv, true);
			$grant_list = make_drop_down('response_grant_id', $gi, $gv, $r['response_grant_id'], 'onchange="fill_zero(\'grant-list\', \'grant-amount\')"', 'id="grant-list"');
						
			$event_status_list = make_drop_down('response_status', array(1,0), $lang['Event_status'], $r['response_event_status'], 'onchange="fill_zero(\'response-status\', \'response-attendance\')"', 'id="response-status"');
			$event_audience_list = make_drop_down('response_audience', $ai, $av, $r['response_audience']);
			$event_primary_focus_list = make_drop_down('response_primary_focus', $fi, $fv, $r['response_primary_focus']);
			$event_subject_list = make_drop_down('response_subject', $si, $sv, $r['response_subject']);
						
			$response_attendance_box = make_input_box(TEXT, 'response_attendance', $r['response_attendance'], 'size="3" maxlength="4" id="response-attendance"');
			$response_comments_box = make_textarea('response_comments', 4, 50, stripslashes($r['response_comments']) );
			$response_billed_box = make_input_box(TEXT, 'response_billed_amount', $r['response_billed_amount'], 'size="3" maxlength="10" id="billed-amount"');
			$response_grant_box = make_input_box(TEXT, 'response_grant_amount', $r['response_grant_amount'], 'size="3" maxlength="10" id="grant-amount"');
			
			// Do the checkboxes, much better than the YES/NO radio buttons! Nice!
			$response_billed_checked = ( $r['response_billed'] == 1 ? 'checked="checked"' : NULL);
			$response_free_event_checked = ( $r['response_free_event'] == 1 ? 'checked="checked"' : NULL);
			$response_unrestricted_grant_checked = ( $r['response_unrestricted_grant'] == 1 ? 'checked="checked"' : NULL);
			$response_restricted_grant_checked = ( $r['response_restricted_grant'] == 1 ? 'checked="checked"' : NULL);
			
			$response_billed_checkbox = make_input_box(CHECKBOX, 'response_billed', 1, $response_billed_checked);
			$response_free_event_checkbox = make_input_box(CHECKBOX, 'response_free_event', 1, $response_free_event_checked);
			$response_unrestricted_grant_checkbox = make_input_box(CHECKBOX, 'response_unrestricted_grant', 1, $response_unrestricted_grant_checked);
			$response_restricted_grant_checkbox = make_input_box(CHECKBOX, 'response_restricted_grant', 1, $response_restricted_grant_checked);
		} else {
			$grant_list = ( $r['response_grant_id'] > 0 ? get_grant_organization($r['response_grant_id']) : $lang['No'] );
			$event_status_list = $lang['Event_status'][ !$r['response_event_status'] ];
			$event_audience_list = $av[ $r['response_audience'] ];
			$event_primary_focus_list = $fv[ ($r['response_primary_focus'] - 1) ];
			$event_subject_list = $sv[ ($r['response_subject'] - 1 ) ];
			
			$response_attendance_box = $r['response_attendance'];
			$response_comments_box = $r['response_comments'];
			$response_billed_box = $r['response_billed_amount'];
			$response_grant_box = $r['response_grant_amount'];
			
			$response_billed_checkbox = ( $r['response_billed'] == 1 ? $lang['Yes'] : $lang['No'] );
			$response_free_event_checkbox = ( $r['response_free_event'] == 1 ? $lang['Yes'] : $lang['No'] );
			$response_unrestricted_grant_checkbox = ( $r['response_unrestricted_grant'] == 1 ? $lang['Yes'] : $lang['No'] );
			$response_restricted_grant_checkbox = ( $r['response_restricted_grant'] == 1 ? $lang['Yes'] : $lang['No'] );
		}
		
		// Now start to load the question list
		// Now retrieve a list of question from the DB
		// for the second half of the evaluation.
		
		// This pre/post test information can be edited
		// at any time since basically no one fills it out
		// and its not used for reporting information.
		$sql = "SELECT rq.question_text, rq.question_answer, rq.question_type 
				FROM `" . RESPONSE_QUESTION . "` rq";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

		$d = array();
		$d = unserialize($r['response_data']);
		
		$x = 0;
		$question_id = 0;
		
		$p = array($lang['Program_tracking_pretest'], $lang['Program_tracking_posttest']);
		while ( $q = $db->getarray($result) ) {
			$t->set_template( load_template('program_tracking_finish_eval_question') );
			$t->set_vars( array(
				'QUESTION_TEXT' => $q['question_text']
				)
			);
			$question_list .= $t->parse($dbconfig['show_template_name']);
			
			$m = unserialize($d[$x]);
		
			if ( $q['question_type'] == 'text' ) {
				$a = array();
				$a = unserialize($q['question_answer']);
				
				for ( $i=0; $i<count($m); $i++ ) {
					$t->set_template( load_template('program_tracking_finish_eval_question_response') );
					$t->set_vars( array(
						'QUESTION_RESPONSE' => $a[$i],
						'QUESTION_ID' => $question_id,
						'QUESTION_RESPONSE_ID' => $i,
						'QUESTION_VALUE' => $m[$i],
						'QUESTION_REQUIRED_STAR' => '*'
						)
					);
					$question_list .= $t->parse($dbconfig['show_template_name']);
				}
			} else {
				// Seperate incremental operator
				// from $i since we have to increment it
				// every line as well, can't use $i + $i and
				// $i + ++$i because ++$i is a ternary operation.
				
				// See explanation above.
				$z = 0;

				for ( $i=0; $i<count($p); $i++ ) {
					$t->set_template( load_template('program_tracking_finish_eval_question_true_false') );
					$t->set_vars( array(
						'L_TRUE' => $lang['True'],
						'L_FALSE' => $lang['False'],
						'QUESTION_RESPONSE' => $p[$i],
						'QUESTION_ID' => $question_id,
						'QUESTION_RESPONSE_TRUE' => $i + $z,
						'QUESTION_RESPONSE_FALSE' => ($i + ($z+1) ),
						'QUESTION_TRUE_VALUE' => $m[ $i + $z ],
						'QUESTION_FALSE_VALUE' => $m[ $i + (++$z) ],
						)
					);
					$question_list .= $t->parse($dbconfig['show_template_name']);
				}
			}

			$x++;
			$question_id++;
		}
		
		$t->set_template( load_template('program_tracking_edit_eval_form', false) );
		$t->set_vars( array(
			'L_EDIT_EVALUATION' => $lang['Program_tracking_edit_evaluation'],
			'L_EVENT_STATUS' => $lang['Program_tracking_event_status'],
			'L_EVENT_AUDIENCE' => $lang['Program_tracking_event_audience'],
			'L_PRIMARY_FOCUS' => $lang['Program_tracking_primary_focus'],
			'L_EVENT_SUBJECT' => $lang['Program_tracking_subject_subject'],
			'L_ATTENDANCE' => $lang['Program_tracking_attendance'],
			'L_COMMENTS' => $lang['Program_tracking_comments'],
			'L_RESPONSE_BILLED' => $lang['Program_tracking_workshop_billed'],
			'L_RESPONSE_BILLED_AMOUNT' => $lang['Program_tracking_how_much_billed'],
			'L_RESPONSE_GRANT' => $lang['Program_tracking_who_grant'],
			'L_RESPONSE_GRANT_AMOUNT' => $lang['Program_tracking_how_much_grant'],
			'L_RESPONSE_FREE_EVENT' => $lang['Program_tracking_free_event'],
			'L_RESPONSE_UNRESTRICTED_GRANT' => $lang['Program_tracking_unrestricted_grant'],
			'L_RESPONSE_RESTRICTED_GRANT' => $lang['Program_tracking_restricted_grant'],
			'L_UPDATE_EVALUATION' => $lang['Program_tracking_edit_evaluation'],
			'RESPONSE_ID' => $responseid,
			'RESPONSE_EVENT_STATUS_LIST' => $event_status_list,
			'RESPONSE_EVENT_AUDIENCE_LIST' => $event_audience_list,
			'RESPONSE_PRIMARY_FOCUS_LIST' => $event_primary_focus_list,
			'RESPONSE_EVENT_SUBJECT_LIST' => $event_subject_list,
			'RESPONSE_ATTENDANCE_BOX' => $response_attendance_box,
			'RESPONSE_COMMENTS_BOX' => $response_comments_box,
			'RESPONSE_BILLED_CHECKBOX' => $response_billed_checkbox,
			'RESPONSE_BILLED_BOX' => $response_billed_box,
			'RESPONSE_GRANT_BOX' => $response_grant_box,
			'RESPONSE_FREE_EVENT_CHECKBOX' => $response_free_event_checkbox . ' | ',
			'RESPONSE_UNRESTRICTED_GRANT_CHECKBOX' => $response_unrestricted_grant_checkbox . ' | ',
			'RESPONSE_RESTRICTED_GRANT_CHECKBOX' => $response_restricted_grant_checkbox . ' | ',
			'GRANT_LIST' => $grant_list,
			'QUESTION_LIST' => $question_list
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	} else {
		$page = load_page('program_tracking');
		$content = parse_page( $page['page_text'] );
	}
}

if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	if ( $do == 'starteval' ) {
		// Start phase 1 of the post event evaluation. This is filled out by all.
		$pagination[] = array('program_tracking.php?do=completeevents', $lang['Completed_no_eval']);
		$pagination[] = array(NULL, $lang['Post_event_info']);
		
		$incoming = collect_vars($_POST, array('response_status' => INT, 'response_audience' => INT, 'response_primary_focus' => INT, 'response_subject' => INT, 'response_attendance' => MIXED, 'response_comments' => MIXED));
		extract($incoming);
		
		if ( !is_numeric($eventid) || $eventid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}
		
		// Make sure this response doesn't already exist
		// in case the user refreshes the page or something
		$sql = "SELECT * FROM `" . RESPONSE . "` r 
				WHERE r.response_event_id = '" . $eventid . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $db->numrows($result) > 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_response_exists']);
		}
				
		$e = array();
		$e = get_event_data($eventid);
		
		$regionid = $e['event_region_id'];
		unset($e);
		
		$response_attendance = str_replace(',', '', $response_attendance);
		
		$response_audience = ( $response_audience == 0 ? 1 : $response_audience );
		$response_primary_focus = ( $response_primary_focus == 0 ? 1 : $response_primary_focus );
		$response_subject = ( $response_subject == 0 ? 1 : $response_subject );
		
		
		// Retrieve the number of people who registered for this event.
		// This is calculated automatically rather than a form field
		// because public events allow registration, and private events
		// registration is tracked by the RD or Volunteer teaching the
		// event. Also, in order to be fully registered for a class,
		// the person must've been included on the fulfillment report.
		$sql = "SELECT COUNT(rq.queue_id) AS num_registered FROM `" . REGISTER_QUEUE . "` rq 
				WHERE rq.queue_event_id = '" . $eventid . "' 
					AND rq.queue_authorized = '1' 
					AND rq.queue_fullfilled = '1'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$row = $db->getarray($result) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_data']);
		$db->freeresult($result);
		
		$response_registered = $row['num_registered'];
		
		$sql = "INSERT INTO `" . RESPONSE . "`(response_id, response_event_id, response_region_id, 
					response_completed, response_event_status, response_audience, response_primary_focus, 
					response_subject, response_attendance, response_registered, response_comments)
				VALUES(NULL, '" . $eventid . "', '" . $regionid . "', '0', '" . $response_status . "', 
					'" . $response_audience . "', '" . $response_primary_focus . "', 
					'" . $response_subject . "', '" . $response_attendance . "', '" . $response_registered . "',
					'" . $response_comments . "')";
		
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		// Email all of the RD's telling them its their turn now
		if ( $dbconfig['send_email'] == 1 ) {
			$rds = array();
			$rds = get_region_directors($regionid);
			
			for ( $i=0; $i<count($rds); $i++ ) {
				$email_text = sprintf($lang['Email_program_tracking_message'], ($rds[$i]['user_first_name'] . ' ' . $rds[$i]['user_last_name']) );
				send_email($rds[$i]['user_email'], $lang['Email_program_tracking_subject'], $email_text);
			}
		}

		$content = make_content($lang['Program_tracking_save_evaluation_thank_you']);
	} elseif ( $do == 'finisheval' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( !is_numeric($responseid) || $responseid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}

		// This is done to get the Region ID
		$r = get_response_data($responseid);
		$eventid = $r['response_event_id'];
					
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $r['response_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
				
		$pagination[] = array('program_tracking.php?do=completeevents', $lang['Completed_no_eval']);
		$pagination[] = array(NULL, $lang['Post_event_info']);
						
		$incoming = collect_vars($_POST, array('response_billed' => INT, 'response_billed_amount' => MIXED, 'response_grant_id' => INT, 'response_grant_amount' => MIXED, 'response_free_event' => INT, 'response_unrestricted_grant' => INT, 'response_restricted_grant' => INT));
		extract($incoming);
		
		$response_billed_amount = preg_replace('/(,|\$)/', NULL, $response_billed_amount);
		$response_grant_amount = preg_replace('/(,|\$)/', NULL, $response_grant_amount);
		
		// Force it to be 1 if they somehow sent a value
		$response_billed = ( $response_billed_amount > 0 ? 1 : 0 );
		
		// We have a grant ID, ensure that the grant amount to use is not greater than the grant balance.
		// Always do this before the actual UPDATE because then an incorrect grant balance will be reflected!
		if ( $response_grant_id > 0 ) {
			$grant_balance = 0;
			$grant_balance = get_grant_balance($response_grant_id);
		}
		
		$r = array();
		$a = array();
		
		// Now compile the results
		// Wasn't that a lot friggin better than the old way? Ha!
		// Building all of the subarrays, serializing them, and then
		// pushing them into the big array and serializing that.
		for ( $i=0; $i<count($_POST['response']); $i++ ) {
			for ( $j=0; $j<count($_POST['response'][$i]); $j++ ) {
				$v = intval($_POST['response'][$i][$j]);
				if ( empty($v) || $v < 0 ) {
					$v = 0;
				}
				array_push($a, $v);
			}
			
			$r[] = serialize($a);
			$a = array();
		}
		
		$r = serialize($r);
		
		$sql = "UPDATE `" . RESPONSE . "` SET 
					response_completed = '1', response_billed = '" . $response_billed . "', 
					response_billed_amount = '" . $response_billed_amount . "', 
					response_grant_id = '" . $response_grant_id . "', response_grant_amount = '" . $response_grant_amount . "', 
					response_free_event = '" . $response_free_event . "', response_unrestricted_grant = '" . $response_unrestricted_grant . "', 
					response_restricted_grant = '" . $response_restricted_grant . "', response_data = '" . $r . "'
				WHERE response_id = '" . $responseid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$sql = "UPDATE `" . EVENT . "` SET event_complete = '1' WHERE event_id = '" . $eventid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		if ( $response_grant_id > 0 && $response_grant_amount > $grant_balance ) {
			$sql = "UPDATE `" . RESPONSE . "` SET 
						response_grant_id = '0', response_grant_amount = '0' 
					WHERE response_id = '" . $responseid . "'";
			$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
			
			cccs_message(WARNING_MESSAGE, $lang['Error_grant_amount_less_than_grant_balance']);
		}
		
		$content = make_content($lang['Program_tracking_finish_evaluation_thank_you']);
		
		unset($a, $r, $sql);
	} elseif ( $do == 'editeval' ) {
		// Ensure only RD's from this region and Admins can see this
		if ( !is_numeric($responseid) || $responseid <= 0 ) {
			cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
		}

		// This is done to get the Region ID
		$r = get_response_data($responseid);
						
		// Ensure only RD's from this region and Admins can see this
		if ( ( $usercache['user_type'] == REGIONAL_DIRECTOR && $usercache['user_region_id'] != $r['response_region_id'] ) 
			|| $usercache['user_type'] >= VOLUNTEER_STAFF ) {
			
			cccs_message(WARNING_CRITICAL, $lang['Error_user_level']);
		}
		
		$pagination[] = array('program_tracking.php?do=editeval&responseid=' . $responseid, $lang['Program_tracking_finished_evaluation']);
		$pagination[] = array(NULL, $lang['Program_tracking_edit_evaluation']);
				
		$incoming = collect_vars($_POST, array('response_status' => INT, 'response_audience' => INT, 'response_primary_focus' => INT, 'response_subject' => INT, 'response_attendance' => MIXED, 'response_comments' => MIXED, 'response_billed' => INT, 'response_billed_amount' => MIXED, 'response_grant_id' => INT, 'response_grant_amount' => MIXED, 'response_free_event' => INT, 'response_unrestricted_grant' => INT, 'response_restricted_grant' => INT));
		extract($incoming);
		
		$response_billed_amount = preg_replace('/(,|\$)/', NULL, $response_billed_amount);
		$response_grant_amount = preg_replace('/(,|\$)/', NULL, $response_grant_amount);
		
		// Force it to be 1 if they somehow sent a value
		if ( $response_billed_amount > 0 ) {
			$response_billed = 1;
		} else {
			$response_billed = 0;
		}
		
		// Force this to zero if the status was cancelled. This
		// prevents them from coming back and editing a cancelled
		// classes audience if they did that for some reason.
		if ( $response_status == 0 ) {
			$response_attendance = 0;
		}
		
		$r = array();
		$a = array();
		
		// Now compile the results
		// Wasn't that a lot friggin better than the old way? Ha!
		for ( $i=0; $i<count($_POST['response']); $i++ ) {
			for ( $j=0; $j<count($_POST['response'][$i]); $j++ ) {
				$v = intval($_POST['response'][$i][$j]);
				if ( empty($v) || $v < 0 ) {
					$v = 0;
				}
				array_push($a, $v);
			}
			
			$r[] = serialize($a);
			$a = array();
		}
		
		$r = serialize($r);
		
		// Ensure these are something other than 0 so that the system
		// doesn't throw up when an RD wants to view an evaluation
		$response_audience = ( $response_audience == 0 ? 1 : $response_audience );
		$response_primary_focus = ( $response_primary_focus == 0 ? 1 : $response_primary_focus );
		$response_subject = ( $response_subject == 0 ? 1 : $response_subject );
		
		$sql = "UPDATE `" . RESPONSE . "` SET 
					response_completed = '1', response_event_status = '" . $response_status . "',
					response_audience = '" . $response_audience . "', response_primary_focus = '" . $response_primary_focus . "',
					response_subject = '" . $response_subject . "', response_attendance = '" . $response_attendance . "', 
					response_comments = '" . $response_comments . "', response_billed = '" . $response_billed . "', 
					response_billed_amount = '" . $response_billed_amount . "', 
					response_grant_id = '" . $response_grant_id . "', response_grant_amount = '" . $response_grant_amount . "', 
					response_free_event = '" . $response_free_event . "', response_unrestricted_grant = '" . $response_unrestricted_grant . "', 
					response_restricted_grant = '" . $response_restricted_grant . "', response_data = '" . $r . "'
				WHERE response_id = '" . $responseid . "'";
		$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$content = make_content($lang['Program_tracking_update_evaluation_thank_you']);
		
		unset($a, $r, $sql);
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