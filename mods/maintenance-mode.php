<?php

// Widget display callback function
function display_maintenance_mode()
{
    if (!is_user_logged_in()) {
        $maintenance_mode = get_option('maintenance_mode', 'none'); // 'none', 'coming_soon', 'maintenance_mode

        $font_url = plugins_url('../assets/Inter-Medium.ttf', __FILE__);
        $font_bold_url = plugins_url('../assets/Inter-Bold.ttf', __FILE__);

        echo '
   <meta charset="utf-8"> <style>
   
    @font-face {
        font-family: "Inter";
        src: url("' . $font_url . '") format("truetype");
        font-weight: normal;
        font-style: normal;
    }
    
    @font-face {
        font-family: "Inter";
        src: url("' . $font_bold_url . '") format("truetype");
        font-weight: bold;
        font-style: normal;
    }
    
    body {
        font-family:Inter,  Arial, Helvetica, sans-serif;
        text-align: center;
        padding: 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    
    img {
        max-width: 100%;
        margin-bottom: 30px;
        margin-top: 10px;   
        display: block;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,.2);
    }

    p {
        font-size: 1.9em;
        max-width: 20em;
        margin: 20px auto;
    }

    a {
        text-decoration: none;
        color: #000;
        font-weight: bold;
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

        echo '<img src="' . plugins_url('../assets/img/maintenance1.jpeg', __FILE__) . '" class=img alt="Logo" height="400">';

        echo '<div style="margin-bottom:80px"> Ein Projekt mit <a target="_blank" href="https://faktorxmensch.com">FAKTOR<span>&times;</span>MENSCH</a>. </div>';

        die(); // Stop further execution
    }
}

// Register the widget
function register_maintenance_mode()
{
    add_action('template_redirect', 'display_maintenance_mode');
}

add_action('wp', 'register_maintenance_mode');
