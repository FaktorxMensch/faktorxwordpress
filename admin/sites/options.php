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
    $fx_plugin_config = fxwp_get_options_config();
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
        wp_send_json_success($result);
    }
    wp_send_json_error(array('message' => 'Aktion fehlgeschlagen'));
}

add_action('wp_ajax_fx_plugin_execute_action', 'fx_plugin_execute_action');

/**
 * Konfigurationsarray für das Panel.
 * Hier werden auch Options (mit fxwp_-Präfix) und Action-Einträge definiert.
 * Zusätzlich wurden Beispieloptionen für die neuen Typen alert, code und json eingefügt.
 */

// options config in current dir under ../../includes
require_once dirname(__FILE__) . '/../../includes/options-config.php';
$fx_plugin_config = fxwp_get_options_config();

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
 * AJAX-Handler zum Abrufen der aktuellen Optionen.
 */
function fx_plugin_get_options()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Nicht berechtigt'));
    }

    // Laden Sie die Basis-Konfiguration
    $fx_plugin_config = fxwp_get_options_config();

    // Überschreiben Sie die Standardwerte mit den in der Datenbank gespeicherten Werten
    foreach ($fx_plugin_config['nav_pages'] as &$page) {
        foreach ($page['sections'] as &$section) {
            foreach ($section['options'] as $option_key => &$option) {
                $stored = get_option($option_key, '___not_set___');
                if ($stored !== '___not_set___') {
                    $option['default'] = $stored;
                }
                $option['value'] = $option['default'];
            }
        }
    }
    unset($page, $section, $option);

    wp_send_json_success($fx_plugin_config);
}

add_action('wp_ajax_fx_plugin_get_options', 'fx_plugin_get_options');


/**
 * Gibt die Konfiguration als JavaScript-Objekt im Head aus.
 */
function fx_plugin_localize_config()
{
    $fx_plugin_config = fxwp_get_options_config();
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
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <div id="fx-plugin-panel">
        <main class="wrap fx-content" v-for="(currentNav, key) in filteredNavPages">
            <h1 class="fx-header">{{ currentNav.title }}</h1>
            <!-- a search bar to search all options inside fxwp -->
            <input type="text" v-model="search" v-if="key==0"
                   autofocus
                   placeholder="Durchsuche alle Optionen in Faktor&times;WP ..." class="fx-search">
            <div class="fx-sections">
                <div v-for="(section, sIndex) in currentNav.sections" :key="sIndex"
                     :class="['fx-section-density-' + (section.density || 'normal')]"
                     class="postbox fx-section">
                    <h2 class="fx-section-header">{{ section.title }}</h2>
                    <div class="fx-options">
                        <div v-for="(option, key) in section.options" :key="key" class="fx-option">
                            <label :for="key" class="fx-option-label" v-if="option.title && option.type !== 'checkbox'">
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
                                    <option v-for="(choice, idx) in option.choices" :key="idx" :value="idx">
                                        {{ choice}}
                                    </option>
                                </select>
                            </template>
                            <!-- Checkbox (custom styled) -->
                            <template v-else-if="option.type === 'checkbox'">
                                <div class="custom-checkbox-inline">
                                    <input type="checkbox" :id="key" v-model="option.value"
                                           @change="saveOption(key, option.value)">
                                    <label class="custom-checkbox-box" :for="key"></label>
                                    <span class="custom-checkbox-label"> <i v-if="option.icon" :class="option.icon"></i> {{ option.title }} </span>
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
                                <button class="action-button" :disabled="loadingActions[key]"
                                        @click="executeAction(key)">
                                    <span v-if="loadingActions[key]"> <i
                                                class="dashicons dashicons-update dashicons-spinner"
                                                style="animation: spin 2s infinite linear;"></i>
                                    </span>
                                    {{ option.title }}
                                </button>
                            </template>
                            <!-- Alert -->
                            <template v-else-if="option.type === 'alert'">
                                <div :class="['fx-alert', 'alert-' + (option.color || 'secondary')]">
                                    <i v-if="option.alertIcon" :class="option.alertIcon"></i>
                                    {{ option.text }}
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

                            <p class="fx-option-description" v-if="option.description">{{ option.description }}</p>
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
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Grundlayout */
        #fx-plugin-panel {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
        }

        .fx-sections {
            display: flex;
            flex-direction: column;
            margin-top: 15px;
            gap: 15px;
        }

        .fx-section {
            padding: 15px;
        }

        .fx-section-density-dense {
            .fx-option-label {
                display: none;
            }
        }

        .fx-search {
            padding: 8px;
            margin-top: 10px;
            box-sizing: border-box;
            flex: 1;
            width: 100%;
        }

        .fx-section-header {
            margin-bottom: 10px;
            margin-top: 0;
            color: #2d2d2d;
        }

        .fx-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fx-option {
            display: flex;
            flex-direction: column;
        }

        .fx-option-label {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .fx-option input[type="text"],
        .fx-option input[type="number"],
        .fx-option select,
        .fx-json-editor {
            padding: 8px;
            font-size: 13px;
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
            font-size: 12px;
            color: #777;
            margin-top: 3px;
        }

        .postbox {
            padding-bottom: 5px;
        }

        /* Custom Checkbox */
        .custom-checkbox-inline {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            position: relative;
        }

        .custom-checkbox-inline input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
        }

        .custom-checkbox-box {
            width: 16px;
            height: 16px;
            background: #fff;
            border: 2px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, border-color 0.2s;
            position: relative;
        }

        .custom-checkbox-inline input[type="checkbox"]:checked + .custom-checkbox-box {
            background: #0073aa;
            border-color: #0073aa;
        }

        .custom-checkbox-inline input[type="checkbox"]:checked + .custom-checkbox-box:after {
            content: "\2713";
            position: absolute;
            display: block;
            color: #fff;
            font-size: 12px;
            text-align: center;
            width: 100%;
            margin-top: 4px;
            height: 100%;
        }

        .custom-checkbox-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 4px;
        }

        /* Custom Radio */
        .custom-radio {
            display: block;
        }

        .custom-radio-item {
            margin-bottom: 6px;
            position: relative;
            padding-left: 24px;
            cursor: pointer;
        }

        .custom-radio-item input[type="radio"] {
            opacity: 0;
            position: absolute;
            width: 18px;
            height: 18px;
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
            left: -24px;
            top: 0;
            width: 18px;
            height: 18px;
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
            padding: 8px 12px;
            background: #0073aa;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
            width: 25em; /* statt fester Breite */
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
            background: #0073aa;
            color: #fff;
            padding: 10px 16px;
            border-radius: 4px;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            margin-top: 8px;
        }

        .snackbar.error {
            background: #dc3545;
        }

        .snackbar.warning {
            background: #ffc107;
        }

        .snackbar.info {
            background: #1eb5d8;
        }

        .snackbar.success {
            background: #28a745;
        }

        .snackbar.secondary {
            background: #6c757d;
        }

        /* Alert Box */
        .fx-alert {
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
            position: relative;
            font-size: 13px;
        }

        .alert-primary {
            background: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
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

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .alert-secondary {
            border: 1px solid #d6d8dbaa;
            color: #383d41;
        }

        /* Code Block */
        .fx-code {
            background: #272822;
            color: #f8f8f2;
            padding: 8px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
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
            padding-right: 30px;
        }

        .fx-code-copy {
            position: absolute;
            top: 10px;
            right: 8px;
            font-size: 16px;
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
            padding-right: 30px;
        }

        .fx-json-copy {
            position: absolute;
            top: 4px;
            right: 8px;
            font-size: 16px;
            color: #0073aa;
            cursor: pointer;
        }
    </style>

    <!-- Einbinden von Vue.js und jQuery via CDN -->
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

        window.vueFxPanel = new Vue({
            el: '#fx-plugin-panel',
            data: {
                navPages: fxPluginConfig.nav_pages ? Object.values(fxPluginConfig.nav_pages) : [],
                currentNav: {title: '', sections: []},
                loadingActions: {},
                search: '',
                snackbars: [] // Für mehrere Snackbar-Meldungen
            },
            created: function () {
                this.refreshOptions();
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

            computed: {
                filteredNavPages: function () {
                    // Wenn kein Suchbegriff eingegeben wurde, gib nur die aktuell ausgewählte Seite zurück
                    if (!this.search) {
                        return [this.currentNav];
                    }
                    const searchTerm = this.search.toLowerCase();
                    const filteredPages = this.navPages
                        .map(page => {
                            // Erstelle eine flache Kopie der Seite
                            const newPage = Object.assign({}, page);
                            // Verwandle das sections-Objekt in ein Array, um map() nutzen zu können
                            newPage.sections = Object.values(page.sections)
                                .map(section => {
                                    const newOptions = {};
                                    Object.keys(section.options).forEach(key => {
                                        const option = section.options[key];
                                        const title = option.title ? option.title.toLowerCase() : '';
                                        const description = option.description ? option.description.toLowerCase() : '';
                                        // Wir ignorieren in beiden fällen alles ausser zahlen und buchstaben
                                        const regex = /[^a-z0-9]/g;
                                        // Treffer, wenn der Suchbegriff als Substring vorkommt
                                        // oder der Levenshtein-Abstand kleiner oder gleich 2 ist
                                        if (
                                            title.replace(regex, '').includes(searchTerm.replace(regex, '')) ||
                                            description.replace(regex, '').includes(searchTerm.replace(regex, '')) ||
                                            this.levenshtein(searchTerm, title) <= 2 ||
                                            this.levenshtein(searchTerm, description) <= 2
                                        ) {
                                            newOptions[key] = option;
                                        }
                                    });
                                    if (Object.keys(newOptions).length > 0) {
                                        const newSection = Object.assign({}, section);
                                        newSection.options = newOptions;
                                        return newSection;
                                    }
                                    return null;
                                })
                                .filter(section => section !== null);
                            return newPage.sections.length > 0 ? newPage : null;
                        })
                        .filter(page => page !== null);
                    console.log(filteredPages);
                    if (filteredPages.length === 0) {
                        return [{title: 'Keine Ergebnisse', sections: []}];
                    }
                    return filteredPages;
                }
            },

            methods: {
                levenshtein: function (a, b) {
                    if (a.length === 0) return b.length;
                    if (b.length === 0) return a.length;
                    const matrix = [];
                    // Initialisiere die erste Zeile und Spalte
                    for (let i = 0; i <= b.length; i++) {
                        matrix[i] = [i];
                    }
                    for (let j = 0; j <= a.length; j++) {
                        matrix[0][j] = j;
                    }
                    // Berechne die Matrix
                    for (let i = 1; i <= b.length; i++) {
                        for (let j = 1; j <= a.length; j++) {
                            if (b.charAt(i - 1) === a.charAt(j - 1)) {
                                matrix[i][j] = matrix[i - 1][j - 1];
                            } else {
                                matrix[i][j] = Math.min(
                                    matrix[i - 1][j - 1] + 1, // Substitution
                                    Math.min(
                                        matrix[i][j - 1] + 1,   // Insertion
                                        matrix[i - 1][j] + 1    // Deletion
                                    )
                                );
                            }
                        }
                    }
                    return matrix[b.length][a.length];
                },
                loadNavPage: function (nav) {
                    this.currentNav = nav;
                    var newUrl = updateQueryStringParameter(window.location.href, 'nav', nav.slug);
                    window.history.pushState({path: newUrl}, '', newUrl);
                    // Update auch das WP-Submenu (aktives Element hervorheben)
                    jQuery('.wp-submenu li').removeClass('current');
                    jQuery('.wp-submenu li a[href="admin.php?page=fxwp-options&nav=' + nav.slug + '"]')
                        .parent().addClass('current');

                    // clear search and focus again
                    this.search = '';
                    this.$nextTick(() => {
                        this.$el.querySelector('.fx-search').focus();
                    });
                },
                // Speichern bei onchange
                saveOption: function (key, value) {
                    console.log('saving option' + key + "=" + value)

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
                refreshOptions: function () {
                    $.post(ajaxurl, {
                        action: 'fx_plugin_get_options'
                    }, function (response) {
                        if (response.success) {
                            // Aktualisieren Sie das Konfigurationsobjekt in Vue
                            this.navPages = response.data.nav_pages ? Object.values(response.data.nav_pages) : [];

                            // Optional: Falls Sie die aktuell aktive Navigation beibehalten wollen:
                            const currentSlug = this.currentNav.slug;
                            const found = this.navPages.find(function (page) {
                                return page.slug === currentSlug;
                            });
                            if (found) {
                                this.currentNav = found;
                            } else {
                                this.currentNav = this.navPages[0];
                            }

                            this.showSnackbar("Optionen aktualisiert", "success");
                        } else {
                            this.showSnackbar(response.data.message, "error");
                        }
                    }.bind(this));
                },
                executeAction: function (key) {
                    this.$set(this.loadingActions, key, true);
                    $.post(ajaxurl, {
                        action: 'fx_plugin_execute_action',
                        action_key: key
                    }, function (response) {
                        this.$set(this.loadingActions, key, false);

                        if (response.success) {
                            if (response.data?.redirect) {
                                window.open(response.data.redirect, '_blank');
                                this.showSnackbar("Aktion ausgeführt, öffne in neuem Tab", 'success');
                            } else {
                                this.showSnackbar(response.data.message, 'success');
                                // Nach erfolgreicher Aktion die Optionen neu laden
                                this.refreshOptions();
                            }
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

        jQuery(document).on('click', '.wp-submenu a[href*="admin.php?page=fxwp-options"]', function (e) {
            e.preventDefault(); // Verhindert den Seitenreload
            // Extrahiere den 'nav'-Parameter aus der URL
            var url = new URL(jQuery(this).attr('href'), window.location.origin);
            var navSlug = url.searchParams.get('nav');

            if (navSlug && window.vueFxPanel) {
                // Finde in den Vue-Daten die entsprechende Navigation-Seite
                var navPage = window.vueFxPanel.navPages.find(function (page) {
                    return page.slug === navSlug;
                });
                if (navPage) {
                    window.vueFxPanel.loadNavPage(navPage);
                }
            } else if (window.vueFxPanel) {
                // Fallback: Falls kein 'nav'-Parameter vorhanden ist, lade die erste Seite
                window.vueFxPanel.loadNavPage(window.vueFxPanel.navPages[0]);
            }
        });

    </script>
    <?php
}
