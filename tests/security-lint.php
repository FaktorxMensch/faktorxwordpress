<?php
/**
 * Security rules checked on the plugin's source text. Each rule encodes a mistake
 * this plugin has actually made, so it cannot come back unnoticed.
 * Run:  php tests/security-lint.php
 */
if (PHP_SAPI !== 'cli') { exit; }

$root = dirname(__DIR__);
$files = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    // tests/ is not shipped; disabled/ is not loaded by the plugin
    if (substr($rel, -4) !== '.php' || preg_match('#^(tests|tools|disabled|\.git|\.githooks|\.github|vendor|node_modules)/#', $rel)) {
        continue;
    }
    $files[$rel] = file_get_contents($f->getPathname());
}
ksort($files);

$problems = array();
$fail = function (string $rule, string $file, string $detail) use (&$problems) {
    $problems[] = "  [$rule] $file: $detail";
};
$lineOf = function (string $src, int $offset): int {
    return substr_count($src, "\n", 0, $offset) + 1;
};
/** Text from $offset up to the bracket that closes the first $open bracket found after it. */
$balanced = function (string $src, int $offset, string $open, string $close): string {
    $start = strpos($src, $open, $offset);
    if ($start === false) return '';
    $depth = 0;
    for ($i = $start, $n = strlen($src); $i < $n; $i++) {
        if ($src[$i] === $open) $depth++;
        elseif ($src[$i] === $close && --$depth === 0) return substr($src, $start, $i - $start + 1);
    }
    return substr($src, $start);
};
/** Body of a named function, searched in all files. */
$functionBody = function (string $name) use ($files, $balanced): ?string {
    foreach ($files as $src) {
        if (preg_match('/function\s+' . preg_quote($name, '/') . '\s*\(/', $src, $m, PREG_OFFSET_CAPTURE)) {
            return $balanced($src, $m[0][1], '{', '}');
        }
    }
    return null;
};

// Routes that are deliberately open because they check the API key themselves.
$openRestRoutes = array('/run-backup-cron');
// The only places allowed to do these things:
$allowEval = array('mods/shortcodes-mod.php');
$allowWpLoad = array('monitor.php', 'admin/fxm-recover.php');
// fxm_admin-only widgets: status display, and the cron URL to copy to the host
$allowApiKeyOutput = array('widgets/debugging_widget.php', 'widgets/backup-health-widget.php');

foreach ($files as $file => $src) {
    // 1. REST routes must say who may call them.
    if (preg_match_all('/register_rest_route\s*\(/', $src, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            $call = $balanced($src, $hit[1], '(', ')');
            $line = $lineOf($src, $hit[1]);
            if (strpos($call, 'permission_callback') === false) {
                $fail('rest-permission', $file, "line $line: register_rest_route without permission_callback (open to everyone)");
            } elseif (strpos($call, '__return_true') !== false) {
                $known = false;
                foreach ($openRestRoutes as $route) { $known = $known || strpos($call, $route) !== false; }
                if (!$known) $fail('rest-permission', $file, "line $line: route is open (__return_true) but not on the allow-list in tests/security-lint.php");
            }
        }
    }

    // 2. eval only where it already is.
    if (preg_match('/(?<![\w>$])eval\s*\(/', $src, $m, PREG_OFFSET_CAPTURE) && !in_array($file, $allowEval, true)) {
        $fail('eval', $file, 'line ' . $lineOf($src, $m[0][1]) . ': eval() outside ' . implode(', ', $allowEval));
    }

    // 3. AJAX handlers: logged-in only, and they must check a nonce.
    if (preg_match_all('/add_action\s*\(\s*[\'"]wp_ajax_(nopriv_)?([\w-]+)[\'"]\s*,\s*[\'"]([\w]+)[\'"]/', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            if ($hit[1] !== '') {
                $fail('ajax', $file, "wp_ajax_nopriv_{$hit[2]}: AJAX action open to visitors who are not logged in");
            }
            $body = $functionBody($hit[3]);
            if ($body === null) {
                $fail('ajax', $file, "handler {$hit[3]}() not found");
            } elseif (!preg_match('/check_ajax_referer|fx_plugin_ajax_guard|wp_verify_nonce/', $body)) {
                $fail('ajax', $file, "{$hit[3]}() does not check a nonce (CSRF)");
            }
        }
    }

    // 4. admin-post handlers: nonce and capability.
    if (preg_match_all('/add_action\s*\(\s*[\'"]admin_post_(nopriv_)?([\w-]+)[\'"]\s*,\s*[\'"]([\w]+)[\'"]/', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $body = (string)$functionBody($hit[3]);
            if ($hit[1] !== '') $fail('admin-post', $file, "admin_post_nopriv_{$hit[2]}: open to visitors");
            if (!preg_match('/wp_verify_nonce|check_admin_referer/', $body)) $fail('admin-post', $file, "{$hit[3]}() does not check a nonce (CSRF)");
            if (strpos($body, 'current_user_can') === false) $fail('admin-post', $file, "{$hit[3]}() does not check a capability");
        }
    }

    // 5. Only the known files may be entry points that boot WordPress themselves.
    if (preg_match('/wp-load\.php/', $src) && !in_array($file, $allowWpLoad, true)) {
        $fail('entry-point', $file, 'loads wp-load.php itself -> directly callable over the web; add it to the allow-list only with its own authentication and a test in tests/entrypoints.php');
    }

    // 6. The backup directory has a random name: always ask fxwp_backup_dir().
    if ($file !== 'includes/helpers.php' && preg_match('#fxwp-backups/#', $src, $m, PREG_OFFSET_CAPTURE)) {
        $fail('backup-dir', $file, 'line ' . $lineOf($src, $m[0][1]) . ': hard-coded backup path, use fxwp_backup_dir()');
    }

    // 7. Never write request data or the API key into the log.
    if (preg_match_all('/error_log\s*\(/', $src, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            $call = $balanced($src, $hit[1], '(', ')');
            if (preg_match('/\$_(POST|GET|REQUEST|COOKIE|SERVER)|postdata|php:\/\/input|api_key/i', $call)) {
                $fail('log-secrets', $file, 'line ' . $lineOf($src, $hit[1]) . ': error_log() of request data / API key');
            }
        }
    }

    // 8. The API key must not be printed into pages (it authorises monitor.php).
    if (!in_array($file, $allowApiKeyOutput, true)
        && preg_match('/(echo|print|<\?=)[^;]*get_option\(\s*[\'"]fxwp_api_key[\'"]/', $src, $m, PREG_OFFSET_CAPTURE)) {
        $fail('api-key-output', $file, 'line ' . $lineOf($src, $m[0][1]) . ': API key printed into the page');
    }

    // 9. External scripts must be pinned to an exact version.
    if (preg_match_all('#https://(?:cdn\.jsdelivr\.net/npm|unpkg\.com)/[\w.-]+(@[^/"\']*)?/#', $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($m as $hit) {
            $version = isset($hit[1]) ? $hit[1][0] : '';
            if (!preg_match('/^@\d+\.\d+\.\d+/', $version)) {
                $fail('cdn-pin', $file, 'line ' . $lineOf($src, $hit[0][1]) . ": external script without exact version ({$hit[0][0]})");
            }
        }
    }

    // 10. No user input straight into the page.
    if (preg_match_all('/(?:echo|print|<\?=)[^;\n]*\$_(GET|POST|REQUEST|COOKIE)\[/', $src, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            if (!preg_match('/esc_(html|attr|url|js|textarea)\s*\(|intval\s*\(|wp_json_encode\s*\(/', $hit[0])) {
                $fail('raw-output', $file, 'line ' . $lineOf($src, $hit[1]) . ': request parameter printed without escaping');
            }
        }
    }
}

// 11. Forms that store executable PHP / install code must verify their nonce.
$mustCheck = array(
    'fxwp_display_shortcode_form' => 'check_admin_referer',
    'fxwp_plugin_list_installer_page' => 'check_admin_referer',
    'fxwp_url_actions' => 'check_admin_referer',
    'fx_plugin_save_option' => 'fx_plugin_ajax_guard',
    'fx_plugin_execute_action' => 'fx_plugin_ajax_guard',
    'fx_plugin_get_options' => 'fx_plugin_ajax_guard',
    // the updater must hand over to the verified path when a signing key is set
    'fxm_plugin_updater' => 'fxwp_update_signing_enabled',
    'fxwp_verified_plugin_update' => 'fxwp_update_verify_dir',
);
foreach ($mustCheck as $fn => $needle) {
    $body = $functionBody($fn);
    if ($body === null) {
        $problems[] = "  [guards] function $fn() not found -- if it was renamed, update tests/security-lint.php";
    } elseif (strpos($body, $needle) === false) {
        $problems[] = "  [guards] $fn() no longer calls $needle()";
    }
}

if ($problems) {
    echo "Security lint: " . count($problems) . " problem(s)\n" . implode("\n", $problems) . "\n";
    exit(1);
}
echo "Security lint: " . count($files) . " files ok\n";
