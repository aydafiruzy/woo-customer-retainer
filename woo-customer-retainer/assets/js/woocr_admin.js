jQuery(document).ready(function($) {
    // Function to handle product removal
    function remove_woocr_product(product_id) {
        $.ajax({
            url: ajaxurl, // WordPress AJAX handler URL
            type: 'POST',
            data: {
                action: 'remove_woocr_product',
                product_id: product_id
            },
            success: function(response) {
                if (response.success) {
                    $('#AJAX-response_' + product_id).html('Product removed successfully.');
                    $('#' + product_id).remove(); // Remove the row from the table
                } else {
                    $('#AJAX-response_' + product_id).html('Failed to remove product.');
                }
            },
            error: function(xhr, status, error) {
                $('#AJAX-response_' + product_id).html('Error: ' + error);
            }
        });
    }

    // Function to handle product update
    function update_woocr_product(product_id) {
        var sms_time1 = $('#sms_time1_' + product_id).val();
        var sms_text1 = $('#sms_text1_' + product_id).val();
        var sms_time2 = $('#sms_time2_' + product_id).val();
        var sms_text2 = $('#sms_text2_' + product_id).val();
        var sms_time3 = $('#sms_time3_' + product_id).val();
        var sms_text3 = $('#sms_text3_' + product_id).val();
        var active = $('#active_' + product_id).is(':checked') ? 1 : 0;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_woocr_product',
                product_id: product_id,
                sms_time1: sms_time1,
                sms_text1: sms_text1,
                sms_time2: sms_time2,
                sms_text2: sms_text2,
                sms_time3: sms_time3,
                sms_text3: sms_text3,
                active: active
            },
            success: function(response) {
                if (response.success) {
                    $('#AJAX-response_' + product_id).html('اطلاعات آیتم با موفقیت بروز شد.');
                } else {
                    $('#AJAX-response_' + product_id).html('مشکلی در بروزرسانی این آیتم وجود داشت.');
                }
            },
            error: function(xhr, status, error) {
                $('#AJAX-response_' + product_id).html('Error: ' + error);
            }
        });
    }

    // Attach click events to buttons dynamically
    $(document).on('click', '.remove_woocr_product', function() {
        var product_id = $(this).data('product_id');
        remove_woocr_product(product_id);
    });

    $(document).on('click', '.update_woocr_product', function() {
        var product_id = $(this).data('product_id');
        update_woocr_product(product_id);
    });
});
