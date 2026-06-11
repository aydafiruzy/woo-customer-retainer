<?php
if (!defined('ABSPATH')) {
//     date_default_timezone_set('Asia/Tehran');
    echo '<hr>';
    echo time();
    echo '<hr>go to<pre>https://site.com/wp-cron.php?doing_wp_cron</pre> to make me run manually';
    echo '<hr>';
	echo date('G');
    exit;
}
/**
 * Adds a custom cron schedule for every 10 minutes.
 *
 * @param array $schedules An array of non-default cron schedules.
 * @return array Filtered array of non-default cron schedules.
 */
function woocr_every_10minutes( $schedules ) {
	$schedules[ 'every-10-minutes' ] = array( 'interval' => 10 * MINUTE_IN_SECONDS, 'display' => __( 'Every 10 minutes', 'woocr' ) );
	return $schedules;
}
add_filter( 'cron_schedules', 'woocr_every_10minutes' );

// Schedule the event
function schedule_wooc_retain_cron() {
    if (!wp_next_scheduled('wooc_retain_cron')) {
        wp_schedule_event(time(), 'every-10-minutes', 'wooc_retain_cron');
        // error_log('Scheduled wooc_retain_cron event');
    }
}
add_action('init', 'schedule_wooc_retain_cron');

// Check if the event is scheduled
function check_scheduled_events() {
    if (wp_next_scheduled('wooc_retain_cron')) {
        // error_log('wooc_retain_cron is scheduled');
    } else {
        // error_log('wooc_retain_cron is not scheduled');
    }
}
add_action('init', 'check_scheduled_events');

add_action('wooc_retain_cron', 'wooc_retain_cron_job');
// Lets Define the cron job function
function wooc_retain_cron_job() {
	
    global $wpdb;
    date_default_timezone_set('Asia/Tehran');
     
    $savedWOOCRoptions = get_option('woocr_options');

    if (isset($savedWOOCRoptions['wooCR-disable-nights']) && $savedWOOCRoptions['wooCR-disable-nights']) {
        $current_hour = date('G');
        // error_log('Cron job Is Running due to no restriction at DAYTIME ' . $current_hour);
        if ($current_hour >= 0 && $current_hour < 9) {
            // error_log('Cron job aborted due to night hours restriction ' . $current_hour);
            return;
        }
    }

    $table_name = $wpdb->prefix . 'wooc_retain';
    $results = $wpdb->get_results("SELECT * FROM $table_name WHERE send_status NOT IN ('paid_qesti', 'sms_sent_error', 'sms_sent_blacklist', 'duplicated', 'order_completed', 'sms_sent3' ,'no-more-pattern')");


    if (!$results) {
        // error_log('No records found for processing');
        return;
    }

    // Translate status messages
    $status_translations = [
        'scheduled' => 'Scheduled',          // برنامه ریزی شده 
        'sms_sent1' => 'First SMS Sent',     // اولین ارسال
        'sms_sent2' => 'Second SMS Sent',    // دومین ارسال
        'sms_sent3' => 'Third SMS Sent',     // سومین ارسال
        'sms_sent_error' => 'Error',         // دچار مشکل
        'order_completed' => 'Completed',     // تکمیل است
        'paid_qesti' => 'Installment',        // اقساطی
        'duplicated' => 'Duplicate Purchase', // تکرارِ خرید
        'no-more-pattern' => 'No Pattern'     // بدون پترن
    ];

    foreach ($results as $row) {
        $current_time = current_time('mysql');
        $current_time = strtotime($current_time);
        $current_send_status = $row->send_status;
		$time_order = strtotime($row->time_create_order);
        $time_send = strtotime($row->time_send);
        $time_send2 = strtotime($row->time_send2);
        $time_send3 = strtotime($row->time_send3);
        
        $current_user_phone = strval($row->phone_number);
		if($row->product_id){
			$product_setup_id = $row->product_id;
		}else{
			$product_setup_id = 0;
		}

        // $current_time = time();
        
        if (($current_time > $time_send) && ($current_time > $time_send2) && ($current_time > $time_send3)) {
            if($time_send3 > $time_order){
            	$current_send_status = 'sms_sent2';
			}
        }

        switch ($current_send_status) {
            case 'scheduled':
                $sms_newstatus = 'sms_sent1';
                break;
            case 'sms_sent1':
                $sms_newstatus = 'sms_sent2';
                break;
            case 'sms_sent2':
                $sms_newstatus = 'sms_sent3';
                break;
            // default:
            //     // error_log('Unexpected send status: ' . $current_send_status);
            //     // continue 2; // Skip to the next iteration of the outer loop
        }
		

        $wooc_progress_allowed = 0;
        if ($current_time > $time_send && $current_send_status == 'scheduled') {
            $wooc_progress_allowed = 1;
        }
        if ($current_time > $time_send2 && $current_send_status == 'sms_sent1') {
            $wooc_progress_allowed = 1;
        }
        if ($current_time > $time_send3 && $current_send_status == 'sms_sent2') {
            $wooc_progress_allowed = 1;
        }
        
// 	error_log('Cron Is Running and time is: '.$current_time . ' send1 '. $time_send . ' send2 '.$time_send2 .' send3 ' . $time_send3 . ' sendStatus ' . $current_send_status . ' Progree allow? ' . $wooc_progress_allowed );
	
        $order_id = $row->order_id;
        $order = wc_get_order($order_id);
        $order_phone = $order->get_billing_phone;
        if ($order && ($order->has_status('qp-paying') || $order->has_status('qp-overdue')) ) {
            $wpdb->update(
                $table_name,
                array('send_status' => 'paid_qesti'),
                array('order_id' => $order_id)
            );
            // error_log('Order ID ' . $order_id . ' marked as paid_qesti');
            continue;
        } elseif ($order && ($order->has_status('completed'))) {
            $wpdb->update(
                $table_name,
                array('send_status' => 'order_completed'),
                array('order_id' => $order_id)
            );
            // error_log('Order ID ' . $order_id . ' marked as paid_qesti');
            continue;
        } elseif($order && woocr_has_newer_paid_order($current_user_phone, ['qp-paying', 'completed'], $order_id)){
            $wpdb->update(
                $table_name,
                array('send_status' => 'duplicated'),
                array('order_id' => $order_id)
            );
            // error_log('Order ID ' . $order_id . ' marked as duplicated');
            continue;
        } else {
            // if ($wooc_progress_allowed) {
            if ($wooc_progress_allowed) {
                if ($order) {
                    $billing_first_name = $order->get_billing_first_name();
                    $billing_last_name = $order->get_billing_last_name();
                    $checkout_name = $billing_first_name && $billing_last_name ? "$billing_first_name $billing_last_name" : $billing_first_name;

                    if ($product_setup_id != 0) {
                        $items = $order->get_items();
                        $first_item = reset($items);
                        if ($first_item) {
                            $woocr_product_setup = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}wooc_retain_products WHERE product_id = %d AND setup_active != 0",
                                $product_setup_id
                            ));
                        }
                    } else {
                        // Resetting the result set if there is a previous query
                        $wpdb->flush();
                        global $wpdb;

                        // Execute the second query
                        $woocr_product_setup = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}wooc_retain_products WHERE product_id = 0 AND setup_active != 0"
                        ));
                    }

                    if ($woocr_product_setup) {
                        switch ($current_send_status) {
                            case 'scheduled':
                                $sms_pattern = $woocr_product_setup->setup_text_send1;
                                break;
                            case 'sms_sent1':
                                $sms_pattern = $woocr_product_setup->setup_text_send2;
                                break;
                            case 'sms_sent2':
                                $sms_pattern = $woocr_product_setup->setup_text_send3;
                                break;
                            default:
                                // error_log('No SMS pattern found for status: ' . $current_send_status);
                                // continue 2;
                        }
                        if($product_setup_id != 0){
                            $woocr_product_name = $first_item->get_name();
                        }

                        if ($sms_pattern) {
            // error_log('SMSpatt: ' . $sms_pattern);
                            $api_metatext = str_replace('%name%', $checkout_name ?: '', $sms_pattern);
                            $api_metatext = str_replace('%product%', $woocr_product_name, $api_metatext);

                            if ($sms_pattern && $product_setup_id == 0) {
                                $api_metatext = str_replace('%link%', '', $api_metatext);
                            } else {
                                $woocr_product_link = get_permalink($product_setup_id);
                                $api_metatext = str_replace('%link%', $woocr_product_link, $api_metatext);
                            }

                            $api_receiver = $order->get_billing_phone();
                            $api_line = $savedWOOCRoptions['wooCR-MeliLine'];

                            if ($api_metatext && $api_receiver) {
                                ini_set("soap.wsdl_cache_enabled", 0);
                                $sms = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl", array("encoding" => "UTF-8"));
                                $data = array(
                                    "username" => "",
                                    "password" => "",
                                    "to" => array($api_receiver),
                                    "from" => $api_line,
                                    "text" => $api_metatext,
                                    "isflash" => false
                                );
                                $result = $sms->SendSimpleSMS($data)->SendSimpleSMSResult;
                            } else {
                                $sms_newstatus = 'sms_sent_error'; // Send Error to DB
                            }
                            if ( $result && (intval($result->string) > 1000) ){
                                
                                $wpdb->update(
                                    $table_name,
                                    array('send_status' => $sms_newstatus),
                                    array('order_id' => $order_id)
                                );
                                if ($wpdb->last_error) {
                                    error_log( "Database Error: " . $wpdb->last_error);
                                }
                            } elseif ( intval($result->string) == 35 ) {
                                $sms_newstatus = 'sms_sent_blacklist';
                                $wpdb->update(
                                    $table_name,
                                    array('send_status' => $sms_newstatus),
                                    array('order_id' => $order_id)
                                );
                                if ( $wpdb->last_error ) {
                                    error_log( "Database Error: " . $wpdb->last_error);
                                }
                            } else {
                                continue;
                            }
                        } else {
                            error_log('Missing SMS metadata or receiver for order ID ' . $order_id);
                            $wpdb->update(
                                $table_name,
                                array('send_status' => 'no-more-pattern'),
                                array('order_id' => $order_id)
                            );
                        }
                    } else {
                        error_log('No product setup found for product ID ' . $product_setup_id);
                    }
                }else{
                    // error_log('ERROR UPDATING Missing Order or Some Other Informations');
                    $wpdb->update(
                        $table_name,
                        array('send_status' => 'sms_sent_error'),
                        array('order_id' => $order_id)
                    );
                }
            }
        }
        
    }
}