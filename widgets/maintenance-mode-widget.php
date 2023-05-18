<?php
// Register the widget
function register_maintenance_mode_widget()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget(
        'maintenance_mode_widget', // Widget ID
        'Maintenance Mode', // Widget title
        'display_maintenance_mode_widget' // Display callback function
    );
}

add_action('wp_dashboard_setup', 'register_maintenance_mode_widget');

// Widget display callback function
function display_maintenance_mode_widget()
{

    if (isset($_POST['maintenance_mode'])) {
        //nonce
        if (!isset($_POST['maintenance_mode_widget_nonce']) || !wp_verify_nonce($_POST['maintenance_mode_widget_nonce'], 'save_maintenance_mode_widget')) {
            wp_die('Sorry, your nonce did not verify.');
        }
        $mode = sanitize_text_field($_POST['maintenance_mode']);
        update_option('maintenance_mode', $mode);
    }

    $maintenance_mode = get_option('maintenance_mode', 'none'); // 'none', 'coming_soon', 'maintenance_mode

    if ($maintenance_mode == 'maintenance_mode') {
        // Display maintenance mode content
        echo '<p>' . __('Wartungsmodus is enabled. Your website is temporarily unavailable.', 'text-domain') . '</p>';
    } else if ($maintenance_mode == 'coming_soon') {
        // Display coming soon content
        echo '<p>' . __('Coming Soon. Our website is under construction.', 'text-domain') . '</p>';
    } else {
        // Display default content
        echo '<p>' . __('Maintenance mode is disabled.', 'text-domain') . '</p>';
    }

    // have a form to set maintenance mode to 'coming_soon' or 'maintenance_mode' or 'none' via select with auto submit

    // Form to set maintenance mode status
    echo '<form method="post">';
    echo '<label for="maintenance_mode">' . __('Set Maintenance Mode:', 'text-domain') . '</label>';
    wp_nonce_field('save_maintenance_mode_widget', 'maintenance_mode_widget_nonce');
    echo '<select name="maintenance_mode" id="maintenance_mode" onchange="this.form.submit()">';
    echo '<option value="none" ' . selected($maintenance_mode, 'none', false) . '>' . __('None', 'text-domain') . '</option>';
    echo '<option value="coming_soon" ' . selected($maintenance_mode, 'coming_soon', false) . '>' . __('Coming Soon', 'text-domain') . '</option>';
    echo '<option value="maintenance_mode" ' . selected($maintenance_mode, 'maintenance_mode', false) . '>' . __('Maintenance Mode', 'text-domain') . '</option>';
    echo '</select>';
    echo '</form>';

}


