<?php

function fxwp_register_custom_post_types()
{
    // get post types from options
    $post_types = get_option('fxwp_post_types', array());
    foreach ($post_types as $post_type) {
        if (!isset($post_type['post_type']) || !isset($post_type['label']) || !isset($post_type['public']) || !isset($post_type['show_in_menu']) || !isset($post_type['supports']) || !isset($post_type['menu_icon'])) {
            continue;
        }
        register_post_type($post_type['post_type'], array(
            'labels' => array(
                'name' => $post_type['label'],
                'singular_name' => $post_type['label'],
            ),
            'public' => $post_type['public'],
            'show_in_menu' => $post_type['show_in_menu'],
            'supports' => $post_type['supports'],
            'menu_icon' => $post_type['menu_icon'],
        ));
    }


    add_action('admin_menu', 'fxwp_add_custom_meta_boxes');

}

add_action('init', 'fxwp_register_custom_post_types');

function fxwp_add_custom_meta_boxes()
{
    $post_type_fields = get_option('fxwp_custom_fields', array());
    foreach ($post_type_fields as $post_type => $fields) {
        foreach ($fields as $field) {
            if (!isset($field['field_name']) || !isset($field['field_label']) || !isset($field['field_type'])) {
                continue;
            }
            add_post_type_support($post_type, 'custom-fields');
            add_meta_box(
                $field['field_name'],
                $field['field_label'],
                'fxwp_custom_meta_box_callback',
                $post_type,
                'normal',
                'high',
                array('field' => $field)
            );
        }
    }
}

// Custom meta box callback function
function fxwp_custom_meta_box_callback($post, $args)
{
    $field = $args['args']['field'];

    // Retrieve existing meta box values
    $meta_value = get_post_meta($post->ID, $field['field_name'], true);

    if (!$meta_value) {
        $meta_value = $field['default_value'] ?? '';
    }

    // if checkbox
    if ($field['field_type'] === 'checkbox') {
        $checked = $meta_value === 'on' ? 'checked' : '';
        ?>
        <input type="hidden" name="custom_meta_field" value="off">
        <input type="checkbox" id="custom_meta_field" name="custom_meta_field" value="on" <?php echo $checked; ?>>
        <?php
        return;
    }

    // if text
    if ($field['field_type'] === 'text' || $field['field_type'] === 'number' || $field['field_type'] === 'email' || $field['field_type'] === 'url' || $field['field_type'] === 'date' || $field['field_type'] === 'time') {
        ?>
        <input type="text"
               type="<?php echo $field['field_type']; ?>"
               id="custom_meta_field" name="custom_meta_field" value="<?php echo esc_attr($meta_value); ?>">
        <?php
        return;
    }
}


function fxwp_save_custom_meta_box_data($post_id)
{
    // Verify nonce
    if (!isset($_POST['custom_meta_box_nonce']) || !wp_verify_nonce($_POST['custom_meta_box_nonce'], 'custom_meta_box_nonce')) {
        return;
    }

    // Check if the current user has permission to save the meta box data
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the meta box value
    if (isset($_POST['custom_meta_field'])) {
        $meta_value = sanitize_text_field($_POST['custom_meta_field']);
        update_post_meta($post_id, 'custom_meta_key', $meta_value);
    }
}

add_action('save_post', 'fxwp_save_custom_meta_box_data');


function fxwp_custom_fields_page()
{
    // Handle form submissions for adding post types
    if (isset($_POST['add_post_type'])) {
        $label = sanitize_text_field($_POST['post_type']);
        // generate slug from label , e.g. "My Post Type" => "my-post-type"
        $post_type = sanitize_title($label);
        $editor = isset($_POST['editor']) ? sanitize_text_field($_POST['editor']) : '';
        $thumbnail = isset($_POST['thumbnail']) ? sanitize_text_field($_POST['thumbnail']) : '';
        $menu_icon = sanitize_text_field($_POST['menu_icon']);

        // fxwp_post_types is an array of post types that we have already added
        // to avoid adding the same post type twice

        // check if post type already exists
        $post_types = get_option('fxwp_post_types', array());
        if (in_array($post_type, $post_types)) {
            echo '<div class="notice notice-error"><p>Post type ' . $post_type . ' already exists.</p></div>';
            return;
        }

        $supports = array('title');
        if ($editor) {
            $supports[] = 'editor';
        }
        if ($thumbnail) {
            $supports[] = 'thumbnail';
        }


        // add post type to array of post types
        $post_types[] = array(
            'post_type' => $post_type,
            'label' => $label,
            'public' => true,
            'show_in_menu' => true,
            'supports' => $supports,
            'menu_icon' => $menu_icon,
        );
        update_option('fxwp_post_types', $post_types);
        echo '<div class="notice notice-success"><p>Post type ' . $post_type . ' added successfully.</p></div>';
    }

    // Handle form submissions for adding custom fields
    if (isset($_POST['add_custom_field'])) {
        $post_type = sanitize_text_field($_POST['post_type_sel']);
        $field_name = sanitize_text_field($_POST['field_name']);
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_type = sanitize_text_field($_POST['field_type']);

        echo '<div class="notice notice-success"><p>Custom field ' . $field_name . ' for post type ' . $post_type . ' added successfully.</p></div>';

        $post_type_fields = get_option('fxwp_custom_fields', array());
        if (!isset($post_type_fields[$post_type])) {
            $post_type_fields[$post_type] = array();
        }
        $post_type_fields[$post_type][] = array(
            'field_name' => $field_name,
            'field_label' => $field_label,
            'field_type' => $field_type,
            'default_value' => '', // implement later
        );
        update_option('fxwp_custom_fields', $post_type_fields);

    }

    // Display the form to add post types
    ?>
    <div class="wrap">
        <h1>Add Post Type</h1>
        <form method="post" action="">
            <label for="post_type">Label:</label>
            <input type="text" id="post_type" name="post_type" required>
            <div>
                <label for="editor">Editor:</label>
                <input type="checkbox" id="editor" name="editor" value="editor">
            </div>
            <div>
                <label for="thumbnail">Thumbnail:</label>
                <input type="checkbox" id="thumbnail" name="thumbnail" value="thumbnail">
            </div>
            <div>
                <label for="menu_icon">Menu Icon:</label>
                <input type="text" id="menu_icon" name="menu_icon" required>
                <p style="font-size: 10px;">Hier findest du alle Icons: <a
                        href="https://developer.wordpress.org/resource/dashicons/#admin-post" target="_blank">https://developer.wordpress.org/resource/dashicons/#admin-post</a>
                </p>
            </div>
            <input type="submit" name="add_post_type" value="Add Post Type" class="button button-primary">
        </form>
    </div>
    <?php

    // Display the form to add custom fields for each post type
    ?>
    <div class="wrap">
        <h1>Add Custom Fields</h1>
        <form method="post" action="">
            <label for="post_type">Post Type:</label>
            <select id="post_type" name="post_type_sel">
                <?php
                // Get all registered post types
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $post_type) {
                    ?>
                    <option
                        value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->label); ?></option>
                    <?php
                }
                ?>
            </select>
            <br>
            <label for="field_name">Field Name:</label>
            <input type="text" id="field_name" name="field_name" required>
            <br>
            <label for="field_label">Field Label:</label>
            <input type="text" id="field_label" name="field_label" required>
            <br>
            <label for="field_type">Field Type:</label>
            <select id="field_type" name="field_type">
                <option value="text">Text</option>
                <option value="textarea">Textarea</option>
                <option value="checkbox">Checkbox</option>
                <option value="number">Number</option>
                <option value="date">Date</option>
                <option value="email">Email</option>
                <option value="url">URL</option>
                <!-- Add more field types as needed -->
            </select>
            <br>
            <input type="submit" name="add_custom_field" value="Add Custom Field" class="button button-primary">
        </form>
    </div>
    <?php

    // Display the page to edit custom fields for each post type
    ?>
    <div class="wrap">
        <h1>Edit Custom Fields</h1>
        <?php
        // Get all registered post types
        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $post_type) {
            ?>
            <h2><?php echo esc_html($post_type->label); ?></h2>
            <?php
            // Get all custom fields for the current post type
            $all_custom_fields = get_option('fxwp_custom_fields', array());
            $custom_fields = isset($all_custom_fields[$post_type->name]) ? $all_custom_fields[$post_type->name] : array();
            if (!empty($custom_fields)) {
                ?>
                <table class="widefat">
                    <thead>
                    <tr>
                        <th>Field Name</th>
                        <th>Field Label</th>
                        <th>Field Type</th>
                        <!-- Add more columns as needed -->
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($custom_fields as $field) {
                        // Exclude non-custom fields
                        if (strpos($field['field_name'], '_') !== 0) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($field['field_name']); ?></td>
                                <td><?php echo esc_html($field['field_label']); ?></td>
                                <td><?php echo esc_html($field['field_type']); ?></td>
                                <!-- Add more columns as needed -->
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>No custom fields found.</p>';
            }
        }
        ?>
    </div>
    <?php
}
