<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Classes
 * @since       1.0
 */
class SSP_Frontend {
	public $version;
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
	private $template_path;
	public $token;
	public $home_url;

	/**
	 * Constructor
	 * @param 	string $file Plugin base file
	 */
	public function __construct( $file, $version ) {

		$this->version = $version;

		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
		$this->template_path = trailingslashit( $this->dir ) . 'templates/';
		$this->home_url = trailingslashit( home_url() );
		$this->token = 'podcast';

		// Add meta data to start of podcast content
		$locations = get_option( 'ss_podcasting_player_locations', array() );

		if ( in_array( 'content', (array) $locations ) ) {
			add_filter( 'the_content', array( $this, 'content_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt', (array) $locations ) ) {
			add_filter( 'the_excerpt', array( $this, 'excerpt_meta_data' ), 10, 1 );
		}

		// Add SSP label and version to generator tags
		add_action( 'get_the_generator_html', array( $this, 'generator_tag' ), 10, 2 );
		add_action( 'get_the_generator_xhtml', array( $this, 'generator_tag' ), 10, 2 );

		// Add RSS meta tag to site header
		add_action( 'wp_head' , array( $this, 'rss_meta_tag' ) );

		// Add podcast episode to main query loop if setting is activated
		add_action( 'pre_get_posts' , array( $this, 'add_to_home_query' ) );

		// Make sure to fetch all relevant post types when viewing series archive
		add_action( 'pre_get_posts' , array( $this, 'add_all_post_types' ) );

		// Download podcast episode
		add_action( 'wp', array( $this, 'download_file' ), 1 );

		// Add shortcodes
		add_shortcode( 'ss_podcast', 'ss_podcast_shortcode' );
		add_shortcode( 'podcast_episode', array( $this, 'podcast_episode_shortcode' ) );

		// Register widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ), 1 );
	}

	/**
	 * Get download link for episode
	 * @param  integer $episode_id ID of episode
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {

		// Get file URL
		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return;
		}

		// Get download link based on permalink structure
		if ( get_option( 'permalink_structure' ) ) {
			$episode = get_post( $episode_id );

			// Get file extension - default to MP3 to prevent empty extension strings
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if( ! $ext ) {
				$ext = 'mp3';
			}

			$link = $this->home_url . 'podcast-download/' . $episode_id . '/' . $episode->post_name . '.' . $ext;
		} else {
			$link = add_query_arg( array( 'podcast_episode' => $episode_id ), $this->home_url );
		}

		// Allow for dyamic referrer
		$referrer = apply_filters( 'ssp_download_referrer', $referrer, $episode_id );

		// Add referrer flag if supplied
		if ( $referrer ) {
			$link = add_query_arg( array( 'ref' => $referrer ), $link );
		}

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $episode_id, $file );
	}

	/**
	 * Add episode meta data to the full content
	 * @param  string $content Existing content
	 * @return string          Modified content
	 */
	public function content_meta_data( $content = '' ) {
		global $post, $wp_current_filter, $episode_context;

		// Don't output unformatted data on excerpts
		if ( in_array( 'get_the_excerpt', (array) $wp_current_filter ) ) {
			return $content;
		}

		// Don't output episode meta in shortcode or widget
		if ( isset( $episode_context ) && in_array( $episode_context, array( 'shortcode', 'widget' ) ) ) {
			return $content;
		}

		if( post_password_required( $post->ID ) ) {
			return $content;
		}

		$podcast_post_types = ssp_post_types( true );

		if ( in_array( $post->post_type, $podcast_post_types ) && ! is_feed() && ! isset( $_GET['feed'] ) ) {

			// Get episode meta data
			$meta = $this->episode_meta( $post->ID, 'content' );

			// Get specified player position
			$player_position = get_option( 'ss_podcasting_player_content_location', 'above' );

			switch( $player_position ) {
				case 'above': $content = $meta . $content; break;
				case 'below': $content = $content . $meta; break;
			}

		}

		return $content;
	}

	/**
	 * Add episode meta data to the excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function excerpt_meta_data( $excerpt = '' ) {
		global $post;

		if( post_password_required( $post->ID ) ) {
			return $excerpt;
		}

		$podcast_post_types = ssp_post_types( true );

		if ( ( in_array( $post->post_type, $podcast_post_types ) ) && ! is_feed() ) {

			$meta = $this->episode_meta( $post->ID, 'excerpt' );

			$excerpt = $meta . $excerpt;

		}

		return $excerpt;
	}

	/**
	 * Get episode meta data
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string          	   Episode meta
	 */
	public function episode_meta( $episode_id = 0, $context = 'content' ) {

		$meta = '';

		if ( ! $episode_id ) {
			return $meta;
		}

		$file = $this->get_enclosure( $episode_id );

		if ( $file ) {

			if ( get_option( 'permalink_structure' ) ) {
				$file = $this->get_episode_download_link( $episode_id );
			}

			$meta .= '<div class="podcast_player">' . $this->audio_player( $file ) . '</div>';

			if ( apply_filters( 'ssp_show_episode_details', true, $episode_id, $context ) ) {
				$meta .= $this->episode_meta_details( $episode_id, $context );
			}
		}

		$meta = apply_filters( 'ssp_episode_meta', $meta, $episode_id, $context );

		return $meta;
	}

	/**
	 * Fetch episode meta details
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string              Episode meta details
	 */
	public function episode_meta_details ( $episode_id = 0, $context = 'content' ) {

		if ( ! $episode_id ) {
			return;
		}

		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return;
		}

		$link = $this->get_episode_download_link( $episode_id, 'download' );
		$duration = get_post_meta( $episode_id , 'duration' , true );
		$size = get_post_meta( $episode_id , 'filesize' , true );
		if ( ! $size ) {
			$size_data = $this->get_file_size( $file );
			$size = $size_data['formatted'];
			if ( $size ) {
				if ( isset( $size_data['formatted'] ) ) {
					update_post_meta( $episode_id, 'filesize', $size_data['formatted'] );
				}

				if ( isset( $size_data['raw'] ) ) {
					update_post_meta( $episode_id, 'filesize_raw', $size_data['raw'] );
				}
			}
		}

		$date_recorded = get_post_meta( $episode_id, 'date_recorded', true );

		$meta = '<div class="podcast_meta"><aside>';
		if ( $link ) { $meta .= '<a href="' . esc_url( $link ) . '" title="' . get_the_title() . ' ">' . __( 'Download file' , 'ss-podcasting' ) . '</a>'; }
		if ( $duration ) { if ( $link ) { $meta .= ' | '; } $meta .= __( 'Duration' , 'ss-podcasting' ) . ': ' . $duration; }
		if ( $size ) { if ( $duration || $link ) { $meta .= ' | '; } $meta .= __( 'Size' , 'ss-podcasting' ) . ': ' . $size; }
		if ( $date_recorded ) { if ( $size || $duration || $link ) { $meta .= ' | '; } $meta .= __( 'Recorded on' , 'ss-podcasting' ) . ' ' . date( get_option( 'date_format' ), strtotime( $date_recorded ) ); }
		$meta .= '</aside></div>';

		$meta = apply_filters( 'ssp_episode_meta_details', $meta, $episode_id, $context );

		return $meta;

	}

	/**
	 * Add podcast to home page query
	 * @param object $query The query object
	 */
	public function add_to_home_query( $query ) {

		if ( is_admin() ) {
			return;
		}

		$include_in_main_query = get_option('ss_podcasting_include_in_main_query');
		if ( $include_in_main_query && $include_in_main_query == 'on' ) {
			if ( $query->is_home() && $query->is_main_query() ) {
				$query->set( 'post_type', array( 'post', 'podcast' ) );
			}
		}
	}

	public function add_all_post_types ( $query ) {

		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( is_post_type_archive( 'podcast' ) || is_tax( 'series' ) ) {

			$podcast_post_types = ssp_post_types( false );

			if ( empty( $podcast_post_types ) ) {
				return;
			}

			$episode_ids = ssp_episode_ids();
			if ( ! empty( $episode_ids ) ) {

				$query->set( 'post__in', $episode_ids );

				$podcast_post_types[] = 'podcast';
				$query->set( 'post_type', $podcast_post_types );

			}

		}

	}

	/**
	 * Get size of media file
	 * @param  string  $file File name & path
	 * @return boolean       File size on success, boolean false on failure
	 */
	public function get_file_size( $file = '' ) {

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// Get file data (for local file)
			$data = wp_read_audio_metadata( $file );

			$raw = $formatted = '';

			if ( $data ) {
				$raw = $data['filesize'];
				$formatted = $this->format_bytes( $raw );
			} else {

				// get file data (for remote file)
				$data = wp_remote_head( $file, array( 'timeout' => 10, 'redirection' => 5 ) );

				if ( ! is_wp_error( $data ) && is_array( $data ) && isset( $data['headers']['content-length'] ) ) {
					$raw = $data['headers']['content-length'];
					$formatted = $this->format_bytes( $raw );
				}
			}

			if ( $raw || $formatted ) {

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
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// Identify file by root path and not URL (required for getID3 class)
			$site_root = trailingslashit( ABSPATH );
			$file = str_replace( $this->home_url, $site_root, $file );

			// Get file data (will only work for local files)
			$data = wp_read_audio_metadata( $file );

			$duration = false;

			if ( $data ) {
				if ( isset( $data['length_formatted'] ) && strlen( $data['length_formatted'] ) > 0 ) {
					$duration = $data['length_formatted'];
				} else {
					if ( isset( $data['length'] ) && strlen( $data['length'] ) > 0 ) {
						$duration = gmdate( 'H:i:s', $data['length'] );
					}
				}
			}

			if ( $data ) {
				return apply_filters( 'ssp_file_duration', $duration, $file );
			}

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

		if ( $size ) {

		    $base = log ( $size ) / log( 1024 );
		    $suffixes = array( '' , 'k' , 'M' , 'G' , 'T' );
		    $formatted_size = round( pow( 1024 , $base - floor( $base ) ) , $precision ) . $suffixes[ floor( $base ) ];

		    return apply_filters( 'ssp_file_size_formatted', $formatted_size, $size );
		}

		return false;
	}

	/**
	 * Get MIME type of attachment file
	 * @param  string $attachment Attachment URL
	 * @return mixed              MIME type on success, false on failure
	 */
	public function get_attachment_mimetype( $attachment = '' ) {

		if ( $attachment ) {
		    global $wpdb;

		    $prefix = $wpdb->prefix;

		    $sql = 'SELECT ID FROM %s posts WHERE guid="%s";';
		    $prepped = $wpdb->prepare( $sql, array( $prefix, esc_url_raw( $attachment ) ) );
		    $attachment = $wpdb->get_col( $prepped );

		    if ( isset( $attachment[0] ) ) {
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

		$player = '';

		if ( $src ) {
			// Use built-in WordPress media player
			$player = wp_audio_shortcode( array( 'src' => $src ) );

			// Allow filtering so that alternative players can be used
			$player = apply_filters( 'ssp_audio_player', $player, $src );
		}

		return $player;
	}

	/**
	 * Get episode image
	 * @param  integer $id   ID of episode
	 * @param  string  $size Image size
	 * @return string        Image HTML markup
	 */
	public function get_image( $id = 0, $size = 'podcast-thumbnail' ) {
		$image = '';

		if ( has_post_thumbnail( $id ) ) {
			// If not a string or an array, and not an integer, default to 200x9999.
			if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
				$size = array( intval( $size ), intval( $size ) );
			} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
				$size = array( 200, 9999 );
			}
			$image = get_the_post_thumbnail( intval( $id ), $size );
		}

		return apply_filters( 'ssp_episode_image', $image, $id );
	}

	/**
	 * Get podcast
	 * @param  mixed $args Arguments to be passed to the query.
	 * @return mixed       Array if true, boolean if false.
	 */
	public function get_podcast( $args = '' ) {
		$defaults = array(
			'title' => '',
			'content' => 'series',
			'series' => ''
		);

		$args = apply_filters( 'ssp_get_podcast_args', wp_parse_args( $args, $defaults ) );

		$query = array();

		if ( 'episodes' == $args['content'] ) {

			// Get selected series
			$podcast_series = '';
			if ( isset( $args['series'] ) && $args['series'] ) {
				$podcast_series = $args['series'];
			}

			// Get query args
			$query_args = apply_filters( 'ssp_get_podcast_query_args', ssp_episodes( -1, $podcast_series, true, '' ) );

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

			if ( count( $terms ) > 0) {

				foreach ( $terms as $term ) {
					$query[ $term->term_id ] = new stdClass();
					$query[ $term->term_id ]->title = $term->name;
		    		$query[ $term->term_id ]->url = get_term_link( $term );

		    		$query_args = apply_filters( 'ssp_get_podcast_series_query_args', ssp_episodes( -1, $term->slug, true, '' ) );

		    		$posts = get_posts( $query_args );

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

		if ( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, 'audio_file', true ), $episode_id );
		}

		return '';
	}

	/**
	 * Get episode from audio file
	 * @param  string $file File name & path
	 * @return object       Episode post object
	 */
	public function get_episode_from_file( $file = '' ) {
		global $post;

		$episode = false;

		if ( $file != '' ) {

			$post_types = ssp_post_types( true );

			$args = array(
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'meta_key' => 'audio_file',
				'meta_value' => $file
			);

			$qry = new WP_Query( $args );

			if ( $qry->have_posts() ) {
				while ( $qry->have_posts() ) { $qry->the_post();
					$episode = $post;
					break;
				}
			}
		}

		return apply_filters( 'ssp_episode_from_file', $episode, $file );

	}

	/**
	 * Download file from `podcast_episode` query variable
	 * @return void
	 */
	public function download_file() {

		if ( is_podcast_download() ) {
			global $wp_query;

			// Get requested episode ID
			$episode_id = intval( $wp_query->query_vars['podcast_episode'] );

			if ( isset( $episode_id ) && $episode_id ) {

				// Get episode post object
				$episode = get_post( $episode_id );

				// Make sure we have a valid episode post object
				if ( ! $episode || ! is_object( $episode ) || is_wp_error( $episode ) || ! isset( $episode->ID ) ) {
					return;
				}

				// Get audio file for download
				$file = $this->get_enclosure( $episode_id );

				// Exit if no file is found
				if ( ! $file ) {
					return;
				}

				// Get file referrer
				$referrer = '';
				if( isset( $_GET['ref'] ) ) {
					$referrer = esc_attr( $_GET['ref'] );
				}

				// Allow other actions - functions hooked on here must not output any data
			    do_action( 'ssp_file_download', $file, $episode, $referrer );

			    // Set necessary headers for download
				header( "Pragma: no-cache" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Robots: none" );
				header( "Content-Description: File Transfer" );
				header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
				header( "Content-Transfer-Encoding: binary" );

				// Set size of file
		        if ( $size = @filesize( $file ) ) {
		        	header( "Content-Length: " . $size );
		        }

		        // Check file referrer
		        if( 'download' == $referrer ) {

		        	// Force file download
		        	header( "Content-Type: application/force-download" );

			        // Use ssp_readfile_chunked() if allowed on the server or simply access file directly
					@ssp_readfile_chunked( "$file" ) or header( 'Location: ' . $file );

				} else {
					// For all other referrers simply access the file directly
					header( 'Location: ' . $file );
				}

			}
		}
	}

	/**
	 * Display plugin name and version in generator meta tag
	 * @return void
	 */
	public function generator_tag( $gen, $type ) {

		// Allow generator tags to be hidden if necessary
		if ( apply_filters( 'ssp_show_generator_tag', true, $type ) ) {

			$generator = 'Seriously Simple Podcasting ' . esc_attr( $this->version );

			switch ( $type ) {
				case 'html':
					$gen .= "\n" . '<meta name="generator" content="' . $generator . '">';
				break;
				case 'xhtml':
					$gen .= "\n" . '<meta name="generator" content="' . $generator . '" />';
				break;
			}

		}

		return $gen;
	}

	/**
	 * Display feed meta tag in site HTML
	 * @return void
	 */
	public function rss_meta_tag() {

		// Get feed slug
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );

		if ( get_option( 'permalink_structure' ) ) {
			$feed_url = $this->home_url . 'feed/' . $feed_slug;
		} else {
			$feed_url = $this->home_url . '?feed=' . $feed_slug;
		}
		$custom_feed_url = get_option( 'ss_podcasting_feed_url' );
		if ( $custom_feed_url ) {
			$feed_url = $custom_feed_url;
		}

		$feed_url = apply_filters( 'ssp_feed_url', $feed_url );

		$html = '<link rel="alternate" type="application/rss+xml" title="' . __( 'Podcast RSS feed', 'ss-podcasting' ) . '" href="' . esc_url( $feed_url ) . '" />';

		echo "\n" . apply_filters( 'ssp_rss_meta_tag', $html ) . "\n\n";
	}

	/**
	 * Register plugin widgets
	 * @return void
	 */
	public function register_widgets () {

		$widgets = array(
			'recent-episodes' => 'Recent_Episodes',
			'single-episode' => 'Single_Episode',
			'series' => 'Series',
		);

		foreach ( $widgets as $id => $name ) {
			require_once( $this->dir . '/includes/widgets/class-ssp-widget-' . $id . '.php' );
			register_widget( 'SSP_Widget_' . $name );
		}

	}

	/**
	 * Shortcode function to display single podcast episode
	 * @param  array  $params Shortcode paramaters
	 * @return string         HTML output
	 */
	public function podcast_episode_shortcode ( $params ) {

		$atts = shortcode_atts( array(
	        'episode' => 0,
	        'content' => 'title,player,details',
	    ), $params );

		extract( $atts );

	    if ( ! $episode ) {
	    	return;
	    }

	    // Setup array of content items and trim whitespace
	    $content_items = explode( ',', $content );
	    $content_items = array_map( 'trim', $content_items );

	    // Get episode for display
	    $html = $this->podcast_episode( $episode, $content_items, 'shortcode' );

	    return $html;

	}

	/**
	 * Show single podcast episode with specified content items
	 * @param  integer $episode_id    ID of episode post
	 * @param  array   $content_items Orderd array of content items to display
	 * @return string                 HTML of episode with specified content items
	 */
	public function podcast_episode ( $episode_id = 0, $content_items = array( 'title', 'player', 'details' ), $context = '' ) {
		global $post, $episode_context;

		if ( ! $episode_id || ! is_array( $content_items ) || empty( $content_items ) ) {
			return;
		}

		// Get episode object
		$episode = get_post( $episode_id );

		if ( ! $episode || is_wp_error( $episode ) ) {
			return;
		}

		$html = '<div class="podcast-episode episode-' . esc_attr( $episode_id ) . '">' . "\n";

			// Setup post data for episode post object
			$post = $episode;
			setup_postdata( $post );

			$episode_context = $context;

			// Display specified content items in the order supplied
			foreach ( $content_items as $item ) {

				switch( $item ) {

					case 'title':
						$html .= '<h3 class="episode-title">' . get_the_title() . '</h3>' . "\n";
					break;

					case 'excerpt':
						$html .= '<p class="episode-excerpt">' . get_the_excerpt() . '</p>' . "\n";
					break;

					case 'content':
						$html .= '<div class="episode-content">' . apply_filters( 'the_content', get_the_content() ) . '</div>' . "\n";
					break;

					case 'player':
						$file = $this->get_enclosure( $episode_id );
						if ( get_option( 'permalink_structure' ) ) {
							$file = $this->get_episode_download_link( $episode_id );
						}
		    			$html .= '<div class="podcast_player">' . $this->audio_player( $file ) . '</div>' . "\n";
					break;

					case 'details':
						$html .= $this->episode_meta_details( $episode_id, $episode_context );
					break;

				}
			}

			// Reset post data after fetching episode details
			wp_reset_postdata();

		$html .= '</div>' . "\n";

	    return $html;
	}

}