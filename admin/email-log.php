<?php

function fxwp_display_email_logs()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "email_logs";

    // If the user has clicked the "Send Test Email" button, send a test email
    if (isset($_POST['action']) && $_POST['action'] == 'fxwp_send_test_email') {
        if (!wp_verify_nonce($_POST['fxwp_send_test_email_nonce'], 'fxwp_send_test_email')) {
            wp_die('Security check failed');
        }

        $to = get_option('admin_email');
        $subject = 'Test E-Mail von ' . get_bloginfo('name');
        $message = 'Dies ist eine Test E-Mail von ' . get_bloginfo('name') . '.';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $message, $headers);

        echo '<div class="notice notice-success"><p>' . esc_html('Die Test E-Mail wurde erfolgreich gesendet.') . '</p></div>';
    }

    $email_logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");
    echo '<div class="wrap"><h1>Email Log</h1>';

    if (count($email_logs) == 0) {
        echo '<p>' . esc_html('Bisher wurden keine E-Mails von dieser Website gesendet.') . '</p>';

        // Add a button to send a test email
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="fxwp_send_test_email">';
        echo '<input type="hidden" name="fxwp_send_test_email_nonce" value="' . wp_create_nonce('fxwp_send_test_email') . '">';
        echo '<input type="submit" value="' . esc_attr('Test E-Mail senden') . '" class="button button-primary">';
        echo '</form>';
    } else {
        echo '<p>' . esc_html('Hier sind die letzten 50 E-Mails, die von dieser Website gesendet wurden.') . '</p>';

        echo '<table class="widefat">';
        echo '<thead><tr><th>To</th><th>Subject</th><th>Timestamp</th></tr></thead>';
        foreach ($email_logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->to_email) . '</td>';
            echo '<td>' . esc_html($log->subject) . '</td>';
            echo '<td>' . esc_html($log->timestamp) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

    }
}