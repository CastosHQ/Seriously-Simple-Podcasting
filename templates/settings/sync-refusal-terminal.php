<?php
/**
 * Sync status label for a refusal SSP cannot offer a confirmation for.
 *
 * @var \SeriouslySimplePodcasting\Entities\Sync_Status $status
 * @var string $classes      Label CSS classes.
 * @var string $reason       Castos's reason for the refusal.
 * @var string $refusal_link Link to the podcast in the Castos dashboard.
 *
 * @package SeriouslySimplePodcasting
 */

?>
<div class="ssp-sync-refusal-terminal">
	<div class="ssp-sync-label <?php echo esc_attr( $classes ); ?> ssp-full-label" title="<?php echo esc_html( $status->get_tooltip( true ) ); ?>">
		<span><?php echo esc_html( $status->title ); ?></span>
	</div>
	<?php if ( '' !== $reason ) : ?>
		<p><?php echo esc_html( $reason ); ?></p>
	<?php endif; ?>
	<?php if ( ! empty( $refusal_link ) ) : ?>
		<a href="<?php echo esc_url( $refusal_link ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'View this podcast in Castos', 'seriously-simple-podcasting' ); ?>
		</a>
	<?php endif; ?>
</div>
