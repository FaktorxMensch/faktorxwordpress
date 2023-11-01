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

            error_log("Current version: " . $current_version . " Latest version: " . $latest_version);

            if (version_compare($current_version, $latest_version, '<')) {
                //error_log( "fxm_care_do_update" );
                // New update available, trigger the update process.
                $output = array();
                exec("which git", $output);
                if (!in_array("not found", $output)) {
                    error_log("Git is installed");
                    fxm_git_plugin_updater($latest_version_git);
                } else {
                    error_log("Git is not installed, trying to upgrade plugin anyways...");
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-error is-dismissible"><p>Git is not installed on your server. Plugin will try to update the old way but withour any warranty.</p></div>';
                    });
                    fxm_NO_git_plugin_updater($latest_version_git);
                    return;
                }
            } else {
                error_log("No update available");
                add_action('admin_notices', function () use ($latest_version_git) {
                    echo '<div class="notice notice-info is-dismissible"><p>FXWP plugin is up to date, current version is ' . $latest_version_git . '.</p></div>';
                });
            }
        }


        error_log("=====================================================");

    }
}

function fxm_git_plugin_updater($latest_version_git)
{

    //If plugin includes a .git folder, use git to update
    $plugin_git_dir = FXWP_PLUGIN_DIR . "/.git";
    if (file_exists($plugin_git_dir)) {
        error_log("Plugin includes a .git folder, using git to update");
        $output = array();
        exec("cd " . FXWP_PLUGIN_DIR . " && git pull origin master", $output);
        error_log("Git output: " . implode("\n", $output));
        add_action('admin_notices', function () use ($latest_version_git) {
            echo '<div class="notice notice-success is-dismissible"><p>FXWP plugin has been updated to version ' . $latest_version_git . '.</p></div>';
        });
    } else {
        //Delete plugin folder and clone project from git
        error_log("Plugin does not include a .git folder, deleting plugin folder and cloning project from git");

        $output = array();
        exec("cd " . FXWP_PLUGIN_DIR . ' && find . -path "*/*" -delete', $output);

        $output = array();
        exec("git clone https://github.com/ziegenhagel/faktorxwordpress.git .", $output);
        error_log("Git output: " . implode("\n", $output));
        add_action('admin_notices', function () use ($latest_version_git) {
            echo '<div class="notice notice-success is-dismissible"><p>FXWP plugin has been updated via git to version ' . $latest_version_git . '.</p></div>';
        });
    }
}


function fxm_NO_git_plugin_updater($latest_version_git)
{

    $latest_version = $latest_version_git;
    if (strpos($latest_version_git, 'v') !== false) {
        $latest_version = str_replace("v", "", $latest_version_git);
    }

    // Step 0: Initialize the WordPress filesystem.
    require_once ABSPATH . 'wp-admin/includes/file.php';
    WP_Filesystem();

    // Step 1: Download the latest plugin ZIP file from GitHub.
    $zip_url = 'https://github.com/ziegenhagel/faktorxwordpress/archive/' . $latest_version_git . '.zip';
    $temp_file = download_url($zip_url);

    // Step 2: Check if the download was successful.
    if (!is_wp_error($temp_file)) {

        // Step 3: Unzip the downloaded file and overwrite the existing plugin files.
        $unzip_result = unzip_file($temp_file, WP_PLUGIN_DIR);

        if (is_wp_error($unzip_result)) {
            // Error occurred while unzipping.
            // You may want to handle this case gracefully.
            error_log("Error occurred while unzipping.");
        } else {
            // Successful update.

            $extracted_root_folder = trailingslashit(WP_PLUGIN_DIR) . basename(FXWP_PLUGIN_DIR) . '-' . $latest_version;

            fxm_move_directory_contents($extracted_root_folder, FXWP_PLUGIN_DIR);
            fxm_recursive_delete($extracted_root_folder);

            // Show a success message to the admin.
            add_action('admin_notices', function () use ($latest_version) {
                echo '<div class="notice notice-success is-dismissible"><p>FXWP plugin has been updated to version ' . $latest_version . '.</p></div>';
            });
        }

        // Step 4: Clean up the temporary ZIP file.
        unlink($temp_file);

    } else {
        // Error occurred while downloading the update.
        // You may want to handle this case gracefully.
        error_log("Error occurred while downloading the update.");
    }

}

// Helper function to move the contents from one directory to another.
function fxm_move_directory_contents($src, $dest)
{
    $files = glob($src . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $file_dest = $dest . '/' . basename($file);
            copy($file, $file_dest);
            unlink($file);
        }
    }
}


// Helper function to recursively delete a directory and its contents.
function fxm_recursive_delete($path)
{
    if (is_file($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            fxm_recursive_delete(realpath($path) . 'auto_updater-cron.php/' . $file);
        }
        return rmdir($path);
    }
    return false;
}