<?php
function fxwp_register_shortcodes()
{
    $fxwp_shortcodes = get_option('fxwp_shortcodes', array());

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

