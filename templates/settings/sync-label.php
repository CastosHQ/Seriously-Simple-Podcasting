<?php
/**
 * This template is used for podcast and episode sync status labels.
 *
 * @var \SeriouslySimplePodcasting\Entities\Sync_Status $status
 * @var string $classes
 * @var string $link
 * @var bool   $is_full_label
 *
 * @package SeriouslySimplePodcasting
 */

$ssp_is_full_label = ! empty( $is_full_label );
$ssp_classes       = ! empty( $classes ) ? $classes : $status->status;
if ( $ssp_is_full_label ) {
	$ssp_classes .= ' ssp-full-label';
}
?>
<div class="ssp-sync-label <?php echo esc_attr( $ssp_classes ); ?>" title="<?php echo esc_html( $status->get_tooltip( $ssp_is_full_label ) ); ?>">
	<?php if ( $ssp_is_full_label ) : ?>
		<span><?php echo esc_html( $status->title ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $link ) ) : ?>
		<a href="<?php echo esc_attr( $link ); ?>" aria-label="<?php echo esc_attr( $status->title ); ?>"></a>
	<?php endif; ?>
</div>
