<?php
/*
Plugin Name: Faktor Mensch Werbebanner
Description: Zeigt einen riesigen Werbebanner im WordPress-Backend an.
Version: 1.0
Author: Dein Name
*/

add_action('admin_notices', 'faktor_mensch_admin_banner');

function faktor_mensch_admin_banner()
{
    // Nur auf dem Dashboard anzeigen (optional anpassen)
    $screen = get_current_screen();
    if (!isset($screen->id) || 'dashboard' !== $screen->id) {
        return;
    }
    // Hier den Pfad zum gewünschten Hintergrundbild eintragen:
    $background_image_url = plugin_dir_url(__FILE__) . '../assets/img/banner.jpeg';
    ?>
    <div id="fxm-banner" style="
            width: calc( 100% - 20px);
            height: 50vh;
            background: url('<?php echo esc_url($background_image_url); ?>') no-repeat center center;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: end;
            align-items: start;
            border-radius: 1em;
            color: #ffffff;
            gap: 1em;
            box-sizing: border-box;
            padding: 4em 4em;
            ">
        <h1 style="color: #fff; font-weight: bold; font-size: 3em; margin: 0 0 10px;display:flex;align-items: center">
            Willkommen bei
            <img src="<?php echo plugin_dir_url(__FILE__) . '../assets/logo_dark.svg'; ?>" alt="Faktor Mensch"
                 style="height: .85em;vertical-align: middle;margin-left: .3em;margin-top:-.06em;margin-right: .2em">
            !
        </h1>
        <p style="font-size: 1.5em; margin: 0 0 10px;">Wir begeistern Menschen für Medien.</p>
        <div style="display: flex;flex-wrap: wrap;gap: 1em;">
            <a href="https://faktorxmensch.com" target="_blank" class="fxm-btn">Über uns</a>
            <a href="https://faktorxmensch.com/portfolio" target="_blank" class="fxm-btn">Projekte</a>
            <a href="https://faktorxmensch.com/agentur#team" target="_blank" class="fxm-btn">Das Team</a>
            <!-- support -->
            <a href="https://faktorxmensch.com/support" target="_blank" class="fxm-btn">Support kontaktieren</a>
        </div>
        <style>
            #fxm-banner .fxm-btn {
                padding: .75em 1em;
                background: white;
                text-decoration: none;
                color: #000;
                font-size: 1.4em;
                border-radius: 2em;
                transition: .3s;
                opacity: .8;
            }

            #fxm-banner .fxm-btn::after {
                content: '\203A';
                margin-left: .3em;
            }

            #fxm-banner .fxm-btn:hover {
                transform: scale(1.03);
                opacity: 1;
                outline: 3px solid #fff6;
                box-shadow: 0 0 0 3px hsla(0, 0%, 100%, .4), 0 0 30px 4px rgba(198, 32, 129, .4);
            }

        </style>
    </div>
    <?php
}
