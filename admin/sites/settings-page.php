<?php
function fxwp_settings_page()
{
    // check if we want fxwp_api_key_renew
    if (isset($_GET['fxwp_api_key_renew']) && $_GET['fxwp_api_key_renew'] == 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ihr API-Schlüssel wurde erfolgreich erneuert.', 'fxwp') . '</p></div>';
        fxwp_deactivation();
        fxwp_activation();
    }

    // if fxwp_self_update is set
    if(isset($_GET['fxwp_self_update']) && $_GET['fxwp_self_update'] == 'true') {
	    // do hourly which does the update
        fxm_do_this_hourly();

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Das Plugin wurde erfolgreich aktualisiert.', 'fxwp') . '</p></div>';
    }

    // Check if the plugin is activated
    $api_key = get_option('fxwp_api_key');
    $google_fonts_remove = get_option('fxwp_google_fonts_remove');

    if(current_user_can("fxm_admin")) {
        // Deactivated features description
        $deactivated_features_description = array(
            'fxwp_deact_ai' => 'KI Funktionen deaktivieren',
            'fxwp_deact_backups' => 'Backups deaktivieren',
            'fxwp_deact_autoupdates' => 'Automatische Updates deaktivieren',
            'fxwp_deact_email_log' => 'E-Mail Log für Kundis ausblenden',
            'fxwp_deact_shortcodes' => 'Shortcodes für Kundis ausblenden',
            'fxwp_deact_dashboards' => 'Alle Dashboards für Kundis ausblenden',
	        'fxwp_deact_debug_log_widget' => 'Debug Log Widget ausblenden',
            'fxwp_deact_customer_settings' => 'Settings für Kundis komplett ausblenden',
            'fxwp_deact_hide_plugin' => 'Plugin vor Kundis komplett verstecken',
        );
        //Get debugging options description from mods/debug-mod.php
        global $debugging_options_description;

        // Get deactivated features
        $deactivated_features = get_option('fxwp_deactivated_features');
        // if deactivated features is empty, fill it with false
        if (empty($deactivated_features)) {
            $deactivated_features = array_fill_keys(array_keys($deactivated_features_description), false);
        } else {
            $deactivated_features = get_object_vars(json_decode($deactivated_features));
        }
        // Get debugging options
        $debugging_options = get_option('fxwp_debugging_options');
        // if debugging options are empty, fill it with false
        if (empty($debugging_options)) {
	        $debugging_options = array_fill_keys(array_keys($debugging_options_description), false);
        } else {
	        $debugging_options = get_object_vars(json_decode($debugging_options));
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Faktor &times; WordPress Einstellungen', 'fxwp'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fxwp_settings_group');
            do_settings_sections('fxwp_settings_group');
            ?>
            <table class="form-table">
                <!-- Favicon -->
                <tr>
                    <th scope="row"><?php echo esc_html__('Favicon auswählen', 'fxwp'); ?></th>
                    <td>
                        <p><?php echo esc_html__('Wählen Sie Ihr Favicon aus der Medienbibliothek.', 'fxwp'); ?></p>
                        <select name="fxwp_favicon">
                            <option value=""><?php echo esc_html__('Kein Favicon ausgewählt', 'fxwp'); ?></option>
                            <?php
                            $args = array(
                                'post_type' => 'attachment',
                                'post_mime_type' => 'image',
                                'post_status' => 'inherit',
                                'posts_per_page' => -1,
                            );
                            $favicon_id = get_option('fxwp_favicon');
                            $attachments = get_posts($args);
                            foreach ($attachments as $attachment) {
                                // post title or filename lowercase should contain 'favicon' or 'ico' or 'logo'
                                if (strpos(strtolower($attachment->post_title), 'ico') === false && strpos(strtolower($attachment->post_title), 'logo') === false) {
                                    continue;
                                }

                                $selected = '';
                                if ($favicon_id && $attachment->ID === intval($favicon_id)) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . esc_attr($attachment->ID) . '" ' . $selected . '>' . esc_html($attachment->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <?php
                        $favicon_id = get_option('fxwp_favicon');
                        if ($favicon_id) {
                            $favicon_url = wp_get_attachment_url($favicon_id);
                            echo '<img src="' . esc_url($favicon_url) . '" alt="Favicon" width="22" height="22">';
                        }
                        ?>
                    </td>
                </tr>

                <!-- Logo -->
                <tr>
                    <th scope="row"><?php echo esc_html__('Logo auswählen', 'fxwp'); ?></th>
                    <td>
                        <p><?php echo esc_html__('Wählen Sie Ihr Logo aus der Medienbibliothek.', 'fxwp'); ?></p>
                        <select name="fxwp_logo">
                            <option value=""><?php echo esc_html__('Kein Logo ausgewählt', 'fxwp'); ?></option>
                            <?php
                            $args = array(
                                'post_type' => 'attachment',
                                'post_mime_type' => 'image',
                                'post_status' => 'inherit',
                                'posts_per_page' => -1,
                            );
                            $logo_id = get_option('fxwp_logo');
                            $attachments = get_posts($args);
                            foreach ($attachments as $attachment) {
                                // post title or filename lowercase should contain 'favicon' or 'ico' or 'logo'
                                if (strpos(strtolower($attachment->post_title), 'ico') === false && strpos(strtolower($attachment->post_title), 'logo') === false) {
                                    continue;
                                }

                                $selected = '';
                                if ($logo_id && $attachment->ID === intval($favicon_id)) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . esc_attr($attachment->ID) . '" ' . $selected . '>' . esc_html($attachment->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <?php
                        if ($logo_id) {
                            $logo_url = wp_get_attachment_url($logo_id);
                            echo '<img src="' . esc_url($logo_url) . '" alt="Favicon" width="22" height="22">';
                        }
                        ?>
                    </td>
                </tr>

                <!-- 404 page -->
                <tr>
                    <th scope="row"><?php echo esc_html__('404 Seite auswählen', 'fxwp'); ?></th>
                    <td>
                        <p><?php echo esc_html__('Wählen Sie die Seite, die als 404-Seite angezeigt werden soll.', 'fxwp'); ?></p>
                        <select name="fxwp_404_page">
                            <option value=""><?php echo esc_html__('Keine 404-Seite ausgewählt', 'fxwp'); ?></option>
                            <?php
                            $args = array(
                                'post_type' => 'page',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                            );
                            $pages = get_posts($args);
                            $selected_404_page_id = get_option('fxwp_404_page');
                            foreach ($pages as $page) {
                                $selected = '';
                                if ($selected_404_page_id && $page->ID === intval($selected_404_page_id)) {
                                    $selected = 'selected';
                                }
                                echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <!-- Google Fonts if current user is fxm_admin -->
                <?php if(current_user_can('fxm_admin')) { ?>
                    <tr>
                        <th scope="row">
                            <?php echo esc_html__('Google Fonts entfernen', 'fxwp'); ?>
                        </th>
                        <td>
                            <p><?php echo esc_html__('Sollen die Google Fonts entfernt werden?', 'fxwp'); ?></p>
                            <select name="fxwp_google_fonts_remove">
                                <option value="nein" <?php selected($google_fonts_remove, 'nein'); ?>>Nein</option>
                                <option value="einfach" <?php selected($google_fonts_remove, 'einfach'); ?>>Ja, einfach
                                </option>
                                <option value="aggresiv" <?php selected($google_fonts_remove, 'aggresiv'); ?>>Ja, aggresiv
                                </option>
                            </select>
                            <p style="font-size: 0.8em;margin-bottom: 10px;"><?php echo esc_html__('"Ja, einfach" erfordert die Plugin Installation via "Install Helper"', 'fxwp'); ?></p>

                        </td>
                    </tr>
                <?php } ?>

                <!-- View options if current user is fxm_admin -->
                <?php if(current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Ansichtsoptionen', 'fxwp'); ?></th>
                        <td>
                            <p><?php echo esc_html__('Wählen Sie die gewünschte Ansichtsoption aus:', 'fxwp'); ?></p>
                            <label>
                                <input type="radio" name="fxwp_view_option"
                                       value="einfach" <?php checked(get_option('fxwp_view_option', 'einfach'), 'einfach'); ?>>
                                <?php echo esc_html__('Einfache Ansicht', 'fxwp'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="fxwp_view_option"
                                       value="erweitert" <?php checked(get_option('fxwp_view_option'), 'erweitert'); ?>>
                                <?php echo esc_html__('Erweiterte Ansicht', 'fxwp'); ?>
                            </label>
                        </td>
                    </tr>
                <?php } ?>

                <!-- API Key if current user is fxm_admin-->
                <?php if (current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Faktor&times;WP Lizenz', 'fxwp'); ?></th>
                        <td>
                            <p><?php echo esc_html__('Bitte geben Sie Ihren Lizenz Schlüssel ein.', 'fxwp'); ?></p>
                            <div class="flex">
                                <input type="text" name="fxwp_api_key" value="<?php echo esc_attr($api_key); ?>"/>
                                <!-- have a new activation button -->
                                <?php if ($api_key) { ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=fxwp-settings&fxwp_api_key_renew=true')); ?>"
                                       class="button button-secondary"><?php echo esc_html__('Lizenz erneuern', 'fxwp'); ?></a>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>

                <!-- local env? if current user can fxm_admin -->
                <?php if(current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Lokale Umgebung', 'fxwp'); ?></th>
                        <td>
                            <!-- use FXWP_LOCAL_ENV constant -->
                            <p><?php
                                if (defined('FXWP_LOCAL_ENV') && FXWP_LOCAL_ENV) {
                                    echo esc_html__('Sie befinden sich in einer lokalen Umgebung.', 'fxwp');
                                } else {
                                    echo esc_html__('Sie befinden sich nicht in einer lokalen Umgebung.', 'fxwp');
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                <?php } ?>

                <!-- deactivate features if current user can fxm_admin -->
                <?php if(current_user_can("fxm_admin")) { ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Funktionen de-/aktivieren', 'fxwp'); ?></th>
                        <td>
                           <ul class="checkbox-list" id="deactivated_features_list">
                               <?php
                                foreach ($deactivated_features_description as $option => $label) {
                                echo "<li><input type='checkbox' name='{$option}' id='{$option}'";
                                if ($deactivated_features[$option]) {
                                    echo " checked value='true'";
                                } else {
                                    echo " value='false'";
                                }
                                echo "/><label for='{$option}'>{$label}</label></li>";
                               } ?>
                           </ul>
                            <p style="color: #E88813"><?php echo __('Achtung: Entfernte Haken aktivieren Features nicht direkt wieder! (zb Auto Update muss manuell noch gestaret werden)', 'fxwp'); ?></p>
                        </td>
                    </tr>
                <?php } ?>

                <!-- change debugging mode -->
	            <?php if (current_user_can("fxm_admin")) { ?>
                    <tr id="fxwp-debugging-options">
                        <th scope="row"><?php echo esc_html__('Debugging de-/aktivieren', 'fxwp'); ?></th>
                        <td>
                            <ul class="checkbox-list" id="deactivated_features_list">
			                    <?php
			                    foreach ($debugging_options_description as $option => $label) {
				                    echo "<li><input type='checkbox' name='{$option}' id='{$option}'";
				                    if ($debugging_options[$option]) {
					                    echo " checked value='true'";
				                    } else {
					                    echo " value='false'";
				                    }
				                    echo "/><label for='{$option}'><code>{$label}</code></label></li>";
			                    } ?>
                            </ul>
                        </td>
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
                    <tr>
                        <th scope="row"><?php echo esc_html__('Kunde', 'fxwp'); ?></th>
                        <td><p><?php print_r(get_option('fxwp_customer')); ?></p></td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Projekt', 'fxwp'); ?></th>
                        <td><p><?php print_r(get_option('fxwp_project')); ?></p></td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Pläne', 'fxwp'); ?></th>
                        <td><p><?php print_r(get_option('fxwp_plans')); ?></p></td>
                    </tr>
                <?php } ?>

            </table>
            <?php submit_button(); ?>
        </form>

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
                <path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z" />
            </svg>
            <form method="post" action="index.php?fxwp_sync=1" class="tag-update">
                <input type="text" name="fxwp_self_update_tag" placeholder="Tag" required style="width:60px"/>
                <input type="submit" class="button button-primary" value="<?php echo esc_html__('manuell installieren', 'fxwp'); ?>" />
            </form>
        <?php } ?>
    </div>
    <style>
        form.inline {
            display: inline;
        }
        .inline svg {
            display: inline;
            margin-bottom: -5px;
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
        let debugging_options_list = {}
        document.getElementById('fxwp-debugging-options').querySelectorAll('input[type="checkbox"]').forEach((el) => {
            debugging_options_list[el.id] = el.checked
        })
        e.formData.append('fxwp_debugging_options', JSON.stringify(debugging_options_list))
        console.log(e.formData)
    });
    </script>
    <?php
}

function fxwp_register_settings()
{
    if (current_user_can("fxm_admin")) {
	    register_setting( 'fxwp_settings_group', 'fxwp_api_key' );
	    register_setting( 'fxwp_settings_group', 'fxwp_google_fonts_remove' );
	    register_setting( 'fxwp_settings_group', 'fxwp_view_option', array( 'default' => 'erweitert' ) );
	    register_setting( 'fxwp_settings_group', 'fxwp_deactivated_features');
	    register_setting( 'fxwp_settings_group', 'fxwp_debugging_options');

    }
    register_setting('fxwp_settings_group', 'fxwp_favicon');
    register_setting('fxwp_settings_group', 'fxwp_logo');
    register_setting('fxwp_settings_group', 'fxwp_404_page');

}

add_action('admin_init', 'fxwp_register_settings');
