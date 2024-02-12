<?php

add_action( 'admin_menu', 'fxwp_restrict_pages', 999 );
function fxwp_restrict_pages(): void {
	if (current_user_can('fxm_admin')) {
		return;
	}

	$restricted_features = get_option( 'fxwp_restricted_features' );

	if ( empty($restricted_features) ) {
		return ;
	} else {
		$restricted_features = get_object_vars( json_decode( $restricted_features ) );
	}

	foreach ($restricted_features as $option => $value) {
		if (!$value) {
			continue;
		} else {
			switch ($option) {
				case 'fxwp_restr_pages':
					remove_menu_page('edit.php?post_type=page'); // Pages
					break;
				case 'fxwp_restr_posts':
					remove_menu_page('edit.php'); // Blog posts
					break;
				case 'fxwp_restr_uploads':
					remove_menu_page('upload.php'); // Uploads
					break;
				case 'fxwp_restr_themes':
					remove_menu_page('themes.php'); // Appearance
					break;
				case 'fxwp_restr_updates-submenu':
					remove_submenu_page('index.php', 'update-core.php'); // Updates
					break;
				case 'fxwp_restr_elememtor-templates':
					remove_menu_page('edit.php?post_type=elementor_library'); // Elementor Templates
					break;
				case 'fxwp_restr_wpcf7':
					remove_menu_page('wpcf7'); // Contact Forms 7
					break;
				case 'fxwp_restr_admin_plugins':
					remove_menu_page('plugins.php'); // Plugins
					break;
				case 'fxwp_restr_admin_users':
					remove_menu_page('users.php'); // Users
					break;
				case 'fxwp_restr_admin_tools':
					remove_menu_page('tools.php'); // Tools
					break;
				case 'fxwp_restr_admin_settings':
					remove_menu_page('options-general.php'); // Settings
					break;
				case 'fxwp_restr_admin_elementor':
					remove_menu_page('elementor'); // Elementor Settings
					break;
				case 'fxwp_restr_admin_eael':
					remove_menu_page('eael-settings'); // Essential Addons for Elementor Settings
					break;
				default:
					break;
			}

		}
	}
}

add_action( 'admin_bar_menu', 'fxwp_restrict_admin_bar', 999);
function fxwp_restrict_admin_bar( $wp_admin_bar ): void {
	if (current_user_can('fxm_admin')) {
		return;
	}

	$restricted_features = get_option( 'fxwp_restricted_features' );

	if ( empty($restricted_features) ) {
		return ;
	} else {
		$restricted_features = get_object_vars( json_decode( $restricted_features ) );
	}

	foreach ($restricted_features as $option => $value) {
		if (!$value) {
			continue;
		} else {
			switch ($option) {
				case 'fxwp_restr_new-button':
					// Remove the button "new"
					$wp_admin_bar->remove_node( 'new-content' );
					break;
				case 'fxwp_restr_updates-indicator':
					// Remove updates
					$wp_admin_bar->remove_node( 'updates' );
					break;
				case 'fxwp_restr_my-account':
					// Remove edit profile
					$wp_admin_bar->remove_node( 'my-account' );
//					break;
				default:
					break;
			}
		}
	}
}

