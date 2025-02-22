<?php
require_once plugin_dir_path(__FILE__) . '../../includes/helpers.php';

//deactivate updates if they are still activated
if (fxwp_check_deactivated_features('fxwp_deact_autoupdates')) {
    fxwp_disable_automatic_updates();
}
function fxwp_enable_automatic_updates()
{
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (fxwp_check_deactivated_features('fxwp_deact_autoupdates')) {
        fxwp_disable_automatic_updates();
        return;
    }

    update_option('fxwp_automatic_updates', true);
    remove_filter( 'automatic_updater_disabled', '__return_true' );
    add_filter('auto_update_core', '__return_true');
    add_filter('auto_update_plugin', '__return_true');
    add_filter('auto_update_theme', '__return_true');


    // Get all plugins
    $plugins = array_keys(get_plugins());
    // Enable auto updates for all plugins
    update_option('auto_update_plugins', $plugins);

    // Get all themes
    $themes = array_keys(wp_get_themes());
    // Enable auto updates for all themes
    update_option('auto_update_themes', $themes);
}

// add action after installed plugin or theme
add_action('upgrader_process_complete', 'fxwp_enable_automatic_updates', 10, 2);

function fxwp_disable_automatic_updates()
{
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    update_option('fxwp_automatic_updates', false);
    add_filter( 'automatic_updater_disabled', '__return_true' );
    remove_filter('auto_update_core', '__return_true');
    remove_filter('auto_update_plugin', '__return_true');
    remove_filter('auto_update_theme', '__return_true');

    // Get all plugins
    $plugins = array_keys(get_plugins());
    // Disable auto updates for all plugins
    update_option('auto_update_plugins', array());

    // Get all themes
    $themes = array_keys(wp_get_themes());
    // Disable auto updates for all themes
    update_option('auto_update_themes', array());

}

function fxwp_updates_page()
{

    // Check if a backup action was submitted
    if (isset($_POST['fxwp_update_settings_nonce']) && wp_verify_nonce($_POST['fxwp_update_settings_nonce'], 'fxwp_update_settings')) {
        // Run the appropriate function based on the submitted action
        switch ($_POST['fxwp_automatic_updates']) {
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
        <?php fxwp_show_deactivated_feature_warning('fxwp_deact_autoupdates'); ?>
        <h2>Automatische Aktualisierungen</h2>
        <form method="post" action="">
            <?php wp_nonce_field('fxwp_update_settings', 'fxwp_update_settings_nonce'); ?>
            <label>
                <select name="fxwp_automatic_updates">
                    <option value="1" <?php selected(get_option('fxwp_automatic_updates', true), true); ?>>Aktiviert
                    </option>
                    <option value="0" <?php selected(get_option('fxwp_automatic_updates', true), false); ?>>Deaktiviert
                    </option>
                </select>
            </label>
            <p class="description">Wenn aktiviert, werden alle Plugins und die WordPress-Kernsoftware automatisch
                aktualisiert.</p>

            <h2>Manuelle Aktualisierungen</h2>
            <p>Um manuell zu aktualisieren, klicken Sie auf den "Jetzt aktualisieren" Button neben dem jeweiligen
                Element, das Sie aktualisieren möchten.</p>
            <?php
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            // Holen Sie sich die Liste der installierten Plugins
            $plugins = get_plugins();

            if (!empty($plugins)) {
                echo '<h3>Plugins</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Plugin</th><th>Aktion</th></tr></thead>';
                echo '<tbody>';
                foreach ($plugins as $plugin_file => $plugin_data) {
                    $update_url = wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($plugin_file)), 'upgrade-plugin_' . $plugin_file);
                    echo '<tr>';
                    echo '<td>' . $plugin_data['Name'] . '</td>';
                    echo '<td><a href="' . $update_url . '" class="button button-primary">Jetzt aktualisieren</a></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
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


        <div class="flex">
            <form method="post" action="" class="inline">
                <?php echo esc_html__('Version', 'fxwp'); ?>
                <?php echo esc_html(FXWP_VERSION); ?>
                <a href="<?php echo esc_url(admin_url('index.php?fxwp_sync=1')); ?>">
                    <?php echo esc_html__('Prüfen auf Updates', 'fxwp'); ?>
                </a>
            </form>
            <?php if (current_user_can("fxm_admin")) { ?>
                <svg class="inline"
                     height="1.5em"
                     xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     onclick="document.querySelector('svg.inline').classList.toggle('flip');document.querySelector('.tag-update').classList.toggle('inline');"
                >
                    <title>chevron-left</title>
                    <path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z"/>
                </svg>
                <form method="post" action="index.php?fxwp_sync=1" class="tag-update">
                    <input type="text" name="fxwp_self_update_tag" placeholder="Tag" required style="width:60px"/>
                    <input type="submit" class="button button-primary"
                           value="<?php echo esc_html__('manuell installieren', 'fxwp'); ?>"/>
                </form>
            <?php } ?>
        </div>

        <style>
            .scroll-box {
                max-height: 200px;
                overflow: auto;
                border: 1px solid #ccc;
                padding: 10px;
                width: calc(100vw - 400px);
            }

            .flex {
                display: flex;
                height: 2em;
                align-items: center;
            }

            form.inline {
                display: inline;
            }

            svg.inline {
                opacity: 0.5;
                transition: all 0.1s;
            }

            svg.inline:hover {
                cursor: pointer;
                -webkit-transform: rotate(180deg);
                -ms-transform: rotate(45deg);
                opacity: 1;
            }

            .tag-update {
                display: none;
            }

            .flip {
                -webkit-transform: rotate(180deg);
                -ms-transform: rotate(45deg);
                transform: rotate(180deg);
            }
        </style>
        <script>
            document.addEventListener('formdata', (e) => {
                let deactivated_features_list = {}
                document.getElementById('deactivated_features_list').querySelectorAll('input[type="checkbox"]').forEach((el) => {
                    deactivated_features_list[el.id] = el.checked
                })
                // If fxwp plugin should be completely hidden, hide menu items as well
                if (deactivated_features_list['fxwp_deact_hide_plugin']) {
                    deactivated_features_list['fxwp_deact_customer_settings'] = true
                    deactivated_features_list['fxwp_deact_dashboards'] = true
                }
                e.formData.append('fxwp_deactivated_features', JSON.stringify(deactivated_features_list))

                let restricted_features_list = {}
                document.getElementById('restricted_features_list').querySelectorAll('input[type="checkbox"]').forEach((el) => {
                    restricted_features_list[el.id] = el.checked
                })
                // // If fxwp plugin should be completely hidden, hide menu items as well
                // if (restricted_features_list['fxwp_deact_hide_plugin']) {
                //     restricted_features_list['fxwp_deact_customer_settings'] = true
                //     restricted_features_list['fxwp_deact_dashboards'] = true
                // }
                e.formData.append('fxwp_restricted_features', JSON.stringify(restricted_features_list))

                let debugging_options_list = {}
                document.getElementById('fxwp-debugging-options').querySelectorAll('input[type="checkbox"]').forEach((el) => {
                    debugging_options_list[el.id] = el.checked
                })
                e.formData.append('fxwp_debugging_options', JSON.stringify(debugging_options_list))
                console.log(e.formData)
            });
        </script>

    </div>
    <?php
}
