<?php
/**
 * Select field template.
 *
 * @package SeriouslySimplePodcasting
 *
 * @var string $k
 * @var array $v
 * @var string $class
 * @var string $data
 */

?>
<p>
	<span class="ssp-episode-details-label"><?php echo wp_kses_post( $v['name'] ); ?></span><br/>
	<select name="<?php echo esc_attr( $k ); ?>" class="ssp-sync ssp-field-
	<?php
	echo esc_attr( $k )
	?>
	<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $k ); ?>">
	<?php foreach ( $v['options'] as $option => $label ) : ?>
		<option <?php selected( $option, $data ); ?> value="<?php echo esc_attr( $option ); ?>">
		<?php
			echo esc_attr( $label )
		?>
		</option>
	<?php endforeach ?>
	</select>
	<span class="description"><?php echo wp_kses_post( $v['description'] ); ?></span>
</p>
