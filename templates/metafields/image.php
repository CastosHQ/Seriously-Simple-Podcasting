<?php
/**
 * @var string $label
 * @var string $description
 * @var string $data
 * @var string $k
 * */
?>
<p>
	<span class="ssp-episode-details-label"><?php echo wp_kses_post( $label ) ?></span><br/>
	<img id="<?php echo esc_attr( $k ) ?>_preview" src="<?php echo esc_attr( $data ) ?>"
		 style="max-width:200px;height:auto;margin:20px 0;"/>
	<br/>
	<input id="<?php echo esc_attr( $k ) ?>_button" type="button" class="button"
		   value="<?php _e( 'Upload new image', 'seriously-simple-podcasting' ) ?>"/>
	<input id="<?php echo esc_attr( $k ) ?>_delete" type="button" class="button"
		   value="<?php _e( 'Remove image', 'seriously-simple-podcasting' ) ?>"/>
	<input id="<?php echo esc_attr( $k ) ?>" type="hidden" name="<?php echo esc_attr( $k ) ?>"
		   value="<?php echo esc_attr( $data ) ?>"/>
	<br/>
	<span class="description"><?php echo wp_kses_post( $description ) ?></span>
<p/>
