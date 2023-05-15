<?php

function fxwp_optimize_images()
{
    // Get all the images
    $images = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'numberposts' => -1,
    ));

    // Retrieve the stored optimized images from options
    $optimized_images = get_option('fxwp_optimized_images', array());

    foreach ($images as $image) {

        $image_url = wp_get_attachment_url($image->ID);

        // Check if the image has already been optimized (by comparing the image ID)
        foreach ($optimized_images as $optimized_image) {
            if ($optimized_image['id'] == $image->ID) {
                // If the image has already been optimized, skip it
                continue 2;
            }
        }

        $optimized_image_url = fxwp_optimize_image($image_url);
        // After optimization, store the optimized image URL and title
        $optimized_image = array(
            'url' => $optimized_image_url,
            'id' => $image->ID,
            'current_size' => round(filesize(str_replace(site_url('/'), ABSPATH, $optimized_image_url)) / 1024) . ' KB',
        );
        // Add the optimized image to the array
        $optimized_images[] = $optimized_image;

    }
    // Update the stored optimized images in options
    update_option('fxwp_optimized_images', $optimized_images);
}

// Placeholder optimization function (replace with your actual optimization logic)
function fxwp_optimize_image($image_url)
{
    // Get the path to the image file
    $image_path = str_replace(site_url('/'), ABSPATH, $image_url);

    // Get the image size info
    $image_info = getimagesize($image_path);
    $image_width = $image_info[0];
    $image_height = $image_info[1];

    // Check if the image needs resizing
    if ($image_width > 2000 || $image_height > 2000) {

        // Calculate the new dimensions while preserving aspect ratio
        $aspect_ratio = $image_width / $image_height;
        if ($aspect_ratio > 1) {
            $new_width = 2000;
            $new_height = round(2000 / $aspect_ratio);
        } else {
            $new_width = round(2000 * $aspect_ratio);
            $new_height = 2000;
        }

        // Resize the image
        $resized_image = wp_get_image_editor($image_path);
        if (!is_wp_error($resized_image)) {
            $resized_image->resize($new_width, $new_height, false);
            $resized_image->save($image_path);
        }
    }

    // Apply 95% compression to the image
    $compressed_image = wp_get_image_editor($image_path);
    if (!is_wp_error($compressed_image)) {
        $compressed_image->set_quality(95);
        $compressed_image->save($image_path);
    }

    // Return the optimized image URL
    return $image_url;
}

// Schedule the fxwp_optimize_images() function to run hourly
if (!wp_next_scheduled('fxwp_optimize_images_task')) {
    wp_schedule_event(time(), 'hourly', 'fxwp_optimize_images_task');
}
add_action('fxwp_optimize_images_task', 'fxwp_optimize_images');

function fxwp_reset_optimized_images()
{
    // Delete the stored optimized images from options
    delete_option('fxwp_optimized_images');
}