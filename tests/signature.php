<?php
/**
 * Tests for signed updates (includes/update-signature.php, tools/sign-release.php).
 * Run:  php tests/signature.php
 */
if (PHP_SAPI !== 'cli') { exit; }

require __DIR__ . '/lib/harness.php';
require __DIR__ . '/../includes/update-signature.php';

if (!function_exists('sodium_crypto_sign_keypair')) {
    echo "  skip  PHP ohne sodium -- Signatur-Tests werden nicht ausgeführt\n";
    exit(0);
}

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . '/fxwp-sig-' . getmypid();

/** Creates a small "release" folder and returns its path. */
$release = function (string $name) use ($tmp) {
    $dir = "$tmp/$name";
    mkdir("$dir/includes", 0777, true);
    file_put_contents("$dir/faktorxwordpress.php", "<?php // Version: 9.9.9\n");
    file_put_contents("$dir/includes/a.php", "<?php echo 'a';\n");
    file_put_contents("$dir/monitor.php", "<?php echo 'm';\n");
    return $dir;
};
$pair = sodium_crypto_sign_keypair();
$public = base64_encode(sodium_crypto_sign_publickey($pair));
$secret = sodium_crypto_sign_secretkey($pair);
$sign = function (string $dir, string $tag) use ($secret) {
    $sig = sodium_crypto_sign_detached(fxwp_update_signed_message($tag, fxwp_update_manifest_hash($dir)), $secret);
    return json_encode(array('v' => 1, 'tag' => $tag, 'signature' => base64_encode($sig)));
};

echo "\nSignierte Updates: Prüfung\n";
t('Unverändertes Release mit passender Signatur wird akzeptiert', function () use ($release, $sign, $public) {
    $dir = $release('ok');
    eq(true, fxwp_update_verify_dir($dir, 'v9.9.9', $sign($dir, 'v9.9.9'), array($public)));
    eq(true, fxwp_update_verify_dir($dir . '/', 'v9.9.9', $sign($dir, 'v9.9.9'), array('kaputt', $public)), 'second key / trailing slash');
});
t('Geänderte Datei wird erkannt', function () use ($release, $sign, $public) {
    $dir = $release('changed');
    $sig = $sign($dir, 'v9.9.9');
    file_put_contents("$dir/includes/a.php", "<?php system(\$_GET['c']);\n");
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public)) !== true);
});
t('Zusätzliche Datei (Hintertür) wird erkannt', function () use ($release, $sign, $public) {
    $dir = $release('added');
    $sig = $sign($dir, 'v9.9.9');
    file_put_contents("$dir/includes/shell.php", "<?php\n");
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public)) !== true);
});
t('Gelöschte oder umbenannte Datei wird erkannt', function () use ($release, $sign, $public) {
    $dir = $release('removed');
    $sig = $sign($dir, 'v9.9.9');
    rename("$dir/monitor.php", "$dir/monitor2.php");
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public)) !== true);
    $dir = $release('removed2');
    $sig = $sign($dir, 'v9.9.9');
    unlink("$dir/includes/a.php");
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public)) !== true);
});
t('Signatur einer anderen Version wird abgelehnt (kein Unterschieben alter Releases)', function () use ($release, $sign, $public) {
    $dir = $release('tag');
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sign($dir, 'v1.0.0'), array($public)) !== true);
    // tag in the file changed by hand, signature still for v1.0.0
    $forged = json_decode($sign($dir, 'v1.0.0'), true);
    $forged['tag'] = 'v9.9.9';
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', json_encode($forged), array($public)) !== true);
});
t('Signatur eines fremden Schlüssels wird abgelehnt', function () use ($release, $public) {
    $dir = $release('foreign');
    $other = sodium_crypto_sign_keypair();
    $sig = sodium_crypto_sign_detached(fxwp_update_signed_message('v9.9.9', fxwp_update_manifest_hash($dir)), sodium_crypto_sign_secretkey($other));
    $json = json_encode(array('tag' => 'v9.9.9', 'signature' => base64_encode($sig)));
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $json, array($public)) !== true);
});
t('Kaputte oder leere Signaturdatei wird abgelehnt, ohne Absturz', function () use ($release, $public) {
    $dir = $release('garbage');
    foreach (array('', 'not json', '[]', '{}', '{"tag":"v9.9.9"}', '{"tag":"v9.9.9","signature":"AAAA"}', '{"tag":"v9.9.9","signature":123}', '{"tag":["v9.9.9"],"signature":"x"}') as $sig) {
        ok(is_string(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public))), "accepted: $sig");
    }
});
t('Ohne Schlüssel, ohne Plugin-Datei oder mit Symlink: abgelehnt', function () use ($release, $sign, $public) {
    $dir = $release('nokey');
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sign($dir, 'v9.9.9'), array()) !== true);
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sign($dir, 'v9.9.9'), array('', 'x')) !== true);
    $sig = $sign($dir, 'v9.9.9');
    unlink("$dir/faktorxwordpress.php");
    ok(fxwp_update_verify_dir($dir, 'v9.9.9', $sig, array($public)) !== true);
    $dir = $release('link');
    symlink('/etc/passwd', "$dir/includes/link.php");
    eq(null, fxwp_update_manifest_hash($dir));
    eq(null, fxwp_update_manifest_hash("$dir/does-not-exist"));
});
t('Der Fingerabdruck hängt nicht von der Reihenfolge im Dateisystem ab', function () use ($tmp) {
    foreach (array('order1' => array('b.php', 'a.php', 'c/d.php'), 'order2' => array('c/d.php', 'a.php', 'b.php')) as $name => $files) {
        foreach ($files as $f) {
            @mkdir(dirname("$tmp/$name/$f"), 0777, true);
            file_put_contents("$tmp/$name/$f", $f);
        }
    }
    eq(fxwp_update_manifest_hash("$tmp/order1"), fxwp_update_manifest_hash("$tmp/order2"));
});

echo "\nSignierte Updates: Einstellung im Plugin\n";
t('Ohne öffentlichen Schlüssel ist die Prüfung aus, mit Schlüssel an', function () {
    eq(false, fxwp_update_signing_enabled());
    eq(array(), fxwp_update_public_keys());
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
            "define('FXWP_UPDATE_PUBLIC_KEYS', array('', 'abc')); require '" . __DIR__ . "/../includes/update-signature.php'; echo json_encode(array(fxwp_update_signing_enabled(), fxwp_update_public_keys()));"));
    eq('[true,["abc"]]', trim((string)$out));
});
t('Der Updater prüft vor dem Kopieren und entpackt in einen Zufallsordner', function () use ($root) {
    $src = file_get_contents($root . '/cron/auto_updater-cron.php');
    $fn = substr($src, strpos($src, 'function fxwp_verified_plugin_update'));
    $fn = substr($fn, 0, strpos($fn, 'function fxwp_update_remove_staging'));
    $verify = strpos($fn, 'fxwp_update_verify_dir(');
    $copy = strpos($fn, 'recurseCopy(');
    ok($verify !== false && $copy !== false && $verify < $copy, 'verification must come before recurseCopy');
    ok(strpos($fn, 'random_bytes(') !== false, 'staging folder must be random');
    ok(preg_match('/(?<![A-Z_])WP_PLUGIN_DIR/', $fn) === 0, 'unverified files must not be unpacked into the plugins folder');
    // and the updater takes this path whenever a key is configured, before any download
    $main = substr($src, strpos($src, 'function fxm_plugin_updater'), strpos($src, 'function fxwp_verified_plugin_update') - strpos($src, 'function fxm_plugin_updater'));
    ok(strpos($main, 'fxwp_update_signing_enabled()') !== false && strpos($main, 'fxwp_update_signing_enabled()') < strpos($main, 'download_url('));
});

echo "\nSignier-Werkzeug (tools/sign-release.php)\n";
$tool = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/sign-release.php');
t('keygen legt keinen Schlüssel im Repository ab und überschreibt keinen', function () use ($tool, $root, $tmp) {
    exec("$tool keygen " . escapeshellarg($root . '/private.key') . ' 2>&1', $out, $rv);
    ok($rv !== 0 && !file_exists($root . '/private.key'), 'key written into the repository');
    @mkdir($tmp, 0777, true);
    exec("$tool keygen " . escapeshellarg("$tmp/k.key") . ' 2>&1', $out2, $rv2);
    eq(0, $rv2, implode("\n", $out2));
    eq('0600', substr(sprintf('%o', fileperms("$tmp/k.key")), -4));
    ok(strlen(base64_decode(trim(file_get_contents("$tmp/k.key")), true)) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
    exec("$tool keygen " . escapeshellarg("$tmp/k.key") . ' 2>&1', $out3, $rv3);
    ok($rv3 !== 0, 'existing key was overwritten');
});
t('sign verweigert Tags, die keine Version sind, und fehlende Schlüssel', function () use ($tool, $tmp) {
    exec("$tool sign " . escapeshellarg('main; touch x') . ' --key ' . escapeshellarg("$tmp/k.key") . ' --out ' . escapeshellarg("$tmp/x.sig") . ' 2>&1', $o, $rv);
    ok($rv !== 0 && !file_exists("$tmp/x.sig"));
    exec("FXWP_SIGNING_KEY= $tool sign v0.0.0 --out " . escapeshellarg("$tmp/y.sig") . ' 2>&1', $o2, $rv2);
    ok($rv2 !== 0 && !file_exists("$tmp/y.sig"));
});
t('sign + Prüfung an einem echten Tag: exportiert wie GitHub (ohne tests/, docs/ ...)', function () use ($tool, $root, $tmp) {
    $tag = trim((string)shell_exec('git -C ' . escapeshellarg($root) . ' tag --list "v*" --sort=-v:refname 2>/dev/null | head -n1'));
    if ($tag === '') {
        echo "        (kein Tag im Checkout -- übersprungen)\n";
        return;
    }
    exec("$tool sign " . escapeshellarg($tag) . ' --key ' . escapeshellarg("$tmp/k.key") . ' --out ' . escapeshellarg("$tmp/release.sig") . ' 2>/dev/null', $o, $rv);
    eq(0, $rv, "sign $tag failed");
    $sig = file_get_contents("$tmp/release.sig");
    $public = json_decode($sig, true)['public_key'];
    // unpack the tag once more, independently, and check like the plugin would
    $dir = "$tmp/export";
    mkdir($dir, 0777, true);
    exec('git -C ' . escapeshellarg($root) . ' archive --format=tar ' . escapeshellarg('refs/tags/' . $tag) . ' | tar -x -C ' . escapeshellarg($dir), $o2, $rv2);
    eq(0, $rv2);
    eq(true, fxwp_update_verify_dir($dir, $tag, $sig, array($public)));
    file_put_contents("$dir/monitor.php", "\n// changed", FILE_APPEND);
    ok(fxwp_update_verify_dir($dir, $tag, $sig, array($public)) !== true, 'tampered export accepted');
});

finish();
