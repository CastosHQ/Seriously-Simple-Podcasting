<?php
/**
 * @see \SeriouslySimplePodcasting\Controllers\Players_Controller::render_playlist_compact_player()
 *
 * @var string $safe_type
 * @var string $safe_style
 * @var string $width
 * @var string $height
 * @var array $data
 * @var string $class
 **/

// The media tag is a literal, never the passed type.
$media_tag = 'video' === $safe_type ? 'video' : 'audio';
?>

<div class="wp-playlist wp-<?php echo esc_attr( $media_tag ) ?>-playlist wp-playlist-<?php echo esc_attr( $safe_style ) ?> <?php echo esc_attr( $class ) ?>">
	<<?php echo esc_html( $media_tag ); ?> controls="controls" preload="none" width="<?php echo (int) $width; ?>"<?php
	if ( 'video' === $media_tag ):
		echo ' height="', (int) $height, '"';
	endif; ?>>
</<?php echo esc_html( $media_tag ); ?>>


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
