<?php
// todo ema

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function fxwp_seo_calculator()
{

    // Get all published posts
    $args = array(
        // any post type
        'post_type' => 'any',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);

    $error_links = array(); // Array to store error links

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();

            // Get the post content
            $content = get_post_field('post_content', $post_id);
        }
    }

}

// Run the link check when the cron event is triggered
add_action('fxwp_seo_calculator_cron', 'fxwp_seo_calculator');

// Schedule the cron event to run daily
/*if (!wp_next_scheduled('fxwp_seo_calculator_cron')) {
    wp_schedule_event(time(), 'daily', 'fxwp_seo_calculator_cron');
}*/
