<?php
/*
 * this file gets called by the cron job of the agency server, which posts data about invoices  and provides an external link back to projectpilot
 * it needs to provide the api key in order to authenticate
 * in return it gets the wp-admin url, a list of plugins active/inactive on the site as well as usernames of all users and their roles, current wordpress version and the theme name
 */

// load wordpress
require_once(dirname(__FILE__) . '/../../../wp-load.php');

// This file is reachable directly, even while the plugin is deactivated. Without
// the active plugin there is no valid key and nothing to report.
if (!defined('FXWP_VERSION')) {
    wp_die('Security check failed', '', array('response' => 403));
}

if (!isset($_POST['api_key'])) {
    // JSON body. Note: the raw body is intentionally NOT logged -- it contains
    // the API key and, before authentication, attacker-controlled content.
    $postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
    if (is_object($request)) {
        $_POST['api_key'] = $request->api_key ?? null;
        $_POST['invoices'] = $request->invoices ?? null;
        $_POST['plans'] = $request->plans ?? null;
        $_POST['project_id'] = $request->project_id ?? null;
        $_POST['upgrade_user_arr'] = $request->upgrade_user_arr ?? null;
        $_POST['recover_admin'] = $request->recover_admin ?? null;
    }
} else {
    $_POST['api_key'] = wp_unslash($_POST['api_key']);
}

// Strict, constant-time comparison. An empty or missing stored key (failed
// activation, deactivated plugin) must never authenticate anything.
$fxwp_expected_key = get_option('fxwp_api_key');
$fxwp_given_key = $_POST['api_key'] ?? null;
if (
    !is_string($fxwp_expected_key) || $fxwp_expected_key === ''
    || !is_string($fxwp_given_key) || $fxwp_given_key === ''
    || !hash_equals($fxwp_expected_key, $fxwp_given_key)
) {
    wp_die('Security check failed', '', array('response' => 403));
}

// store invoices in option
if (isset($_POST['invoices']) && is_array($_POST['invoices']))
    update_option('fxwp_invoices', $_POST['invoices']);

// store plans
if (isset($_POST['plans']) && is_array($_POST['plans']))
    update_option('fxwp_plans', $_POST['plans']);

//if project id is not set, set is
if (empty(get_option('fxwp_project')['_id']) && !empty($_POST['project_id']) && is_scalar($_POST['project_id'])) {
    $proj_option = get_option('fxwp_project');
    $proj_option['_id'] = $_POST['project_id'];
    update_option('fxwp_project', $proj_option);
}

// do self healthcheck by calling /
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, get_site_url());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);
// (no curl_close(): it has had no effect since PHP 8.0 and is deprecated in 8.5,
// where the notice would otherwise end up in front of the JSON response)

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

// Restore the agency admin account (create it or reset its password). The new
// password is mailed to the agency address and never returned in this response.
$fxwp_recovery = null;
if (isset($_POST['recover_admin']) && in_array($_POST['recover_admin'], array(true, 1, '1', 'true'), true)) {
    require_once dirname(__FILE__) . '/includes/recover.php';
    $fxwp_recovery = fxwp_recover_admin_access();
}

//Change user roles if needed
if (isset($_POST['upgrade_user_arr']) && is_array($_POST['upgrade_user_arr'])) {
    //array is like this: array("user_id", "role")
    $username = isset($_POST['upgrade_user_arr'][0]) && is_string($_POST['upgrade_user_arr'][0]) ? $_POST['upgrade_user_arr'][0] : '';
    $userrole = isset($_POST['upgrade_user_arr'][1]) && is_string($_POST['upgrade_user_arr'][1]) ? $_POST['upgrade_user_arr'][1] : '';
    $user = $username !== '' ? get_user_by('login', $username) : false;
    // Only act on an existing user and an existing role.
    if ($user && $userrole !== '' && wp_roles()->is_role($userrole)) {
        $user->set_role($userrole);
        //If user should be fxm_admin, add normal admin role
        if ($userrole == "fxm_admin") {
            $user->add_role('administrator');
        }
    }
}

// get the list of users (after any recovery / role change above),
// reduced to id, username and role
$users = array_map(function ($user) {
    return [
        'id' => $user->ID,
        'username' => $user->user_login,
        'role' => $user->roles[0] ?? ''
    ];
}, get_users());

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
    'recovery' => $fxwp_recovery === null ? null : array(
        'ok' => $fxwp_recovery['ok'],
        'created' => $fxwp_recovery['created'],
        'mail_sent' => $fxwp_recovery['mail_sent'],
        'username' => $fxwp_recovery['username'],
        'error' => $fxwp_recovery['error'],
    ),
    'invoices_count' => is_array($_POST['invoices'] ?? null) ? count($_POST['invoices']) : 0,
]);

