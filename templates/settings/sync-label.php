<?php
/**
 * This template is used for both Podcast and Episode sync status labels.
 *
 * @var string $classes
 * @var string $tooltip
 * @var string $title
 * @var string $link
 * */
?>
<div class="ssp-sync-label <?php echo esc_attr( $classes ) ?>" title="<?php echo $tooltip ?>">
	<span><?php echo esc_html( $title ) ?></span>
	<?php if ( ! empty( $link ) ) : ?>
		<a href="<?php echo esc_attr( $link ) ?>"></a>
	<?php endif ?>
</div>
