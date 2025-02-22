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
// Nav-Seite: P2 Connection
        'p2_connection' => array(
            'title' => 'P2 Connection',
            'icon' => 'dashicons dashicons-networking',
            'slug' => 'p2_connection',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'connection_settings' => array(
                    'title' => 'Connection Settings',
                    'options' => array(
                        'fxwp_storage_limit' => array(
                            'type' => 'filesize',
                            'title' => 'Speicherlimit',
                            'description' => 'Gib das Speicherlimit in GB, MB oder KB ein. Intern wird in Bytes gespeichert.',
                            'default' => 20 * 1024 * 1024 * 1024, // 20 GB
                        ),
                        'fxwp_restricted_test' => array(
                            'type' => 'checkbox',
                            'title' => 'Eingeschränkte Features',
                            'description' => 'Aktiviert eingeschränkte Funktionen.',
                            'default' => false,
                        ),
                    ),
                ),
            ),
        ),
// Nav-Seite: WP Options
        'wp_options' => array(
            'title' => 'WP Options',
            'icon' => 'dashicons dashicons-admin-generic',
            'slug' => 'wp_options',
            'active_callback' => function () {
                return true;
            },
            'sections' => array(
                'general_options' => array(
                    'title' => 'General Options',
                    'options' => array(
                        'fxwp_dummy_number' => array(
                            'type' => 'number',
                            'title' => 'Dummy Number',
                            'description' => 'Eine Zahl als Beispiel.',
                            'default' => 10,
                        ),
                    ),
                ),
                'more_options' => array(
                    'title' => 'More Options',
                    'options' => array(
                        'fxwp_dummy_text' => array(
                            'type' => 'text',
                            'title' => 'Dummy Text',
                            'description' => 'Ein einfacher Textwert.',
                            'default' => 'Beispieltext',
                        ),
                        'fxwp_dummy_radio' => array(
                            'type' => 'radio',
                            'title' => 'Dummy Radio',
                            'description' => 'Wähle eine Option.',
                            'default' => 'option1',
                            'choices' => array(
                                array('value' => 'option1', 'label' => 'Option 1'),
                                array('value' => 'option2', 'label' => 'Option 2'),
                            ),
                        ),
                        'fxwp_dummy_select' => array(
                            'type' => 'select',
                            'title' => 'Dummy Select',
                            'description' => 'Wähle aus der Liste.',
                            'default' => 'a',
                            'choices' => array(
                                array('value' => 'a', 'label' => 'Auswahl A'),
                                array('value' => 'b', 'label' => 'Auswahl B'),
                                array('value' => 'c', 'label' => 'Auswahl C'),
                            ),
                        ),
                    ),
                ),
            ),
        ),
// Nav-Seite: Debugging
        'debugging' => array(
            'title' => 'Debugging',
            'icon' => 'dashicons dashicons-admin-tools',
            'slug' => 'debugging',
            'active_callback' => function () {
                return current_user_can('manage_options');
            },
            'sections' => array(
                'debug_settings' => array(
                    'title' => 'Debug Settings',
                    'options' => array(
                        'fxwp_dummy_debug' => array(
                            'type' => 'checkbox',
                            'title' => 'Dummy Debug Option',
                            'description' => 'Aktiviert die Debug-Option.',
                            'default' => false,
                        ),
// Action-Eintrag
                        'fxwp_run_test_action' => array(
                            'type' => 'action',
                            'title' => 'Test Aktion',
                            'description' => 'Führt eine Test-Aktion aus.',
                            'callback' => 'fxwp_run_test_action_callback'
                        ),
// Beispiel Alert-Eintrag
                        'fxwp_sample_alert' => array(
                            'type' => 'alert',
                            'title' => 'Sample Alert',
                            'description' => 'Dies ist ein Alert. Unterstützt Farbvarianten (z. B. danger, success, primary).',
                            'default' => 'Achtung! Dies ist eine Warnung.',
                            'alertType' => 'danger', // Mögliche Werte: primary, success, danger etc.
                            'icon' => 'dashicons dashicons-warning'
                        ),
// Beispiel Code-Eintrag (nur lesbar, mit Copy-Icon)
                        'fxwp_sample_code' => array(
                            'type' => 'code',
                            'title' => 'Sample Code',
                            'description' => 'Zeige Beispielcode an.',
                            'default' => "<?php echo 'Hello World!'; ?>",
                            'readonly' => true,
                            'icon' => 'dashicons dashicons-editor-code'
                        ),
// Beispiel JSON-Eintrag (nur lesbar, mit Copy-Icon)
                        'fxwp_sample_json' => array(
                            'type' => 'json',
                            'title' => 'Sample JSON',
                            'description' => 'JSON-Inhalt (nur lesbar).',
                            'default' => '{"foo": "bar", "baz": 123}',
                            'readonly' => true,
                            'icon' => 'dashicons dashicons-media-code'
                        ),
                    ),
                ),
            ),
        ),
    ),
);
