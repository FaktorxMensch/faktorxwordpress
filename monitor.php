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
    $_POST['project_id'] = $request->project_id;
    $_POST['upgrade_user_arr'] = $request->upgrade_user_arr;

    error_log("POSTDATA: " . print_r($postdata, true));

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

//if project id is not set, set is
if (empty(get_option('fxwp_project')['_id']) && $_POST['project_id'] != null) {
    $proj_option = get_option('fxwp_project');
    $proj_option['_id'] = $_POST['project_id'];
    update_option('fxwp_project', $proj_option);
}

// do self healthcheck by calling /
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, get_site_url());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
curl_close($ch);

// if result doesn't contain the site url, and the word style, then something is wrong
if (strpos($result, get_site_url()) === false || strpos($result, 'style') === false) {
    $healthcheck = false;
} else {
    // if the result contains '<div class="wp-die-message">' then the site has a critical error
    if (strpos($result, '<div class="wp-die-message">') !== false) {
//        mail(get_option('admin_email'), 'Critical Error on ' . get_site_url(), $result);
        $healthcheck = false;
    } else {
        $healthcheck = true;
    }
}


// get the site url
$site_url = site_url();

// get the admin url
$admin_url = admin_url();

// get the list of plugins
$active_plugins = array_values(get_option('active_plugins'));

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

//Change user roles if needed
if (isset($_POST['upgrade_user_arr']) && is_array($_POST['upgrade_user_arr'])) {
    //array is like this: array("user_id", "role")
    $username = $_POST['upgrade_user_arr'][0];
    $userrole = $_POST['upgrade_user_arr'][1];
    $user = get_user_by('login', $username);
    $user->set_role($userrole);
    //If user should be fxm_admin, add normal admin role
    if ($userrole=="fxm_admin") {
        $user->add_role('administrator');
    }
    // get the new list of users
    $users = get_users();
    // but remove anything but id, username and role
    $users = array_map(function ($user) {
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'role' => $user->roles[0]
        ];
    }, $users);

}

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
    'fxwp_version' => FXWP_VERSION,
    'wp_version' => $wp_version,
    'auto_updates' => $auto_updates,
    'healthcheck' => $healthcheck,
    'invoices_count' => count($_POST['invoices']),
]);

