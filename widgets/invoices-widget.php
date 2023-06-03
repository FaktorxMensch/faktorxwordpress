<?php

function fxwp_invoices_widget()
{
    $invoices = get_option('fxwp_invoices', []);

    $invoices_per_page = 5; // Number of invoices to display per page
    $total_invoices = count($invoices);
    $total_pages = ceil($total_invoices / $invoices_per_page);

    $current_page = isset($_GET['invoice_page']) ? intval($_GET['invoice_page']) : 1;
    $start_index = ($current_page - 1) * $invoices_per_page;
    $end_index = $start_index + $invoices_per_page - 1;
    $invoices_to_display = array_slice($invoices, $start_index, $invoices_per_page);

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
    foreach ($invoices_to_display as $invoice) {

        echo '<tr>';
        echo '<td><a href="' . esc_url($invoice->url) . '" target="_blank">' . esc_html($invoice->number) . '</a></td>';
        echo '<td>' . date('d.m.Y', strtotime($invoice->created_at)) . '</td>';
        echo '<td>' . fxwp_invoice_status($invoice->status) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

    echo '<div id="invoices-pagination"></div>'; // Placeholder for pagination

    echo '<style>
        #invoices-pagination {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        #invoices-pagination div {
        }
        #invoices-pagination a {
            color: #007cba;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #007cba;
            border-radius: 3px;
            margin: 0 2px;
        }
        #invoices-pagination a.current {
            background-color: #007cba;
            color: #fff;
        }
        #invoices-pagination a:hover {
            background-color: #007cba;
            color: #fff;
        }
        </style>';

    echo '<script>
        var invoicesTable = document.getElementById("invoices-table");
        var paginationContainer = document.getElementById("invoices-pagination");

        function generatePagination() {
            paginationContainer.innerHTML = ""; // Clear previous pagination

            var totalPages = ' . $total_pages . ';
            var currentPage = ' . $current_page . ';

            if (totalPages > 1) {
                var paginationHTML = "";

                if (currentPage > 1) {
                    paginationHTML += "<a href=\'?invoice_page=" + (currentPage - 1) + "\'>&laquo; Previous</a><div>";
                } else {
                    paginationHTML += "<div></div><div>";
                }

                for (var i = 1; i <= totalPages; i++) {
                    if (i === currentPage) {
                        paginationHTML += "<a class=\'current\'>" + i + "</a>";
                    } else {
                        paginationHTML += "<a href=\'?invoice_page=" + i + "\'>" + i + "</a>";
                    }
                }

                if (currentPage < totalPages) {
                    paginationHTML += "</div><a href=\'?invoice_page=" + (currentPage + 1) + "\'>Next &raquo;</a>";
                } else {
                    paginationHTML += "</div><div></div>";
                }

                paginationContainer.innerHTML = paginationHTML;
            }
        }

        generatePagination();
    </script>';
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
