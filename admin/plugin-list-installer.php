<?php
function fxwp_plugin_list_installer_page()
{
    // Definieren Sie Ihre Plugin-Sammlungen
    $plugin_collections = array(
        'Arbeiten mit Beiträgen' => array(
            array(
                'name' => 'pods',
                'options' => array(
                    'option1' => 'value1',
                    'option2' => 'value2',
                    // weitere Optionen hier
                ),
            ),
//            array(
//                'name' => 'post-types-order',
//                'options' => array(),
//            ),
        ),
        'Website-Verbesserung' => array(
            array(
                'name' => 'broken-link-checker',
                'options' => array(
                    'option1' => 'value1',
                    'option2' => 'value2',
                    // weitere Optionen hier
                ),
            ),
            array(
                'name' => 'wp-super-cache',
                'options' => array(),
            ),
        )
        // Fügen Sie nach Bedarf weitere Sammlungen hinzu
    );

    echo '<div class="wrap">';
    ?>
    <label class="switch fixed-upper-right">
        <input type="checkbox" id="togBtn">
        <div class="slider round">
            <span class="on">Erweitert</span>
            <span class="off">Einfach</span>
        </div>
    </label>
    <?php

    echo '<div style="display:none" class="advanced-options">';
    echo '<h1>Plugin-Listen-Installer</h1>';

    foreach ($plugin_collections as $collection_name => $plugins) {
        echo "<div class='collection-box'>";
        echo "<h2>{$collection_name}</h2>";
        echo "<ul class='plugin-list'>";
        foreach ($plugins as $plugin) {
            $plugin = $plugin['name'];
            echo "<li>
                <img src='https://ps.w.org/{$plugin}/assets/icon-128x128.png'>
                <div id='plugin-{$plugin}' >
                    <h3>" . str_replace('-', ' ', ucfirst($plugin)) . "</h3>
                    <p><strong>Autor:</strong></p> <p><strong>Downloads:</strong></p> <p><strong>Bewertungen:</strong></p>
                </div>
            </li>";
        }
        echo "</ul>";
        echo '<div style="display:flex;gap:8px;><form method="post" action="">';
        echo '<form method="post">';
        echo "<input type='hidden' name='plugin_collection' value='{$collection_name}'/>";
        echo '<input type="submit" value="Sammlung installieren" class="button button-primary button-large"/>';
        echo '</form>';
        echo '<form method="post">';
        echo "<input type='hidden' name='plugin_collection' value='{$collection_name}'/>";
        echo '<input type="submit" name="configure_plugins" value="Plugins konfigurieren" class="button button-secondary button-large"/>
        </form>
        </div>';
        echo "</div>";
    }

    // THEMES
    echo '<h1 style="margin-top:25px">Theme-Installer</h1>';
    echo '<div class="collection-box">';
    echo '<h2>Benutzerdefinierte Themes</h2>';
    echo '<ul class="plugin-list"><li>
        <img src="https://faktorxmensch.com/wp-content/uploads/2023/01/cropped-logo_quibic.png">
        <div>
            <h3>Faktor&times;WordPress Theme</h3>
            <p><strong>Autor:</strong> Faktor Mensch MEDIA UG (haftungsbeschränkt)</p> <p><strong>Downloads:</strong> 426</p> <p><strong>Bewertungen:</strong> 53</p>
        </div>
    </li></ul>';
    // have a form with post that sets POST fxwp_install_theme to true
    echo '<form method="post">';
    echo '<input type="hidden" name="fxwp_install_theme" value="true"/>';
    echo '<input type="submit" value="Theme installieren" class="button button-primary button-large"/>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    // EINRICHTUNG
    echo '<h1 style="margin-top:25px">Seiten Einrichtung</h1>';
    echo '<div class="collection-box">';
    echo '<h2>Einrichtung</h2>';
    echo '<form method="post">';
    echo '<ul class="checkbox-list">';
    $options = array(

        // Plugin Paket Standard installieren
        'fxwp_install_plugin_package' => 'Plugin Paket Standard installieren',
        // Faktor&times;WordPress Theme installieren
        'fxwp_install_theme' => 'Faktor&times;WordPress Theme installieren',

        // Startseite erstellen
        'fxwp_create_homepage' => 'Startseite erstellen',
        // Top und Footer Menüs erstellen
        'fxwp_create_menus' => 'Top und Footer Menüs erstellen',
        // Datenschutzerklärung erstellen
        'fxwp_create_privacy_policy' => 'Datenschutzerklärung erstellen',
        // Impressum erstellen
        'fxwp_create_imprint' => 'Impressum erstellen',
        // Cookie Hinweis einrichten
        'fxwp_create_cookie_notice' => 'Cookie Hinweis erstellen',
        // Kontaktseite mit Formular erstellen
        'fxwp_create_contact_page' => 'Kontaktseite mit Formular erstellen (Contact Form 7)',
        // AGB und Shop-Seite erstellen
        'fxwp_create_shop_pages' => 'AGB und Shop-Seite erstellen',
    );
    foreach ($options as $option => $label) {
        echo "<li><input checked value='true' type='checkbox' name='{$option}' id='{$option}'/><label for='{$option}'>{$label}</label></li>";
    }
    echo '</ul>';
    // have a form with post that sets POST fxwp_install_theme to true
    echo '<input type="hidden" name="fxwp_site_setup" value="true"/>';
    echo '<input type="submit" value="Seite einrichten" class="button button-primary button-large"/>';
    echo '</form>';
    echo '</div>';

    echo '</div>';

    ?>
    <script>
        var checkbox = document.getElementById('togBtn');
        var advancedOptions = document.querySelectorAll('.advanced-options');

        checkbox.addEventListener('change', function () {
            for (var i = 0; i < advancedOptions.length; i++) {
                advancedOptions[i].style.display = this.checked ? "block" : "none";
            }
        });
    </script>
    <?php
    // Überprüfen, ob die Plugins konfiguriert werden sollen
    if (isset($_POST['configure_plugins'])) {
        $selected_collection = $_POST['plugin_collection'];

        foreach ($plugin_collections[$selected_collection] as $plugin_data) {
            $plugin = $plugin_data['name'];
            // Aktualisieren der Plugin-Optionen
            foreach ($plugin_data['options'] as $option => $value) {
                update_option($option, $value);
            }
            echo "<p>{$plugin} erfolgreich konfiguriert.</p>";
        }
    } else if (isset($_POST['plugin_collection'])) {
        $selected_collection = $_POST['plugin_collection'];

        // Installieren und aktivieren Sie die Plugins in der ausgewählten Sammlung
        foreach ($plugin_collections[$selected_collection] as $plugin_data) {
            $plugin = $plugin_data['name'];
            $plugin_source = "https://downloads.wordpress.org/plugin/{$plugin}.zip";

            // Notwendige WordPress-Dateien einbeziehen
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $upgrader = new Plugin_Upgrader();
            $installed = $upgrader->install($plugin_source);

            if (!is_wp_error($installed) && $installed) {

                $result = null;
                $plugin_files = glob('/path/to/your/plugins/*.php');
                foreach ($plugin_files as $plugin_file) {
                    $plugin_data = get_plugin_data($plugin_file);

                    if (!empty($plugin_data['Name'])) {
                        // Use any unique part of the plugin path.
                        // 'plugin-name/plugin-name.php' for example.
                        $plugin_slug = plugin_basename($plugin_file);
                        $result = activate_plugin($plugin_slug);
                    }
                }

                if (is_null($result)) {

                    $update_options_count = 0;
                    // Aktualisieren der Plugin-Optionen
                    foreach ($plugin_data['options'] as $option => $value) {
                        update_option($option, $value);
                        $update_options_count++;
                    }

                    echo "<p>" . esc_html($plugin_data['Name']) . " erfolgreich installiert und aktiviert. Es wurden {$update_options_count} Optionen aktualisiert.</p>";


                } else {
                    echo "<p>Aktivierung von {$plugin} fehlgeschlagen.</p>";
                    echo '<meta http-equiv="refresh" content="1;url=' . admin_url('plugins.php') . '">';
                }
            } else {
                echo "<p>Installation von {$plugin} fehlgeschlagen.</p>";
            }
        }

    }

    // sometimes we wnat to install the fxwp theme
    if (isset($_POST['fxwp_install_theme'])) {
        // check the nonce
        fxwp_install_theme();
    }

    // Include JavaScript to fetch plugin details after page load
    echo "<script>
    var pluginCollections = " . json_encode($plugin_collections) . ";
    var pluginDetails = {};

    function fetchPluginDetails(plugin) {
        plugin = plugin.name;
        var apiURL = 'https://api.wordpress.org/plugins/info/1.0/' + plugin + '.json';

        fetch(apiURL)
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                pluginDetails[plugin] = data;
                updatePluginDetails(plugin);
            })
            .catch(function(error) {
                console.log('Error fetching plugin details:', error);
            });
    }

    function updatePluginDetails(plugin) {
        var pluginData = pluginDetails[plugin];

        if (pluginData) {
            var pluginElement = document.getElementById('plugin-' + plugin);
            pluginElement.innerHTML = '<h3>' + pluginData.name + ' (Version: ' + pluginData.version + ')</h3>' +
                '<p><strong>Autor:</strong> ' + pluginData.author + '</p>' +
                '<p><strong>Downloads:</strong> ' + pluginData.downloaded + '</p>' +
                '<p><strong>Bewertungen:</strong> ' + pluginData.rating + '</p>';

            pluginElement.classList.remove('loading');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        for (var collectionName in pluginCollections) {
            if (pluginCollections.hasOwnProperty(collectionName)) {
                var plugins = pluginCollections[collectionName];

                for (var i = 0; i < plugins.length; i++) {
                    var plugin = plugins[i];
                    fetchPluginDetails(plugin);
                }
            }
        }
    });
    </script>";

}
