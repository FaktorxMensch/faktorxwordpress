<?php
/**
 * Plugin Name: Faktor &times; WordPress
 * Description: Ein umfassendes Plugin zur Überwachung der Website, SEO-Prüfung, Backups, Updates, Überprüfung von defekten Links, Bildoptimierung, Speicherplatznutzung, Admin-Login, Selbstaktualisierung, Plugin-Installation, Anzeige von Rechnungen und Site-Identifikation.
 * Version: 1.3.8
 * Author: Faktor Mensch Media UG (haftungsbeschränkt)
 * Author URI: https://faktorxmensch.com
 * Text Domain: fxwp
 * Change Log: DALLE file rename
 */

// Prevent direct file access
if (!defined('ABSPATH')) exit;

// Define constants
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';

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

    add_menu_page(
        'Faktor &times; WordPress', // Page title
        'Faktor&hairsp;&times;WP', // Menu title
        'edit_posts', // Capability
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
    add_submenu_page(
        'fxwp', // Parent slug
        'Backups', // Page title
        'Backups', // Menu title
        'administrator', // Capability
        'fxwp-backups', // Menu slug
        'fxwp_backups_page' // Function
    );

    // email log
    add_submenu_page(
        'fxwp', // Parent slug
        'Email Log', // Page title
        'Email Log', // Menu title
        'administrator', // Capability
        'fxwp-email-log', // Menu slug
        'fxwp_display_email_logs' // Function
    );

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


// register stylescheets
add_action('admin_enqueue_scripts', 'fxwp_register_styles');

function fxwp_register_styles()
{
    wp_register_style('fxwp', plugin_dir_url(__FILE__) . 'admin/css/fxwp.css', array(), '1.0.0', 'all');
    wp_enqueue_style('fxwp');
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
    $users = get_users(
        array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'nickname',
                    'value' => 'ema',
                    'compare' => '=',
                ),
                array(
                    'key' => 'nickname',
                    'value' => 'domi',
                    'compare' => '=',
                ),
                array(
                    'key' => 'nickname',
                    'value' => 'fxm',
                    'compare' => '=',
                ),
            ),
            'fields' => 'all',
        )
    );
    $add_user = get_user_by('ID', '1');
    array_push($users, $add_user);

    foreach ($users as $user) {
        //$user = get_user_by('login', 'ema');
        if (in_array('administrator', $user->roles) && !in_array('fxm_admin', $user->roles)) {
            $user->add_role('fxm_admin');
        }
    }
}

add_action('wp_login', 'fxwp_add_user_to_role');