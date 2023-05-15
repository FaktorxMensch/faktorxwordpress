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
    $backupDir = $rootDir . 'wp-content/fxwp-backups/';

    // Check if the backup directory exists, if not, create it
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // Define the name of the backup file
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.zip';

    // Dump the Database
    $dumpFile = $backupFile . '.sql';

    // take wp-configs DB credentials
    $output = array();
    $returnValue = null;
    exec("mysqldump --user={" . DB_USER . "} --password={" . DB_PASSWORD . "} --host={" . DB_HOST . "} " . DB_NAME . " > $dumpFile", $output, $returnValue);

    // if mysqldump failed
    if ($returnValue !== 0) {
        // fall back to PHP
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_error) {
            die('Connect Error (' . $mysqli->connect_errno . ') '
                . $mysqli->connect_error);
        }

        $tables = array();
        $result = $mysqli->query('SHOW TABLES');
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $tables[] = $row[0];
        }

        $sql = 'SET FOREIGN_KEY_CHECKS=0;' . "\n";
        foreach ($tables as $table) {
            $result = $mysqli->query('SELECT * FROM ' . $table);
            $numFields = $result->field_count;
            $numRows = $result->num_rows;
            $i = 0;

            $sql .= 'DROP TABLE IF EXISTS ' . $table . ';';
            $row2 = $mysqli->query('SHOW CREATE TABLE ' . $table)->fetch_row();
            $sql .= "\n\n" . $row2[1] . ";\n\n";

            for ($j = 0; $j < $numFields; $j++) {
                while ($row = $result->fetch_row()) {
                    if ($i % $numRows == 0) {
                        $sql .= 'INSERT INTO ' . $table . ' VALUES(';
                    } else {
                        $sql .= '(';
                    }

                    for ($k = 0; $k < $numFields; $k++) {
                        if (isset($row[$k])) {
                            $sql .= '"' . $mysqli->real_escape_string($row[$k]) . '"';
                        } else {
                            $sql .= '""';
                        }
                        if ($k < $numFields - 1) {
                            $sql .= ',';
                        }
                    }

                    if ((($i + 1) % $numRows) == 0) {
                        $sql .= ");";
                    } else {
                        $sql .= "),";
                    }
                    $i++;
                }
            }
        }
        $sql .= "\n\n\n";
    }

    $sql .= 'SET FOREIGN_KEY_CHECKS=1;';

    file_put_contents($dumpFile, $sql);

    $mysqli->close();

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
        // Skip directories (they would be added automatically) and skip wp-config.php and skip everythign under wp-content/fxwp-backups
        if (!$file->isDir() && strpos($name, '/wp-content/uploads/') === false && strpos($name, '/wp-config.php') === false && strpos($name, '/wp-content/fxwp-backups/') === false) {
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
    $backupFile = ABSPATH . 'wp-content/fxwp-backups/' . $backupFile;

    // Create a new zip archive
    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== true) {
        exit("Failed to open backup file $backupFile");
    }

    // Extract the backup file
    $zip->extractTo(ABSPATH);
    $zip->close();

    // Restore the database
    $dumpFile = $backupFile . '.sql';

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if ($mysqli->connect_error) {
        die('Connect Error (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
    }

    $mysqli->query('SET FOREIGN_KEY_CHECKS=0');
    $mysqli->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');

    // Read the SQL dump file
    $sqlStatements = file_get_contents($dumpFile);

    // Execute the SQL statements
    if ($mysqli->multi_query($sqlStatements)) {
        do {
            // Fetch the result of each query
            $mysqli->store_result();
        } while ($mysqli->more_results() && $mysqli->next_result());
    }

    $mysqli->query('SET FOREIGN_KEY_CHECKS=1');
    $mysqli->query('SET SQL_MODE=""');

    $mysqli->close();

}

function fxwp_delete_backup()
{
    // Define the WordPress root directory
    $rootDir = ABSPATH;

    // Define the backup directory
    $backupDir = $rootDir . 'wp-content/fxwp-backups/';

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
    $backupDir = $rootDir . 'wp-content/fxwp-backups/';

    // Get all backup files
    $files = glob($backupDir . '*.zip');

    // make an array of the files
    $files = array_map(function ($file) {
        return basename($file);
    }, $files);

    return $files;
}
