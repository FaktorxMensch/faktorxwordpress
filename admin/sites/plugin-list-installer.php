<?php
function fxwp_configure_collection($collection)
{
    foreach ($collection as $plugin_data) {
        $plugin = $plugin_data['name'];
        // Aktualisieren der Plugin-Optionen
        foreach ($plugin_data['options'] as $option => $value) {
            update_option($option, $value);
        }
        echo "<p>{$plugin} erfolgreich konfiguriert.</p>";
    }
}


function fxwp_install_plugin($plugin)
{

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
        $plugin_files = glob(WP_PLUGIN_DIR . '/' . $plugin . '/*.php');
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


function fxwp_install_collection($collection)
{

    // Installieren und aktivieren Sie die Plugins in der ausgewählten Sammlung
    foreach ($collection as $plugin_data) {
        $plugin = $plugin_data['name'];

        fxwp_install_plugin($plugin);
    }


}

function fxwp_plugin_list_installer_page()
{
    $site_setup_options = array(

        // Faktor&times;WordPress Theme installieren
        'fxwp_install_theme' => 'Faktor&times;WordPress Theme installieren',
        // Plugin Paket Standard installieren
        'fxwp_install_first_collection' => 'Plugin Paket Standard installieren',

        // Startseite erstellen
        'fxwp_create_homepage' => 'Startseite erstellen',
        // Datenschutzerklärung erstellen
        'fxwp_create_privacy_policy' => 'Datenschutzerklärung erstellen',
        // Impressum erstellen
        'fxwp_create_imprint' => 'Impressum erstellen',
        // Cookie Hinweis einrichten
        'fxwp_create_cookie_notice' => 'Cookie Hinweis erstellen',
        // Kontaktseite mit Formular erstellen
        'fxwp_create_contact_page' => 'Kontaktseite mit Formular erstellen (Contact Form 7)',
        // Shop und AGB-Seite dazu erstellen
        'fxwp_create_shop_pages' => 'Shop und AGB-Seite dazu erstellen',
        // SEO Plugin installieren
        'fxwp_install_seo_plugin' => 'SEO Plugin installieren',
        // Top und Footer Menüs erstellen
        'fxwp_create_menus' => 'Top und Footer Menüs erstellen',
        //Local google fonts plugin installieren
        'fxwp_install_local_google_fonts' => 'Local google fonts plugin installieren',
    );

    // Definieren Sie Ihre Plugin-Sammlungen
    $plugin_collections = array(
        'Standard' => array(
            array(
                'name' => 'post-types-order',
                'options' => array(),
            ),
        ),
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
            array(
                'name' => 'autoptimize',
                'options' => array(),
            ),
        ),
        'Sicherheit' => array(
            array(
                'name' => 'wordfence',
                'options' => array(),
            )
        ),
        'SEO' => array(
            array(
                'name' => 'seo-by-rank-math',
                'options' => array(),
            )
        ),
        'Block site builder' => array(
            array(
                'name' => 'elementor',
                'options' => array(),
            )
        )
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
    foreach ($site_setup_options as $option => $label) {
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

    if (isset($_POST['fxwp_site_setup'])) {
        // go through all options and set them
        foreach ($site_setup_options as $option => $label) {
            if (isset($_POST[$option])) {

                switch ($option) {
                    case 'fxwp_install_first_collection':
                        fxwp_install_collection($plugin_collections[0]);
                        break;
                    case 'fxwp_install_theme':
                        fxwp_install_theme();
                        break;
                    case 'fxwp_create_homepage':
                        // have wordpress create a page with the title "Home" and the slug "home"
                        $page = get_page_by_title('Home');
                        if (!$page) {
                            $page = wp_insert_post(
                                array(
                                    'post_title' => 'Home',
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_name' => 'home',
                                    'post_content' => 'Dies ist die Homepage',
                                )
                            );
                        }

                        // set it as the homepage
                        // FIX DOESNT WORK
                        update_option('show_on_front', 'page');
                        update_option('page_on_front', $page->ID);

                        break;

                    case 'fxwp_create_menus':
                        // create a menu with the name "Hauptmenü" in location  in locaiton header-menu
                        $menu_name = 'Hauptmenü';
                        $menu_exists = wp_get_nav_menu_object($menu_name);
                        $theme_location = 'header-menu';
                        if (!$menu_exists) {
                            $menu_id = wp_create_nav_menu($menu_name);
                            $locations = get_theme_mod('nav_menu_locations');
                            $locations[$theme_location] = $menu_id;
                            set_theme_mod('nav_menu_locations', $locations);
                        }
                        // add the home page to the menu
                        $page = get_page_by_title('Home');
                        wp_update_nav_menu_item($menu_id, 0, array(
                            'menu-item-title' => __('Home'),
                            'menu-item-classes' => 'home',
                            'menu-item-url' => home_url('/'),
                            'menu-item-status' => 'publish'));
                        // add the kontakt page to the menu
                        $page = get_page_by_title('Kontakt');
                        wp_update_nav_menu_item($menu_id, 0, array(
                            'menu-item-title' => __('Kontakt'),
                            'menu-item-classes' => 'kontakt',
                            'menu-item-url' => home_url('/kontakt'),
                            'menu-item-status' => 'publish'));
                        // create a menu with the name "Footer" in location  in locaiton footer-menu
                        $menu_name = 'Footer';
                        $menu_exists = wp_get_nav_menu_object($menu_name);
                        $theme_location = 'footer-menu';
                        if (!$menu_exists) {
                            $menu_id = wp_create_nav_menu($menu_name);
                            $locations = get_theme_mod('nav_menu_locations');
                            $locations[$theme_location] = $menu_id;
                            set_theme_mod('nav_menu_locations', $locations);
                        }
                        // add the pages agb, impressum, datenschutz / datenschutzerklärung to the menu
                        foreach (array('AGB', 'Impressum', 'Datenschutz', 'Datenschutzerklärung') as $page_title) {
                            $page = get_page_by_title($page_title);
                            if ($page)
                                wp_update_nav_menu_item($menu_id, 0, array(
                                    'menu-item-title' => __($page_title),
                                    'menu-item-classes' => strtolower($page_title),
                                    'menu-item-url' => home_url('/' . strtolower($page_title)),
                                    'menu-item-status' => 'publish'));
                        }

                        break;
                    case 'fxwp_create_privacy_policy':
                        // create a page with the title "Datenschutzerklärung" and the slug "datenschutzerklaerung"
                        $page = get_page_by_title('Datenschutzerklärung');
                        if (!$page) {
                            $page = wp_insert_post(
                                array(
                                    'post_title' => 'Datenschutzerklärung',
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_content' => 'Bitte ersetzen Sie diesen Text mit Ihrer Datenschutzerklärung',
                                    'post_slug' => 'datenschutzerklaerung'
                                )
                            );
                            // set a please change me text as content
                        }
                        // set it as the privacy policy page
                        update_option('wp_page_for_privacy_policy', $page->ID);
                        break;
                    case 'fxwp_create_imprint':
                        // create a page with the title "Impressum" and the slug "impressum"
                        $page = get_page_by_title('Impressum');
                        if (!$page) {
                            $page = wp_insert_post(
                                array(
                                    'post_title' => 'Impressum',
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_content' => 'Bitte ersetzen Sie diesen Text mit Ihrem Impressum',
                                    'post_slug' => 'impressum'
                                )
                            );
                            // set a please change me text as content
                        }
                        break;
                    case 'fxwp_create_cookie_notice':
                        // install the cookie notice plugin
                        fxwp_install_plugin('complianz-gdpr');
                        // activate the cookie notice plugin
                        activate_plugin('complianz-gdpr/complianz-gpdr.php');

                        // Set the cookie notice settings
                        $customer = get_option('fxwp_customer');
                        $customer_address = $customer['default_address']['name'] . PHP_EOL . $customer['default_address']['address1'] . PHP_EOL . $customer['default_address']['address2'];
                        update_option('complianz_options_wizard', unserialize('a:17:{s:15:"country_company";s:2:"DE";s:7:"regions";s:2:"eu";s:18:"eu_consent_regions";s:3:"yes";s:18:"uk_consent_regions";s:2:"no";s:9:"us_states";a:6:{s:3:"cal";s:1:"0";s:3:"col";s:1:"0";s:3:"con";s:1:"0";s:3:"nev";s:1:"0";s:3:"uta";s:1:"0";s:3:"vir";s:1:"0";}s:21:"wp_admin_access_users";s:2:"no";s:16:"cookie-statement";s:9:"generated";s:17:"privacy-statement";s:6:"custom";s:9:"impressum";s:6:"custom";s:10:"disclaimer";s:4:"none";s:17:"organisation_name";s:' . strlen($customer['name']) . ':"' . $customer['name'] . '";s:15:"address_company";s:' . strlen($customer_address) . ':"' . $customer_address . '";s:13:"email_company";s:' . strlen($customer['email']) . ':"' . $customer['email'] . '";s:17:"telephone_company";s:' . strlen($customer['phone']) . ':"' . $customer['phone'] . '";s:18:"records_of_consent";s:2:"no";s:11:"datarequest";s:2:"no";s:11:"respect_dnt";s:3:"yes";}'));
                        // Update other options as needed

                        echo "<p>Cookie notice successfully installed and configured.</p>";

                        break;
                    case 'fxwp_create_contact_page':

                        // install the contact form 7 plugin
                        fxwp_install_plugin('contact-form-7');
                        // activate the contact form 7 plugin
                        activate_plugin('contact-form-7/wp-contact-form-7.php');
                        // create a contact form with the title "Kontaktformular"
                        $contact_form = get_page_by_title('Kontaktformular');
                        if (!$contact_form) {
                            $contact_form = wp_insert_post(
                                array(
                                    'post_title' => 'Kontaktformular',
                                    'post_type' => 'wpcf7_contact_form',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_content' => '[text* name placeholder "Name*"]
                                        [text* email placeholder "E-Mail*"]
                                        [text* subject placeholder "Betreff*"]
                                        [textarea* message placeholder "Nachricht*"]
                                        [submit "Senden"]'
                                )
                            );
                        }
                        // create a page with the title "Kontakt" and the slug "kontakt"
                        $page = get_page_by_title('Kontakt');
                        if (!$page) {
                            $page = wp_insert_post(
                                array(
                                    'post_title' => 'Kontakt',
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_content' => '<h1>Kontakt</h1>[contact-form-7 id="' . $contact_form . '" title="Kontaktformular"]', 'post_slug' => 'kontakt'
                                )
                            );
                        }
                        break;
                    case 'fxwp_create_shop_pages':
                        // install woo commerce
                        fxwp_install_plugin('woocommerce');
                        // activate woo commerce
                        activate_plugin('woocommerce/woocommerce.php');
                        // agb page
                        $page = get_page_by_title('AGB');
                        if (!$page) {
                            $page = wp_insert_post(
                                array(
                                    'post_title' => 'AGB',
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'post_author' => 1,
                                    'post_content' => 'Bitte ersetzen Sie diesen Text mit Ihren AGB',
                                    'post_slug' => 'agb'
                                )
                            );
                        }
                        // set it as the terms and conditions page
                        update_option('woocommerce_terms_page_id', $page->ID);
                        // datenschutz page
                        $page = get_page_by_title('Datenschutzerklärung');
                        update_option('woocommerce_privacy_policy_page_id', $page->ID);
                        break;
                    case 'fxwp_install_seo_plugin':
                        // install the seo plugin
                        fxwp_install_plugin('seo-by-rank-math');
                        // activate the seo plugin
                        activate_plugin('seo-by-rank-math/rank-math.php');
                        break;
                    case 'fxwp_install_local_google_fonts':

                        // install the local google fonts plugin
                        fxwp_install_plugin('local-google-fonts');
                        // activate the local google fonts plugin
                        activate_plugin('local-google-fonts/local-google-fonts.php');

                        // update the plugin options
                        update_option('local_google_fonts', unserialize('a:1:{s:9:"auto_load";s:1:"1";}'));
                        update_option('fxwp_google_fonts_remove', 'einfach');

                        break;
                    default:
                        echo "Option {$option} not implemented yet";
                        break;
                }
            }
        }
    }

    // Überprüfen, ob die Plugins konfiguriert werden sollen
    if (isset($_POST['configure_plugins'])) {
        fxwp_configure_collection($plugin_collections[$_POST['plugin_collection']]);
    } else if (isset($_POST['plugin_collection'])) {
        fxwp_install_collection($plugin_collections[$_POST['plugin_collection']]);
    }

    // sometimes we wnat to install the fxwp theme
    if (isset($_POST['fxwp_install_theme'])) {
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
