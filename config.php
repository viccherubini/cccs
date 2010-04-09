<?php

/**
 * config.php
 * Contains commonly used configuration information for the website.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

$db_hostname = 'localhost';
$db_database = '';
$db_username = '';
$db_password = '';

$site_url = "localhost/";
$site_protocol = "http://";
$site_basedir = "cccs";

// Some default languages
$lang['Error_connection'] = "Failed to connect to the database";
$lang['Error_configuration'] = "Failed to load configuration settings";
$lang['Error_load_template'] = "Failed to load template %s";
$lang['Mysql_said'] = "MySQL said";
$lang['On'] = "on";
$lang['Line'] = "line";
$lang['In'] = "in";
$lang['File'] = "file";


// Colors and fonts and sizes
$site_colors = array('#7296d0', '#299b9b', '#a76969', '#d9afaf', '#1f7170', '#2a208a', '#9f269e', '#c02b2b', '#800000', '#ff9600', '#935600');
$site_colors_names = array('Default Style', 'Light Green', 'Salmon', 'Light Pink', 'Dark Green', 'Dark Blue', 'Purple', 'Red', 'Maroon', 'Orange', 'Brown');
$site_fonts = array('Arial', 'Verdana', 'Georgia', 'Times New Roman', 'Courier');
$site_font_sizes = array('10px', '11px', '12px', '13px');

?>