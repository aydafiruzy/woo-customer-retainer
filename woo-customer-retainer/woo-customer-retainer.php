<?php
/**
 * Plugin Name: SMS Retention System
 * Plugin URI: https://aydafirouzi.com/
 * Description: SMS cart retention and customer recycler for WooCommerce.
 * Version: 1.0.1
 * Author: Ayda Firouzi
 * Author URI: https://aydafirouzi.com/
 * Text Domain: woo-cr
 * Domain Path: /i18n/languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WOOCR_DIR', plugin_dir_path(__FILE__));
define('WOOCR_URL', plugin_dir_url(__FILE__));
define('WOOCR_ASSETS', WOOCR_URL . 'assets/');
define('WOOCR_INC', WOOCR_DIR . 'inc/');

/**
 * Creates required database tables for the plugin
 */
function create_retainer_tables() {
    global $wpdb;
    
    // Create main retention table
    $table_name = $wpdb->prefix . 'wooc_retain';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        order_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        send_status varchar(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        phone_number varchar(15) NOT NULL,
        time_create_order datetime NOT NULL,
        time_send datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        time_send2 datetime DEFAULT '0000-00-00 00:00:00' NULL,
        time_send3 datetime DEFAULT '0000-00-00 00:00:00' NULL,
        PRIMARY KEY  (order_id)
    ) $charset_collate;";
    
    // Create products configuration table
    $table2_name = $wpdb->prefix . 'wooc_retain_products';
    $sql2 = "CREATE TABLE $table2_name (
        product_id bigint(20) NOT NULL,
        setup_time_send1 smallint NULL,
        setup_text_send1 TEXT NULL,
        setup_time_send2 smallint NULL,
        setup_text_send2 TEXT NULL,
        setup_time_send3 smallint NULL,
        setup_text_send3 TEXT NULL,
        setup_active boolean NULL,
        PRIMARY KEY  (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
}

/**
 * Drops plugin tables on deactivation
 */
function drop_retainer_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'create_retainer_tables');
register_deactivation_hook(__FILE__, 'drop_retainer_table');

// Load admin files if in admin area
if (is_admin()) {
    require_once WOOCR_INC . 'admin/menus.php';
    require_once WOOCR_INC . 'admin/woocr_ajax_rowshandler.php';
}

// Load cron functionality
require_once WOOCR_INC . 'woocr-cronjob.php';

/**
 * Checks if user has any completed orders
 * 
 * @param int $user_id User ID to check
 * @return bool True if user has completed orders
 */
function woocr_has_completed_orders($user_id) {
    return !empty(wc_get_orders([
        'customer' => $user_id,
        'status' => ['completed', 'qp-paying', 'qp-overdue']
    ]));
}

/**
 * Handles new order creation for retention system
 * 
 * @param WC_Order $order Order object
 */
function woocr_on_order_creation($order) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wooc_retain';

    // Get order details
    $order_id = $order->get_id();
    $user_id = $order->get_user_id();
    $phone_number = get_post_meta($order_id, '_billing_phone', true);

    // Handle missing phone number
    if (!$phone_number) {
        $user = get_user_by('id', $user_id);
        $phone_number = $user->user_login;
        if (!$phone_number || preg_match('/[A-Za-z]/', $phone_number)) {
            return;
        }
    }

    // Continue with order processing...
}

function woocr_on_order_status_completed($order_id) {
	$rt_woocr_order_total = get_post_meta($order_id, '_order_total', true);
	if($rt_woocr_order_total > 0 ){
		global $wpdb;
		$table_name = $wpdb->prefix . 'wooc_retain';

		// Get phone number for the completed order
		$phone_number = get_post_meta($order_id, '_billing_phone', true);

		// Ensure the phone number is not empty
		if (!empty($phone_number)) {
			// Prepare the query to find rows with the matching phone number
			$woocr_query = $wpdb->prepare("SELECT * FROM $table_name WHERE phone_number = %s", $phone_number);
			$woocr_results = $wpdb->get_results($woocr_query);

			// Log the results for debugging purposes
// 			error_log('Results: ' . print_r($woocr_results, true));

			// Check if there are any matching rows
			if (!empty($woocr_results)) {
				foreach ($woocr_results as $row) {
					// Update the send_status for each matching row
					$result = $wpdb->update(
						$table_name,
						array('send_status' => 'order_completed'),
						array('order_id' => $row->order_id),  // Update using the primary key from wooc_retain table
						array('%s'),
						array('%d')
					);

					// Log the query and result for debugging purposes
				// 	error_log('Updated row order_id: ' . $row->order_id);
				// 	error_log('Query: ' . $wpdb->last_query);
				// 	error_log('Result: ' . $result);

					// Check for errors and log them
					if ($result === false) {
						error_log('Error updating row order_id ' . $row->order_id . ': ' . $wpdb->last_error);
					}
				}
			} else {
				error_log('No matching rows found for phone number: ' . $phone_number);
			}
		} else {
			error_log('No phone number found for order ID: ' . $order_id);
		}
	}
}
add_action('woocommerce_order_status_completed', 'woocr_on_order_status_completed', 10, 1);

function woocr_has_newer_paid_order($phone_number, $order_statuses = ['qp-paying', 'completed'], $current_order_id = null) {
    // Ensure phone number is valid
    if (empty($phone_number)) {
        return false;
    }

    // Prepare order query arguments
    $args = [
        'billing_phone' => $phone_number,
        'status' => $order_statuses,
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'exclude' => $current_order_id ? [$current_order_id] : [],
    ];

    // Use WooCommerce order query
    $orders = wc_get_orders($args);

    // Return true only if a newer order is found AND it meets status criteria
    if (!empty($orders)) {
        $newer_order = reset($orders);
        return $newer_order && in_array($newer_order->get_status(), array_map('wc_clean', $order_statuses));
    }

    return false;
}

function woocr_load_textdomain() {
    load_plugin_textdomain('woo-cr', false, dirname(plugin_basename(__FILE__)) . '/i18n/languages/');
}
add_action('plugins_loaded', 'woocr_load_textdomain');