<?php
function fxwp_plugin_list_installer_page()
{
    // Define your plugin collections
    $plugin_collections = array(
        'Working with Posts' => array(
            'pods',
            'post-types-order'
        ),
        'Site Improvement' => array(
            'broken-link-checker',
            'wp-super-cache'
        )
        // add more collections as needed
    );

    // Check if a collection has been selected
    if (isset($_POST['plugin_collection'])) {
        $selected_collection = $_POST['plugin_collection'];

        // Install and activate the plugins in the selected collection
        foreach ($plugin_collections[$selected_collection] as $plugin) {
            // Specify the source of the plugin
            $plugin_source = "https://downloads.wordpress.org/plugin/{$plugin}.zip";  // Update this URL

            // Include the necessary WordPress files
            include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            include_once ABSPATH . 'wp-admin/includes/plugin.php';

            // Use WordPress's Plugin_Upgrader class to install the plugin
            $upgrader = new Plugin_Upgrader();
            $installed = $upgrader->install($plugin_source);

            // Check if the installation was successful
            if (!is_wp_error($installed) && $installed) {
                // Activate the plugin
                $result = activate_plugin($plugin);

                // Check if the activation was successful
                if (is_null($result)) {
                    echo "<p>{$plugin} installed and activated successfully.</p>";
                } else {
                    echo "<p>Failed to activate {$plugin}.</p>";
                    // have a meta refresh to plugins.php
                    echo '<meta http-equiv="refresh" content="1;url=' . admin_url('plugins.php') . '">';
                }
            } else {
                echo "<p>Failed to install {$plugin}.</p>";
            }
        }
    }

    // wordpress page
    echo '<div class="wrap">';
    echo '<h1>Plugin List Installer</h1>';

    // Display the form to select a plugin collection
    echo '<form method="post" action="">';
    echo '<select name="plugin_collection">';
    foreach ($plugin_collections as $name => $plugins) {
        echo "<option value='{$name}'>{$name}</option>";
    }
    echo '</select>';
    echo '<input type="submit" value="Install" class="button button-primary button-large"/>';
    echo '</form>';
    echo '</div>';

}

