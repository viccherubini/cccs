<?php

/**
 * invoice.php
 * Start page for all of the website.
 *
 * "Oh, Debbay!" - Artie Lange, THSS
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

// See if they are a volunteer staff member and somehow managed to get a session set.
// If so, tell them and log them out
can_view(VOLUNTEER_STAFF);

$content_title = make_title($lang['Title_welcome'], false);
$content_subtitle = make_title($lang['Subtitle_welcome'], true);

// The page header isn't included because we don't need everything
// it prints out, so this code is copied from there.
include $root_path . 'includes/page_printheader.php';

if ( $_SERVER['REQUEST_METHOD'] == GET ) {
	$incoming = collect_vars($_GET, array('do' => MIXED, 'eventid' => INT, 'type' => MIXED));
	extract($incoming);
	
	if ( !is_numeric($eventid) || $eventid <= 0 ) {
		cccs_message(WARNING_MESSAGE, $lang['Error_bad_id']);
	}
	
	// Show them the invoice!
	if ( $do == 'invoice' ) {
		$sql = "SELECT * FROM `" . EVENT . "` e 
				LEFT JOIN `" . PROGRAM . "` p
					ON e.event_program_id = p.program_id
				LEFT JOIN `" . RESPONSE . "` r 
					ON e.event_id = r.response_event_id 
				WHERE e.event_id = '" . $eventid . "'";
		$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
		
		$e = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);
		
		$invoice_unit_cost = 0;
		
		// If the invoice was a billed event, just print out how
		// much the event was for, else, print out the
		// grant information for record keeping.
		if ( $type == 'billed' ) {
			$invoice_unit_cost = $e['response_billed_amount'];
			$invoice_to = $e['event_contact_organization'];
			$invoice_to_address = $e['event_contact_address'];
			$invoice_to_city = $e['event_contact_city'];
			$invoice_to_state = $e['event_contact_state'];
			$invoice_to_zip_code = $e['event_contact_zip_code'];
		} elseif ( $type == 'grant' ) {
			$invoice_unit_cost = $e['response_grant_amount'];
					
			$g = get_grant_data($e['response_grant_id']);
			
			$invoice_to = $g['grant_organization'];
			$invoice_to_address = $g['grant_address'];
			$invoice_to_city = $g['grant_city'];
			$invoice_to_state = $g['grant_state'];
			$invoice_to_zip_code = $g['grant_zip_code'];
		}
		
		$t->set_template( load_template('invoice_item') );
		$t->set_vars( array(
			'INVOICE_DATE' => date($dbconfig['date_format'], $e['event_start_date']),
			'INVOICE_QUANTITY' => 1,
			'INVOICE_EVENT_ID' => $e['event_id'],
			'INVOICE_DESCRIPTION' => stripslashes($e['program_name']),
			'INVOICE_UNIT_COST' => number_format($invoice_unit_cost, 2),
			'INVOICE_TOTAL' => number_format($invoice_unit_cost, 2)
			)
		);
		$invoice_list = $t->parse($dbconfig['show_template_name']);
		$invoice_total = $invoice_unit_cost;
		
		$u = get_user_data($usercache['user_id']);
		if ( !empty($u['user_location_home_address_two']) ) {
			$address = $u['user_location_home_address_one'] . '<br />' . $u['user_location_home_address_two'];
		} else {
			$address = $u['user_location_home_address_one'];
		}
		
		$payment_address = $u['user_first_name'] . ' ' . $u['user_last_name'] . '<br />' . $u['user_job_title'] . '<br />' . $address . '<br />';
		$payment_address .= $u['user_location_city'] . ', ' . $u['user_location_state'] . ' ' . $u['user_location_zip_code'];
		
		if ( $e['event_region_id'] > 0 ) {
			$r = array();
			$r = get_region_data($e['event_region_id']);
			$taxpayer_number = $r['region_taxnumber'];
		} else {
			$taxpayer_number = $lang['Invoice_taxpayer_number'];
		}
		
		$t->set_template( load_template('invoice') );
		$t->set_vars( array(
			'L_DIVISION' => $lang['Invoice_division'],
			'L_TO' => $lang['To'],
			'L_DATE' => $lang['Date'],
			'L_ATTENTION' => $lang['Invoice_attention'],
			'L_TAXPAYER_NO' => $lang['Invoice_taxypayer_no'],
			'L_QUANTITY' => $lang['Invoice_quantity'],
			'L_EVENT_ID' => $lang['Event_event_id'],
			'L_DESCRIPTION' => $lang['Invoice_description'],
			'L_UNIT_COST' => $lang['Invoice_unit_cost'],
			'L_TOTAL' => $lang['Report_total'],
			'L_BALANCE' => $lang['Invoice_balance'],
			'L_MAKE_CHECKS_TO' => $lang['Invoice_make_checks_to'],
			'L_REMIT_PAYMENTS' => $lang['Invoice_remit_payments'],
			'L_INVOICE_FOOTER' => $lang['Invoice_footer'],
			'INVOICE_REGION_NAME' => get_region_name($e['event_region_id']),
			'INVOICE_TO' => $invoice_to,
			'INVOICE_TO_ADDRESS' => $invoice_to_address,
			'INVOICE_TO_CITY' => $invoice_to_city,
			'INVOICE_TO_STATE' => $invoice_to_state,
			'INVOICE_TO_ZIP_CODE' => $invoice_to_zip_code,
			'INVOICE_DATE' => date($dbconfig['date_format'], CCCSTIME ),
			'INVOICE_ATTENTION' => $e['event_contact_name'],
			'TAXPAYER_NUMBER' => $taxpayer_number,
			'INVOICE_LIST' => $invoice_list,
			'INVOICE_BALANCE' => number_format($invoice_total, 2),
			'INVOICE_PAYMENT_ADDRESS' => $payment_address
			)
		);
		$content = $t->parse($dbconfig['show_template_name']);
	}
}

// Now we collect the information from the database for filling out this page
$pagination[] = array(NULL, $lang['Welcome']);

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

$t->set_template( load_template('overall_body') );
$t->set_vars( array(
	'BODY_CONTENT' => $content,
	'BODY_COPYRIGHT' => NULL
	)
);
print $t->parse($dbconfig['show_template_name']);

include $root_path . 'includes/page_exit.php';

?>