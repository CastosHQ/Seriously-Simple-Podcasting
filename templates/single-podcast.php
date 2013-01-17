<?php
/**
 * Template file for display single podcast episodes
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

global $ss_podcasting, $wp_query;

get_header(); ?>

<?php
$id = get_the_ID();
$file = get_post_meta( $id , 'enclosure' , true );
$terms = wp_get_post_terms( $id , 'series' );
foreach( $terms as $term ) {
	$series_id = $term->term_id;
	$series = $term->name;
	break;
}

$post = get_post( $id );
?>


<div id="content" class="podcast_template">

	<div class="podcast_left">

		<header>
			<h1><?php echo $post->post_title; ?></h1>
		</header>

		<div class="podcast_content">

			<section>

				<?php if( has_post_thumbnail( $id ) ) { ?>
					<?php $img = wp_get_attachment_image_src( get_post_thumbnail_id( $id ) ); ?>
					<a href="<?php echo $img[0]; ?>" title="<?php echo $post->post_title; ?>">
						<?php echo get_the_post_thumbnail( $id , 'podcast-thumbnail' , array( 'class' => 'podcast_image' , 'align' => 'left' , 'alt' => get_the_title() , 'title' => get_the_title() ) ); ?>
					</a>
				<?php } ?>

				<?php if( $file && strlen( $file ) > 0 ) { ?>
					<div class="podcast_player">
						<?php echo $ss_podcasting->audio_player( $file ); ?>
					</div>
					<br/><br/>
				<?php } ?>

				<?php
				
				$file = get_post_meta( $id , 'enclosure' , true );
				$duration = get_post_meta( $id , 'duration' , true );
				$size = get_post_meta( $id , 'filesize' , true );

				$meta = '<div class="podcast_meta"><aside>';
				if( $file && strlen( $file ) > 0 ) { $meta .= '<a href="' . esc_url( $file ) . '" title="' . $post->post_title . ' ">Download file</a>'; }
				if( $duration && strlen( $duration ) > 0 ) { if( $file && strlen( $file ) > 0 ) { $meta .= ' | '; } $meta .= 'Duration: ' . $duration; }
				if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $file && strlen( $file ) > 0 ) ) { $meta .= ' | '; } $meta .= 'Size: ' . $size; }
				$meta .= '</aside></div>';

				echo $meta . wpautop( $post->post_content );

				?>

			<section>

		</div>

		<div class="podcast_clear"></div>

	</div>

	<div class="podcast_right">
		
		<?php

		$args = array(
			'post_type' => 'podcast',
			'post_status' => 'publish',
			'series' => $series
		);

		$qry = new WP_Query( $args );

		if( isset( $qry->posts[0] ) ) { ?>

			<div class="widget series_episodes">

				<h3><?php _e( 'Series:' , 'ss-podcasting' ); ?> <?php echo $series; ?></h3>

				<ul>
					<?php foreach( $qry->posts as $episode ) {
						
						$this_id = $episode->ID;
						$class_tail = '';
						if( $this_id == $id ) {
							$class_tail = ' current_episode';
						}

						echo '<li class="episode_title' . $class_tail . '">';

						echo '<a href="' . get_permalink( $this_id ) . '" title="' . $episode->post_title . '">' . $episode->post_title . '</a>';

						echo '</li>';

					} ?>
				</ul>

			</div>

		<?php } ?>
		
		<?php if ( function_exists( 'dynamic_sidebar' ) ) { dynamic_sidebar( 'podcast_sidebar' ); } ?>
	</div>

	<div class="podcast_clear"></div>

</div>


<?php get_footer(); ?>