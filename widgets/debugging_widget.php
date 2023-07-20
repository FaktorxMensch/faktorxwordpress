<?php

function fxwp_debugging_widget()
{
    echo "<p>Current server: " . FXWP_API_URL . "<br/></p>";
    echo "<p>Api key:". get_option('fxwp_api_key') . "<br/></p>";
    // call ?fxwp_website_description_edit
    echo "<p>OpenAI Website description: <a href='?fxwp_website_description_edit'>Edit</a><br/></p>";
    echo "<style>#fxwp_debugging_widget h2 {color: #E3A354;}</style>";
}

function fxwp_register_debugging_widget()
{

    if (!current_user_can('administrator')) {
        return;
    }

    wp_add_dashboard_widget(
        'fxwp_debugging_widget', // Widget slug.
        'F&times;M Debug',
        'fxwp_debugging_widget' // Display function.
    );
}


add_action('wp_dashboard_setup', 'fxwp_register_debugging_widget');
