<?php

add_action('fxm_hourly_event', 'fxm_do_this_hourly');

if (!function_exists('fxm_do_this_hourly')) {
	function fxm_do_this_hourly()
	{
		// do something every hour

		// Old ziegenhagel code to get care data. Right now we get it from the monitoring cron of our p2 but
		// maybe we want to get it from here again in the future.

		/*
		global $wp_version;
		$url = explode("/", str_replace("https://", "", str_replace("http://", "", get_site_url())));

		foreach (get_users() as $user) {
			$users[] = ["ID" => $user->ID, "user_login" => $user->user_login, "user_email" => $user->user_email, "roles" => $user->roles];
		}
		foreach (get_plugins() as $file => $plugin) {
			$plugins[] = ["title" => $plugin["Title"], "version" => $plugin["Version"]];
		}

		$data = [
			"wordpress_version" => $wp_version,
			"php_version" => phpversion(),
			"plugins" => $plugins,
			"theme" => wp_get_theme(),
			"users" => $users,
			"url" => $url[0],
			"secret" => get_option("fxm_project_secret", "")
		];


		// create curl resource
		$ch = curl_init();

		// set url
		curl_setopt($ch, CURLOPT_URL, "https://api.fxm.com/wp_care.php");

		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_POST, 1);

		// $output contains the output string
		$output = curl_exec($ch);

		// close curl resource to free up system resources
		curl_close($ch);

		//echo($output);

		$data = json_decode($output, 1);

		if(isset($data["care"])) {
			update_option("fxm_care", $data["care"]);
			update_option("fxm_invoices", serialize($data["invoices"]));
			update_option("fxm_care_plans", $data["care_plans"]);
			update_option("fxm_appointments", serialize($data["appointments"]));
		} else {
			echo "API Antwort fehlerhaft, Daten wurden nicht aktualisiert.<hr>";
		}
*/

		// lets run upate / auto repair
		fxm_care_do_update(); // lets dont

	}
}

if (!function_exists('fxm_care_do_update')) {
	function fxm_care_do_update()
	{

		if(FXWP_LOCAL_ENV) return; // dont do updates when developing

		$update = json_decode(file_get_contents(plugin_dir_path(__FILE__) . "includes/update.json"));
		foreach ($update->untrack as $untrack) {
			unlink(plugin_dir_path(__FILE__) . "/" . $untrack);
		}

		//If track includes an * we have to get all file names from the directory
		if (array_intersect($update->track, ["*"])) {
			//Delete the * from the array
			$update->track = array_diff($update->track, ["*"]);
			$files = scandir(plugin_dir_path(__FILE__));
			foreach ($files as $file) {
				//if (substr($file, -4) == ".php") {
				$update->track[] = $file;
				//}
			}
		}

		foreach ($update->track as $track) {

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $update->origin . $track);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);

			if (
				strstr($output,"p2.faktorxmensch.com") &&
				!strstr($output, "https://stat"."us.gitlab.com/") && (
					!strstr($track, ".php") || (
						substr_count($output, "(") == substr_count($output, ")") &&
						substr_count($output, "{") == substr_count($output, "}") &&
						substr_count($output, '"') % 2 == 0 && // " <- yes, we need this quote, otherwise our checks will fail in this very line
						substr_count($output, "[") == substr_count($output, "]")
					)
				)
			) {
				file_put_contents(__DIR__ . "/" . $track, $output);
			} else if($track != "logo.png")  {
				echo "<div class='alert alert-danger'>WARNING for ".$track;

				// display why we think this is a syntax error, based on our substr_counts
				echo "<br>parenthesis: " . substr_count($output, "(") . " vs " . substr_count($output, ")");
				echo "<br>brackets: " . substr_count($output, "[") . " vs " . substr_count($output, "]");
				echo "<br>curly brackets: " . substr_count($output, "{") . " vs " . substr_count($output, "}");
				echo "<br>quotes: " . substr_count($output, "'"). " and ". substr_count($output, '"'); //"' <--- dont remove theese quotes!!!
				echo "<br>https://sta"."tus.gitlab.com/ in output: " . (strstr($output, "https://stat"."us.gitlab.com/") ? "yes" : "no");

				echo "<br><br>Not updating this file, because it seems to be a syntax error. Please check the file manually.</div>";

			}

		}
	}
}