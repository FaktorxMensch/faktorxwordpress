<?php
function fxwp_change_footer_text ( $footer_text ) {
	// Edit the line below to customize the footer text.
//	$footer_text = 'Powered by <a href="https://www.wordpress.org" target="_blank" rel="noopener">WordPress</a> | WordPress Tutorials: <a href="https://www.wpbeginner.com" target="_blank" rel="noopener">WPBeginner</a>';
	//esc footer_text to see if it works
	$footer_text = "<span style='display: inline-flex; width: 100%; justify-content: flex-start'>".$footer_text;
	$footer_text .= '<span> | Ein Projekt mit <a href="https://www.faktorxmensch.com" target="_blank" rel="noopener">Faktor&times;Mensch</a></span>';
	return $footer_text."</span>";
}
add_filter(	'admin_footer_text', 'fxwp_change_footer_text');

function fxwp_change_footer_version ( $footer_version ) {
	$footer_version = "F&times;WP Version: ".FXWP_VERSION. " | WP ".$footer_version;
	return $footer_version;
}
add_filter(	'update_footer', 'fxwp_change_footer_version', 11);
?>