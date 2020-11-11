<div class="elementor-section elementor-section-boxed">
	<div class="elementor-container">

		<div id="ssp-recent-episodes" class="recent-episodes">
			<div class="ssp-recent-episodes-items">
				<?php foreach ( $episodes as $episode ) { ?>
					<div class="ssp-recent-episode-post">
						<a href="<?php echo get_the_permalink( $episode->ID ); ?>" title="<?php echo $episode->post_title ?>">
							<img src="<?php echo esc_url( get_the_post_thumbnail_url( $episode->ID, 'medium' ) ); ?>" alt="<?php echo $episode->post_title ?>">
						</a>
						<h4>
							<a href="<?php echo get_the_permalink( $episode->ID ); ?>" title="<?php echo $episode->post_title ?>"><?php echo $episode->post_title ?></a>
						</h4>
						<p><?php echo $episode->post_excerpt ?></p>
						<a href="<?php echo get_the_permalink( $episode->ID ); ?>" title="<?php echo $episode->post_title ?>" class="view-episode">Listen →</a>
					</div>
				<?php } ?>
			</div>
		</div>

	</div>
</div>
