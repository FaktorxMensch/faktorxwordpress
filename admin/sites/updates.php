<?php
require_once plugin_dir_path(__FILE__) . '../../includes/helpers.php';

// Deaktiviert Updates, wenn diese noch aktiviert sind
if (fxwp_check_deactivated_features('fxwp_deact_autoupdates')) {
    fxwp_disable_automatic_updates();
}

function fxwp_enable_automatic_updates()
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (fxwp_check_deactivated_features('fxwp_deact_autoupdates')) {
        fxwp_disable_automatic_updates();
        return;
    }

    update_option('fxwp_automatic_updates', true);
    remove_filter('automatic_updater_disabled', '__return_true');
    add_filter('auto_update_core', '__return_true');
    add_filter('auto_update_plugin', '__return_true');
    add_filter('auto_update_theme', '__return_true');

    // Alle Plugins abrufen und automatische Updates aktivieren
    $plugins = array_keys(get_plugins());
    update_option('auto_update_plugins', $plugins);

    // Alle Themes abrufen und automatische Updates aktivieren
    $themes = array_keys(wp_get_themes());
    update_option('auto_update_themes', $themes);
}

add_action('upgrader_process_complete', 'fxwp_enable_automatic_updates', 10, 2);

function fxwp_disable_automatic_updates()
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    update_option('fxwp_automatic_updates', false);
    add_filter('automatic_updater_disabled', '__return_true');
    remove_filter('auto_update_core', '__return_true');
    remove_filter('auto_update_plugin', '__return_true');
    remove_filter('auto_update_theme', '__return_true');

    $plugins = array_keys(get_plugins());
    update_option('auto_update_plugins', array());

    $themes = array_keys(wp_get_themes());
    update_option('auto_update_themes', array());
}

// Funktion zum Abrufen der neuesten Tags (hier statisch, alternativ per API z. B. von GitHub)
if (!function_exists('fxwp_get_latest_tags')) {
    function fxwp_get_latest_tags()
    {
        // try getting it via shell cli otherwise not at all
        $tags = shell_exec('git tag');
        if ($tags === null) {
            return array();
        }
        $tags = explode("\n", $tags);
        $tags = array_filter($tags);
        $tags = array_slice($tags, -5);
        return $tags;
    }
}
// Funktion zum Abrufen des Changelogs für einen Tag (hier statisch, alternativ per API)
if (!function_exists('fxwp_get_changelog')) {
    function fxwp_get_changelog($tag)
    {
        // try getting it via shell cli otherwise not at all
        $changelog = shell_exec('git log --pretty=format:"%h - %s (%an)" ' . $tag . '^..HEAD');
        if ($changelog === null) {
            return '';
        }
        $changelog = explode("\n", $changelog);
        $changelog = array_filter($changelog);
        $changelog = array_slice($changelog, 0, 5);
        $changelog = '<ul class="changelog" id="changelog-' . $tag . '">';
        foreach ($changelog as $line) {
            $changelog .= '<li>' . $line . '</li>';
        }
        $changelog .= '</ul>';
        $changelog .= '<span class="changelog-toggle" onclick="toggleChangelog(\'' . $tag . '\')">Changelog anzeigen</span>';
        return $changelog;
    }
}

function fxwp_updates_page()
{

    // Backup- bzw. Updateaktion verarbeiten
    if (isset($_POST['fxwp_update_settings_nonce']) && wp_verify_nonce($_POST['fxwp_update_settings_nonce'], 'fxwp_update_settings')) {
        switch ($_POST['fxwp_automatic_updates']) {
            case '1':
                fxwp_enable_automatic_updates();
                break;
            case '0':
                fxwp_disable_automatic_updates();
                break;
        }
    }
    ?>
    <div class="wrap">
        <h1>Aktualisierungen</h1>
        <?php fxwp_show_deactivated_feature_warning('fxwp_deact_autoupdates'); ?>

        <!-- Automatische Aktualisierungen -->
        <h2>Automatische Aktualisierungen</h2>
        <form method="post" action="">
            <?php wp_nonce_field('fxwp_update_settings', 'fxwp_update_settings_nonce'); ?>
            <label>
                <select name="fxwp_automatic_updates">
                    <option value="1" <?php selected(get_option('fxwp_automatic_updates', true), true); ?>>Aktiviert
                    </option>
                    <option value="0" <?php selected(get_option('fxwp_automatic_updates', true), false); ?>>
                        Deaktiviert
                    </option>
                </select>
            </label>
            <p class="description">
                Wenn aktiviert, werden alle Plugins und die WordPress-Kernsoftware automatisch aktualisiert.
            </p>

            <!-- Manuelle Aktualisierungen (Plugins & Core) -->
            <h2>Manuelle Aktualisierungen</h2>
            <p>
                Klicken Sie auf den "Jetzt aktualisieren" Button neben dem jeweiligen Element, um manuell zu
                aktualisieren.
            </p>
            <?php
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            // Installierte Plugins abrufen
            $plugins = get_plugins();

            if (!empty($plugins)) {
                echo '<h3>Plugins</h3>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>Plugin</th><th>Aktion</th></tr></thead>';
                echo '<tbody>';
                foreach ($plugins as $plugin_file => $plugin_data) {
                    $update_url = wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($plugin_file)), 'upgrade-plugin_' . $plugin_file);
                    echo '<tr>';
                    echo '<td>' . $plugin_data['Name'] . '</td>';
                    echo '<td><a href="' . $update_url . '" class="button button-primary">Jetzt aktualisieren</a></td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            }

            echo '<h3>WordPress-Kernsoftware</h3>';
            $core_update_url = wp_nonce_url(admin_url('update-core.php'), 'upgrade-core');
            echo '<a href="' . $core_update_url . '" class="button button-primary">WordPress jetzt aktualisieren</a>';
            ?>

            <p><strong>Hinweis:</strong> Es wird empfohlen, vor Aktualisierungen ein Backup zu erstellen.</p>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary"
                       value="Änderungen speichern">
            </p>
        </form>

        <!-- Neuer Bereich: Manuelle Installation -->
        <div class="manual-update-section">
            <h3><?php echo esc_html__('Manuelle Installation', 'fxwp'); ?></h3>

            <?php
            // show latest tags only if > 0
            if (count(fxwp_get_latest_tags()) > 0) { ?>
                <p><?php echo esc_html__('Sie können eine der folgenden Versionen installieren:', 'fxwp'); ?></p>

                <!-- Bereich für vordefinierte Versionen (neuesten 5 Tags) -->
                <div class="predefined-versions">
                    <h4><?php echo esc_html__('Vordefinierte Versionen', 'fxwp'); ?></h4>
                    <ul class="tag-list">
                        <?php
                        $latest_tags = fxwp_get_latest_tags();
                        foreach ($latest_tags as $tag): ?>
                            <li class="flex">
                                <form method="post" action="index.php?fxwp_sync=1" style="display:inline-block;">
                                    <input type="hidden" name="fxwp_self_update_tag"
                                           value="<?php echo esc_attr($tag); ?>"/>
                                    <button type="submit"
                                            class="button button-primary"><?php echo esc_html($tag); ?></button>
                                </form>
                                <?php echo fxwp_get_changelog($tag); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php } ?>

            <!-- Bereich für benutzerdefinierte Version -->
            <div class="custom-version">
                <h4><?php echo esc_html__('Benutzerdefinierte Version', 'fxwp'); ?></h4>
                <form method="post" action="index.php?fxwp_sync=1">
                    <input type="text" name="fxwp_custom_update_tag"
                           placeholder="<?php echo esc_attr__('z.B. v2.0.0', 'fxwp'); ?>" required style="width:200px"/>
                    <input type="submit" class="button button-primary"
                           value="<?php echo esc_html__('Installieren', 'fxwp'); ?>"/>
                </form>
            </div>
        </div>

        <!-- Aktuelle Version & Update-Prüfung -->
        <div class="flex" style="margin-top:10px">
            <form method="post" action="" class="inline" style="height: .6em">
                <?php echo esc_html__('Version', 'fxwp'); ?> <?php echo esc_html(FXWP_VERSION); ?>
                <a href="<?php echo esc_url(admin_url('index.php?fxwp_sync=1')); ?>">
                    <?php echo esc_html__('Prüfen auf Updates', 'fxwp'); ?>
                </a>
            </form>
            <?php if (current_user_can("fxm_admin")) { ?>
                <svg class="inline"
                     height="1.5em"
                     xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     onclick="document.querySelector('svg.inline').classList.toggle('flip');document.querySelector('.tag-update').classList.toggle('inline');">
                    <title>chevron-left</title>
                    <path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z"/>
                </svg>
                <form method="post" action="index.php?fxwp_sync=1" class="tag-update">
                    <input type="text" name="fxwp_self_update_tag" placeholder="Tag" required style="width:60px"/>
                    <input type="submit" class="button button-primary"
                           value="<?php echo esc_html__('manuell installieren', 'fxwp'); ?>"/>
                </form>
            <?php } ?>
        </div>

        <style>
            .manual-update-section {
                border: 1px solid #ddd;
                padding: 15px;
                margin-top: 20px;
                background: #f9f9f9;
            }

            .manual-update-section h3 {
                border-bottom: 1px solid #ccc;
                padding-bottom: 5px;
            }

            .predefined-versions, .custom-version {
                margin-top: 15px;
            }

            .tag-list {
                list-style: none;
                padding: 0;
            }

            .changelog {
                display: none;
                border: 1px solid #ccc;
                padding: 5px;
                margin-top: 5px;
                background: #fff;
            }

            .changelog-toggle {
                cursor: pointer;
                margin-left: 10px;
                font-weight: bold;
            }

            .flex {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            form.inline {
                display: inline;
            }

            svg.inline {
                opacity: 0.5;
                transition: all 0.1s;
            }

            svg.inline:hover {
                cursor: pointer;
                transform: rotate(180deg);
                opacity: 1;
            }

            .tag-update {
                display: none;
            }

            .flip {
                transform: rotate(180deg);
            }
        </style>
        <script>
            function toggleChangelog(tag) {
                var changelogDiv = document.getElementById('changelog-' + tag);
                if (changelogDiv.style.display === 'none' || changelogDiv.style.display === '') {
                    changelogDiv.style.display = 'block';
                } else {
                    changelogDiv.style.display = 'none';
                }
            }
        </script>
    </div>
    <?php
}
