<?php
/**
 * Widget select field template.
 *
 * @package SeriouslySimplePodcasting
 *
 * @var string $id
 * @var string $field_id
 * @var string $field_name
 * @var string $placeholder
 * @var string $label
 * @var string $value
 * @var array $items
 */

?>
<p><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
    <select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>">		
		<?php if ( $placeholder ) : ?>
			<option value=""><?php _e( $placeholder, 'seriously-simple-podcasting' ); ?></option>
		<?php endif; ?>
		<?php foreach ( $items as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $value, $k ); ?>><?php echo esc_html( $v ); ?></option>
		<?php endforeach; ?>
	</select>
</p>
