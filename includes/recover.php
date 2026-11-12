<?php
/**
 * Emergency recovery of the agency's admin access to a site.
 *
 * Creates (or resets the password of) the recovery account, gives it the
 * administrator + fxm_admin roles and mails the new password to the fixed
 * agency address. The password is never returned to the caller.
 *
 * Callers must authenticate the request themselves. There are three ways in:
 *   - monitor.php with a valid API key and "recover_admin": true (from ProjectPilot)
 *   - admin/fxm-recover.php from a shell on the server (CLI)
 *   - admin/fxm-recover.php in the browser, after uploading an empty file named
 *     fxwp-recover.flag into the plugin folder (proof of FTP access, one use)
 */

if (!function_exists('fxwp_recover_admin_access')) {
    /**
     * @return array{ok:bool,created:bool,mail_sent:bool,username:string,error:string}
     */
    function fxwp_recover_admin_access(): array
    {
        $email = 'accounts@faktorxmensch.com';
        $username = 'fxmadmin-recovery';
        $display_name = 'Faktor Mensch Admin';

        $result = array('ok' => false, 'created' => false, 'mail_sent' => false, 'username' => $username, 'error' => '');

        $admin_role = get_role('administrator');
        if (!$admin_role) {
            $result['error'] = 'Administrator role not found.';
            return $result;
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

        if (!$user) {
            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                $result['error'] = 'User creation failed: ' . $user_id->get_error_message();
                return $result;
            }
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name,
                'nickname' => $display_name,
            ));
            $result['created'] = true;
        } else {
            $user_id = $user->ID;
            $username = $user->user_login;
            $result['username'] = $username;
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

        $result['mail_sent'] = (bool)wp_mail($email, $subject, $message);
        $result['ok'] = true;

        return $result;
    }
}
