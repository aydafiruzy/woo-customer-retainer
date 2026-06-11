<div class="wrap woocr-page">
    <h2>به ازای هر محصول، یک ردیف ایجاد کنید:</h2>
    <p>
        درصورتِ نیاز، می‌توانید از متغیر "<code>%name%</code>" برای نام کاربری، از متغیر "<code>%product%</code>" برای نام محصول و "<code>%link%</code>" برای لینک به صفحه محصول استفاده کنید.
    </p>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="text-center" style="width: 95px;">
                    شناسه محصول
                </th>
                <th class="text-center">
                    جزئیات ارسال اس‌ام‌اس اول
                </th>
                <th class="text-center">
                    جزئیات ارسال اس‌ام‌اس دوم
                </th>
                <th class="text-center">
                    جزئیات ارسال اس‌ام‌اس سوم
                </th>

                <th class="text-center" style="width: 75px;">
                    فعال
                </th>

                <th class="text-center" style="width: 115px;">
                    حذف/آپدیت
                </th>
            </tr>
        </thead>
        <tbody id="tbody">
            <?php 
            global $wpdb;
            $table_name = $wpdb->prefix . 'wooc_retain_products';
            $rows = $wpdb->get_results("SELECT * FROM $table_name");
            if (!empty($rows)) {
                foreach ($rows as $row) { ?>
                <tr id="<?php echo esc_attr($row->product_id); ?>" class="rt_rowClass">
                    <td>
                        <label for="sms_product_<?php echo esc_attr($row->product_id); ?>"></label>
                        <input class="text-center" type="text" name="sms_product" id="sms_product_<?php echo esc_attr($row->product_id); ?>" value="<?php echo esc_attr($row->product_id); ?>" DISABLED>
                        <p style="text-align: center;"><?php
                            $current_product_title = $product_title = get_the_title($row->product_id);
										 
							if($row->product_id == 0 ){
								echo 'الگوی ارسال عمومی، برای سفارشات چندمحصولی';
							}elseif ($current_product_title) {
                                echo $current_product_title;
                            }else{
                                echo 'چنین آیتمی یافت نشد';
                            }
                            unset($current_product_title);
                        ?></p>
                    </td>
                    <td>
                        <label for="sms_time1_<?php echo esc_attr($row->product_id); ?>">تایم ارسال پیامک اول(دقیقه)</label>
                        <input type="text" class="text-center" id="sms_time1_<?php echo esc_attr($row->product_id); ?>" name="sms_time1" value="<?php echo esc_attr($row->setup_time_send1); ?>">
                        </br>
                        <label for="sms_text1_<?php echo esc_attr($row->product_id); ?>">متن پیامک اول</label>
                        <textarea style="height: 235px" id="sms_text1_<?php echo esc_attr($row->product_id); ?>" name="sms_text1"><?php echo esc_textarea($row->setup_text_send1); ?></textarea>
                    </td>
                    <td>
                        <label for="sms_time2_<?php echo esc_attr($row->product_id); ?>">تایم ارسال پیامک دوم(ساعت)</label>
                        <input type="text" class="text-center" id="sms_time2_<?php echo esc_attr($row->product_id); ?>" name="sms_time2" value="<?php echo esc_attr($row->setup_time_send2); ?>">
                        </br>
                        <label for="sms_text2_<?php echo esc_attr($row->product_id); ?>">متن پیامک دوم</label>
                        <textarea style="height: 235px" id="sms_text2_<?php echo esc_attr($row->product_id); ?>" name="sms_text2"><?php echo esc_textarea($row->setup_text_send2); ?></textarea>
                    </td>
                    <td>
                        <label for="sms_time3_<?php echo esc_attr($row->product_id); ?>">تایم ارسال پیامک سوم(ساعت)</label>
                        <input type="text" class="text-center" id="sms_time3_<?php echo esc_attr($row->product_id); ?>" name="sms_time3" value="<?php echo esc_attr($row->setup_time_send3); ?>">
                        </br>
                        <label for="sms_text3_<?php echo esc_attr($row->product_id); ?>">متن پیامک سوم</label>
                        <textarea style="height: 235px" id="sms_text3_<?php echo esc_attr($row->product_id); ?>" name="sms_text3"><?php echo esc_textarea($row->setup_text_send3); ?></textarea>
                    </td>
                    <td class="text-center" style="width: 75px;">
                        <input type="checkbox" id="active_<?php echo esc_attr($row->product_id); ?>" name="active" <?php checked($row->setup_active, 1); ?>>
                        <label for="active_<?php echo esc_attr($row->product_id); ?>">فعال</label>
                    </td>
                    <td class="text-center" style="width: 115px;">
                        <button data-product_id="<?php echo esc_attr($row->product_id); ?>" class="btn button-secondary remove_woocr_product" type="button" style="margin:4px">حذف ردیف</button>
                        </br>
                        <button data-product_id="<?php echo esc_attr($row->product_id); ?>" class="btn button-secondary update_woocr_product" type="button" style="margin:4px">آپدیت ردیف</button>
                        </br>
                        <span id="AJAX-response_<?php echo esc_attr($row->product_id); ?>"></span>
                    </td>

                </tr>
                <?php
                
                }
            }else {
                echo '<tr><td colspan="6">No records found.</td></tr>';
            }
                ?>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>


<form action="" method="POST">
    <table>
        <tr class="rt_rowClass">
            <td class="text-center" style="width: 95px;">
                <label for="sms_product"></label>
                <input type="text" class="text-center" name="sms_product" id="sms_product">
            </td>
            <td>
                <label for="sms_time1">تایم ارسال پیامک اول(دقیقه)</label>
                <input type="text" class="text-center" id="sms_time1" name="sms_time1">
                </br>
                <label for="sms_text1">متن پیامک اول</label>
                <textarea style="height: 235px" type="text" id="sms_text1" name="sms_text1">%name%&#13;&#10;%product%&#13;&#10;%link%</textarea>
            </td>
            <td>
                <label for="sms_time2">تایم ارسال پیامک دوم(ساعت)</label>
                <input type="text" class="text-center" id="sms_time2" name="sms_time2">
                </br>
                <label for="sms_text2">متن پیامک دوم</label>
                <textarea style="height: 235px" type="text" id="sms_text2" name="sms_text2"></textarea>
            </td>
            <td>
                <label for="sms_time3">تایم ارسال پیامک سوم(ساعت)</label>
                <input type="text" class="text-center" id="sms_time3" name="sms_time3">
                </br>
                <label for="sms_text3">متن پیامک سوم</label>
                <textarea style="height: 235px" type="text" id="sms_text3" name="sms_text3"></textarea>
            </td>
            <td class="text-center" style="width: 75px;">
                <input type="checkbox" id="active" name="active" />
                <label for="active">فعال</label>
            </td>
            <td class="text-center" class="text-center" style="width: 115px;">
            <button type="submit" name="wooCR-add_product" class="btn button-primary btn-md">
                درج محصول جدید
            </button>
            </td>
        </tr>
    </table>
</form>


</div>