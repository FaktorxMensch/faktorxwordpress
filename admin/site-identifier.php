<?php
function fxwp_site_identifier_page()
{
    ?>
    <div class="wrap">
        <h1><?php _e('Welcome to WPWithHeart', 'wpwh'); ?></h1>
        <div id="wpwh-banner"
             style="background: url(<?php echo plugins_url('images/gradient-7258997_1280.png', __FILE__); ?>) no-repeat center center;background-size: cover; height: 300px; margin: 20px 0;">
        </div>
        <h2><?php _e('Keeping your site safe, secure, and optimized', 'wpwh'); ?></h2>
        <p><?php _e('Our plugin provides a range of features designed to help you get the most out of your WordPress site.', 'wpwh'); ?></p>
        <div class="wpwh-feature">
            <h3><?php _e('Feature 1', 'wpwh'); ?></h3>
            <p><?php _e('Description of feature 1.', 'wpwh'); ?></p>
        </div>
        <div class="wpwh-feature">
            <h3><?php _e('Feature 2', 'wpwh'); ?></h3>
            <p><?php _e('Description of feature 2.', 'wpwh'); ?></p>
        </div>
        <!-- Add more features as needed -->
        <div class="wpwh-contact">
            <h3><?php _e('Need help?', 'wpwh'); ?></h3>
            <p><?php _e('Contact us at support@example.com for assistance.', 'wpwh'); ?></p>
        </div>

    </div>
    <?php
}
