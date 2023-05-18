<?php
function fxwp_render_view_option()
{
    if (get_option('fxwp_view_option', 'einfach') === 'erweitert' || current_user_can('erweiterte_ansicht')) {
        // Code to display in Erweiterte Ansicht or for users with erweiterte_ansicht capability
        echo '<style>
        .hide_simple, .hide_advanced {
            display:none !important;
        }
        </style>';
        // ...
    } else {
        // Code to display in Einfache Ansicht
        echo '<style>
        .hide_simple {
            display:none !important;
        }
        </style>';
    }
}

// only in the backend
if (is_admin()) {
    add_action('admin_head', 'fxwp_render_view_option');
}