<?php

/**
 * page_printheader.php
 * This file is included on every page and contains the code to
 * set up common attributes of the website such as the stylesheet,
 * header graphic, menu, and login area.
*/

if ( !defined('IN_CCCS') ) {
	exit;
}

if ( !empty($_SESSION) && $_SESSION['user_logged_in'] == true ) {
	$style = load_style();
	
	if ( empty($style) || empty($_SESSION['user_style_id']) ) {
		$style_font = $dbconfig['style_font'];
		$style_font_size = $dbconfig['style_font_size'];
		$style_color = $dbconfig['style_color'];
	} else {
		$style_font = $style['user_style_font'];
		$style_font_size = $style['user_style_font_size'];
		$style_color = $style['user_style_color'];
	}
} else {
	$style_font = $dbconfig['style_font'];
	$style_font_size = $dbconfig['style_font_size'];
	$style_color = $dbconfig['style_color'];
}

// Load up and print the stylesheet and starting HTML tags
$t->set_template( load_template('overall_header') );
$t->set_vars( array(
	'CONFIG_SITE_TITLE' => $dbconfig['site_title'],
	'CONFIG_STYLE_FONT' => $style_font,
	'CONFIG_STYLE_FONT_SIZE' => $style_font_size,
	'CONFIG_STYLE_COLOR' => $style_color,
	'CONFIG_STYLE_PUBLIC_EVENT' => $dbconfig['style_public_event'],
	'CONFIG_STYLE_PRIVATE_EVENT' => $dbconfig['style_private_event'],
	'PAGE_NAME' => $page_name,
	)
);
print $t->parse($dbconfig['show_template_name']);

?>