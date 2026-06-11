<?php
// Remove product AJAX handler
add_action('wp_ajax_remove_woocr_product', 'remove_woocr_product');
// add_action('wp_ajax_nopriv_remove_woocr_product', 'remove_woocr_product');

function remove_woocr_product() {
    global $wpdb;
    $product_id = intval($_POST['product_id']);
    $table_name = $wpdb->prefix . 'wooc_retain_products';

    if ($wpdb->delete($table_name, array('product_id' => $product_id))) {
        wp_send_json_success('Product removed successfully.');
    } else {
        wp_send_json_error('Failed to remove product.');
    }
}

// Update product AJAX handler
add_action('wp_ajax_update_woocr_product', 'update_woocr_product');
add_action('wp_ajax_nopriv_update_woocr_product', 'update_woocr_product');

function update_woocr_product() {
    global $wpdb;
    $product_id = intval($_POST['product_id']);
    $sms_time1 = sanitize_text_field($_POST['sms_time1']);
    $sms_text1 = sanitize_textarea_field($_POST['sms_text1']);
    $sms_time2 = sanitize_text_field($_POST['sms_time2']);
    $sms_text2 = sanitize_textarea_field($_POST['sms_text2']);
    $sms_time3 = sanitize_text_field($_POST['sms_time3']);
    $sms_text3 = sanitize_textarea_field($_POST['sms_text3']);
    $active = intval($_POST['active']);

    $table_name = $wpdb->prefix . 'wooc_retain_products';
    $result = $wpdb->update(
        $table_name,
        array(
            'setup_time_send1' => $sms_time1,
            'setup_text_send1' => $sms_text1,
            'setup_time_send2' => $sms_time2,
            'setup_text_send2' => $sms_text2,
            'setup_time_send3' => $sms_time3,
            'setup_text_send3' => $sms_text3,
            'setup_active' => $active
        ),
        array('product_id' => $product_id)
    );

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Product updated successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update product.'));
    }
}

// Update response messages
$messages = [
    'success' => [
        'product_removed' => 'Product removed successfully',
        'product_updated' => 'Product information updated successfully',
        'row_deleted' => 'Row deleted successfully'
    ],
    'error' => [
        'product_remove' => 'Failed to remove product',
        'product_update' => 'Failed to update product',
        'row_delete' => 'Failed to delete row'
    ]
];
