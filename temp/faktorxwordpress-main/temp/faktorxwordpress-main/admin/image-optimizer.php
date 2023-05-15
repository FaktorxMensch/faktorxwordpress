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
    $output = '<h2>' . esc_html__('Optimized Images', 'fxwp') . '</h2>';

    // have a optimze now and reset button
    $output .= '<p><a href="' . esc_url(admin_url('admin.php?page=fxwp-image-optimizer&fxwp_optimize_images=1')) . '" class="button button-primary">' . esc_html__('Optimize Now', 'fxwp') . '</a> <a href="' . esc_url(admin_url('admin.php?page=fxwp-image-optimizer&fxwp_reset_optimized_images=1')) . '" class="button button-secondary">' . esc_html__('Reset', 'fxwp') . '</a></p>';

    $output .= '<ul>';

    if (empty($optimized_images)) {
        $output .= '<li>' . esc_html__('No images optimized yet.', 'fxwp') . '</li>';
        echo $output;
        return;
    }

    foreach ($optimized_images as $optimized_image) {
        $image_url = $optimized_image['url'];
        $image_id = $optimized_image['id'];

        $output .= '<li><a href="' . esc_url($image_url) . '">' . esc_html($image_url) . '</a></li>';
    }

    $output .= '</ul>';

    echo $output;
}
