<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_podcast_download' ) ) {
	/**
	 * Check if podcast file is being downloaded
	 * @since  1.5
	 * @return boolean True if file is being downloaded
	 */
	function is_podcast_download() {
		global $wp_query;

		$download = $episode = false;

		if ( isset( $wp_query->query_vars['podcast_episode'] ) && $wp_query->query_vars['podcast_episode'] ) {
			$download = true;
			$episode = intval( $wp_query->query_vars['podcast_episode'] );
		}

		return apply_filters( 'ssp_is_podcast_download', $download, $episode );
	}
}

if ( ! function_exists( 'ss_get_podcast' ) ) {
	/**
	 * Wrapper function to get the podcast episodes from the SeriouslySimplePodcasting class.
	 * @param  mixed $args  Arguments
	 * @since  1.0.0
	 * @return mixed        Array if true, boolean if false.
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
	 * @param  mixed $args Arguments
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

			if ( $query['content'] == 'episodes' ) {

				$i = 0;
				foreach ( $query as $post ) {

					if ( ! is_object( $post ) ) {
						continue;
					}

					$template = $tpl;
					$i++;

					setup_postdata( $post );

					$class = 'podcast';

					$title = get_the_title();
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $post->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$meta = $ss_podcasting->episode_meta( $post->ID, 'shortcode' );

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );
					$template = str_replace( '%%META%%', $meta, $template );

					$html .= $template;

				}

			} else {

				$i = 0;
				foreach ( $query as $series ) {

					if ( ! is_object( $series ) ) continue;

					$template = $tpl;
					$i++;

					$class = 'podcast';

					$title = $series->title;
					if ( true == $args['link_title'] ) {
						$title = '<a href="' . esc_url( $series->url ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';
					}

					$template = str_replace( '%%CLASS%%', $class, $template );
					$template = str_replace( '%%TITLE%%', $title, $template );

					$meta = $series->count . __( ' episodes' , 'seriously-simple-podcasting' );
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
	 * @return string          HTML output
	 */
	function ss_podcast_shortcode ( $atts ) {

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

if ( ! function_exists( 'ssp_episode_ids' ) ) {

	/**
	 * Get post IDs of all podcast episodes for all post types
	 * @since  1.8.2
	 * @return array
	 */
	function ssp_episode_ids () {
		global $ss_podcasting;

		// Remove action to prevent infinite loop
		remove_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		// Setup the default args
		$args = array(
			'post_type'      => array( 'podcast' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		// Do we have any additional post types to add?
		$podcast_post_types = ssp_post_types( false );

		if ( ! empty( $podcast_post_types ) ) {
			$args['post_type']  = ssp_post_types();
			$args['meta_query'] = array(
				array(
					'key'     => 'audio_file',
					'compare' => '!=',
					'value'   => '',
				),
			);
		}

		// Do we have this stored in the cache?
		$key   = 'episode_ids';
		$group = 'ssp';
		$podcast_episodes = wp_cache_get( $key, $group );

		// If nothing in cache then fetch episodes again and store in cache
		if ( $podcast_episodes === false ) {
			$podcast_episodes = get_posts( $args );
			wp_cache_set( $key, $podcast_episodes, $group, HOUR_IN_SECONDS * 12 );
		}

		// Reinstate action for future queries
		add_action( 'pre_get_posts', array( $ss_podcasting, 'add_all_post_types' ) );

		return $podcast_episodes;
	}

}

if ( ! function_exists( 'ssp_episodes' ) ) {

	/**
	 * Fetch all podcast episodes
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

		if ( $context === 'glance' ) {
			return $episode_ids;
		}

		if ( empty( $episode_ids ) ) {
			return array();
		}

		// Get all valid podcast post types
		$podcast_post_types = ssp_post_types( true );

		if ( empty ( $podcast_post_types ) ) {
			return array();
		}

		// Fetch podcast episodes
		$args = array(
			'post_type'           => $podcast_post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => $n,
			'ignore_sticky_posts' => true,
			'post__in'            => $episode_ids,
		);

		if ( $series ) {
			$args['series'] = esc_attr( $series );
		}

		$args = apply_filters( 'ssp_episode_query_args', $args, $context );

		if ( $return_args ) {
			return $args;
		}

		// Do we have anything in the cache here?
		$key   = 'episodes_' . $series;
		$group = 'ssp';
		$posts = wp_cache_get( $key, $group );

		// If nothing in cache then fetch episodes again and store in cache
		if ( $posts === false ) {
			$posts = get_posts( $args );
			wp_cache_add( $key, $posts, $group, HOUR_IN_SECONDS * 12 );
		}

		return $posts;
	}

}

if ( ! function_exists( 'ssp_post_types' ) ) {

	/**
	 * Fetch all valid podcast post types
	 * @param  boolean $include_podcast Include the `podcast` post type or not
	 * @since  1.8.7
	 * @return array                    Array of podcast post types
	 */
	function ssp_post_types ( $include_podcast = true ) {

		// Get saved podcast post type option
		$podcast_post_types = get_option( 'ss_podcasting_use_post_types', array() );

		// Add `podcast` post type to array if required
		if ( $include_podcast ) {
			$podcast_post_types[] = 'podcast';
		}

		$valid_podcast_post_types = array();

		// Check if post types exist
		if ( ! empty( $podcast_post_types ) ) {

			foreach ( $podcast_post_types as $type ) {
				if ( post_type_exists( $type ) ) {
					$valid_podcast_post_types[] = $type;
				}
			}

		}

		// Return only the valid podcast post types
		return apply_filters( 'ssp_podcast_post_types', $valid_podcast_post_types, $include_podcast );
	}
}

if( ! function_exists( 'ssp_get_feed_category_output' ) ) {

	/**
	 * Get the XML markup for the feed category st the specified level
	 * @param  int    $level Category level
	 * @return string        XML output for feed vategory
	 */
	function ssp_get_feed_category_output ( $level = 1, $series_id ) {

		$level = (int) $level;

		if( 1 == $level ) {
			$level = '';
		}

		$category = get_option( 'ss_podcasting_data_category' . $level, '' );
		if ( $series_id ) {
			$series_category = get_option( 'ss_podcasting_data_category' . $level . '_' . $series_id, 'no-category' );
			if ( 'no-category' != $series_category ) {
				$category = $series_category;
			}
		}
		if ( ! $category ) {
			$category = '';
			$subcategory = '';
		} else {
			$subcategory = get_option( 'ss_podcasting_data_subcategory' . $level, '' );
			if ( $series_id ) {
				$series_subcategory = get_option( 'ss_podcasting_data_subcategory' . $level . '_' . $series_id, 'no-subcategory' );
				if ( 'no-subcategory' != $series_subcategory ) {
					$subcategory = $series_subcategory;
				}
			}
		}

		return apply_filters( 'ssp_feed_category_output', array( 'category' => $category, 'subcategory' => $subcategory ), $level, $series_id );
	}

}

if ( ! function_exists( 'ssp_readfile_chunked' ) ) {

	/**
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param    string    file
	 * @param    boolean   return bytes of file
	 * @since  	 1.0.0
	 * @return   mixed
	 */
    function ssp_readfile_chunked( $file, $retbytes = true ) {

		$chunksize = 1 * ( 1024 * 1024 );
		$cnt = 0;

		$handle = fopen( $file, 'r' );
		if ( $handle === false ) {
			return false;
		}

		while ( ! feof( $handle ) ) {
			$buffer = fread( $handle, $chunksize );
			echo $buffer;
			ob_flush();
			flush();

			if ( $retbytes ) {
				$cnt += strlen( $buffer );
			}
		}

		$status = fclose( $handle );

		if ( $retbytes && $status ) {
			return $cnt;
		}

		return $status;
    }
}