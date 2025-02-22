<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX-Handler zum Speichern einer Option.
 */
function fx_plugin_save_option()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Nicht berechtigt'));
    }
    $option_key = sanitize_text_field($_POST['option_key']);
    $option_value = $_POST['option_value'];

    // Bei Checkboxen: Werte "true" und "false" in Boolean umwandeln
    if ($option_value === "true") {
        $option_value = true;
    } elseif ($option_value === "false") {
        $option_value = false;
    }

    $old_value = get_option($option_key, '___not_set___');
    $updated = update_option($option_key, $option_value);
    if ($updated || $old_value === $option_value) {
        wp_send_json_success(array('message' => 'Option gespeichert'));
    } else {
        wp_send_json_error(array('message' => 'Speichern fehlgeschlagen'));
    }
}

add_action('wp_ajax_fx_plugin_save_option', 'fx_plugin_save_option');

/**
 * AJAX-Handler zum Ausführen einer Action.
 */
function fx_plugin_execute_action()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Nicht berechtigt'));
    }
    $action_key = sanitize_text_field($_POST['action_key']);
    global $fx_plugin_config;
    $callback = '';
    foreach ($fx_plugin_config['nav_pages'] as $page) {
        foreach ($page['sections'] as $section) {
            if (isset($section['options'][$action_key]) && $section['options'][$action_key]['type'] === 'action') {
                $callback = $section['options'][$action_key]['callback'];
                break 2;
            }
        }
    }
    if ($callback && is_callable($callback)) {
        $result = call_user_func($callback);
        wp_send_json_success(array('message' => $result));
    }
    wp_send_json_error(array('message' => 'Aktion fehlgeschlagen'));
}

add_action('wp_ajax_fx_plugin_execute_action', 'fx_plugin_execute_action');

/**
 * Dummy Callback für eine Action.
 */
function fxwp_run_test_action_callback()
{
    return "Test-Aktion wurde erfolgreich ausgeführt!";
}

/**
 * Konfigurationsarray für das Panel.
 * Hier werden auch Options (mit fxwp_-Präfix) und Action-Einträge definiert.
 * Zusätzlich wurden Beispieloptionen für die neuen Typen alert, code und json eingefügt.
 */

// options config in current dir under ../../includes
require_once dirname(__FILE__) . '/../../includes/options-config.php';
global $fx_plugin_config;

// Vor dem Localize – für jede Option den gespeicherten Wert aus der DB laden,
// sofern vorhanden. Falls der Schlüssel "default" nicht gesetzt ist, wird er initialisiert.
foreach ($fx_plugin_config['nav_pages'] as $page_key => &$page) {
    foreach ($page['sections'] as $section_key => &$section) {
        foreach ($section['options'] as $option_key => &$option) {
            if (!isset($option['default'])) {
                $option['default'] = '';
            }
            $stored = get_option($option_key, '___not_set___');
            if ($stored !== '___not_set___') {
                $option['default'] = $stored;
            }
            $option['value'] = $option['default'];
        }
    }
}
unset($page, $section, $option);

/**
 * Gibt die Konfiguration als JavaScript-Objekt im Head aus.
 */
function fx_plugin_localize_config()
{
    global $fx_plugin_config;
    ?>
    <script>
        var fxPluginConfig = <?php echo json_encode($fx_plugin_config, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php
}

add_action('admin_head', 'fx_plugin_localize_config');

/**
 * Die Panel-Seite – aufgerufen via fxwp_panel_page() in der Admin-Navigation.
 */
function fxwp_options_page()
{
    ?>
    <div id="fx-plugin-panel">
        <aside class="fx-sidebar">
            <h2 class="title">Optionen</h2>
            <ul>
                <li v-for="(nav, index) in navPages" :key="index" :class="{ active: nav === currentNav }">
                    <a href="#" @click.prevent="loadNavPage(nav)">
                        <i v-if="nav.icon" :class="nav.icon"></i>
                        {{ nav.title }}</a>
                </li>
            </ul>
        </aside>
        <main class="fx-content">
            <h1 class="fx-header">{{ currentNav.title }}</h1>
            <div class="fx-sections">
                <div v-for="(section, sIndex) in currentNav.sections" :key="sIndex" class="fx-section">
                    <h2 class="fx-section-header">{{ section.title }}</h2>
                    <div class="fx-options">
                        <div v-for="(option, key) in section.options" :key="key" class="fx-option">
                            <label :for="key" class="fx-option-label">
                                <!-- Zeige Dashicon, falls definiert -->
                                <i v-if="option.icon" :class="option.icon"></i>
                                {{ option.title }}
                            </label>

                            <!-- Text -->
                            <template v-if="option.type === 'text'">
                                <input type="text" :id="key" v-model="option.value"
                                       @change="saveOption(key, option.value)">
                            </template>
                            <!-- Number -->
                            <template v-else-if="option.type === 'number'">
                                <input type="number" :id="key" v-model.number="option.value"
                                       @change="saveOption(key, option.value)">
                            </template>
                            <!-- Select -->
                            <template v-else-if="option.type === 'select'">
                                <select :id="key" v-model="option.value" @change="saveOption(key, option.value)">
                                    <option v-for="(choice, idx) in option.choices" :key="idx" :value="choice.value">
                                        {{ choice.label }}
                                    </option>
                                </select>
                            </template>
                            <!-- Checkbox (custom styled) -->
                            <template v-else-if="option.type === 'checkbox'">
                                <div class="custom-checkbox">
                                    <input type="checkbox" :id="key" v-model="option.value"
                                           @change="saveOption(key, option.value)">
                                    <label :for="key"></label>
                                </div>
                            </template>
                            <!-- Radio -->
                            <template v-else-if="option.type === 'radio'">
                                <div class="custom-radio">
                                    <div v-for="(choice, idx) in option.choices" :key="idx" class="custom-radio-item">
                                        <input type="radio" :id="key + '_' + idx" :name="key" :value="choice.value"
                                               v-model="option.value" @change="saveOption(key, option.value)">
                                        <label :for="key + '_' + idx">{{ choice.label }}</label>
                                    </div>
                                </div>
                            </template>
                            <!-- Filesize -->
                            <template v-else-if="option.type === 'filesize'">
                                <input type="text" :id="key" :value="formatFilesize(option.value)"
                                       @change="updateFilesize(key, $event.target.value)">
                            </template>
                            <!-- Action -->
                            <template v-else-if="option.type === 'action'">
                                <button class="action-button" @click="executeAction(key)">{{ option.title }}</button>
                            </template>
                            <!-- Alert -->
                            <template v-else-if="option.type === 'alert'">
                                <div :class="['fx-alert', 'alert-' + (option.alertType || 'primary')]">
                                    <i v-if="option.icon" :class="option.icon"></i>
                                    {{ option.value }}
                                </div>
                            </template>
                            <!-- Code (nur lesbar, mit Copy-Icon in der Ecke) -->
                            <template v-else-if="option.type === 'code'">
                                <div class="fx-code-container">
                                    <pre class="fx-code"><code>{{ option.value }}</code></pre>
                                    <i class="dashicons dashicons-editor-code fx-code-copy"
                                       @click="copyToClipboard(option.value)"></i>
                                </div>
                            </template>
                            <!-- JSON (nur lesbar, mit Copy-Icon in der Ecke) -->
                            <template v-else-if="option.type === 'json'">
                                <div class="fx-json-container">
                                    <textarea :id="key" class="fx-json-editor" v-model="option.value"
                                              readonly></textarea>
                                    <i class="dashicons dashicons-admin-page fx-json-copy"
                                       @click="copyToClipboard(option.value)"></i>
                                </div>
                            </template>

                            <p class="fx-option-description">{{ option.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- Multiple Snackbars -->
        <div class="snackbars">
            <div v-for="(sb, index) in snackbars" :key="index" class="snackbar" :class="sb.type">
                {{ sb.message }}
            </div>
        </div>
    </div>

    <style>
        /* Grundlayout */
        #fx-plugin-panel {
            display: flex;
            height: 100%;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
        }

        /* Sidebar */
        .fx-sidebar {
            width: 220px;
            background: #1D2327;
            padding: 10px;
            margin-top: 20px;
            border-radius: 1em;
            box-sizing: border-box;
        }

        .fx-sidebar .title {
            color: #fff;
            margin-top: 15px;
            margin-left: 1em;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .fx-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .fx-sidebar li {
            margin-bottom: 12px;
        }

        .fx-sidebar a {
            display: flex;
            padding: 12px 12px;
            text-decoration: none;
            color: #fff;
            font-size: 16px;
            border-radius: 4px;
            transition: background 0.2s;
            align-items: center;
            gap: .5em;
        }


        .fx-sidebar .active a {
            background: #2271B1;
        }

        .fx-sidebar li:not(.active):hover a,
        .fx-sidebar li:not(.active):focus a {
            color: #72AEE6;
        }

        /* Content */
        .fx-content {
            flex: 1;
            padding: 20px;
            margin: 20px;
            background: #f7f7f7;
            border: 1px solid #e1e1e1;
            box-sizing: border-box;
            border-radius: 1em;
            overflow-y: auto;
        }

        .fx-header {
            font-size: 28px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e1e1e1;
            padding-bottom: 10px;
        }

        .fx-sections {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .fx-section {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #e1e1e1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .fx-section-header {
            font-size: 22px;
            margin-bottom: 15px;
            color: #2d2d2d;
        }

        .fx-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .fx-option {
            display: flex;
            flex-direction: column;
        }

        .fx-option-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .fx-option input[type="text"],
        .fx-option input[type="number"],
        .fx-option select,
        .fx-json-editor {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .fx-option input[type="text"]:focus,
        .fx-option input[type="number"]:focus,
        .fx-option select:focus,
        .fx-json-editor:focus {
            border-color: #0073aa;
            outline: none;
        }

        .fx-option-description {
            font-size: 13px;
            color: #777;
            margin-top: 5px;
        }

        /* Custom Checkbox */
        .custom-checkbox {
            position: relative;
            width: 24px;
            height: 24px;
        }

        .custom-checkbox input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 24px;
            height: 24px;
            margin: 0;
            cursor: pointer;
        }

        .custom-checkbox label {
            position: absolute;
            top: 0;
            left: 0;
            width: 24px;
            height: 24px;
            background: #fff;
            border: 2px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .custom-checkbox input[type="checkbox"]:checked + label {
            background: #0073aa;
            border-color: #0073aa;
        }

        .custom-checkbox input[type="checkbox"]:checked + label:after {
            content: "\2713";
            position: absolute;
            top: 0;
            left: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
        }

        /* Custom Radio */
        .custom-radio {
            display: block;
        }

        .custom-radio-item {
            margin-bottom: 8px;
            position: relative;
            padding-left: 28px;
            cursor: pointer;
        }

        .custom-radio-item input[type="radio"] {
            opacity: 0;
            position: absolute;
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
        }

        .custom-radio-item label {
            position: relative;
            cursor: pointer;
        }

        .custom-radio-item label:before {
            content: "";
            position: absolute;
            left: -28px;
            top: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            box-sizing: border-box;
        }

        .custom-radio-item input[type="radio"]:checked + label:before {
            background: #0073aa;
            border-color: #0073aa;
        }

        /* Action-Button */
        .action-button {
            padding: 10px 16px;
            background: #0073aa;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .action-button:hover,
        .action-button:focus {
            background: #005880;
        }

        /* Snackbars */
        .snackbars {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .snackbar {
            background: #28a745;
            color: #fff;
            padding: 14px 24px;
            border-radius: 4px;
            font-size: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }

        .snackbar.error {
            background: #dc3545;
        }

        /* Alert Box */
        .fx-alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 10px;
            position: relative;
            font-size: 14px;
        }

        .alert-primary {
            background: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Code Block */
        .fx-code {
            background: #272822;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 13px;
        }

        /* Container für Code-Snippet mit Copy-Icon */
        .fx-code-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .fx-code-container .fx-code {
            width: 100%;
            box-sizing: border-box;
            padding-right: 30px; /* Platz für das Icon */
        }

        .fx-code-copy {
            position: absolute;
            top: 20px;
            right: 8px;
            font-size: 18px;
            color: #fff;
            cursor: pointer;
        }

        /* JSON-Container */
        .fx-json-container {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .fx-json-editor {
            width: 100%;
            box-sizing: border-box;
            padding-right: 30px; /* Platz für das Icon */
        }

        .fx-json-copy {
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 18px;
            color: #0073aa;
            cursor: pointer;
        }
    </style>

    <!-- Einbinden von Vue.js und jQuery via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Hilfsfunktion: Aktualisiert oder fügt einen URL-Parameter hinzu.
        function updateQueryStringParameter(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
                return uri + separator + key + "=" + value;
            }
        }

        // Funktion zur Konvertierung von Dateigrößen-Eingaben
        function parseFilesize(input) {
            input = input.trim().toUpperCase();
            input = input.replace(/\s/g, '');
            input = input.replace(/,/g, '.');
            var multiplier = 1;
            if (input.endsWith("GB")) {
                multiplier = 1073741824;
                input = input.replace("GB", "");
            } else if (input.endsWith("MB")) {
                multiplier = 1048576;
                input = input.replace("MB", "");
            } else if (input.endsWith("KB")) {
                multiplier = 1024;
                input = input.replace("KB", "");
            }
            var numericValue = parseFloat(input);
            if (isNaN(numericValue)) {
                return 0;
            }
            return numericValue * multiplier;
        }

        // Formatierung von Bytes in GB (als String)
        function formatFilesize(bytes) {
            if (!bytes || isNaN(bytes)) return "";
            return (bytes / 1073741824).toFixed(2) + " GB";
        }

        jQuery(document).ready(function ($) {
            new Vue({
                el: '#fx-plugin-panel',
                data: {
                    navPages: fxPluginConfig.nav_pages ? Object.values(fxPluginConfig.nav_pages) : [],
                    currentNav: {title: '', sections: []},
                    snackbars: [] // Für mehrere Snackbar-Meldungen
                },
                created: function () {
                    var params = new URLSearchParams(window.location.search);
                    var navSlug = params.get('nav');
                    if (navSlug) {
                        var found = this.navPages.find(function (page) {
                            return page.slug === navSlug;
                        });
                        if (found) {
                            this.loadNavPage(found);
                        } else {
                            this.loadNavPage(this.navPages[0]);
                        }
                    } else {
                        this.loadNavPage(this.navPages[0]);
                    }
                },
                methods: {
                    loadNavPage: function (nav) {
                        this.currentNav = nav;
                        var newUrl = updateQueryStringParameter(window.location.href, 'nav', nav.slug);
                        window.history.pushState({path: newUrl}, '', newUrl);
                    },
                    // Speichern bei onchange
                    saveOption: function (key, value) {
                        let option = this.findOption(key);
                        if (option && option.type === 'filesize') {
                            if (!/^\d+$/.test(value)) {
                                value = parseFilesize(value);
                            }
                        }
                        $.post(ajaxurl, {
                            action: 'fx_plugin_save_option',
                            option_key: key,
                            option_value: value
                        }, function (response) {
                            if (response.success) {
                                this.showSnackbar(response.data.message, 'success');
                            } else {
                                this.showSnackbar(response.data.message, 'error');
                            }
                        }.bind(this));
                    },
                    // Für Filesize: Umrechnung und speichern
                    updateFilesize: function (key, value) {
                        var newBytes = parseFilesize(value);
                        let option = this.findOption(key);
                        if (option) {
                            option.value = newBytes;
                            this.saveOption(key, newBytes);
                        }
                    },
                    findOption: function (key) {
                        let found = null;
                        if (this.currentNav.sections) {
                            Object.keys(this.currentNav.sections).forEach(function (sectionKey) {
                                let section = this.currentNav.sections[sectionKey];
                                if (section.options && section.options[key]) {
                                    found = section.options[key];
                                }
                            }.bind(this));
                        }
                        return found;
                    },
                    executeAction: function (key) {
                        $.post(ajaxurl, {
                            action: 'fx_plugin_execute_action',
                            action_key: key
                        }, function (response) {
                            if (response.success) {
                                this.showSnackbar(response.data.message, 'success');
                            } else {
                                this.showSnackbar(response.data.message, 'error');
                            }
                        }.bind(this));
                    },
                    showSnackbar: function (message, type) {
                        this.snackbars.push({message: message, type: type});
                        setTimeout(function () {
                            this.snackbars.shift();
                        }.bind(this), 3000);
                    },
                    // Wrapper für die Formatierung von Filesize
                    formatFilesize: function (bytes) {
                        return formatFilesize(bytes);
                    },
                    copyToClipboard: function (text) {
                        var textarea = document.createElement("textarea");
                        textarea.value = text;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand("copy");
                        document.body.removeChild(textarea);
                        this.showSnackbar("Inhalt kopiert", "success");
                    }
                }
            });
        });
    </script>
    <?php
}
