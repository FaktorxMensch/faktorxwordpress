<?php

/* Change logo from the administration bar */
function wphelp_change_admin_logo()
{
    echo '
<style type="text/css">
#wpadminbar #wp-admin-bar-wp-logo > a {
background-image: url(' . plugins_url("assets/logo--rounded-light.svg", FXWP_PLUGIN_DIR . basename(FXWP_PLUGIN_DIR)) . ') !important;
background-position: -2px -2px;
background-size: 110%;
color:rgba(0, 0, 0, 0);
}
#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
content: none;
}

#wpadminbar #wp-admin-bar-wp-logo > a:hover {
background-size: 110% !important;
background-position: -2px -2px !important;
}
</style>
';

    // if its a local instance, have div#wpadminbar be orange
    if (FXWP_LOCAL_ENV) {
        echo '<style>
            #wpadminbar #wp-admin-bar-wp-logo > a {
                background-image: url(' . plugins_url("assets/p2.png", FXWP_PLUGIN_DIR . basename(FXWP_PLUGIN_DIR)) . ') !important;
                background-size: 90%;
                background-repeat: no-repeat;
                background-position: 0.5px 2px;
            }
        </style>';
    }

    // as long as were in wp-admin also change wpadmin
    if (is_admin()) {
//                background:#049a42 100% !important;
        echo '<style>
            #wpadminbar {
                background-image: linear-gradient(90deg, #BA38F8 0%, #39B9FF 50%, #00DC5E 100%) !important;
            }
        </style>';
    }
}

add_action('wp_before_admin_bar_render', 'wphelp_change_admin_logo');

// Change login logo
function fxm_login_logo()
{ ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo plugins_url("assets/logo--footer-blue.svg", FXWP_PLUGIN_DIR.basename( FXWP_PLUGIN_DIR ))?>);
            height: 65px;
            width: 320px;
            background-size: 320px 65px;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>
<?php }

add_action('login_enqueue_scripts', 'fxm_login_logo');

function fxm_login_logo_url()
{
    return "https://faktorxmensch.com";
}

add_filter('login_headerurl', 'fxm_login_logo_url');

function fxm_login_logo_url_title()
{
    return 'Faktor Mensch MEDIA UG (haftungsbeschränkt)';
}

add_filter('login_headertext', 'fxm_login_logo_url_title');
