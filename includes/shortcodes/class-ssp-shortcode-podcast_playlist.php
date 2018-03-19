<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Recent Podcast Episodes Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Shortcodes
 * @since 		1.15.0
 */
class SSP_Shortcode_Podcast_Playlist {

	/**
	 * Shortcode function to display podcast playlist (copied and modified from wp-includes/media.php)
	 * @param  array  $params Shortcode paramaters
	 * @return string         HTML output
	 */
	function shortcode( $params ) {
		global $content_width, $ss_podcasting;

		// Get list of episode IDs for display from `episodes` parameter
		if ( ! empty( $params['episodes'] ) ) {
			// 'episodes' is explicitly ordered, unless you specify otherwise.
			if ( empty( $params['orderby'] ) ) {
				$params['orderby'] = 'post__in';
			}
			$params['include'] = $params['episodes'];
		}

		// Parse shortcode attributes
		$atts = shortcode_atts( array(
			'type'		    => 'audio',
			'series'	    => '',
			'order'		    => 'ASC',
			'orderby'	    => 'menu_order ID',
			'include'	    => '',
			'exclude'       => '',
			'style'		    => 'light',
			'player_style'  => 'mini',
			'tracklist'     => true,
			'tracknumbers'  => true,
			'images'	    => true,
            'limit'         => -1
		), $params, 'podcast_playlist' );

		// Included posts must be passed as an array
		if( $atts['include'] ) {
			$atts['include'] = explode( ',', $atts['include'] );
		}

		// Excluded posts must be passed as an array
		if( $atts['exclude'] ) {
			$atts['exclude'] = explode( ',', $atts['exclude'] );
		}

		// Get all podcast post types
		$podcast_post_types = ssp_post_types( true );

		// Set up query arguments for fetching podcast episodes
		$query_args = array(
			'post_status'         => 'publish',
			'post_type'           => $podcast_post_types,
			'posts_per_page'      => (int) $atts['limit'] > 0 ? $atts['limit'] : -1,
			'order'				  => $atts['order'],
			'orderby'			  => $atts['orderby'],
			'ignore_sticky_posts' => true,
			'post__in'            => $atts['include'],
			'post__not_in'        => $atts['exclude'],
		);

		// Make sure to only fetch episodes that have a media file
		$query_args['meta_query'] = array(
			array(
				'key'     => 'audio_file',
				'compare' => '!=',
				'value'   => '',
			),
		);

		// Limit query to episodes in defined series only
		if ( $atts['series'] ) {

			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'series',
					'field' => 'slug',
					'terms' => $atts['series'],
				),
			);

		}

		// Allow dynamic filtering of query args
		$query_args = apply_filters( 'ssp_podcast_playlist_query_args', $query_args );

		// Fetch all episodes for display
		$episodes = get_posts( $query_args );

		if( empty ( $episodes ) ) {
			return;
		}

		$outer = 22; // default padding and border of wrapper

		$default_width = 640;
		$default_height = 360;

		$theme_width = empty( $content_width ) ? $default_width : ( $content_width - $outer );
		$theme_height = empty( $content_width ) ? $default_height : round( ( $default_height * $theme_width ) / $default_width );

		$data = array(
			'type' => $atts['type'],
			// don't pass strings to JSON, will be truthy in JS
			'tracklist' => wp_validate_boolean( $atts['tracklist'] ),
			'tracknumbers' => wp_validate_boolean( $atts['tracknumbers'] ),
			'images' => wp_validate_boolean( $atts['images'] ),
			'artists' => false,
		);

		$tracks = array();
		foreach ( $episodes as $episode ) {

			$url = $ss_podcasting->get_enclosure( $episode->ID );
			if ( get_option( 'permalink_structure' ) ) {
				$url = $ss_podcasting->get_episode_download_link( $episode->ID );
				$url = str_replace( 'podcast-download', 'podcast-player', $url );
			}

			// Get episode file type
			$ftype = wp_check_filetype( $url, wp_get_mime_types() );

			if( $episode->post_excerpt ) {
				$episode_excerpt = $episode->post_excerpt;
			} else {
				$episode_excerpt = $episode->post_title;
			}

			// Setup episode data for media player
			$track = array(
				'src' => $url,
				'type' => $ftype['type'],
				'caption' => $episode->post_title,
				'title' => $episode_excerpt,
				'description' => $episode->post_content,
			);

			// We don't need the ID3 meta data here, but still need to set an empty array
			$track['meta'] = array();

			// Set video dimensions for player
			if ( 'video' === $atts['type'] ) {
				$track['dimensions'] = array(
					'original' => compact( $default_width, $default_height ),
					'resized' => array(
						'width' => $theme_width,
						'height' => $theme_height
					)
				);
			}

			// Get episode image
			if ( $atts['images'] ) {
				$thumb_id = get_post_thumbnail_id( $episode->ID );
				if ( ! empty( $thumb_id ) ) {
					list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'full' );
					$track['image'] = compact( 'src', 'width', 'height' );
					list( $src, $width, $height ) = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
					$track['thumb'] = compact( 'src', 'width', 'height' );
				} else {
					$track['image'] = '';
					$track['thumb'] = '';
				}
			}

			$tracks[] = $track;
		}

		$data['tracks'] = $tracks;

		$safe_type = esc_attr( $atts['type'] );
		$safe_style = esc_attr( $atts['style'] );

		static $instance = 0;
		$instance++;

		ob_start();

        //$player_style = (string) get_option( 'ss_podcasting_player_style' ); // Not taking into account global settings for now
        //$player_style = $atts['player_style'];
		$player_style = "mini";

		if ( 1 === $instance && "larger" !== $player_style ) {
			/* This hook is defined in wp-includes/media.php */
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		} ?>
        <div class="wp-playlist wp-<?php echo $safe_type ?>-playlist wp-playlist-<?php echo $safe_style ?>">

            <?php

                if( 'audio' === $atts['type'] && "larger" == $player_style ){
                    echo $ss_podcasting->media_player( $ss_podcasting->get_episode_download_link( $episodes[0]->ID ), $episodes[0]->ID, "large" );
                }else{
                    ?>
                        <<?php echo $safe_type ?> controls="controls" preload="none" width="<?php
                        echo (int) $theme_width;
                        ?>"<?php if ( 'video' === $safe_type ):
                            echo ' height="', (int) $theme_height, '"';
                        endif; ?>></<?php echo $safe_type ?>>
                    <?php
                }

                if( "larger" == $player_style ) :
                    global $largePlayerInstanceNumber;
                    add_action( 'wp_footer', function(){
                        global $largePlayerInstanceNumber;
                        ?>
                            <script>
                                (function($){
                                    $( document ).ready( function(){
                                        $( '#sspPlayListTracks<?php echo $largePlayerInstanceNumber; ?> .ssp-playlist-item a' ).on( 'click', function(e){
                                            $( '#sspPlayListTracks<?php echo $largePlayerInstanceNumber; ?>' ).find( '.ssp-playlist-playing' ).removeClass( 'ssp-playlist-playing' );
                                            $( e.currentTarget ).parents( '.ssp-playlist-item' ).addClass( 'ssp-playlist-playing' );
                                            window.ssp_player<?php echo $largePlayerInstanceNumber; ?>.load( $( e.currentTarget ).attr( 'href' ) );
                                            $( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> .ssp-player-title' ).html(
                                                $( e.currentTarget ).data( 'ssp-title' ) +
                                                ( $( e.currentTarget ).data( 'ssp-series' ) ? '<br><span class="ssp-player-series">' + $( e.currentTarget ).data( 'ssp-series' ) + '</span>' : '' )
                                            );
                                            $( '#ssp_player_id_<?php echo $largePlayerInstanceNumber; ?> a.ssp-episode-download' ).attr( 'href', $( e.currentTarget ).data( 'ssp-download' ) );
                                            e.returnValue = false;
                                            e.preventDefault();
                                            return false;
                                        } );
                                    } );
                                }(jQuery))
                            </script>
                        <?php
                    } );
			if ( true === $data['tracklist']) :
                    ?>
                        <div class="ssp-playlist-tracks" id="sspPlayListTracks<?php echo $largePlayerInstanceNumber; ?>">
                            <?php
                                $pc = 0;

                                foreach ( $episodes as $episode ) {

                                    if( $series = get_the_terms( $episode->ID, 'series' ) ){
                                        $episode_series =  ( !empty( $series ) && isset( $series[0] ) ) ? substr( $series[0]->name, 0, 35) . ( strlen( $series[0]->name ) > 35 ? '...' : '' ) : '';
                                    }else{
                                        $episode_series = '';
                                    }

                                    $pc++;
                                    $url = $ss_podcasting->get_enclosure( $episode->ID );

                                    if ( get_option( 'permalink_structure' ) ) {
                                        $url = $ss_podcasting->get_episode_download_link( $episode->ID );
                                        $url = str_replace( 'podcast-download', 'podcast-player', $url );
                                    }
                                    printf(
                                        '
                                            <div class="ssp-playlist-item' . ( 1 === $pc ? " ssp-playlist-playing" : "" ) . '">
                                                <a class="ssp-playlist-caption" href="%s" data-ssp-title="%s" data-ssp-series="%s" data-ssp-download="%s">
                                                    %s
                                                </a>
                                            </div>
                                        ',
                                        $url,
                                        $episode->post_title,
                                        $episode_series,
                                        $ss_podcasting->get_episode_download_link( $episode->ID, 'download' ),
                                        $pc . '. ' . $episode->post_title
                                    );
                                }
                            ?>
                        </div>
                    <?php
			endif;
                else :
            ?>

                <?php if ( 'audio' === $atts['type'] ) : ?>
                    <div class="wp-playlist-current-item"></div>
                <?php endif ?>

                <div class="wp-playlist-next"></div>
                <div class="wp-playlist-prev"></div>

                <noscript>
                <ol>
                    <?php
                        foreach ( $episodes as $episode ) {
                            $url = $ss_podcasting->get_enclosure( $episode->ID );
                            if ( get_option( 'permalink_structure' ) ) {
                                $url = $ss_podcasting->get_episode_download_link( $episode->ID );
                                $url = str_replace( 'podcast-download', 'podcast-player', $url );
                            }
                            printf( '<li>%s</li>', $url );
                        }
                    ?>
                </ol>
                </noscript>
                <script type="application/json" class="wp-playlist-script"><?php echo wp_json_encode( $data ) ?></script>

            <?php
                endif; // Only show the above if it is core WordPress Media Player
            ?>

        </div>
		<?php
		return ob_get_clean();
	}

}

$GLOBALS['ssp_shortcodes']['podcast_playlist'] = new SSP_Shortcode_Podcast_Playlist();
