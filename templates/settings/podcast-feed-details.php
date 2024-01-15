<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Series_Controller::show_feed_info()
 *
 * @var string $edit_feed_url
 * @var WP_Term $term
 * @var \SeriouslySimplePodcasting\Handlers\Settings_Handler $settings_handler
 * @var array $feed_fields
 * */
?>
<tr class="form-field term-upload-wrap">
	<th scope="row">
		<label><?php echo __( 'Podcast Feed Details', 'seriously-simple-podcasting' ) ?></label>
		<p><a class="view-feed-link" href="<?php echo esc_url( $edit_feed_url ) ?>">
				<span class="dashicons dashicons-edit"></span>
				<?php echo __( 'Edit Feed Settings', 'seriously-simple-podcasting' ) ?></a></p>
		<p><a class="view-feed-link" href="<?php echo esc_url( ssp_get_feed_url( $term->slug ) ); ?>" target="_blank">
				<span class="dashicons dashicons-rss"></span>
				<?php echo __( 'View feed', 'seriously-simple-podcasting' ) ?>
			</a></p>
	</th>
	<td>
		<table style="border: 1px solid #ccc; width: 100%; padding: 0 10px;">
			<?php foreach ( $settings_handler->get_feed_fields() as $field ) : ?>
				<?php
				$value = $settings_handler->get_feed_option( $field, $term->term_id );
				if ( ! $value || ! is_string( $value ) ) {
					continue;
				}
				if ( 'image' === $field['type'] ) {
					$value = sprintf( '<img src="%s" style="width: 100px;">', $value );
				}
				?>
				<tr>
					<th><?php echo $field['label']; ?>:</th>
					<td><?php echo $value; ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</td>
</tr>
