<?php
function fxwp_settings_page()
{
    // Check if the plugin is activated
    $api_key = get_option('fxwp_api_key');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('FXWP Settings', 'fxwp'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fxwp_settings_group');
            do_settings_sections('fxwp_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo esc_html__('Activation Status', 'fxwp'); ?></th>
                    <td>
                        <?php
                        if ($api_key) {
                            echo '<span style="color:green;">' . esc_html__('Activated', 'fxwp') . '</span>';
                        } else {
                            echo '<span style="color:red;">' . esc_html__('Not Activated', 'fxwp') . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('API Key', 'fxwp'); ?></th>
                    <td>
                        <input type="text" name="fxwp_api_key" value="<?php echo esc_attr($api_key); ?>"/>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function fxwp_register_settings()
{
    register_setting('fxwp_settings_group', 'fxwp_api_key');
}

add_action('admin_init', 'fxwp_register_settings');
