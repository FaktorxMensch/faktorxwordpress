<?php
declare(strict_types=1);

/**
 * Emergency tool: restores the agency's admin account on this site and mails
 * the new password to the agency address.
 *
 * It must not be callable by just anyone, so it only runs when the caller has
 * proven access to the server:
 *
 *   1. From a shell:   php wp-content/plugins/faktorxwordpress/admin/fxm-recover.php
 *   2. In the browser: upload an empty file named "fxwp-recover.flag" into the
 *      plugin folder (wp-content/plugins/faktorxwordpress/) via FTP, then open
 *      this file's URL. The flag is deleted on use, so it works exactly once.
 *
 * Without server access, use ProjectPilot instead (monitor call with
 * "recover_admin": true, authenticated by the site's API key).
 */

$fxwp_recover_is_cli = (PHP_SAPI === 'cli');

if (!$fxwp_recover_is_cli) {
    $fxwp_recover_flag = dirname(__DIR__) . '/fxwp-recover.flag';

    if (!is_file($fxwp_recover_flag)) {
        http_response_code(404);
        exit;
    }
    // One use only: if the flag cannot be removed, refuse to run -- otherwise
    // the script would stay open to everyone.
    if (!@unlink($fxwp_recover_flag)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Could not delete fxwp-recover.flag. Delete it manually; nothing was changed.\n");
    }
}

function fxm_find_wp_load(string $start_dir): ?string
{
    $dir = realpath($start_dir);

    while ($dir && $dir !== dirname($dir)) {
        $candidate = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';

        if (is_file($candidate)) {
            return $candidate;
        }

        $dir = dirname($dir);
    }

    return null;
}

$wp_load = fxm_find_wp_load(__DIR__);

if (!$wp_load) {
    http_response_code(500);
    exit('wp-load.php not found. Put this file inside the WordPress installation.');
}

require_once $wp_load;
// Also works while the plugin itself is deactivated.
require_once dirname(__DIR__) . '/includes/recover.php';

$result = fxwp_recover_admin_access();

if (!$fxwp_recover_is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

if (!$result['ok']) {
    exit($result['error'] . "\n");
}

echo "Done.\n\n";
echo "Created new user: " . ($result['created'] ? 'yes' : 'no, existing user password was reset') . "\n";
echo "Email sent: " . ($result['mail_sent'] ? 'yes' : 'no') . "\n\n";
