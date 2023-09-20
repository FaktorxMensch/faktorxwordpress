<?php
function fxwp_register_shortcodes()
{
    $fxwp_shortcodes = get_option('fxwp_shortcodes', array());

    // Check if the 'fxwp-menu' shortcode is already registered
    $fxwp_menu_shortcode_exists = false;

    foreach ($fxwp_shortcodes as $shortcode_data) {
        if ($shortcode_data['tag'] === 'fxwp-menu') {
            $fxwp_menu_shortcode_exists = true;
            break;
        }
    }

    if (!$fxwp_menu_shortcode_exists) {
        // Define the attributes and code for the 'fxwp-menu' shortcode
        $menu_shortcode_data = array(
            'tag' => 'fxwp-menu',
            'attributes' => array(
                array('name' => 'name', 'description' => 'menu name', 'default' => ''),
            ),
            'description' => 'FXM menu Shortcode for adding menus into the page',
            'code' => '<?php
    $menu_name = $atts["name"];
    $menu = wp_get_nav_menu_object($menu_name);

    if ($menu) {
        $menu_args = array(
            "menu" => $menu_name,
            "menu_class" => "extra-menu footer-menu list-none", // Customize the CSS class
            "echo" => true // Display the menu immediately
        );

        wp_nav_menu($menu_args);
    } else {
        echo "Menu not found.";
    }
?>',
        );

        // Add the 'fxwp-menu' shortcode data to the options array
        $fxwp_shortcodes[] = $menu_shortcode_data;

        // Update the options in the database
        update_option('fxwp_shortcodes', $fxwp_shortcodes);
    }

    foreach ($fxwp_shortcodes as $shortcode_data) {
        $shortcode_tag = $shortcode_data['tag'];
        $attributes = $shortcode_data['attributes'];

        $callback = function ($atts) use ($shortcode_tag, $shortcode_data) {
            // mape $attributes to$shortcode_data['defaults'], $attribuces has the keys default and name
            $pairs = [];
            foreach ($shortcode_data['attributes'] as $attribute) {
                $pairs[$attribute['name']] = $attribute['default'];
            }

            $atts = shortcode_atts($pairs, $atts, $shortcode_tag);

            // unescape "
            $dangerous_code = str_replace('\\"', '"', $shortcode_data['code']);

            ob_start();
            eval('?>' . $dangerous_code . '<?php ');
            $output = ob_get_clean();

            return $output;
        };

        add_shortcode($shortcode_tag, $callback);
    }
}

add_action('init', 'fxwp_register_shortcodes');

