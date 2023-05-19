<?php
// have a corn that deletes email logs older than 30 days
function fxwp_delete_old_email_logs()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "email_logs";
    $wpdb->query("DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
}

// add the cron job
add_action('fxwp_delete_old_email_logs', 'fxwp_delete_old_email_logs');

if (!wp_next_scheduled('fxwp_delete_old_email_logs')) {
    wp_schedule_event(time(), 'daily', 'fxwp_delete_old_email_logs');
}