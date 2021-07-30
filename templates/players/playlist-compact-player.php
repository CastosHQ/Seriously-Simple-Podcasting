<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Players_Controller::render_playlist_compact_player()
 *
 * @var string $safe_type
 * @var string $safe_style
 * @var string $width
 * @var string $height
 * @var array $data
 **/
?>

<div class="wp-playlist wp-<?php echo $safe_type ?>-playlist wp-playlist-<?php echo $safe_style ?>">
	<<?php echo $safe_type ?> controls="controls" preload="none" width="<?php echo (int) $width; ?>"<?php
	if ( 'video' === $safe_type ):
		echo ' height="', (int) $height, '"';
	endif; ?>>
</<?php echo $safe_type ?>>


<?php	if ( 'audio' === $data['type'] ) : ?>
	<div class="wp-playlist-current-item"></div>
<?php endif ?>

<div class="wp-playlist-next"></div>
<div class="wp-playlist-prev"></div>

<noscript>
	<ol>
		<?php
		foreach ( $data['tracks'] as $track ) {
			printf( '<li>%s</li>', $track['src'] );
		}
		?>
	</ol>
</noscript>
<script type="application/json" class="wp-playlist-script"><?php echo wp_json_encode( $data ) ?></script>

</div><!-- Closing div -->
