<?php
/**
 * @var WP_Post[] $episodes
 * @var bool $show_episode_image
 * @var bool $show_episode_title
 * @var bool $show_episode_excerpt
 * @var bool $show_read_more
 * @var bool $show_date
 * @var string $read_more_text
 * @var string $date_source
 * @var string $date_format
 * @var string $episode_image_source
 * @var int $columns
 * */


$get_image_func = 'featured_image' === $episode_image_source ? 'get_featured_image_src' : 'get_album_art';
$ssp_episode_controller = ssp_episode_controller();
?>
<div class="elementor-section elementor-section-boxed">
	<style>
		:root {
			--ssp-recent-episodes-columns: <?php echo $columns; ?>
		}
	</style>
	<div class="elementor-container">
		<div id="ssp-recent-episodes" class="recent-episodes">
			<div class="ssp-recent-episodes-items">
				<?php foreach ( $episodes as $episode ) { ?>
					<div class="ssp-recent-episode-post">
						<?php if ( $show_episode_image ) : ?>
						<a href="<?php echo get_the_permalink( $episode->ID ); ?>" title="<?php echo $episode->post_title ?>">
							<?php $album_art = $ssp_episode_controller->$get_image_func( $episode->ID, 'medium' ); ?>
							<img src="<?php echo esc_url( $album_art['src'] ); ?>" alt="<?php echo $episode->post_title ?>">
						</a>
						<?php endif; ?>

						<?php if ( $show_episode_title ) : ?>
						<h4>
							<a href="<?php echo get_the_permalink( $episode->ID ); ?>"
							   title="<?php echo $episode->post_title ?>"><?php echo $episode->post_title ?></a>
						</h4>
						<?php endif; ?>
						<?php if ( $show_date ) :
							$date = '';
							if ( 'recorded' === $date_source ) {
								$date = get_post_meta( $episode->ID, 'date_recorded', true );
							}

							if ( $date ) {
								$date = date( $date_format, strtotime( $date ) );
							} else {
								$date = get_post_time( $date_format, true, $episode->ID );
							}
						?>

						<p class="ssp-recent-episode-post__date"><?php echo $date; ?></p>
						<?php endif; ?>
						<?php if ( $show_episode_excerpt ) : ?>
						<p class="ssp-recent-episode-post__excerpt"><?php echo $episode->post_excerpt ?></p>
						<?php endif; ?>


						<?php if ( $show_read_more ) : ?>
						<a href="<?php echo get_the_permalink( $episode->ID ); ?>"
						   title="<?php echo $episode->post_title ?>" class="view-episode"><?php echo $read_more_text; ?></a>
						<?php endif; ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
