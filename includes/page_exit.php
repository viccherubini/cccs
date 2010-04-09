<?php

if ( !defined('IN_CCCS') ) {
	exit;
}

// Optimize all of the tables
// I've never encountered this before when you delete data to have to 
// optimize the table.
$sql = "SHOW TABLES";
$result = $db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);

while ( $table = $db->getarray($result) ) {
	$key = key($table);
	
	$sql = "OPTIMIZE TABLE `" . $table[$key] . "`";
	$db->dbquery($sql) or cccs_message(WARNING_CRITICAL, $lang['Error_failed_query'], __LINE__, __FILE__, $db->dberror(), $sql);
}

$db->freeresult($result);

if ( !($db->disconnect()) ) {
	cccs_message(ERROR_CRITICAL, $lang['Error_disconnect'], __LINE__, __FILE__, $db->dberror(), NULL);
}

// Free memory
$db = NULL;
$t = NULL;
unset($templatecache);

exit;
?>