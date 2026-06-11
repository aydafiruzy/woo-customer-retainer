<div class="wrap">
<?php
    defined( 'ABSPATH' ) || exit;


    $savedWOOCRoptions = get_option('woocr_options');
?>

    <h1><?php _e('SMS Recovery Plugin Settings', 'woo-cr'); ?></h1>
    <form action="" method="post" autocomplete="off">
        <label for="wooCR-MeliLine"><?php _e('SMS Sending Line', 'woo-cr'); ?></br>
            <input type="text" name="wooCR-MeliLine" id="wooCR-MeliLine" value="<?php echo ($savedWOOCRoptions['wooCR-MeliLine']) ? $savedWOOCRoptions['wooCR-MeliLine'] : '' ; ?>">
        </label>
        <br><br>
        <input type="checkbox" name="wooCR-active" id="wooCR-active" <?php echo ($savedWOOCRoptions['wooCR-active'] == 'on') ? 'checked' : 'nocheck' ; ?> >
        <label for="wooCR-active"><?php _e('Enable Plugin Functionality', 'woo-cr'); ?></label>

        <br>
        <input type="checkbox" name="wooCR-disable-nights" id="wooCR-disable-nights" <?php echo ($savedWOOCRoptions['wooCR-disable-nights'] == 'on') ? 'checked':'' ?> >
        <label for="wooCR-disable-nights"><?php _e('Disable Between 12 AM to 9 AM', 'woo-cr'); ?></label>
        <br><br>
        <button type="submit" class="button button-primary" name="WOOCR-SaveSettings">
            <?php _e('Save Settings', 'woo-cr'); ?>
        </button>
    </form>

    <pre id="dataFromDB" style="direction:ltr;padding: 15px">
        <?php print_r($savedWOOCRoptions);?>
    </pre>
</div>