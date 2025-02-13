<?php

/* Change logo from the administration bar */
function wphelp_change_admin_logo() {
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
}
add_action('wp_before_admin_bar_render', 'wphelp_change_admin_logo');

// Change login logo
function fxm_login_logo() { ?>
	<style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo plugins_url("assets/logo--footer-blue.svg", FXWP_PLUGIN_DIR.basename( FXWP_PLUGIN_DIR ))?>);
            height:65px;
            width:320px;
            background-size: 320px 65px;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
	</style>
<?php }
add_action( 'login_enqueue_scripts', 'fxm_login_logo' );

function fxm_login_logo_url() {
	return "https://faktorxmensch.com";
}
add_filter( 'login_headerurl', 'fxm_login_logo_url' );

function fxm_login_logo_url_title() {
	return 'Faktor Mensch MEDIA UG (haftungsbeschränkt)';
}
add_filter( 'login_headertext', 'fxm_login_logo_url_title' );
