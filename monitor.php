<?php
/*
 * this file gets called by the cron job of the agency server, which posts data about invoices  and provides an external link back to projectpilot
 * it needs to provide the api key in order to authenticate
 * in return it gets the wp-admin url, a list of plugins active/inactive on the site as well as usernames of all users and their roles, current wordpress version and the theme name
 */

// load wordpress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

//$_POST['api_key'] = get_option('fxwp_api_key');
if (!isset($_POST['api_key'])) {
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    $_POST['api_key'] = $request->api_key;
    $_POST['invoices'] = $request->invoices;
    $_POST['plans'] = $request->plans;
}
if (!isset($_POST['api_key']) || $_POST['api_key'] != get_option('fxwp_api_key')) {
    wp_die('Security check failed');
}

// store invoices in option
if (isset($_POST['invoices']) && is_array($_POST['invoices']))
    update_option('fxwp_invoices', $_POST['invoices']);

// store plans
if (isset($_POST['plans']) && is_array($_POST['plans']))
    update_option('fxwp_plans', $_POST['plans']);

// do self healthcheck by calling /
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, get_site_url());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);

// if result doesnt contain the site url, and the word style, then something is wrong
if (strpos($result, get_site_url()) === false || strpos($result, 'style') === false) {
    $healthcheck = false;
} else {
    $healthcheck = true;
}


// get the site url
$site_url = site_url();

// get the admin url
$admin_url = admin_url();

// get the list of plugins
$active_plugins = get_option('active_plugins');

// get the list of users
$users = get_users();
// but remove anything but id, username and role
$users = array_map(function ($user) {
    return [
        'id' => $user->ID,
        'username' => $user->user_login,
        'role' => $user->roles[0]
    ];
}, $users);

// get the current wordpress version
$wp_version = get_bloginfo('version');

// check if auto updates are enabled
$auto_updates = get_option('fxwp_auto_updates');

// return the data as json
header('Content-Type: application/json');
echo json_encode([
    'site_url' => $site_url,
    'admin_url' => $admin_url,
    'active_plugins' => $active_plugins,
    'users' => $users,
    'wp_version' => $wp_version,
    'auto_updates' => $auto_updates,
    'healthcheck' => $healthcheck,
    'invoices_count' => count($_POST['invoices']),
]);

