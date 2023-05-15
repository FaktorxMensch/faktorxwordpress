<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function fxwp_check_links()
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

            // Use a regular expression to find all URLs in the post content
            preg_match_all('/<a\s[^>]*?href=["\']([^"\']+)/', $content, $matches);

            // Check each URL for its status
            if (!empty($matches[1])) {

                // check only a few random links, not all of them (for performance reasons)
                // since we're using a cron job, we can check all of them randomly over time

                // shuffel the array
                shuffle($matches[1]);

                // take the first 10 elements
                $matches[1] = array_slice($matches[1], 0, 10);

                foreach ($matches[1] as $url) {

                    // Check if the URL is broken
                    echo 'Checking ' . $url . '...';

                    $response = wp_remote_get($url);

                    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                        // URL is broken or returned an error
                        // Store the broken link in the errors array
                        $error_links[] = array(
                            'post_id' => $post_id,
                            'url' => $url,
                        );
                    }

                }
            }
        }
    }

    wp_reset_postdata();

    // Store the errors in the WordPress options table
    update_option('fxwp_broken_links', $error_links);
}

// Run the link check when the cron event is triggered
add_action('fxwp_broken_link_checker_cron', 'fxwp_check_links');

// Schedule the cron event to run daily
if (!wp_next_scheduled('fxwp_broken_link_checker_cron')) {
    wp_schedule_event(time(), 'daily', 'fxwp_broken_link_checker_cron');
}
