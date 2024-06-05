<?php
add_filter('wp_mail', 'fxwp_log_outgoing_mail');

function fxwp_log_outgoing_mail($args)
{
    // Mask the email for privacy
    $to = $args['to'];

    // Check if $to is an array
    if (is_array($to)) {
        // Extract the first email address from the array
        $to = reset($to);
    }
    // Validate the extracted email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        // Log the array as an error
        error_log('Invalid email address array: ' . print_r($args['to'], true));

        // Set a fallback email address
        $to = 'E!@error_in_function_for_email_log.com';
    }

    $at_position = strpos($to, '@');
    if ($at_position !== false) {
        $to = substr_replace($to, '***', 2, $at_position - 2);
    }

    // Prepare the email content
    $email_content = array(
        'to_email' => $to,
        'subject' => $args['subject'],
        'message' => $args['message'],
        'headers' => $args['headers'],
        //if $args['attachments'] is an array, convert it to a string
        'attachments' => is_array($args['attachments']) ? implode(',', $args['attachments']) : $args['attachments'],
//        'attachments' => $args['attachments'],
        'timestamp' => current_time('mysql'),
    );

//    error_log("fxwp_log_outgoing_mail: " . print_r($email_content, true));

    // Save to the database
    global $wpdb;
    $table_name = $wpdb->prefix . "email_logs";
    $wpdb->insert($table_name, $email_content);

//    echo '<div class="notice notice-success"><p>' . esc_html('Die E-Mail wurde erfolgreich geloggt.') . '</p></div>';

    // don't forget to return the args to ensure the email is sent
    return $args;
}

function fxwp_create_email_log_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'email_logs';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        to_email text NOT NULL,
        subject text NOT NULL,
        message longtext NOT NULL,
        headers text NOT NULL,
        attachments text,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
