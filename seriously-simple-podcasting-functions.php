<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! function_exists( 'is_podcast_feed' ) ) {
	/**
	 * Check if current page is podcast feed
	 * @since  1.4.2
	 * @return boolean True if current page is podcast feed
	 */
	function is_podcast_feed() {
		if( isset( $_GET['feed'] ) && in_array( $_GET['feed'], array( 'podcast', 'itunes' ) ) ) {
			return true;
		}
		return false;
	}
}

if( ! function_exists( 'is_file_download' ) ) {
	/**
	 * Check if podcast file is being downloaded
	 * @since  1.5
	 * @return boolean True if file is being downloaded
	 */
	function is_file_download() {
		if( isset( $_GET['podcast_episode'] ) ) {
			return true;
		}
		return false;
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
add_action( 'get_podcast', 'podcast_get' );

if ( ! function_exists( 'ss_podcast' ) ) {
	/**
	 * Display or return HTML-formatted podcast data.
	 * @param  string/array $args Arguments
	 * @since  1.0.0
	 * @return string
	 */
	function ss_podcast ( $args = '' ) {
		global $post;

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

		// Allow child themes/plugins to filter here.
		$args = apply_filters( 'ss_podcast_args', $args );
		$html = '';

		do_action( 'ss_podcast_before', $args );

		// The Query.
		$query = ss_get_podcast( $args );

		// The Display.
		if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
			$html .= $args['before'] . "\n";

			if ( '' != $args['title'] ) {
				$html .= $args['before_title'] . esc_html( $args['title'] ) . $args['after_title'] . "\n";
			}

			$html .= '<div class="ss_podcast">' . "\n";

			// Begin templating logic.
			$tpl = '<div class="%%CLASS%%"><h4 class="podcast-title">%%TITLE%%</h4><span class="meta">%%META%%</span></div>';
			$tpl = apply_filters( 'ss_podcast_item_template', $tpl, $args );

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

					$duration = get_post_meta( $post->ID , 'duration' , true );

					if( $duration && strlen( $duration ) > 0 ) {
						$meta = __( 'Duration: ' , 'ss-podcasting' ) . $duration;
					} else {
						$meta = '';
					}

					$template = str_replace( '%%META%%' , $meta , $template );

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

		// Allow themes/plugins to filter here.
		$html = apply_filters( 'ss_podcast_html', $html, $query, $args );

		if ( $args['echo'] != true ) { return $html; }

		// Should only run if "echo" is set to true.
		echo $html;

		do_action( 'ss_podcast_after', $args ); // Only if "echo" is set to true.
	}
}

if ( ! function_exists( 'ss_podcast_shortcode' ) ) {
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

// Register shortcode
add_shortcode( 'ss_podcast', 'ss_podcast_shortcode' );

if ( ! function_exists('readfile_chunked')) {

	/**
	 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/
	 *
	 * @param    string    file
	 * @param    boolean    return bytes of file
	 * @return   mixed
	 */
    function readfile_chunked( $file, $retbytes = true ) {

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