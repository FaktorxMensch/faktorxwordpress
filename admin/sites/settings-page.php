<?php
function fxwp_settings_page()
{
    // check if we want fxwp_api_key_renew
    if (isset($_GET['fxwp_api_key_renew']) && $_GET['fxwp_api_key_renew'] == 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Ihr API-Schlüssel wurde erfolgreich erneuert.', 'fxwp') . '</p></div>';
        fxwp_deactivation();
        fxwp_activation();
    }

    // Check if the plugin is activated
    $api_key = get_option('fxwp_api_key');
    $google_fonts_remove = get_option('fxwp_google_fonts_remove');
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

        <form method="post" action="">
            <?php echo esc_html__('Version', 'fxwp'); ?>
            <?php echo esc_html(FXWP_VERSION); ?>
            <a href="<?php echo esc_url(admin_url('index.php?fxwp_sync=1')); ?>"
            ><?php echo esc_html__('Prüfen auf Updates', 'fxwp'); ?></a>
        </form>

    </div>
    <?php
}

function fxwp_register_settings()
{
    if (current_user_can("fxm_admin")) {
	    register_setting( 'fxwp_settings_group', 'fxwp_api_key' );
	    register_setting( 'fxwp_settings_group', 'fxwp_google_fonts_remove' );
	    register_setting( 'fxwp_settings_group', 'fxwp_view_option', array( 'default' => 'erweitert' ) );
    }
    register_setting('fxwp_settings_group', 'fxwp_favicon');
    register_setting('fxwp_settings_group', 'fxwp_logo');
    register_setting('fxwp_settings_group', 'fxwp_404_page');

}

add_action('admin_init', 'fxwp_register_settings');
