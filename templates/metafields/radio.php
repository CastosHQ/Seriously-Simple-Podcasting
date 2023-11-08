<?php
/**
 * @var array $v
 * @var string $k
 * @var string $data
 * @var string $class
 * */
?>
<p>
	<span class="ssp-episode-details-label"><?php echo wp_kses_post( $v['name'] ) ?></span><br/>
	<?php foreach ( $v['options'] as $option => $label ) : ?>
		<input style="vertical-align: bottom;" name="<?php echo esc_attr( $k ) ?>" type="radio" class="<?php
		echo esc_attr( $class ) ?>" id="<?php echo esc_attr( $k ) . '_' . esc_attr( $option ) ?>" <?php
		echo checked( $option, $data, false ) ?> value="<?php echo esc_attr( $option ) ?>"/>
		<label style="margin-right:10px;" for="<?php echo esc_attr( $k ) . '_' . esc_attr( $option ) ?>">
			<?php echo esc_html( $label ) ?></label>
	<?php endforeach ?>
	<span class="description"><?php echo wp_kses_post( $v['description'] ) ?></span>
</p>
