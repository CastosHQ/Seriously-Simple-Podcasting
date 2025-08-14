<?php
/**
 * This template is used for both Podcast and Episode sync status labels.
 *
 * @var \SeriouslySimplePodcasting\Entities\Sync_Status $status
 * @var string $classes
 * @var string $link
 * @var bool $is_full_label
 *
 * */

$tooltip = sprintf( __( 'Sync status: %s', 'seriously-simple-podcasting' ), $status->title );
if ( $status->error ) {
	$tooltip .= PHP_EOL . $status->error;
}
$classes = ! empty( $classes ) ? $classes : $status->status;
if ( ! empty( $is_full_label ) ) {
	$classes .= ' ssp-full-label';
	$tooltip .= PHP_EOL . $status->message;
}
?>
<div class="ssp-sync-label <?php echo esc_attr( $classes ) ?>" title="<?php echo esc_html( $tooltip ) ?>">
	<?php if ( ! empty( $is_full_label ) ): ?>
	<span><?php echo esc_html( $status->title ) ?></span>
	<?php endif ?>
	<?php if ( ! empty( $link ) ) : ?>
		<a href="<?php echo esc_attr( $link ) ?>"></a>
	<?php endif ?>
</div>
