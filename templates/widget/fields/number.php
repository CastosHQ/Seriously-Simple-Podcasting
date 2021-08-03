<?php
/**
 * @var string $id
 * @var string $field_id
 * @var string $field_name
 * @var string $placeholder
 * @var string $label
 * @var string $value
 * */
?>
<p><label for="<?php echo $field_id ?>"><?php _e( $label, 'seriously-simple-podcasting' ); ?></label>
	<input class="widefat" id="<?php echo $field_id ?>"
		   name="<?php echo $field_name ?>" type="number"
		   placeholder="<?php _e( $placeholder, 'seriously-simple-podcasting' ); ?>"
		   value="<?php echo $value; ?>"/>
</p>
