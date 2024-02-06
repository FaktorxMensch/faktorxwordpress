<?php

if(!function_exists('fxwp_self_update')) {
    function fxwp_self_update()
    {
        fxm_do_this_hourly();
    }
}

add_action('fxm_hourly_event', 'fxm_do_this_hourly');

if (!function_exists('fxm_do_this_hourly')) {
    function fxm_do_this_hourly()
    {
        error_log("================  FXWP Plugin Update  ====================");
        error_log("Local env: " . (FXWP_LOCAL_ENV ? "true" : "false"));
//		if (FXWP_LOCAL_ENV) {
//			error_log("Not checking for updates in local environment");
//			add_action( 'admin_notices', function (){
//				echo '<div class="notice notice-error is-dismissible"><p>You are running the plugin on a localhost. Plugin will not update.</p></div>';
//			} );
//			return;
//		}

        $plugin_data = get_plugin_data(FXWP_PLUGIN_DIR . '/faktorxwordpress.php');
        $current_version = $plugin_data['Version'];
        $github_api_url = 'https://api.github.com/repos/ziegenhagel/faktorxwordpress/releases/latest';

        $response = wp_remote_get($github_api_url);

        if (!is_wp_error($response) && $response['response']['code'] === 200) {
            $github_data = json_decode($response['body'], true);
            $latest_version_git = $github_data['tag_name'];

            //if version contains "v" remove it
            $latest_version = $latest_version_git;
            if (strpos($latest_version_git, 'v') !== false) {
                $latest_version = str_replace("v", "", $latest_version_git);
            }

            error_log("Currently installed version: " . $current_version . " Latest version: " . $latest_version);

            if (version_compare($current_version, $latest_version, '<')) {
                fxm_plugin_updater($latest_version_git);
                return;
            } else {
                error_log("No update available");
                add_action('admin_notices', function () use ($latest_version_git) {
                    echo '<div class="notice notice-info is-dismissible"><p>FXWP plugin is up-to-date, current version is ' . $latest_version_git . '.</p></div>';
                });
            }
        }


        error_log("=====================================================");

    }
}

function fxm_plugin_updater($latest_version_git, $debug_update = null)
{

    $latest_version = $latest_version_git;
    if (strpos($latest_version_git, 'v') !== false) {
        $latest_version = str_replace("v", "", $latest_version_git);
    }

    // Step 0: Initialize the WordPress filesystem.
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();

    // Step 1: Download the latest plugin ZIP file from GitHub.
	$zip_url = null;
	if ($debug_update) {
		$zip_url = 'https://github.com/ziegenhagel/faktorxwordpress/archive/refs/tags/' . $latest_version_git . '.zip';
	} else {
		$zip_url = 'https://github.com/ziegenhagel/faktorxwordpress/archive/' . $latest_version_git . '.zip';
	}
	$temp_file = download_url($zip_url);

    if (is_wp_error($temp_file)) {
        error_log("Error occurred while downloading the update. Maybe disk space is full?");
        error_log($temp_file->get_error_message());
        return new WP_Error('download_error', __('Something went wrong while downloading the latest plugin ZIP file. Maybe disk space is full?', 'fxwp'));
    } else {
        error_log("Downloaded update to " . $temp_file);
    }

    try {

        // Step 2: Check if the download was successful.
        if (!is_wp_error($temp_file)) {

            // Step 3: Unzip the downloaded file
            $unzip_result = unzip_file($temp_file, WP_PLUGIN_DIR);

            if (is_wp_error($unzip_result)) {
                throw new Exception('Error occurred while unzipping the update.');
            } else {
                // Successful update.

                $extracted_root_folder = trailingslashit(WP_PLUGIN_DIR) . pathinfo($temp_file, PATHINFO_FILENAME);

                //Check if $extracted_root_folder exists, otherwise throw error
                if (!file_exists($extracted_root_folder)) {
                    throw new Exception('Name of unzipped folder does not match expected name.');
                }
                recurseCopy($extracted_root_folder, FXWP_PLUGIN_DIR);

                // Show a success message to the admin.
                add_action('admin_notices', function () use ($latest_version) {
                    echo '<div class="notice notice-success is-dismissible"><p>FXWP plugin has been updated to version ' . $latest_version . '.</p></div>';
                });
            }

            // Step 4: Clean up the temporary ZIP file.
            unlink($temp_file);

        } else {
            throw new Exception('Error occurred while downloading the update.');
        }

    } catch (Exception $e) {
        error_log("Error occurred: " . $e->getMessage());
        // Show a error message to the admin.
        add_action('admin_notices', function () use ($latest_version) {
            echo '<div class="notice notice-error is-dismissible"><p>Error occurred while updating.</p></div>';
        });
    }

}

// Helper function to move the contents from one directory to another.
function recurseCopy(
    string $sourceDirectory,
    string $destinationDirectory
): void {
    $directory = opendir($sourceDirectory);

    if (is_dir($destinationDirectory) === false) {
        mkdir($destinationDirectory);
    }

    while (($file = readdir($directory)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        if (is_dir("$sourceDirectory/$file") === true) {
            recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
        else {
            copy("$sourceDirectory/$file", "$destinationDirectory/$file");
            unlink("$sourceDirectory/$file");
        }
    }

    closedir($directory);
    rmdir($sourceDirectory);
}