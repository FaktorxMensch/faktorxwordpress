<?php

// Redirect non fxm_admins to the dashboard if they are accessing the about.php page
add_action('current_screen', 'fxm_about_page_redirect');
/**
 * Redirect specific admin page
 */
function fxm_about_page_redirect() {
	$my_current_screen = get_current_screen();
	if (isset($my_current_screen->base) && $my_current_screen->base == 'about' && !current_user_can('fxm_admin')) {
		wp_redirect(admin_url());
		exit();
	}
}