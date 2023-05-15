<?php

// have a example widget
function fxwp_seo_check_widget()
{
    // have todo for ema
    echo '<img src="https://images.klipfolio.com/website/public/fd5b14b3-8ff8-4685-abc9-e2e571403ca3/SEO%20Traffic.png" alt="SEO Check" style="width:100%;height:auto;">';
    echo '<p>Der ausführliche SEO Check ist nur für Administratoren verfügbar. Er befindet sich im Menü unter "SEO Check".</p>';
    // link to subsite
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=fxwp-seo-check')) . '" class="button button-secondary">' . esc_html__('Go to SEO Check', 'fxwp') . '</a></p>';
}

// add action
add_action('admin_post_fxwp_disable_dashboard_widgets', 'fxwp_disable_dashboard_widgets');

// register it in the admin dashboard
function fxwp_register_seo_check_widget()
{
    wp_add_dashboard_widget(
        'fxwp_seo_check_widget',
        'SEO Check',
        'fxwp_seo_check_widget'
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_seo_check_widget');