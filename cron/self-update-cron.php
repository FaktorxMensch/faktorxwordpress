<?php

// this function is called by the cron job and checks if there is a new version of the plugin
function fxwp_self_update()
{
    // Define the URL of the remote config file
    $config_url = 'https://raw.githubusercontent.com/ziegenhagel/faktorxwordpress/main/includes/config.php';

    // Initialize a cURL session
    $curl = curl_init();

    // Set the cURL options
    curl_setopt($curl, CURLOPT_URL, $config_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);

    // Execute the cURL request and get the response
    $config_content = curl_exec($curl);

    // Close the cURL session
    curl_close($curl);

    // Extract the version information from the config file
    $version_match = preg_match("/define\('FXWP_VERSION',\s*'(.*?)'\);/", $config_content, $matches);

    if (!$version_match) {
        // The version information could not be extracted, abort
        return;
    }

    $remote_version = $matches[1];
    $current_version = FXWP_VERSION;

    if (version_compare($remote_version, $current_version, '>')) {
        // The remote version is newer, perform self-update

        // Define the URL of the GitHub repository's ZIP archive
        $repo_url = 'https://github.com/ziegenhagel/faktorxwordpress/archive/main.zip';

        // Define the directory where the plugin files are located
        $plugin_directory = plugin_dir_path(__FILE__) . "../";

        // Define the path of the ZIP archive
        $zip_path = $plugin_directory . 'temp.zip';

        // Define the path of the temporary directory
        $temp_directory = $plugin_directory . 'temp/';

        // Initialize a new cURL session
        $curl = curl_init();

        // Set the cURL options
        curl_setopt($curl, CURLOPT_URL, $repo_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);

        // Execute the cURL request and get the ZIP archive
        $zip_content = curl_exec($curl);

        // Close the cURL session
        curl_close($curl);

        // Save the ZIP archive
        file_put_contents($zip_path, $zip_content);

        // Open the ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zip_path)) {
            // Extract the ZIP archive to the temporary directory
            $zip->extractTo($temp_directory);
            $zip->close();

            // Initialize the WordPress filesystem object
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }

            // Copy the files from the temporary directory to the plugin directory
            copy_dir($temp_directory . 'faktorxwordpress-main/', $plugin_directory);

            // Delete the temporary directory
            $wp_filesystem->delete($temp_directory, true);

        }

        // Delete the ZIP archive
        unlink($zip_path);

        // Log the self-update
        error_log('Self-update performed successfully.');
    } else {
        // The plugin is already up to date
        error_log('No new update available.');
    }

}

// add the cron job
add_action('fxwp_self_update', 'fxwp_self_update');

// schedule the cron job
if (!wp_next_scheduled('fxwp_self_update')) {
    wp_schedule_event(time(), 'daily', 'fxwp_self_update');
}
