<?php
/**
 * @var $k
 * @var array $v
 * @var string $data
 * */
?>

<p><label class="ssp-episode-details-label" for="<?php echo esc_attr( $k ) ?>"><?php
  echo wp_kses_post( $v['name'] ) ?></label></p>
<p><input name="<?php echo esc_attr( $k ) ?>" type="text" id="upload_<?php
  echo esc_attr( $k ) ?>" value="<?php echo esc_attr( $data ) ?>" />
<input type="button" class="button" id="upload_<?php echo esc_attr( $k ) ?>_button" value="<?php
	_e( 'Upload File', 'seriously-simple-podcasting' )
	?>" data-uploader_title="<?php _e( 'Choose a file', 'seriously-simple-podcasting' )
	?>" data-uploader_button_text="<?php _e( 'Insert podcast file', 'seriously-simple-podcasting' ) ?>" /><br/>
<span class="description"><?php echo wp_kses_post( $v['description'] ) ?></span></p>
