<?php
/**
 * Widget text field template.
 *
 * @package SeriouslySimplePodcasting
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
			name="<?php echo esc_attr( $field_name ); ?>" type="text"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			value="<?php echo esc_attr( $value ); ?>"/>
</p>