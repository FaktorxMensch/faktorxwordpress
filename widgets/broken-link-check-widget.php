<?php
// a widget that shows the latest broken links
function fxwp_broken_link_checker_widget()
{
    $fxwp_broken_links = get_option('fxwp_broken_links', array());

    if (empty($fxwp_broken_links)) {
        echo '<p>' . esc_html__('Keine Links gefunden.', 'fxwp') . '</p>';
        return;
    }

    // slice first 5
    $fxwp_broken_links = array_slice($fxwp_broken_links, 0, 5);

    echo '<p>' . esc_html__('Folgenden Links zeigen auf nicht existierende Seiten und sollten korrigiert werden.', 'fxwp') . '</p>';
    echo '<ul>';
    foreach ($fxwp_broken_links as $fxwp_broken_link) {
        $url = $fxwp_broken_link['url'];
        echo '<li><a href="' . esc_url($url) . '">' . esc_html($url) . '</a></li>';
    }
    echo '</ul>';

    // show link to all broken links
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=fxwp-broken-link-checker')) . '" class="button button-primary">' . esc_html__('Zeige alle fehlerhaften Links', 'fxwp') . '</a></p>';


}

// add dashboard widget
function fxwp_register_broken_link_checker_widget()
{
    wp_add_dashboard_widget(
        'fxwp_broken_link_checker_widget', // Widget slug.
        'Nicht funktionierende Links', // Title.
        'fxwp_broken_link_checker_widget' // Display function.
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_broken_link_checker_widget');