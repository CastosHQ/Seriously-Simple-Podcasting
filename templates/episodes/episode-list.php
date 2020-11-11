
<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

foreach ( $episodes as $podcastEpisode ) {
	$player = new Players_Controller($file, $version);
	$episode = [];
	$player_style = get_option( 'ss_podcasting_player_style', 'standard' );
	?>
		<article class="podcast-<?php echo $podcastEpisode->ID ?> podcast type-podcast">
			<h5>
				<a class="entry-title-link" rel="bookmark" href="<?php echo $podcastEpisode->guid ?>">
					<?php echo $podcastEpisode->post_title; ?>
				</a>
			</h5>
				<?php
                    if($player_style === 'standard') {
	                    echo $player->media_player($podcastEpisode->ID);
                    }
                    if($player_style === 'larger') {
	                    $episode_id['id'] = $podcastEpisode->ID;
	                    echo $player->elementor_html_player($episode_id);
                    }
				?>
		</article>
	<?php }
?>

