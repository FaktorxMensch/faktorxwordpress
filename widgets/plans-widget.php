<?php
function fxwp_plans_widget()
{
    $plans = get_option('fxwp_plans', array());

    if (empty($plans) || $plans == new stdClass()){
        echo '<p>Keine Pläne gefunden.</p>';
    } else {
        echo '<table class="wp-list-table widefat fixed">';
        echo '<thead><tr>';
        echo '<th>Name</th>';
        echo '<th style="text-align: end">Abrechnung</th>';
//        echo '<th><div style="text-align: end;">Monatliche Kosten</div></th>';
        echo '</tr></thead>';

        echo '<tbody>';
        foreach ($plans as $plan) {
            echo '<tr>';
            echo '<td style="text-decoration: ' . ($plan->cancelled_at ? 'line-through' : 'none') . '">' . $plan->plan->name . '</td>';
            echo '<td style="text-align: end">' . ($plan->cancelled_at ? 'gekündigt' : ($plan->invoicing_interval == 'monthly' ? 'monatlich' : 'jährlich')) . '</td>';

//            echo '<td align="end">' . number_format($plan->plan->monthly_costs / 100, 2) . ' €</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
}

// Register the plans widget
function fxwp_register_plans_widget()
{
    $plans = get_option('fxwp_plans', array());
    if (!empty($plans)) {
        wp_add_dashboard_widget(
            'fxwp_plans_widget', // Widget slug
            'Pläne', // Title
            'fxwp_plans_widget' // Display function
        );
    }
}

add_action('wp_dashboard_setup', 'fxwp_register_plans_widget');
