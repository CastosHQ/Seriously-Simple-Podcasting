<?php
/**
 * @var string $id
 * @var string $field_id
 * @var string $field_name
 * @var string $placeholder
 * @var string $label
 * @var string $value
 * @var array $items
 * */
?>

<p><label for="<?php echo $field_id; ?>"><?php _e( $label, 'seriously-simple-podcasting' ); ?></label>
	<select id="<?php echo $field_id ?>" name="<?php echo $field_name ?>">
		<?php if ( $placeholder ) : ?>
			<option value=""><?php _e( $placeholder, 'seriously-simple-podcasting' ); ?></option>
		<?php endif; ?>
		<?php foreach ( $items as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k )?>" <?php selected( $value, $k ) ?>><?php echo $v ?></option>
		<?php endforeach; ?>
	</select>
</p>
