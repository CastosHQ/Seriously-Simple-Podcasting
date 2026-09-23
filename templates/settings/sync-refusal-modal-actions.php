<?php
/**
 * Guidance and actions of the Castos sync confirmation modal.
 *
 * @var string $series_edit_link Link to this podcast's edit screen.
 *
 * @package SeriouslySimplePodcasting
 */

?>
<p class="ssp-modal__guidance">
	<?php
	printf(
		wp_kses(
			/* translators: %s: URL to edit this podcast's GUID. */
			__( 'Sync not started. To create a separate podcast on Castos, <a href="%s">generate a new GUID</a> for this podcast.', 'seriously-simple-podcasting' ),
			array(
				'a' => array(
					'href' => array(),
				),
			)
		),
		esc_url( $series_edit_link )
	);
	?>
</p>
<div class="ssp-modal__actions">
	<button type="button" class="button-secondary ssp-admin" data-ssp-modal-close="true">
		<?php esc_html_e( 'Cancel', 'seriously-simple-podcasting' ); ?>
	</button>
	<button type="button" class="button-primary trigger-sync ssp-admin js-sync-refusal-connect" data-ssp-modal-close="true">
		<?php esc_html_e( 'Connect and overwrite', 'seriously-simple-podcasting' ); ?>
	</button>
</div>
