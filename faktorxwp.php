<?php
/**
 * Plugin Name: WPWithHeart
 * Description: A comprehensive plugin to handle site monitoring, SEO check, backups, updates, broken link checks, image optimization, storage usage, admin login, self-update, plugin installation, invoice display, and site identification.
 * Version: 1.0
 * Author: Faktor Mensch Media
 * Text Domain: faktorxwp
 */

// Prevent direct file access
if (!defined('ABSPATH')) exit;

// Define constants
require_once plugin_dir_path(__FILE__) . 'includes/config.php';
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';

register_activation_hook(__FILE__, 'fxwp_activation');
register_deactivation_hook(__FILE__, 'fxwp_deactivation');

function fxwp_activation()
{
    // Der API-Schlüssel könnte in den Plugin-Einstellungen gespeichert werden
    $api_key = get_option('fxwp_api_key');

    // Stellen Sie sicher, dass der API-Schlüssel gesetzt ist
    if ($api_key) {
        // Bauen Sie Ihre Anfrage zusammen. Ändern Sie die URL und die Datenstruktur nach Ihren Bedürfnissen
        $response = wp_remote_post(fxwp_API_URL . '/activate', array(
            'body' => array(
                'api_key' => $api_key,
                // provide current domain to API
                'domain' => site_url(),
                // and main user id
                'user' => get_current_user_id(),
                // as well as plugin version
                'version' => fxwp_VERSION
            )
        ));

        // Überprüfen Sie den Status der Anfrage
        if (is_wp_error($response)) {
            // Fehlgeschlagene Anfrage. Fügen Sie hier Ihren Fehlerbehandlungscode ein
        } else {
            // Erfolgreiche Anfrage. Fügen Sie hier Ihren Erfolgsbehandlungscode ein
        }
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

    add_menu_page(
        'Faktor &times; WordPress', // Page title
        'Faktor &times; WP', // Menu title
        'manage_options', // Capability
        'faktorxwp', // Menu slug
        'fxwp_site_identifier_page', // Function
        'dashicons-heart', // Icon
        6 // Position
    );

    // upadtes
    add_submenu_page(
        'faktorxwp', // Parent slug
        'Updates', // Page title
        'Updates', // Menu title
        'manage_options', // Capability
        'faktorxwp-updates', // Menu slug
        'fxwp_updates_page' // Function
    );

    // backups
    add_submenu_page(
        'faktorxwp', // Parent slug
        'Backups', // Page title
        'Backups', // Menu title
        'manage_options', // Capability
        'faktorxwp-backups', // Menu slug
        'fxwp_backups_page' // Function
    );

    add_submenu_page(
        'faktorxwp', // Parent slug
        'SEO Check', // Page title
        'SEO Check', // Menu title
        'manage_options', // Capability
        'faktorxwp-seo-check', // Menu slug
        'fxwp_seo_check_page' // Function
    );

    // broekn link checker
    add_submenu_page(
        'faktorxwp', // Parent slug
        'Broken Link Checker', // Page title
        'Broken Link Checker', // Menu title
        'manage_options', // Capability
        'faktorxwp-broken-link-checker', // Menu slug
        'fxwp_broken_link_checker_page' // Function
    );

    // image optimizer
    add_submenu_page(
        'faktorxwp', // Parent slug
        'Image Optimizer', // Page title
        'Image Optimizer', // Menu title
        'manage_options', // Capability
        'faktorxwp-image-optimizer', // Menu slug
        'fxwp_image_optimizer_page' // Function
    );

    // settings
    add_submenu_page(
        'faktorxwp', // Parent slug
        'Settings', // Page title
        'Settings', // Menu title
        'manage_options', // Capability
        'faktorxwp-settings', // Menu slug
        'fxwp_settings_page' // Function
    );

}


