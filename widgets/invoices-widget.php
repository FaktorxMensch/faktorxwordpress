<?php

function fxwp_invoices_widget()
{
    $invoices = get_option('fxwp_invoices', []);

    if (empty($invoices)) {
        echo '<p>' . esc_html__('Keine Rechnungen gefunden.', 'fxwp') . '</p>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Rechnung Nr.', 'fxwp') . '</th>';
    echo '<th>' . esc_html__('Datum', 'fxwp') . '</th>';
    echo '<th>' . esc_html__('Info', 'fxwp') . '</th>';
    echo '</tr></thead>';

    echo '<tbody>';
    foreach ($invoices as $invoice) {
        echo '<tr>';
        echo '<td><a href="' . esc_url($invoice->url) . '" target="_blank">' . esc_html($invoice->number) . '</a></td>';
        echo '<td>' . date('d.m.Y', strtotime($invoice->created_at)) . '</td>';
        echo '<td>' . fxwp_invoice_status($invoice->status) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

function fxwp_invoice_status($status)
{
    switch ($status) {
        case 'paid':
            return 'Bezahlt';
        case 'open':
            return 'Offen';
        case 'canceled':
            return 'Storniert';
        default:
            return 'Unbekannt';
    }
}

// Add dashboard widget
function fxwp_register_invoices_widget()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget(
        'fxwp_invoices_widget', // Widget slug.
        'Rechnungen', // Title.
        'fxwp_invoices_widget' // Display function.
    );
}


add_action('wp_dashboard_setup', 'fxwp_register_invoices_widget');
