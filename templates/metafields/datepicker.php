<?php
/**
 * @var array $v
 * @var string $k
 * @var string $data
 * @var string $class
 * */

$display_date = '';
if ( $data ) {
	$display_date = date( 'j F, Y', strtotime( $data ) );
}
?>
<p class="hasDatepicker">
	<label class="ssp-episode-details-label" for="<?php echo esc_attr( $k ) ?>_display"><?php
		echo wp_kses_post( $v['name'] ) ?></label>
	<br/>
	<input type="text" class="ssp-sync ssp-datepicker <?php echo esc_attr( $class )
	?>" name="<?php echo esc_attr( sprintf('%s_display', $k ) )
	?>" id="<?php echo esc_attr( sprintf('%s_display', $k ) )
	?>" value="<?php echo esc_attr( $display_date ) ?>" />
	<input name="<?php echo esc_attr( $k )
	?>" id="<?php echo esc_attr( $k )
	?>" class="ssp-sync" type="hidden" value="<?php echo esc_attr( $data ) ?>" />
	<br/>
	<span class="description"><?php echo wp_kses_post( $v['description'] ) ?></span>
</p>
