<?php
function fxwp_enqueue_favicon()
{
    wp_enqueue_script('fxwp-favicon', '', array(), false, true);
}

function fxwp_output_favicon()
{
    $favicon_id = get_option('fxwp_favicon');
    if ($favicon_id) {
        $favicon_url = wp_get_attachment_url($favicon_id);
        if ($favicon_url) {
            echo '<!-- Favicon -->';
            echo '<link rel="shortcut icon" href="' . esc_url($favicon_url) . '" type="image/x-icon" />';
            echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/x-icon" />';
        }
    }
}

add_action('wp_enqueue_scripts', 'fxwp_enqueue_favicon');
add_action('wp_head', 'fxwp_output_favicon');
