<?php

add_filter('plugin_action_links', 'disable_fxwp_plugin_deactivation', 10, 4);
function disable_fxwp_plugin_deactivation($actions, $plugin_file, $plugin_data, $context)
{
    /* if there is only one user and it is fxm, allow deactivation */
    if (count(get_users()) == 1 && get_users()[0]->user_login == 'fxm') {
        return $actions;
    }

    if (current_user_can('fxm_admin')) {
        return $actions;
    }

    if (array_key_exists('deactivate', $actions) && in_array($plugin_file, array(
            'faktorxwordpress/faktorxwordpress.php',
        )))
        unset($actions['deactivate']);
    return $actions;
}