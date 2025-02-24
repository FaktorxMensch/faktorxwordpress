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


/**
 * Holt die Release-Daten aus der Nuxt API.
 *
 * @return array Enthält Release-Daten (tag, description, isPreview) oder ein leeres Array bei Fehlern.
 */
function fxwp_get_releases_from_api()
{
    // URL des Nuxt API Endpoints (anpassen, falls nötig)
    $api_url = 'https://p2.faktorxmensch.com/api/fxwp/tags';

    // Statischer Key, der auch in Nuxt überprüft wird (aus .env/nuxt.config oder hier statisch)
    $staticKey = '0e1b08beec03f538176e6a3ea3802062jFUA';

    // Payload als JSON codiert
    $payload = json_encode(['key' => $staticKey]);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        curl_close($curl);
        return array(); // Fehlerfall: leeres Array zurückgeben
    }

    curl_close($curl);
    $data = json_decode($response, true);
    return is_array($data) ? $data : array();
}

function fxwp_updates_page()
{
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

    // Enqueue Vue.js
    wp_enqueue_script('vue', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), null, true);
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 10px"><?php echo esc_html__('System-Updates', 'fxwp'); ?></h1>
        <?php fxwp_show_deactivated_feature_warning('fxwp_deact_autoupdates'); ?>

        <!-- Automatische Aktualisierungen -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">
                    <?php echo esc_html__('Automatische Aktualisierungen', 'fxwp'); ?>
                </h2>
            </div>
            <div class="inside" style="padding-bottom: 15px; margin-bottom: 0">
                <form method="post" action="" style="padding-bottom: 0; margin-bottom: 0">
                    <?php wp_nonce_field('fxwp_update_settings', 'fxwp_update_settings_nonce'); ?>
                    <label>
                        <select name="fxwp_automatic_updates" class="regular-text">
                            <option value="1" <?php selected(get_option('fxwp_automatic_updates', true), true); ?>>
                                <?php echo esc_html__('Aktiviert', 'fxwp'); ?>
                            </option>
                            <option value="0" <?php selected(get_option('fxwp_automatic_updates', true), false); ?>>
                                <?php echo esc_html__('Deaktiviert', 'fxwp'); ?>
                            </option>
                        </select>
                    </label>
                    <p class="description">
                        <?php echo esc_html__('Wenn aktiviert, werden alle Plugins und die WordPress-Kernsoftware automatisch aktualisiert.', 'fxwp'); ?>
                    </p>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?php echo esc_attr__('Änderungen speichern', 'fxwp'); ?>">
                </form>
            </div>
        </div>

        <!-- Manuelle Aktualisierungen (Plugins & Core) -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">
                    <?php echo esc_html__('Manuelle Aktualisierungen', 'fxwp'); ?>
                </h2>
            </div>
            <div class="inside">
                <p>
                    <?php echo esc_html__('Klicken Sie auf den "Jetzt aktualisieren" Button neben dem jeweiligen Element, um manuell zu aktualisieren.', 'fxwp'); ?>
                </p>
                <?php
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                // Installierte Plugins abrufen
                $plugins = get_plugins();

                if (!empty($plugins)) {
                    echo '<h3>' . esc_html__('Plugins', 'fxwp') . '</h3>';
                    echo '<table class="wp-list-table widefat fixed striped plugins">';
                    echo '<thead><tr><th>' . esc_html__('Plugin', 'fxwp') . '</th><th>' . esc_html__('Aktion', 'fxwp') . '</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($plugins as $plugin_file => $plugin_data) {
                        $update_url = wp_nonce_url(admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($plugin_file)), 'upgrade-plugin_' . $plugin_file);
                        echo '<tr>';
                        echo '<td>' . esc_html($plugin_data['Name']) . '</td>';
                        echo '<td><a href="' . esc_url($update_url) . '" class="button button-secondary">' . esc_html__('Jetzt aktualisieren', 'fxwp') . '</a></td>';
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';
                }

                echo '<h3>' . esc_html__('WordPress-Kernsoftware', 'fxwp') . '</h3>';
                $core_update_url = wp_nonce_url(admin_url('update-core.php'), 'upgrade-core');
                echo '<p><a href="' . esc_url($core_update_url) . '" class="button button-secondary">' . esc_html__('WordPress jetzt aktualisieren', 'fxwp') . '</a></p>';
                ?>
            </div>
        </div>

        <!-- Manuelle Installation mit Vue.js -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle">
                    <?php echo esc_html__('Manuelle Installation', 'fxwp'); ?>
                </h2>
            </div>
            <div class="inside" id="fxwp-manual-install">
                <!-- Suchleiste -->
                <div class="search-box" style="margin-bottom: 1em;">
                    <input
                            type="search"
                            v-model="searchQuery"
                            class="regular-text"
                            placeholder="<?php echo esc_attr__('Suche in Changelogs...', 'fxwp'); ?>"
                    >
                </div>

                <!-- Verfügbare Versionen -->
                <div v-if="releases && releases.length" v-cloak>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                        <tr>
                            <th width="50"><?php echo esc_html__('Version', 'fxwp'); ?></th>
                            <th width="100"><?php echo esc_html__('Typ', 'fxwp'); ?></th>
                            <th width="100%"><?php echo esc_html__('Changelog', 'fxwp'); ?></th>
                            <th width="100"><?php echo esc_html__('Aktion', 'fxwp'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="release in filteredReleases" :key="release.tag">
                            <td>
                                {{ release.tag }}
                            </td>
                            <td>
                                <span v-if="release.isPreview" class="fxwp-preview-badge">Preview</span>
                            </td>
                            <td>
                                <div v-if="release.description" v-html="highlightMatches(release.description)"></div>
                                <div v-else><?php echo esc_html__('Kein Changelog verfügbar', 'fxwp'); ?></div>

                                <div style="margin-top: .2em;">
                                    <a :href="release.compareLink" target="_blank" class="hover"
                                       v-if="release.compareLink">
                                        <?php echo esc_html__('Vergleiche mit vorheriger Version', 'fxwp'); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <form method="post" action="index.php?fxwp_sync=1" style="display: inline;">
                                    <input type="hidden" name="fxwp_self_update_tag" :value="release.tag">
                                    <button type="submit"
                                            class="button button-secondary"><?php echo esc_html__('Installieren', 'fxwp'); ?></button>
                                </form>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="notice notice-info">
                    <p><?php echo esc_html__('Keine Versionen gefunden.', 'fxwp'); ?></p>
                </div>

                <!-- Benutzerdefinierte Version -->
                <div class="custom-version" style="margin-top: 1em;">
                    <h4><?php echo esc_html__('Benutzerdefinierte Version', 'fxwp'); ?></h4>
                    <form method="post" action="index.php?fxwp_sync=1" class="custom-version-form"
                          style="margin-bottom: 0">
                        <input
                                type="text"
                                name="fxwp_custom_update_tag"
                                class="regular-text"
                                placeholder="<?php echo esc_attr__('z.B. v2.0.0', 'fxwp'); ?>"
                                required
                        >
                        <button type="submit" class="button button-secondary">
                            <?php echo esc_html__('Installieren', 'fxwp'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Aktuelle Version & Update-Prüfung -->
        <div class="fxwp-version-info">
            <span><?php echo esc_html__('Version', 'fxwp'); ?><?php echo esc_html(FXWP_VERSION); ?></span>
            <a href="<?php echo esc_url(admin_url('index.php?fxwp_sync=1')); ?>">
                <?php echo esc_html__('Prüfen auf Updates', 'fxwp'); ?>
            </a>
        </div>

        <style>
            [v-cloak] {
                display: none;
            }

            a.hover:hover {
                text-decoration: underline;
                cursor: pointer;
            }

            .fxwp-preview-badge {
                background: #dc3232;
                color: white;
                font-size: 11px;
                padding: 2px 6px;
                border-radius: 3px;
                margin-left: 5px;
            }

            .custom-version-form {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .fxwp-version-info {
                margin-top: 20px;
                padding: 10px;
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 3px;
            }

            mark.highlight {
                background-color: #ffeb3b;
                padding: 1px 3px;
                border-radius: 2px;
            }

            .postbox h2 {
                margin: 0;
                font-size: inherit;
                padding: 8px 12px;
            }
        </style>

        <?php
        // Inline JavaScript für Vue.js Initialisierung
        $releases = fxwp_get_releases_from_api();
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const {createApp} = Vue;

                createApp({
                    data() {
                        return {
                            releases: <?php echo json_encode($releases); ?>,
                            searchQuery: ''
                        }
                    },
                    computed: {
                        filteredReleases() {

                            const maxReleases = 10;

                            let releases = this.releases;
                            releases.forEach((release, index) => {
                                if (index < releases.length - 1) {
                                    release.compareLink = `https://github.com/ziegenhagel/faktorxwordpress/compare/${releases[index + 1].tag}...${release.tag}`;
                                }
                            })

                            if (!this.searchQuery) return releases.slice(0, maxReleases);

                            const query = this.searchQuery.toLowerCase();
                            return releases.filter(release => {
                                const description = release.description || '';
                                return release.tag.toLowerCase().includes(query) ||
                                    description.toLowerCase().includes(query);
                            }).map(release => {
                                return {
                                    ...release,
                                };
                            }).slice(0, maxReleases);

                        }
                    },
                    methods: {
                        highlightMatches(text) {
                            if (!this.searchQuery || !text) return text;

                            const regex = new RegExp(`(${this.searchQuery})`, 'gi');
                            return text.replace(regex, '<mark class="highlight">$1</mark>');
                        }
                    }
                }).mount('#fxwp-manual-install');
            })
            ;
        </script>
    </div>
    <?php
}


