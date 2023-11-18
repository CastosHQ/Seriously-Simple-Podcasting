<?php
/**
 * @var string $k
 * @var array $v
 * @var string $class
 * @var string $data
 * */
?>
<p><input name="<?php echo esc_attr( $k ) ?>" type="checkbox" class="<?php echo esc_attr( $class ) ?>" id="<?php
	echo esc_attr( $k ) ?>"<?php checked( 'on', $data ) ?>/>
	<label for="<?php echo esc_attr( $k ) ?>"><span><?php echo wp_kses_post( $v['description'] ) ?></span></label>
</p>
