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

global $fx_plugin_config;
$fx_plugin_config = array(
    'nav_pages' => array(
        // Seite: P2 Connection – hier werden die bisher getrennten Optionen zusammengefasst.
        'p2_connection' => array(
            'title' => 'Hosting',
            'icon' => 'dashicons dashicons-networking',
            'slug' => 'p2_connection',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'connection_settings' => array(
                    'title' => 'Connection Settings',
                    'options' => array(
                        // Zusammengefasste Option (bisher fxwp_storage_limit und fxwp_restricted_test)
                        'fxwp_restricted' => array(
                            'type' => 'filesize',
                            'title' => 'Speicherlimit',
                            'description' => 'Gib das Speicherlimit in GB, MB oder KB ein. Intern wird in Bytes gespeichert.',
                            'default' => 20 * 1024 * 1024 * 1024, // 20 GB
                        ),
                    ),
                ),
            ),
        ),
        // NEU: Seite zum Anzeigen der P2 JSON-Daten
        'p2_data' => array(
            'title' => 'P2 Integration',
            'icon' => 'dashicons dashicons-media-code',
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
            'icon' => 'dashicons dashicons-shield',
            'slug' => 'restrictions',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                // section for fxm restrictions (only fxwp_view_option)
                'fxm_restrictions' => array(
                    'title' => 'Faktor×Mensch Beschränkungen',
                    'options' => array(
                        'fxwp_view_option' => array(
                            'type' => 'select',
                            'title' => 'Ansichtsoption (in Settings)',
                            'description' => 'Wählen Sie die Ansichtsoption für Faktor×Mensch.',
                            'default' => 'einfach',
                            'choices' => array(
                                'einfach' => 'Einfach',
                                'erweitert' => 'Erweitert',
                            ),
                        ),
                    ),
                ),

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
                    ),
                ),
            ),
        ),

        // Seite für Updates (durch kunden)
        'p2_updates' => array(
            'title' => 'Updates',
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
    ),
);
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
    global $fx_plugin_config;
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
function  fxwp_get_deact()
{
    global $fx_plugin_config;
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
