<?php

/**
 * report.php
 * Page for making all of those pretty reports.
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

$content_title = make_title($lang['Title_report'], false);
$content_subtitle = NULL;

// Pagination data
$pagination[] = array('usercp.php', $lang['Control_panel']);	
$pagination[] = array('report.php', $lang['Report']);

// See if they are a volunteer and somehow managed to get a session set.
// If so, tell them and log them out.
// RD's can make reports of other regions, so this
// is the only security needed on this page.
can_view(VOLUNTEER);

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$incoming = collect_vars($_GET, array('do' => MIXED, 'report' => MIXED, 'sort' => MIXED, 'sort_field' => MIXED, 'report_region_id' => INT, 'report_start_month' => INT, 'report_start_day' => INT, 'report_start_year' => INT, 'report_end_month' => INT, 'report_end_day' => INT, 'report_end_year' => INT, 'report_status' => INT, 'report_revenue_type' => MIXED, 'report_volunteer_id' => MIXED, 'report_volunteer_type' => MIXED, 'report_event_title' => MIXED, 'report_start_date' => MIXED, 'report_end_date' => MIXED, 'eventid' => INT));
	extract($incoming);

	// Make this here so we don't have to pass around the individual date values	
	if ( empty($report_start_date) && empty($report_end_date) ) {
		$report_start_date = mktime(0, 0, 0, intval($report_start_month), intval($report_start_day), intval($report_start_year));
		$report_end_date = mktime(23, 59, 59, intval($report_end_month), intval($report_end_day), intval($report_end_year));
	}
			
	if ( $do == 'print' ) {
		include $root_path . 'includes/page_printheader.php';
		
		$sort = strtoupper($sort);
	
		if ( empty($sort) || ($sort != 'ASC' && $sort != 'DESC') ) {
			$sort = 'ASC';
		}

		if ( $report == 'report_volunteers' ) {
			if ( empty($sort_field) || ($sort_field != 'name' && $sort_field != 'hours') ) {
				$sort_field = 'name';
			}
			
			$content = create_volunteer_report($report_region_id, $report_start_date, $report_end_date, $sort, $sort_field, true);
		} elseif ( $report == 'report_nfcc' ) {
			$content = create_nfcc_report($report_start_date, $report_end_date, $report_region_id, true);
		} elseif ( $report == 'report_workshop' ) {
			$content = create_workshop_stat_report($report_status, $report_start_date, $report_end_date, $report_revenue_type, $report_volunteer_id, $report_volunteer_type, $report_event_title, $report_region_id, $sort, $sort_field, true);
		} elseif ( $report == 'report_accounting' ) {
			$content = create_accounting_report($report_start_date, $report_end_date, $report_region_id, true);
		} elseif ( $report == 'report_monthly' ) {
			$content = create_printable_monthly_report($report_region_id);
		}
		
		$t->set_template( load_template('overall_body') );
		$t->set_vars( array(
			'BODY_CONTENT' => $content,
			'BODY_COPYRIGHT' => NULL
			)
		);
		print $t->parse($dbconfig['show_template_name']);
	} elseif ( $do == 'report' ) {
		include $root_path . 'includes/page_header.php';
		
		if ( $report == 'report_volunteers' ) {
			$pagination[] = array(NULL, $lang['Report_volunteers']);
			$content_pagination = make_pagination($pagination);
			
			$content = make_content($lang['Report_volunteers_innacurate']);
				
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content_subtitle = make_title($lang['Title_workshop_volunteers_in_region'], true);
			
			$sort = strtoupper($sort);
			
			if ( empty($sort) || ($sort != 'ASC' && $sort != 'DESC') ) {
				$sort = 'ASC';
			}
			
			if ( empty($sort_field) || ($sort_field != 'name' && $sort_field != 'hours') ) {
				$sort_field = 'name';
			}
			
			$content .= create_volunteer_report($report_region_id, $report_start_date, $report_end_date, $sort, $sort_field, false);
		} elseif ( $report == 'report_nfcc' ) {
			$pagination[] = array(NULL, $lang['Report_nfcc_info_report']);
			$content_pagination = make_pagination($pagination);
			
			$content_subtitle = make_title($lang['Title_workshop_nfcc_info_report'], true);		
			
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content = create_nfcc_report($report_start_date, $report_end_date, $report_region_id);
		} elseif ( $report == 'report_workshop' ) {
			$pagination[] = array(NULL, $lang['Report_workshop_stat_report']);
			$content_pagination = make_pagination($pagination);
		
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content_subtitle = make_title($lang['Title_workshop_stat_report'], true);
			
			if ( empty($sort) || ($sort != 'asc' && $sort != 'desc') ) {
				$sort = 'asc';
			}
			
			if ( empty($sort_field) || ($sort_field != 'date' && $sort_field != 'billed' && $sort_field != 'donation' ) ) {
				$sort_field = 'date';
			}
					
			$content = create_workshop_stat_report($report_status, $report_start_date, $report_end_date, $report_revenue_type, $report_volunteer_id, $report_volunteer_type, $report_event_title, $report_region_id, $sort, $sort_field);
		} elseif ( $report == 'report_accounting' ) {
			$pagination[] = array(NULL, $lang['Report_accounting_report']);
			$content_pagination = make_pagination($pagination);
			
			if ( !validate_date($report_start_month, $report_start_day, $report_start_year) ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_date']);
			}
			
			if ( !validate_date($report_end_month, $report_end_day, $report_end_year) ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_date']);
			}
			
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content = create_accounting_report($report_start_date, $report_end_date, $report_region_id);
		} elseif ( $report == 'view_accounting_report' ) {
			$pagination[] = array(NULL, $lang['Report_accounting_report']);
			$content_pagination = make_pagination($pagination);
			
			if ( !is_numeric($eventid) || $eventid <= 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
	
			$content = create_final_accounting_report($eventid, false);
		} elseif ( $report == 'report_monthly' ) {
			$pagination[] = array(NULL, $lang['Report_monthly_report']);
			$content_pagination = make_pagination($pagination);
			
			$content = create_monthly_report($report_region_id);
		} elseif ( $report == 'report_hourly' ) {
			$pagination[] = array(NULL, $lang['Report_hourly_report']);
			$content_pagination = make_pagination($pagination);
		
			$content = create_hourly_report($report_region_id, $report_start_date, $report_end_date);
		} elseif ( $report == 'report_quality_assurance' ) {
			$pagination[] = array(NULL, $lang['Report_quality_assurance_report']);
			$content_pagination = make_pagination($pagination);
			
			if ( !validate_date($report_start_month, $report_start_day, $report_start_year) ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_date']);
			}
			
			if ( !validate_date($report_end_month, $report_end_day, $report_end_year) ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_date']);
			}
			
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content = create_quality_assurance_report($report_region_id, $report_volunteer_id, $report_start_date, $report_end_date);
		} elseif ( $report == 'report_income' ) {
			$pagination[] = array(NULL, $lang['Report_income']);
			$content_pagination = make_pagination($pagination);
			
			if ( !is_numeric($report_region_id) || $report_region_id < 0 ) {
				cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
			}
			
			$content_subtitle = make_title($lang['Report_income'], true);

			$content .= create_income_report($report_region_id, $report_start_date, $report_end_date, false);
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
	} else {
		include $root_path . 'includes/page_header.php';
		
		$content_pagination = make_pagination($pagination);

		/**
		 * There are (as of yet) eight types of reports: NFCC Info Report,
		 * Volunteers in a Region, Workshop Stat Report, the Accounting Report,
		 * CSV/Excel Report, Monthly Report, Quality Assurance Report, and
		 * Income Report.
		*/
		
		$r_ids = array();
		$r_names = array();
		$r_ids[] = 0;
		$r_names[] = $lang['Regions_all_regions'];
		get_regions($r_ids, $r_names);
		
		$m_keys = array();
		$m_values = array();
		make_month_array($m_keys, $m_values);
		
		$start_month_list = make_drop_down('report_start_month', $m_keys, $m_values);
		$start_day_list = make_drop_down('report_start_day', make_day_array(), make_day_array() );
		$start_year_list = make_drop_down('report_start_year', make_year_array(), make_year_array() );
		
		$end_month_list = make_drop_down('report_end_month', $m_keys, $m_values, date("n", CCCSTIME) );
		$end_day_list = make_drop_down('report_end_day', make_day_array(), make_day_array(), date("j", CCCSTIME) );
		$end_year_list = make_drop_down('report_end_year', make_year_array(), make_year_array(), date("Y", CCCSTIME) );
		
		$region_list = make_drop_down('report_region_id', $r_ids, $r_names);
		
		$content = make_content($lang['Report_text']);
		
		// Start the NFCC Information Report
		$t->set_template( load_template('report_nfcc_report_form') );
		$t->set_vars( array(
			'L_NFCC_INFO_REPORT' => $lang['Report_nfcc_info_report'],
			'L_SHOW_EVENTS_FROM' => $lang['Report_show_events_from'],
			'L_TO' => $lang['To'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'L_GENERATE_NFCC_INFO_REPORT' => $lang['Report_generate_nfcc_info_report'],
			'REPORT_START_MONTH' => $start_month_list,
			'REPORT_START_DAY' => $start_day_list,
			'REPORT_START_YEAR' => $start_year_list,
			'REPORT_END_MONTH' => $end_month_list,
			'REPORT_END_DAY' => $end_day_list,
			'REPORT_END_YEAR' => $end_year_list,
			'REPORT_REGION_LIST' => $region_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the NFCC Information Report
		
		
		// Start the Volunteers Report
		$t->set_template( load_template('report_volunteers_report_form') );
		$t->set_vars( array(
			'L_VOLUNTEERS_IN_REGION' => $lang['Report_volunteers_in_region'],
			'L_SHOW_HOURS_FROM' => $lang['Report_show_hours_from'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'L_GENERATE_VOLUNTEERS_REPORT' => $lang['Report_generate_volunteers_report'],
			'REPORT_REGION_LIST' => $region_list,
			'REPORT_START_MONTH' => $start_month_list,
			'REPORT_START_DAY' => $start_day_list,
			'REPORT_START_YEAR' => $start_year_list,
			'REPORT_END_MONTH' => $end_month_list,
			'REPORT_END_DAY' => $end_day_list,
			'REPORT_END_YEAR' => $end_year_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the Volunteers Report
		
		
		// Start the CSV Report
		$t->set_template( load_template('report_csv_report_form') );
		$t->set_vars( array(
			'L_CSV_REPORT' => $lang['Report_csv_report'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'L_INCLUDE_CLIENTS' => $lang['Report_include_clients'],
			'L_GENERATE_CSV_REPORT' => $lang['Report_generate_csv_report'],
			'REPORT_REGION_LIST' => $region_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the CSV Report

		
		// Start the Workshop Stat Report
		array_unshift($lang['Event_status'], $lang['All']);
		$report_status_list = make_drop_down('report_status', array(0, 1, -1), $lang['Event_status']);
		
		$users = get_user_list($region_id);
		$ids = array($lang['All']);
		$names = array($lang['All']);
		
		for ( $i=0; $i<count($users); $i++ ) {
			$ids[] = $users[$i]['user_id'];
			$names[] = $users[$i]['user_first_name'] . ' ' . $users[$i]['user_last_name'] . ' (' . $users[$i]['user_email'] . ')';
		}
		
		$report_volunteer_list = make_drop_down('report_volunteer_id', $ids, $names);
		
		$p_ids = array();
		$p_names = array();
		get_programs($p_ids, $p_names, true, true);
		
		array_unshift($p_ids, 0);
		array_unshift($p_names, $lang['All']);
		$report_event_title_list = make_drop_down('report_event_title', $p_ids, $p_names);
		
		array_unshift($lang['Control_panel_volunteer_types'], $lang['All']);
		$report_volunteer_type_list = make_drop_down('report_volunteer_type', $lang['Control_panel_volunteer_types'], $lang['Control_panel_volunteer_types']);
		
		array_unshift($lang['Report_revenue_types'], $lang['All']);
		$report_revenue_list = make_drop_down('report_revenue_type', $lang['Report_revenue_types'], $lang['Report_revenue_types']);
		
		$t->set_template( load_template('report_workshop_stat_form') );
		$t->set_vars( array(
			'L_WORKSHOP_STAT_REPORT' => $lang['Report_workshop_stat_report'],
			'L_EVENT_STATUS' => $lang['Report_event_status'],
			'L_SHOW_EVENTS_FROM' => $lang['Report_show_events_from'],
			'L_REVENUE_TYPE' => $lang['Report_revenue_type'],
			'L_VOLUNTEER' => $lang['Report_volunteer'],
			'L_VOLUNTEER_TYPE' => $lang['Report_volunteer_type'],
			'L_EVENT_TITLE' => $lang['Report_event_title'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'L_GENERATE_WORKSHOP_STAT_REPORT' => $lang['Report_generate_workshop_stat_report'],
			'REPORT_STATUS_LIST' => $report_status_list,
			'REPORT_START_MONTH' => $start_month_list,
			'REPORT_START_DAY' => $start_day_list,
			'REPORT_START_YEAR' => $start_year_list,
			'REPORT_END_MONTH' => $end_month_list,
			'REPORT_END_DAY' => $end_day_list,
			'REPORT_END_YEAR' => $end_year_list,
			'REPORT_REGION_LIST' => $region_list,
			'REPORT_REVENUE_LIST' => $report_revenue_list,
			'REPORT_VOLUNTEER_LIST' => $report_volunteer_list,
			'REPORT_VOLUNTEER_TYPE_LIST' => $report_volunteer_type_list,
			'REPORT_TITLE_LIST' => $report_event_title_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the Workshop Stat Report
		
		
		// Start the Accounting Report
		$t->set_template( load_template('report_accounting_form') );
		$t->set_vars( array(
			'L_ACCOUNTING_REPORT' => $lang['Report_accounting_report'],
			'L_GENERATE_ACCOUNTING_REPORT' => $lang['Report_generate_accounting_report'],
			'L_SHOW_EVENTS_FROM' => $lang['Report_show_events_from'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'REPORT_START_MONTH' => $start_month_list,
			'REPORT_START_DAY' => $start_day_list,
			'REPORT_START_YEAR' => $start_year_list,
			'REPORT_END_MONTH' => $end_month_list,
			'REPORT_END_DAY' => $end_day_list,
			'REPORT_END_YEAR' => $end_year_list,
			'REPORT_REGION_LIST' => $region_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the Accounting Report
		
		
		array_shift($r_ids);
		array_shift($r_names);
		array_shift($r_ids);
		array_shift($r_names);
		$monthly_region_list = make_drop_down('report_region_id', $r_ids, $r_names, $usercache['user_region_id']);
		
		// Start the Monthly Report
		$t->set_template( load_template('report_monthly_report_form') );
		$t->set_vars( array(
			'L_MONTHLY_REPORT' => $lang['Report_monthly_report'],
			'L_GENERATE_MONTHLY_REPORT' => $lang['Report_generate_monthly_report'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'REPORT_REGION_LIST' => $monthly_region_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the Monthly Report
		
		
		/*
		if ( $usercache['user_type'] == ADMINISTRATOR ) {
			// Start Quality Assurance Report
			$t->set_template( load_template('report_quality_assurance_form') );
			$t->set_vars( array(
				'L_QUALITY_ASSURANCE_REPORT' => $lang['Report_quality_assurance_report'],
				'L_GENERATE_QUALITY_ASSURANCE_REPORT' => $lang['Report_generate_quality_assurance_report'],
				'L_SHOW_EVENTS_FROM' => $lang['Report_show_events_from'],
				'L_CFE_REGION' => $lang['Report_cfe_region'],
				'REPORT_START_MONTH' => $start_month_list,
				'REPORT_START_DAY' => $start_day_list,
				'REPORT_START_YEAR' => $start_year_list,
				'REPORT_END_MONTH' => $end_month_list,
				'REPORT_END_DAY' => $end_day_list,
				'REPORT_END_YEAR' => $end_year_list,
				'REPORT_REGION_LIST' => $region_list,
				'REPORT_VOLUNTEER_LIST' => $report_volunteer_list
				)
			);
			$content .= $t->parse($dbconfig['show_template_name']);
			// End Quality Assurance Report
		}
		*/
		
		
		// Start the Income Report
		$t->set_template( load_template('report_income_report_form') );
		$t->set_vars( array(
			'L_INCOME_REPORT' => $lang['Report_income'],
			'L_SHOW_INCOMES_AFTER' => $lang['Report_show_incomes_after'],
			'L_CFE_REGION' => $lang['Report_cfe_region'],
			'L_GENERATE_INCOME_REPORT' => $lang['Report_generate_income_report'],
			'REPORT_REGION_LIST' => $region_list,
			'REPORT_START_MONTH' => $start_month_list,
			'REPORT_START_DAY' => $start_day_list,
			'REPORT_START_YEAR' => $start_year_list
			)
		);
		$content .= $t->parse($dbconfig['show_template_name']);
		// End the Income Report
		
		
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
	}
}

include $root_path . 'includes/page_exit.php';

?>