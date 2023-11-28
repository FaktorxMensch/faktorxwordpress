<?php
function fxwp_storage_usage_widget()
{
    $available_space = fxwp_get_available_storage_space();
    $used_space = FXWP_STORAGE_LIMIT - $available_space;
    $used_space = $used_space;
    $percentage = round($used_space / $available_space * 100);

    echo '<p>' . sprintf(esc_html__('Ihr verfügbarer Speicherplatz beträgt %1$s. Sie haben %2$s belegt, davon sind %3$s für E-Mails reserviert.', 'fxwp'), fxwp_format_file_size($available_space), fxwp_format_file_size($used_space), fxwp_format_file_size(4 * 1024 * 1024 * 1024)) . '</p>';
    echo '<div class="fxwp-storage-usage">';
    echo '<div class="fxwp-storage-usage-bar" style="width: ' . esc_attr($percentage) . '%"></div>';
    echo '<div class="fxwp-storage-usage-text">' . esc_html($percentage) . '%</div>';
    echo '</div>';
}

// Limit storage for users to 20GB
function fxwp_check_storage_limit($file)
{
    // TODO: fix this, it is broken
    // Check if the file is being uploaded
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES[$file])) {

        $available_space = fxwp_get_available_storage_space();

        // Get the uploaded file size
        $file_size = $_FILES[$file]['size'];

        // Check if the uploaded file exceeds the available space
        if ($file_size > $available_space) {
            // File upload failed, display error message
            $error_message = __('You have exceeded your storage limit of 20GB. Please delete some files or contact the administrator.', 'fxwp');
            wp_die($error_message);
        }
    }
}

function fxwp_get_available_storage_space()
{
    $storage_limit = FXWP_STORAGE_LIMIT;
    $mails_space = 4 * 1024 * 1024 * 1024; // 4GB reserved for emails
    $used_space = fxwp_get_directory_size(WP_CONTENT_DIR) + $mails_space;
    $available_space = $storage_limit - $used_space;

    return $available_space;
}

// Get the size of a directory recursively
function fxwp_get_directory_size($dir)
{
    $size = 0;

    $dir_iterator = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }

    return $size;
}

//add_filter('wp_handle_upload_prefilter', 'fxwp_check_storage_limit');

function fxwp_display_storage_limit_notice()
{
    $available_space = fxwp_get_available_storage_space();

    if ($available_space > 1024 * 1024 * 1024) { //1GB
        // No need to display the notice
        return;
    }

    echo '<div class="notice notice-warning">';
    echo '<p>';
    printf(
        esc_html__('Ihr verfügbarer Speicherplatz beträgt %1$s von %2$s.', 'fxwp'),
        fxwp_format_file_size($available_space),
        fxwp_format_file_size(FXWP_STORAGE_LIMIT)
    );
    echo '</p>';
    echo '</div>';
}

function fxwp_format_file_size($bytes)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $index = 0;
    while (($bytes >= 1024 || $bytes <= -1024 )&& $index < count($units) - 1) {
        $bytes /= 1024;
        $index++;
    }
    return round($bytes, 2) . ' ' . $units[$index];
}

add_action('admin_notices', 'fxwp_display_storage_limit_notice');


// add dashboard widget
function fxwp_register_storage_usage_widget()
{
    wp_add_dashboard_widget(
        'fxwp_storage_usage_widget', // Widget slug.
        'Speicherplatz', // Title.
        'fxwp_storage_usage_widget' // Display function.
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_storage_usage_widget');
