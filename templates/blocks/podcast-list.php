<?php
use SeriouslySimplePodcasting\Integrations\Blocks\Castos_Blocks;

/**
 * @see Castos_Blocks::podcast_list_render_callback
 *
 * @var \SeriouslySimplePodcasting\Repositories\Episode_Repository $episode_repository;
 * @var \SeriouslySimplePodcasting\Controllers\Players_Controller $players_controller;
 * @var string $permalink_structure
 * @var string $player_style
 * @var WP_Query $episodes_query
 * @var WP_Post $episode
 * @var bool $show_player
 * @var bool $show_title
 * @var bool $show_img
 * @var string $img_size
 * @var bool $is_player_below
 * @var bool $show_excerpt
 * @var string $permalink
 * @var string $permalink_structure
 * @var string $paginate
 * @var int $columns_per_row
 * @var int $title_size
 * @var bool $title_under_img
 * */
?>

<?php if ( $episodes_query->have_posts() ) : ?>
	<style>
		:root {
			--ssp-podcast-list-title-size: <?php echo $title_size ?>px;
			--ssp-podcast-list-cols: <?php echo $columns_per_row ?>;
		}
	</style>
	<div class="ssp-podcast-list">
		<div class="ssp-podcast-list__articles">
		<?php while ( $episodes_query->have_posts() ) :
			$episodes_query->the_post();
			$episode   = get_post();
			$permalink = get_permalink();

			$player = '';
			if ( ! empty( $show_player ) ) {
				$file   = $permalink_structure ? $episode_repository->get_episode_download_link( $episode->ID ) : $episode_repository->get_enclosure( $episode->ID );
				$player = $players_controller->load_media_player( $file, $episode->ID, $player_style );
			}
			?>
			<article class="podcast-<?php echo $episode->ID ?> podcast type-podcast">
				<?php if ( $show_title && ! $title_under_img ) : ?>
				<h3>
					<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo the_title(); ?>
					</a>
				</h3>
				<?php endif; ?>
				<div class="podcast-content">
					<?php if ( $show_img ) : ?>
						<a class="podcast-image-link" href="<?php echo esc_url( $permalink ) ?>"
						   aria-hidden="true" tabindex="-1">
							<?php echo ssp_episode_image( $episode->ID, $img_size ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $show_title && $title_under_img ) : ?>
						<h3>
							<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
								<?php echo the_title(); ?>
							</a>
						</h3>
					<?php endif; ?>
					<?php if ( $player && ! $is_player_below ) : ?>
						<p><?php echo $player; ?></p>
					<?php endif; ?>
					<?php if ( $show_excerpt ) : ?>
						<p><?php echo get_the_excerpt(); ?></p>
					<?php endif; ?>
					<?php if ( $player && $is_player_below ) : ?>
						<p><?php echo $player; ?></p>
					<?php endif; ?>
				</div>
			</article>
		<?php endwhile ?>
		</div>
		<?php echo is_array( $paginate ) ?
			'<div class="ssp-podcast-list__pagination">' . implode( "\n", $paginate ) . '</div>' :
			'';
		?>
	</div>
<?php wp_reset_postdata(); ?>
<?php else : ?>
	<?php _e( 'Sorry, episodes not found', 'seriously-simple-podcasting' ); ?>
<?php endif;
