<?php
/**
 * @var string $k
 * @var array $v
 * @var string $data
 * @var bool $is_castos
 * @var \SeriouslySimplePodcasting\Entities\Castos_File_Data $file_data
 * */
?>

<div>
 <div>
	 <br><label class="ssp-episode-details-label" for="<?php echo esc_attr( $k ) ?>"><?php
		 echo wp_kses_post( $v['name'] ) ?></label><br><br>
 </div>


<?php if ( $is_castos ) : ?>
	<div id="ssp_upload_notification"><?php
	_e( 'An error has occurred with the file upload functionality. Please check your site for any plugin or theme conflicts.',
	'seriously-simple-podcasting' ) ?></div>
<?php endif ?>

<div>
	<input name="<?php echo esc_attr( $k ) ?>" type="text" id="upload_<?php
	  echo esc_attr( $k ) ?>" value="<?php echo esc_attr( $data ) ?>" />

	<?php if ( $is_castos ) : ?>
	  <div id="ssp_upload_container" style="display: inline;">
		  <button class="button" id="ssp_select_file" href="javascript:"><?php
			  _e( 'Select file', 'seriously-simple-podcasting' ) ?></button>
	  </div>
	<?php else : ?>
		<input type="button" class="button" id="upload_<?php echo esc_attr( $k ) ?>_button" value="<?php
		_e( 'Upload File', 'seriously-simple-podcasting' ) ?>" data-uploader_title="<?php
		_e( 'Choose a file', 'seriously-simple-podcasting' ) ?>" data-uploader_button_text="<?php
		_e( 'Insert podcast file', 'seriously-simple-podcasting' ) ?>" />
	<?php endif ?>
</div>

<div class="episode-file-description">
	<span class="description"><?php echo wp_kses_post( $v['description'] ) ?></span>

	<div class="episode-file-description__filename" <?php echo ( ! $file_data->path || $file_data->path !== $data ) ? 'style="display:none"' : '' ?>>
		<span class="ssp-episode-details-label"><?php _e( 'Original Filename:', 'seriously-simple-podcasting' ) ?></span>
		<span id="castos_file_name"><?php echo esc_html( $file_data->name ) ?></span>
	</div>
</div>

</div>
