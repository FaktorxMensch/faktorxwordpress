<?php
/*
Zeigt im Dashboard den Update-Status an, ermöglicht das Aktualisieren von Plugins und Themes und bietet die Option, per E‑Mail benachrichtigt zu werden, wenn Updates verfügbar sind.
*/

// Einstellungen speichern, wenn Formular abgeschickt wurde
add_action('admin_init', 'cud_handle_settings_form');
function cud_handle_settings_form()
{
    if (isset($_POST['cud_settings_nonce']) && wp_verify_nonce($_POST['cud_settings_nonce'], 'cud_save_settings')) {
        $notify_enabled = isset($_POST['cud_notify_enabled']) ? 1 : 0;
        $notify_email = isset($_POST['cud_notify_email']) ? sanitize_email($_POST['cud_notify_email']) : '';
        update_option('cud_notify_enabled', $notify_enabled);
        update_option('cud_notify_email', $notify_email);
        add_action('admin_notices', function () {
            echo '<div class="updated"><p>Einstellungen wurden gespeichert.</p></div>';
        });
    }
}

// Menüpunkt im Dashboard hinzufügen
add_action('admin_menu', 'cud_add_dashboard_menu');
function cud_add_dashboard_menu()
{

    // Nur wenn die fxm option für die customer update dashboard aktiviert ist
    if (!get_option('fxm_customer_update_dashboard', 0)) {
        // wenn die option nicht existiert, dann wird sie erstellt
        add_option('fxm_customer_update_dashboard', 0);

        return;
    }

    // Update-Daten laden
    $update_plugins = get_site_transient('update_plugins');
    $update_themes = get_site_transient('update_themes');

    $count = 0;
    if (!empty($update_plugins->response)) {
        $count += count($update_plugins->response);
    }
    if (!empty($update_themes->response)) {
        $count += count($update_themes->response);
    }

    // Menütitel zusammenbauen: Falls Updates verfügbar sind, wird ein Badge angehängt.
    $menu_title = 'Updates';
    if ($count > 0) {
        $menu_title .= ' <span class="important update-plugins count-' . $count . '"><span class="plugin-count">' . $count . '</span></span>';
    }

    add_menu_page(
        'Update Dashboard',   // Seitentitel
        $menu_title,          // Dynamisch erstellter Menütitel
        'edit_posts',         // Berechtigung (zum Beispiel reicht "Beiträge bearbeiten")
        'update-dashboard',   // Slug
        'cud_dashboard_page', // Callback-Funktion
        'dashicons-update',   // Icon
        3                     // Position
    );
}

// Dashboard-Seite
function cud_dashboard_page()
{
    echo '<div class="wrap">';
    echo '<h1>Update Dashboard</h1>';

    // Update-Daten laden
    $update_plugins = get_site_transient('update_plugins');
    $update_themes = get_site_transient('update_themes');

    $plugin_updates_available = !empty($update_plugins->response);
    $theme_updates_available = !empty($update_themes->response);

    // Falls ein Update-Request per GET vorliegt, diese Aktionen ausführen
    if (isset($_GET['do_update'])) {
        if ('plugins' === $_GET['do_update']) {
            cud_update_plugins();
        }
        if ('themes' === $_GET['do_update']) {
            cud_update_themes();
        }
        // Nach dem Update werden die Update-Transiente geleert und neu geladen
        wp_clean_plugins_cache(true);
        set_site_transient('update_plugins', null);
        wp_update_plugins();
        wp_update_themes();
    }

    // Anzeige der Update-Informationen in farblich hervorgehobenen Boxen
    if ($plugin_updates_available || $theme_updates_available) {
        // Disclaimer Box vor den Update-Buttons
        echo '<div class="notice notice-info">';
        echo '<p><strong>Achtung:</strong> Es sind Updates verfügbar für: ';
        if ($plugin_updates_available) {
            echo 'Plugins ';
        }
        if ($theme_updates_available) {
            echo 'Themes ';
        }
        echo '</p>';
        echo '</div>';

        echo '<div class="notice notice-warning">';
        echo '<h3>Wichtiger Hinweis zu Updates</h3>';
        echo '<p>Bitte beachten Sie die folgenden Informationen, bevor Sie Updates durchführen:</p>';
        echo '<ul style="list-style-type: disc; margin-left: 20px;">';
        echo '<li>WordPress ist eine quelloffene Software und Updates können gelegentlich zu unerwarteten Problemen führen.</li>';
        echo '<li>Dies gilt insbesondere für Plugins und Themes von Drittanbietern, die möglicherweise nicht vollständig mit der neuesten WordPress-Version kompatibel sind.</li>';
        echo '<li>Durch das Ausführen von Updates bestätigen Sie, dass Sie die möglichen Risiken verstehen und akzeptieren.</li>';
        echo '<li><strong>Wichtig:</strong> Prüfen Sie nach dem Update unbedingt Ihre Website auf Fehler oder Funktionsstörungen.</li>';
        echo '<li>Faktor&times;Mensch MEDIA hat keinen Einfluss auf die Entwicklung und Qualität dieser Updates, da sie von den jeweiligen Entwicklern bereitgestellt werden.</li>';
        echo '</ul>';
        echo '<p>Wir empfehlen grundsätzlich, Updates durchzuführen, um die Sicherheit und Funktionalität Ihrer Website zu gewährleisten. Dieser Hinweis dient lediglich Ihrer Information.</p>';
        echo '</div>';

        echo '<p>Nach dem Update sollten Sie unbedingt prüfen, ob alles einwandfrei funktioniert.</p>';
        echo '</div>';
        echo '<p>';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=update-dashboard&do_update=plugins')) . '">Plugins aktualisieren</a> ';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=update-dashboard&do_update=themes')) . '">Themes aktualisieren</a>';
        echo '</p>';

        // E‑Mail-Benachrichtigung senden, falls aktiviert und gültige E‑Mail-Adresse vorhanden ist
        $notify_enabled = get_option('cud_notify_enabled', 0);
        $notify_email = get_option('cud_notify_email', '');
        if (empty($notify_email)) {
            // Falls keine E‑Mail gesetzt ist, den zweiten Benutzer ermitteln (falls vorhanden)
            $users = get_users();
            if (count($users) > 1) {
                $notify_email = $users[1]->user_email;
            }
        }
        if ($notify_enabled && is_email($notify_email)) {
            cud_send_notification_email($plugin_updates_available, $theme_updates_available);
        }
    } else {
        echo '<div class="notice notice-success">';
        echo '<p>Alles in Ordnung: Es sind keine Updates verfügbar.</p>';
        echo '</div>';
    }

    // Trennlinie
    echo '<hr />';

    // Einstellungsformular für E‑Mail-Benachrichtigung (unterhalb der Update-Infos)
    ?>
    <h2>E‑Mail Benachrichtigung</h2>
    <form method="post" action="">
        <?php wp_nonce_field('cud_save_settings', 'cud_settings_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">E‑Mail Benachrichtigung aktivieren</th>
                <td>
                    <input type="checkbox" id="cud_notify_enabled" name="cud_notify_enabled"
                           value="1" <?php checked(get_option('cud_notify_enabled', 0), 1); ?> />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">E‑Mail Adresse</th>
                <td>
                    <?php
                    // Falls keine E‑Mail gesetzt ist, den zweiten Benutzer ermitteln (falls vorhanden)
                    $notify_email = get_option('cud_notify_email', '');
                    if (empty($notify_email)) {
                        $users = get_users();
                        if (count($users) > 1) {
                            $notify_email = $users[1]->user_email;
                        }
                    }
                    ?>
                    <input type="email" id="cud_notify_email" name="cud_notify_email"
                           value="<?php echo esc_attr($notify_email); ?>" style="width:300px;"/>
                    <p class="description">Geben Sie die E‑Mail-Adresse ein, an die Benachrichtigungen gesendet werden
                        sollen.</p>
                </td>
            </tr>
        </table>
        <?php submit_button('Einstellungen speichern'); ?>
    </form>

    <!-- JavaScript: Falls Checkbox nicht aktiviert ist, wird die E‑Mail-Adresseingabe halbtransparent dargestellt -->
    <script type="text/javascript">
        (function ($) {
            function toggleEmailOpacity() {
                if ($('#cud_notify_enabled').is(':checked')) {
                    $('#cud_notify_email').css('opacity', '1');
                } else {
                    $('#cud_notify_email').css('opacity', '0.5');
                }
            }

            $(document).ready(function () {
                toggleEmailOpacity();
                $('#cud_notify_enabled').change(function () {
                    toggleEmailOpacity();
                });
            });
        })(jQuery);
    </script>

    <?php
    echo '</div>';
}

// Plugins aktualisieren
function cud_update_plugins()
{
    if (!current_user_can('update_plugins')) {
        echo '<div class="error"><p>Sie haben keine Berechtigung, Plugins zu aktualisieren.</p></div>';
        return;
    }

    require_once(ABSPATH . 'wp-admin/includes/update.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $update_plugins = get_site_transient('update_plugins');

    if (!empty($update_plugins->response)) {
        foreach ($update_plugins->response as $plugin_file => $plugin_data) {
            $result = $upgrader->upgrade($plugin_file);
            if (is_wp_error($result)) {
                echo '<div class="error"><p>Fehler beim Aktualisieren des Plugins <strong>' . esc_html($plugin_file) . '</strong>: ' . esc_html($result->get_error_message()) . '</p></div>';
            } elseif (false === $result) {
                echo '<div class="error"><p>Fehler beim Aktualisieren des Plugins <strong>' . esc_html($plugin_file) . '</strong>.</p></div>';
            } else {
                echo '<div class="updated"><p>Das Plugin <strong>' . esc_html($plugin_file) . '</strong> wurde erfolgreich aktualisiert.</p></div>';
            }
        }
    }
}

// Themes aktualisieren
function cud_update_themes()
{
    if (!current_user_can('update_themes')) {
        echo '<div class="error"><p>Sie haben keine Berechtigung, Themes zu aktualisieren.</p></div>';
        return;
    }

    require_once(ABSPATH . 'wp-admin/includes/update.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    $upgrader = new Theme_Upgrader(new Automatic_Upgrader_Skin());
    $update_themes = get_site_transient('update_themes');

    if (!empty($update_themes->response)) {
        foreach ($update_themes->response as $theme_slug => $theme_data) {
            $result = $upgrader->upgrade($theme_slug);
            if (is_wp_error($result)) {
                echo '<div class="error"><p>Fehler beim Aktualisieren des Themes <strong>' . esc_html($theme_slug) . '</strong>: ' . esc_html($result->get_error_message()) . '</p></div>';
            } elseif (false === $result) {
                echo '<div class="error"><p>Fehler beim Aktualisieren des Themes <strong>' . esc_html($theme_slug) . '</strong>.</p></div>';
            } else {
                echo '<div class="updated"><p>Das Theme <strong>' . esc_html($theme_slug) . '</strong> wurde erfolgreich aktualisiert.</p></div>';
            }
        }
    }
}

// E‑Mail-Benachrichtigung senden
function cud_send_notification_email($plugin_updates, $theme_updates)
{
    // Um wiederholte Benachrichtigungen zu vermeiden, wird ein Transient gesetzt (z. B. für 1 Stunde)
    if (get_transient('cud_notification_sent')) {
        return;
    }

    $notify_email = get_option('cud_notify_email', '');
    if (!is_email($notify_email)) {
        return;
    }

    $subject = 'Update-Benachrichtigung: Neue Updates verfügbar';
    $message = "Sehr geehrte/r Administrator/in,\r\n\r\n";
    $message .= "es sind neue Updates verfügbar:\r\n\r\n";
    if ($plugin_updates) {
        $plugin_link = admin_url('admin.php?page=update-dashboard&do_update=plugins');
        $message .= "- Plugins: Zum Aktualisieren klicken Sie bitte auf diesen Link:\r\n" . $plugin_link . "\r\n\r\n";
    }
    if ($theme_updates) {
        $theme_link = admin_url('admin.php?page=update-dashboard&do_update=themes');
        $message .= "- Themes: Zum Aktualisieren klicken Sie bitte auf diesen Link:\r\n" . $theme_link . "\r\n\r\n";
    }
    $message .= "Bitte überprüfen Sie anschließend, ob Ihre Website einwandfrei funktioniert.\r\n\r\n";
    $message .= "Mit freundlichen Grüßen,\r\nIhr System";

    // E‑Mail versenden
    wp_mail($notify_email, $subject, $message);

    // Transient setzen, um erneutes Senden für 1 Stunde zu verhindern
    set_transient('cud_notification_sent', true, HOUR_IN_SECONDS);
}
