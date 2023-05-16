<?php

// Widget display callback function
function display_maintenance_mode()
{
    if (!is_user_logged_in()) {
        $maintenance_mode = get_option('maintenance_mode', 'none'); // 'none', 'coming_soon', 'maintenance_mode

        if ($maintenance_mode == 'maintenance_mode') {
            echo '<p>' . __('Wartungsmodus. Unsere Website ist vorübergehend nicht verfügbar.', 'text-domain') . '</p>';
        } else if ($maintenance_mode == 'coming_soon') {
            echo '<p>' . __('Demnächst verfügbar. Unsere Website befindet sich im Aufbau.', 'text-domain') . '</p>';
        } else {
            return;
        }

        die(); // Stop further execution
    }
}

// Register the widget
function register_maintenance_mode()
{
    add_action('template_redirect', 'display_maintenance_mode');

}

add_action('wp', 'register_maintenance_mode');
