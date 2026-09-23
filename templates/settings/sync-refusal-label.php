<?php
/**
 * Sync status label for a refusal the user can resolve by connecting to the Castos podcast.
 *
 * @var \SeriouslySimplePodcasting\Entities\Sync_Status $status
 * @var string   $classes           Label CSS classes.
 * @var string   $refusal_id        Modal ID.
 * @var string[] $difference_labels Translated labels of the fields that differ.
 * @var string   $series_edit_link  Link to this podcast's edit screen.
 *
 * @package SeriouslySimplePodcasting
 */

?>
<div class="ssp-sync-refusal">
	<button type="button" class="ssp-sync-label <?php echo esc_attr( $classes ); ?> ssp-full-label"
			title="<?php echo esc_html( $status->get_tooltip( true ) ); ?>" aria-expanded="false" aria-haspopup="dialog"
			aria-controls="<?php echo esc_attr( $refusal_id ); ?>"
			data-ssp-modal-open="<?php echo esc_attr( $refusal_id ); ?>">
		<span><?php echo esc_html( $status->title ); ?></span>
	</button>
	<?php
	ssp_renderer()->render(
		'components/modal',
		array(
			'id'      => $refusal_id,
			'title'   => __( 'Connect to the existing Castos podcast?', 'seriously-simple-podcasting' ),
			'body'    => ssp_renderer()->fetch( 'settings/sync-refusal-modal-body', compact( 'difference_labels' ) ),
			'actions' => ssp_renderer()->fetch( 'settings/sync-refusal-modal-actions', compact( 'series_edit_link' ) ),
		)
	);
	?>
</div>
