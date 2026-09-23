<?php
/**
 * Body of the Castos sync confirmation modal.
 *
 * @var string[] $difference_labels Translated labels of the fields that differ.
 *
 * @package SeriouslySimplePodcasting
 */

?>
<p class="ssp-modal__lead">
	<?php esc_html_e( 'Castos already has a podcast with this GUID. Connecting overwrites its details with the ones from WordPress.', 'seriously-simple-podcasting' ); ?>
</p>
<?php if ( ! empty( $difference_labels ) ) : ?>
	<div class="ssp-modal__differences">
		<p class="ssp-modal__list-heading">
			<?php esc_html_e( 'These fields differ:', 'seriously-simple-podcasting' ); ?>
		</p>
		<ul>
			<?php foreach ( $difference_labels as $ssp_label ) : ?>
				<li><?php echo esc_html( $ssp_label ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
