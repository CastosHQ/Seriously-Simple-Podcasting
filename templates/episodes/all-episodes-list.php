<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

while ( $episodes->have_posts() ) : $episodes->the_post();
	$player_style = get_option( 'ss_podcasting_player_style', 'standard' );
	if ( $player_style === 'standard' ) {
		$media_player = $player->media_player( get_post()->ID );
	} else {
		$episode_id['id'] = get_post()->ID;
		$media_player     = $player->render_html_player( $episode_id );
	}
	?>
	<article class="podcast-<?php echo get_post()->ID ?> podcast type-podcast">
		<h5>
			<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( get_the_permalink( get_post()->ID ) ); ?>">
				<?php echo get_post()->post_title; ?>
			</a>
		</h5>
		<?php if ( isset( $settings['show_featured_image'] ) && 'yes' === $settings['show_featured_image'] ) { ?>
			<a class="podcast-image-link" href="<?php echo get_post()->guid ?>" aria-hidden="true"
			   tabindex="-1">
				<?php echo get_the_post_thumbnail( get_post()->ID, 'full' ); ?>
			</a>
		<?php } ?>
		<?php if ( isset( $settings['show_episode_player'] ) && 'yes' === $settings['show_episode_player'] ) {
			echo $media_player;
		} ?>
		<?php if ( isset( $settings['show_episode_excerpt'] ) && 'yes' === $settings['show_episode_excerpt'] ) { ?>
			<p><?php echo get_the_excerpt( get_post()->ID ); ?></p>
		<?php } ?>
	</article>
<?php
endwhile;

$total_pages = $episodes->max_num_pages;

if ( $total_pages > 1 ) {

	$current_page = max( 1, get_query_var( 'paged' ) );

	echo paginate_links( array(
		'base'      => get_pagenum_link( 1 ) . '%_%',
		'format'    => '/page/%#%',
		'current'   => $current_page,
		'total'     => $total_pages,
		'prev_text' => __( '« prev' ),
		'next_text' => __( 'next »' ),
	) );
}

wp_reset_postdata();
?>
