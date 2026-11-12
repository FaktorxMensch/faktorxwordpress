<?php
/**
 * Entry-point tests: the two files that are reachable directly over the web
 * (monitor.php, admin/fxm-recover.php) are executed for real, as separate PHP
 * processes, inside a throw-away fake WordPress site.
 * Run:  php tests/entrypoints.php
 */
if (PHP_SAPI !== 'cli') { exit; }

require __DIR__ . '/lib/harness.php';

$root = dirname(__DIR__);
$site = sys_get_temp_dir() . '/fxwp-site-' . getmypid();
$plugin = $site . '/wp-content/plugins/faktorxwordpress';
@mkdir($plugin . '/admin', 0777, true);
@mkdir($plugin . '/includes', 0777, true);
copy(__DIR__ . '/lib/fake-wp-load.php', $site . '/wp-load.php');
copy($root . '/monitor.php', $plugin . '/monitor.php');
copy($root . '/includes/recover.php', $plugin . '/includes/recover.php');
copy($root . '/admin/fxm-recover.php', $plugin . '/admin/fxm-recover.php');

const KEY = 'k3yk3yk3yk3yk3yk3yk3yk3yk3yk3y12';

function php_cgi(): ?string
{
    static $bin = false;
    if ($bin === false) {
        $bin = trim((string)shell_exec('command -v php-cgi 2>/dev/null')) ?: null;
    }
    return $bin;
}

/**
 * Runs a script of the fake site. $opt: state (array), post (array, CLI form fields),
 * cgi (bool: run as a web request), body (string: raw request body, cgi only).
 * @return array{out:string,code:int,state:array}
 */
function run(string $script, array $opt): array
{
    global $site;
    $state = $opt['state'] + array('plugin_active' => true, 'options' => array(), 'roles' => array('administrator', 'editor', 'subscriber', 'fxm_admin'),
            'users' => array(array('login' => 'bob', 'email' => 'bob@example.test', 'roles' => array('subscriber'))));
    $stateFile = $site . '/state.json';
    $outFile = $site . '/out.json';
    $state['options'] += array('active_plugins' => array('faktorxwordpress/faktorxwordpress.php'));
    file_put_contents($stateFile, json_encode($state));
    @unlink($outFile);

    $env = array('FXWP_TEST_STATE' => $stateFile, 'FXWP_TEST_OUT' => $outFile, 'PATH' => getenv('PATH'), 'TMPDIR' => sys_get_temp_dir());
    if (!empty($opt['cgi'])) {
        $body = $opt['body'] ?? '';
        $env += array('REDIRECT_STATUS' => '1', 'SCRIPT_FILENAME' => $script, 'REQUEST_METHOD' => $body === '' ? 'GET' : 'POST',
            'CONTENT_TYPE' => 'application/json', 'CONTENT_LENGTH' => (string)strlen($body), 'GATEWAY_INTERFACE' => 'CGI/1.1');
        $cmd = array(php_cgi(), '-d', 'display_errors=1', '-d', 'error_log=/dev/null', '-d', 'opcache.enable=0', '-d', 'opcache.lockfile_path=' . sys_get_temp_dir());
    } else {
        $body = '';
        $env['FXWP_TEST_POST'] = isset($opt['post']) ? json_encode($opt['post']) : '';
        $cmd = array(PHP_BINARY, '-d', 'display_errors=1', '-d', 'error_log=/dev/null', '-d', 'auto_prepend_file=' . __DIR__ . '/lib/prepend-post.php', $script);
    }
    $proc = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes, dirname($script), $env);
    fwrite($pipes[0], $body);
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    $code = proc_close($proc);
    $final = is_file($outFile) ? json_decode(file_get_contents($outFile), true) : array('users' => array(), 'mails' => array());
    return array('out' => $out, 'code' => $code, 'state' => $final);
}

$monitor = $plugin . '/monitor.php';
$recover = $plugin . '/admin/fxm-recover.php';
$withKey = array('options' => array('fxwp_api_key' => KEY));
$rejected = function (array $r, string $why) {
    ok(strpos($r['out'], 'WP_DIE:Security check failed') !== false, "$why: request was not rejected: " . substr($r['out'], 0, 200));
    eq(array('subscriber'), $r['state']['users']['bob']['roles'], "$why: role changed");
    ok(!isset($r['state']['users']['fxmadmin-recovery']), "$why: recovery account created");
    eq(0, count($r['state']['mails']), "$why: mail sent");
};
$attack = array('upgrade_user_arr' => array('bob', 'administrator'), 'recover_admin' => '1');

echo "\nmonitor.php: Anmeldung mit dem API-Key\n";
t('Falscher Key wird abgewiesen', function () use ($monitor, $withKey, $rejected, $attack) {
    $rejected(run($monitor, array('state' => $withKey, 'post' => array('api_key' => 'wrong') + $attack)), 'wrong key');
});
t('Kein Key wird abgewiesen', function () use ($monitor, $withKey, $rejected, $attack) {
    $rejected(run($monitor, array('state' => $withKey, 'post' => $attack)), 'no key');
});
t('Leerer Key bei leerem gespeichertem Key (fehlgeschlagene Aktivierung) wird abgewiesen', function () use ($monitor, $rejected, $attack) {
    $rejected(run($monitor, array('state' => array('options' => array('fxwp_api_key' => '')), 'post' => array('api_key' => '') + $attack)), 'empty/empty');
});
t('Fehlender gespeicherter Key (nach Deaktivierung) lässt niemanden rein', function () use ($monitor, $rejected, $attack) {
    foreach (array('', '0', 'x') as $given) {
        $rejected(run($monitor, array('state' => array('options' => array()), 'post' => array('api_key' => $given) + $attack)), "missing stored key, given '$given'");
    }
});
t('Bei deaktiviertem Plugin antwortet monitor.php nicht', function () use ($monitor, $withKey, $rejected, $attack) {
    $rejected(run($monitor, array('state' => $withKey + array('plugin_active' => false), 'post' => array('api_key' => KEY) + $attack)), 'plugin inactive');
});
t('Richtiger Key: Status-JSON, ohne den Key preiszugeben', function () use ($monitor, $withKey) {
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY)));
    $json = json_decode($r['out'], true);
    ok(is_array($json), 'no JSON: ' . substr($r['out'], 0, 300));
    eq('bob', $json['users'][0]['username']);
    eq(null, $json['recovery']);
    ok(strpos($r['out'], KEY) === false, 'API key in response');
    eq(array('subscriber'), $r['state']['users']['bob']['roles']);
});

echo "\nmonitor.php: Rollen ändern (ProjectPilot > Benutzer Rollen)\n";
t('Bestehender Benutzer bekommt die gewünschte Rolle', function () use ($monitor, $withKey) {
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'upgrade_user_arr' => array('bob', 'editor'))));
    eq(array('editor'), $r['state']['users']['bob']['roles']);
    eq('editor', json_decode($r['out'], true)['users'][0]['role']);
});
t('fxm_admin bekommt zusätzlich administrator', function () use ($monitor, $withKey) {
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'upgrade_user_arr' => array('bob', 'fxm_admin'))));
    eq(array('fxm_admin', 'administrator'), $r['state']['users']['bob']['roles']);
});
t('Unbekannte Rolle oder unbekannter Benutzer: nichts passiert, kein Absturz', function () use ($monitor, $withKey) {
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'upgrade_user_arr' => array('bob', 'gibtsnicht'))));
    eq(array('subscriber'), $r['state']['users']['bob']['roles']);
    ok(is_array(json_decode($r['out'], true)), substr($r['out'], 0, 300));
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'upgrade_user_arr' => array('nobody', 'administrator'))));
    ok(is_array(json_decode($r['out'], true)), substr($r['out'], 0, 300));
});

echo "\nmonitor.php: Admin-Zugang wiederherstellen (recover_admin)\n";
t('Mit Key: Konto wird angelegt, Passwort nur per Mail, nicht in der Antwort', function () use ($monitor, $withKey) {
    $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'recover_admin' => '1')));
    $json = json_decode($r['out'], true);
    ok(is_array($json), substr($r['out'], 0, 300));
    eq(true, $json['recovery']['ok']);
    eq(true, $json['recovery']['created']);
    eq(true, $json['recovery']['mail_sent']);
    eq(array('administrator', 'fxm_admin'), $r['state']['users']['fxmadmin-recovery']['roles']);
    eq(1, count($r['state']['mails']));
    eq(true, $r['state']['mails'][0]['has_password']);
    eq('accounts@faktorxmensch.com', $r['state']['mails'][0]['to']);
    ok(strpos($r['out'], 'GENERATED-PASSWORD-123') === false, 'password in response');
});
t('Bestehendes Konto mit der Agentur-Adresse: nur Passwort zurücksetzen', function () use ($monitor, $withKey) {
    $state = $withKey + array('users' => array(array('login' => 'fxm', 'email' => 'accounts@faktorxmensch.com', 'roles' => array('editor'))));
    $r = run($monitor, array('state' => $state, 'post' => array('api_key' => KEY, 'recover_admin' => '1')));
    $json = json_decode($r['out'], true);
    eq(false, $json['recovery']['created']);
    eq('fxm', $json['recovery']['username']);
    eq(true, $r['state']['users']['fxm']['password_set']);
    ok(in_array('fxm_admin', $r['state']['users']['fxm']['roles'], true) && in_array('administrator', $r['state']['users']['fxm']['roles'], true));
    ok(!isset($r['state']['users']['fxmadmin-recovery']));
});
t('Ohne ausdrückliche Anforderung wird nichts wiederhergestellt', function () use ($monitor, $withKey) {
    foreach (array(null, '0', 'false', '') as $v) {
        $r = run($monitor, array('state' => $withKey, 'post' => array('api_key' => KEY, 'recover_admin' => $v)));
        ok(!isset($r['state']['users']['fxmadmin-recovery']), 'recover_admin=' . var_export($v, true));
    }
});

echo "\nmonitor.php: JSON-Aufruf wie von ProjectPilot (php-cgi)\n";
if (php_cgi() === null) {
    echo "  skip  php-cgi nicht installiert -- JSON-Aufrufe und Browser-Aufrufe werden nicht getestet\n";
} else {
    t('JSON mit richtigem Key (und null-Feldern wie von ProjectPilot) funktioniert', function () use ($monitor, $withKey) {
        $body = json_encode(array('api_key' => KEY, 'plans' => array(), 'project_id' => 'p1', 'upgrade_user_arr' => null, 'invoices' => array(array('number' => 'R1'))));
        $r = run($monitor, array('state' => $withKey, 'cgi' => true, 'body' => $body));
        $json = json_decode(substr($r['out'], strpos($r['out'], '{')), true);
        ok(is_array($json), substr($r['out'], 0, 300));
        eq(1, $json['invoices_count']);
        eq(null, $json['recovery']);
        eq('p1', $r['state']['options']['fxwp_project']['_id']);
    });
    t('JSON: Rolle ändern und wiederherstellen', function () use ($monitor, $withKey) {
        $body = json_encode(array('api_key' => KEY, 'upgrade_user_arr' => array('bob', 'administrator'), 'recover_admin' => true));
        $r = run($monitor, array('state' => $withKey, 'cgi' => true, 'body' => $body));
        eq(array('administrator'), $r['state']['users']['bob']['roles']);
        ok(isset($r['state']['users']['fxmadmin-recovery']));
    });
    t('JSON: Key als true / 0 / null / Array / leer umgeht die Prüfung nicht', function () use ($monitor, $rejected) {
        foreach (array(true, false, 0, null, array(), array(KEY), '') as $given) {
            foreach (array(array('fxwp_api_key' => KEY), array('fxwp_api_key' => ''), array()) as $options) {
                $body = json_encode(array('api_key' => $given, 'upgrade_user_arr' => array('bob', 'administrator'), 'recover_admin' => true));
                $rejected(run($monitor, array('state' => array('options' => $options), 'cgi' => true, 'body' => $body)), 'key ' . json_encode($given) . ' vs ' . json_encode($options));
            }
        }
    });
    t('Kaputter oder leerer Request-Body wird abgewiesen', function () use ($monitor, $withKey, $rejected) {
        foreach (array('not json', '[]', '"x"', 'null') as $body) {
            $rejected(run($monitor, array('state' => $withKey, 'cgi' => true, 'body' => $body)), "body $body");
        }
    });

    echo "\nadmin/fxm-recover.php im Browser (php-cgi)\n";
    t('Ohne Flag-Datei: 404, nichts passiert', function () use ($recover) {
        $r = run($recover, array('state' => array(), 'cgi' => true));
        ok(strpos($r['out'], 'Status: 404') !== false, substr($r['out'], 0, 200));
        ok(strpos($r['out'], 'Done.') === false);
        eq(array(), $r['state']['users'], 'WordPress must not even be loaded');
    });
    t('Mit Flag-Datei: genau ein Aufruf funktioniert, die Flag wird gelöscht', function () use ($recover, $plugin) {
        touch($plugin . '/fxwp-recover.flag');
        $r = run($recover, array('state' => array(), 'cgi' => true));
        ok(strpos($r['out'], 'Done.') !== false, substr($r['out'], 0, 300));
        eq(array('administrator', 'fxm_admin'), $r['state']['users']['fxmadmin-recovery']['roles']);
        ok(strpos($r['out'], 'GENERATED-PASSWORD-123') === false, 'password printed');
        ok(!is_file($plugin . '/fxwp-recover.flag'), 'flag not deleted');
        $again = run($recover, array('state' => array(), 'cgi' => true));
        ok(strpos($again['out'], 'Status: 404') !== false, 'second call must be refused');
    });
}

echo "\nadmin/fxm-recover.php in der Shell\n";
t('Läuft ohne Flag-Datei und stellt den Zugang wieder her (auch bei deaktiviertem Plugin)', function () use ($recover) {
    $r = run($recover, array('state' => array('plugin_active' => false, 'roles' => array('administrator'))));
    ok(strpos($r['out'], 'Done.') !== false, substr($r['out'], 0, 300));
    eq(array('administrator', 'fxm_admin'), $r['state']['users']['fxmadmin-recovery']['roles']);
    eq(1, count($r['state']['mails']));
    ok(strpos($r['out'], 'GENERATED-PASSWORD-123') === false, 'password printed');
});

finish();
