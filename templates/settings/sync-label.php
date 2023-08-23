<?php
/**
 * This template is used for both Podcast and Episode sync status labels.
 *
 * @var string $classes
 * @var string $tooltip
 * @var string $title
 * @var string $link
 * */

$status = sprintf( __( 'Sync Status: %s', 'seriously-simple-podcasting' ), $title );
if ( $tooltip ) {
	$status .= PHP_EOL . $tooltip;
}
?>
<div class="ssp-sync-label <?php echo esc_attr( $classes ) ?>" title="<?php echo esc_html( $status ) ?>">
	<?php if ( ! empty( $link ) ) : ?>
		<a href="<?php echo esc_attr( $link ) ?>"></a>
	<?php endif ?>
</div>
