<?php
/**
 * Plugin Name: Faktor &times; WordPress
 * Description: Ein umfassendes Plugin zur Überwachung der Website, SEO-Prüfung, Backups, Updates, Überprüfung von defekten Links, Bildoptimierung, Speicherplatznutzung, Admin-Login, Selbstaktualisierung, Plugin-Installation, Anzeige von Rechnungen und Site-Identifikation.
 * Version: 1.0
 * Author: Faktor Mensch Media UG (haftungsbeschränkt)
 * Author URI: https://faktorxmensch.com
 * Text Domain: fxwp
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
        )
    ));

    $response = json_decode($response['body'], true);

    if (isset($response['success']) && $response['success'] === true) {
        // show info
        fxwp_enable_automatic_updates();
    } else {
        // show info
        update_option('fxwp_api_key', '');
    }

}


function fxwp_deactivation()
{
    // code to execute on plugin deactivation
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

    add_submenu_page(
        'fxwp', // Parent slug
        'SEO Check', // Page title
        'SEO Check', // Menu title
        'edit_posts', // Capability
        'fxwp-seo-check', // Menu slug
        'fxwp_seo_check_page' // Function
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
        'dashicons-admin-generic',
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


// register stylescheets
add_action('admin_enqueue_scripts', 'fxwp_register_styles');

function fxwp_register_styles()
{
    wp_register_style('fxwp', plugin_dir_url(__FILE__) . 'admin/css/fxwp.css', array(), '1.0.0', 'all');
    wp_enqueue_style('fxwp');
}
