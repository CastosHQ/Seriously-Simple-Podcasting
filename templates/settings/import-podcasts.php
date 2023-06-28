<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Settings_Controller::show_podcast_sync_settings()
 *
 * @var $not_synced_podcasts
 * */
?>
<div class="ssp_import_podcasts">
	<h2><?php _e('Import Podcasts', 'seriously-simple-podcasting') ?></h2>
	<p><?php _e('We have found Castos podcasts that are not synced to SSP yet. Would you like to import them now?', 'seriously-simple-podcasting') ?></p>

	<table>
		<tbody>
		<?php foreach ( $not_synced_podcasts as $podcast ) : ?>
			<tr>
				<td><?php echo $podcast['podcast_title'] ?></td>
				<td>
					<button data-id="<?php echo esc_attr( $podcast['id'] ) ?>"
							data-nonce="<?php echo wp_create_nonce( 'import_castos_podcast_' . $podcast['id'] ) ?>"
							class="button js-ssp-import-podcast">
						<?php _e( 'Import', 'seriously-simple-podcasting' ) ?>
					</button>
				</td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
</div>
