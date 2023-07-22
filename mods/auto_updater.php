<?php


add_action( 'fxm_hourly_event', 'fxm_do_this_hourly' );

if ( ! function_exists( 'fxm_do_this_hourly' ) ) {
	function fxm_do_this_hourly() {
		error_log( "=====================================================" );
		error_log( "Local env: " . ( FXWP_LOCAL_ENV ? "true" : "false" ) );
		error_log( "Plugin dir: " . FXWP_PLUGIN_DIR );

		$plugin_data = get_plugin_data( FXWP_PLUGIN_DIR . '/faktorxwordpress.php' );
		$current_version = $plugin_data['Version'];
		$github_api_url = 'https://api.github.com/repos/ziegenhagel/faktorxwordpress/releases/latest';

		$response = wp_remote_get( $github_api_url );

		if ( ! is_wp_error( $response ) && $response['response']['code'] === 200 ) {
			$github_data = json_decode( $response['body'], true );
			$latest_version_git = $github_data['tag_name'];

			//if version contains "v" remove it
			$latest_version = $latest_version_git;
			if (strpos($latest_version_git, 'v') !== false) {
				$latest_version = str_replace("v", "", $latest_version_git);
			}

			error_log("Current version: " . $current_version . " Latest version: " . $latest_version);

			if ( version_compare( $current_version, $latest_version, '<' ) ) {
				error_log( "fxm_care_do_update" );
				// New update available, trigger the update process.
				fxm_plugin_updater($latest_version_git);
			}
		}


		error_log( "=====================================================" );

	}
}
function fxm_plugin_updater($latest_version_git) {

	$latest_version = $latest_version_git;
	if (strpos($latest_version_git, 'v') !== false) {
		$latest_version = str_replace("v", "", $latest_version_git);
	}

	// Step 0: Initialize the WordPress filesystem.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	// Step 1: Download the latest plugin ZIP file from GitHub.
	$zip_url   = 'https://github.com/ziegenhagel/faktorxwordpress/archive/' . $latest_version_git . '.zip';
	$temp_file = download_url( $zip_url );

	// Step 2: Check if the download was successful.
	if ( ! is_wp_error( $temp_file ) ) {

		error_log("WP_PLUGIN_DIR: " . WP_PLUGIN_DIR);
		// Step 3: Unzip the downloaded file and overwrite the existing plugin files.
		$unzip_result = unzip_file( $temp_file, WP_PLUGIN_DIR );

		// Step 4: Clean up the temporary ZIP file.
		unlink( $temp_file );

		if ( is_wp_error( $unzip_result ) ) {
			// Error occurred while unzipping.
			// You may want to handle this case gracefully.
			error_log( "Error occurred while unzipping.");
		} else {
			// Successful update.
			// You can do additional tasks here, like running database updates, etc.
			// Optionally, you can update the plugin version in the database.


			$extracted_root_folder = trailingslashit( WP_PLUGIN_DIR ) . basename(FXWP_PLUGIN_DIR )  . '-' . $latest_version;

			error_log("Extracted root folder: " . $extracted_root_folder);

			fxm_move_directory_contents( $extracted_root_folder, FXWP_PLUGIN_DIR );
			//fxm_recursive_delete( $extracted_root_folder );


			update_option( 'your_plugin_version', $latest_version );

			// Show a success message to the admin.
			add_action( 'admin_notices', function () use ( $latest_version ){
				echo '<div class="notice notice-success is-dismissible"><p>Your plugin has been updated to version ' . $latest_version . '.</p></div>';
			} );
		}
	} else {
		// Error occurred while downloading the update.
		// You may want to handle this case gracefully.
		error_log( "Error occurred while downloading the update.");
	}

}

// Helper function to move the contents from one directory to another.
function fxm_move_directory_contents( $src, $dest ) {
	$files = glob( $src . '/*' );
	foreach ( $files as $file ) {
		error_log("Moving file: " . $file . " to: " . $dest . '/' . basename( $file ));
		if ( is_file( $file ) ) {
			$file_dest = $dest . '/' . basename( $file );
			copy( $file, $file_dest );
			unlink( $file );
		}
	}
}


// Helper function to recursively delete a directory and its contents.
function fxm_recursive_delete($path) {
	if (is_file($path)) {
		return unlink($path);
	} elseif (is_dir($path)) {
		$files = array_diff(scandir($path), array('.', '..'));
		foreach ($files as $file) {
			fxm_recursive_delete(realpath($path) . '/' . $file);
		}
		return rmdir($path);
	}
	return false;
}