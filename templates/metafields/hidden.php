<?php
/**
 * Hidden field template.
 *
 * @package SeriouslySimplePodcasting
 *
 * @var string $k
 * @var string $data
 * @var string $class
 */

?>
<p>
	<input name="<?php echo esc_attr( $k ); ?>" type="hidden" id="
	<?php
	echo esc_attr( $k )
	?>
	" class="ssp-sync ssp-field-
	<?php
	echo esc_attr( $k )
	?>
	<?php echo esc_attr( $class ); ?>" value="<?php echo esc_attr( $data ); ?>" />
</p>
