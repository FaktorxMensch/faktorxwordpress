<?php
function fxwp_settings_page()
{
    // check if we want fxwp_api_key_renew
    if (isset($_GET['fxwp_api_key_renew']) && $_GET['fxwp_api_key_renew'] == 'true') {
        // DEPRECATED
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ihr API-Schlüssel wurde erfolgreich erneuert.', 'fxwp') . '</p></div>';
        fxwp_deactivation();
        fxwp_activation();
    }

    // if fxwp_self_update is set
    if (isset($_GET['fxwp_self_update']) && $_GET['fxwp_self_update'] == 'true') {
        // do hourly which does the update
        fxm_do_this_hourly();

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Das Plugin wurde erfolgreich aktualisiert.', 'fxwp') . '</p></div>';
    }

    // Check if the plugin is activated
    $api_key = get_option('fxwp_api_key');
    $google_fonts_remove = get_option('fxwp_google_fonts_remove');

    if (current_user_can("fxm_admin")) {
        // Deactivated features description
        $deactivated_features_description = array(
            'fxwp_deact_ai' => 'KI Funktionen deaktivieren',
            'fxwp_deact_backups' => 'Backups deaktivieren',
            'fxwp_deact_autoupdates' => 'Automatische Updates deaktivieren',
            'fxwp_deact_email_log' => 'E-Mail Log für Kundis ausblenden',
            'fxwp_deact_shortcodes' => 'Shortcodes für Kundis ausblenden',
            'fxwp_deact_dashboards' => 'Alle Dashboards für Kundis ausblenden',
            'fxwp_deact_debug_log_widget' => 'Debug Log Widget ausblenden',
            'fxwp_deact_customer_settings' => 'Plugin Settings für Kundis komplett ausblenden',
            'fxwp_deact_hide_plugin' => 'Plugin vor Kundis komplett verstecken',
        );
        // Restricted features description
        $restricted_features_description = array(
            'fxwp_restr_pages' => 'Seiten',
            'fxwp_restr_posts' => 'Blogposts',
            'fxwp_restr_uploads' => 'Mediendateien',
            'fxwp_restr_themes' => 'Themes',
            'fxwp_restr_updates-submenu' => 'Updates Submenu von Dashboard',
            'fxwp_restr_elememtor-templates' => 'Elementor Templates',
            'fxwp_restr_wpcf7' => 'Contact Form 7',
            'fxwp_restr_new-button' => 'Admin Bar New Button',
            'fxwp_restr_updates-indicator' => 'Admin Bar Updates Indicator',
            'fxwp_restr_my-account' => 'Admin Bar Account',
            'fxwp_restr_admin_plugins' => 'Plugins',
            'fxwp_restr_admin_users' => 'Benutzer',
            'fxwp_restr_admin_tools' => 'Tools',
            'fxwp_restr_admin_settings' => 'WP Einstellungen',
            'fxwp_restr_admin_elementor' => 'Elementor Einstellungen',
            'fxwp_restr_admin_eael' => 'Essential Addons for Elementor Einstellungen',
        );
        //Get debugging options description from mods/debug-mod.php
        global $debugging_options_description;

        // Get deactivated features
        $deactivated_features = fxwp_get_deact();
        // if deactivated features is empty, fill it with false or if it cannot be json parsed
        /*
        if (empty($deactivated_features) || strlen($deactivated_features) < 5) {
            $deactivated_features = array_fill_keys(array_keys($deactivated_features_description), false);
        } else {
            $deactivated_features = get_object_vars(json_decode($deactivated_features));
        }
        */
        // Get debugging options
//        $debugging_options = get_option('fxwp_debugging_options');
        $debugging_options = fxwp_get_debugging();
        // if debugging options are empty, fill it with false
//        if (empty($debugging_options)) {
//            $debugging_options = array_fill_keys(array_keys($debugging_options_description), false);
//        } else {
//            $debugging_options = get_object_vars(json_decode($debugging_options));
//        }
        // Get deactivated features
        $restricted_features = fxwp_get_restr();
        // if deactivated features is empty or shorter than 5 chars, fill it with false
    }

    /* eine liste mit den wp options verwalteten daten anzeigen, die man einstellen kann, darunter der datentyp des feldes, titel beschreibung (optional), default wert. das ganze in kategorien unterteilt */
    /* aktuell enthält sie nur fxwp_storage_limitfxwp_storage_limit und fxm_customer_update_dashboardfxm_customer_update_dashboard */
    $fxm_options = array(
        // kategorie "Weitere Einstellungen"
        array(
            'title' => 'Weitere Einstellungen',
            'options' => array(
                'fxwp_storage_limit' => array(
                    'type' => 'filesize',
                    'title' => 'Speicherlimit',
                    'description' => 'Das Speicherlimit, das auf der Webseite verwendet werden kann.',
                    'default' => 20 * 1024 * 1024 * 1024, // 20GB
                ),
                'fxm_customer_update_dashboard' => array(
                    'type' => 'checkbox',
                    'title' => 'Kunden Update Dashboard',
                    'description' => 'Wenn true, wird eine Unterseite für Kund:innen angezeigt, die selbst Updates machen wollen.',
                    'default' => false,
                ),
            ),
        ),
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Faktor &times; WordPress Einstellungen', 'fxwp'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fxwp_settings_group');
            do_settings_sections('fxwp_settings_group');
            ?>
            <table class="form-table">

                <!-- View options if current user is fxm_admin -->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Ansichtsoptionen', 'fxwp'); ?></th>
                        <td>Wurde zu Options gemoved.</td>
                    </tr>
                <?php } ?>

                <!-- API Key if current user is fxm_admin-->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Faktor&times;WP Lizenz', 'fxwp'); ?></th>
                        <td>Wurde zu Options gemoved.</td>
                    </tr>
                <?php } ?>

                <!-- local env? if current user can fxm_admin -->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Lokale Umgebung', 'fxwp'); ?></th>
                        <td>
                            Wurde zu Options gemoved.
                        </td>
                    </tr>
                <?php } ?>

                <!-- deactivate features if current user can fxm_admin -->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Funktionen de-/aktivieren', 'fxwp'); ?></th>
                        <td>Wurde zu Options gemoved.</td>
                    </tr>
                <?php } ?>

                <!-- restrict features if current user can fxm_admin -->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Menüseiten ausblenden', 'fxwp'); ?></th>
                        <td>Wurde zu Options gemoved.</td>
                    </tr>
                <?php } ?>

                <!-- change debugging mode -->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr id="fxwp-debugging-options">
                        <th scope="row"><?php echo esc_html__('Debugging de-/aktivieren', 'fxwp'); ?></th>
                        <td>Wurde zu Options gemoved.</td>
                    </tr>
                    <script>
                        <!--                        Flash this area if url contains #fxwp-debugging -->
                        if (window.location.hash === '#fxwp-debugging') {
                            //Instead of making background red, show red border
                            document.getElementById('fxwp-debugging').style.border = '5px solid #E88813';
                            //remove flash 1s later
                            setTimeout(function () {
                                document.getElementById('fxwp-debugging').style.border = 'none';
                            }, 500);
                        }
                    </script>
                <?php } ?>

                <!-- print get_option for fxwp_customer and fxwp_project -->
                <!-- only if current user is fxm_admin -->
                <?php if (current_user_can("fxm_admin")) { ?>

                    <tr class="jsondata">
                        <th scope="row"><?php echo esc_html__('Kunde', 'fxwp'); ?></th>
                        <td>
                            <pre class="scroll-box"><?php print_r(get_option('fxwp_customer')); ?></pre>
                        </td>
                    </tr>

                    <tr class="jsondata">
                        <th scope="row"><?php echo esc_html__('Projekt', 'fxwp'); ?></th>
                        <td>
                            <pre class="scroll-box"><?php print_r(get_option('fxwp_project')); ?></pre>
                        </td>
                    </tr>

                    <tr class="jsondata">
                        <th scope="row"><?php echo esc_html__('Pläne', 'fxwp'); ?></th>
                        <td colspan="2">
                            <pre class="scroll-box"><?php print_r(get_option('fxwp_plans')); ?></pre>
                        </td>
                    </tr>

                    <!-- .jsondata per deafult ausblenden und mit einem button einblenden können via js -->
                    <script>
                        document.querySelectorAll('.jsondata').forEach((el) => {
                            el.style.display = 'none';
                        });
                    </script>

                    <tr>
                        <td colspan="2"></td>
                    </tr>


                <?php } ?>
            </table>
            <?php submit_button(); ?>
        </form>


        <?php
        // fxm_format_bytes
        function fxm_format_bytes($bytes, $precision = 2)
        {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            $bytes /= (1 << (10 * $pow));

            return round($bytes, $precision) . $units[$pow];
        }

        ?>

        <!-- OPTIONS -->


    </div>
    <?php
}

function fxwp_register_settings()
{
    if (current_user_can("fxm_admin")) {
        register_setting('fxwp_settings_group', 'fxwp_api_key');
        register_setting('fxwp_settings_group', 'fxwp_google_fonts_remove');
        register_setting('fxwp_settings_group', 'fxwp_view_option', array('default' => 'erweitert'));
        register_setting('fxwp_settings_group', 'fxwp_deactivated_features');
        register_setting('fxwp_settings_group', 'fxwp_debugging_options');
        register_setting('fxwp_settings_group', 'fxwp_restricted_features');


    }
    register_setting('fxwp_settings_group', 'fxwp_favicon');
    register_setting('fxwp_settings_group', 'fxwp_logo');
    register_setting('fxwp_settings_group', 'fxwp_404_page');

}

add_action('admin_init', 'fxwp_register_settings');
