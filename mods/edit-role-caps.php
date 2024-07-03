<?php

//If user is fxm_admin but has not the correct capabilities, add them
function fxwp_fix_fxm_admin_capabilities()
{
    global $current_user;
    $user_roles = (array)$current_user->roles;
    if (!in_array('administrator', $user_roles) && in_array('fxm_admin', $user_roles)) {
        $current_user->add_role('administrator');
    }
}
add_action('admin_init', 'fxwp_fix_fxm_admin_capabilities');



function fxm_edit_role_caps()
{
	$roleObject = get_role('editor');
	if (!$roleObject->has_cap('edit_theme_options')) {
		$roleObject->add_cap('edit_theme_options');
	}
}

add_action('wp_login', 'fxm_edit_role_caps');

// Limiting admin user from editing fxm_admin
function fxwp_limit_admin_user_editing_fxm_admin( $caps, $cap, $user_ID, $args ) {
    if ( ( $cap === 'edit_user' || $cap === 'delete_user' ) && $args ) {
        $editing_user = get_userdata( $user_ID ); // The user performing the task
        $edited_user = get_userdata( $args[0] ); // The user being edited/deleted

        if ( $editing_user && $edited_user && $editing_user->ID != $edited_user->ID /* User can always edit self */ ) {
            // If editing_user is fxm_admin, everything is allowed
            if (in_array('fxm_admin', (array)$editing_user->roles)) {
                return $caps;
            } else {
                // If edited_user is fxm_admin, current user is not allowed to edit/delete
                if (in_array('fxm_admin', (array)$edited_user->roles)) {
                    return $caps[] = 'do_not_allow'; // fxm_admin can do everything
                } else {
                    // If edited_user is not fxm_admin, current user is allowed to edit/delete
                    return $caps;
                }
            }
        }
    }

    return $caps;
}
add_filter( 'map_meta_cap', 'fxwp_limit_admin_user_editing_fxm_admin', 10, 4 );

// Limiting admin user from creating new fxm_admin
function fxwp_limit_admin_user_creating_new_fxm_admin( $all_roles ) {
    if ( ! current_user_can('fxm_admin')) {
        unset( $all_roles['fxm_admin'] );
    }

    return $all_roles;
}
add_filter( 'editable_roles', 'fxwp_limit_admin_user_creating_new_fxm_admin' );