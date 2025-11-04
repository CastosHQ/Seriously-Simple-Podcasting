<?php
/**
 * Podcast List Template
 *
 * @see SeriouslySimplePodcasting\ShortCodes\Podcast_List::shortcode()
 *
 * @var array  $podcasts           Array of podcast data with keys: name, url, cover_image, description, episode_count
 * @var int    $columns            Number of columns for the grid layout (1-3)
 * @var string $clickable          Clickability mode: 'button', 'card', or 'title'
 * @var bool   $show_button        Whether to show the listen button (pre-processed)
 * @var bool   $show_description   Whether to show podcast descriptions
 * @var bool   $show_episode_count Whether to show episode counts
 * @var string $button_text        Custom text for the listen button
 * @var string $wrapper_class      CSS class for podcast cards (pre-processed)
 * @var string $columns_class      CSS class for grid columns (pre-processed)
 * @var int    $description_words  Maximum number of words for descriptions (pre-processed)
 * @var int    $description_chars  Maximum number of characters for descriptions (pre-processed)
 * @var string $css_vars           CSS variables for background colors (pre-processed)
 *
 * Available Hooks:
 * - ssp/podcast_list/before: Before the entire podcast list
 * - ssp/podcast_list/after: After the entire podcast list
 * - ssp/podcast_list/card/before: Before each podcast card
 * - ssp/podcast_list/card/after: After each podcast card
 * - ssp/podcast_list/image/before: Before each podcast image
 * - ssp/podcast_list/image/after: After each podcast image
 * - ssp/podcast_list/content/before: Before each podcast content area
 * - ssp/podcast_list/content/after: After each podcast content area
 *
 * Note: The ssp/podcast_list/card_data filter is applied in the shortcode class
 * before the data reaches this template.
 *
 * @package SeriouslySimplePodcasting
 * @since 3.13.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure we have podcasts data
if ( empty( $podcasts ) || ! is_array( $podcasts ) ) {
	// Output empty wrapper with CSS classes for consistency
	echo '<div class="ssp-podcasts ' . esc_attr( $columns_class ) . '"></div>';
	return;
}

// All display options are now pre-processed in the shortcode class
?>

<?php
/**
 * @action `ssp/podcast_list/before` Fires before the podcast list container
 * @param array $podcasts Array of podcast data
 * @param int   $columns  Number of columns in the grid
 */
do_action( 'ssp/podcast_list/before', $podcasts, $columns );
?>

<div class="ssp-podcasts <?php echo esc_attr( $columns_class ); ?>" role="region" aria-label="<?php esc_attr_e( 'Podcast List', 'seriously-simple-podcasting' ); ?>"<?php echo ! empty( $css_vars ) ? ' style="' . esc_attr( $css_vars ) . '"' : ''; ?>>
	<?php foreach ( $podcasts as $index => $podcast ) : ?>
		<?php
		// Get podcast-specific data
		$podcast_url        = ! empty( $podcast['url'] ) ? $podcast['url'] : '';
		$title_is_clickable = 'title' === $clickable && ! empty( $podcast_url );
		$card_is_clickable  = 'card' === $clickable && ! empty( $podcast_url );
		$cover_image        = ! empty( $podcast['cover_image'] ) ? $podcast['cover_image'] : '';
		$podcast_name       = isset( $podcast['name'] ) ? $podcast['name'] : '';

		?>
		<?php
		/**
		 * @action `ssp/podcast_list/card/before` Fires before each podcast card
		 * @param array $podcast Podcast data array
		 * @param int   $index   Current podcast index in the loop
		 */
		do_action( 'ssp/podcast_list/card/before', $podcast, $index );
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>" role="article">
			<?php if ( $card_is_clickable ) : ?>
				<!-- Absolutely positioned link that covers the entire card -->
				<a href="<?php echo esc_url( $podcast_url ); ?>" 
					class="ssp-podcast-card-link" 
					aria-label="<?php echo esc_attr( sprintf( __( 'View %s podcast details', 'seriously-simple-podcasting' ), $podcast_name ) ); ?>"></a>
			<?php endif; ?>
			
			<?php
			/**
			 * @action `ssp/podcast_list/image/before` Fires before each podcast image
			 * @param array $podcast Podcast data array
			 * @param int   $index   Current podcast index in the loop
			 */
			do_action( 'ssp/podcast_list/image/before', $podcast, $index );
			?>
			<div class="ssp-podcast-image" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Cover image for %s', 'seriously-simple-podcasting' ), $podcast_name ) ); ?>">
				<?php if ( $cover_image ) : ?>
					<img src="<?php echo esc_url( $cover_image ); ?>" 
						alt="<?php echo esc_attr( sprintf( __( 'Cover image for %s', 'seriously-simple-podcasting' ), $podcast_name ) ); ?>" 
						loading="lazy" />
				<?php else : ?>
					<div class="ssp-podcast-placeholder" aria-hidden="true">
						<span class="ssp-podcast-placeholder-text"><?php echo esc_html( $podcast_name ); ?></span>
					</div>
				<?php endif; ?>
			</div>
			<?php
			/**
			 * @action `ssp/podcast_list/image/after` Fires after each podcast image
			 * @param array $podcast Podcast data array
			 * @param int   $index   Current podcast index in the loop
			 */
			do_action( 'ssp/podcast_list/image/after', $podcast, $index );
			?>
			
			<?php
			/**
			 * @action `ssp/podcast_list/content/before` Fires before each podcast content area
			 * @param array $podcast Podcast data array
			 * @param int   $index   Current podcast index in the loop
			 */
			do_action( 'ssp/podcast_list/content/before', $podcast, $index );
			?>
			<div class="ssp-podcast-content">
				<div class="ssp-podcast-header">
					<?php if ( $title_is_clickable ) : ?>
						<a href="<?php echo esc_url( $podcast_url ); ?>" class="ssp-podcast-title-link">
					<?php endif; ?>
						<h3 class="ssp-podcast-title"><?php echo esc_html( $podcast_name ); ?></h3>
					<?php if ( $title_is_clickable ) : ?>
						</a>
					<?php endif; ?>
				</div>
				
				<?php if ( $show_episode_count ) : ?>
					<div class="ssp-podcast-episode-count" aria-label="<?php esc_attr_e( 'Number of episodes', 'seriously-simple-podcasting' ); ?>">
						<?php
						$episode_count = $podcast['episode_count'];
						/* translators: %d: number of episodes */
						echo esc_html( sprintf( _n( '%d episode', '%d episodes', $episode_count, 'seriously-simple-podcasting' ), $episode_count ) );
						?>
					</div>
				<?php endif; ?>
				
				<?php if ( $show_description && ! empty( $podcast['description'] ) ) : ?>
					<div class="ssp-podcast-description" aria-label="<?php esc_attr_e( 'Podcast description', 'seriously-simple-podcasting' ); ?>">
						<?php echo wp_kses_post( $podcast['description'] ); ?>
					</div>
				<?php endif; ?>
				
				<?php if ( $show_button && ! empty( $podcast_url ) ) : ?>
					<?php if ( ! $card_is_clickable ) : ?>
						<a href="
						<?php
						echo esc_url( $podcast_url );
						?>
						" class="ssp-listen-now-button" aria-label="
						<?php
						echo esc_attr( sprintf( __( 'Listen to %s podcast', 'seriously-simple-podcasting' ), $podcast_name ) );
						?>
						">
					<?php endif; ?>
						<span class="ssp-listen-now-button-content">
							<?php echo esc_html( $button_text ); ?> →
						</span>
					<?php
					if ( ! $card_is_clickable ) :
						?>
						</a><?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
			/**
			 * @action `ssp/podcast_list/content/after` Fires after each podcast content area
			 * @param array $podcast Podcast data array
			 * @param int   $index   Current podcast index in the loop
			 */
			do_action( 'ssp/podcast_list/content/after', $podcast, $index );
			?>
		</div>
		<?php
		/**
		 * @action `ssp/podcast_list/card/after` Fires after each podcast card
		 * @param array $podcast Podcast data array
		 * @param int   $index   Current podcast index in the loop
		 */
		do_action( 'ssp/podcast_list/card/after', $podcast, $index );
		?>
	<?php endforeach; ?>
</div>

<?php
/**
 * @action `ssp/podcast_list/after` Fires after the podcast list container
 * @param array $podcasts Array of podcast data
 * @param int   $columns  Number of columns in the grid
 */
do_action( 'ssp/podcast_list/after', $podcasts, $columns ); ?>
