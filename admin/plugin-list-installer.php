<?php
function fxwp_plugin_list_installer_page()
{
    // Definieren Sie Ihre Plugin-Sammlungen
    $plugin_collections = array(
        'Arbeiten mit Beiträgen' => array(
            'pods',
            'post-types-order'
        ),
        'Website-Verbesserung' => array(
            'broken-link-checker',
            'wp-super-cache'
        )
        // Fügen Sie nach Bedarf weitere Sammlungen hinzu
    );

    echo '<div class="wrap">';
    echo '<h1>Plugin-Listen-Installer</h1>';

    foreach ($plugin_collections as $collection_name => $plugins) {
        echo "<div class='collection-box'>";
        echo "<h2>{$collection_name}</h2>";
        echo "<ul class='plugin-list'>";
        foreach ($plugins as $plugin) {
            echo "<li>
                <img src='https://ps.w.org/{$plugin}/assets/icon-128x128.png'>
                <div id='plugin-{$plugin}' >
                    <h3>".str_replace('-', ' ', ucfirst($plugin))."</h3>
                    <p><strong>Autor:</strong></p> <p><strong>Downloads:</strong></p> <p><strong>Bewertungen:</strong></p>
                </div>
            </li>";
        }
        echo "</ul>";
        echo '<form method="post" action="">';
        echo "<input type='hidden' name='plugin_collection' value='{$collection_name}'/>";
        echo '<input type="submit" value="Sammlung installieren" class="button button-primary button-large"/>';
        echo '</form>';

        echo "</div>";
    }

    echo '</div>';

    // Überprüfen, ob eine Sammlung ausgewählt wurde
    if (isset($_POST['plugin_collection'])) {
        $selected_collection = $_POST['plugin_collection'];

        // Installieren und aktivieren Sie die Plugins in der ausgewählten Sammlung
        foreach ($plugin_collections[$selected_collection] as $plugin) {
            // Geben Sie die Quelle des Plugins an
            $plugin_source = "https://downloads.wordpress.org/plugin/{$plugin}.zip";  // Aktualisieren Sie diese URL

            // Notwendige WordPress-Dateien einbeziehen
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            // Verwenden Sie die Plugin_Upgrader-Klasse von WordPress, um das Plugin zu installieren
            $upgrader = new Plugin_Upgrader();
            $installed = $upgrader->install($plugin_source);

            // Überprüfen Sie, ob die Installation erfolgreich war
            if (!is_wp_error($installed) && $installed) {
                // Aktivieren Sie das Plugin
                $result = activate_plugin($plugin);

                // Überprüfen Sie, ob die Aktivierung erfolgreich war
                if (is_null($result)) {
                    echo "<p>{$plugin} erfolgreich installiert und aktiviert.</p>";
                } else {
                    echo "<p>Aktivierung von {$plugin} fehlgeschlagen.</p>";
                    // Führen Sie eine Meta-Aktualisierung zu plugins.php durch
                    echo '<meta http-equiv="refresh" content="1;url=' . admin_url('plugins.php') . '">';
                }
            } else {
                echo "<p>Installation von {$plugin} fehlgeschlagen.</p>";
            }
        }
    }
    // Include JavaScript to fetch plugin details after page load
    echo "<script>
    var pluginCollections = " . json_encode($plugin_collections) . ";
    var pluginDetails = {};

    function fetchPluginDetails(plugin) {
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
