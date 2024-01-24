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

function fxwp_hide_admin_notices_with_reviews() {

	// This global object is used to store all plugins callbacks for different hooks
	global $wp_filter;

	// Here we define the strings that we don't want to appear in any messages
	$forbidden_message_strings = [
		'Essential Addons'
	];

	// Now we can loop over each of the admin_notice callbacks
	foreach($wp_filter['admin_notices'] as $weight => $callbacks) {

		foreach($callbacks as $name => $details) {

			// Start an output buffer and call the callback
			ob_start();
			call_user_func($details['function']);
			$message = ob_get_clean();

			// Check if this contains our forbidden string
			foreach($forbidden_message_strings as $forbidden_string) {
				if(strpos($message, $forbidden_string) !== FALSE) {

					// Found it - under this callback
					$wp_filter['admin_notices']->remove_filter('admin_notices', $details['function'], $weight);

					// admin notice with infos about the plugin for debugging
					// echo '<div class="notice notice-info"><p>The annoying notice is: ' .print_r($details['function'], true). ', '.$weight.'</p></div>';
				}
			}

		}

	}
}

add_action('in_admin_header', 'fxwp_hide_admin_notices_with_reviews');