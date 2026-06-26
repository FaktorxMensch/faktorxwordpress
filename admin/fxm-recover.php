<?php
declare(strict_types=1);

function fxm_find_wp_load(string $start_dir): ?string
{
    $dir = realpath($start_dir);

    while ($dir && $dir !== dirname($dir)) {
        $candidate = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';

        if (is_file($candidate)) {
            return $candidate;
        }

        $dir = dirname($dir);
    }

    return null;
}

$wp_load = fxm_find_wp_load(__DIR__);

if (!$wp_load) {
    http_response_code(500);
    exit('wp-load.php not found. Put this file inside the WordPress installation.');
}

require_once $wp_load;


$email = 'accounts@faktorxmensch.com';
$username = 'fxmadmin-recovery';
$display_name = 'Faktor Mensch Admin';

$admin_role = get_role('administrator');

if (!$admin_role) {
    exit('Administrator role not found.');
}

$fxm_role = get_role('fxm_admin');

if (!$fxm_role) {
    add_role('fxm_admin', 'FxM Admin', $admin_role->capabilities);
    $fxm_role = get_role('fxm_admin');
}

if ($fxm_role) {
    foreach ($admin_role->capabilities as $cap => $grant) {
        if ($grant) {
            $fxm_role->add_cap($cap, true);
        }
    }

    $fxm_role->add_cap('fxm_admin', true);
}

$user = get_user_by('email', $email);
$password = wp_generate_password(24, true, true);
$created = false;

if (!$user) {
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        exit('User creation failed: ' . $user_id->get_error_message());
    }

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name,
        'nickname' => $display_name,
    ]);

    $user = get_user_by('ID', $user_id);
    $created = true;
} else {
    $user_id = $user->ID;
    wp_set_password($password, $user_id);
}

$wp_user = new WP_User($user_id);
$wp_user->add_role('administrator');
$wp_user->add_role('fxm_admin');

$login_url = wp_login_url();

$subject = 'WordPress Admin-Zugang wiederhergestellt';
$message = "Der WordPress Admin-Zugang wurde wiederhergestellt.\n\n";
$message .= "Login-URL: {$login_url}\n";
$message .= "Benutzername: {$username}\n";
$message .= "E-Mail: {$email}\n";
$message .= "Passwort: {$password}\n\n";
$message .= "Rollen: administrator, fxm_admin\n\n";

$mail_sent = wp_mail($email, $subject, $message);

header('Content-Type: text/plain; charset=utf-8');

echo "Done.\n\n";
echo "Created new user: " . ($created ? 'yes' : 'no, existing user password was reset') . "\n";
echo "Email sent: " . ($mail_sent ? 'yes' : 'no') . "\n\n";
