<?php

function fxwp_image_optimizer_page()
{

    if (!empty($_GET['fxwp_optimize_images'])) {
        fxwp_optimize_images();
    }

    if (!empty($_GET['fxwp_reset_optimized_images'])) {
        fxwp_reset_optimized_images();
    }

    // Retrieve the stored optimized images
    $optimized_images = get_option('fxwp_optimized_images', array());

    // Output the list HTML
    $output = '<div class="wrap"><h1>' . esc_html__('Optimized Images', 'fxwp') . '</h1>';

    $output .= '<p>' . esc_html__('Folgende Bilder wurden auf die optimale Größe verkleinert und sollten nun schneller laden.', 'fxwp') . '</p>';

    // have a optimze now and reset button
    $output .= '<p><a href="' . esc_url(admin_url('admin.php?page=fxwp-image-optimizer&fxwp_optimize_images=1')) . '" class="button button-primary">' . esc_html__('Jetzt optimieren', 'fxwp') . '</a> ';
    $output .= '<a href="' . esc_url(admin_url('admin.php?page=fxwp-image-optimizer&fxwp_reset_optimized_images=1')) . '" class="button button-secondary">' . esc_html__('Zurücksetzen', 'fxwp') . '</a></p>';

    $output .= '<table class="wp-list-table widefat fixed striped">';

    if (empty($optimized_images)) {
        $output .= '<p>' . esc_html__('Keine optimierten Bilder gefunden.', 'fxwp') . '</p>';
        echo $output;
        return;
    }

    foreach ($optimized_images as $optimized_image) {
        $image_url = $optimized_image['url'];
        $image_id = $optimized_image['id'];

        $output .= '<tr>';
        $output .= '<td width="100"><img src="' . esc_url($image_url) . '" width="100" /></td>';
        $output .= '<td><a href="' . esc_url(get_edit_post_link($image_id)) . '" target="_blank">' . esc_html(get_the_title($image_id)) . '</a>
<br>
Aktuelle Größe: ' . esc_html($optimized_image['current_size']) . '<br>
</td>';
        $output .= '</tr>';

    }

    $output .= '</table>';
    $output .= '</div>';

    echo $output;
}
