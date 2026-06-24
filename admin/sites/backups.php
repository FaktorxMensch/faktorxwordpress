<?php
require_once plugin_dir_path(__FILE__) . '../../includes/helpers.php';
function fxwp_mock_backups()
{
    // delete all backups
    $rootDir = ABSPATH;
    $backupDir = $rootDir . 'wp-content/fxwp-backups/';
    $files = glob($backupDir . '*.zip*');
    foreach ($files as $file) {
        unlink($file);
    }

    $hours = 90 * 24;
    // create empty files for testing
    for ($i = 0; $i < $hours; $i += 1) {
        $date = date('Y-m-d_H-i-s', strtotime("-$i hours"));
        $file = "backup_$date.zip";
        $path = WP_CONTENT_DIR . "/fxwp-backups/$file";
        if (!file_exists($path)) {
            file_put_contents($path, '');
            file_put_contents($path . ".sql", '');
        }
    }

}

function fxwp_get_backup_tag($backup)
{
    // Single source of truth shared with the retention engine
    // (fxwp_backup_tier in cron/backups-cron.php).
    switch (fxwp_backup_tier(fxwp_get_backup_timestamp($backup))) {
        case 'son':
            return __('Sohn', 'fxwp');
        case 'father':
            return __('Vater', 'fxwp');
        default:
            return __('Großvater', 'fxwp');
    }
}

// testing
//add_action('init', 'fxwp_mock_backups');
//add_action('init', 'fxwp_delete_expired_backups');

/**
 * Stream a backup file to the browser through PHP.
 *
 * Backups are no longer web-accessible (the directory is denied via .htaccess),
 * so downloads go through here where we can enforce a capability check, the
 * existing nonce, and -- crucially -- guard against path traversal via the
 * user-supplied filename.
 */
function fxwp_download_backup($backupFile, $type = 'files')
{
    if (!current_user_can('administrator')) {
        wp_die('Insufficient permissions');
    }

    // Reject anything that isn't a plain backup filename to stop ../ traversal.
    $backupFile = basename(wp_unslash($backupFile));
    if (!preg_match('/^backup_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.zip$/', $backupFile)) {
        wp_die('Invalid backup file');
    }

    $path = WP_CONTENT_DIR . '/fxwp-backups/' . $backupFile;
    if ($type === 'db') {
        $path .= '.sql';
    }

    // Make sure the resolved path is still inside the backup directory.
    $backupDir = realpath(WP_CONTENT_DIR . '/fxwp-backups/');
    $realPath = realpath($path);
    if ($realPath === false || strpos($realPath, $backupDir) !== 0 || !is_file($realPath)) {
        wp_die('Backup file not found');
    }

    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($realPath) . '"');
    header('Content-Length: ' . filesize($realPath));
    readfile($realPath);
    exit;
}

function fxwp_backups_page()
{
    // Save off-site (S3) settings if the form was submitted (fxm_admin only).
    fxwp_s3_handle_settings_post();

    $s3_test_result = null;

    // Check if a backup action was submitted
    if (isset($_GET['backup_action'])) {

        // check nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'fxwp_critical')) {
            wp_die('Security check');
        }

        // Run the appropriate function based on the submitted action
        switch ($_GET['backup_action']) {
            case 'create':
                // Replace this with your actual backup creation method
                fxwp_create_backup();
                break;
            case 'restore':
                // Replace this with your actual backup restoration method
                // You would also need to pass the backup file name or other identifier as a parameter
                fxwp_restore_backup($_GET['backup_file']);
                break;
            case 'delete':
                // Replace this with your actual backup deletion method
                // You would also need to pass the backup file name or other identifier as a parameter
                fxwp_delete_backup($_GET['backup_file']);
                break;
            case 'cron':
                error_log('Manually running cron for backups');
                // do cron manually but first sent the user to the backup page
                wp_redirect(admin_url('admin.php?page=fxwp-backups'));
                do_action('fxwp_backup_task');
                break;
            case 'download':
                fxwp_download_backup($_GET['backup_file'], isset($_GET['type']) ? $_GET['type'] : 'files');
                break;
            case 's3test':
                $s3_test_result = fxwp_s3_test();
                break;
        }
    }

    // Get a list of existing backups
    // Replace this with your actual method for retrieving a list of backups
    $backups = fxwp_list_backups();
    ?>
    <div class="wrap">
        <h1><?php _e('Archiv', 'fxwp'); ?></h1>
        <p><?php _e('Create and restore backups of your WordPress site.', 'fxwp'); ?></p>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=create'), 'fxwp_critical'); ?>"
           class="button button-primary"> <?php _e('Neue Sicherung erstellen', 'fxwp'); ?> </a>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=cron'), 'fxwp_critical'); ?>"
           class="button button-primary"> <?php _e('Cron manuell ausführen', 'fxwp'); ?> </a>

        <br>
        <?php fxwp_show_deactivated_feature_warning('fxwp_deact_backups'); ?>
        <br>

        <?php if (current_user_can('fxm_admin')):
            $s3_enabled = function_exists('fxwp_s3_enabled') && fxwp_s3_enabled();
            $s3_last = (int) get_option('fxwp_s3_last_upload', 0);
            $s3_err = get_option('fxwp_s3_last_error', '');
            $s3cfg = function_exists('fxwp_s3_config') ? fxwp_s3_config() : array('secret' => '');
            $secret_set = $s3cfg['secret'] !== '';
            ?>
            <?php if ($s3_test_result): ?>
                <div class="notice notice-<?php echo $s3_test_result['ok'] ? 'success' : 'error'; ?> is-dismissible">
                    <p><?php echo esc_html($s3_test_result['message']); ?></p></div>
            <?php endif; ?>
            <div class="postbox" style="margin-top:15px">
                <div class="postbox-header">
                    <h2 class="hndle" style="padding:8px 12px"><?php _e('Off-Site-Sicherung (S3)', 'fxwp'); ?></h2>
                </div>
                <div class="inside">
                    <p>
                        <?php _e('Status:', 'fxwp'); ?>
                        <?php if ($s3_enabled): ?>
                            <strong style="color:#46b450"><?php _e('aktiv', 'fxwp'); ?></strong>
                        <?php else: ?>
                            <strong style="color:#dc3232"><?php _e('nicht konfiguriert', 'fxwp'); ?></strong>
                        <?php endif; ?>
                        <?php if ($s3_last): ?>
                            &middot; <?php printf(esc_html__('Letzter Upload: %s', 'fxwp'), esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $s3_last))); ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($s3_err): ?>
                        <div class="notice notice-error inline"><p><?php printf(esc_html__('Letzter Fehler: %s', 'fxwp'), esc_html($s3_err)); ?></p></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('fxwp_s3_settings', 'fxwp_s3_settings_nonce'); ?>
                        <table class="form-table">
                            <tr><th><label><?php _e('Endpoint-URL', 'fxwp'); ?></label></th>
                                <td><input type="text" name="fxwp_s3_endpoint" class="regular-text" placeholder="https://s3.example.com" value="<?php echo esc_attr(get_option('fxwp_s3_endpoint', '')); ?>"></td></tr>
                            <tr><th><label><?php _e('Region', 'fxwp'); ?></label></th>
                                <td><input type="text" name="fxwp_s3_region" class="regular-text" placeholder="us-east-1" value="<?php echo esc_attr(get_option('fxwp_s3_region', '')); ?>"></td></tr>
                            <tr><th><label><?php _e('Bucket', 'fxwp'); ?></label></th>
                                <td><input type="text" name="fxwp_s3_bucket" class="regular-text" value="<?php echo esc_attr(get_option('fxwp_s3_bucket', '')); ?>"></td></tr>
                            <tr><th><label><?php _e('Pfad-Präfix (optional)', 'fxwp'); ?></label></th>
                                <td><input type="text" name="fxwp_s3_prefix" class="regular-text" placeholder="<?php echo esc_attr(preg_replace('#^https?://#', '', get_site_url()) . '/'); ?>" value="<?php echo esc_attr(get_option('fxwp_s3_prefix', '')); ?>"></td></tr>
                            <tr><th><label><?php _e('Zugriffsschlüssel', 'fxwp'); ?></label></th>
                                <td><input type="text" name="fxwp_s3_access_key" class="regular-text" autocomplete="off" value="<?php echo esc_attr(get_option('fxwp_s3_access_key', '')); ?>"></td></tr>
                            <tr><th><label><?php _e('Geheimer Zugriffsschlüssel', 'fxwp'); ?></label></th>
                                <td><input type="password" name="fxwp_s3_secret_key" class="regular-text" autocomplete="new-password" placeholder="<?php echo $secret_set ? esc_attr__('•••••••• (gesetzt – zum Ändern neu eingeben)', 'fxwp') : esc_attr__('nicht gesetzt', 'fxwp'); ?>">
                                    <p class="description"><?php _e('Wird verschlüsselt gespeichert. Leer lassen, um den bestehenden Schlüssel zu behalten.', 'fxwp'); ?></p></td></tr>
                            <tr><th><label><?php _e('Upload-Modus', 'fxwp'); ?></label></th>
                                <td><?php $mode = get_option('fxwp_s3_upload_mode', 'tiered'); ?>
                                    <select name="fxwp_s3_upload_mode">
                                        <option value="tiered" <?php selected($mode, 'tiered'); ?>><?php _e('Vater + Großvater (1×/Tag + 1×/Monat)', 'fxwp'); ?></option>
                                        <option value="monthly" <?php selected($mode, 'monthly'); ?>><?php _e('Nur Großvater (1×/Monat)', 'fxwp'); ?></option>
                                        <option value="all" <?php selected($mode, 'all'); ?>><?php _e('Jedes Backup', 'fxwp'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('„Vater + Großvater" lädt pro Tag ein Vater- und pro Monat ein Großvater-Backup hoch, abgelegt unter <code>&lt;webseite&gt;/father/</code> bzw. <code>&lt;webseite&gt;/grandfather/</code> und mit Objekt-Tag <code>tier=…</code> für getrennte AWS-Lifecycle-Regeln.', 'fxwp'); ?></p></td></tr>
                            <?php
                            $classes = array('STANDARD' => 'Standard', 'STANDARD_IA' => 'Standard-IA', 'GLACIER_IR' => 'Glacier Instant Retrieval', 'GLACIER' => 'Glacier Flexible', 'DEEP_ARCHIVE' => 'Glacier Deep Archive');
                            $cf = get_option('fxwp_s3_class_father', 'STANDARD_IA');
                            $cg = get_option('fxwp_s3_class_grandfather', 'GLACIER');
                            ?>
                            <tr><th><label><?php _e('Speicherklasse Vater', 'fxwp'); ?></label></th>
                                <td><select name="fxwp_s3_class_father"><?php foreach ($classes as $v => $l) {
                                        echo '<option value="' . esc_attr($v) . '" ' . selected($cf, $v, false) . '>' . esc_html($l) . '</option>';
                                    } ?></select>
                                    <p class="description"><?php _e('Empfehlung Standard-IA: Glacier hätte bei 30-Tage-Aufbewahrung eine 90-Tage-Mindestgebühr.', 'fxwp'); ?></p></td></tr>
                            <tr><th><label><?php _e('Speicherklasse Großvater', 'fxwp'); ?></label></th>
                                <td><select name="fxwp_s3_class_grandfather"><?php foreach ($classes as $v => $l) {
                                        echo '<option value="' . esc_attr($v) . '" ' . selected($cg, $v, false) . '>' . esc_html($l) . '</option>';
                                    } ?></select>
                                    <p class="description"><?php _e('Glacier ist hier ideal (Aufbewahrung > 90 Tage).', 'fxwp'); ?></p></td></tr>
                        </table>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Speichern', 'fxwp'); ?>">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=s3test'), 'fxwp_critical'); ?>" class="button button-secondary"><?php _e('Verbindung testen', 'fxwp'); ?></a>
                    </form>
                    <p class="description"><?php _e('Sicherer: <code>FXWP_S3_*</code>-Konstanten in der <code>wp-config.php</code> definieren (haben Vorrang vor diesen Feldern).', 'fxwp'); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($backups)): ?>
            <table class="wp-list-table widefat fixed striped">
                <?php
                // reverse backups
                $backups = array_reverse($backups);
                foreach ($backups

                as $backup):
                ?>
                <tr>
                    <td>
                        <?php
                        $ts = fxwp_get_backup_timestamp($backup);
                        echo "<b>";
                        printf(esc_html__('Sicherung vom %s um %s', 'fxwp'), date_i18n(get_option('date_format'), $ts), date_i18n(get_option('time_format'), $ts));
                        echo '</b><br>';

                        // get filesize
                        $file = WP_CONTENT_DIR . '/fxwp-backups/' . $backup;
                        if (file_exists($file)) {
                            $size = filesize($file);
                            $size = size_format($size);
                            printf('Dateigröße: %s', $size);
                            $db_file = WP_CONTENT_DIR . '/fxwp-backups/' . $backup . '.sql';
                            echo " | ";
                            if (file_exists($db_file)) {
                                $size = filesize($db_file);
                                $size = size_format($size);
                                printf('Datenbankgröße: %s', $size);
                            } else {
                                printf(' (%s)', __('Datenbank nicht gefunden', 'fxwp'));
                            }
                        } else {
                            printf(' (%s)', __('Datei nicht gefunden', 'fxwp'));
                        }
                        ?>
                    </td>
                    <td align="center">
                        <?php
                        // add tag if backup is grandfather, father or son
                        $tag = fxwp_get_backup_tag($backup);
                        if ($tag) {
                            echo "<span class='backup-gvs " . $tag . "'>$tag</span>";
                            echo "<style>
                                    .backup-gvs {
                                        display: inline-block;
                                        margin: 0px;
                                        //border: 2px solid;
                                        color: green;
                                        background-color: #46b450;
                                        //padding: 0 10px; 
                                        padding: 4px 12px; 
                                        border-radius: 3px;
                                        line-height: 2;
                                    }
                                    .backup-gvs.Großvater {
                                        color: #2196F3;
                                        background-color: rgba(33, 150, 243, 0.12);
                                        border-color: rgba(33, 150, 243, 0.25);
                                    }
                                    .backup-gvs.Vater {
                                        color: #ff9800;
                                        background-color: rgba(255, 152, 0, 0.12);
                                        border-color: rgba(255, 152, 0, 0.25);
                                    }
                                    .backup-gvs.Sohn {
                                        color: #4CAF50;
                                        background-color: rgba(76, 175, 80, 0.12);
                                        border-color: rgba(76, 175, 80, 0.25);
                                    }
                                </style>";
                        }
                        ?>
                    </td>
                    <td align="right">
                        <?php if (filesize($db_file) > 1000 && filesize($file) > 1000) { ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=restore&backup_file=' . $backup), 'fxwp_critical'); ?>"
                               class="button button-secondary"> <?php _e('Restore', 'fxwp'); ?> </a>
                        <?php } else {
                            echo "<a class='button button-warning'>Backup fehlerhaft!</a>";
                        } ?>
                        <!-- have download files and db backup (routed through a
                             capability- and nonce-checked PHP handler since the
                             backup directory is no longer web-accessible) -->
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=download&type=db&backup_file=' . $backup), 'fxwp_critical'); ?>"
                           class="button button-secondary">
                            <?php _e('Datenbank herunterladen', 'fxwp'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=download&type=files&backup_file=' . $backup), 'fxwp_critical'); ?>"
                           class="button button-secondary">
                            <?php _e('Dateien herunterladen', 'fxwp'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=delete&backup_file=' . $backup), 'fxwp_critical'); ?>"
                           class="button button-delete"> <?php _e('Delete', 'fxwp'); ?> </a>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        <?php else: ?>
            <p><?php _e('No backups found.', 'fxwp'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
