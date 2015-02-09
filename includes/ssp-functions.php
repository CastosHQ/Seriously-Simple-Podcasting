<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! function_exists( 'is_podcast_download' ) ) {
	/**
	 * Check if podcast file is being downloaded
	 * @since  1.5
	 * @return boolean True if file is being downloaded
	 */
	function is_podcast_download() {
		$download = false;
		$episode = false;
		if( isset( $_GET['podcast_episode'] ) ) {
			$download = true;
			$episode = $_GET['podcast_episode'];
		}

		return apply_filters( 'ssp_is_podcast_download', $download, $episode );
	}
}

if ( ! function_exists( 'ss_get_podcast' ) ) {
	/**
	 * Wrapper function to get the podcast episodes from the SeriouslySimplePodcasting class.
	 * @param  string/array $args  Arguments
	 * @since  1.0.0
	 * @return array/boolean       Array if true, boolean if false.
	 */
	function ss_get_podcast( $args = '' ) {
		global $ss_podcasting;
		return $ss_podcasting->get_podcast( $args );
	}
}

/**
 * Enable the usage of do_action( 'get_podcast' ) to display podcast within a theme/plugin.
 * @since  1.0.0
 */
add_action( 'get_podcast', 'ss_podcast' );

if ( ! function_exists( 'ss_podcast' ) ) {
	/**
	 * Display or return HTML-formatted podcast data.
	 * @param  string/array $args Arguments
	 * @since  1.0.0
	 * @return string
	 */
	function ss_podcast( $args = '' ) {
		global $post, $ss_podcasting;

		$defaults = array(
			'echo' => true,
			'link_title' => true,
			'title' => '',
			'content' => 'series',
			'series' => '',
			'before' => '<div class="widget widget_ss_podcast">',
			'after' => '</div><!--/.widget widget_ss_podcast-->',
			'before_title' => '<h3>',
			'after_title' => '</h3>'
		);

		$args = wp_parse_args( $args, $defaults );

		// Allow child themes/plugins to filter here
		$args = apply_filters( 'ssp_podcast_args', $args );
		$html = '';

		do_action( 'ssp_podcast_before', $args );

		// The Query
		$query = ss_get_podcast( $args );

		// The Display
		if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
			$html .= $args['before'] . "\n";

			if ( '' != $args['title'] ) {
				$html .= $args['before_title'] . esc_html( $args['title'] ) . $args['after_title'] . "\n";
			}

			$html .= '<div class="ss_podcast">' . "\n";

			// Begin templating logic.
			$tpl = '<div class="%%CLASS%%"><h4 class="podcast-title">%%TITLE%%</h4><aside class="meta">%%META%%</aside></div>';
			$tpl = apply_filters( 'ssp_podcast_item_template', $tpl, $args );

			if( $query['content'] == 'episodes' ) {

				$i = 0;
				foreach ( $query as $post ) {

					if( ! is_object( $post ) ) continue;

					$template = $tpl;
					$i++;

					setup_postdata( $post );

					$class = 'podcast';

					$title = get_the_title();
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $post->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );

					$link = $ss_podcasting->get_episode_download_link( $post->ID );
					$duration = get_post_meta( $post->ID, 'duration', true );
					$size = get_post_meta( $post->ID, 'filesize', true );

					if( ! $size ) {
						$file = $ss_podcasting->get_enclosure( $post->ID );
						$size_data = $ss_podcasting->get_file_size( $file );
						$size = $size_data['formatted'];
						if( $size ) {
							if( isset( $size_data['formatted'] ) ) {
								update_post_meta( $post->ID, 'filesize', $size_data['formatted'] );
							}

							if( isset( $size_data['raw'] ) ) {
								update_post_meta( $post->ID, 'filesize_raw', $size_data['raw'] );
							}
						}
					}

					$meta = '';
					if( $link && strlen( $link ) > 0 ) { $meta .= '<a href="' . esc_url( $link ) . '" title="' . get_the_title() . ' ">' . __( 'Download file' , 'ss-podcasting' ) . '</a>'; }
					if( $duration && strlen( $duration ) > 0 ) { if( $link && strlen( $link ) > 0 ) { $meta .= ' | '; } $meta .= __( 'Duration' , 'ss-podcasting' ) . ': ' . $duration; }
					if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $link && strlen( $link ) > 0 ) ) { $meta .= ' | '; } $meta .= __( 'Size' , 'ss-podcasting' ) . ': ' . $size; }

					$template = str_replace( '%%META%%', $meta, $template );

					$html .= $template;

				}

			} else {

				$i = 0;
				foreach( $query as $series ) {

					if( ! is_object( $series ) ) continue;

					$template = $tpl;
					$i++;

					$class = 'podcast';

					$title = $series->title;
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $series->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );

					$meta = $series->count . __( ' episodes' , 'ss-podcasting' );
					$template = str_replace( '%%META%%' , $meta , $template );

					$html .= $template;

				}

			}

			$html .= '<div class="fix"></div>' . "\n";

			$html .= '</div><!--/.ss_podcast-->' . "\n";
			$html .= $args['after'] . "\n";

			wp_reset_postdata();
		}

		// Allow themes/plugins to filter here
		$html = apply_filters( 'ssp_podcast_html', $html, $query, $args );

		if ( ! $args['echo'] ) {
			return $html;
		}

		// Should only run if "echo" is set to true
		echo $html;

		do_action( 'ssp_podcast_after', $args );
	}
}

if ( ! function_exists( 'ss_podcast_shortcode' ) ) {

	/**
	 * Load podcast shortcode
	 * @param  array  $atts    Shortcode attributes
	 * @param  string $content Shortcode content
	 * @return string          HTML output
	 */
	function ss_podcast_shortcode ( $atts, $content = null ) {

		$args = (array) $atts;

		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => '',
			'echo' => false,
			'size' => 100,
			'link_title' => true
		);

		$args = shortcode_atts( $defaults, $atts );

		// Make sure we return and don't echo.
		$args['echo'] = false;

		return ss_podcast( $args );
	}
}

if( ! function_exists( 'ssp_episode_ids' ) ) {

	/**
	 * Get post IDs of all podcast episodes for all post types
	 * @since  1.8.2
	 * @return array
	 */
	function ssp_episode_ids () {
		global $ss_podcasting;

		// Remove action to prevent infinite loop
		remove_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		$args = array(
			'post_type' => 'podcast',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
		);

		$podcast_episodes = get_posts( $args );

		$podcast_post_types = get_option( 'ss_podcasting_use_post_types', array() );

		if( 0 < count( $podcast_post_types ) ) {

			$args = array(
				'post_type' => $podcast_post_types,
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'audio_file',
						'compare' => '!=',
						'value' => '',
					),
				),
				'fields' => 'ids',
			);

			$other_episodes = get_posts( $args );

			$podcast_episodes = array_merge( (array) $podcast_episodes, (array) $other_episodes );
		}

		// Reinstate action for future queries
		add_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		return $podcast_episodes;
	}

}

if( ! function_exists( 'ssp_episodes' ) ) {

	/**
	 * Fetch podcast episodes
	 * @param  integer $n           Number of episodes to fetch
	 * @param  string  $series      Slug of series to fetch
	 * @param  boolean $return_args True to return query args, false to return posts
	 * @param  string  $context     Context of query
	 * @since  1.8.2
	 * @return array                Array of posts or array of query args
	 */
	function ssp_episodes ( $n = 10, $series = '', $return_args = false, $context = '' ) {

		// Get all podcast episodes IDs
		$episode_ids = (array) ssp_episode_ids();

		if( 0 == count( $episode_ids ) ) {
			return array();
		}

		$podcast_post_types = get_option( 'ss_podcasting_use_post_types', array() );
		$podcast_post_types[] = 'podcast';

		// Fetch podcast episodes
		$args = array(
			'post_type' => $podcast_post_types,
			'post_status' => 'publish',
			'posts_per_page' => $n,
			'ignore_sticky_posts' => true,
			'post__in' => $episode_ids,
		);

		if( $series ) {
			$args['series'] = esc_attr( $series );
		}

		$args = apply_filters( 'ssp_episode_query_args', $args, $context );

		if( $return_args ) {
			return $args;
		}

		return get_posts( $args );
	}

}

if ( ! function_exists( 'ssp_readfile_chunked' ) ) {

	/**
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param    string    file
	 * @param    boolean    return bytes of file
	 * @since  	 1.0.0
	 * @return   mixed
	 */
    function ssp_readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$buffer = '';
		$cnt = 0;

		$handle = fopen( $file, 'r' );
		if ( $handle === FALSE )
			return FALSE;

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			ob_flush();
			flush();

			if ( $retbytes )
				$cnt += strlen( $buffer );
		}

		$status = fclose( $handle );

		if ( $retbytes && $status )
			return $cnt;

		return $status;
    }
}
?>
