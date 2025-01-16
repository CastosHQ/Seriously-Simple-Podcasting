<?php
/**
 * @var string $label
 * @var string $description
 * @var string $data
 * @var string $k
 * @var string $validator
 * */
?>
<p>
	<span class="ssp-episode-details-label"><?php echo wp_kses_post( $label ) ?></span><br/>
	<img class="ssp-sync ssp-preview-<?php echo esc_attr( $k ) ?>" src="<?php echo esc_attr( $data ) ?>"
		 style="max-width:200px;height:auto;margin:20px 0;"/>
	<br/>
	<input id="<?php echo esc_attr( $k ) ?>_button" type="button" class="button"
		   value="<?php _e( 'Upload new image', 'seriously-simple-podcasting' )
		   ?>" data-field="ssp-field-<?php echo esc_attr( $k )
	       ?>" data-preview="ssp-preview-<?php echo esc_attr( $k )
	       ?>" data-validator="<?php echo esc_attr( $validator ) ?>"/>
	<input id="<?php echo esc_attr( $k ) ?>_delete" type="button" class="button ssp-image-delete"
		   value="<?php _e( 'Remove image', 'seriously-simple-podcasting' )
		   ?>" data-field="ssp-field-<?php echo esc_attr( $k )
	       ?>" data-preview="ssp-preview-<?php echo esc_attr( $k )
	       ?>"/>
	<input id="<?php echo esc_attr( $k ) ?>" type="hidden" name="<?php echo esc_attr( $k )
	       ?>" class="ssp-sync ssp-field-<?php echo esc_attr( $k )
	       ?>" value="<?php echo esc_attr( $data ) ?>"/>
	<br/>
	<span class="description"><?php echo wp_kses_post( $description ) ?></span>
<p/>
