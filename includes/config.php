<?php
if (defined('FXWP_VERSION'))
    return;
// Plugin functionality
define('FXWP_VERSION', '0.2');
define('FXWP_API_URL', 'https://p2.faktorxmensch.com/api/fxwp');

// Server Settings
define('FXWP_STORAGE_LIMIT', 20 * 1024 * 1024 * 1024); // 20GB

// GVS Intervalle
define('FXWP_BACKUP_DAYS_SON', 1); // Backup jeden Tag
define('FXWP_BACKUP_DAYS_FATHER', 7); // Backup jede Woche
define('FXWP_BACKUP_DAYS_GRANDFATHER', 30); // Backup jeden Monat
// how many grandfathers to keep
define('FXWP_BACKUP_GRANDFATHERS', 2);
