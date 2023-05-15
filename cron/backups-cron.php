<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!wp_next_scheduled('fxwp_backup_task')) {
    wp_schedule_event(time(), 'daily', 'fxwp_backup_task');
}

add_action('fxwp_backup_task', 'fxwp_create_backup');

function fxwp_create_backup()
{
    // Define the WordPress root directory
    $rootDir = ABSPATH;

    // Define the backup directory
    $backupDir = $rootDir . 'wp-content/wpwh-backups/';

    // Check if the backup directory exists, if not, create it
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Define the name of the backup file
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.zip';

    // Dump the Database
    $dumpFile = $backupFile . '.sql';

    // take wp-configs DB credentials
    exec("mysqldump --user={" . DB_USER . "} --password={" . DB_PASSWORD . "} --host={" . DB_HOST . "} " . DB_NAME . " > $dumpFile");

    // Create a new zip archive
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit("Failed to create backup file $backupFile");
    }

    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically) and skip wp-config.php and skip everythign under wp-content/wpwh-backups
        if (!$file->isDir() && strpos($name, '/wp-content/uploads/') === false && strpos($name, '/wp-config.php') === false && strpos($name, '/wp-content/wpwh-backups/') === false) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootDir));

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

    // Delete old backups
    $files = glob($backupDir . '*.zip'); // Get all zip files
    $now = time();

    foreach ($files as $file) {
        if (is_file($file)) {
            // Delete the file if it's older than X days
            if ($now - filemtime($file) >= 60 * 60 * 24 * fxwp_BACKUP_DAYS) { // Replace X with the number of days
                unlink($file);
            }
        }
    }
}

function fxwp_restore_backup($backupFile)
{
    // Define the backup directory
    $backupFile = ABSPATH . 'wp-content/wpwh-backups/' . $backupFile;

    // Create a new zip archive
    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== true) {
        exit("Failed to open backup file $backupFile");
    }

    // Extract the backup file
    $zip->extractTo(ABSPATH);
    $zip->close();

    // restore the database
    $dumpFile = $backupFile . '.sql';
    exec("mysql --user={" . DB_USER . "} --password={" . DB_PASSWORD . "} --host={" . DB_HOST . "} " . DB_NAME . " < $dumpFile");
}

function fxwp_delete_backup()
{
    // Define the WordPress root directory
    $rootDir = ABSPATH;

    // Define the backup directory
    $backupDir = $rootDir . 'wp-content/wpwh-backups/';

    // Get the latest backup file
    $files = glob($backupDir . '*.zip');
    $latestBackup = $files[0];

    // Delete the backup file
    unlink($latestBackup);
    unlink($latestBackup . '.sql');
}


function fxwp_list_backups()
{
    // Define the WordPress root directory
    $rootDir = ABSPATH;

    // Define the backup directory
    $backupDir = $rootDir . 'wp-content/wpwh-backups/';

    // Get all backup files
    $files = glob($backupDir . '*.zip');

    // make an array of the files
    $files = array_map(function ($file) {
        return basename($file);
    }, $files);

    return $files;
}