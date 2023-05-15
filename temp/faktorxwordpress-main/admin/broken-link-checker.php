<?php

function fxwp_broken_link_checker_page()
{

    // if fxwp_check_links
    if (isset($_GET['fxwp_check_links'])) {
        // Check the links
        fxwp_check_links();
    }


    // Retrieve the stored errors
    $error_links = get_option('fxwp_broken_links', array());

    // Output the table HTML
    $output = '<h2>' . esc_html__('Broken Links', 'fxwp') . '</h2>';
    if (empty($error_links)) {
        $output .= '<p>' . esc_html__('No broken links found.', 'fxwp') . '</p>';
        // have a check now button
        $output .= '<p><a href="' . esc_url(admin_url('admin.php?page=fxwp-broken-link-checker&fxwp_check_links=1')) . '" class="button button-primary">' . esc_html__('Check Now', 'fxwp') . '</a></p>';
        echo $output;
        return;
    } else {
        $output .= '<p>' . esc_html__('The following links are broken:', 'fxwp') . '</p>';
    }

    $output .= '<table class="wp-list-table widefat fixed striped">';
    $output .= '<thead><tr><th>' . esc_html__('Post ID', 'fxwp') . '</th><th>' . esc_html__('URL', 'fxwp') . '</th><th>' . esc_html__('Edit', 'fxwp') . '</th></tr></thead>';
    $output .= '<tbody>';

    foreach ($error_links as $error_link) {
        $post_id = $error_link['post_id'];
        $url = $error_link['url'];
        $edit_url = get_edit_post_link($post_id); // Get the edit link for the post

        $output .= '<tr>';
        $output .= '<td>' . esc_html($post_id) . '</td>';
        $output .= '<td>' . esc_url($url) . '</td>';
        $output .= '<td><a href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'fxwp') . '</a></td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    echo $output;
}
