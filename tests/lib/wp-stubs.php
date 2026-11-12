<?php
// Just enough WordPress to load the plugin's files and call single functions.
// State lives in $GLOBALS['wp'] so each test can set it up and inspect it.
if (PHP_SAPI !== 'cli') { exit; }

class WpDie extends Exception {}          // wp_die(), check_admin_referer() failure
class WpJson extends Exception            // wp_send_json_*()
{
    public $success;
    public $data;
    public $status;
    public function __construct($success, $data, $status)
    {
        parent::__construct($success ? 'json success' : 'json error');
        $this->success = $success;
        $this->data = $data;
        $this->status = $status;
    }
}

function wp_reset(): void
{
    $GLOBALS['wp'] = array(
        'options' => array(),
        'caps' => array(),          // capabilities of the current user
        'valid_nonces' => array(),  // action names whose nonce is accepted
        'calls' => array(),         // recorded side effects
    );
    $_GET = $_POST = $_REQUEST = array();
}
wp_reset();

if (!defined('ABSPATH')) define('ABSPATH', sys_get_temp_dir() . '/fxwp-test-abspath/');
if (!defined('DAY_IN_SECONDS')) define('DAY_IN_SECONDS', 86400);
if (!defined('HOUR_IN_SECONDS')) define('HOUR_IN_SECONDS', 3600);
if (!defined('WEEK_IN_SECONDS')) define('WEEK_IN_SECONDS', 604800);
if (!defined('MINUTE_IN_SECONDS')) define('MINUTE_IN_SECONDS', 60);

function get_option($k, $d = false) { return array_key_exists($k, $GLOBALS['wp']['options']) ? $GLOBALS['wp']['options'][$k] : $d; }
function update_option($k, $v, $autoload = null) { $changed = !array_key_exists($k, $GLOBALS['wp']['options']) || $GLOBALS['wp']['options'][$k] !== $v; $GLOBALS['wp']['options'][$k] = $v; return $changed; }
function add_option($k, $v = '') { if (array_key_exists($k, $GLOBALS['wp']['options'])) return false; $GLOBALS['wp']['options'][$k] = $v; return true; }
function delete_option($k) { unset($GLOBALS['wp']['options'][$k]); return true; }

function current_user_can($cap) { return in_array($cap, $GLOBALS['wp']['caps'], true); }
function wp_die($msg = '', $title = '', $args = array()) { throw new WpDie(is_string($msg) ? $msg : 'wp_die'); }
function check_admin_referer($action = -1, $arg = '_wpnonce') { if (!in_array($action, $GLOBALS['wp']['valid_nonces'], true)) throw new WpDie("nonce:$action"); return 1; }
function check_ajax_referer($action = -1, $arg = false, $die = true) { $okay = in_array($action, $GLOBALS['wp']['valid_nonces'], true); if (!$okay && $die) throw new WpDie("nonce:$action"); return $okay ? 1 : false; }
function wp_verify_nonce($nonce, $action = -1) { return in_array($action, $GLOBALS['wp']['valid_nonces'], true) ? 1 : false; }
function wp_create_nonce($a = -1) { return 'nonce-' . $a; }
function wp_nonce_field($a = -1, $n = '_wpnonce', $r = true, $echo = true) { $f = '<input type="hidden" id="' . $n . '" name="' . $n . '" value="nonce">'; if ($echo) echo $f; return $f; }
function wp_nonce_url($url, $a = -1) { return $url . '&_wpnonce=nonce'; }
function wp_send_json_error($data = null, $status = null) { throw new WpJson(false, $data, $status); }
function wp_send_json_success($data = null, $status = null) { throw new WpJson(true, $data, $status); }

function add_action(...$a) {}
function add_filter(...$a) {}
function remove_action(...$a) {}
function remove_filter(...$a) {}
function apply_filters($tag, $value) { return $value; }
function do_action(...$a) {}
function register_rest_route(...$a) { $GLOBALS['wp']['calls'][] = array('register_rest_route', $a); }
function wp_next_scheduled() { return true; }
function wp_schedule_event() {}
function wp_schedule_single_event() {}

function wp_unslash($v) { return is_string($v) ? stripslashes($v) : $v; }
function sanitize_text_field($s) { return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags((string)$s))); }
function sanitize_textarea_field($s) { return trim(strip_tags((string)$s)); }
function sanitize_email($s) { return trim((string)$s); }
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function esc_js($s) { return addslashes((string)$s); }
function esc_url($s) { return (string)$s; }
function esc_url_raw($s) { return (string)$s; }
function esc_html__($s, $d = null) { return $s; }
function esc_attr__($s, $d = null) { return $s; }
function __($s, $d = null) { return $s; }
function _e($s, $d = null) { echo $s; }
function wp_json_encode($v, $f = 0) { return json_encode($v, $f); }
function trailingslashit($s) { return rtrim($s, '/\\') . '/'; }
function plugin_dir_path($f) { return trailingslashit(dirname($f)); }
function plugin_dir_url($f) { return 'https://example.test/wp-content/plugins/faktorxwordpress/'; }
function plugins_url($p = '', $f = '') { return 'https://example.test/wp-content/plugins/' . ltrim($p, '/'); }
function admin_url($p = '') { return 'https://example.test/wp-admin/' . $p; }
function get_admin_url() { return 'https://example.test/wp-admin/'; }
function get_site_url() { return 'https://example.test'; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function current_time($f) { return date($f); }
function get_gmt_from_date($s, $f = 'Y-m-d H:i:s') { return date($f, strtotime($s . ' UTC')); }
function get_bloginfo() { return 'Test'; }
function is_wp_error($v) { return false; }
function wp_mail(...$a) { $GLOBALS['wp']['calls'][] = array('wp_mail', $a); return true; }
function error_log_stub() {}
function esc_textarea($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function get_admin_page_title() { return 'Test'; }
function submit_button($t = 'Save') { echo '<input type="submit" value="' . esc_attr($t) . '">'; }
