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

function fxwp_activation()
{
    // Der API-Schlüssel könnte in den Plugin-Einstellungen gespeichert werden
    $api_key = get_option('fxwp_api_key');

    // Log-Datei für die Aktivierung erstellen
    $log = [];

    // Stellen Sie sicher, dass der API-Schlüssel gesetzt ist
    if ($api_key) {
        // Bauen Sie Ihre Anfrage zusammen. Ändern Sie die URL und die Datenstruktur nach Ihren Bedürfnissen
        $response = wp_remote_post(FXWP_API_URL . '/activate', array(
            'body' => array(
                'api_key' => $api_key,
                // provide current domain to API
                'domain' => site_url(),
                // and main user id
                'user' => get_current_user_id(),
                // as well as plugin version
                'version' => FXWP_VERSION
            )
        ));

        // Überprüfen Sie den Status der Anfrage
        if (is_wp_error($response)) {
            // Fehlgeschlagene Anfrage. Fügen Sie hier Ihren Fehlerbehandlungscode ein
        } else {
            // Erfolgreiche Anfrage. Fügen Sie hier Ihren Erfolgsbehandlungscode ein
        }

        // log activation
        $log[] = 'Plugin activated';
    }

    $log[] = 'Automatic updates enabled';
    fxwp_enable_automatic_updates();

    // show log
    // fxwp_show_log($log);

}


function fxwp_deactivation()
{
    // code to execute on plugin deactivation
    delete_option('fxwp_api_key');
}


add_action('admin_menu', 'fxwp_plugin_menu');

function fxwp_plugin_menu()
{

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

    // broekn link checker
    add_submenu_page(
        'fxwp', // Parent slug
        'Broken Link Checker', // Page title
        'Broken Link Checker', // Menu title
        'edit_posts', // Capability
        'fxwp-broken-link-checker', // Menu slug
        'fxwp_broken_link_checker_page' // Function
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
        'Plugin Installer', // Page title
        'Plugin Installer', // Menu title
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

}


