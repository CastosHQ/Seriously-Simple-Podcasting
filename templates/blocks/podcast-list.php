<?php
/**
 * Episode List Template
 *
 * @see \SeriouslySimplePodcasting\Presenters\Episode_List_Presenter
 *
 * @var \SeriouslySimplePodcasting\Repositories\Episode_Repository $episode_repository
 * @var \SeriouslySimplePodcasting\Controllers\Players_Controller $players_controller
 * @var string   $permalink_structure
 * @var string   $player_style
 * @var WP_Query $episodes_query
 * @var bool     $show_player
 * @var bool     $show_title
 * @var bool     $show_img
 * @var string   $img_size
 * @var bool     $is_player_below
 * @var bool     $show_excerpt
 * @var array    $paginate
 * @var int      $columns_per_row
 * @var int      $title_size
 * @var bool     $title_under_img
 * @var string   $title_color
 * @var string   $layout
 * @var string   $clickable
 * @var string   $pagination_type
 */
?>

<?php if ( $episodes_query->have_posts() ) : ?>
	<?php
	$is_cards          = 'cards' === $layout;
	$is_card_clickable = $is_cards && 'card' === $clickable;
	$is_title_link     = $is_cards ? 'title' === $clickable : true;
	$show_listen_btn   = $is_cards && 'title' !== $clickable;
	$instance_class    = 'ssp-el-' . wp_unique_id();
	$wrapper_class     = 'ssp-podcast-list ' . $instance_class . ( $is_cards ? ' ssp-podcast-list--cards' : '' );
	if ( $columns_per_row >= 2 ) {
		$wrapper_class .= ' ssp-podcast-list--col-2';
	}
	$pagination_class  = 'ssp-podcast-list__pagination' . ( 'full' === $pagination_type ? ' ssp-podcast-list__pagination--full' : '' );
	?>
	<style>
		.<?php echo esc_attr( $instance_class ); ?> {
			--ssp-episode-list-title-size: <?php echo intval( $title_size ); ?>px;
			--ssp-episode-list-cols: <?php echo intval( $columns_per_row ); ?>;
			<?php if ( $title_color ) : ?>
			--ssp-episode-list-title-color: <?php echo esc_attr( $title_color ); ?>;
			<?php endif; ?>
			<?php if ( ! empty( $text_color ) ) : ?>
			--ssp-episode-list-text-color: <?php echo esc_attr( $text_color ); ?>;
			<?php endif; ?>
			<?php if ( ! empty( $card_bg ) ) : ?>
			--ssp-episode-list-card-bg: <?php echo esc_attr( $card_bg ); ?>;
			<?php endif; ?>
			<?php if ( ! empty( $button_color ) ) : ?>
			--ssp-episode-list-btn-color: <?php echo esc_attr( $button_color ); ?>;
			<?php endif; ?>
			<?php if ( ! empty( $button_bg ) ) : ?>
			--ssp-episode-list-btn-bg: <?php echo esc_attr( $button_bg ); ?>;
			<?php endif; ?>
		}
		<?php if ( ! empty( $link_color ) ) : ?>
		.<?php echo esc_attr( $instance_class ); ?> a:not(.entry-title-link),
		.<?php echo esc_attr( $instance_class ); ?> .ssp-podcast-list__pagination a {
			color: <?php echo esc_attr( $link_color ); ?>;
		}
		<?php endif; ?>
	</style>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
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

			if ( $is_cards ) :
				$article_class = 'podcast-' . $episode->ID . ' podcast type-podcast';
				if ( $is_card_clickable ) {
					$article_class .= ' ssp-episode-card-clickable';
				}
			?>
			<article class="<?php echo esc_attr( $article_class ); ?>">
				<?php if ( $is_card_clickable ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="ssp-episode-card-link"
					   aria-label="<?php echo esc_attr( get_the_title() ); ?>"></a>
				<?php endif; ?>
				<?php if ( $show_img ) : ?>
					<div class="ssp-episode-card-image">
						<?php echo ssp_episode_image( $episode->ID, $img_size ); ?>
					</div>
				<?php endif; ?>
				<div class="ssp-episode-card-body">
					<?php if ( $show_title ) : ?>
					<h3 class="ssp-episode-title">
						<?php if ( $is_title_link ) : ?>
						<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
							<?php echo wp_kses_post( get_the_title() ); ?>
						</a>
						<?php else : ?>
							<?php echo wp_kses_post( get_the_title() ); ?>
						<?php endif; ?>
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
					<?php if ( $show_listen_btn ) : ?>
						<?php if ( ! $is_card_clickable ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>" class="ssp-listen-now-button"
						   aria-label="<?php echo esc_attr( sprintf( __( 'Listen to %s', 'seriously-simple-podcasting' ), get_the_title() ) ); ?>">
						<?php endif; ?>
							<span class="ssp-listen-now-button-content">
								<?php echo esc_html( $button_text ); ?> →
							</span>
						<?php if ( ! $is_card_clickable ) : ?></a><?php endif; ?>
					<?php endif; ?>
				</div>
			</article>
			<?php else : ?>
			<article class="podcast-<?php echo $episode->ID ?> podcast type-podcast">
				<?php if ( $show_title && ! $title_under_img ) : ?>
				<h3 class="ssp-episode-title">
					<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo wp_kses_post( get_the_title() ); ?>
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
						<h3 class="ssp-episode-title">
							<a class="entry-title-link" rel="bookmark" href="<?php echo esc_url( $permalink ); ?>">
								<?php echo wp_kses_post( get_the_title() ); ?>
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
			<?php endif; ?>
		<?php endwhile ?>
		</div>
		<?php echo is_array( $paginate ) && ! empty( $paginate ) ?
			'<div class="' . esc_attr( $pagination_class ) . '">' . implode( "\n", $paginate ) . '</div>' :
			'';
		?>
	</div>
<?php wp_reset_postdata(); ?>
<?php else : ?>
	<?php _e( 'Sorry, episodes not found', 'seriously-simple-podcasting' ); ?>
<?php endif;
