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
        $github_api_url = 'https://api.github.com/repos/FaktorxMensch/faktorxwordpress/releases/latest';

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
                    echo '<div class="notice notice-info is-dismissible"><p>FXWP plugin is up-to-date, current version is ' . $latest_version_git . ' or higher.</p></div>';
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
	if ($debug_update === true) {
		$zip_url = 'https://github.com/FaktorxMensch/faktorxwordpress/archive/refs/tags/' . $latest_version_git . '.zip';
	} else {
		$zip_url = 'https://github.com/FaktorxMensch/faktorxwordpress/archive/' . $latest_version_git . '.zip';
	}

    // With a signing key configured, a release is only installed after its
    // signature has been checked (see includes/update-signature.php).
    if (fxwp_update_signing_enabled()) {
        return fxwp_verified_plugin_update($latest_version_git, $zip_url);
    }

	$temp_file = download_url($zip_url);

    if (is_wp_error($temp_file)) {
	    echo '<div class="notice notice-error is-dismissible"><p>Error downloading update:  ' . $temp_file->get_error_message() . '.</p></div>';
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

/**
 * Install a release only if its signature matches.
 *
 * Differences to the unchecked path above: the signature (release.sig) is
 * fetched first, the archive is unpacked into a randomly named staging folder
 * (so unverified PHP files are never reachable at a guessable URL), and the
 * files are only copied over the plugin once the signature has been verified.
 *
 * @return true|WP_Error
 */
function fxwp_verified_plugin_update($tag, $zip_url)
{
    $abort = function ($reason, $staging = null) use ($tag) {
        if ($staging !== null) {
            fxwp_update_remove_staging($staging);
        }
        error_log('fxwp update to ' . $tag . ' NOT installed: ' . $reason);
        add_action('admin_notices', function () use ($tag, $reason) {
            echo '<div class="notice notice-error is-dismissible"><p>'
                . esc_html('FXWP update to ' . $tag . ' was not installed: ' . $reason) . '</p></div>';
        });
        return new WP_Error('fxwp_update_rejected', $reason);
    };

    $sig = fxwp_update_fetch_signature((string)$tag);
    if (is_wp_error($sig)) {
        return $abort($sig->get_error_message());
    }

    $temp_file = download_url($zip_url);
    if (is_wp_error($temp_file)) {
        return $abort('download failed: ' . $temp_file->get_error_message());
    }

    $staging = trailingslashit(WP_CONTENT_DIR) . 'upgrade/fxwp-update-' . bin2hex(random_bytes(10));
    if (!wp_mkdir_p($staging)) {
        @unlink($temp_file);
        return $abort('could not create the staging folder');
    }

    $unzipped = unzip_file($temp_file, $staging);
    @unlink($temp_file);
    if (is_wp_error($unzipped)) {
        return $abort('unpacking failed: ' . $unzipped->get_error_message(), $staging);
    }

    // A GitHub archive contains exactly one top-level folder.
    $roots = glob($staging . '/*', GLOB_ONLYDIR);
    $entries = glob($staging . '/*');
    if (!is_array($roots) || count($roots) !== 1 || !is_array($entries) || count($entries) !== 1) {
        return $abort('unexpected archive layout', $staging);
    }

    $check = fxwp_update_verify_dir($roots[0], (string)$tag, $sig, fxwp_update_public_keys());
    if ($check !== true) {
        return $abort($check, $staging);
    }

    recurseCopy($roots[0], FXWP_PLUGIN_DIR);
    fxwp_update_remove_staging($staging);

    error_log('fxwp updated to ' . $tag . ' (signature verified)');
    add_action('admin_notices', function () use ($tag) {
        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html('FXWP plugin has been updated to version ' . $tag . ' (signature verified).') . '</p></div>';
    });
    return true;
}

/**
 * Delete a staging folder created by fxwp_verified_plugin_update() -- and
 * nothing else: the path must be an fxwp-update-* folder inside wp-content/upgrade.
 */
function fxwp_update_remove_staging($staging)
{
    $staging = rtrim((string)$staging, '/\\');
    $prefix = trailingslashit(WP_CONTENT_DIR) . 'upgrade/fxwp-update-';
    if (strpos($staging, $prefix) !== 0 || !preg_match('/^[0-9a-f]{20}$/', substr($staging, strlen($prefix))) || !is_dir($staging)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($staging, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        if ($file->isDir() && !$file->isLink()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($staging);
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