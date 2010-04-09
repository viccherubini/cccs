<?php

if ( !defined('IN_CCCS') ) {
	exit;
}

// User levels, or user types definitions
define('GOD', 1, false);
define('ADMINISTRATOR', 100, false);
define('REGIONAL_DIRECTOR', 200, false);
define('VOLUNTEER_STAFF', 300, false);
define('VOLUNTEER', 400, false);
define('APPLICANT', 500, false);
define('DENIED', 600, false);

// Error definitions
define('ERROR_CRITICAL', 100, false);
define('ERROR_MESSAGE', 101, false);
define('WARNING_CRITICAL', 200, false);
define('WARNING_MESSAGE', 201, false);

// Table definitions
define('ARTICLE', 'cccs_article', false);
define('ASSIGNMENT', 'cccs_assignment', false);
define('CALENDAR', 'cccs_calendar', false);
define('CONFIG', 'cccs_configuration', false);
define('CUSTOM_PAGE', 'cccs_custom_page', false);
define('DID_YOU_KNOW', 'cccs_did_you_know', false);
define('DOWNLOAD', 'cccs_download', false);
define('EMAIL', 'cccs_email', false);
define('EVENT', 'cccs_event', false);
define('HOUR', 'cccs_hour', false);
define('GRANT', 'cccs_grant', false);
define('INCOME', 'cccs_income', false);
define('INCOME_HISTORY', 'cccs_income_history', false);
define('MESSAGE', 'cccs_message', false);
define('MESSAGE_TEXT', 'cccs_message_text', false);
define('NEWSLETTER', 'cccs_newsletter', false);
define('QUIZ' , 'cccs_quiz', false);
define('QUIZ_QUESTION', 'cccs_quiz_question', false);
define('PROGRAM', 'cccs_program', false);
define('REGION', 'cccs_region', false);
define('REGION_PAGE', 'cccs_region_page', false);
define('REGISTER_QUEUE', 'cccs_register_queue', false);
define('RESPONSE', 'cccs_response', false);
define('RESPONSE_AUDIENCE', 'cccs_response_audience', false);
define('RESPONSE_FOCUS', 'cccs_response_focus', false);
define('RESPONSE_QUESTION', 'cccs_response_question', false);
define('SESSION', 'cccs_session', false);
define('SPONSOR', 'cccs_sponsor', false);
define('STAFF', 'cccs_staff', false);
define('TEMPLATE', 'cccs_template', false);
define('USER', 'cccs_user', false);
define('USERGROUP', 'cccs_usergroup', false);

// Email definitions
define('ACCOUNTANT_EMAIL_BILLED', 'kim.vance@moneymanagement.org', false);
define('ACCOUNTANT_EMAIL_GRANT', 'helen.sosnowy@moneymanagement.org', false);
define('EMAIL_FROM_NAME', 'Centers', false);
define('EMAIL_FROM_ADDRESS', 'edu@moneymanagement.org', false);
define('REQUEST_MATERIALS', 'edu@moneymanagement.org', false);

define('MAPQUEST', 'http://mapquest.com', false);

// Language abbreviations
define('ENGLISH', 'en', false);

define('EVALS_PER_PAGE', 100, false);

// Whether or not we are in debug mode. 
define('IN_DEBUG', true, false);

// Public or private
define('EVENT_PUBLIC', 'public', false);
define('EVENT_PRIVATE', 'private', false);

// Newsletter
define('NEWSLETTER_LATEST', 'latest', false);
define('NEWSLETTER_ADD', 'add', false);

define('INT', 1, false);
define('MIXED', 2, false);

// Just feel uncomfortable hardcoding values into
// *EVERY* page
define('GET', 'GET', false);
define('POST', 'POST', false);

// Different form types
define('RADIO_BUTTON', 'radio', false);
define('CHECKBOX', 'checkbox', false);
define('TEXT', 'text', false);
define('TEXTAREA', 'textarea', false);
define('YN', 'yn', false);
define('TF', 'tf', false);
define('SUBMIT', 'submit', false);

// The meaning to life and the universe ;)
define('PROGRAM_TRADESHOW', 42, false);

// The actual PHP files
define('PAGE_BANKRUPTCY', 'bankruptcy.php', false);
define('PAGE_CALENDAR', 'calendar.php', false);
define('PAGE_CONFIG', 'config.php', false);
define('PAGE_CSV', 'csv.php', false);
define('PAGE_DOWNLOAD', 'download.php', false);
define('PAGE_EMAIL', 'email.php', false);
define('PAGE_EVENT', 'event.php', false);
define('PAGE_FULLFILLMENT', 'fullfillment.php', false);
define('PAGE_GLOBAL', 'global.php', false);
define('PAGE_GRANT', 'grant.php', false);
define('PAGE_INCOME', 'income.php', false);
define('PAGE_INDEX', 'index.php', false);
define('PAGE_INVOICE', 'invoice.php', false);
define('PAGE_LOGIN', 'login.php', false);
define('PAGE_MESSAGE', 'message.php', false);
define('PAGE_NEWSLETTER', 'newsletter.php', false);
define('PAGE_PAGE', 'page.php', false);
define('PAGE_PRINTEVENT', 'printevent.php', false);
define('PAGE_PRIVACY', 'privacy.php', false);
define('PAGE_PROGRAM_TRACKING', 'program_tracking.php', false);
define('PAGE_QUIZ', 'quiz.php', false);
define('PAGE_REGION', 'region.php', false);
define('PAGE_REGISTER', 'register.php', false);
define('PAGE_REPORT', 'report.php', false);
define('PAGE_REQUEST_MATERIALS', 'request_materials.php', false);
define('PAGE_REQUEST_PROGRAM', 'request_program.php', false);
define('PAGE_RETRIEVE_PASSWORD', 'retrieve_password.php', false);
define('PAGE_SEARCH', 'search.php', false);
define('PAGE_SITEMAP', 'sitemap.php', false);
define('PAGE_STAFF_LIST', 'staff_list.php', false);
define('PAGE_UNAUTHORIZED', 'unauthorized.php', false);
define('PAGE_USERCP', 'usercp.php', false);

include 'programs.php';
?>