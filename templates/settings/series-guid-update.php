<?php
/**
 * Podcast GUID display for the series term edit screen.
 *
 * @var string $guid                    Podcast GUID.
 * @var string $derived_guid            Derived podcast GUID.
 * @var bool   $is_series_connected_to_castos  Whether this podcast is linked to Castos.
 * @var bool   $show_generate_guid      Whether to show the GUID generation button.
 * @var string $modal_id                GUID generation modal ID.
 * @var int    $series_id              Podcast series term ID.
 */
?>
<tr class="form-field term-series-guid-wrap">
	<th scope="row">
		<label><?php esc_html_e( 'Podcast GUID', 'seriously-simple-podcasting' ); ?></label>
	</th>
	<td>
		<code data-ssp-series-guid-value><?php echo esc_html( $guid ); ?></code>
		<p class="description">
			<?php esc_html_e( 'Identifies this podcast in podcast directories and apps.', 'seriously-simple-podcasting' ); ?>
		</p>
		<?php if ( $show_generate_guid ) : ?>
			<p data-ssp-generate-series-guid-control>
				<button type="button" class="button button-secondary" aria-expanded="false" aria-haspopup="dialog"
						aria-controls="<?php echo esc_attr( $modal_id ); ?>" data-ssp-modal-open="<?php echo esc_attr( $modal_id ); ?>">
					<?php esc_html_e( 'Generate new GUID', 'seriously-simple-podcasting' ); ?>
				</button>
			</p>
			<p class="description" data-ssp-generate-series-guid-success hidden aria-live="polite"></p>
			<?php
			ob_start();
			?>
			<p class="ssp-modal__lead">
				<?php esc_html_e( 'This podcast will get a new identity based on this site\'s feed URL. Podcast directories and apps will treat it as a new podcast.', 'seriously-simple-podcasting' ); ?>
			</p>
			<div class="ssp-modal__guid-values">
				<p>
					<strong><?php esc_html_e( 'Current GUID', 'seriously-simple-podcasting' ); ?></strong><br />
					<code data-ssp-generate-series-guid-current><?php echo esc_html( $guid ); ?></code>
				</p>
				<p>
					<strong><?php esc_html_e( 'New GUID', 'seriously-simple-podcasting' ); ?></strong><br />
					<code><?php echo esc_html( $derived_guid ); ?></code>
				</p>
			</div>
			<p class="notice notice-error inline" data-ssp-generate-series-guid-error role="alert" hidden></p>
			<?php
			$ssp_guid_modal_body = ob_get_clean();
			ob_start();
			?>
			<div class="ssp-modal__actions">
				<button type="button" class="button-secondary ssp-admin" data-ssp-modal-close="true">
					<?php esc_html_e( 'Cancel', 'seriously-simple-podcasting' ); ?>
				</button>
				<button type="button" class="button-primary trigger-sync ssp-admin" data-ssp-generate-series-guid
						data-series-id="<?php echo esc_attr( $series_id ); ?>">
					<?php esc_html_e( 'Generate new GUID', 'seriously-simple-podcasting' ); ?>
					<span class="spinner" data-ssp-generate-series-guid-spinner aria-hidden="true"></span>
				</button>
			</div>
			<?php
			$ssp_guid_modal_actions = ob_get_clean();
			ssp_renderer()->render(
				'components/modal',
				array(
					'id'      => $modal_id,
					'title'   => __( 'Generate a new GUID?', 'seriously-simple-podcasting' ),
					'body'    => $ssp_guid_modal_body,
					'actions' => $ssp_guid_modal_actions,
				)
			);
			?>
		<?php elseif ( $is_series_connected_to_castos ) : ?>
			<p class="description">
				<?php esc_html_e( 'This podcast is connected to Castos. The GUID can\'t be changed while connected.', 'seriously-simple-podcasting' ); ?>
			</p>
		<?php endif; ?>
	</td>
</tr>
