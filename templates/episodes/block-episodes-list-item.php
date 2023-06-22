<?php
/**
 * @see \SeriouslySimplePodcasting\Integrations\Blocks\Castos_Blocks::podcast_list_render_callback
 *
 * @var WP_Post $episode
 * @var WP_Post $player
 * @var bool $show_title
 * @var bool $show_img
 * @var string $img_size
 * @var bool $is_player_below
 * @var bool $show_excerpt
 * @var string $permalink
 * */
?>
<article class="podcast-<?php echo $episode->ID ?> podcast type-podcast">
	<h2>
		<?php if ( $show_title ) : ?>
		<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo the_title(); ?>
		</a>
		<?php endif; ?>
	</h2>
	<div class="podcast-content">
		<?php if ( $show_img ) : ?>
			<a class="podcast-image-link" href="<?php echo esc_url( $permalink ) ?>"
			   aria-hidden="true" tabindex="-1">
				<?php echo ssp_episode_image( $episode->ID, $img_size ); ?>
			</a>
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
