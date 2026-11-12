<?php
/**
 * Unit tests: single plugin functions, run against WordPress stubs.
 * Run:  php tests/unit.php
 */
if (PHP_SAPI !== 'cli') { exit; }

require __DIR__ . '/lib/harness.php';
require __DIR__ . '/lib/wp-stubs.php';

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fxwp-unit-' . getmypid();
@mkdir($tmp, 0777, true);
define('WP_CONTENT_DIR', $tmp . '/wp-content');
@mkdir(WP_CONTENT_DIR, 0777, true);
define('FXWP_VERSION', 'test');
define('FXWP_API_URL', 'https://api.example.test/api/fxwp');
define('FXWP_P2_URL', 'https://p2.example.test');
define('FXWP_LOCAL_ENV', false);
define('FXWP_BACKUP_INTERVAL', 'twicedaily');
define('FXWP_BACKUP_DAYS_SON', 3);
define('FXWP_BACKUP_DAYS_FATHER', 12);
define('FXWP_BACKUP_DAYS_GRANDFATHER', 90);
define('FXWP_ERROR_EMAIL', 'errors@example.test');

function get_site_transient() { return false; }
function fxm_plugin_updater($tag, $debug = null) { $GLOBALS['wp']['calls'][] = array('fxm_plugin_updater', $tag, $debug); }
function fxm_do_this_hourly() { $GLOBALS['wp']['calls'][] = array('fxm_do_this_hourly'); }
function fxwp_install_theme() { $GLOBALS['wp']['calls'][] = array('fxwp_install_theme'); }
function calls(string $name): array { return array_values(array_filter($GLOBALS['wp']['calls'], function ($c) use ($name) { return $c[0] === $name; })); }
/** Runs $fn, discards what it prints, returns the printed text. */
function capture(callable $fn): string { ob_start(); try { $fn(); } finally { $out = ob_get_clean(); } return $out; }

require $root . '/includes/options-config.php';
require $root . '/includes/helpers.php';
require $root . '/mods/mail-log-mod.php';
require $root . '/widgets/maintenance-mode-widget.php';
require $root . '/admin/sites/options.php';
require $root . '/admin/sites/shortcodes.php';
require $root . '/admin/sites/plugin-list-installer.php';
require $root . '/admin/sites/topic.php';
require $root . '/widgets/debug-log-widget.php';
require $root . '/widgets/topics_widget.php';
require $root . '/mods/customer-update-dashboard.php';
require $root . '/cron/backups-cron.php';
$restRoutesAfterLoad = calls('register_rest_route');

echo "\nE-Mail-Protokoll: Geheimnisse schwärzen\n";
t('Reset-Link: key wird entfernt, Rest bleibt lesbar', function () {
    $out = fxwp_redact_mail_secrets("Klick: https://x.de/wp-login.php?action=rp&key=AbC123xyz&login=ema\n");
    ok(strpos($out, 'AbC123xyz') === false, 'key still present');
    ok(strpos($out, 'action=rp&key=[entfernt]&login=ema') !== false, $out);
});
t('HTML-Mail: &amp;key= und token= werden entfernt', function () {
    $out = fxwp_redact_mail_secrets('<a href="https://x.de/?a=1&amp;key=SECRET1">x</a> https://x.de/?token=SECRET2');
    ok(strpos($out, 'SECRET1') === false && strpos($out, 'SECRET2') === false, $out);
});
t('Passwort-Zeilen (de/en) werden entfernt', function () {
    $out = fxwp_redact_mail_secrets("Benutzername: fxm\nPasswort: s3cr3t!x\nPassword:   abc def\n");
    ok(strpos($out, 's3cr3t') === false && strpos($out, 'abc def') === false, $out);
    ok(strpos($out, 'Benutzername: fxm') !== false, 'username line must stay');
});
t('Harmloser Text bleibt unverändert', function () {
    $text = "Hallo, monkey=1 ist kein Schlüssel. Ihr Passwort wurde geändert.\n";
    eq($text, fxwp_redact_mail_secrets($text));
    eq('', fxwp_redact_mail_secrets(''));
    eq(null, fxwp_redact_mail_secrets(null));
});
t('Die Recovery-Mail wird im Protokoll geschwärzt', function () use ($root) {
    // the exact line format used by includes/recover.php
    ok(strpos(file_get_contents($root . '/includes/recover.php'), '"Passwort: {$password}\n') !== false, 'recover.php mail format changed -- update fxwp_redact_mail_secrets and this test');
});

echo "\nBackup-Ordner (zufälliger Name, Migration)\n";
$case = function (string $abspath, bool $active) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/lib/backup-dir-case.php') . ' ' . escapeshellarg($abspath) . ' ' . ($active ? '1' : '0');
    $json = json_decode((string)shell_exec($cmd), true);
    ok(is_array($json), 'helper produced no JSON');
    return $json;
};
t('Alter Ordner wird samt Inhalt in einen zufällig benannten umbenannt', function () use ($tmp, $case) {
    $abs = $tmp . '/site-a';
    mkdir($abs . '/wp-content/fxwp-backups', 0777, true);
    touch($abs . '/wp-content/fxwp-backups/backup_2026-01-01_00-00-00.zip');
    $r = $case($abs, false);
    ok(preg_match('#/wp-content/fxwp-backups-[0-9a-f]{20}/$#', $r['dir']) === 1, $r['dir']);
    ok(is_file($r['dir'] . 'backup_2026-01-01_00-00-00.zip'), 'backup was not moved');
    ok(!is_dir($abs . '/wp-content/fxwp-backups'), 'legacy dir still exists');
    ok($r['stable'], 'second call returned another dir');
    eq($r['dir'], $case($abs, false)['dir'], 'next request must find the same directory');
});
t('Während ein Backup läuft, wird nicht umbenannt', function () use ($tmp, $case) {
    $abs = $tmp . '/site-b';
    mkdir($abs . '/wp-content/fxwp-backups', 0777, true);
    $r = $case($abs, true);
    eq($abs . '/wp-content/fxwp-backups/', $r['dir']);
    ok(is_dir($abs . '/wp-content/fxwp-backups'));
});
t('Neue Seite ohne Backups bekommt direkt einen zufälligen Ordnernamen', function () use ($tmp, $case) {
    $abs = $tmp . '/site-c';
    mkdir($abs . '/wp-content', 0777, true);
    ok(preg_match('#/fxwp-backups-[0-9a-f]{20}/$#', $case($abs, false)['dir']) === 1);
});
t('Der Backup-Ordner wird nie mitgesichert', function () {
    ok(fxwp_backup_is_excluded('wp-content/fxwp-backups-0123456789abcdef0123/backup_x.zip'));
    ok(fxwp_backup_is_excluded('wp-content/fxwp-backups/backup_x.zip'));
    ok(fxwp_backup_is_excluded('wp-content/cache/x.php'));
    ok(!fxwp_backup_is_excluded('wp-content/plugins/my-cache-plugin/x.php'));
    ok(!fxwp_backup_is_excluded('wp-content/uploads/2026/01/bild.jpg'));
});

echo "\nSelbst-Update (index.php?fxwp_sync=1)\n";
$sync = function (array $caps, array $nonces, array $post) {
    wp_reset();
    $GLOBALS['wp']['caps'] = $caps;
    $GLOBALS['wp']['valid_nonces'] = $nonces;
    $_GET['fxwp_sync'] = '1';
    $_POST = $post;
    fxwp_url_actions();
};
t('Ohne fxwp_sync passiert nichts', function () {
    wp_reset();
    fxwp_url_actions();
    eq(0, count($GLOBALS['wp']['calls']));
});
t('Abonnent (ohne update_plugins) wird abgewiesen', function () use ($sync) {
    throws('WpDie', function () use ($sync) { $sync(array('read'), array('fxwp_sync'), array('fxwp_self_update_tag' => 'v2.9.3')); });
    eq(0, count(calls('fxm_plugin_updater')));
});
t('Ohne Nonce (CSRF) wird abgewiesen', function () use ($sync) {
    throws('WpDie', function () use ($sync) { $sync(array('update_plugins'), array(), array('fxwp_self_update_tag' => 'v2.9.3')); });
    eq(0, count(calls('fxm_plugin_updater')) + count(calls('fxm_do_this_hourly')));
});
t('Gültige Versions-Tags werden installiert', function () use ($sync) {
    foreach (array('v2.9.3', '2.9.3', 'v2.10', 'v3.0.0-beta.1') as $tag) {
        $sync(array('update_plugins'), array('fxwp_sync'), array('fxwp_self_update_tag' => $tag));
        eq(array(array('fxm_plugin_updater', $tag, true)), calls('fxm_plugin_updater'), $tag);
    }
    $sync(array('update_plugins'), array('fxwp_sync'), array('fxwp_custom_update_tag' => ' v2.8.0 '));
    eq(array(array('fxm_plugin_updater', 'v2.8.0', true)), calls('fxm_plugin_updater'));
});
t('Manipulierte Tags (Pfade, URLs, Branches) werden abgewiesen', function () use ($sync) {
    $bad = array('../../../../evil/repo/archive/refs/heads/main', 'v2.9.3/../../x', 'main', 'https://evil.test/x', 'v2.9.3?x=1', 'v2.9.3 ; rm', '', 'v2', '..');
    foreach ($bad as $tag) {
        throws('WpDie', function () use ($sync, $tag) { $sync(array('update_plugins'), array('fxwp_sync'), array('fxwp_self_update_tag' => $tag)); });
        eq(0, count(calls('fxm_plugin_updater')), "tag accepted: $tag");
    }
});
t('Ohne Tag läuft das normale Update', function () use ($sync) {
    $sync(array('update_plugins'), array('fxwp_sync'), array());
    eq(1, count(calls('fxm_do_this_hourly')));
});

echo "\nOptions-Panel (AJAX)\n";
$save = function (array $caps, array $nonces, array $post) {
    wp_reset();
    $GLOBALS['wp']['caps'] = $caps;
    $GLOBALS['wp']['valid_nonces'] = $nonces;
    $GLOBALS['wp']['options']['fxwp_shortcodes'] = 'ORIGINAL';
    $_POST = $post;
    return throws('WpJson', 'fx_plugin_save_option');
};
$fxm = array('fxm_admin', 'manage_options');
t('Normaler Administrator (kein fxm_admin) darf nicht speichern', function () use ($save) {
    $r = $save(array('manage_options', 'administrator'), array('fx_plugin_options'), array('option_key' => 'fxwp_deact_ai', 'option_value' => 'true'));
    eq(false, $r->success);
    eq(false, get_option('fxwp_deact_ai', false));
});
t('Ohne Nonce (CSRF) wird nicht gespeichert', function () use ($save, $fxm) {
    $r = $save($fxm, array(), array('option_key' => 'fxwp_deact_ai', 'option_value' => 'true'));
    eq(false, $r->success);
    eq(false, get_option('fxwp_deact_ai', false));
});
t('Fremde Optionen (Shortcode-PHP, Rollen, Registrierung) sind nicht schreibbar', function () use ($save, $fxm) {
    foreach (array('fxwp_shortcodes', 'wp_user_roles', 'default_role', 'users_can_register', 'siteurl', 'active_plugins', '') as $key) {
        $r = $save($fxm, array('fx_plugin_options'), array('option_key' => $key, 'option_value' => 'x'));
        eq(false, $r->success, $key);
        eq('ORIGINAL', get_option('fxwp_shortcodes'));
        ok($key === 'fxwp_shortcodes' || $key === '' || get_option($key, '__unset') === '__unset', "$key was written");
    }
});
t('Aktionen und Anzeige-Felder sind keine schreibbaren Optionen', function () {
    $w = fx_plugin_writable_options();
    foreach (array('fxwp_debugging_write', 'fxwp_api_key_renew', 'fxwp_local_instance', 'fxwp_customer_json') as $key) {
        ok(!isset($w[$key]), "$key must not be writable");
    }
    ok(isset($w['fxwp_deact_ai']) && isset($w['fxwp_storage_limit']) && isset($w['fxwp_backup_interval']));
});
t('Array-Werte werden abgelehnt', function () use ($save, $fxm) {
    $r = $save($fxm, array('fx_plugin_options'), array('option_key' => 'fxwp_deact_ai', 'option_value' => array('a' => 'b')));
    eq(false, $r->success);
});
t('fxm_admin mit Nonce speichert eine Panel-Option (true/false als Boolean)', function () use ($save, $fxm) {
    $r = $save($fxm, array('fx_plugin_options'), array('option_key' => 'fxwp_deact_ai', 'option_value' => 'true'));
    eq(true, $r->success);
    eq(true, get_option('fxwp_deact_ai'));
    $r = $save($fxm, array('fx_plugin_options'), array('option_key' => 'cud_notify_email', 'option_value' => 'a@b.de'));
    eq(true, $r->success);
    eq('a@b.de', get_option('cud_notify_email'));
});
t('Optionen lesen und Aktionen ausführen nur für fxm_admin mit Nonce', function () {
    foreach (array('fx_plugin_get_options', 'fx_plugin_execute_action') as $fn) {
        wp_reset();
        $GLOBALS['wp']['caps'] = array('manage_options');
        $GLOBALS['wp']['valid_nonces'] = array('fx_plugin_options');
        eq(false, throws('WpJson', $fn)->success, "$fn without fxm_admin");
        wp_reset();
        $GLOBALS['wp']['caps'] = array('fxm_admin');
        eq(false, throws('WpJson', $fn)->success, "$fn without nonce");
    }
});
t('Die Konfiguration (Kundendaten) wird nur fxm_admin auf der Panel-Seite ausgegeben', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('manage_options');
    $_GET['page'] = 'fxwp-options';
    eq('', capture('fx_plugin_localize_config'));
    wp_reset();
    $GLOBALS['wp']['caps'] = array('fxm_admin');
    eq('', capture('fx_plugin_localize_config'), 'other admin pages must not get the config');
    $_GET['page'] = 'fxwp-options';
    ok(strpos(capture('fx_plugin_localize_config'), 'fxPluginConfig') !== false);
});

echo "\nShortcodes (gespeichertes PHP, eval)\n";
$shortcodePost = array('fxwp_shortcode_tag' => 'evil', 'fxwp_shortcode_description' => 'd', 'fxwp_shortcode_code' => '<?php system("id"); ?>');
t('Ohne Nonce (CSRF) wird kein PHP gespeichert', function () use ($shortcodePost) {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('manage_options');
    $_POST = $shortcodePost;
    throws('WpDie', function () { capture('fxwp_display_shortcode_form'); });
    eq('__unset', get_option('fxwp_shortcodes', '__unset'));
});
t('Ohne manage_options wird kein PHP gespeichert', function () use ($shortcodePost) {
    wp_reset();
    $GLOBALS['wp']['valid_nonces'] = array('fxwp_shortcode_nonce');
    $_POST = $shortcodePost;
    throws('WpDie', function () { capture('fxwp_display_shortcode_form'); });
    eq('__unset', get_option('fxwp_shortcodes', '__unset'));
});
t('Mit Recht und Nonce wird der Shortcode gespeichert', function () use ($shortcodePost) {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('manage_options');
    $GLOBALS['wp']['valid_nonces'] = array('fxwp_shortcode_nonce');
    $_POST = $shortcodePost;
    capture('fxwp_display_shortcode_form');
    eq('evil', get_option('fxwp_shortcodes')[0]['tag']);
});
t('Die Shortcode-Liste gibt Beschreibungen escaped aus', function () {
    wp_reset();
    $GLOBALS['wp']['options']['fxwp_shortcodes'] = array(array('tag' => 't', 'attributes' => array(), 'description' => '<script>alert(1)</script>', 'code' => ''));
    ok(strpos(capture('fxwp_display_settings_page'), '<script>alert(1)</script>') === false);
});

echo "\nInstallationshilfe\n";
t('Installieren ohne Nonce (CSRF) wird abgewiesen', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('install_plugins');
    $_POST = array('fxwp_install_theme' => 'true');
    throws('WpDie', function () { capture('fxwp_plugin_list_installer_page'); });
    eq(0, count(calls('fxwp_install_theme')));
});
t('Installieren ohne install_plugins wird abgewiesen', function () {
    wp_reset();
    $GLOBALS['wp']['valid_nonces'] = array('fxwp_installer');
    $_POST = array('fxwp_install_theme' => 'true');
    throws('WpDie', function () { capture('fxwp_plugin_list_installer_page'); });
    eq(0, count(calls('fxwp_install_theme')));
});
t('Mit Recht und Nonce wird installiert; jedes Formular trägt die Nonce', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('install_plugins');
    $GLOBALS['wp']['valid_nonces'] = array('fxwp_installer');
    $_POST = array('fxwp_install_theme' => 'true');
    $html = capture('fxwp_plugin_list_installer_page');
    eq(1, count(calls('fxwp_install_theme')));
    // The first form of each collection sits behind a tag with a broken style
    // attribute and never worked; every working form must carry the nonce.
    eq(substr_count($html, '<form method="post">'), substr_count($html, 'name="_fxwp_installer_nonce"'));
});

echo "\nSchreibwerkstatt (abgeschaltet)\n";
t('Die Seite zeigt den Hinweis und nie den API-Key', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('manage_options', 'fxm_admin');
    $GLOBALS['wp']['options']['fxwp_api_key'] = 'SUPERSECRETAPIKEY';
    $_GET = array('subnav' => 'blogartikel', 'topic' => '{{constructor.constructor("alert(1)")()}}');
    $html = capture('fxwp_topic_page');
    ok(strpos($html, 'SUPERSECRETAPIKEY') === false, 'API key printed');
    ok(strpos($html, 'constructor') === false, 'topic parameter printed');
    ok(strpos($html, 'nicht verfügbar') !== false);
});
t('Es gibt keine offenen Schreibwerkstatt-REST-Routen mehr', function () use ($restRoutesAfterLoad) {
    foreach ($restRoutesAfterLoad as $c) {
        ok(strpos($c[1][1], 'create_post') === false && strpos($c[1][1], 'content_render') === false, $c[1][1]);
    }
    ok(!function_exists('fxwp_create_post') && !function_exists('fxwp_content_render'));
});

echo "\nDebug-Log-Widget\n";
t('Log-Inhalte werden escaped ausgegeben', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('fxm_admin');
    file_put_contents(WP_CONTENT_DIR . '/debug.log',
        "[24-Jan-2026 09:13:33 UTC] PHP Warning:  bad <script>alert(1)</script> in /x.php on line 1\n"
        . "POSTDATA: <img src=x onerror=alert(2)>\n"
        . "[24-Jan-2026 09:13:34 UTC] PHP <b>Evil</b>:  x\n");
    $html = capture('fxwp_debug_log_widget');
    ok(strpos($html, '<script>alert(1)</script>') === false, 'script tag from log printed');
    ok(strpos($html, '<img src=x') === false, 'img tag from log printed');
    ok(strpos($html, '<b>Evil</b>') === false, 'level printed unescaped');
    ok(strpos($html, '&lt;script&gt;alert(1)') !== false, 'log line missing');
});
t('Log leeren nur für fxm_admin', function () {
    wp_reset();
    $GLOBALS['wp']['valid_nonces'] = array('fxwp_debug_log_clear_action');
    $_POST['fxwp_debug_log_clear_nonce'] = 'x';
    file_put_contents(WP_CONTENT_DIR . '/debug.log', 'keep');
    throws('WpDie', 'fxwp_handle_debug_log_clear');
    eq('keep', file_get_contents(WP_CONTENT_DIR . '/debug.log'));
});

echo "\nKunden-Update-Dashboard\n";
t('Updates per Link ohne Nonce (CSRF) werden abgewiesen', function () {
    wp_reset();
    $GLOBALS['wp']['caps'] = array('update_plugins', 'update_themes');
    $_GET['do_update'] = 'plugins';
    throws('WpDie', function () { capture('cud_dashboard_page'); });
});
t('Benachrichtigungs-Einstellungen nur mit Recht', function () {
    wp_reset();
    $GLOBALS['wp']['valid_nonces'] = array('cud_save_settings');
    $_POST = array('cud_settings_nonce' => 'x', 'cud_notify_email' => 'angreifer@example.test', 'cud_notify_enabled' => '1');
    cud_handle_settings_form();
    eq('__unset', get_option('cud_notify_email', '__unset'));
});

finish();
