<?php

while ( $episodes->have_posts() ) : $episodes->the_post();

	$player_style = get_option( 'ss_podcasting_player_style', 'standard' );

	if ( $player_style === 'standard' ) {
		$media_player = $player->media_player( get_post()->ID );
	} else {
		$media_player = $player->render_html_player( get_post()->ID );
	}
	?>
	<article class="podcast-<?php echo get_post()->ID ?> podcast type-podcast">
		<h2>
			<a class="entry-title-link" rel="bookmark" href="<?php echo get_post()->guid ?>">
				<?php echo get_post()->post_title; ?>
			</a>
		</h2>
        <div class="podcast-content">
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
        </div>
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
