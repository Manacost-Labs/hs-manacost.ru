<?php
/**
 * Created by PhpStorm.
 * User: tagdiv
 * Date: 20.07.2016
 * Time: 16:21
 */

/*
 * At runtime:
 * - the 'action' of the 'tdc-live-panel' frame is set to the tdcAdminIFrameUI._$liveIframe url parameter
 * - the 'tdc_content' hidden param is set to the current content (the content generated from the backbone structure)
 */

// - 'tdc_action' hidden field can have one of the values: 'tdc_ajax_save_post' or 'preview'
// --- at 'preview' nothing is saved to the database
// --- at 'tdc_ajax_save_post' the content and the live panel settings are saved to the database
// - 'tdc_content' hidden field is the shortcode of the new content

?>




<form id="tdc-live-panel" method="post">

	<input type="hidden" id="td_magic_token" name="td_magic_token" value="<?php echo wp_create_nonce("td-update-panel") ?>"/>
	<input type="hidden" id="tdc_action" name="tdc_action" value="tdc_ajax_save_post">
	<input type="hidden" id="tdc_post_id" name="tdc_post_id" value="<?php echo $post->ID ?>">
    <input type="hidden" id="tdc_content" name="tdc_content" value="">
	<input type="hidden" id="tdc_customized" name="tdc_customized" value="">
    <?php
        $tdbTemplateType = tdc_util::get_get_val('tdbTemplateType');
        if( $tdbTemplateType == 'single' ) {
            echo '<input type="hidden" id="tdc_single_post_content_width" name="tdc_single_post_content_width" value="">';
        }
    ?>

	<?php
		// Extensions add their content in 'tdc-live-panel' (usually their own hidden input fields that will be saved by composer - see 'tdc_extension_save' action)
		do_action( 'tdc_extension_settings' );
	?>

</form>

