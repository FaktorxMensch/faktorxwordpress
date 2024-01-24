<?php

//Make debug options description array available for settings page
global $debugging_options_description;
$debugging_options_description = array(
	'fxwp_debugging_enable' => "define( 'WP_DEBUG', true );",
	'fxwp_debugging_log' => "define( 'WP_DEBUG_LOG', true );",
	'fxwp_debugging_display' => "define( 'WP_DEBUG_DISPLAY', true );",
	'fxwp_debugging_scripts' => "define( 'SCRIPT_DEBUG', true );",
	'fxwp_debugging_savequeries' => "define( 'SAVEQUERIES', true );",
	'fxwp_debugging_errorreporting' => "error_reporting(E_ALL);",
	'fxwp_debugging_display_ini' => "ini_set('display_errors',1);",
	'fxwp_debugging_display_ini_startup' => "ini_set('display_startup_errors', '1');",
);

function fxwp_change_debug_status($old_value , $new_value, $option) {

	error_log("User changed debug options.");

	//make array from new value
	$new_value = get_object_vars(json_decode($new_value));

//	error_log("New value: " . print_r($new_value, true));

	//make backup of wp-config.php for sanity
	if(!copy(ABSPATH . 'wp-config.php', ABSPATH . 'wp-config-backup.php')) {
		error_log("Could not backup wp-config.php");
		return;
	}

	$fileContents = file(ABSPATH . 'wp-config.php');
	$finalLines = [];
	$stopEditingReached = false;
	$inConditionalBlock = false;
	$startEditingIndex = null;
	global $debugging_options_description;

	foreach ($fileContents as $line) {
		$shouldRemoveLine = false;

		// Use regex to match the conditional block with flexible whitespace (e.g. if ( ! defined( 'WP_DEBUG' ) ) {)
		if (preg_match("/if\s*\(\s*!\s*defined\s*\(\s*'WP_DEBUG'\s*\)\s*\)\s*{/", $line)) {
			$inConditionalBlock = true;
		}
		if ($inConditionalBlock && strpos($line, "}") !== false) {
			$inConditionalBlock = false;
			$finalLines[] = $line;
			continue;
		}

		if (strpos($line, "That's all, stop editing!") !== false) {
			$stopEditingReached = true;
		}
		if (strpos($line, "Add any custom values between this line and") !== false) {
			$startEditingIndex = count( $finalLines )+1;
		}

		if (!$stopEditingReached && !$inConditionalBlock) {
			foreach ($debugging_options_description as $key => $value) {
				if (strpos($value, 'define') !== false || strpos($value, 'ini_set') !== false) {
					// Extract the function name and the first parameter
					preg_match("/(define|ini_set)\s*\(\s*'([^']+)'/", $value, $matches);
					if ($matches) {
						$functionName = $matches[1];
						$firstParam = $matches[2];

						// Create a regex pattern to match the function name and first parameter
						$pattern = '/' . preg_quote($functionName, '/') . '\s*\(\s*\'\s*' . preg_quote($firstParam, '/') . '\s*\'\s*,/';
					}
				} else {
					// Handle the error_reporting case
					$pattern = '/'.preg_quote(explode('(', $value)[0], '/').'\s*\(/';
				}

				if (preg_match($pattern, $line)) {
					$shouldRemoveLine = true;
					break;
				}
			}
		}

		if (!$shouldRemoveLine) {
			$finalLines[] = $line;
		}
	}

	// Making sure that the essential configuration is present
	$essentialPatterns = ['/define\s*\(\s*\'DB_NAME\'/', '/define\s*\(\s*\'DB_USER\'/', '/define\s*\(\s*\'DB_PASSWORD\'/', '/define\s*\(\s*\'AUTH_KEY\'/'];
	$allEssentialsPresent = true;

	foreach ($essentialPatterns as $pattern) {
		$found = false;
		foreach ($finalLines as $line) {
			if (preg_match($pattern, $line)) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			$allEssentialsPresent = false;
			break;
		}
	}

	if (!$allEssentialsPresent) {
		error_log("Essential configuration missing. Aborting update.");
		return;
	}


	// Verify final lines before writing to temporary file
	if (empty($finalLines)) {
		error_log("No content to write to wp-config.php");
		return;
	}

	if ($startEditingIndex !== null) {
		foreach ($new_value as $key => $value) {
			if ($value) {
				// Insert the line at the found index
				array_splice($finalLines, $startEditingIndex, 0, $debugging_options_description[$key] . "\n");
				$startEditingIndex++; // Increment index to maintain the insertion position
			}
		}
	}


	file_put_contents(ABSPATH . 'wp-config.php', implode("", $finalLines));


}
add_action('update_option_fxwp_debugging_options', 'fxwp_change_debug_status', 100, 3);
