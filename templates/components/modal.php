<?php
/**
 * Reusable native admin modal.
 * 
 * @since 3.18.0
 *
 * @var string $id      Modal ID.
 * @var string $title   Modal title.
 * @var string $body    Modal body HTML.
 * @var string $actions Modal footer HTML.
 *
 * @package SeriouslySimplePodcasting
 */

$ssp_modal_id      = isset( $id ) ? (string) $id : '';
$ssp_modal_title   = isset( $title ) ? (string) $title : '';
$ssp_modal_body    = isset( $body ) ? (string) $body : '';
$ssp_modal_actions = isset( $actions ) ? (string) $actions : '';
?>
<dialog id="<?php echo esc_attr( $ssp_modal_id ); ?>" class="ssp-modal"
		aria-labelledby="<?php echo esc_attr( $ssp_modal_id . '-title' ); ?>" aria-modal="true" data-ssp-modal>
	<div class="ssp-modal__content">
		<div class="ssp-modal__header">
			<h2 id="<?php echo esc_attr( $ssp_modal_id . '-title' ); ?>" class="ssp-modal__title">
				<?php echo esc_html( $ssp_modal_title ); ?>
			</h2>
			<button type="button" class="ssp-modal__close" data-ssp-modal-close
					aria-label="<?php esc_attr_e( 'Close', 'seriously-simple-podcasting' ); ?>">
				<span aria-hidden="true">✕</span>
			</button>
		</div>
		<div class="ssp-modal__body">
			<?php echo wp_kses_post( $ssp_modal_body ); ?>
		</div>
		<div class="ssp-modal__footer">
			<?php echo wp_kses_post( $ssp_modal_actions ); ?>
		</div>
	</div>
</dialog>
