<?php
/**
 * @var \SeriouslySimplePodcasting\Entities\Sync_Status $status
 * */
?>
<div class="ssp-episode-sync-status">
	<div>
		<label class="ssp-episode-details-label" for="audio_file">
			<?php echo __( 'Sync Status:', 'seriously-simple-podcasting' ) ?>
		</label>
	</div>
	<div class="ssp-episode-sync-status__data">
			<span class="ssp-sync-label ssp-full-label <?php echo esc_attr( $status->status ) ?>"
				  title="<?php echo esc_html( $status->title ) ?>">
			<?php echo esc_html( $status->title ) ?>
			</span>
		<span><?php echo esc_html( $status->message ) ?></span>
	</div>
	<div class="ssp-episode-sync-status__description">
		<?php echo $status->error; ?>
	</div>
</div>
