<?php

function fxwp_enable_automatic_updates()
{
    update_option('automatic_updates', true);
    add_filter('auto_update_core', '__return_true');
    add_filter('auto_update_plugin', '__return_true');
    add_filter('auto_update_theme', '__return_true');
}

function fxwp_disable_automatic_updates()
{
    update_option('automatic_updates', false);
    remove_filter('auto_update_core', '__return_true');
    remove_filter('auto_update_plugin', '__return_true');
    remove_filter('auto_update_theme', '__return_true');
}

function fxwp_updates_page()
{

    // Check if a backup action was submitted
    if (isset($_POST['fxwp_update_settings_nonce']) && wp_verify_nonce($_POST['fxwp_update_settings_nonce'], 'fxwp_update_settings')) {
        // Run the appropriate function based on the submitted action
        switch ($_POST['automatic_updates']) {
            case '1':
                // Replace this with your actual backup creation method
                fxwp_enable_automatic_updates();
                break;
            case '0':
                // Replace this with your actual backup restoration method
                // You would also need to pass the backup file name or other identifier as a parameter
                fxwp_disable_automatic_updates();
                break;
        }
    }

    ?>
    <div class="wrap">
        <h1>Aktualisierungen</h1>

        <h2>Automatische Aktualisierungen</h2>
        <form method="post" action="">
            <?php wp_nonce_field('fxwp_update_settings', 'fxwp_update_settings_nonce'); ?>
            <label>
                <select name="automatic_updates">
                    <option value="1" <?php selected(get_option('automatic_updates', true), true); ?>>Aktiviert</option>
                    <option value="0" <?php selected(get_option('automatic_updates', true), false); ?>>Deaktiviert
                    </option>
                </select>
            </label>
            <p class="description">Wenn aktiviert, werden alle Plugins und die WordPress-Kernsoftware automatisch
                aktualisiert.</p>

            <h2>Manuelle Aktualisierungen</h2>
            <p>Um manuell zu aktualisieren, klicken Sie auf den "Jetzt aktualisieren" Button neben dem jeweiligen
                Element, das Sie aktualisieren möchten.</p>
            <?php
            // Holen Sie sich die Liste der installierten Plugins
            $plugins = get_plugins();

            if (!empty($plugins)) {
                echo '<h3>Plugins</h3>';
                echo '<ul>';
                foreach ($plugins as $plugin_file => $plugin_data) {
                    $update_url = wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($plugin_file)), 'upgrade-plugin_' . $plugin_file);
                    echo '<li>';
                    echo '<span>' . $plugin_data['Name'] . '</span>';
                    echo '<a href="' . $update_url . '" class="button button-primary">Jetzt aktualisieren</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }

            echo '<h3>WordPress-Kernsoftware</h3>';
            $core_update_url = wp_nonce_url(admin_url('update-core.php'), 'upgrade-core');
            echo '<a href="' . $core_update_url . '" class="button button-primary">WordPress jetzt aktualisieren</a>';
            ?>

            <p><strong>Hinweis:</strong> Es wird empfohlen, vor der Durchführung von Aktualisierungen ein Backup zu
                erstellen.</p>

            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                     value="Änderungen speichern"></p>
        </form>
    </div>
    <?php
}
