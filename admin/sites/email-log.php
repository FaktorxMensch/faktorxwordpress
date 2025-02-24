<?php
function fxwp_display_email_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . "email_logs";

    // Enqueue necessary scripts and styles
    wp_enqueue_script('jquery');
    add_action('admin_footer', 'fxwp_email_log_scripts');

    // Test email handling
    if (isset($_POST['action']) && $_POST['action'] == 'fxwp_send_test_email') {
        if (!wp_verify_nonce($_POST['fxwp_send_test_email_nonce'], 'fxwp_send_test_email')) {
            wp_die('Security check failed');
        }

        $to = get_option('admin_email');
        $subject = 'Test E-Mail von ' . get_bloginfo('name');
        $message = 'Dies ist eine Test E-Mail von ' . get_bloginfo('name') . '.';
        $headers = array('Content-Type: text/html; charset=UTF-8');

        $mail = wp_mail($to, $subject, $message, $headers);

        if (!$mail) {
            echo '<div class="notice notice-error"><p>' . esc_html('Die Test E-Mail konnte nicht gesendet werden.') . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html('Die Test E-Mail wurde erfolgreich gesendet.') . '</p></div>';
        }
    }

    $email_logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");
    echo '<div class="wrap"><h1>E-Mail-Protokoll</h1>';

    if(current_user_can('fxm_admin') && fxwp_check_deactivated_features('fxwp_deact_email_log')) {
        ?>
        <br>
        <div class="notice notice-error">
            <p><?php _e('Email Log ist nicht sichtbar für Kundis auf Grund der Plugin Einstellungen!', 'fxwp'); ?></p>
        </div>
        <?php
    }

    // Add search field
    echo '<div class="tablenav top">';
    echo '<div class="alignleft actions">';
    echo '<input type="text" id="email-log-search" class="regular-text" placeholder="Suche in allen Spalten..." style="margin: 0 0 8px 0;">';
    echo '</div>';
    echo '</div>';

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
        echo '<thead><tr>
            <th>To</th>
            <th>Subject</th>
            <th>Timestamp</th>
            <th>Headers</th>
            <th>Message</th>
            <th>Attachments</th>
        </tr></thead>';

        foreach ($email_logs as $log) {
            $log_id = esc_attr($log->id);
            echo '<tr>';
            echo '<td>' . esc_html($log->to_email) . '</td>';
            echo '<td>' . esc_html($log->subject) . '</td>';
            echo '<td>' . esc_html($log->timestamp) . '</td>';

            // Headers column
            echo '<td>';
            echo '<button type="button" class="button toggle-content" data-target="headers-' . $log_id . '">Headers anzeigen</button>';
            echo '<div class="content-wrapper" id="headers-' . $log_id . '" style="display: none;">';
            echo '<pre>' . esc_html($log->headers) . '</pre>';
            echo '</div>';
            echo '</td>';

            // Message column
            echo '<td>';
            echo '<button type="button" class="button toggle-content" data-target="message-' . $log_id . '">Nachricht anzeigen</button>';
            echo '<div class="content-wrapper" id="message-' . $log_id . '" style="display: none;">';
            echo '<div class="content-controls">';
            echo '<button type="button" class="button view-raw active" data-log-id="' . $log_id . '">Raw</button>';
            echo '<button type="button" class="button view-rendered" data-log-id="' . $log_id . '">Rendered</button>';
            echo '</div>';
            echo '<div class="content-raw" id="message-raw-' . $log_id . '">';
            echo '<pre>' . esc_html($log->message) . '</pre>';
            echo '</div>';
            echo '<div class="content-rendered" id="message-rendered-' . $log_id . '" style="display: none;">';
            echo wp_kses_post($log->message);
            echo '</div>';
            echo '</div>';
            echo '</td>';

            // Attachments column
            echo '<td>';
            if (!empty($log->attachments)) {
                echo '<button type="button" class="button toggle-content" data-target="attachments-' . $log_id . '">Anhänge anzeigen</button>';
                echo '<div class="content-wrapper" id="attachments-' . $log_id . '" style="display: none;">';
                echo '<pre>' . esc_html($log->attachments) . '</pre>';
                echo '</div>';
            } else {
                echo '-';
            }
            echo '</td>';

            echo '</tr>';
        }
        echo '</table>';
    }
    echo '</div>';
}

function fxwp_email_log_scripts() {
    ?>
    <style>
        .content-wrapper {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            max-width: 600px;
        }
        .content-controls {
            margin-bottom: 10px;
        }
        .content-controls .button {
            margin-right: 5px;
        }
        .content-controls .button.active {
            background: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        .content-raw pre,
        .content-wrapper pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            background: #fff;
            padding: 10px;
            margin: 0;
            font-size: 12px;
        }
        .no-results {
            padding: 10px;
            background-color: #f0f0f1;
            border-left: 4px solid #72aee6;
            max-height: 300px;
            overflow-y: auto;
        }
        .content-rendered {
            background: #fff;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        .widefat td {
            vertical-align: top;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            // Search functionality
            $('#email-log-search').on('input', function() {
                var searchText = $(this).val().toLowerCase();
                var hasResults = false;

                $('table.widefat tbody tr').each(function() {
                    var $row = $(this);
                    var rowText = '';

                    // Collect text from visible cells (excluding hidden content)
                    $row.find('td').each(function() {
                        var $cell = $(this);
                        // Get the text content excluding hidden elements
                        var visibleText = $cell.clone()
                            .find('.content-wrapper').remove()
                            .end()
                            .text();
                        rowText += ' ' + visibleText;

                        // Also search in the hidden content
                        var hiddenContent = $cell.find('.content-wrapper pre').text();
                        if (hiddenContent) {
                            rowText += ' ' + hiddenContent;
                        }
                    });

                    rowText = rowText.toLowerCase();

                    if (rowText.includes(searchText)) {
                        $row.show();
                        hasResults = true;
                    } else {
                        $row.hide();
                    }
                });

                // Show/hide "no results" message
                var $noResults = $('#no-search-results');
                if (!hasResults) {
                    if (!$noResults.length) {
                        $('table.widefat').after('<p id="no-search-results" class="no-results">Keine Ergebnisse gefunden.</p>');
                    }
                    $('#no-search-results').show();
                } else {
                    $('#no-search-results').hide();
                }
            });

            // Clear search when ESC is pressed
            $(document).keyup(function(e) {
                if (e.key === "Escape") {
                    $('#email-log-search').val('').trigger('input');
                }
            });
            // Toggle content visibility
            $('.toggle-content').click(function() {
                var targetId = $(this).data('target');
                $('#' + targetId).slideToggle();
                $(this).text(function(i, text) {
                    if (text.includes('anzeigen')) {
                        return text.replace('anzeigen', 'verbergen');
                    } else {
                        return text.replace('verbergen', 'anzeigen');
                    }
                });
            });

            // Switch between raw and rendered views
            $('.view-raw, .view-rendered').click(function() {
                var logId = $(this).data('log-id');
                var isRaw = $(this).hasClass('view-raw');

                // Update buttons
                $(this).addClass('active').siblings().removeClass('active');

                // Show/hide content
                if (isRaw) {
                    $('#message-raw-' + logId).show();
                    $('#message-rendered-' + logId).hide();
                } else {
                    $('#message-raw-' + logId).hide();
                    $('#message-rendered-' + logId).show();
                }
            });
        });
    </script>
    <?php
}