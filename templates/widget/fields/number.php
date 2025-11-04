<?php
/**
 * Widget number field template.
 *
 * @package Seriously Simple Podcasting
 *
 * @var string $id
 * @var string $field_id
 * @var string $field_name
 * @var string $placeholder
 * @var string $label
 * @var string $value
 */

?>
<p><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $field_id ); ?>"
			name="<?php echo esc_attr( $field_name ); ?>" type="number"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			value="<?php echo esc_attr( $value ); ?>"/>
</p>