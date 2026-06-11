<?php
defined( 'ABSPATH' ) || exit;

function woocr_register_admin_menu()
{
    add_menu_page(
        'SMS Retention Plugin Settings',
        'SMS Retention',
        'manage_options', //! USER_Capability to manage this menu
        'woocr_options',
        'woocr_menu_handler'
    );
    add_submenu_page(
        'woocr_options', // Parent slug
        'Product SMS Support Settings',
        'Define Products',
        'manage_options', // Capability
        'woocr_products_table', // Menu slug
        'woocr_productsTable_handler' // Function to display the content
    );
    add_submenu_page(
        'woocr_options', // Parent slug
        'Orders and Sending Status',
        'Display Data',
        'manage_options', // Capability
        'woocr_display_data', // Menu slug
        'woocr_display_data_handler' // Function to display the content
    );
}


function woocr_plugin_enqueue_scripts() {
    // Enqueue CSS file
    wp_enqueue_style('rt-woocr-admin-css', WOOCR_ASSETS . 'css/woocr_admin.css');

    // Enqueue JS file
    wp_enqueue_script('rt-woocr-admin-js', WOOCR_ASSETS . 'js/woocr_admin.js', array('jquery'), '', true);
}

add_action('admin_enqueue_scripts', 'woocr_plugin_enqueue_scripts');





function woocr_menu_handler()
{
    if(isset($_POST['WOOCR-SaveSettings'])){
        $woocr_meta_data = $_POST;
        // print_r($woocr_meta_data);
            // Check if the textarea value is set
            if(isset($_POST['wooCR-ProdList'])) {
                // Explode the textarea value by new lines to create an array
                $prodList = explode("\n", $_POST['wooCR-ProdList']);
                
                // Trim each element in the array to remove any leading/trailing whitespace
                $prodList = array_map('trim', $prodList);
                
                // Cast each product ID to integer
                $prodList = array_map('intval', $prodList);
        
                // Save the array to your options
                $woocr_meta_data['wooCR-ProdList'] = $prodList;
        
                // update_option('woocr_options', $savedWOOCRoptions);
            }
        update_option('woocr_options', $woocr_meta_data );
        
    }
    include WOOCR_INC.'admin/settings.tpl.php';
}
function woocr_display_data_handler()
{
    include WOOCR_INC.'admin/display-data.tpl.php';
}


function woocr_productsTable_handler()
{
    

    global $wpdb;
    if (isset($_POST['wooCR-add_product'])) {
        // Collect form data
        $product_id = $_POST['sms_product'];
        $time_send1 = $_POST['sms_time1'];
        $text_send1 = $_POST['sms_text1'];
        $time_send2 = $_POST['sms_time2'];
        $text_send2 = $_POST['sms_text2'];
        $time_send3 = $_POST['sms_time3'];
        $text_send3 = $_POST['sms_text3'];
        $active = isset($_POST['active']) ? 1 : 0;

        // Prepare data for insertion
        $data = array(
            'product_id' => $product_id,
            'setup_time_send1' => $time_send1,
            'setup_text_send1' => $text_send1,
            'setup_time_send2' => $time_send2,
            'setup_text_send2' => $text_send2,
            'setup_time_send3' => $time_send3,
            'setup_text_send3' => $text_send3,
            'setup_active' => $active,
        );

        // Table name
        $table_name = $wpdb->prefix . 'wooc_retain_products';

        // Insert data into the database
        $wpdb->insert($table_name, $data);

        // Optionally, you can redirect the user after the data is inserted
        // wp_safe_redirect( home_url() );
        // exit();
    }
    include WOOCR_INC.'admin/tabled_options.tpl.php';
    // print_r($data);
}




add_action( 'admin_menu', 'woocr_register_admin_menu' );