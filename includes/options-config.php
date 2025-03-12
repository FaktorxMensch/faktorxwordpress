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
        'p2_connection' => array(
            'order' => 30,
            'title' => 'PHP Server',
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
                            'text' => fxwp_is_local_instance() ? 'Es handelt sich um eine lokale Instanz.' : 'Es handelt sich um eine online Instanz.',
                            'description' => 'Zeigt an, ob die aktuelle Instanz lokal oder online betrieben wird.',
                            'keywords' => array('lokal', 'local', 'instance', 'localhost', 'entwicklung', 'development')
                        ),
                        'fxwp_local_instance_color' => array(
                            'type' => 'checkbox',
                            'title' => 'Lokale Instanz durch Farbe erkennen',
                            'description' => 'Setzt das Farbschema für lokale Instanzen, um sie besser zu erkennen.',
                            'default' => true,
                            'keywords' => array('farbe', 'color', 'local', 'instance', 'theme', 'icon')
                        ),
                        'fxwp_restricted' => array(
                            'type' => 'filesize',
                            'title' => 'Speicherlimit',
                            'description' => 'Gib das Speicherlimit in GB, MB oder KB ein. Intern wird der Wert in Bytes gespeichert.',
                            'default' => 20 * 1024 * 1024 * 1024,
                            'keywords' => array('speicher', 'storage', 'limit', 'memory', 'gb', 'mb', 'kb')
                        ),
                    ),
                ),
                'debugging' => array(
                    'title' => 'Debugging',
                    'density' => 'dense',
                    'options' => array(
                        'fxwp_debugging_hint' => array(
                            'type' => 'alert',
                            'title' => 'Debugging Optionen',
                            'alertIcon' => 'dashicons dashicons-warning',
                            'color' => 'primary',
                            'text' => 'Bitte beachten Sie, dass die Debugging Optionen erst nach dem Klick auf "In wp-config schreiben" aktiviert werden.',
                            'description' => 'Hinweis zur Aktivierung der Debugging Optionen in der wp-config.php.',
                            'keywords' => array('debug', 'debugging', 'hinweis', 'warning', 'wp-config')
                        ),
                        'fxwp_debugging_enable' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG aktivieren',
                            'default' => false,
                            'description' => 'Schaltet den WordPress Debug-Modus ein.',
                            'keywords' => array('wp_debug', 'debug', 'aktivieren', 'enable')
                        ),
                        'fxwp_debugging_log' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG_LOG aktivieren',
                            'default' => false,
                            'description' => 'Aktiviert das Debug Logging in eine Logdatei.',
                            'keywords' => array('debug_log', 'logging', 'log', 'debug', 'aktivieren', 'enable')
                        ),
                        'fxwp_debugging_display' => array(
                            'type' => 'checkbox',
                            'title' => 'WP_DEBUG_DISPLAY aktivieren',
                            'default' => false,
                            'description' => 'Aktiviert die Anzeige von Debug-Fehlern im Browser.',
                            'keywords' => array('display', 'debug', 'errors', 'anzeigen', 'enable')
                        ),
                        'fxwp_debugging_scripts' => array(
                            'type' => 'checkbox',
                            'title' => 'SCRIPT_DEBUG aktivieren',
                            'default' => false,
                            'description' => 'Erzwingt die Verwendung von nicht-minifizierten Skripten.',
                            'keywords' => array('script_debug', 'scripts', 'debug', 'enable')
                        ),
                        'fxwp_debugging_savequeries' => array(
                            'type' => 'checkbox',
                            'title' => 'SAVEQUERIES aktivieren',
                            'default' => false,
                            'description' => 'Speichert alle Datenbankabfragen zu Debugging-Zwecken.',
                            'keywords' => array('savequeries', 'queries', 'database', 'debug', 'aktivieren', 'enable')
                        ),
                        'fxwp_debugging_errorreporting' => array(
                            'type' => 'checkbox',
                            'title' => 'error_reporting(E_ALL) aktivieren',
                            'default' => false,
                            'description' => 'Aktiviert umfassendes Fehlerreporting.',
                            'keywords' => array('error_reporting', 'errors', 'debug', 'aktivieren', 'enable')
                        ),
                        'fxwp_debugging_display_ini' => array(
                            'type' => 'checkbox',
                            'title' => 'display_errors aktivieren',
                            'default' => false,
                            'description' => 'Setzt ini_set, um Fehler anzuzeigen.',
                            'keywords' => array('ini_set', 'display_errors', 'debug', 'enable', 'anzeigen')
                        ),
                        'fxwp_debugging_display_ini_startup' => array(
                            'type' => 'checkbox',
                            'title' => 'display_startup_errors aktivieren',
                            'default' => false,
                            'description' => 'Ermöglicht die Anzeige von PHP-Startfehlern.',
                            'keywords' => array('ini_set', 'startup_errors', 'debug', 'enable', 'anzeigen')
                        ),
                        'fxwp_debugging_write' => array(
                            'type' => 'action',
                            'title' => 'Debugging Optionen in wp-config schreiben',
                            'description' => 'Schreibt die aktiven Debugging Optionen in die wp-config.php.',
                            'callback' => 'fxwp_write_debugging',
                            'keywords' => array('wp-config', 'write', 'debug', 'configuration', 'schreiben')
                        ),
                    ),
                ),
            ),
        ),
        'p2_data' => array(
            'title' => 'Projektpilot',
            'order' => 30,
            'icon' => 'dashicons dashicons-vault',
            'slug' => 'p2_data',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'p2_json_display' => array(
                    'title' => 'JSON Daten',
                    'options' => array(
                        'fxwp_customer_json' => array(
                            'type' => 'json',
                            'title' => 'Kunde JSON',
                            'description' => 'Anzeige der Kundendaten (P2) als JSON.',
                            'default' => json_encode(get_option('fxwp_customer', array())),
                            'readonly' => true,
                            'keywords' => array('kunde', 'customer', 'json', 'daten', 'data', 'anzeige')
                        ),
                        'fxwp_project_json' => array(
                            'type' => 'json',
                            'title' => 'Projekt JSON',
                            'description' => 'Anzeige der Projektdaten (P2) als JSON.',
                            'default' => json_encode(get_option('fxwp_project', array())),
                            'readonly' => true,
                            'keywords' => array('projekt', 'project', 'json', 'daten', 'data', 'anzeige')
                        ),
                        'fxwp_plans_json' => array(
                            'type' => 'json',
                            'title' => 'Pläne JSON',
                            'description' => 'Anzeige der Plandaten (P2) als JSON.',
                            'default' => json_encode(get_option('fxwp_plans', array())),
                            'readonly' => true,
                            'keywords' => array('pläne', 'plans', 'json', 'daten', 'data', 'anzeige')
                        ),
                    ),
                ),
                'license_management' => array(
                    'title' => 'Faktor×WP Lizenz',
                    'options' => array(
                        'fxwp_api_key' => array(
                            'type' => 'text',
                            'title' => 'Lizenz Schlüssel',
                            'description' => 'Bitte geben Sie Ihren Lizenzschlüssel ein.',
                            'default' => '',
                            'keywords' => array('lizenz', 'license', 'api_key', 'key', 'auth')
                        ),
                        'fxwp_api_key_renew' => array(
                            'type' => 'action',
                            'title' => 'Lizenz erneuern',
                            'description' => 'Erneuert den aktuellen API-Schlüssel.',
                            'callback' => 'fxwp_run_api_key_renew',
                            'keywords' => array('erneuern', 'renew', 'license', 'api_key', 'update')
                        ),
                        'fxwp_api_key_uninstall' => array(
                            'type' => 'action',
                            'title' => 'Lizenz deinstallieren',
                            'description' => 'Deinstalliert den Lizenzschlüssel per Knopfdruck.',
                            'callback' => 'fxwp_run_api_key_uninstall',
                            'keywords' => array('deinstallieren', 'uninstall', 'license', 'api_key', 'remove')
                        ),
                        'fxwp_api_key_check' => array(
                            'type' => 'action',
                            'title' => 'Lizenz prüfen',
                            'description' => 'Prüft, ob der eingegebene Lizenzschlüssel gültig ist.',
                            'callback' => 'fxwp_run_api_key_check',
                            'keywords' => array('prüfen', 'check', 'license', 'validation', 'api_key')
                        ),
                    ),
                ),
            ),
        ),
        'restrictions' => array(
            'title' => 'Zugriffssteuerung',
            'order' => 20,
            'icon' => 'dashicons dashicons-shield',
            'slug' => 'restrictions',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'deactivated_features' => array(
                    'title' => 'Deaktivierte Funktionen',
                    'density' => 'dense',
                    'options' => array(
                        'fxwp_deact_ai' => array(
                            'type' => 'checkbox',
                            'title' => 'KI Funktionen deaktivieren',
                            'default' => false,
                            'description' => 'Schaltet Funktionen im Bereich künstliche Intelligenz ab.',
                            'keywords' => array('ai', 'künstliche intelligenz', 'disable', 'deaktivieren')
                        ),
                        'fxwp_deact_backups' => array(
                            'type' => 'checkbox',
                            'title' => 'Backups deaktivieren',
                            'default' => false,
                            'description' => 'Deaktiviert automatische Backups.',
                            'keywords' => array('backups', 'disable', 'deaktivieren', 'sicherung')
                        ),
                        'fxwp_deact_autoupdates' => array(
                            'type' => 'checkbox',
                            'title' => 'Automatische Updates deaktivieren',
                            'default' => false,
                            'description' => 'Schaltet automatische Updates ab.',
                            'keywords' => array('autoupdates', 'automatic', 'disable', 'deaktivieren')
                        ),
                        'fxwp_deact_email_log' => array(
                            'type' => 'checkbox',
                            'title' => 'E‑Mail Log für Kundis ausblenden',
                            'default' => false,
                            'description' => 'Blendet das E‑Mail Log vor Kund:innen aus.',
                            'keywords' => array('email', 'log', 'hide', 'ausblenden', 'customer', 'kundis')
                        ),
                        'fxwp_deact_shortcodes' => array(
                            'type' => 'checkbox',
                            'title' => 'Shortcodes für Kundis ausblenden',
                            'default' => false,
                            'description' => 'Blendet Shortcodes im Frontend vor Kund:innen aus.',
                            'keywords' => array('shortcodes', 'hide', 'ausblenden', 'customer', 'kundis')
                        ),
                        'fxwp_deact_dashboards' => array(
                            'type' => 'checkbox',
                            'title' => 'Alle Dashboards für Kundis ausblenden',
                            'default' => false,
                            'description' => 'Versteckt alle administrativen Dashboards vor Kund:innen.',
                            'keywords' => array('dashboards', 'hide', 'ausblenden', 'customer', 'kundis')
                        ),
                        'fxwp_deact_debug_log_widget' => array(
                            'type' => 'checkbox',
                            'title' => 'Debug Log Widget ausblenden',
                            'default' => false,
                            'description' => 'Blendet das Debug Log Widget im Backend aus.',
                            'keywords' => array('debug log', 'widget', 'hide', 'ausblenden')
                        ),
                        'fxwp_deact_customer_settings' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugin Settings für Kundis ausblenden',
                            'default' => false,
                            'description' => 'Verbirgt sämtliche Plugin-Einstellungen vor Kund:innen.',
                            'keywords' => array('settings', 'plugin', 'hide', 'ausblenden', 'customer', 'kundis')
                        ),
                        'fxwp_deact_hide_plugin' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugin vor Kundis verstecken',
                            'default' => false,
                            'description' => 'Versteckt das gesamte Plugin im Adminbereich vor Kund:innen.',
                            'keywords' => array('plugin', 'hide', 'ausblenden', 'customer', 'kundis')
                        ),
                        'fxwp_deact_import' => array(
                            'type' => 'action',
                            'title' => 'Deaktivierte Funktionen importieren',
                            'description' => 'Importiert deaktivierte Funktionen aus einer älteren Version.',
                            'callback' => 'fxwp_import_deactivated_features',
                            'keywords' => array('import', 'deaktiviert', 'deactivated', 'legacy', 'importieren')
                        ),
                    ),
                ),
                'restricted_features' => array(
                    'density' => 'dense',
                    'title' => 'Eingeschränkte Funktionen',
                    'options' => array(
                        'fxwp_restr_pages' => array(
                            'type' => 'checkbox',
                            'title' => 'Seiten',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Seiten ein.',
                            'keywords' => array('seiten', 'pages', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_posts' => array(
                            'type' => 'checkbox',
                            'title' => 'Blogposts',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Blogposts ein.',
                            'keywords' => array('posts', 'blogposts', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_uploads' => array(
                            'type' => 'checkbox',
                            'title' => 'Mediendateien',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Mediendateien ein.',
                            'keywords' => array('uploads', 'media', 'files', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_themes' => array(
                            'type' => 'checkbox',
                            'title' => 'Themes',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Theme-Einstellungen ein.',
                            'keywords' => array('themes', 'design', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_updates-submenu' => array(
                            'type' => 'checkbox',
                            'title' => 'Updates Submenu von Dashboard',
                            'default' => false,
                            'description' => 'Blendet das Updates-Submenü im Dashboard aus.',
                            'keywords' => array('updates', 'submenu', 'dashboard', 'restrict', 'ausblenden')
                        ),
                        'fxwp_restr_elememtor-templates' => array(
                            'type' => 'checkbox',
                            'title' => 'Elementor Templates',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Elementor Templates ein.',
                            'keywords' => array('elementor', 'templates', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_wpcf7' => array(
                            'type' => 'checkbox',
                            'title' => 'Contact Form 7',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Contact Form 7 Einstellungen ein.',
                            'keywords' => array('contact form 7', 'wpcf7', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_new-button' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar New Button',
                            'default' => false,
                            'description' => 'Blendet den "New" Button in der Admin Bar aus.',
                            'keywords' => array('admin bar', 'new button', 'restrict', 'ausblenden')
                        ),
                        'fxwp_restr_updates-indicator' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar Updates Indicator',
                            'default' => false,
                            'description' => 'Schränkt die Anzeige des Updates-Indikators in der Admin Bar ein.',
                            'keywords' => array('admin bar', 'updates', 'indicator', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_my-account' => array(
                            'type' => 'checkbox',
                            'title' => 'Admin Bar Account',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf den Accountbereich in der Admin Bar ein.',
                            'keywords' => array('admin bar', 'account', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_plugins' => array(
                            'type' => 'checkbox',
                            'title' => 'Plugins',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Plugin-Einstellungen ein.',
                            'keywords' => array('plugins', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_users' => array(
                            'type' => 'checkbox',
                            'title' => 'Benutzer',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf die Benutzerverwaltung ein.',
                            'keywords' => array('benutzer', 'users', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_tools' => array(
                            'type' => 'checkbox',
                            'title' => 'Tools',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Tools im Adminbereich ein.',
                            'keywords' => array('tools', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_settings' => array(
                            'type' => 'checkbox',
                            'title' => 'WP Einstellungen',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf allgemeine WordPress Einstellungen ein.',
                            'keywords' => array('wp', 'einstellungen', 'settings', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_elementor' => array(
                            'type' => 'checkbox',
                            'title' => 'Elementor Einstellungen',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Elementor-spezifische Einstellungen ein.',
                            'keywords' => array('elementor', 'settings', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_eael' => array(
                            'type' => 'checkbox',
                            'title' => 'Essential Addons for Elementor Einstellungen',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Einstellungen der Essential Addons for Elementor ein.',
                            'keywords' => array('eael', 'elementor', 'settings', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_admin_wordfence' => array(
                            'type' => 'checkbox',
                            'title' => 'Wordfence',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Wordfence ein.',
                            'keywords' => array('wf', 'wordfence', 'securtiy', 'settings', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_rank_math' => array(
                            'type' => 'checkbox',
                            'title' => 'Rank Math',
                            'default' => false,
                            'description' => 'Schränkt den Zugriff auf Rank Math ein.',
                            'keywords' => array('rm', 'rank', 'math', 'seo', 'settings', 'admin', 'restrict', 'einschränken')
                        ),
                        'fxwp_restr_import' => array(
                            'type' => 'action',
                            'title' => 'Eingeschränkte Funktionen importieren',
                            'description' => 'Importiert eingeschränkte Funktionen aus einer älteren Version.',
                            'callback' => 'fxwp_import_restricted_features',
                            'keywords' => array('import', 'restricted', 'features', 'legacy', 'importieren')
                        ),
                    ),
                ),
                'plugin_restrictions' => array(
                    'title' => 'Plugin Zugriffssteuerung',
                    'options' => array(
                        'fxwp_wordfence_email_mod_active' => array(
                            'type' => 'checkbox',
                            'title' => 'Wordfence Mod ist aktiviert',
                            'description' => 'Aktiviert den Wordfence Mod, um zu verhindern, dass Kund:innen Wordfence E-Mails einsehen oder ändern können.',
                            'default' => true,
                            'keywords' => array('wordfence', 'email', 'mod', 'security', 'schutz', 'active')
                        ),
                    ),
                ),
            ),
        ),
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
                            'description' => 'Ermöglicht es Kund:innen, Updates manuell über ein vereinfachtes Dashboard anzustoßen.',
                            'default' => false,
                            'keywords' => array('customer', 'update', 'dashboard', 'anzeigen', 'manual')
                        ),
                        'cud_notify_enabled' => array(
                            'type' => 'checkbox',
                            'title' => 'E‑Mail Benachrichtigung aktivieren',
                            'description' => 'Sendet E‑Mail-Benachrichtigungen, wenn Updates verfügbar sind.',
                            'default' => false,
                            'keywords' => array('email', 'notification', 'benachrichtigung', 'update', 'enable')
                        ),
                        'cud_notify_email' => array(
                            'type' => 'text',
                            'title' => 'E‑Mail Adresse',
                            'description' => 'Geben Sie die E‑Mail-Adresse ein, an die Update-Benachrichtigungen gesendet werden.',
                            'default' => '',
                            'keywords' => array('email', 'address', 'benachrichtigung', 'update')
                        ),
                    ),
                ),
                'auto_update_section' => array(
                    'title' => 'Automatische & manuelle Aktualisierungen',
                    'options' => array(
                        'fxwp_automatic_updates' => array(
                            'type' => 'checkbox',
                            'title' => 'Automatische Updates',
                            'description' => 'Aktiviert automatische Updates für Plugins und die WordPress-Kernsoftware.',
                            'default' => true,
                            'keywords' => array('automatic', 'updates', 'auto', 'automatisch', 'wordpress', 'core')
                        ),
                        'fxwp_manual_update_core' => array(
                            'type' => 'action',
                            'title' => 'WordPress jetzt aktualisieren',
                            'description' => 'Führt eine manuelle Aktualisierung der WordPress-Kernsoftware durch.',
                            'callback' => 'fxwp_run_manual_update_core',
                            'keywords' => array('manual', 'update', 'wordpress', 'core', 'aktualisieren')
                        ),
                    ),
                ),
            ),
        ),
        'backup_settings' => array(
            'title' => 'Datensicherung',
            'order' => 35,
            'icon' => 'dashicons dashicons-backup',
            'slug' => 'backup_settings',
            'sections' => array(
                'backup_settings' => array(
                    'title' => 'Backup Einstellungen',
                    'options' => array(
                        'fxwp_backup_interval' => array(
                            'type' => 'select',
                            'title' => 'Backup Intervall',
                            'description' => 'Wählen Sie aus, wie oft Backups erstellt werden sollen.',
                            'default' => 'twicedaily',
                            'choices' => array(
                                'hourly' => 'Stündlich',
                                'twicedaily' => 'Zweimal täglich',
                                'daily' => 'Täglich'
                            ),
                            'keywords' => array('backup', 'intervall', 'interval', 'schedule', 'planen')
                        ),
                        'fxwp_backup_days_son' => array(
                            'type' => 'number',
                            'title' => 'Stündliche Backups behalten (Tage)',
                            'description' => 'Anzahl der Tage, für die stündliche Backups aufbewahrt werden sollen.',
                            'default' => 3,
                            'keywords' => array('backup', 'retention', 'hourly', 'days', 'aufbewahren')
                        ),
                        'fxwp_backup_days_father' => array(
                            'type' => 'number',
                            'title' => 'Tägliche Backups behalten (Tage)',
                            'description' => 'Anzahl der Tage, für die tägliche Backups aufbewahrt werden sollen.',
                            'default' => 12,
                            'keywords' => array('backup', 'retention', 'daily', 'days', 'aufbewahren')
                        ),
                        'fxwp_backup_days_grandfather' => array(
                            'type' => 'number',
                            'title' => 'Monatliche Backups behalten (Tage)',
                            'description' => 'Anzahl der Tage, für die monatliche Backups aufbewahrt werden sollen.',
                            'default' => 90,
                            'keywords' => array('backup', 'retention', 'monthly', 'days', 'aufbewahren')
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
        'fxwp_restr_admin_wordfence' => 'Wordfence',
        'fxwp_restr_rank_math' => 'Rank Math',
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

