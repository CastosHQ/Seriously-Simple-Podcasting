<?php
/**
 * @var string $k
 * @var array $v
 * @var string $data
 * @var string $class
 * */
?>
<p>
	<label class="ssp-episode-details-label" for="<?php echo esc_attr( $k ) ?>"><?php
		echo wp_kses_post( $v['name'] ) ?></label><br/>
	<?php wp_editor( $data, $k, array( 'editor_class' => esc_attr( $class ) ) ) ?><br/>
	<span class="description"><?php echo wp_kses_post( $v['description'] ) ?></span>
</p>
