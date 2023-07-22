<?php


add_action( 'fxm_hourly_event', 'fxm_do_this_hourly' );

if ( ! function_exists( 'fxm_do_this_hourly' ) ) {
	function fxm_do_this_hourly() {

			$plugin_data = get_plugin_data( FXWP_PLUGIN_DIR . '/faktorxwordpress.php' );
			$current_version = $plugin_data['Version'];
			$github_api_url = 'https://api.github.com/repos/ziegenhagel/faktorxwordpress/releases/latest';

			$response = wp_remote_get( $github_api_url );

			if ( ! is_wp_error( $response ) && $response['response']['code'] === 200 ) {
				$github_data = json_decode( $response['body'], true );
				$latest_version = $github_data['tag_name'];

				if ( version_compare( $current_version, $latest_version, '<' ) ) {
					// New update available, trigger the update process.
					fxm_plugin_updater($latest_version);
				}
			}

		error_log( "=====================================================" );
		error_log( "fxm_care_do_update" );
		error_log( "Local env: " . ( FXWP_LOCAL_ENV ? "true" : "false" ) );
		error_log( "Plugin dir: " . FXWP_PLUGIN_DIR );

		error_log( "=====================================================" );

	}
}
function fxm_plugin_updater($latest_version) {

	// Step 1: Download the latest plugin ZIP file from GitHub.
	$zip_url   = 'https://github.com/ziegenhagel/faktorxwordpress/archive/' . $latest_version . '.zip';
	$temp_file = download_url( $zip_url );

	// Step 2: Check if the download was successful.
	if ( ! is_wp_error( $temp_file ) ) {
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
			update_option( 'your_plugin_version', $latest_version );

			// Show a success message to the admin.
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-success is-dismissible"><p>Your plugin has been updated to version ' . $latest_version . '.</p></div>';
			} );
		}
	} else {
		// Error occurred while downloading the update.
		// You may want to handle this case gracefully.
		error_log( "Error occurred while downloading the update.");
	}

}
