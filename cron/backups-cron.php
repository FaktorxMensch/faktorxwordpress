<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
require_once plugin_dir_path(__FILE__) . '../includes/helpers.php';

/**
 * Backup engine -- chunked & resumable.
 *
 * A backup is run as a *job* whose state lives in the option `fxwp_backup_state`.
 * Each invocation of the cron processes one time-budgeted "slice" of the job and,
 * if more work remains, schedules itself to continue. This decouples "how long a
 * backup takes" from "how long a single PHP request may run", which is the root
 * cause of the orphaned-".sql-without-.zip" failures customers report: previously
 * the whole site was zipped in a single ZipArchive::close(), and any timeout or
 * disk hiccup during that one spike left a half-written archive behind.
 *
 * Phases:
 *   db       -> dump the database (mysqldump, or a resumable per-table PHP fallback)
 *   files    -> add site files to the zip in batches, closing/reopening each slice
 *   finalize -> verify integrity, run retention, record success
 */

if (!wp_next_scheduled('fxwp_backup_task')) {
    wp_schedule_event(time(), FXWP_BACKUP_INTERVAL, 'fxwp_backup_task');
}

// Both the recurring schedule and the self-rescheduled "continue" event drive the
// same tick. The continue event lets a long backup advance across many short
// requests instead of needing one long one.
add_action('fxwp_backup_task', 'fxwp_backup_tick');
add_action('fxwp_backup_continue', 'fxwp_backup_tick');

/**
 * Cron entry point. Advances a running job, or starts a new one when due.
 */
function fxwp_backup_tick()
{
    if (fxwp_check_deactivated_features('fxwp_deact_backups')) {
        return;
    }

    // Heartbeat: record that the backup cron actually fired. The dashboard uses
    // this to warn if backups have effectively stopped running (e.g. no traffic
    // to drive WP-Cron and no external cron configured).
    fxwp_backup_record_cron_run();

    $budget = (int)get_option('fxwp_backup_time_budget', 15); // seconds per slice

    try {
        $state = get_option('fxwp_backup_state', array());
        if (!empty($state['active'])) {
            // A job is in progress -> advance it by one slice.
            fxwp_backup_process_slice($budget);
        } else {
            // Start a new job only if one is due. Gate on both last completion and
            // last attempt so a frequent external cron can't trigger a start storm.
            $last = max(
                (int)get_option('fxwp_backup_last_completed', 0),
                (int)get_option('fxwp_backup_last_attempt', 0)
            );
            if (time() - $last >= fxwp_backup_interval_seconds() - 300) {
                fxwp_backup_start();
                fxwp_backup_process_slice($budget);
            }
        }
    } catch (\Throwable $e) {
        $msg = 'Backup process failed on ' . get_site_url() . ': ' . $e->getMessage();
        error_log($msg);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail(FXWP_ERROR_EMAIL, 'Backup failed on ' . get_site_url(), $msg, $headers);
    }
}

/**
 * Map the configured WP-Cron schedule name to seconds.
 */
function fxwp_backup_interval_seconds()
{
    $map = array(
        'hourly'     => HOUR_IN_SECONDS,
        'twicedaily' => 12 * HOUR_IN_SECONDS,
        'daily'      => DAY_IN_SECONDS,
        'weekly'     => WEEK_IN_SECONDS,
    );
    $i = FXWP_BACKUP_INTERVAL;
    return isset($map[$i]) ? $map[$i] : 12 * HOUR_IN_SECONDS;
}

/**
 * Record a cron heartbeat as a per-day counter (local day buckets).
 *
 * A rolling list of timestamps would be capped and under-count a frequent cron;
 * counting per calendar day gives an accurate "runs in the last 7 days" figure
 * while staying tiny (we keep at most the last 14 day buckets).
 */
function fxwp_backup_record_cron_run()
{
    update_option('fxwp_backup_cron_last_run', time());

    $days = get_option('fxwp_backup_cron_days', array());
    if (!is_array($days)) {
        $days = array();
    }
    $today = current_time('Y-m-d'); // local day
    $days[$today] = (isset($days[$today]) ? (int)$days[$today] : 0) + 1;

    if (count($days) > 14) {
        ksort($days);
        $days = array_slice($days, -14, null, true);
    }
    update_option('fxwp_backup_cron_days', $days);
}

/**
 * Health summary for the dashboard widget / login warning.
 *
 * @return array{last_run:int,runs_last_7d:int,last_backup:int,healthy:bool}
 */
function fxwp_backup_cron_health()
{
    $last = (int)get_option('fxwp_backup_cron_last_run', 0);
    $days = get_option('fxwp_backup_cron_days', array());
    if (!is_array($days)) {
        $days = array();
    }

    // Sum runs across the last 7 calendar days (purely string-based, so no
    // timezone ambiguity).
    $runs7 = 0;
    $todayTs = strtotime(current_time('Y-m-d'));
    for ($i = 0; $i < 7; $i++) {
        $key = date('Y-m-d', $todayTs - $i * DAY_IN_SECONDS);
        if (isset($days[$key])) {
            $runs7 += (int)$days[$key];
        }
    }

    $weekAgo = time() - WEEK_IN_SECONDS;
    return array(
        'last_run'     => $last,
        'runs_last_7d' => $runs7,
        'last_backup'  => (int)get_option('fxwp_backup_last_completed', 0),
        // "Runs at least once a week" is the bar we promise customers.
        'healthy'      => ($runs7 >= 1) && ($last >= $weekAgo),
    );
}

/* -------------------------------------------------------------------------- */
/*  Job lifecycle                                                              */
/* -------------------------------------------------------------------------- */

/**
 * Begin a new backup job: prepare the directory, run pre-flight checks and write
 * the initial state. Throws (caught by the tick) if the environment isn't ready.
 */
function fxwp_backup_start()
{
    fxwp_backup_raise_limits();
    fxwp_fix_execution_time();

    // Throttle starts regardless of outcome so a frequent external cron can't
    // re-trigger a failing backup every few minutes.
    update_option('fxwp_backup_last_attempt', time());

    $backupDir = ABSPATH . 'wp-content/fxwp-backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    fxwp_check_backup_permissions($backupDir);
    fxwp_secure_backup_dir($backupDir);
    fxwp_check_backup_disk_space($backupDir);

    // Name the backup in the site's local timezone (WordPress runs PHP in UTC,
    // so plain date() would be 2h behind German wall-clock in summer).
    // fxwp_get_backup_timestamp() parses it back timezone-aware.
    $base = 'backup_' . current_time('Y-m-d_H-i-s');
    // The DB dump is written first and incrementally; start it empty.
    @file_put_contents($backupDir . $base . '.zip.sql', '');

    $state = array(
        'active'          => true,
        'base'            => $base,
        'phase'           => 'db',
        'started_at'      => time(),
        'tables'          => fxwp_backup_list_tables(),
        'table_offset'    => 0,
        'php_dump'        => false,
        'mysqldump_tried' => false,
        'db_header'       => false,
        'manifest_built'  => false,
        'file_index'      => 0,
        'total_files'     => 0,
        'attempts'        => 0,
        'error'           => '',
    );

    update_option('fxwp_backup_expected_completion', 0);
    update_option('fxwp_backup_state', $state);
    error_log('fxwp backup started: ' . $base);
}

/**
 * Process one slice of the active job. With $budgetSeconds === 0 it runs the job
 * to completion (used by the manual "create backup now" button).
 */
function fxwp_backup_process_slice($budgetSeconds)
{
    fxwp_backup_raise_limits();

    $state = get_option('fxwp_backup_state', array());
    if (empty($state['active'])) {
        return;
    }

    $state['attempts'] = (int)$state['attempts'] + 1;
    $deadline = $budgetSeconds > 0 ? microtime(true) + $budgetSeconds : 0;

    $backupDir  = ABSPATH . 'wp-content/fxwp-backups/';
    $backupFile = $backupDir . $state['base'] . '.zip';
    $dumpFile   = $backupFile . '.sql';

    try {
        do {
            switch ($state['phase']) {
                case 'db':
                    fxwp_backup_db_phase($state, $dumpFile, $deadline);
                    break;
                case 'files':
                    fxwp_backup_files_phase($state, $backupDir, $backupFile, $deadline);
                    break;
                case 'finalize':
                    fxwp_backup_finalize($state, $backupDir, $backupFile, $dumpFile);
                    break;
                case 's3':
                    if (function_exists('fxwp_s3_upload_phase')) {
                        fxwp_s3_upload_phase($state, $backupDir, $backupFile, $dumpFile, $deadline);
                    } else {
                        $state['active'] = false;
                    }
                    break;
                default:
                    $state['active'] = false;
            }
            update_option('fxwp_backup_state', $state);
        } while (!empty($state['active']) && ($deadline == 0 || microtime(true) < $deadline));
    } catch (\Throwable $e) {
        // Abort the job (don't loop forever on a hard error). Any DB dump already
        // written is kept on disk as a usable database-only backup.
        $state['active'] = false;
        $state['error']  = $e->getMessage();
        update_option('fxwp_backup_state', $state);
        throw new Exception($e->getMessage());
    }

    if (!empty($state['active'])) {
        fxwp_backup_schedule_continue();
    }
}

/**
 * Ask WP to fire another tick soon, so a running job keeps advancing even on a
 * site whose only driver is WP-Cron. (Single events are de-duplicated.)
 */
function fxwp_backup_schedule_continue()
{
    if (!wp_next_scheduled('fxwp_backup_continue')) {
        wp_schedule_single_event(time() + 30, 'fxwp_backup_continue');
    }
}

function fxwp_backup_raise_limits()
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(0);
    @ignore_user_abort(true);
}

/* -------------------------------------------------------------------------- */
/*  Phase: database                                                            */
/* -------------------------------------------------------------------------- */

function fxwp_backup_db_phase(&$state, $dumpFile, $deadline)
{
    if (empty($state['php_dump'])) {
        if (empty($state['mysqldump_tried'])) {
            // Persist the "tried" flag *before* shelling out, so a crash mid-dump
            // doesn't make us retry mysqldump forever.
            $state['mysqldump_tried'] = true;
            update_option('fxwp_backup_state', $state);

            if (fxwp_backup_mysqldump_full($dumpFile) && @filesize($dumpFile) > 0) {
                $state['phase'] = 'files';
                return;
            }
            error_log('fxwp backup: mysqldump unavailable/failed, falling back to PHP dump');
        }
        // Switch to the resumable PHP dump and start the .sql from scratch.
        $state['php_dump'] = true;
        @file_put_contents($dumpFile, '');
    }

    // Resumable, table-by-table PHP dump.
    $mysqli = fxwp_backup_db_connect();
    if (empty($state['db_header'])) {
        file_put_contents($dumpFile, "SET FOREIGN_KEY_CHECKS=0;\n", FILE_APPEND);
        $state['db_header'] = true;
    }

    while (!empty($state['tables'])) {
        if ($deadline && microtime(true) >= $deadline) {
            $mysqli->close();
            return; // resume this table next slice
        }
        $table  = $state['tables'][0];
        $offset = (int)$state['table_offset'];
        $done   = fxwp_backup_php_dump_table($mysqli, $table, $dumpFile, $offset, $deadline);
        $state['table_offset'] = $offset;
        if ($done) {
            array_shift($state['tables']);
            $state['table_offset'] = 0;
        } else {
            $mysqli->close();
            return; // deadline hit mid-table; resume from $offset next slice
        }
    }

    file_put_contents($dumpFile, "SET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);
    $mysqli->close();
    $state['phase'] = 'files';
}

/**
 * Run a hardened single-shot mysqldump of the whole database.
 *
 * Hardening over the old command:
 *   - credentials passed via a 0600 --defaults-extra-file (not on the command
 *     line, so they don't leak through `ps`), and escapeshellarg'd paths;
 *   - DB_HOST parsed for host:port / socket forms (the old --host='localhost:3306'
 *     silently broke mysqldump);
 *   - --single-transaction --quick for a consistent, low-memory InnoDB dump;
 *   - stderr captured and logged, and a return-code + non-empty-file check so a
 *     failed dump is detected instead of leaving a truncated .sql behind.
 *
 * @return bool true on success.
 */
function fxwp_backup_mysqldump_full($dumpFile)
{
    if (!function_exists('exec')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return false;
    }

    list($host, $port, $socket) = fxwp_backup_parse_db_host(DB_HOST);

    $cnf = tempnam(sys_get_temp_dir(), 'fxwpcnf');
    if ($cnf === false) {
        return false;
    }
    $ini = "[client]\nuser=" . DB_USER . "\npassword=" . DB_PASSWORD . "\n";
    if ($socket !== '') {
        $ini .= "socket=" . $socket . "\n";
    } else {
        $ini .= "host=" . $host . "\n";
        if ($port !== '') {
            $ini .= "port=" . $port . "\n";
        }
    }
    file_put_contents($cnf, $ini);
    @chmod($cnf, 0600);

    $errFile = $dumpFile . '.err';
    $cmd = 'mysqldump --defaults-extra-file=' . escapeshellarg($cnf)
        . ' --single-transaction --quick --no-tablespaces --skip-lock-tables'
        . ' --default-character-set=utf8mb4 ' . escapeshellarg(DB_NAME)
        . ' > ' . escapeshellarg($dumpFile)
        . ' 2> ' . escapeshellarg($errFile);

    $out = array();
    $rv = null;
    @exec($cmd, $out, $rv);
    @unlink($cnf);

    if ($rv !== 0) {
        $err = @file_get_contents($errFile);
        error_log('fxwp mysqldump failed (rv=' . $rv . '): ' . substr((string)$err, 0, 500));
        @unlink($errFile);
        return false;
    }
    @unlink($errFile);
    return true;
}

/**
 * Parse a WordPress DB_HOST into [host, port, socket].
 * Handles "localhost", "127.0.0.1:3306", "localhost:/path/to/sock" and "/path/sock".
 */
function fxwp_backup_parse_db_host($h)
{
    $host = (string)$h;
    $port = '';
    $socket = '';
    if ($host !== '' && $host[0] === '/') {
        return array('', '', $host); // bare socket path
    }
    if (strpos($host, ':') !== false) {
        list($a, $b) = explode(':', $host, 2);
        $host = $a;
        if (is_numeric($b)) {
            $port = $b;
        } else {
            $socket = $b;
        }
    }
    return array($host, $port, $socket);
}

function fxwp_backup_db_connect()
{
    list($host, $port, $socket) = fxwp_backup_parse_db_host(DB_HOST);
    $mysqli = @new mysqli(
        $host !== '' ? $host : null,
        DB_USER,
        DB_PASSWORD,
        DB_NAME,
        $port !== '' ? (int)$port : 0,
        $socket !== '' ? $socket : null
    );
    if ($mysqli->connect_error) {
        throw new Exception('Failed to connect to the database: ' . $mysqli->connect_error);
    }
    @$mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function fxwp_backup_list_tables()
{
    $mysqli = fxwp_backup_db_connect();
    $tables = array();
    $result = $mysqli->query('SHOW TABLES');
    if ($result) {
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $tables[] = $row[0];
        }
    }
    $mysqli->close();
    return $tables;
}

/**
 * Dump one table to $dumpFile in row batches, resuming from $offset.
 *
 * Correctness fixes over the old fallback: NULLs are written as NULL (not ""),
 * and rows are streamed in batches so a large table never buffers fully in PHP
 * memory and can be paused/resumed at a deadline.
 *
 * @param int  $offset  In/out: row offset to resume from; updated as rows are written.
 * @return bool true when the table is fully dumped, false if paused at the deadline.
 */
function fxwp_backup_php_dump_table($mysqli, $table, $dumpFile, &$offset, $deadline)
{
    $batch = (int)get_option('fxwp_backup_rows_per_batch', 2000);
    $tableEsc = str_replace('`', '``', $table);

    if ($offset === 0) {
        $create = $mysqli->query('SHOW CREATE TABLE `' . $tableEsc . '`');
        $createRow = $create ? $create->fetch_row() : null;
        $header = "\nDROP TABLE IF EXISTS `" . $tableEsc . "`;\n";
        if ($createRow) {
            $header .= $createRow[1] . ";\n";
        }
        file_put_contents($dumpFile, $header, FILE_APPEND);
    }

    while (true) {
        if ($deadline && microtime(true) >= $deadline) {
            return false; // paused
        }
        $result = $mysqli->query('SELECT * FROM `' . $tableEsc . '` LIMIT ' . $batch . ' OFFSET ' . $offset);
        if (!$result || $result->num_rows === 0) {
            return true; // done
        }
        $numFields = $result->field_count;
        $buf = '';
        while ($row = $result->fetch_row()) {
            $vals = array();
            for ($k = 0; $k < $numFields; $k++) {
                $vals[] = $row[$k] === null ? 'NULL' : "'" . $mysqli->real_escape_string($row[$k]) . "'";
            }
            $buf .= 'INSERT INTO `' . $tableEsc . '` VALUES(' . implode(',', $vals) . ");\n";
        }
        file_put_contents($dumpFile, $buf, FILE_APPEND);

        $rows = $result->num_rows;
        $offset += $rows;
        $result->free();
        if ($rows < $batch) {
            return true; // last page
        }
    }
}

/* -------------------------------------------------------------------------- */
/*  Phase: files                                                              */
/* -------------------------------------------------------------------------- */

function fxwp_backup_files_phase(&$state, $backupDir, $backupFile, $deadline)
{
    $rootDir  = ABSPATH;
    $manifest = $backupDir . '.' . $state['base'] . '.files';

    if (empty($state['manifest_built'])) {
        fxwp_backup_build_manifest($rootDir, $manifest, $state);
        $state['manifest_built'] = true;
        $state['file_index'] = 0;
    }

    $lines = file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        $lines = array();
    }
    $total = count($lines);
    $state['total_files'] = $total;

    $zip = new ZipArchive();
    // First slice creates the archive; later slices append to it. Closing the zip
    // each slice means we never rely on one giant final close() that can time out.
    $flag = ((int)$state['file_index'] === 0) ? (ZipArchive::CREATE | ZipArchive::OVERWRITE) : 0;
    if ($zip->open($backupFile, $flag) !== true) {
        throw new Exception('Failed to open backup zip: ' . $backupFile . ' (' . $zip->getStatusString() . ')');
    }

    $i = (int)$state['file_index'];
    $added = 0;
    $perSlice = (int)get_option('fxwp_backup_files_per_slice', 300);
    for (; $i < $total; $i++) {
        if ($deadline) {
            if (microtime(true) >= $deadline || $added >= $perSlice) {
                break;
            }
        }
        $path = $lines[$i];
        if (is_file($path)) {
            $rel = substr($path, strlen($rootDir));
            @$zip->addFile($path, $rel);
        }
        $added++;
    }

    if ($zip->close() !== true) {
        throw new Exception('Failed to close backup zip: ' . $backupFile);
    }

    // Advance the cursor only after a successful flush.
    $state['file_index'] = $i;
    if ($i >= $total) {
        $state['phase'] = 'finalize';
    }
}

/**
 * Build the list of files to archive.
 *
 * Exclusions match whole path *segments* (not substrings), so we no longer
 * accidentally drop legitimate files like a plugin named "...-cache" or any path
 * that merely contains the word "backup" -- a bug that silently produced
 * incomplete, unrestorable archives.
 */
function fxwp_backup_build_manifest($rootDir, $manifest, &$state)
{
    $fh = fopen($manifest, 'w');
    if ($fh === false) {
        throw new Exception('Cannot write backup manifest: ' . $manifest);
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
        RecursiveIteratorIterator::CATCH_GET_CHILD // skip unreadable dirs
    );

    $count = 0;
    foreach ($it as $file) {
        if ($file->isDir()) {
            continue;
        }
        $path = $file->getPathname();
        $rel  = substr($path, strlen($rootDir));
        if (fxwp_backup_is_excluded($rel)) {
            continue;
        }
        fwrite($fh, $path . "\n");
        $count++;
    }
    fclose($fh);
    $state['total_files'] = $count;
}

function fxwp_backup_is_excluded($relativePath)
{
    $exclude = array(
        'fxwp-backups', 'cache', 'upgrade', 'backwpup', 'wp-clone',
        'snapshots', 'updraft', 'ai1wm-backups', 'node_modules', '.git',
    );
    $segments = explode('/', str_replace('\\', '/', trim($relativePath, '/')));
    foreach ($segments as $seg) {
        if (in_array(strtolower($seg), $exclude, true)) {
            return true;
        }
    }
    return false;
}

/* -------------------------------------------------------------------------- */
/*  Phase: finalize                                                            */
/* -------------------------------------------------------------------------- */

function fxwp_backup_finalize(&$state, $backupDir, $backupFile, $dumpFile)
{
    // Integrity check: the zip must be openable with files in it, and the SQL
    // dump must be non-empty. Only then do we treat the backup as good.
    $reason = '';
    if (!file_exists($backupFile)) {
        $reason = 'zip file missing';
    } else {
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            $reason = 'zip not openable';
        } else {
            if ($zip->numFiles <= 0) {
                $reason = 'zip contains no files';
            }
            $zip->close();
        }
    }
    if ($reason === '' && (!file_exists($dumpFile) || @filesize($dumpFile) <= 0)) {
        $reason = 'database dump empty or missing';
    }

    if ($reason !== '') {
        $state['active'] = false;
        $state['error'] = $reason;
        throw new Exception('Backup verification failed: ' . $reason);
    }

    fxwp_delete_expired_backups();
    update_option('fxwp_backup_last_completed', time());
    update_option('fxwp_backup_expected_completion', 1);
    @unlink($backupDir . '.' . $state['base'] . '.files');
    error_log('fxwp backup completed: ' . $state['base']);

    // The local backup is now verified and recorded as successful. If off-site
    // (S3) copy is configured, hand off to the resumable upload phase; its
    // success/failure is independent of the (already good) local backup.
    if (function_exists('fxwp_s3_enabled') && fxwp_s3_enabled()) {
        $state['phase'] = 's3';
        unset($state['s3']); // initialised lazily by the upload phase
    } else {
        $state['active'] = false;
    }
}

/* -------------------------------------------------------------------------- */
/*  Manual / synchronous entry point (admin "create backup now")              */
/* -------------------------------------------------------------------------- */

/**
 * Run a backup to completion in the current request. Used by the admin button.
 * Resumes an in-progress job if one exists, otherwise starts a fresh one.
 */
function fxwp_create_backup(): void
{
    $state = get_option('fxwp_backup_state', array());
    if (empty($state['active'])) {
        fxwp_backup_start();
    }
    do {
        fxwp_backup_process_slice(0); // 0 = no time budget: run to completion
        $state = get_option('fxwp_backup_state', array());
    } while (!empty($state['active']));
}

/* -------------------------------------------------------------------------- */
/*  Pre-flight checks                                                          */
/* -------------------------------------------------------------------------- */

function fxwp_check_backup_permissions($backupDir)
{
    if (!is_writable($backupDir)) {
        error_log("Backup directory not writable: $backupDir");
        throw new Exception("Backup directory not writable");
    }

    $testFile = $backupDir . 'test.tmp';
    if (@file_put_contents($testFile, 'test') === false) {
        error_log("Cannot write to backup directory: $backupDir");
        throw new Exception("Cannot write to backup directory");
    }
    unlink($testFile);
}

/**
 * Block all web access to the backup directory.
 *
 * Backups bundle the database dump and wp-config.php (which holds the DB
 * credentials), so they must never be downloadable at a guessable URL. We drop
 * both an .htaccess (Apache / LiteSpeed) and an empty index.html (directory
 * listing fallback for any server). On nginx, location-level denies must still
 * be configured at the server level -- the admin download buttons go through a
 * capability- and nonce-checked PHP handler instead of a direct file URL.
 */
function fxwp_secure_backup_dir($backupDir)
{
    $htaccess = $backupDir . '.htaccess';
    if (!file_exists($htaccess)) {
        $rules = "# Added by Faktor x WordPress -- backups contain DB dumps and credentials.\n"
            . "<IfModule mod_authz_core.c>\n"
            . "    Require all denied\n"
            . "</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n"
            . "    Order allow,deny\n"
            . "    Deny from all\n"
            . "</IfModule>\n";
        @file_put_contents($htaccess, $rules);
    }

    $index = $backupDir . 'index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }
}

/**
 * Abort the backup before writing anything if there isn't enough space.
 *
 * IMPORTANT: on shared hosting disk_free_space()/disk_total_space() report the
 * *physical filesystem* (often many TB), not your account quota -- so they can
 * say "2 TB free" while your 20 GB plan is full. We therefore prefer the
 * quota-aware figure from fxwp_get_available_storage_space() (used vs configured
 * fxwp_storage_limit), and only fall back to disk_free_space() if it's missing.
 */
function fxwp_check_backup_disk_space($backupDir)
{
    // Estimate the next backup's size from the largest existing one (+50% margin),
    // with a 512 MB floor when there's nothing to measure yet.
    $largest = 0;
    foreach (glob($backupDir . 'backup_*.zip') as $existing) {
        $largest = max($largest, (int)@filesize($existing));
    }
    $required = max((int)($largest * 1.5), 512 * 1024 * 1024);

    if (function_exists('fxwp_get_available_storage_space')) {
        $available = fxwp_get_available_storage_space();
    } else {
        $available = @disk_free_space($backupDir);
        if ($available === false) {
            return; // can't tell -- don't block
        }
    }

    if ($available !== false && $available < $required) {
        throw new Exception(
            'not enough free space for backup. Available: ' . fxwp_format_file_size($available)
            . ', required: ~' . fxwp_format_file_size($required) . '.'
        );
    }
}

function fxwp_fix_execution_time()
{
    $max_execution_time = ini_get('max_execution_time');
    if ($max_execution_time < 180) {
        @ini_set('max_execution_time', 180);
    }
    $userIniPath = ABSPATH . '.user.ini';

    if (file_exists($userIniPath)) {
        $currentSettings = file_get_contents($userIniPath);
        if (strpos($currentSettings, 'max_execution_time') === false) {
            @file_put_contents($userIniPath, "\nmax_execution_time=180", FILE_APPEND);
        } else {
            $currentSettings = preg_replace('/max_execution_time\s*=\s*\d+/', 'max_execution_time=180', $currentSettings);
            @file_put_contents($userIniPath, $currentSettings);
        }
    }
}

/* -------------------------------------------------------------------------- */
/*  Retention                                                                  */
/* -------------------------------------------------------------------------- */

function fxwp_get_backup_timestamp($filename)
{
    $base = basename($filename);
    $base = str_replace(array('.sql', '.zip'), '', $base);
    $base = str_replace('backup_', '', $base);
    $parts = explode('_', $base);

    $date = $parts[0];
    $time = isset($parts[1]) ? str_replace('-', ':', $parts[1]) : '00:00:00';

    // Filenames are written in the site's local timezone (see fxwp_backup_start).
    // get_gmt_from_date() interprets the string as local time and returns the
    // correct UTC epoch, so retention math and date_i18n() display stay correct.
    return (int) get_gmt_from_date($date . ' ' . $time, 'U');
}

function fxwp_delete_expired_backups()
{
    $backupDir = ABSPATH . 'wp-content/fxwp-backups/';
    $files = glob($backupDir . 'backup_*.zip');
    if (!is_array($files)) {
        $files = array();
    }

    // Oldest first.
    array_multisort(
        array_map('fxwp_get_backup_timestamp', $files), SORT_NUMERIC, SORT_ASC,
        $files
    );

    $now = time();
    $hourly = $daily = $monthly = array();

    foreach ($files as $file) {
        $fileTime = fxwp_get_backup_timestamp($file);
        $hoursOld = floor(($now - $fileTime) / HOUR_IN_SECONDS);
        $daysOld  = floor(($now - $fileTime) / DAY_IN_SECONDS);

        if ($hoursOld < FXWP_BACKUP_DAYS_SON) {
            $hourly[] = $file;
        } elseif ($daysOld < FXWP_BACKUP_DAYS_FATHER) {
            $daily[] = $file;
        } elseif ($daysOld < FXWP_BACKUP_DAYS_GRANDFATHER) {
            $monthly[] = $file;
        }
        // older than grandfather -> not kept (deleted below)
    }

    // Keep one backup per hour / day / month respectively.
    $keptHourly = $keptDaily = $keptMonthly = array();
    foreach ($hourly as $file) {
        $keptHourly[date('Y-m-d-H', fxwp_get_backup_timestamp($file))] = $file;
    }
    foreach ($daily as $file) {
        $keptDaily[date('Y-m-d', fxwp_get_backup_timestamp($file))] = $file;
    }
    foreach ($monthly as $file) {
        $keptMonthly[date('Y-m', fxwp_get_backup_timestamp($file))] = $file;
    }

    $keptBackups = array_merge(
        array_values($keptHourly),
        array_values($keptDaily),
        array_values($keptMonthly)
    );

    // Delete zips (and their .sql) we are not keeping.
    foreach ($files as $file) {
        if (!in_array($file, $keptBackups, true)) {
            @unlink($file);
            @unlink($file . '.sql');
        }
    }

    // Orphaned .sql (a DB dump whose .zip never completed) is a *valuable*
    // database-only backup, not garbage. We keep it -- and crucially do NOT email
    // about it on every run (that was the noise customers complained about) --
    // removing it only once it is older than the grandfather window. The in-
    // progress job's own dump is skipped.
    $state = get_option('fxwp_backup_state', array());
    $activeBase = (!empty($state['active']) && !empty($state['base'])) ? $state['base'] : null;

    foreach (glob($backupDir . 'backup_*.zip.sql') as $sql) {
        $zip = substr($sql, 0, -4); // strip ".sql"
        if (file_exists($zip)) {
            continue; // has a matching zip -> not an orphan
        }
        $base = basename($zip, '.zip');
        if ($activeBase !== null && $base === $activeBase) {
            continue; // backup currently in progress
        }
        $daysOld = floor(($now - fxwp_get_backup_timestamp($zip)) / DAY_IN_SECONDS);
        if ($daysOld >= FXWP_BACKUP_DAYS_GRANDFATHER) {
            @unlink($sql);
        }
    }

    // Clean up stale manifests left by aborted jobs.
    foreach (glob($backupDir . '.backup_*.files') as $mf) {
        if (@filemtime($mf) < $now - DAY_IN_SECONDS) {
            @unlink($mf);
        }
    }
}

/* -------------------------------------------------------------------------- */
/*  Restore / delete / list                                                    */
/* -------------------------------------------------------------------------- */

function fxwp_restore_backup($backupFile)
{
    $backupFile = ABSPATH . 'wp-content/fxwp-backups/' . basename($backupFile);

    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== true) {
        exit("Failed to open backup file $backupFile");
    }
    $zip->extractTo(ABSPATH);
    $zip->close();

    $dumpFile = $backupFile . '.sql';
    $mysqli = fxwp_backup_db_connect();

    $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
    $mysqli->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');

    $sqlStatements = file_get_contents($dumpFile);
    if ($mysqli->multi_query($sqlStatements)) {
        do {
            $mysqli->store_result();
        } while ($mysqli->more_results() && $mysqli->next_result());
    }

    $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
    $mysqli->query('SET SQL_MODE=""');
    $mysqli->close();
}

function fxwp_delete_backup($backupFile = null)
{
    $backupDir = ABSPATH . 'wp-content/fxwp-backups/';

    if ($backupFile) {
        $target = $backupDir . basename($backupFile);
    } else {
        $files = glob($backupDir . '*.zip');
        $target = $files ? $files[0] : null;
    }
    if ($target) {
        @unlink($target);
        @unlink($target . '.sql');
    }
}

function fxwp_list_backups()
{
    $backupDir = ABSPATH . 'wp-content/fxwp-backups/';
    $files = glob($backupDir . '*.zip');
    return array_map('basename', $files ?: array());
}

/* -------------------------------------------------------------------------- */
/*  External triggers (reliable cron without depending on site traffic)        */
/* -------------------------------------------------------------------------- */

/**
 * REST endpoint so an external/system cron (e.g. All-Inkl) can drive backups
 * directly, independent of site traffic and WP-Cron:
 *
 *   https://EXAMPLE.com/wp-json/fxwp/v1/run-backup-cron?key=<fxwp_api_key>
 *
 * Each call processes one slice, so configuring the host cron to hit it every
 * few minutes lets even large sites finish a backup across many short requests.
 */
add_action('rest_api_init', function () {
    register_rest_route('fxwp/v1', '/run-backup-cron', array(
        'methods'             => 'GET',
        'callback'            => 'fxwp_backup_rest_run',
        'permission_callback' => '__return_true',
    ));
});

function fxwp_backup_rest_run($request)
{
    $key = (string)$request->get_param('key');
    $expected = (string)get_option('fxwp_api_key');
    if ($key === '' || $expected === '' || !hash_equals($expected, $key)) {
        return new WP_REST_Response(array('ok' => false, 'error' => 'invalid key'), 403);
    }

    do_action('fxwp_backup_task');

    $state = get_option('fxwp_backup_state', array());
    return new WP_REST_Response(array(
        'ok'     => true,
        'active' => !empty($state['active']),
        'phase'  => isset($state['phase']) ? $state['phase'] : null,
    ), 200);
}

/**
 * WP-CLI: `wp fxwp-backup` advances the backup by one slice (or starts one).
 * Pair with a system cron for the most reliable, timeout-free backups:
 *   * * * * * cd /path/to/wp && wp fxwp-backup --quiet
 */
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('fxwp-backup', function () {
        do_action('fxwp_backup_task');
        $s = get_option('fxwp_backup_state', array());
        WP_CLI::success('Backup tick done. ' . (!empty($s['active'])
            ? 'In progress (phase: ' . $s['phase'] . ').'
            : 'No job active.'));
    });
}
