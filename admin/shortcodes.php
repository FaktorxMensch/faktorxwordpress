<?php
// Den Inhalt der Einstellungsseite anzeigen:
function fxwp_display_settings_page()
{
    // Holen Sie sich die gespeicherten Shortcodes
    $fxwp_shortcodes = get_option('fxwp_shortcodes', array());
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?>
            <a href="<?php echo admin_url('admin.php?page=my-custom-shortcodes-add-new'); ?>" class="page-title-action">Neu
                hinzufügen</a>
        </h1>
        <br>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col">Shortcode-Tag</th>
                <th scope="col">Attribute</th>
                <th scope="col">Beschreibung</th>
                <!--                <th scope="col">Aktionen</th>-->
            </tr>
            </thead>
            <tbody>
            <?php
            // Überprüfen Sie, ob Shortcodes vorhanden sind
            if (!empty($fxwp_shortcodes)) {
                // Durchlaufen Sie jeden Shortcode und erstellen Sie eine Tabellenzeile
                foreach ($fxwp_shortcodes as $shortcode_data) {
                    $shortcode_tag = $shortcode_data['tag'];
                    echo '<tr>';
                    echo '<td>[<strong>' . esc_html($shortcode_tag) . '</strong>]</td>';
                    echo '<td>' . esc_html(implode(', ', array_map(function ($attribute) {
                            return $attribute['name'] . '=' . $attribute['default'];
                        }, $shortcode_data['attributes']))) . '</td>';
                    echo '<td>' . ($shortcode_data['description']) . '</td>';
//                    echo '<td>'; echo '<a href="' . admin_url('admin.php?page=fxwp-shortcode-edit&tag=' . urlencode($shortcode_tag)) . '">Bearbeiten</a>'; echo '</td>';
                    echo '</tr>';
                }
            } else {
                // Zeige eine Nachricht an, wenn keine Shortcodes vorhanden sind
                echo '<tr><td colspan="2">Keine Shortcodes gefunden.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Den Inhalt der Seite "Neu hinzufügen" anzeigen:
function fxwp_display_add_new_page()
{
    // Lade vorhandene Shortcodes
    $fxwp_shortcodes = get_option('fxwp_shortcodes', array());

    // Anlegen eines neuen Shortcodes
    if (isset($_POST['fxwp_shortcode_tag'])) {

        // show notice
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Shortcode wurde erfolgreich hinzugefügt.</p>
            </div>
            <?php
        });

        echo '<p>Shortcode wurde erfolgreich hinzugefügt.</p>';

        $new_shortcode = array(
            'tag' => sanitize_text_field($_POST['fxwp_shortcode_tag']),
            'attributes' => array(),
            'description' => sanitize_textarea_field($_POST['fxwp_shortcode_description']),
            // allow php code
            'code' => ($_POST['fxwp_shortcode_code']),
        );

        // Attribute hinzufügen
        if (isset($_POST['fxwp_shortcode_attributes'])) {
            foreach ($_POST['fxwp_shortcode_attributes'] as $attribute) {
                $new_shortcode['attributes'][] = array(
                    'name' => sanitize_text_field($attribute['name']),
                    'description' => sanitize_textarea_field($attribute['description']),
                    'default' => sanitize_text_field($attribute['default']),
                );
            }
        }

        // Den neuen Shortcode zum Array hinzufügen
        $fxwp_shortcodes[] = $new_shortcode;

        // Update the option
        update_option('fxwp_shortcodes', $fxwp_shortcodes);
    }

    // Das Formular anzeigen
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post">
            <?php
            wp_nonce_field('fxwp_shortcode_nonce', 'fxwp_add_shortcode_nonce');
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="fxwp_shortcode_tag">Shortcode Tag</label>
                    </th>
                    <td>
                        <input required name="fxwp_shortcode_tag" id="fxwp_shortcode_tag" type="text"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label>Attribute</label>
                    </th>
                    <td>
                        <div id="fxwp_shortcode_attributes">
                            <!-- Hier werden die dynamischen Felder eingefügt -->
                        </div>
                        <button type="button" id="fxwp_add_attribute">Attribut hinzufügen</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fxwp_shortcode_description">Beschreibung</label>
                    </th>
                    <td>
                        <textarea name="fxwp_shortcode_description" id="fxwp_shortcode_description"
                                  class="large-text code" rows="10"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fxwp_shortcode_code">PHP Code</label>
                    </th>
                    <td>
                        <textarea name="fxwp_shortcode_code" id="fxwp_shortcode_code" class="large-text code"
                                  rows="10"></textarea>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
            // Submit-Button ausgeben
            submit_button('Neuer Shortcode');
            ?>
        </form>
        <script>document.getElementById('fxwp_add_attribute').addEventListener('click', function () {
                var attributeDiv = document.createElement('div');
                attributeDiv.className = 'fxwp_attribute';

                var nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.name = 'fxwp_shortcode_attributes[' + Date.now() + '][name]';
                nameInput.placeholder = 'Attributname';
                attributeDiv.appendChild(nameInput);

                var descInput = document.createElement('input');
                descInput.type = 'text';
                descInput.name = 'fxwp_shortcode_attributes[' + Date.now() + '][description]';
                descInput.placeholder = 'Beschreibung';
                attributeDiv.appendChild(descInput);

                var defaultInput = document.createElement('input');
                defaultInput.type = 'text';
                defaultInput.name = 'fxwp_shortcode_attributes[' + Date.now() + '][default]';
                defaultInput.placeholder = 'Standardwert';
                attributeDiv.appendChild(defaultInput);

                document.getElementById('fxwp_shortcode_attributes').appendChild(attributeDiv);
            });
        </script>
    </div>
    <?php
}

// Den Inhalt der Dokumentationsseite anzeigen:
function fxwp_display_doc_page()
{
    // Überprüfen, ob dem Benutzer der Zugriff erlaubt ist
    if (!current_user_can('manage_options')) return;

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <!-- TODO: Shortcode-Dokumentation hier anzeigen -->

    </div>
    <?php
    $shortcodes = get_option('fxwp_shortcodes', []);

    foreach ($shortcodes as $shortcode) {
        echo '<div class="card fxwp_shortcode">';
        echo '<h1>[' . esc_html($shortcode['tag']) . ']</h1>';
        echo '<p>' . esc_html($shortcode['description']) . '</p>';
        echo '<h3>Attribute:</h3>';

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Standardwert</th><th>Beschreibung</th><th>Beispiel</th></tr></thead>';

        foreach ($shortcode['attributes'] as $attribute) {
            echo '<tr>';
            echo '<th scope="row">' . esc_html($attribute['name']) . '</th>';
            echo '<td>' . esc_html($attribute['default']) . '</td>';
            echo '<td>' . esc_html($attribute['description']) . '</td>';
            echo '<td>[' . esc_html($shortcode['tag']) . ' ' . esc_html($attribute['name']) . '="' . esc_html($attribute['default']) . '"]</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '<h3>PHP Code:</h3>';
        echo '<pre><code>' . str_replace('<', '&lt;', str_replace('>', '&gt;', ($shortcode['code']))) . '</code></pre>';
        echo '</div>';
    }
}
