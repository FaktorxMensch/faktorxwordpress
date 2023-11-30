<?php

function fxwp_debugging_widget()
{
    echo "<p>Current server: " . FXWP_API_URL . "<br/></p>";
    echo "<p>Api key:". get_option('fxwp_api_key') . "<br/></p>";
    // call ?fxwp_website_description_edit
    echo "<p>OpenAI Website description: <a href='?fxwp_website_description_edit'>Edit</a><br/></p>";
    echo "<style>#fxwp_debugging_widget h2 {color: #E3A354;}</style>";
}

function fxwp_debugging_widget_without_fxm_admin_capability()
{
    global $current_user;
    $user_roles = $current_user->roles;
    $user_roles_list = implode(", ", $user_roles);
    echo "<em>You are an admin and you have a fxm email address but your are not an fxm_admin. This is probably a mistake.</em><br/>";
    echo "<p>Current user roles: " . $user_roles_list . "<br/></p>";
    echo "<em>Contact an fxm_admin to fix this or change it yourself.</em><br/>";
    echo "<style>#fxwp_debugging_widget_without_fxm_admin_capability h2 {color: red;}</style>";
}

function fxwp_register_debugging_widget()
{
    //Check if current user email includes "@faktorxmensch"
    global $current_user;
    $user_email = $current_user->user_email;
    $user_has_fxm_email = str_contains($user_email, '@faktorxmensch');


    if (current_user_can('fxm_admin') && current_user_can('administrator')) {
        wp_add_dashboard_widget(
            'fxwp_debugging_widget', // Widget slug.
            'F&times;M Debug',
            'fxwp_debugging_widget' // Display function.
        );
    } elseif (current_user_can('administrator') && $user_has_fxm_email) {
        //Current user is admin and has fxm email address but is not fxm_admin which seems like a mistake
        wp_add_dashboard_widget(
            'fxwp_debugging_widget_without_fxm_admin_capability', // Widget slug.
            'F&times;M Debug',
            'fxwp_debugging_widget_without_fxm_admin_capability' // Display function.
        );
    } else {
        return;
    }
}


add_action('wp_dashboard_setup', 'fxwp_register_debugging_widget');
