<?php
/**
 * Podcast List Template
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
	// Output empty wrapper for consistency
	echo '<div class="ssp-podcasts"></div>';
	return;
}

// Get columns parameter with default value
$columns = isset( $columns ) ? intval( $columns ) : 1;
?>

<div class="ssp-podcasts ssp-podcasts-columns-<?php echo esc_attr( $columns ); ?>">
	<?php foreach ( $podcasts as $podcast ) : ?>
		<div class="ssp-podcast-card">
			<div class="ssp-podcast-image">
				<?php if ( ! empty( $podcast['cover_image'] ) ) : ?>
					<img src="<?php echo esc_url( $podcast['cover_image'] ); ?>" alt="<?php echo esc_attr( $podcast['name'] ); ?>" />
				<?php else : ?>
					<div class="ssp-podcast-placeholder">
						<span class="ssp-podcast-placeholder-text"><?php echo esc_html( $podcast['name'] ); ?></span>
					</div>
				<?php endif; ?>
			</div>
			
			<div class="ssp-podcast-content">
				<div class="ssp-podcast-header">
					<h3 class="ssp-podcast-title"><?php echo esc_html( $podcast['name'] ); ?></h3>
				</div>
				
				<div class="ssp-podcast-episode-count">
					<?php
					$episode_count = $podcast['episode_count'];
					if ( $episode_count === 1 ) {
						echo esc_html( $episode_count . ' episode' );
					} else {
						echo esc_html( $episode_count . ' episodes' );
					}
					?>
				</div>
				
				<?php if ( ! empty( $podcast['description'] ) ) : ?>
					<div class="ssp-podcast-description">
						<?php echo wp_kses_post( $podcast['description'] ); ?>
					</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $podcast['url'] ) ) : ?>
					<a href="<?php echo esc_url( $podcast['url'] ); ?>" class="ssp-listen-now-button">
						Listen Now →
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<style>
.ssp-podcasts {
	display: grid;
	gap: 20px;
	max-width: 100%;
}

/* Single column layout (default) */
.ssp-podcasts-columns-1 {
	grid-template-columns: 1fr;
}

/* Two column layout */
.ssp-podcasts-columns-2 {
	grid-template-columns: repeat(2, 1fr);
}

/* Three column layout */
.ssp-podcasts-columns-3 {
	grid-template-columns: repeat(3, 1fr);
}

.ssp-podcast-card {
	display: flex;
	background: #f8f9fa;
	border-radius: 8px;
	padding: 16px;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	transition: box-shadow 0.3s ease;
	position: relative;
}

.ssp-podcast-card:hover {
	box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.ssp-podcast-image {
	flex-shrink: 0;
	width: 120px;
	height: 120px;
	margin-right: 20px;
	border-radius: 8px;
	overflow: hidden;
	background: #e9ecef;
	display: flex;
	align-items: center;
	justify-content: center;
}

.ssp-podcast-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.ssp-podcast-placeholder {
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #6c5ce7, #a29bfe);
	color: white;
	text-align: center;
	padding: 10px;
}

.ssp-podcast-placeholder-text {
	font-size: 14px;
	font-weight: 600;
	line-height: 1.2;
	word-break: break-word;
}

.ssp-podcast-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: flex-start;
}

.ssp-podcast-header {
	margin-bottom: 8px;
}

.ssp-podcast-title {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
	color: #6c5ce7;
	line-height: 1.2;
}

.ssp-listen-now-button {
	background: #343a40;
	color: white;
	text-decoration: none;
	padding: 8px 16px;
	border-radius: 4px;
	font-size: 14px;
	font-weight: 600;
	white-space: nowrap;
	transition: background-color 0.3s ease;
	display: inline-block;
	margin-top: auto;
	margin-left: auto;
	width: fit-content;
	box-sizing: border-box;
}

.ssp-listen-now-button:hover {
	background: #495057;
	color: white;
	text-decoration: none;
}

.ssp-podcast-episode-count {
	font-size: 16px;
	color: #6c757d;
	margin-bottom: 8px;
	font-weight: 500;
}

.ssp-podcast-description {
	font-size: 16px;
	color: #6c757d;
	line-height: 1.5;
	margin: 0 0 16px 0;
	text-align: justify;
}

/* Responsive design */
@media (max-width: 768px) {
	/* Force single column layout on mobile for all column configurations */
	.ssp-podcasts-columns-2,
	.ssp-podcasts-columns-3 {
		grid-template-columns: 1fr;
	}
	
	.ssp-podcast-card {
		flex-direction: column;
		text-align: center;
	}
	
	.ssp-podcast-image {
		width: 300px;
        height: 300px;
        max-width: 90%;
        max-height: 90%;
		margin: 0 auto 15px auto;
	}
	
	.ssp-podcast-title {
		font-size: 20px;
		text-align: center;
	}
	
	.ssp-listen-now-button {
		font-size: 12px;
		padding: 12px 12px;
		max-width: 300px;
		width: 90%;
		display: block;
		margin: auto auto 12px auto;
		text-align: center;
	}
	
	.ssp-podcast-episode-count {
		font-size: 14px;
	}
	
	.ssp-podcast-description {
		font-size: 14px;
	}
}

/* Tablet responsive design */
@media (max-width: 1024px) and (min-width: 769px) {
	/* Reduce 3 columns to 2 columns on tablet */
	.ssp-podcasts-columns-3 {
		grid-template-columns: repeat(2, 1fr);
	}
}
</style>
