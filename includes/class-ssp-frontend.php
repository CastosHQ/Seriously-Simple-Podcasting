<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.0
 */
class SSP_Frontend {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	private $token;
	private $home_url;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 * @return 	void
	 */
	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';

		// Use plugin CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add meta data to start of podcast content
		$locations = get_option( 'ss_podcasting_player_locations', array() );

		if( in_array( 'content', (array) $locations ) ) {
			add_filter( 'the_content', array( $this, 'content_meta_data' ) );
		}

		if( in_array( 'excerpt', (array) $locations ) ) {
			add_filter( 'the_excerpt', array( $this, 'content_meta_data' ) );
		}

		// Add RSS meta tag to site header
		add_action( 'wp_head' , array( $this, 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		add_action( 'pre_get_posts' , array( $this, 'add_to_home_query' ) );

		add_action( 'wp', array( $this, 'download_file' ), 1 );
	}

	/**
	 * Load frontend CSS
	 * @return void
	 */
	public function enqueue_scripts() {
		if( apply_filters( 'ssp_use_plugin_styles', true ) ) {
			wp_register_style( 'ss_podcasting', esc_url( $this->assets_url . 'css/style.css' ), array(), '1.8.0' );
			wp_enqueue_style( 'ss_podcasting' );
		}
	}

	/**
	 * Get download link for episode
	 * @param  integer $episode_id ID of episode
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id ) {
		$file = $this->get_enclosure( $episode_id );
		$link = add_query_arg( array( 'podcast_episode' => $file ), $this->home_url );
		return apply_filters( 'ssp_episode_download_link', $link, $episode_id, $file );
	}

	/**
	 * Dislpay episode meta data
	 * @param  string $content Episode content
	 * @return string          Updated episode content
	 */
	public function content_meta_data( $content ) {
		global $post;

		$allowed_post_types = get_option( 'ss_podcasting_use_post_types', array() );
		$allowed_post_types[] = $this->token;

		if( ( in_array( get_post_type(), $allowed_post_types ) ) && ! is_feed( 'podcast' ) ) {

			$post_id = get_the_ID();
			$file = $this->get_enclosure( $post_id );

			$meta = '';

			if( $file ) {
				$link = $this->get_episode_download_link( $post_id );
				$duration = get_post_meta( $post_id , 'duration' , true );
				$size = get_post_meta( $post_id , 'filesize' , true );
				if( ! $size ) {
					$size = $this->get_file_size( $file );
					$size = $size['formatted'];
					if( $size ) {
						if( isset( $size['formatted'] ) ) {
							update_post_meta( $post_id, 'filesize', $size['formatted'] );
						}

						if( isset( $size['raw'] ) ) {
							update_post_meta( $post_id, 'filesize_raw', $size['raw'] );
						}
					}
				}

				$meta .= '<div class="podcast_player">' . $this->audio_player( $file ) . '</div>';

				$meta .= '<div class="podcast_meta"><aside>';
				if( $link && strlen( $link ) > 0 ) { $meta .= '<a href="' . esc_url( $link ) . '" title="' . get_the_title() . ' ">' . __( 'Download file' , 'ss-podcasting' ) . '</a>'; }
				if( $duration && strlen( $duration ) > 0 ) { if( $link && strlen( $link ) > 0 ) { $meta .= ' | '; } $meta .= __( 'Duration' , 'ss-podcasting' ) . ': ' . $duration; }
				if( $size && strlen( $size ) > 0 ) { if( ( $duration && strlen( $duration ) > 0 ) || ( $file && strlen( $file ) > 0 ) ) { $meta .= ' | '; } $meta .= __( 'Size' , 'ss-podcasting' ) . ': ' . $size; }
				$meta .= '</aside></div>';
			}

			$meta = apply_filters( 'ssp_episode_meta', $meta, $post_id );

			$content = $meta . $content;

		}

		return $content;

	}

	/**
	 * Add podcast to home page query
	 * @param object $query The query object
	 */
	public function add_to_home_query( $query ) {
		if ( ! is_admin() ) {
			$include_in_main_query = get_option('ss_podcasting_include_in_main_query');
			if( $include_in_main_query && $include_in_main_query == 'on' ) {
				if ( $query->is_home() && $query->is_main_query() ) {
					$query->set( 'post_type', array( 'post', 'podcast' ) );
				}
			}
		}
	}

	/**
	 * Get size of media file
	 * @param  string  $file File name & path
	 * @return boolean       File sizeo n success, boolean false on failure
	 */
	public function get_file_size( $file = '' ) {

		if( $file ) {

			$data = wp_remote_head( $file, array( 'timeout' => 10, 'redirection' => 5 ) );

			if( ! is_wp_error( $data ) && is_array( $data ) && isset( $data['headers']['content-length'] ) ) {

				$raw = $data['headers']['content-length'];
				$formatted = $this->format_bytes( $raw );

				$size = array(
					'raw' => $raw,
					'formatted' => $formatted
				);

				return apply_filters( 'ssp_file_size', $size, $file );

			}

		}

		return false;
	}

	/**
	 * Get duration of audio file
	 * Uses getid3 class for calculation audio duration - http://www.getid3.org/
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {

		if( $file ) {

			if( ! class_exists( 'getid3' ) ) {
				require_once( $this->assets_dir . '/getid3/getid3.php' );
			}

			$getid3 = new getid3();

			// Identify file by root path and not URL (required for getID3 class)
			$site_root = trailingslashit( ABSPATH );
			$file = str_replace( $this->home_url, $site_root, $file );

			$info = $getid3->analyze( $file );

			$duration = false;

			if( isset( $info['playtime_string'] ) && strlen( $info['playtime_string'] ) > 0 ) {
				$duration = $info['playtime_string'];
			} else {
				if( isset( $info['playtime_seconds'] ) && strlen( $info['playtime_seconds'] ) > 0 ) {
					$duration = gmdate( 'H:i:s' , $info['playtime_seconds'] );
				}
			}

			return apply_filters( 'ssp_file_duration', $duration, $file );

		}

		return false;
	}

	/**
	 * Format filesize for display
	 * @param  integer $size      Raw file size
	 * @param  integer $precision Level of precision for formatting
	 * @return mixed              Formatted file size on success, false on failure
	 */
	protected function format_bytes( $size , $precision = 2 ) {

		if( $size ) {

		    $base = log ( $size ) / log( 1024 );
		    $suffixes = array( '' , 'k' , 'M' , 'G' , 'T' );
		    $bytes = round( pow( 1024 , $base - floor( $base ) ) , $precision ) . $suffixes[ floor( $base ) ];

		    return apply_filters( 'ssp_file_size_formatted', $bytes, $size );
		}

		return false;
	}

	/**
	 * Format duration of audio track for display
	 * @param  integer $duration Raw duration in seconds
	 * @return mixed             Formatted duration on success, 0 on failure
	 */
	public function format_duration( $duration = '' ) {

		$length = false;

		if( $duration ) {
			sscanf( $duration , "%d:%d:%d" , $hours , $minutes , $seconds );
			$length = isset( $seconds ) ? $hours * 3600 + $minutes * 60 + $seconds : $hours * 60 + $minutes;

			if( ! $length ) {
				$length = (int) $duration;
			}

			return apply_filters( 'ssp_file_duration_formatted', $length, $duration );
		}

		return 0;
	}

	/**
	 * Get MIME type of attachment file
	 * @param  string $attachment Attachment URL
	 * @return mixed              MIME type on success, false on failure
	 */
	public function get_attachment_mimetype( $attachment = '' ) {

		if( $attachment ) {
		    global $wpdb;

		    $prefix = $wpdb->prefix;

		    $attachment = $wpdb->get_col($wpdb->prepare( 'SELECT ID FROM ' . $prefix . 'posts' . ' WHERE guid="' . $attachment . '";' ) );

		    if( $attachment[0] ) {
			    $id = $attachment[0];

			    $mime_type = get_post_mime_type( $id );

			    return apply_filters( 'ssp_attachment_mimetype', $mime_type, $id );
			}

		}

		return false;

	}

	/**
	 * Load audio player for given file
	 * @param  string $src Source of audio file
	 * @return mixed       Audio player HTML on success, false on failure
	 */
	public function audio_player( $src = '' ) {

		if( $src ) {
			return wp_audio_shortcode( array( 'src' => $src ) );
		}

		return false;
	}

	/**
	 * Get episode image
	 * @param  integer $id   ID of episode
	 * @param  string  $size Image size
	 * @return string        Image HTML markup
	 */
	public function get_image( $id = 0, $size = 'podcast-thumbnail' ) {
		$response = '';

		if ( has_post_thumbnail( $id ) ) {
			// If not a string or an array, and not an integer, default to 200x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 200, 9999 );
			}
			$response = get_the_post_thumbnail( intval( $id ), $size );
		}

		return apply_filters( 'ssp_episode_image', $response, $id );
	}

	/**
	 * Get podcast
	 * @param  string/array $args Arguments to be passed to the query.
	 * @return array/boolean      Array if true, boolean if false.
	 */
	public function get_podcast( $args = '' ) {
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => ''
		);

		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'ssp_get_podcast_args', $args );

		if( 'episodes' == $args['content'] ) {

			// The Query Arguments
			$query_args = array();
			$query_args['post_type'] = 'podcast';
			$query_args['posts_per_page'] = -1;
			$query_args['suppress_filters'] = 0;

			if ( $args['series'] != '' ) {
				$query_args['series'] = $args['series'];
			}

			// The Query
			$query = get_posts( $query_args );

			// The Display
			if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
				foreach ( $query as $k => $v ) {
					// Get the URL
					$query[$k]->url = get_permalink( $v->ID );
				}
			} else {
				$query = false;
			}

		} else {

			$terms = get_terms( 'series' );

			if( count( $terms ) > 0) {
				foreach ( $terms as $term ) {
					$query[ $term->term_id ] = new stdClass();
					$query[ $term->term_id ]->title = $term->name;
		    		$query[ $term->term_id ]->url = get_term_link( $term );
		    		$posts = get_posts( array(
		    			'post_type' => 'podcast',
		    			'posts_per_page' => -1,
		    			'series' => $term->slug
		    			)
		    		);
		    		$count = count( $posts );
		    		$query[ $term->term_id ]->count = $count;
			    }
			}

		}

		$query['content'] = $args['content'];

		return $query;
	}

	/**
	 * Get episode enclosure
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {

		if( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, 'enclosure', true ), $episode_id );
		}

		return false;
	}

	/**
	 * Get episode from audio file
	 * @param  string $file File name & path
	 * @return object       Episode post object
	 */
	public function get_episode_from_file( $file = '' ) {

		$episode = false;

		if( $file != '' ) {

			$args = array(
				'post_type' => 'podcast',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_key' => 'enclosure',
				'meta_value' => $file
			);

			$qry = new WP_Query( $args );

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) { $qry->the_post();
					$episode = get_queried_object();
					break;
				}
			}
		}

		return apply_filters( 'ssp_episode_from_file', $episode, $file );

	}

	/**
	 * Download file from $_GET['podcast_episode']
	 * @return void
	 */
	public function download_file() {

		if( is_podcast_download() ) {

			$file = esc_attr( $_GET['podcast_episode'] );

			if( $file ) {

				// Get episode object
				$episode = $this->get_episode_from_file( $file );

				// Allow other actions
			    do_action( 'ss_podcasting_file_download', $file, $episode );

			    // Set necessary headers
				header( "Pragma: no-cache" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Robots: none" );
				header( "Content-Type: application/force-download" );
				header( "Content-Description: File Transfer" );
				header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
				header( "Content-Transfer-Encoding: binary" );

				// Set size of file
		        if ( $size = @filesize( $file ) ) {
		        	header( "Content-Length: " . $size );
		        }

		        // Use ssp_readfile_chunked() if allowed on the server or simply access file directly
				@ssp_readfile_chunked( "$file" ) or header( 'Location: ' . $file );

			}
		}
	}

	/**
	 * Display feed meta tag in site HTML
	 * @return void
	 */
	public function rss_meta_tag() {

		// Get feed slug
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );

		$feed_url = $this->site_url . 'feed/' . $feed_slug;
		$custom_feed_url = get_option('ss_podcasting_feed_url');
		if( $custom_feed_url ) {
			$feed_url = $custom_feed_url;
		}

		$html = '<link rel="alternate" type="application/rss+xml" title="Podcast RSS feed" href="' . esc_url( $feed_url ) . '" />';

		echo apply_filters( 'ssp_rss_meta_tag', $html );
	}

}