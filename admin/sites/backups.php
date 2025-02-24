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
    //Backup is grandfather if it is older than 30 days
    if (fxwp_get_backup_timestamp($backup) < strtotime('-30 days')) {
        return __('Großvater', 'fxwp');
    }
    //Backup is father if it is older than 7 days
    if (fxwp_get_backup_timestamp($backup) < strtotime('-7 days')) {
        return __('Vater', 'fxwp');
    }
    //Backup is son if it is younger than 7 days
    if (fxwp_get_backup_timestamp($backup) > strtotime('-7 days')) {
        return __('Sohn', 'fxwp');
    }
}

// testing
//add_action('init', 'fxwp_mock_backups');
//add_action('init', 'fxwp_delete_expired_backups');

function fxwp_backups_page()
{
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
                        <!-- have download files and db backup -->
                        <a href="<?php echo esc_url(content_url('fxwp-backups/' . $backup . '.sql')); ?>"
                           class="button button-secondary">
                            <?php _e('Datenbank herunterladen', 'fxwp'); ?>
                        </a>
                        <a href="<?php echo esc_url(content_url('fxwp-backups/' . $backup)); ?>"
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
