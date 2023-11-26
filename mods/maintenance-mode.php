<?php

// Widget display callback function
function display_maintenance_mode()
{
    if (!is_user_logged_in()) {
        $maintenance_mode = get_option('maintenance_mode', 'none'); // 'none', 'coming_soon', 'maintenance_mode

echo '
   <meta charset="utf-8"> <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        text-align: center;
        padding: 50px;
        font-size: 1.4em;
    }

    p {
        font-size: 2em;
        max-width: 20em;
        margin: 50px auto;
    }

    a {
        text-decoration: none;
        color: #000;
        font-weight: bolder;
        transition: .1s;
        display: inline-block;
        border-bottom: 2px solid transparent;
    }

    a:hover {
        border-bottom-color: orange;
    }

    a span {
        color: orange;
    }
</style>
<title>Wartungsarbeiten</title>
';

        if ($maintenance_mode == 'maintenance_mode') {
            echo '<p>' . __('Wir arbeiten gerade an dieser Website.<br> Bitte besuchen Sie uns später wieder.', 'text-domain') . '</p>';
        } else if ($maintenance_mode == 'coming_soon') {
            echo '<p>' . __('Wir arbeiten gerade an dieser Seite.<br> Besuchen Sie uns gerne später wieder.', 'text-domain') . '</p>';
        } else {
            return;
        }

        echo '<div> Ein Projekt mit <a target="_blank" href="https://faktorxmensch.com">FAKTOR<span>&times;</span>MENSCH</a>. </div>';

        die(); // Stop further execution
    }
}

// Register the widget
function register_maintenance_mode()
{
    add_action('template_redirect', 'display_maintenance_mode');
}

add_action('wp', 'register_maintenance_mode');
