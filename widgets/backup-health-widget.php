<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Backup-health surfacing for fxm_admin.
 *
 * - A dashboard ("Übersicht") widget showing how often the backup cron actually
 *   fires and when the last successful backup ran.
 * - An admin notice (shown on every admin page, i.e. right after login) that
 *   warns loudly when the backup cron has run less than once in the last 7 days,
 *   which means backups have effectively stopped (no traffic to drive WP-Cron
 *   and no external/system cron configured).
 *
 * Both are restricted to fxm_admin so customers don't see internal plumbing.
 */

function fxwp_backup_health_widget_render()
{
    $h = fxwp_backup_cron_health();

    $fmt = function ($ts) {
        if (!$ts) {
            return __('nie', 'fxwp');
        }
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $ts)
            . ' (' . human_time_diff($ts, time()) . ')';
    };

    $statusColor = $h['healthy'] ? '#46b450' : '#dc3232';
    $statusText  = $h['healthy']
        ? __('Backup-Cron läuft regelmäßig', 'fxwp')
        : __('Backup-Cron läuft NICHT regelmäßig', 'fxwp');

    echo '<p style="font-weight:600;color:' . esc_attr($statusColor) . '">'
        . '&#9679; ' . esc_html($statusText) . '</p>';

    echo '<ul style="margin-left:1em;list-style:disc">';
    echo '<li>' . sprintf(
        esc_html__('Cron-Läufe in den letzten 7 Tagen: %d', 'fxwp'),
        (int)$h['runs_last_7d']
    ) . '</li>';
    echo '<li>' . sprintf(
        esc_html__('Letzter Cron-Lauf: %s', 'fxwp'),
        esc_html($fmt($h['last_run']))
    ) . '</li>';
    echo '<li>' . sprintf(
        esc_html__('Letztes erfolgreiches Backup: %s', 'fxwp'),
        esc_html($fmt($h['last_backup']))
    ) . '</li>';
    if (function_exists('fxwp_s3_enabled') && fxwp_s3_enabled()) {
        echo '<li>' . sprintf(
            esc_html__('Letzte Off-Site-Kopie (S3): %s', 'fxwp'),
            esc_html($fmt((int)get_option('fxwp_s3_last_upload', 0)))
        ) . '</li>';
        $s3err = get_option('fxwp_s3_last_error', '');
        if ($s3err) {
            echo '<li style="color:#dc3232">' . esc_html__('S3-Fehler: ', 'fxwp') . esc_html($s3err) . '</li>';
        }
    }
    echo '</ul>';

    $state = get_option('fxwp_backup_state', array());
    if (!empty($state['active'])) {
        echo '<p>' . sprintf(
            esc_html__('Backup läuft gerade (Phase: %s)…', 'fxwp'),
            esc_html($state['phase'])
        ) . '</p>';
    }

    if (!$h['healthy']) {
        echo '<p>' . esc_html__('Empfehlung: einen externen Cron einrichten, der diese URL alle paar Minuten aufruft:', 'fxwp') . '</p>';
        echo '<code style="word-break:break-all">'
            . esc_html(rest_url('fxwp/v1/run-backup-cron') . '?key=' . get_option('fxwp_api_key'))
            . '</code>';
    }
}

function fxwp_register_backup_health_widget()
{
    if (!current_user_can('fxm_admin')) {
        return;
    }
    wp_add_dashboard_widget(
        'fxwp_backup_health_widget',
        'Backup-Status (Faktor&times;WP)',
        'fxwp_backup_health_widget_render'
    );
}
add_action('wp_dashboard_setup', 'fxwp_register_backup_health_widget');

function fxwp_backup_health_admin_notice()
{
    if (!current_user_can('fxm_admin')) {
        return;
    }
    if (fxwp_check_deactivated_features('fxwp_deact_backups')) {
        return; // backups intentionally off -> no warning
    }

    $h = fxwp_backup_cron_health();
    if ($h['runs_last_7d'] >= 1) {
        return; // healthy
    }

    $last = $h['last_run']
        ? human_time_diff($h['last_run'], time()) . ' ' . __('her', 'fxwp')
        : __('noch nie', 'fxwp');

    echo '<div class="notice notice-error"><p>';
    echo '<strong>' . esc_html__('Faktor×WP Backup-Warnung:', 'fxwp') . '</strong> ';
    printf(
        esc_html__('Der Backup-Cron lief in den letzten 7 Tagen nicht (letzter Lauf: %s). Backups werden möglicherweise nicht erstellt.', 'fxwp'),
        esc_html($last)
    );
    echo ' <a href="' . esc_url(admin_url('admin.php?page=fxwp-backups')) . '">'
        . esc_html__('Backups prüfen', 'fxwp') . '</a>';
    echo '</p></div>';
}
add_action('admin_notices', 'fxwp_backup_health_admin_notice');
