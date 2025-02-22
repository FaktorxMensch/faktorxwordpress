<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure we can use is_plugin_active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class FaktorX_WordFence_Mod
{

    // check for get_option if the mod is deactivaed
    private bool $is_active = true;

    private string $custom_email = FXWP_ERROR_EMAIL;

    public function __construct()
    {
        // remove option
        $this->is_active = boolval(get_option('fxwp_wordfence_email_mod_active', true));
        if (!$this->is_active) {
            return;
        }
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init(): void
    {
        if (!$this->is_wordfence_active()) {
            return;
        }

        // Change email when plugin loads
        $this->modify_wordfence_email();

        // Prevent changes to the email setting
        add_filter('pre_update_option_wf_alertEmails', array($this, 'prevent_email_change'), 10, 2);
    }

    private function is_wordfence_active(): bool
    {
        return is_plugin_active('wordfence/wordfence.php');
    }

    private function modify_wordfence_email(): void
    {
        if (class_exists('wfConfig')) {
            try {
                wfConfig::set('alertEmails', $this->custom_email);

                // Verify the change was successful
                $current_email = wfConfig::get('alertEmails');
                if ($current_email !== $this->custom_email) {
                    $this->send_failure_notification('Failed to update WordFence email settings. Current email: ' . $current_email);
                }
            } catch (Exception $e) {
                $this->send_failure_notification('Error updating WordFence email: ' . $e->getMessage());
            }
        } else {
            $this->send_failure_notification('WordFence configuration class not found.');
        }
    }

    public function prevent_email_change($new_value, $old_value): string
    {
        if ($new_value !== $this->custom_email) {
            $this->send_failure_notification('Attempted unauthorized change of WordFence email from ' . $old_value . ' to ' . $new_value);
        }
        return $this->custom_email;
    }

    private function send_failure_notification($message): void
    {
        $site_url = get_site_url();
        $subject = 'WordFence Email Modification Failed - ' . $site_url;

        $body = "Hello,\n\n";
        $body .= "There was an issue with WordFence email modification on {$site_url}:\n\n";
        $body .= $message . "\n\n";
        $body .= "Time: " . current_time('mysql') . "\n";
        $body .= "Site URL: " . $site_url . "\n";
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($this->custom_email, $subject, $body, $headers);
    }
}

new FaktorX_WordFence_Mod();