<?php
/**
 * Plugin Name: Faktor &times; WordPress
 * Description: Ein umfassendes Plugin zur Überwachung der Website, SEO-Prüfung, Backups, Updates, Überprüfung von defekten Links, Bildoptimierung, Speicherplatznutzung, Admin-Login, Selbstaktualisierung, Plugin-Installation, Anzeige von Rechnungen und Site-Identifikation.
 * Version: 1.7.8
 * Author: Faktor Mensch Media UG (haftungsbeschränkt)
 * Author URI: https://faktorxmensch.com
 * Text Domain: fxwp
 * Change Log: Fix for p2 api key not included and errors therefore v1.7.6-v1.7.7: Fix for disallow normal admin to edit fxm_user had a bug. v1.7.5: Fix for wpforms returning email-address as array and not as single address. v1.7.4: Only increasing version number v1.7.3: Changing excluded backup patterns and how it is checked v1.7.2: Changed the pattern which is used to find the start of the 'edit from here' block. If it was missing our function did not work v1.7.1: Many minor changes v1.6.9: Changing default backup interval from hourly to twicedaily and only keeping every second FXWP_BACKUP_DAYS_FATHER v1.6.8: Storage usage widget: Color on space nearly full. Fix for dashboard showing wrong percentage v1.6.7: Changing p2 url if directly opening project v1.6.6: Adding colored labels to backup page for better overview about backup age v1.6.5 Fix missing line for checking on uncompleted backups v1.6.4 Adding handlers if previous backup is not completed v1.6.3 Deleting unsuccessful backups and sending emails to us about it v1.6.2: Fixing bug at login if no user with id 1 exists v1.6.1: Renaming 'debug' widget to status widget; backup debugging stuff, previous: Fix for ionos behaving weird v1.5.8: Default widget if all other are hidden, minor improvements. v1.5.7: Added functionality to hide pages and settings from customers. pre:Added option to manually update to any tag, added admin notice in update process if update has errors. v1.5.3: Added functionality to set debug mode from plugin settings and added debug widget. v1.5.2: Added functionality to hide or deactivate features
 */

// ToDo: Built a wp option which reflects backup frequency. If not set, use the option from the config file. If set, use the option from the wp option
// ToDo: Copy backups to s3 after creation. Only copy the first grandfather backup of the month to s3
// ToDo: If storage is at 95% or more, send an email to us

// Prevent direct file access
if (!defined('ABSPATH')) exit;

// Define constants
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

register_activation_hook(__FILE__, 'fxwp_activation');
register_deactivation_hook(__FILE__, 'fxwp_deactivation');

// show error on every admin page
add_action('admin_notices', 'fxwp_show_error');

function fxwp_show_error()
{
    if (get_option('fxwp_api_key') !== '')
        return;

    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Das Plugin Faktor&times;WordPress konnte nicht aktiviert werden. Bitte Plugin deaktivieren und erneut aktivieren.', 'fxwp') . '</p></div>';

    // deactivate plugin
    deactivate_plugins(plugin_basename(__FILE__));

    // reload page after 3s
    echo '<script>setTimeout(function(){location.reload()}, 3000);</script>';

}

function fxwp_activation()
{

    // Der API-Schlüssel könnte in den Plugin-Einstellungen gespeichert werden
    $api_key = get_option('fxwp_api_key');

    // if no api key is set, make up a random one
    if (!$api_key) {
        $api_key = wp_generate_password(32, false, false);
        update_option('fxwp_api_key', $api_key);
    }

    // Log-Datei für die Aktivierung erstellen

    // Stellen Sie sicher, dass der API-Schlüssel gesetzt ist
    // Bauen Sie Ihre Anfrage zusammen. Ändern Sie die URL und die Datenstruktur nach Ihren Bedürfnissen
    $response = wp_remote_post(FXWP_API_URL . '/activate', array(
        'body' => array(
            'api_key' => $api_key,
            'plugin_url' => plugin_dir_url(__FILE__), // provide plugin url to API
        ),
	    //'sslverify' => false,
    ));

    $response = json_decode($response['body'], true);

    if (isset($response['success']) && $response['success'] === true) {
        // show info
        fxwp_enable_automatic_updates();
        update_option('fxwp_customer', $response['customer']);
        update_option('fxwp_project', $response['project']);
        update_option('fxwp_view_options', "erweitert");
        update_option('fxwp_plans', arrayToObject($response['plans']));
    } else {
        // show info
        update_option('fxwp_api_key', '');
    }

    fxwp_create_email_log_table();

	if (!wp_next_scheduled('fxm_hourly_event')) {
		wp_schedule_event(time(), 'hourly', 'fxm_hourly_event');
	}

}


function fxwp_deactivation()
{
	if(!current_user_can('fxm_admin')) {
		error_log("fxwp_deactivation: no fxm_admin");
		return;
	}
    $api_key = get_option('fxwp_api_key');
    // code to execute on plugin deactivation
    $response = wp_remote_post(FXWP_API_URL . '/activate', array(
        'body' => array(
            'deactivate_api_key' => $api_key,
        )
    ));
    delete_option('fxwp_api_key');
}


add_action('admin_menu', 'fxwp_plugin_menu');

function fxwp_plugin_menu()
{
    if (get_option('fxwp_api_key') === '')
        return;
	// If we have deactivated the customer settings, we don't want to show the menu unless the user is fxm_admin
    if (fxwp_check_deactivated_features('fxwp_deact_customer_settings') && !current_user_can('fxm_admin')) {
        return;
    }

    add_menu_page(
        'Faktor &times; WordPress', // Page title
        'Faktor&hairsp;&times;WP', // Menu title
        'edit_theme_options', // Capability
        'fxwp', // Menu slug
        'fxwp_updates_page', // Function
        'dashicons-shield', // Icon
        6 // Position
    );


//    // Custom Fields
//    add_submenu_page(
//        'fxwp', // Parent slug
//        'Custom Fields', // Page title
//        'Custom Fields', // Menu title
//        'manage_options', // Capability
//        'fxwp-custom-fields', // Menu slug
//        'fxwp_custom_fields_page' // Function
//    );

    // upadtes
    add_submenu_page(
        'fxwp', // Parent slug
        'Updates', // Page title
        'Updates', // Menu title
        'administrator', // Capability
        'fxwp-updates', // Menu slug
        'fxwp_updates_page' // Function
    );

    // backups
    if (current_user_can('fxm_admin') || !fxwp_check_deactivated_features('fxwp_deact_backups')) {
        add_submenu_page(
            'fxwp', // Parent slug
            'Backups', // Page title
            'Backups', // Menu title
            'administrator', // Capability
            'fxwp-backups', // Menu slug
            'fxwp_backups_page' // Function
        );
    }

    // email log
    if (current_user_can('fxm_admin') || !fxwp_check_deactivated_features('fxwp_deact_email_log')) {
        add_submenu_page(
            'fxwp', // Parent slug
            'Email Log', // Page title
            'Email Log', // Menu title
            'administrator', // Capability
            'fxwp-email-log', // Menu slug
            'fxwp_display_email_logs' // Function
        );
    }

    // image optimizer
    add_submenu_page(
        'fxwp', // Parent slug
        'Image Optimizer', // Page title
        'Image Optimizer', // Menu title
        'administrator', // Capability
        'fxwp-image-optimizer', // Menu slug
        'fxwp_image_optimizer_page' // Function
    );

    // plugin installer
    add_submenu_page(
        'fxwp', // Parent slug
        'Install Helper', // Page title
        'Install Helper', // Menu title
        'administrator', // Capability
        'fxwp-plugin-installer', // Menu slug
        'fxwp_plugin_list_installer_page' // Function
    );

    // settings
    add_submenu_page(
        'fxwp', // Parent slug
        'Settings', // Page title
        'Settings', // Menu title
        'administrator', // Capability
        'fxwp-settings', // Menu slug
        'fxwp_settings_page' // Function
    );


    remove_submenu_page('fxwp', 'fxwp');

    // Shortcodes
    if (current_user_can('fxm_admin') || !fxwp_check_deactivated_features('fxwp_deact_shortcodes')) {
        add_menu_page(
            'Meine Benutzerdefinierten Shortcodes',
            'Shortcodes',
            'manage_options',
            'my-custom-shortcodes',
            'fxwp_display_settings_page',
            'dashicons-shortcode',
            null
        );

        // Eine Unterseite unter unserem Top-Level-Menü hinzufügen:
        add_submenu_page(
            'my-custom-shortcodes',
            'Neuen Shortcode hinzufügen',
            'Neu hinzufügen',
            'manage_options',
            'my-custom-shortcodes-add-new',
            'fxwp_display_add_new_page'
        );

        // Eine weitere Unterseite für die Dokumentation hinzufügen:
        add_submenu_page(
            'my-custom-shortcodes',
            'Shortcode Dokumentation',
            'Dokumentation',
            'manage_options',
            'my-custom-shortcodes-doc',
            'fxwp_display_doc_page'
        );
    }

    if (current_user_can('fxm_admin') || !fxwp_check_deactivated_features('fxwp_deact_ai')) {
        // Schreibwerkstatt
        add_menu_page(
            'Schreibwerkstatt',
            'Schreibwerkstatt',
            'manage_options',
            'fxwp-topic-page',
            'fxwp_topic_page',
            'dashicons-format-status',
            null
        );
    }


}


// register stylescheets
add_action('admin_enqueue_scripts', 'fxwp_register_styles');

function fxwp_register_styles()
{
    wp_register_style('fxwp', plugin_dir_url(__FILE__) . 'admin/css/fxwp.css', array(), '1.0.0', 'all');
    wp_enqueue_style('fxwp');
    wp_admin_css_color( 'fxm1', __( 'FxM' ),
        plugin_dir_url(__FILE__) . 'admin/css/admin-scheme.css',
        array( '#1d2327', '#fff', '#f59700' , '#0a46bd')
    );
}

/* adminbar frontend */
if (!function_exists('fxm_adminbar')) {
	function fxm_adminbar()
	{
		wp_enqueue_style('adminbar.css', plugins_url('admin/css/adminbar.css', __FILE__), false, '1.0.0', 'all');
	}
}
add_action('wp_enqueue_scripts', "fxm_adminbar");


//Add fxm role to allow us to have special capabilities
function fxwp_add_role()
{
    add_role('fxm_admin',
        'FxM Admin',
        get_role('administrator')->capabilities
    );
    $role = get_role('fxm_admin');
    $role->add_cap('fxm_admin', true);
}

add_action('init', 'fxwp_add_role');

//Add Users to fxm role
function fxwp_add_user_to_role()
{
    //ToDo: Change this because this is unsafe. Find new way to define who is fxm and who is not
//    $users = get_users(
//        array(
//            'meta_query' => array(
//                'relation' => 'OR',
//                array(
//                    'key' => 'nickname',
//                    'value' => 'ema',
//                    'compare' => '=',
//                ),
//                array(
//                    'key' => 'nickname',
//                    'value' => 'domi',
//                    'compare' => '=',
//                ),
//                array(
//                    'key' => 'nickname',
//                    'value' => 'fxm',
//                    'compare' => '=',
//                ),
//            ),
//            'fields' => 'all',
//        )
//    );
    $users = array();
    $add_user = get_user_by('ID', '1');

    // if $add_user array is empty, return
    if (empty($add_users)) {
        return;
    }

    array_push($users, $add_user);

    foreach ($users as $user) {
        if (in_array('administrator', $user->roles) && !in_array('fxm_admin', $user->roles)) {
            $user->add_role('fxm_admin');
        }
    }
}

add_action('wp_login', 'fxwp_add_user_to_role');