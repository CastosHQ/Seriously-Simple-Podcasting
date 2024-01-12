<?php
/**
 * @var bool $mode
 * @var string $series_img_title
 * @var string $taxonomy
 * @var string $default_image
 * @var string $src
 * @var string $image_width
 * @var string $image_height
 * @var string $series_settings
 * @var string $media_id
 * @var string $upload_btn_title
 * @var string $upload_btn_text
 * @var string $upload_btn_value
 * @var string $series_img_desc
 *
 * */
?>
<img alt="<?php echo esc_html( $series_img_title ) ?>" id="<?php echo esc_attr( $taxonomy ) ?>_image_preview"
	 data-src="<?php echo esc_attr( $default_image ) ?>"
	 src="<?php echo esc_attr( $src ) ?>" width="<?php echo esc_attr( $image_width ) ?>"
	 height="<?php echo esc_attr( $image_height ) ?>"/>
<div>
	<input type="hidden" id="<?php echo esc_attr( $taxonomy ) ?>_image_id"
		   name="<?php echo esc_attr( $series_settings ) ?>"
		   value="<?php echo esc_attr( $media_id ) ?>"/>
	<button
		id="<?php echo esc_attr( $taxonomy ) ?>_upload_image_button" class="button"
		data-uploader_title="<?php echo esc_attr( $upload_btn_title ) ?>"
		data-uploader_button_text="<?php echo esc_attr( $upload_btn_text ) ?>">
		<span class="dashicons dashicons-format-image"></span>
		<?php echo esc_html( $upload_btn_value ) ?>
	</button>
	<button id="<?php echo esc_html( $taxonomy ) ?>_remove_image_button" class="button">&times;</button>
</div>
<p class="description"><?php echo esc_html( $series_img_desc ) ?></p>
