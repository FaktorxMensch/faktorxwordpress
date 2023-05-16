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
    register_setting('fxwp_settings_group', 'fxwp_google_fonts_remove');
}

add_action('admin_init', 'fxwp_register_settings');
