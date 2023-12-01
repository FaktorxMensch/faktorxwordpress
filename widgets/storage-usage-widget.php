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

// Cancel the upload if the file exceeds the storage limit
function fxwp_check_uploading_file_exceedes_storage_limit($file)
{

    $available_space = fxwp_get_available_storage_space();

    // Get the uploaded file size
    $file_size = $file['size'];

 //Check if the uploaded file exceeds the available space
    if ($file_size > $available_space) {
        $error_message = __('You have exceeded your storage limit of '.fxwp_format_file_size(FXWP_STORAGE_LIMIT).'. Please delete some files or contact the administrator.', 'fxwp');
        //stop upload and set error message, which is currently not displayed anywhere but who knows, maybe in the future?
        $file['error'] = $error_message;
    }
    return $file;
}

// Limit the upload size to the available storage space
function fxwp_limit_upload_size($size)
{
    $available_space = fxwp_get_available_storage_space();
    if ($size > $available_space) {
    return $available_space;
    } else {
        return $size;
    }
}

// Add error message to the media page if the storage limit is exceeded
function fxwp_admin_notice_upload_media_storage_limit() {
    // Check if we're on the media page
    $screen = get_current_screen();
    if ( isset( $screen->id ) && $screen->id === 'upload' ) {
        if (fxwp_get_available_storage_space() < 50 * 1024 * 1024) { //50MB
            echo '<div class="notice notice-error"><p>'.__('You have less than 50MB free storage. Upload is not possible anymore. Please free up some space first! ', 'fxwp').'</p></div>';
        }
        else if (fxwp_get_available_storage_space() < 500 * 1024 * 1024) { //500MB
            echo '<div class="notice notice-error"><p>'.__('You have less than 500MB free storage. Your upload may cancel if the file is too large. ', 'fxwp').'</p></div>';
        }
    }
}

function fxwp_disable_upload_button() {
    $screen = get_current_screen();
    if ( isset( $screen->id ) && $screen->id === 'upload' ) {
        if(fxwp_get_available_storage_space() < 50 * 1024 * 1024) { //50MB
            // Add custom CSS to disable the upload button
            echo '<style>
                    .page-title-action {
                        pointer-events: none;
                        opacity: 0.5;
                    }
                  </style>';
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

// add dashboard widget
function fxwp_register_storage_usage_widget()
{
    wp_add_dashboard_widget(
        'fxwp_storage_usage_widget', // Widget slug.
        'Speicherplatz', // Title.
        'fxwp_storage_usage_widget' // Display function.
    );
}

$proj = get_option('fxwp_project', array());
$external_hosting = $proj['website_meta']['hoster'];
//error_log("external_hosting: " . print_r($external_hosting, true));
if ( $external_hosting == "" || $external_hosting == null ) {
    //Enable all this only if the website is hosted by us
    add_action('admin_notices', 'fxwp_display_storage_limit_notice');
    add_action('wp_dashboard_setup', 'fxwp_register_storage_usage_widget');
    add_filter('wp_handle_upload_prefilter', 'fxwp_check_uploading_file_exceedes_storage_limit' );
    add_filter('upload_size_limit', 'fxwp_limit_upload_size', 20);
    add_action( 'admin_notices', 'fxwp_admin_notice_upload_media_storage_limit' );
    add_action( 'admin_head', 'fxwp_disable_upload_button' );

}