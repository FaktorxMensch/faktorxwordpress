<?php
function fxwp_settings_page()
{

    // check if self update was triggered
    if (isset($_GET['fxwp_self_update']) && $_GET['fxwp_self_update'] == 'true') {
        fxwp_self_update();
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

                <th scope="row"><?php echo esc_html__('Aktuelles Favicon', 'fxwp'); ?></th>
                <td>
                    <?php
                    $favicon_id = get_option('fxwp_favicon');
                    if ($favicon_id) {
                        $favicon_url = wp_get_attachment_url($favicon_id);
                        echo '<img src="' . esc_url($favicon_url) . '" alt="Favicon" width="22" height="22">';
                    }
                    ?>
                </td>

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
                </tr>

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
                </tr>
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


                <tr>
                    <th scope="row"><?php echo esc_html__('API Schlüssel', 'fxwp'); ?></th>
                    <td>
                        <p><?php echo esc_html__('Bitte geben Sie Ihren API Schlüssel ein.', 'fxwp'); ?></p>
                        <input type="text" name="fxwp_api_key" value="<?php echo esc_attr($api_key); ?>"/>
                    </td>
                </tr>
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

                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <form method="post" action="">
            <input type="hidden" name="fxwp_self_update" value="true"/>
            <?php wp_nonce_field('fxwp_self_update', 'fxwp_self_update_nonce'); ?>
            <?php echo esc_html__('Version', 'fxwp'); ?>
            <?php echo esc_html(FXWP_VERSION); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=fxwp-settings&fxwp_self_update=true')); ?>"
            ><?php echo esc_html__('Prüfen auf Updates', 'fxwp'); ?></a>
        </form>

    </div>
    <?php
}

function fxwp_register_settings()
{
    register_setting('fxwp_settings_group', 'fxwp_api_key');
    register_setting('fxwp_settings_group', 'fxwp_favicon');
    register_setting('fxwp_settings_group', 'fxwp_logo');
    register_setting('fxwp_settings_group', 'fxwp_google_fonts_remove');
    register_setting('fxwp_settings_group', 'fxwp_404_page');

}

add_action('admin_init', 'fxwp_register_settings');
