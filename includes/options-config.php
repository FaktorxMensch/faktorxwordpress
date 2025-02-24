<?php
/*
 * Description: Konfiguration der Plugin-Optionen.
 * Struktur des Konfigurationsarrays ($fx_plugin_config):
 * ------------------------------------------------------
 * $fx_plugin_config ist ein assoziatives Array, das die gesamte Konfiguration des Plugins speichert. Es enthält:
 *
 * 1. 'nav_pages' (array) - Definiert die Navigationsseiten im Plugin-Adminbereich.
 *    Jede Seite ist ein Schlüssel-Wert-Paar mit:
 *    - Schlüssel: Einzigartiger Bezeichner der Seite.
 *    - Wert: Array mit folgenden Schlüsseln:
 *      - 'title' (string): Titel der Seite.
 *      - 'icon' (string): Dashicons-Klasse für das Icon.
 *      - 'slug' (string): Slug der Seite.
 *      - 'active_callback' (function): Funktion, die bestimmt, ob die Seite aktiv ist.
 *      - 'sections' (array): Enthält die Abschnitte der Seite.
 *
 * 2. 'sections' (array) - Enthält Abschnitte innerhalb einer Navigationsseite.
 *    Jeder Abschnitt hat folgende Struktur:
 *    - 'title' (string): Titel des Abschnitts.
 *    - 'options' (array): Enthält die Optionen des Abschnitts.
 *
 * 3. 'options' (array) - Definiert die verfügbaren Optionen innerhalb eines Abschnitts.
 *    Jede Option besteht aus folgenden Parametern:
 *    - 'type' (string): Typ der Option. Unterstützte Typen sind:
 *      - 'text': Textfeld
 *      - 'number': Zahlenfeld
 *      - 'checkbox': Kontrollkästchen
 *      - 'radio': Radio-Button-Gruppe
 *      - 'select': Dropdown-Menü
 *      - 'action': Ausführbare Aktion
 *      - 'alert': Warnung mit Farboptionen
 *      - 'code': Code-Anzeige (nur lesbar)
 *      - 'json': JSON-Daten (nur lesbar)
 *    - 'title' (string): Titel der Option.
 *    - 'description' (string): Beschreibung der Option.
 *    - 'default' (mixed): Standardwert der Option.
 *    - 'choices' (array, optional): Für 'radio' oder 'select', definiert mögliche Werte und Labels.
 *    - 'callback' (string, optional): Name einer Funktion für 'action' Optionen.
 *    - 'readonly' (bool, optional): Für 'code' und 'json', ob die Option schreibgeschützt ist.
 *    - 'alertType' (string, optional): Für 'alert', gibt den Typ der Warnung an (primary, success, danger etc.).
 *    - 'icon' (string, optional): Dashicons-Klasse für die Anzeige.
 *
 */

global $fxwp_plugin_config;
$fxwp_plugin_config = array(
    'nav_pages' => array(
        // Seite: P2 Connection – hier werden die bisher getrennten Optionen zusammengefasst.
        'p2_connection' => array(
            'order' => 30,
            'title' => 'WP Hosting',
            'icon' => 'dashicons dashicons-wordpress-alt',
            'slug' => 'p2_connection',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'connection_settings' => array(
                    'title' => 'Instanz',
                    'options' => array(
                        'fxwp_local_instance' => array(
                            'type' => 'alert',
                            'alertIcon' => 'dashicons dashicons-admin-site',
                            'title' => 'Lokale Instanz',
//                            'color' => fxwp_is_local_instance() ? 'warning' : 'info',
                            'text' => fxwp_is_local_instance() ? 'Es handelt sich um eine lokale Instanz.' : 'Es handelt sich um eine online Instanz.',
                        ),
                        'fxwp_restricted' => array(
                            'type' => 'filesize',
                            'title' => 'Speicherlimit',
                            'description' => 'Gib das Speicherlimit in GB, MB oder KB ein. Intern wird in Bytes gespeichert.',
                            'default' => 20 * 1024 * 1024 * 1024, // 20 GB
                        ),
                    ),
                ),

                'debugging' => array(
                    'title' => 'Debugging',
                    'density' => 'dense',
                    'options' => array(
                        // ein hinweis dass diese option gesetzt werden mmüssen und aber erst änderungen übernommen werden wenn man auf in wp-config schreiben klickt
                        'fxwp_debugging_hint' => array(
                            'type' => 'alert',
                            'title' => 'De  bugging Optionen',
                            'alertIcon' => 'dashicons dashicons-warning',
                            'color' => 'primary',
                            'text' => 'Bitte beachten Sie, dass die Debugging Optionen erst nach dem Klick auf "In wp-config schreiben" aktiviert werden.',
                        ),
                        'fxwp_debugging_enable' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_log' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG_LOG aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_display' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG_DISPLAY aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_scripts' => array(
                            'type' => 'checkbox',
                            'title' => 'SCRIPT_DEBUG aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_savequeries' => array(
                            'type' => 'checkbox',
                            'title' => 'SAVEQUERIES aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_errorreporting' => array(
                            'type' => 'checkbox',
                            'title' => 'error_reporting(E_ALL) aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_display_ini' => array(
                            'type' => 'checkbox',
                            'title' => 'display_errors aktivieren',
                            'default' => false,
                        ),
                        'fxwp_debugging_display_ini_startup' => array(
                            'type' => 'checkbox',
                            'title' => 'display_startup_errors aktivieren',
                            'default' => false,
                        ),

                        // write to wp-config.php
                        'fxwp_debugging_write' => array(
                            'type' => 'action',
                            'title' => 'Debugging Optionen in wp-config schreiben',
                            'description' => 'Schreibt Debugging Optionen in die wp-config.php.',
                            'callback' => 'fxwp_write_debugging',
                        ),
                    ),
                ),
            ),
        ),
        // NEU: Seite zum Anzeigen der P2 JSON-Daten
        'p2_data' => array(
            'title' => 'P2 Integration',
            'order' => 25,
            'icon' => 'dashicons dashicons-vault',
            'slug' => 'p2_data',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'p2_json_display' => array(
                    'title' => 'P2 JSON Daten',
                    'options' => array(
                        'fxwp_customer_json' => array(
                            'type' => 'json',
                            'title' => 'Kunde JSON',
                            'description' => 'Anzeige der Kundendaten (P2).',
                            'default' => json_encode(get_option('fxwp_customer', array())),
                            'readonly' => true,
                        ),
                        'fxwp_project_json' => array(
                            'type' => 'json',
                            'title' => 'Projekt JSON',
                            'description' => 'Anzeige der Projektdaten (P2).',
                            'default' => json_encode(get_option('fxwp_project', array())),
                            'readonly' => true,
                        ),
                        'fxwp_plans_json' => array(
                            'type' => 'json',
                            'title' => 'Pläne JSON',
                            'description' => 'Anzeige der Plandaten (P2).',
                            'default' => json_encode(get_option('fxwp_plans', array())),
                            'readonly' => true,
                        ),
                    ),
                ),
                'license_management' => array(
                    'title' => 'Faktor×WP Lizenz',
                    'options' => array(
                        'fxwp_api_key' => array(
                            'type' => 'text',
                            'title' => 'Lizenz Schlüssel',
                            'description' => 'Bitte geben Sie Ihren Lizenz Schlüssel ein.',
                            'default' => '',
                        ),
                        'fxwp_api_key_renew' => array(
                            'type' => 'action',
                            'title' => 'Lizenz erneuern',
                            'description' => 'Erneuert den API-Schlüssel.',
                            'callback' => 'fxwp_run_api_key_renew',
                        ),
                        'fxwp_api_key_uninstall' => array(
                            'type' => 'action',
                            'title' => 'Lizenz deinstallieren',
                            'description' => 'Deinstalliert den Lizenz Schlüssel per Knopfdruck.',
                            'callback' => 'fxwp_run_api_key_uninstall',
                        ),
                        // checekn action ob die lizenz gültig ist
                        'fxwp_api_key_check' => array(
                            'type' => 'action',
                            'title' => 'Lizenz prüfen',
                            'description' => 'Prüft ob der Lizenz Schlüssel gültig ist.',
                            'callback' => 'fxwp_run_api_key_check',
                        ),

                    ),
                ),
            ),
        ),
        // Seite für  Restirioncts
        'restrictions' => array(
            'title' => 'Beschränkungen',
            'order' => 20,
            'icon' => 'dashicons dashicons-shield',
            'slug' => 'restrictions',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                // Section für deaktivierte Funktionen
                'deactivated_features' => array(
                    'title' => 'Deaktivierte Funktionen',
                    'density' => 'dense',
                    'options' => array(
                        'fxwp_deact_ai' => array(
                            'type' => 'checkbox',
                            'title' => 'KI Funktionen deaktivieren',
                            'default' => false,
                        ),
                        'fxwp_deact_backups' => array(
                            'type' => 'checkbox',
                            'title' => 'Backups deaktivieren',
                            'default' => false,
                        ),
                        'fxwp_deact_autoupdates' => array(
                            'type' => 'checkbox',
                            'title' => 'Automatische Updates deaktivieren',
                            'default' => false,
                        ),
                        'fxwp_deact_email_log' => array(
                            'type' => 'checkbox',
                            'title' => 'E‑Mail Log für Kundis ausblenden',
                            'default' => false,
                        ),
                        'fxwp_deact_shortcodes' => array(
                            'type' => 'checkbox',
                            'title' => 'Shortcodes für Kundis ausblenden',
                            'default' => false,
                        ),
                        'fxwp_deact_dashboards' => array(
                            'type' => 'checkbox',
                            'title' => 'Alle Dashboards für Kundis ausblenden',
                            'default' => false,
                        ),
                        'fxwp_deact_debug_log_widget' => array(
                            'type' => 'checkbox',
                            'title' => 'Debug Log Widget ausblenden',
                            'default' => false,
                        ),
                        'fxwp_deact_customer_settings' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugin Settings für Kundis komplett ausblenden',
                            'default' => false,
                        ),
                        'fxwp_deact_hide_plugin' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugin vor Kundis komplett verstecken',
                            'default' => false,
                        ),
                        /* button um deaktivierte funktionen aus alter version zu importieren */
                        'fxwp_deact_import' => array(
                            'type' => 'action',
                            'title' => 'Deaktivierte Funktionen importieren',
                            'description' => 'Importiert deaktivierte Funktionen aus einer älteren Version.',
                            'callback' => 'fxwp_import_deactivated_features',
                        ),

                    ),
                ),
                // Section für eingeschränkte Funktionen
                'restricted_features' => array(
                    'density' => 'dense',
                    'title' => 'Eingeschränkte Funktionen',
                    'options' => array(
                        'fxwp_restr_pages' => array(
                            'type' => 'checkbox',
                            'title' => 'Seiten',
                            'default' => false,
                        ),
                        'fxwp_restr_posts' => array(
                            'type' => 'checkbox',
                            'title' => 'Blogposts',
                            'default' => false,
                        ),
                        'fxwp_restr_uploads' => array(
                            'type' => 'checkbox',
                            'title' => 'Mediendateien',
                            'default' => false,
                        ),
                        'fxwp_restr_themes' => array(
                            'type' => 'checkbox',
                            'title' => 'Themes',
                            'default' => false,
                        ),
                        'fxwp_restr_updates-submenu' => array(
                            'type' => 'checkbox',
                            'title' => 'Updates Submenu von Dashboard',
                            'default' => false,
                        ),
                        'fxwp_restr_elememtor-templates' => array(
                            'type' => 'checkbox',
                            'title' => 'Elementor Templates',
                            'default' => false,
                        ),
                        'fxwp_restr_wpcf7' => array(
                            'type' => 'checkbox',
                            'title' => 'Contact Form 7',
                            'default' => false,
                        ),
                        'fxwp_restr_new-button' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar New Button',
                            'default' => false,
                        ),
                        'fxwp_restr_updates-indicator' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar Updates Indicator',
                            'default' => false,
                        ),
                        'fxwp_restr_my-account' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar Account',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_plugins' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugins',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_users' => array(
                            'type' => 'checkbox',
                            'title' => 'Benutzer',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_tools' => array(
                            'type' => 'checkbox',
                            'title' => 'Tools',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_settings' => array(
                            'type' => 'checkbox',
                            'title' => 'WP Einstellungen',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_elementor' => array(
                            'type' => 'checkbox',
                            'title' => 'Elementor Einstellungen',
                            'default' => false,
                        ),
                        'fxwp_restr_admin_eael' => array(
                            'type' => 'checkbox',
                            'title' => 'Essential Addons for Elementor Einstellungen',
                            'default' => false,
                        ),
                        // eingeschränkte Funktionen importieren aus alter
                        'fxwp_restr_import' => array(
                            'type' => 'action',
                            'title' => 'Eingeschränkte Funktionen importieren',
                            'description' => 'Importiert eingeschränkte Funktionen aus einer älteren Version.',
                            'callback' => 'fxwp_import_restricted_features',
                        ),
                    ),
                ),
                // Tiefliegende Plugin Beschränkungen
                'plugin_restrictions' => array(
                    'title' => 'Plugin Beschränkungen',
                    'options' => array(
                        'fxwp_wordfence_email_mod_active' => array(
                            'type' => 'checkbox',
                            'title' => 'Wordfence Mod ist aktiviert',
                            'description' => 'Aktiviert den Wordfence Mod, damit Kundis nichts an Wordfence E-mails mitbekommen bzw. ändern können.',
                            'default' => true,
                        ),
                    ),
                ),
            ),
        ),
        // Seite für Updates (durch kunden)
        'p2_updates' => array(
            'title' => 'Kundi-Updates',
            'order' => 40,
            'icon' => 'dashicons dashicons-update',
            'slug' => 'p2_updates',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'update_settings' => array(
                    'title' => 'Updates durch Kund:innen',
                    'options' => array(
                        'fxm_customer_update_dashboard' => array(
                            'type' => 'checkbox',
                            'title' => 'Kunden Update Dashboard anzeigen',
                            'description' => 'Ermöglicht Kund:innen manuelles Update in einer einfachen Ansicht.',
                            'default' => false,
                        ),
                        'cud_notify_enabled' => array(
                            'type' => 'checkbox',
                            'title' => 'E‑Mail Benachrichtigung aktivieren',
                            'description' => 'Aktiviert die E‑Mail Benachrichtigung bei verfügbaren Updates.',
                            'default' => false,
                        ),
                        'cud_notify_email' => array(
                            'type' => 'text',
                            'title' => 'E‑Mail Adresse',
                            'description' => 'Geben Sie die E‑Mail-Adresse ein, an die Benachrichtigungen gesendet werden sollen.',
                            'default' => '',
                        ),
                    ),
                ),
                'auto_update_section' => array(
                    'title' => 'Automatische & manuelle Aktualisierungen',
                    'options' => array(
                        'fxwp_automatic_updates' => array(
                            'type' => 'checkbox',
                            'title' => 'Automatische Updates',
                            'description' => 'Wenn aktiviert, werden alle Plugins und die WordPress-Kernsoftware automatisch aktualisiert.',
                            'default' => true,
                        ),
                        'fxwp_manual_update_core' => array(
                            'type' => 'action',
                            'title' => 'WordPress jetzt aktualisieren',
                            'description' => 'Führt eine manuelle Aktualisierung der WordPress-Kernsoftware durch.',
                            'callback' => 'fxwp_run_manual_update_core',
                        ),
                    ),
                ),

            ),
        ),
        'backup_settings' => array(
            'title' => 'Sicherung',
            'order' => 50,
            'icon' => 'dashicons dashicons-backup',
            'slug' => 'backup_settings',
            'sections' => array(
                'backup_settings' => array(
                    'title' => 'Backup Einstellungen',
                    'options' => array(
                        'fxwp_backup_interval' => array(
                            'type' => 'select',
                            'title' => 'Backup Intervall',
                            'description' => 'Wie oft sollen Backups erstellt werden?',
                            'default' => 'twicedaily',
                            'choices' => array(
                                'hourly' => 'Stündlich',
                                'twicedaily' => 'Zweimal täglich',
                                'daily' => 'Täglich'
                            )
                        ),
                        'fxwp_backup_days_son' => array(
                            'type' => 'number',
                            'title' => 'Stündliche Backups behalten (Tage)',
                            'description' => 'Für wie viele Tage sollen stündliche Backups behalten werden?',
                            'default' => 3
                        ),
                        'fxwp_backup_days_father' => array(
                            'type' => 'number',
                            'title' => 'Tägliche Backups behalten (Tage)',
                            'description' => 'Für wie viele Tage sollen tägliche Backups behalten werden?',
                            'default' => 12
                        ),
                        'fxwp_backup_days_grandfather' => array(
                            'type' => 'number',
                            'title' => 'Monatliche Backups behalten (Tage)',
                            'description' => 'Für wie viele Tage sollen monatliche Backups behalten werden?',
                            'default' => 90
                        )
                    )
                )
            )
        ),
    ),
);


function fxwp_get_options_config()
{
    global $fxwp_plugin_config;

    // Allow other plugins to add/modify the configuration.
    $fxwp_plugin_config = apply_filters('fxwp_options_config', $fxwp_plugin_config);

    // order the pages by order
    uasort($fxwp_plugin_config['nav_pages'], function ($a, $b) {
        return $a['order'] <=> $b['order'];
    });

    return $fxwp_plugin_config;
}

function fxwp_run_manual_update_core()
{
    $update_url = wp_nonce_url(admin_url('update-core.php'), 'upgrade-core');
    // curl it
    return array("redirect" => $update_url);
}

function fxwp_run_api_key_renew()
{
    fxwp_deactivation();
    fxwp_activation();
    return array("message" => "Lizenz Schlüssel erneuert.", "color" => "info");
}

function fxwp_run_api_key_uninstall()
{
    fxwp_deactivation();
    return array("message" => "Lizenz Schlüssel deinstalliert.", "color" => "danger");
}

function fxwp_run_api_key_check()
{
    /*
     * import {LetterSend, Project} from "~/server/eloquent";
import axios from "axios";
import {DEFAULT_PRICE} from "~/composables/enum";

export default defineEventHandler(async (event) => {
    const body = await readBody(event)
    const apikey = event.context.params.apikey
    const project = await Project.findOne({"fxwp.api_key": {$exists: true, $eq: apikey}})
    // returne ob die lizenz gülting ist oder nicht
    if (!project) return {error: 'Projekt mit diesem API Key nicht gefunden', success: false}
    return {success: true}
})


     */
    $api_key = get_option('fxwp_api_key');
    // code to execute on plugin deactivation
    $response = wp_remote_post(FXWP_API_URL . '/' . $api_key . '/check');

    if (is_wp_error($response)) {
        return "Fehler beim Überprüfen des Lizenzschlüssels.";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data['success']) {
            return array("message" => "Lizenz Schlüssel gültig.", "color" => "info");
        } else {
            return array("message" => "Lizenz Schlüssel ungültig.", "color" => "error", "data" => $data, "response" => $response);
        }
    }

}

function fxwp_get_restr()
{
    // return like an array of fxwp_restr_posts, ... (only includes elements that are set tot true
    // we need to gather all get_options (in the above config array we see whichc there are), get tem live from there
    $fx_plugin_config = fxwp_get_options_config();
    $options = $fx_plugin_config['nav_pages']['restrictions']['sections']['restricted_features']['options'];

    if (!$options) return;

    $restr = array();
    foreach ($options as $key => $option) {
        if (get_option($key)) {
            $restr[$key] = get_option($key);
        }
    }
    return $restr;
}

// for the other section as well
function fxwp_get_deact()
{
    $fx_plugin_config = fxwp_get_options_config();
    $options = $fx_plugin_config['nav_pages']['restrictions']['sections']['deactivated_features']['options'];

    if (!$options) return;

    $deact = array();
    foreach ($options as $key => $option) {
        if (get_option($key)) {
            $deact[$key] = get_option($key);
        }
    }
    return $deact;
}

// fxwp_debugging
function fxwp_get_debugging()
{
    $fx_plugin_config = fxwp_get_options_config();
    $options = $fx_plugin_config['nav_pages']['p2_connection']['sections']['debugging']['options'];

    if (!$options) return;

    $debugging = array();
    foreach ($options as $key => $option) {
        if (get_option($key)) {
            $debugging[$key] = get_option($key);
        }
    }
    return $debugging;
}

function fxwp_import_deactivated_features()
{
    $deactivated_features_description = array(
        'fxwp_deact_ai' => 'KI Funktionen deaktivieren',
        'fxwp_deact_backups' => 'Backups deaktivieren',
        'fxwp_deact_autoupdates' => 'Automatische Updates deaktivieren',
        'fxwp_deact_email_log' => 'E-Mail Log für Kundis ausblenden',
        'fxwp_deact_shortcodes' => 'Shortcodes für Kundis ausblenden',
        'fxwp_deact_dashboards' => 'Alle Dashboards für Kundis ausblenden',
        'fxwp_deact_debug_log_widget' => 'Debug Log Widget ausblenden',
        'fxwp_deact_customer_settings' => 'Plugin Settings für Kundis komplett ausblenden',
        'fxwp_deact_hide_plugin' => 'Plugin vor Kundis komplett verstecken',
    );

    $alt = get_option("fxwp_deactivated_features");
    $alt = json_decode($alt, true);

    // die jetz alle als ecthe wp_options
    foreach ($deactivated_features_description as $key => $description) {
        // vom alten hole
        update_option($key, $alt[$key]);
    }
}

function fxwp_import_restricted_features()
{
    $restricted_features_description = array(
        'fxwp_restr_pages' => 'Seiten',
        'fxwp_restr_posts' => 'Blogposts',
        'fxwp_restr_uploads' => 'Mediendateien',
        'fxwp_restr_themes' => 'Themes',
        'fxwp_restr_updates-submenu' => 'Updates Submenu von Dashboard',
        'fxwp_restr_elememtor-templates' => 'Elementor Templates',
        'fxwp_restr_wpcf7' => 'Contact Form 7',
        'fxwp_restr_new-button' => 'Admin Bar New Button',
        'fxwp_restr_updates-indicator' => 'Admin Bar Updates Indicator',
        'fxwp_restr_my-account' => 'Admin Bar Account',
        'fxwp_restr_admin_plugins' => 'Plugins',
        'fxwp_restr_admin_users' => 'Benutzer',
        'fxwp_restr_admin_tools' => 'Tools',
        'fxwp_restr_admin_settings' => 'WP Einstellungen',
        'fxwp_restr_admin_elementor' => 'Elementor Einstellungen',
        'fxwp_restr_admin_eael' => 'Essential Addons for Elementor Einstellungen',
    );

    $alt = get_option("fxwp_restricted_features");
    $alt = json_decode($alt, true);

    // die jetz alle als ecthe wp_options
    foreach ($restricted_features_description as $key => $description) {
        // vom alten hole
        update_option($key, $alt[$key]);
    }
}

/*$debugging_options_description = array(
	'fxwp_debugging_enable' => "define( 'WP_DEBUG', true );",
	'fxwp_debugging_log' => "define( 'WP_DEBUG_LOG', true );",
	'fxwp_debugging_display' => "define( 'WP_DEBUG_DISPLAY', true );",
	'fxwp_debugging_scripts' => "define( 'SCRIPT_DEBUG', true );",
	'fxwp_debugging_savequeries' => "define( 'SAVEQUERIES', true );",
	'fxwp_debugging_errorreporting' => "error_reporting(E_ALL);",
	'fxwp_debugging_display_ini' => "ini_set('display_errors',1);",
	'fxwp_debugging_display_ini_startup' => "ini_set('display_startup_errors', '1');",
);
*/
function fxwp_write_debugging()
{
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

    $filePath = ABSPATH . 'wp-config.php';
    $fileContents = file($filePath);
    $finalLines = array();

    // Entferne vorhandene Debugging-Blöcke, die mit unseren Markern versehen sind
    $inExistingBlock = false;
    foreach ($fileContents as $line) {
        if (strpos($line, "// Faktor×WordPress Debugging Options") !== false) {
            $inExistingBlock = true;
            continue;
        }
        if ($inExistingBlock) {
            if (strpos($line, "// End of Faktor×WordPress Debugging Options") !== false) {
                $inExistingBlock = false;
            }
            continue;
        }
        $finalLines[] = $line;
    }

    // Entferne einzelne Debugging-Optionen, falls sie bereits außerhalb eines Blocks existieren
    foreach ($finalLines as $index => $line) {
        foreach ($debugging_options_description as $key => $value) {
            if (strpos($line, $value) !== false) {
                unset($finalLines[$index]);
                break;
            }
        }
    }
    $finalLines = array_values($finalLines);

    // Bestimme die Einfügeposition: bevorzugt nach der Zeile mit /**#@-*/
    $insertionIndex = null;
    foreach ($finalLines as $index => $line) {
        if (strpos($line, '/**#@-*/') !== false) {
            $insertionIndex = $index + 1;
            break;
        }
    }
    // Falls nicht gefunden, dann nach der Zeile mit $table_prefix einfügen
    if ($insertionIndex === null) {
        foreach ($finalLines as $index => $line) {
            if (strpos($line, '$table_prefix') !== false) {
                $insertionIndex = $index + 1;
                break;
            }
        }
    }
    // Falls auch das nicht gefunden wird, am Ende der Datei einfügen
    if ($insertionIndex === null) {
        $insertionIndex = count($finalLines);
    }

    // Erstelle den neuen Debugging-Block
    $debugBlock = array();
    $debugBlock[] = "// Faktor×WordPress Debugging Options\n";
    $debugBlock[] = "// ------------------------------\n";
    foreach ($debugging_options_description as $key => $value) {
        if (get_option($key)) {
            $debugBlock[] = $value . "\n";
        }
    }
    $debugBlock[] = "// ------------------------------\n";
    $debugBlock[] = "// End of Faktor×WordPress Debugging Options\n";

    // Füge den Debugging-Block an der festgelegten Position ein
    array_splice($finalLines, $insertionIndex, 0, $debugBlock);

    // Schreibe die modifizierten Inhalte zurück in die wp-config.php
    if (!file_put_contents($filePath, implode('', $finalLines))) {
        error_log("Konnte wp-config.php nicht sichern");
        return;
    }

    return array("message" => "Debugging Optionen erfolgreich in wp-config.php geschrieben.", "color" => "info");
}


function fxwp_is_local_instance()
{
    return defined('FXWP_LOCAL_ENV') && FXWP_LOCAL_ENV;
}

/*
// add each options page acutally as submenu item to the settings menu
like here
    if (current_user_can('fxm_admin')) {
        add_submenu_page(
            'fxwp', // Parent slug
            'Options', // Page title
            'Options', // Menu title
            'administrator', // Capability
            'fxwp-options', // Menu slug
            'fxwp_options_page' // Function
        );
    }
*/
function fxwp_add_options_pages()
{
    $fx_plugin_config = fxwp_get_options_config();
    foreach ($fx_plugin_config['nav_pages'] as $key => $page) {
        add_submenu_page(
            'fxwp', // Parent slug
            $page['title'], // Page title
            $page['title'], // Menu title
            'administrator', // Capability
            // acutally hard link to /wp-admin/admin.php?page=fxwp-options&nav=p2_data (with the nav being the key of the page)
            'admin.php?page=fxwp-options&nav=' . $key, // Menu slug
            'fxwp_options_page' // Function
        );
    }
    // hide a submenu item that matches href=admin.php?page=fxwp-options via css
    echo '<script>document.addEventListener("DOMContentLoaded", function() {document.querySelector("a[href=\'admin.php?page=fxwp-options\']").style.display = "none";});</script>';
}

add_action('admin_menu', 'fxwp_add_options_pages');

