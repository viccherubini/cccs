<?php

/**
 * download.php
 * Allows a user to download a file.
 *
*/

define('IN_CCCS', true, false);

error_reporting(E_PARSE | E_ERROR);

$root_path = './';
include $root_path . 'global.php';

$incoming = collect_vars($_GET, array('fileid' => INT));
extract($incoming);

// Ensure the ID given is correct and good
if ( !is_numeric($fileid) || $fileid <= 0 ) {
	cccs_message(WARNING_CRITICAL, $lang['Error_bad_id']);
}

$sql = "SELECT * FROM `" . DOWNLOAD . "` d 
		WHERE d.download_id = '" . $fileid . "'";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

$file = $db->getarray($result) or cccs_message(WARNING_MESSAGE, $lang['Error_failed_data']);

if ( $file['download_isprivate'] == 1 ) {
	// See if they are an applicant and somehow managed to get a session set.
	// If so, tell them and log them out
	can_view(APPLICANT);
}

// Ok, they've passed the test, they can download the file...
// Get the right header...
header('Content-type: ' . $file['download_mime_type']);

// Get the name of the file, Windows only...
$location = split("\/", $file['download_location']);
$file_name = $location[ count($location)-1 ];

// The file will be called what its called on the server
header('Content-Disposition: attachment; filename="' . $file_name . '"');

// Get the file
readfile($dbconfig['file_directory'] . '/' . $file['download_location']);

// Update the count
$sql = "UPDATE `" . DOWNLOAD . "` 
		SET download_count = download_count + 1 
			WHERE download_id = '" . $fileid . "'";
$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

include $root_path . 'includes/page_exit.php';

?>