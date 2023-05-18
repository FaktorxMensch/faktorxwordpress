<?php
// this is an installer & updater for the fxwp theme
// it installs theme from url php code in wordpress without using command line exec like stuff
// it also updates the theme if it is already installed and the version is different
// url is FXWP_THEME_REPO_URI

function fxwp_install_theme()
{

    $url = FXWP_THEME_REPO_URI;

    // Define the path where the theme will be unpacked.
    $theme_dir = get_theme_root();

    // Include the necessary libraries.
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    // Download file to temp dir.
    $temp_file = download_url($url);

    // Check for download errors.
    if (is_wp_error($temp_file)) {
        return $temp_file;
    }

    // Unpack the downloaded package file.
    $result = unzip_file($temp_file, $theme_dir);
    // Return success.
    if (is_wp_error($result)) {
        // Handle any errors.
        echo 'Error: ' . $result->get_error_message();
        return false;
    } else {
        echo 'Theme installed successfully.';

        // Delete the temporary file.
        @unlink($temp_file);

        $theme_dir_name = 'faktorxwordpress-theme';

        // activate the theme
        switch_theme($theme_dir_name);

        return true;
    }
}
