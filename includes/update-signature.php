<?php
/**
 * Signed plugin updates.
 *
 * The self-updater installs code from GitHub on every customer site. Without a
 * check, whoever controls the GitHub repository (stolen account, stolen token)
 * can run code on all of them. So each release is signed with a private key
 * that is NOT stored in the repository, and the plugin only installs a release
 * whose signature matches one of the public keys in FXWP_UPDATE_PUBLIC_KEYS
 * (includes/config.php).
 *
 * What is signed is not the zip file (GitHub generates those and does not
 * promise they stay byte-identical) but the *contents*: a list of every file
 * with its SHA-256, bound to the version tag. The signature is published as the
 * release asset "release.sig".
 *
 * Signing:  tools/sign-release.php     How-to: docs/release-signing.md
 *
 * This file has no WordPress dependencies except fxwp_update_fetch_signature(),
 * so the signer and the tests can use the very same code as the plugin.
 */

if (!defined('FXWP_UPDATE_SIG_ASSET')) {
    define('FXWP_UPDATE_SIG_ASSET', 'release.sig');
}

/**
 * Public keys (base64, Ed25519) that may sign releases; empty = checking is off.
 *
 * @return string[]
 */
function fxwp_update_public_keys(): array
{
    if (!defined('FXWP_UPDATE_PUBLIC_KEYS') || !is_array(FXWP_UPDATE_PUBLIC_KEYS)) {
        return array();
    }
    return array_values(array_filter(FXWP_UPDATE_PUBLIC_KEYS, function ($k) {
        return is_string($k) && $k !== '';
    }));
}

function fxwp_update_signing_enabled(): bool
{
    return count(fxwp_update_public_keys()) > 0;
}

/**
 * Fingerprint of a directory tree: SHA-256 over the sorted list of
 * "<sha256 of file>  <relative path>" lines. Any added, removed, renamed or
 * changed file changes the result.
 *
 * @return string|null hex hash, or null if the directory cannot be read or
 *                     contains something that is not a regular file/directory
 */
function fxwp_update_manifest_hash(string $dir): ?string
{
    $dir = rtrim($dir, '/\\');
    if (!is_dir($dir)) {
        return null;
    }

    $lines = array();
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            if ($file->isLink()) {
                return null; // a release never contains links
            }
            if ($file->isDir()) {
                continue;
            }
            $path = $file->getPathname();
            $hash = hash_file('sha256', $path);
            if ($hash === false) {
                return null;
            }
            $rel = str_replace('\\', '/', substr($path, strlen($dir) + 1));
            $lines[$rel] = $hash . '  ' . $rel;
        }
    } catch (Exception $e) {
        return null;
    }

    if (empty($lines)) {
        return null;
    }
    ksort($lines, SORT_STRING);

    return hash('sha256', implode("\n", $lines) . "\n");
}

/** The exact bytes that get signed: purpose, version tag, content fingerprint. */
function fxwp_update_signed_message(string $tag, string $manifestHash): string
{
    return "fxwp-update-v1\n" . $tag . "\n" . $manifestHash;
}

/**
 * Check an unpacked release against its signature.
 *
 * @param string   $dir        unpacked release (the folder containing faktorxwordpress.php)
 * @param string   $tag        the version that was asked for, e.g. "v2.9.4"
 * @param string   $sigJson    contents of release.sig
 * @param string[] $publicKeys base64 Ed25519 public keys
 * @return true|string true if valid, otherwise the reason (for the log)
 */
function fxwp_update_verify_dir(string $dir, string $tag, string $sigJson, array $publicKeys)
{
    if (!function_exists('sodium_crypto_sign_verify_detached')) {
        return 'this PHP cannot verify signatures (sodium missing)';
    }
    if (empty($publicKeys)) {
        return 'no public key configured';
    }

    $sig = json_decode($sigJson, true);
    if (!is_array($sig) || !isset($sig['tag'], $sig['signature']) || !is_string($sig['tag']) || !is_string($sig['signature'])) {
        return 'signature file is not valid';
    }
    // The signature must be for exactly the version we asked for -- otherwise a
    // correctly signed *old* release could be served under a new name.
    if (!hash_equals($tag, $sig['tag'])) {
        return 'signature is for ' . $sig['tag'] . ', not for ' . $tag;
    }

    $signature = base64_decode($sig['signature'], true);
    if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
        return 'signature is malformed';
    }

    if (!is_file(rtrim($dir, '/\\') . '/faktorxwordpress.php')) {
        return 'download does not contain the plugin';
    }
    $manifestHash = fxwp_update_manifest_hash($dir);
    if ($manifestHash === null) {
        return 'downloaded files could not be read';
    }

    $message = fxwp_update_signed_message($tag, $manifestHash);
    foreach ($publicKeys as $key) {
        $public = base64_decode((string)$key, true);
        if ($public === false || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            continue;
        }
        try {
            if (sodium_crypto_sign_verify_detached($signature, $message, $public)) {
                return true;
            }
        } catch (Exception $e) {
            // try the next key
        }
    }

    return 'signature does not match the downloaded files';
}

/**
 * Download release.sig of a GitHub release.
 *
 * @return string|WP_Error the file's contents
 */
function fxwp_update_fetch_signature(string $tag)
{
    $url = 'https://github.com/FaktorxMensch/faktorxwordpress/releases/download/' . rawurlencode($tag) . '/' . FXWP_UPDATE_SIG_ASSET;
    $response = wp_remote_get($url, array('timeout' => 20, 'redirection' => 5));
    if (is_wp_error($response)) {
        return $response;
    }
    $code = (int)wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return new WP_Error('fxwp_no_signature', 'release.sig for ' . $tag . ' not available (HTTP ' . $code . ')');
    }
    $body = (string)wp_remote_retrieve_body($response);
    if ($body === '' || strlen($body) > 4096) {
        return new WP_Error('fxwp_no_signature', 'release.sig for ' . $tag . ' is empty or too large');
    }
    return $body;
}
