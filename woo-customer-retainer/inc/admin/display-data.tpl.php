<?php 
function wooc_get_checkout_name($order_id) {
    // Ensure WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return 'WooCommerce is not active';
    }

    // Get the order object
    $order = wc_get_order($order_id);

    if (!$order) {
        return 'Order not found';
    }

    // Get the billing first name and last name
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();

    // Combine to get the full name
    $full_name = $first_name . ' ' . $last_name;

    return $full_name;
}
?>
<style>
.tablenav-pages :is(.page-numbers, .next-page, .prev-page, .paged-navigation) {
    padding: 5px;
    display: block;
    margin: 5px;
    border: 1px solid #cccccc;
    border-radius: 50%;
    text-align: center;
    width: 19px;
}
.tablenav-pages .pagination-links {
    display: flex;
    flex-wrap: wrap;
}
</style>
<?php
global $wpdb;
$table_name = $wpdb->prefix . 'wooc_retain';

$total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$records_per_page = 200; // Adjust as needed
$total_pages = ceil($total_records / $records_per_page);

$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $records_per_page;


    // Retrieve data from the database
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY time_create_order DESC LIMIT %d OFFSET %d",
        $records_per_page,
        $offset
    ));

// Pagination
echo '<div class="tablenav">';
echo '<div class="tablenav-pages">';

if ($total_pages > 1) {
    echo '<span class="pagination-links">';

    // Previous Page Link
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        echo '<a class="prev-page" href="' . admin_url("admin.php?page=woocr_display_data&paged=$prev_page") . '">&laquo;</a>';
    } else {
        echo '<span class="paged-navigation">&laquo;</span>';
    }

    // Page Numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            echo "<span class='page-numbers current'>$i</span>";
        } else {
            echo "<a class='page-numbers' href='" . admin_url("admin.php?page=woocr_display_data&paged=$i") . "'>$i</a>";
        }
    }

    // Next Page Link
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        echo '<a class="next-page" href="' . admin_url("admin.php?page=woocr_display_data&paged=$next_page") . '">&raquo;</a>';
    } else {
        echo '<span class="paged-navigation">&raquo;</span>';
    }

    echo '</span>';
}

echo '</div>';
echo '</div>';



    // Display the data in a table
    echo '<div class="wrap">';
    echo '<h1 style="font-family: inherit;">Display Data</h1>';
    echo '<table class="wp-list-table widefat fixed striped table-view-list" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Order ID</th>';
	echo '<th>Product Pattern ID</th>';
    echo '<th>Recovery Status</th>';
    echo '<th>User ID</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Mobile Number</th>';
    echo '<th>Order Creation Time</th>';
    echo '<th>First SMS Time</th>';
    echo '<th>Second SMS</th>';
    echo '<th>Third SMS</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Status translations array
    $status_translations = [
        'scheduled' => 'Scheduled',
        'sms_sent1' => 'First Send',
        'sms_sent2' => 'Second Send',
        'sms_sent3' => 'Third Send',
        'sms_sent_error' => 'Error',
        'order_completed' => 'Completed',
        'paid_qesti' => 'Installment',
        'duplicated' => 'Repeat Purchase [Success]',
        'no-more-pattern' => 'No Pattern/Complete',
    ];

    if (!empty($results)) {
        foreach ($results as $row) {
            // Guest user handling
            $retain_receiver = ($row->user_id == 0) ? 'Guest User' : $row->user_id;
            $retain_status = $row->send_status;
            $retain_status_name = $status_translations[$retain_status] ?? $retain_status;
            $row_style = '';
            switch($retain_status){
                case 'scheduled':
                    $row_style = 'background: #ffd93d;'; 
                    break;
                case 'sms_sent1':
                    $row_style = 'background: #fecbcb;';
                    break;
                case 'sms_sent2':
                    $row_style = 'background: #fecbcb;';
                    break;
                case 'sms_sent3':
                    $row_style = 'background: #fecbcb;';
                    break;
				case 'sms_sent_error':
					$row_style = 'background: grey;color:white;';
					break;
                case 'order_completed':
                    $row_style = 'background: #c8ffda;';
                    break;
                case 'paid_qesti':
                    $row_style = 'background: #c8ffda;';
                    break;
                case 'duplicated':
                    $row_style = 'background: #d9c8ff;';
                    break;
				case 'no-more-pattern':
                    $row_style = 'background: #38da6385;';
                    break;
            }
            $woocr_order_edit = '<a href="'.admin_url('post.php?post=' . esc_attr($row->order_id) . '&action=edit').'">نمایش‌سفارش</a>' ;
            echo "<tr style='$row_style'>";
            echo '<td>' . esc_html($row->order_id) . '  ' . $woocr_order_edit . '</td>';
			echo '<td>' . $row->product_id . '</td>';
            echo '<td>' . $retain_status_name . '</td>';
            echo '<td>' . $retain_receiver . '</td>';
            echo '<td>' . wooc_get_checkout_name($row->order_id). '</td>';
            echo '<td>' . esc_html($row->phone_number). '</td>';
            echo '<td>' . esc_html($row->time_create_order) . '</td>';
            echo '<td>' . esc_html($row->time_send) . '</td>';
            echo '<td>' . esc_html($row->time_send2) . '</td>';
            echo '<td>' . esc_html($row->time_send3) . '</td>';
            echo '</tr>';
            unset($row_style);
        }
    } else {
        echo '<tr>';
        echo '<td colspan="7">No data found</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';