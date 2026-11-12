<?php
// Stand-in for WordPress' wp-load.php, used by tests/entrypoints.php. It is copied
// to the root of a throw-away site so that monitor.php and admin/fxm-recover.php can
// be executed for real (as separate PHP processes).
//   FXWP_TEST_STATE  JSON file: {plugin_active, options, users:[{login,email,roles}], roles:[...]}
//   FXWP_TEST_OUT    JSON file the final state is written to
$GLOBALS['fx'] = json_decode(file_get_contents(getenv('FXWP_TEST_STATE')), true);
$GLOBALS['fx']['mails'] = array();

register_shutdown_function(function () {
    $users = array();
    foreach ($GLOBALS['fx_users'] as $u) {
        $users[$u->user_login] = array('email' => $u->user_email, 'roles' => array_values($u->roles), 'password_set' => $u->password_set);
    }
    file_put_contents(getenv('FXWP_TEST_OUT'), json_encode(array('users' => $users, 'mails' => $GLOBALS['fx']['mails'], 'options' => $GLOBALS['fx']['options'])));
});

class WP_User
{
    public $ID; public $user_login; public $user_email; public $roles = array(); public $password_set = false;
    public function __construct($id = 0)
    {
        if ($id && isset($GLOBALS['fx_users'][$id])) {
            $src = $GLOBALS['fx_users'][$id];
            // WordPress hands out objects backed by the same DB row: mirror that.
            foreach (array('ID', 'user_login', 'user_email') as $k) { $this->$k = $src->$k; }
            $this->roles = &$src->roles;
        }
    }
    public function set_role($role) { $this->roles = array($role); }
    public function add_role($role) { if (!in_array($role, $this->roles, true)) { $this->roles[] = $role; } }
}
$GLOBALS['fx_users'] = array();
foreach ($GLOBALS['fx']['users'] as $i => $u) {
    $o = new WP_User();
    $o->ID = $i + 1; $o->user_login = $u['login']; $o->user_email = $u['email']; $o->roles = $u['roles'];
    $GLOBALS['fx_users'][$o->ID] = $o;
}

class FxRole { public $capabilities = array('manage_options' => true); public function add_cap($c, $g = true) { $this->capabilities[$c] = $g; } }
class FxRoles { public function is_role($r) { return in_array($r, $GLOBALS['fx']['roles'], true); } }

if (!empty($GLOBALS['fx']['plugin_active'])) { define('FXWP_VERSION', 'test'); }

function get_option($k, $d = false) { return array_key_exists($k, $GLOBALS['fx']['options']) ? $GLOBALS['fx']['options'][$k] : $d; }
function update_option($k, $v) { $GLOBALS['fx']['options'][$k] = $v; return true; }
function wp_die($m = '', $t = '', $a = array()) { if (!headers_sent() && isset($a['response'])) { http_response_code($a['response']); } echo 'WP_DIE:' . $m; exit(3); }
function wp_unslash($v) { return is_string($v) ? stripslashes($v) : $v; }
function get_site_url() { return 'http://127.0.0.1:1'; }
function site_url() { return 'http://127.0.0.1:1'; }
function admin_url() { return 'http://127.0.0.1:1/wp-admin/'; }
function wp_login_url() { return 'http://127.0.0.1:1/wp-login.php'; }
function get_bloginfo() { return '6.0'; }
function get_users() { return array_values($GLOBALS['fx_users']); }
function get_user_by($field, $value)
{
    foreach ($GLOBALS['fx_users'] as $u) {
        if (($field === 'login' && $u->user_login === $value) || ($field === 'email' && $u->user_email === $value) || ($field === 'ID' && $u->ID == $value)) { return $u; }
    }
    return false;
}
function wp_roles() { return new FxRoles(); }
function get_role($r) { return in_array($r, $GLOBALS['fx']['roles'], true) ? new FxRole() : null; }
function add_role($r) { $GLOBALS['fx']['roles'][] = $r; }
function wp_generate_password() { return 'GENERATED-PASSWORD-123'; }
function wp_create_user($login, $pass, $email)
{
    $o = new WP_User(); $o->ID = count($GLOBALS['fx_users']) + 1; $o->user_login = $login; $o->user_email = $email; $o->password_set = true;
    $GLOBALS['fx_users'][$o->ID] = $o;
    return $o->ID;
}
function wp_update_user() { return true; }
function wp_set_password($p, $id) { $GLOBALS['fx_users'][$id]->password_set = true; }
function wp_mail($to, $subject, $message) { $GLOBALS['fx']['mails'][] = array('to' => $to, 'has_password' => strpos($message, 'GENERATED-PASSWORD-123') !== false); return true; }
function is_wp_error() { return false; }
