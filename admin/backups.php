<?php
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
        }
    }

    // Get a list of existing backups
    // Replace this with your actual method for retrieving a list of backups
    $backups = fxwp_list_backups();
    ?>
    <div class="wrap">
        <h1><?php _e('Backup Manager', 'fxwp'); ?></h1>
        <p><?php _e('Create and restore backups of your WordPress site.', 'fxwp'); ?></p>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=create'), 'fxwp_critical'); ?>"
           class="button button-primary"> <?php _e('Neue Sicherung erstellen', 'fxwp'); ?> </a>

        <br>
        <br>
        <?php if (!empty($backups)): ?>
            <table class="wp-list-table widefat fixed striped">
                <?php foreach ($backups

                as $backup): ?>
                <tr>
                    <td>
                        <?php
                        $backup2 = str_replace('backup_', '', $backup);
                        $backup2 = str_replace('.zip', '', $backup2);
                        $parts = explode('_', $backup2);

                        $date = $parts[0];
                        $time = $parts[1];
                        $date = str_replace('-', '.', $date);
                        $time = str_replace('-', ':', $time);

                        $ts = strtotime($date . ' ' . $time);
                        echo "<b>";printf(esc_html__('Sicherung vom %s um %s', 'fxwp'), date_i18n(get_option('date_format'), $ts), date_i18n(get_option('time_format'), $ts));
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
                    <td align="right">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fxwp-backups&backup_action=restore&backup_file=' . $backup), 'fxwp_critical'); ?>"
                           class="button button-secondary"> <?php _e('Restore', 'fxwp'); ?> </a>
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