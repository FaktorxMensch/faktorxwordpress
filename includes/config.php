<?php
if (defined('FXWP_VERSION'))
    return;
// Plugin functionality
// read plugin version from plugin header
$plugin_data = get_file_data(__DIR__ . '/../faktorxwordpress.php', array('Version' => 'Version'), false);
define('FXWP_VERSION', $plugin_data['Version']);

//Get plugin root path, so go one directory up
define('FXWP_PLUGIN_DIR', plugin_dir_path(__DIR__ ));

// check if we are in a local environment
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '.local') !== false) {
    define('FXWP_LOCAL_ENV', true);
//    define('FXWP_API_URL', 'http://localhost:3000/api/fxwp');
} else {
    define('FXWP_LOCAL_ENV', false);
}

// API URL
define('FXWP_API_URL', 'https://cors.faktorxmensch.com/api/fxwp');
// P2 URL
define('FXWP_P2_URL', 'https://p2.faktorxmensch.com');

// Server Settings
// use option to store setting, have the option default to 20 GB
define('FXWP_STORAGE_LIMIT', get_option('fxwp_storage_limit', 20 * 1024 * 1024 * 1024));
// set the option if not set
if (!get_option('fxwp_storage_limit')) {
    update_option('fxwp_storage_limit', 20 * 1024 * 1024 * 1024);
}

// GVS Intervalle
// wir wollen die letzten 24 stunden, dann von jedem tag der letzten woche, dann von jedem monat der letzten 2 monate
// Replace the existing backup constants with these
define('FXWP_BACKUP_INTERVAL', get_option('fxwp_backup_interval', 'twicedaily'));
define('FXWP_BACKUP_DAYS_SON', get_option('fxwp_backup_days_son', 3));
define('FXWP_BACKUP_DAYS_FATHER', get_option('fxwp_backup_days_father', 12));
define('FXWP_BACKUP_DAYS_GRANDFATHER', get_option('fxwp_backup_days_grandfather', 90));


define('FXWP_THEME_REPO_URI', 'https://github.com/FaktorxMensch/faktorxwordpress-theme/archive/refs/heads/main.zip');

define('FXWP_ERROR_EMAIL', 'wp@faktorxmensch.com');
