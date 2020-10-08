<?php

use SeriouslySimplePodcasting\Controllers\Players_Controller;

while ( $episodes->have_posts() ) : $episodes->the_post();
	$player       = new Players_Controller( $file, $version );
	$player_style = get_option( 'ss_podcasting_player_style', 'standard' );
	?>

    <article class="podcast-<?php echo get_post()->ID ?> podcast type-podcast">
        <h5>
            <a class="entry-title-link" rel="bookmark" href="<?php echo get_post()->guid ?>">
				<?php echo get_post()->post_title; ?>
            </a>
        </h5>
		<?php
		if ( $player_style === 'standard' ) {
			echo $player->media_player( get_post()->ID );
		}
		if ( $player_style === 'larger' ) {
			$episode_id['id'] = get_post()->ID;
			echo $player->elementor_html_player( $episode_id );
		}
		?>
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