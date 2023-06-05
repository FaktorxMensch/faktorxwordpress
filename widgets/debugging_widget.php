<?php

function fxwp_debugging_widget()
{
    echo "<p>Currently no debugging. Add custom code in fxm plugin to test stuff.</p>";
    echo "<style>#fxwp_debugging_widget h2 {color: #E3A354;}</style>";
}

function fxwp_register_debugging_widget()
{

    if (!current_user_can('administrator')) {
        return;
    }

    wp_add_dashboard_widget(
        'fxwp_debugging_widget', // Widget slug.
        'FxM Custom Debugging', // Title.
        'fxwp_debugging_widget' // Display function.
    );
}


add_action('wp_dashboard_setup', 'fxwp_register_debugging_widget');
