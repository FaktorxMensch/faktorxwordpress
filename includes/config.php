<?php
if (!defined('FXWP_API_URL')) {
    define('FXWP_API_URL', 'https://p2.faktorxmensch.com/api/fxwp');
}
if (!defined('FXWP_VERSION')) {
    define('FXWP_VERSION', '0.2');
}
if (!defined('FXWP_BACKUP_DAYS')) {
    define('FXWP_BACKUP_DAYS', 14);
}
if(!defined('FXWP_STORAGE_LIMIT')) {
    define('FXWP_STORAGE_LIMIT',  20 * 1024 * 1024 * 1024); // 20GB
}