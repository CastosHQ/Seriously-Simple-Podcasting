
<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

foreach ( $episodes as $podcastEpisode ) {
	$player = new Players_Controller($file, $version);
	?>
		<article class="podcast-<?php echo $podcastEpisode->ID ?> podcast type-podcast">
			<h5>
				<a class="entry-title-link" rel="bookmark" href="<?php echo $podcastEpisode->guid ?>">
					<?php echo $podcastEpisode->post_title; ?>
				</a>
			</h5>
				<?php
					echo $player->media_player($podcastEpisode->ID);
					//echo do_shortcode('[elementor_html_player id=' . $podcastEpisode->ID . ']');
				?>
		</article>
	<?php }
?>