<?php
function fxwp_show_log($log)
{
    // have textarea with log
    echo '<textarea style="width: 100%; height: 300px;">';
    print_r($log);
    echo '</textarea>';
}

/** Initialization and other stuff in settings-page.php. This function is only used to check if a single feature is disabled.
 * @param $feature_to_check
 * @return bool
 */
function fxwp_check_deactivated_features($feature_to_check) :bool {
    // Get disabled features
    $deactivated_features = get_option('fxwp_deactivated_features');
    if ($deactivated_features === false) {
        return false;
    }
    $deactivated_features = get_object_vars(json_decode($deactivated_features));
    // find feature_to_check in array keys and return value
    if (array_key_exists($feature_to_check, $deactivated_features)) {
        return $deactivated_features[$feature_to_check];
    } else {
        return false;
    }
}

/** Show warning to fxm_admins if features are deactivated through plugin settings
 * and if users may not see some pages or settings
 * @param $feature_to_check
 * @return html code showing warning
 */
function fxwp_show_deactivated_feature_warning($feature_to_check) {
    $warnings = array(
        'fxwp_deact_ai' => 'KI Funktionen sind für Kundis ausgeblendet!',
        'fxwp_deact_backups' => esc_html__('Backups sind in den Plugin Einstellungen deaktiviert!', 'fxwp'),
        'fxwp_deact_autoupdates' => esc_html__('Auto Updates sind in den Plugin Einstellungen deaktiviert!', 'fxwp'),
        'fxwp_deact_email_log' => esc_html__('Email Log ist via den Plugin Einstellungen für Kundis ausgeblendet!', 'fxwp'),
        'fxwp_deact_shortcodes' => esc_html__('Shortcodes sind via den Plugin Einstellungen für Kundis ausgeblendet!', 'fxwp'),
        'fxwp_deact_dashboards' => esc_html__('Alle Dashboards sind via den Plugin Einstellungen für Kundis ausgeblendet!', 'fxwp'),
        'fxwp_deact_customer_settings' => esc_html__('FxWP Einstellungen sind für Kundis via den Plugin Einstellungen komplett ausgeblendet!', 'fxwp'),
        'fxwp_deact_hide_plugin' =>  esc_html__('Plugin ist via den Plugin Einstellungen für Kundis komplett ausgeblendet!', 'fxwp'),
    );
    if (current_user_can('fxm_admin') && fxwp_check_deactivated_features($feature_to_check)) {
        echo '<br><div class="notice notice-error"><p>'.$warnings[$feature_to_check].'</p></div><br>';
    }
}

/** Completly hide fxwp plugin in plugins list if fxwp_deact_hide_plugin is true
 *
 */
function fxwp_hide_plugin($plugins)
{
    if (!current_user_can('fxm_admin') && fxwp_check_deactivated_features('fxwp_deact_hide_plugin')) {
        unset( $plugins[ 'faktorxwordpress/faktorxwordpress.php' ] );
    }
    return $plugins;
}
add_filter('all_plugins', 'fxwp_hide_plugin');

/** Remove fxm dashboards if fxwp_deact_dashboards is true
 *
 */
function fxwp_remove_dashboards()
{
    if (!current_user_can('fxm_admin') && fxwp_check_deactivated_features('fxwp_deact_dashboards')) {
        // Globalize the metaboxes array, this holds all the widgets for wp-admin.
        global $wp_meta_boxes;
        foreach( $wp_meta_boxes["dashboard"] as $position => $core ){

            foreach( $core["core"] as $widget_id => $widget_info ){
                if (strpos($widget_id, 'fxm') !== false || strpos($widget_id, 'fxwp') !== false) {
                    remove_meta_box( $widget_id, 'dashboard', $position );
                }
//                unset($wp_meta_boxes["dashboard"][$position]["core"][$widget_id]);
            }
        }
    }
    if (current_user_can('fxm_admin') && fxwp_check_deactivated_features('fxwp_deact_dashboards')) {
        fxwp_show_deactivated_feature_warning('fxwp_deact_dashboards');
    }
}
add_action( 'wp_dashboard_setup', 'fxwp_remove_dashboards',100);