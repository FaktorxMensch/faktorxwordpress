<?php

// this function is called by the cron job and checks if there is a new version of the plugin
function fxwp_self_update()
{
    // Define the URL of the remote config file
    $config_url = 'https://raw.githubusercontent.com/ziegenhagel/fxwp/main/includes/config.php';

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
    $version_match = preg_match("/define\('fxwp_VERSION',\s*'(.*?)'\);/", $config_content, $matches);

    if ($version_match) {
        $remote_version = $matches[1];
        $current_version = fxwp_VERSION;

        // Compare the version from the remote config with the current version
        if (version_compare($remote_version, $current_version, '>')) {



        } else {
            // The plugin is already up to date
            error_log('No new update available.');
        }
    } else {
        // Failed to extract version information from the config file
        error_log('Unable to retrieve remote version information.');
    }
}

// add the cron job
add_action('fxwp_self_update', 'fxwp_self_update');

// schedule the cron job
if (!wp_next_scheduled('fxwp_self_update')) {
    wp_schedule_event(time(), 'daily', 'fxwp_self_update');
}
