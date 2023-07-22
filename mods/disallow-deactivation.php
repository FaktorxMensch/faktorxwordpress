<?php

add_filter( 'plugin_action_links', 'disable_fxwp_plugin_deactivation', 10, 4 );
function disable_fxwp_plugin_deactivation( $actions, $plugin_file, $plugin_data, $context ) {

	if (current_user_can('fxm_admin')) {
		return $actions;
	}

	if ( array_key_exists( 'deactivate', $actions ) && in_array( $plugin_file, array(
			'faktorxwordpress/faktorxwordpress.php',
		)))
		unset( $actions['deactivate'] );
	return $actions;
}