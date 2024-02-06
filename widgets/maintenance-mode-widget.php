<?php
// Register the widget
function fxwp_register_maintenance_mode_widget()
{
    if (!current_user_can('editor') && !current_user_can('administrator') && !current_user_can('fxm_admin')) {
        return;
    }

    wp_add_dashboard_widget(
        'fxwp_maintenance_mode_widget', // Widget ID
        'Maintenance Mode', // Widget title
        'fxwp_display_maintenance_mode_widget' // Display callback function
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_maintenance_mode_widget');
function fxwp_url_actions() {
	if ( isset( $_GET["fxwp_sync"] ) ) {
		if (isset($_POST['fxwp_self_update_tag']))
		{
			echo '<div class="notice notice-success is-dismissible"><p>' . $_POST['fxwp_self_update_tag'] . '</p></div>';

		} else {
			// do hourly which does the update
            fxm_do_this_hourly();
		}
	}
}

add_action('wp_dashboard_setup', 'fxwp_url_actions');

// Widget display callback function
function fxwp_display_maintenance_mode_widget()
{

    if (isset($_POST['maintenance_mode'])) {
        //nonce
        if (!isset($_POST['maintenance_mode_widget_nonce']) || !wp_verify_nonce($_POST['maintenance_mode_widget_nonce'], 'save_maintenance_mode_widget')) {
            wp_die('Sorry, your nonce did not verify.');
        }
        $mode = sanitize_text_field($_POST['maintenance_mode']);
        update_option('maintenance_mode', $mode);
    }

    $maintenance_mode = get_option('maintenance_mode', 'none'); // 'none', 'coming_soon', 'maintenance_mode

//    if ($maintenance_mode == 'maintenance_mode') {
//        // Display maintenance mode content
//        echo '<p>' . __('Wartungsmodus is enabled. Your website is temporarily unavailable.', 'text-domain') . '</p>';
//    } else if ($maintenance_mode == 'coming_soon') {
//        // Display coming soon content
//        echo '<p>' . __('Coming Soon. Our website is under construction.', 'text-domain') . '</p>';
//    } else {
//        // Display default content
//        echo '<p>' . __('Maintenance mode is disabled.', 'text-domain') . '</p>';
//    }

    // have a form to set maintenance mode to 'coming_soon' or 'maintenance_mode' or 'none' via select with auto submit

    // Form to set maintenance mode status
    echo '<form method="post">';
//    echo '<label for="maintenance_mode">' . __('Set Maintenance Mode:', 'text-domain') . '</label>';
    wp_nonce_field('save_maintenance_mode_widget', 'maintenance_mode_widget_nonce');
    echo '<select style="width:100%" name="maintenance_mode" id="maintenance_mode" onchange="this.form.submit()">';
    echo '<option value="none" ' . selected($maintenance_mode, 'none', false) . '>' . __('Webseite ist online', 'text-domain') . '</option>';
    echo '<option value="coming_soon" ' . selected($maintenance_mode, 'coming_soon', false) . '>' . __('In Entstehung', 'text-domain') . '</option>';
    echo '<option value="maintenance_mode" ' . selected($maintenance_mode, 'maintenance_mode', false) . '>' . __('Wartungsmodus', 'text-domain') . '</option>';
    echo '</select>';
    echo '</form>';

	if (!current_user_can('fxm_admin')) {
		return;
	}

	/**
	 * Add buttons for multiple functions
	 */
	$buttons = [
		[
			"title"=>"F&times;WP Plugin Update",
			"type"=>"action",
			"description"=>"Update this plugin from Git via regular auto repair / auto update.",
			"link"=>get_admin_url()."index.php?fxwp_sync=1",
			"disabled"=>false,
		],
		[
			"title"=>"Change debugging",
			"type"=>"action",
			"description"=>"Go to plugin settings to control debugging behaviour.",
			"link"=>esc_url(admin_url('admin.php?page=fxwp-settings#fxwp-debugging')),
			"disabled"=>false,
		],
		[
			"title"=>"Open project in p2",
			"type"=>"action",
			"description"=>"Opens the associated project in p2 for faster access.",
			"link"=>FXWP_P2_URL.'/project/'.(get_option('fxwp_customer')['_id']),
			"disabled"=>false,
		],
/*		[
			"title"=>"Care+ Wiki-Editor",
			"type"=>"boolean",
			"option"=>"ziegenhagel_careplus_wiki_editor",
			"description"=>"Schaltet den Editor für das CarePlus Wiki ein und aus.",
			"link"=>get_admin_url()."index.php?ziegenhagel_careplus_wiki_editor=",
			"disabled"=>true,
		],
		[
			"title"=>"Care+ Entwicklungs-Modus",
			"type"=>"boolean",
			"option"=>"ziegenhagel_dev",
			"description"=>"Dadurch werden Dateiüberschreibungen bei Updates verhindert.",
			"link"=>get_admin_url()."index.php?ziegenhagel_dev=",
			"disabled"=>true,
		],
		[
			"title"=>"Disconnect from Overtime",
			"type"=>"action",
			"description"=>"Disconnect from Overtime and remove all Care+ data.",
			"link"=>get_admin_url()."index.php?ziegenhagel_purge=1",
			"disabled"=>true,
		],
		[
			"title"=>"Care+ Console",
			"type"=>"boolean",
			"option"=>"ziegenhagel_console",
			"description"=>"Care+ Console nicht mehr anzeigen.",
			"link"=>get_admin_url()."index.php?ziegenhagel_console=",
			"disabled"=>true,
		]*/
	];
/*	if(current_user_can('fxm_admin')) {
		$buttons[] = [
			"title"=>"Care+ Maintenance Mode",
			"type"=>"boolean",
			"option"=>"maintenance_mode",
			"description"=>"Schaltet den Wartungsmodus ein und aus.",
			"link"=>get_admin_url()."index.php?maintenance_mode=",
			"disabled"=>false,
		];
	}*/

	echo "<div style='margin-top: 20px'>";

	foreach($buttons as $index=>$button){

		// line break but not for first button

		// if its a boolean type, check if its true or false
		if($button["type"]=="boolean"){
			// set link
			$button["link"] .= !get_option($button["option"],false)?"1":"0";

			// rename "umschalten" to "ein" or "aus"
			$button["title"] .= !get_option($button["option"],false)?" aktivieren":" deaktivieren";
		}

		$disable_button = $button["disabled"] ? "disabled style='pointer-events: none'" : "";

		// display button
		echo '<div>
            <a class="button" href="'.$button["link"].'" '.$disable_button.' > 
            <span style="vertical-align:sub;margin-left:-2px;margin-right:2px" class="dashicons dashicons-plugins-checked"></span>
            '.$button["title"].' </a>
            <br><small>'.$button["description"].'</small>
            </div><br>';
	}
	echo "</div>";
}


