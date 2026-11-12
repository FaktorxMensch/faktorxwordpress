<?php
/**
 * Release signing tool (command line only). How-to: docs/release-signing.md
 *
 *   php tools/sign-release.php keygen <private-key-file>
 *       Creates a key pair. The private key goes into the file (keep it out of
 *       the repository!), the public key is printed for includes/config.php.
 *
 *   php tools/sign-release.php sign <tag> [--key <private-key-file>] [--out release.sig] [--upload]
 *       Signs the contents of a git tag exactly as GitHub will ship them and
 *       writes release.sig. Without --key the key is read from the environment
 *       variable FXWP_SIGNING_KEY. --upload attaches the file to the GitHub
 *       release with the gh command.
 *
 *   php tools/sign-release.php preflight [--key <private-key-file>]
 *       Tells the release tool (fxr) whether this machine can sign; see the
 *       exit codes at the command below.
 *
 *   php tools/sign-release.php verify <tag> [--sig release.sig]
 *       Checks a signature against the tag and the public keys in
 *       includes/config.php -- the same check the plugin does.
 */
if (PHP_SAPI !== 'cli') { exit; }

require __DIR__ . '/../includes/update-signature.php';

function fail(string $msg): void
{
    fwrite(STDERR, "sign-release: $msg\n");
    exit(1);
}

function option(array $argv, string $name, ?string $default = null): ?string
{
    $i = array_search($name, $argv, true);
    return ($i !== false && isset($argv[$i + 1])) ? $argv[$i + 1] : $default;
}

/** Unpacks the tag the way a GitHub archive does (honours export-ignore). Returns the folder. */
function export_tag(string $tag): string
{
    if (!preg_match('/^v?\d+(\.\d+){1,3}(-[A-Za-z0-9]+(\.[A-Za-z0-9]+)*)?$/', $tag)) {
        fail("'$tag' does not look like a version tag (v2.9.4)");
    }
    $repo = dirname(__DIR__);
    $dir = rtrim(sys_get_temp_dir(), '/') . '/fxwp-sign-' . bin2hex(random_bytes(6));
    mkdir($dir, 0700, true);
    $cmd = 'git -C ' . escapeshellarg($repo) . ' archive --format=tar ' . escapeshellarg('refs/tags/' . $tag)
        . ' | tar -x -C ' . escapeshellarg($dir) . ' 2>&1';
    exec('set -o pipefail 2>/dev/null; ' . $cmd, $out, $rv);
    if ($rv !== 0 || !is_file($dir . '/faktorxwordpress.php')) {
        remove_dir($dir);
        fail("could not export tag $tag (does the tag exist locally?)\n" . implode("\n", $out));
    }
    return $dir;
}

function remove_dir(string $dir): void
{
    if (strpos($dir, '/fxwp-sign-') === false || !is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

function configured_public_keys(): array
{
    // read the constant from includes/config.php without loading WordPress
    $src = file_get_contents(__DIR__ . '/../includes/config.php');
    if (!preg_match("/define\(\s*'FXWP_UPDATE_PUBLIC_KEYS'\s*,\s*array\((.*?)\)\s*\)\s*;/s", $src, $m)) {
        return array();
    }
    // comments in the array may contain quotes
    $body = preg_replace('#//[^\n]*|/\*.*?\*/#s', '', $m[1]);
    preg_match_all("/'([A-Za-z0-9+\/=]{20,})'/", $body, $keys);
    return $keys[1];
}

if (!function_exists('sodium_crypto_sign_keypair')) {
    fail('this PHP has no sodium extension');
}

$cmd = $argv[1] ?? '';

if ($cmd === 'keygen') {
    $file = $argv[2] ?? fail('usage: keygen <private-key-file>');
    if (file_exists($file)) {
        fail("$file already exists -- not overwriting a key");
    }
    // Create the folder if needed (e.g. ~/.config/fxwp), readable only by you.
    $folder = dirname($file);
    if (!is_dir($folder) && !@mkdir($folder, 0700, true)) {
        fail("cannot create the folder $folder");
    }
    $inside = strpos(realpath($folder) . '/', realpath(dirname(__DIR__)) . '/') === 0;
    if ($inside) {
        fail('choose a location outside the repository for the private key');
    }
    $pair = sodium_crypto_sign_keypair();
    $old = umask(0177);
    $written = @file_put_contents($file, base64_encode(sodium_crypto_sign_secretkey($pair)) . "\n");
    umask($old);
    // Never print a public key whose private half was not saved: nothing could
    // ever be signed for it.
    $saved = $written !== false ? base64_decode(trim((string)@file_get_contents($file)), true) : false;
    if ($saved === false || $saved !== sodium_crypto_sign_secretkey($pair)) {
        @unlink($file);
        fail("could not write the private key to $file -- no key was created");
    }
    echo "Private key written to $file (keep it secret, keep a backup).\n\n";
    echo "Public key -- add this line to FXWP_UPDATE_PUBLIC_KEYS in includes/config.php:\n\n";
    echo "    '" . base64_encode(sodium_crypto_sign_publickey($pair)) . "',\n";
    exit(0);
}

if ($cmd === 'sign') {
    $tag = $argv[2] ?? fail('usage: sign <tag> [--key <file>] [--out release.sig] [--upload]');
    $keyFile = option($argv, '--key');
    $secretB64 = $keyFile !== null ? @file_get_contents($keyFile) : getenv('FXWP_SIGNING_KEY');
    if ($secretB64 === false || trim((string)$secretB64) === '') {
        fail('no private key: pass --key <file> or set FXWP_SIGNING_KEY');
    }
    $secret = base64_decode(trim($secretB64), true);
    if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
        fail('the private key is not valid');
    }
    $public = base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret));

    $dir = export_tag($tag);
    $hash = fxwp_update_manifest_hash($dir);
    remove_dir($dir);
    if ($hash === null) {
        fail('could not fingerprint the exported files');
    }
    $signature = sodium_crypto_sign_detached(fxwp_update_signed_message($tag, $hash), $secret);
    $out = option($argv, '--out', FXWP_UPDATE_SIG_ASSET);
    file_put_contents($out, json_encode(array(
            'v' => 1,
            'tag' => $tag,
            'manifest_sha256' => $hash,
            'signature' => base64_encode($signature),
            'public_key' => $public,
        ), JSON_PRETTY_PRINT) . "\n");
    echo "Signed $tag -> $out\n";

    if (!in_array($public, configured_public_keys(), true)) {
        fwrite(STDERR, "WARNING: this key's public key is not in FXWP_UPDATE_PUBLIC_KEYS (includes/config.php) -- sites will reject this release.\n");
    }
    if (in_array('--upload', $argv, true)) {
        passthru('gh release upload ' . escapeshellarg($tag) . ' ' . escapeshellarg($out) . ' --clobber', $rv);
        if ($rv !== 0) {
            fail('upload failed (is there a GitHub release for this tag, and is gh logged in?)');
        }
        echo "Uploaded to the GitHub release $tag.\n";
    }
    exit(0);
}

if ($cmd === 'verify') {
    $tag = $argv[2] ?? fail('usage: verify <tag> [--sig release.sig]');
    $sigFile = option($argv, '--sig', FXWP_UPDATE_SIG_ASSET);
    $sig = @file_get_contents($sigFile);
    if ($sig === false) {
        fail("cannot read $sigFile");
    }
    $keys = configured_public_keys();
    if (!$keys) {
        fail('no public key in FXWP_UPDATE_PUBLIC_KEYS (includes/config.php)');
    }
    $dir = export_tag($tag);
    $result = fxwp_update_verify_dir($dir, $tag, $sig, $keys);
    remove_dir($dir);
    if ($result !== true) {
        fail("INVALID: $result");
    }
    echo "OK: $sigFile is a valid signature for $tag.\n";
    exit(0);
}

if ($cmd === 'preflight') {
    // preflight [--key <file>]: can a release be signed on this machine? Used by
    // the release tool (fxr) BEFORE it changes anything. Exit codes:
    //    0  ready: key file is valid (and matches includes/config.php, if keys are set there)
    //   10  signing is not set up (no public key in config, no key file): nothing to do
    //   11  sites require signatures, but the key file is missing on this machine
    //   12  the key file is not a valid private key
    //   13  the key file does not belong to any public key in includes/config.php
    $keyFile = option($argv, '--key', '');
    $required = count(configured_public_keys()) > 0;
    if ($keyFile === '' || !is_file($keyFile)) {
        exit($required ? 11 : 10);
    }
    $secret = base64_decode(trim((string)@file_get_contents($keyFile)), true);
    if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
        exit(12);
    }
    if ($required && !in_array(base64_encode(sodium_crypto_sign_publickey_from_secretkey($secret)), configured_public_keys(), true)) {
        exit(13);
    }
    exit(0);
}

if ($cmd === 'verify-dir') {
    // verify-dir <tag> <unpacked folder> [--sig release.sig]: check an already
    // unpacked download, e.g. the archive GitHub actually serves.
    $tag = $argv[2] ?? fail('usage: verify-dir <tag> <dir> [--sig release.sig]');
    $dir = $argv[3] ?? fail('usage: verify-dir <tag> <dir> [--sig release.sig]');
    $sig = @file_get_contents(option($argv, '--sig', FXWP_UPDATE_SIG_ASSET));
    $keys = configured_public_keys();
    if ($sig === false || !$keys) {
        fail('signature file unreadable or no public key in includes/config.php');
    }
    $result = fxwp_update_verify_dir($dir, $tag, $sig, $keys);
    if ($result !== true) {
        fail("INVALID: $result");
    }
    echo "OK: $dir matches the signature for $tag.\n";
    exit(0);
}

fail("usage: php tools/sign-release.php keygen|sign|verify|verify-dir|preflight ...  (see the comment at the top of this file)");
