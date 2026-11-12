<?php
// Helper for tests/unit.php: fxwp_backup_dir() caches per process and ABSPATH is a
// constant, so every scenario runs in its own process.
//   php backup-dir-case.php <abspath> <job-active 0|1>   -> prints the directory
if (PHP_SAPI !== 'cli') { exit; }
define('ABSPATH', rtrim($argv[1], '/') . '/');
require __DIR__ . '/wp-stubs.php';
$GLOBALS['wp']['options']['fxwp_backup_state'] = $argv[2] === '1' ? array('active' => true) : array();
require __DIR__ . '/../../includes/options-config.php';
require __DIR__ . '/../../includes/helpers.php';
$first = fxwp_backup_dir();
$second = fxwp_backup_dir();
echo json_encode(array('dir' => $first, 'stable' => $first === $second));
