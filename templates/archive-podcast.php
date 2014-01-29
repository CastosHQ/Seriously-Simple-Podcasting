<?php
/**
 * Template for podcast archive pages
 *
 * @package WordPress
 * @subpackage SeriouslySimplePodcasting
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

get_header(); ?>

	<div id="content" class="podcast_archive">

		<div class="podcast_full">

			<?php if ( have_posts() ) : ?>

				<header>
					<h1><?php _e( 'Podcast' , 'ss-podcasting' ); ?></h1>
				</header>

				<?php
				$feed_url = trailingslashit( home_url() ) . '?feed=podcast';
				$custom_feed_url = get_option('ss_podcasting_feed_url');
				if( $custom_feed_url && strlen( $custom_feed_url ) > 0 && $custom_feed_url != '' ) {
					$feed_url = $custom_feed_url;
				}
				$itunes_url = str_replace( array( 'http:' , 'https:' ) , 'itpc:' , $feed_url );
				?>

				<section class="podcast_subscribe">
					Subscribe: <a href="<?php echo $feed_url; ?>" title="<?php _e( 'Standard podcast RSS feed' , 'ss-podcasting' ); ?>">RSS</a> | <a href="<?php echo $itunes_url; ?>" title="<?php _e( 'iTunes podcast feed' , 'ss-podcasting' ); ?>">iTunes</a>
				</section>

				<section>

					<?php
					/* Start the Loop */
					while ( have_posts() ) : the_post(); ?>

						<?php
						$terms = wp_get_post_terms( get_the_ID() , 'series' );
						foreach( $terms as $term ) {
							$series_id = $term->term_id;
							$series = $term->name;
							break;
						}
						?>

						<article class="podcast_episode">

							<?php if( has_post_thumbnail() ) { ?>
								<?php $img = wp_get_attachment_image_src( get_post_thumbnail_id() ); ?>
								<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
									<?php the_post_thumbnail( 'podcast-thumbnail' , array( 'class' => 'podcast_image' , 'alt' => get_the_title() , 'title' => get_the_title() ) ); ?>
								</a>
							<?php } ?>

							<h3>
								<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a>
								<div class="podcast_meta"><?php echo $series; ?><aside></div>
							</h3>

						</article>
						
					<?php
					endwhile;
					?>

				</section>

			<?php endif; ?>

			<div class="podcast_clear"></div>

		</div>

<?php get_footer(); ?>