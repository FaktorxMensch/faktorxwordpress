<?php

// Hide the welcome panel which is a big annoying banner
add_action('admin_init', 'fxwp_hide_annoyance');

function fxwp_hide_annoyance()
{
	remove_action('welcome_panel', 'wp_welcome_panel');
}

// Hide the news and events widget, a second widget and the quick draft widget
add_action('wp_dashboard_setup', 'remove_dashboard_widgets');
function remove_dashboard_widgets () {
	remove_meta_box('dashboard_primary', 'dashboard', 'side' );
	remove_meta_box('dashboard_secondary', 'dashboard', 'side' );
	remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    //Maybe remove essential addons "rate me" widget later.
    //remove_meta_box('', 'dashboard', 'normal', 'core');

	//Hide elemntor and other widgets
	if (!current_user_can('fxm_admin')) {
		remove_meta_box('e-dashboard-overview', 'dashboard', 'normal', 'core');
	}
}
