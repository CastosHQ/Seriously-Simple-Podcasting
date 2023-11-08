<?php
/**
 * @var string $k
 * @var array $v
 * @var string $data
 * @var string $class
 * */
?>
<p>
	<label class="ssp-episode-details-label" for="<?php esc_attr( $k ) ?>">
		<?php wp_kses_post( $v['name'] ) ?></label>
	<br/>
	<input name="<?php esc_attr( $k ) ?>" type="number" min="0" id="<?php
      echo esc_attr( $k ) ?>" class="<?php echo esc_attr( $class ) ?>" value="<?php echo esc_attr( $data ) ?>" />
	<br/>
	<span class="description"><?php wp_kses_post( $v['description'] ) ?></span>
</p>
