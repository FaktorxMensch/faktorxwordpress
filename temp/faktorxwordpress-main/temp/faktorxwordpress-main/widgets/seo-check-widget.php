<?php

// have a example widget
function fxwp_seo_check_widget()
{
    // Code to generate SEO check widget

    // Example: get the current user
    $user = wp_get_current_user();
    echo 'Helo ' . esc_html($user->display_name) . '!';


}

// add action
add_action('admin_post_fxwp_disable_dashboard_widgets', 'fxwp_disable_dashboard_widgets');

// register it in the admin dashboard
function fxwp_register_seo_check_widget()
{
    wp_add_dashboard_widget(
        'fxwp_seo_check_widget',
        'SEO Check',
        'fxwp_seo_check_widget'
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_seo_check_widget');