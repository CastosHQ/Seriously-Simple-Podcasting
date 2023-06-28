<?php

namespace SeriouslySimplePodcasting\Repositories;

use SeriouslySimplePodcasting\Handlers\CPT_Podcast_Handler;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Query;

/**
 * Episode Repository
 *
 * Used to set or get specific data for an episode
 * Eventually any methods on the episode controller
 * not specific to processing/rendering a request to display and episode
 * should be moved here
 *
 * @package Seriously Simple Podcasting
 * @since 2.4.3
 */
class Episode_Repository implements Service {

	use Useful_Variables;

	public function __construct() {
		$this->init_useful_variables();
	}

	/**
	 * Return a series id for an episode
	 *
	 * @param $id
	 *
	 * @return int
	 * @todo check if there is a global function for this, and use it.
	 */
	public function get_series_id( $id ) {
		$series_id = 0;
		$series    = get_the_terms( $id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : 0;
		}

		return $series_id;
	}

	/**
	 * Return feed url for a specific episode.
	 *
	 * @param $id
	 *
	 * @return string
	 *
	 */
	public function get_feed_url( $id ) {
		$feed_series = 'default';
		$series_id   = $this->get_series_id( $id );
		if ( ! empty( $series_id ) ) {
			$series      = get_term_by( 'id', $series_id, 'series' );
			$feed_series = $series->slug;
		}

		$permalink_structure = get_option( 'permalink_structure' );

		if ( $permalink_structure ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', SSP_CPT_PODCAST );
			$feed_url  = trailingslashit( home_url() ) . 'feed/' . $feed_slug;
		} else {
			$feed_url = trailingslashit( home_url() ) . '?feed=' . $this->token;
		}

		if ( $feed_series && 'default' !== $feed_series ) {
			if ( $permalink_structure ) {
				$feed_url .= '/' . $feed_series;
			} else {
				$feed_url .= '&podcast_series=' . $feed_series;
			}
		}

		$feed_url = apply_filters( 'ssp_feed_url', $feed_url );

		return $feed_url;
	}


	/**
	 * @param $episode_id
	 *
	 * @return int|null
	 */
	public function get_episode_series_id( $episode_id ) {
		$series_id = null;
		$series    = get_the_terms( $episode_id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : null;
		}

		return $series_id;
	}

	/**
	 * @param array $atts
	 *
	 * @return int[]|\WP_Post[]
	 */
	public function get_playlist_episodes( $atts ) {
		// Get all podcast post types
		$podcast_post_types = ssp_post_types( true );

		// Set up query arguments for fetching podcast episodes
		$query_args = array(
			'post_status'         => 'publish',
			'post_type'           => $podcast_post_types,
			'posts_per_page'      => (int) $atts['limit'] > 0 ? $atts['limit'] : 10,
			'order'               => $atts['order'],
			'orderby'             => $atts['orderby'],
			'ignore_sticky_posts' => true,
			'post__in'            => $atts['include'],
			'post__not_in'        => $atts['exclude'],
			'paged'               => $atts['page'] > 0 ? $atts['page'] : 1,
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
			$series_arr = strpos( $atts['series'], ',' ) ? explode( ',', $atts['series'] ) : (array) $atts['series'];

			foreach ( $series_arr as $series ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'series',
					'field'    => 'slug',
					'terms'    => $series,
				);
			}

			if ( count( $series_arr ) > 1 ) {
				$query_args['tax_query']['relation'] = 'OR';
			}
		}

		// Allow dynamic filtering of query args
		$query_args = apply_filters( 'ssp_podcast_playlist_query_args', $query_args );

		// Fetch all episodes for display
		return get_posts( $query_args );
	}

	/**
	 * @param $episode_id
	 *
	 * @return false|mixed|void
	 */
	public function get_podcast_title( $episode_id ) {
		$series_id = $this->get_episode_series_id( $episode_id );

		if ( $series_id ) {
			$title = get_option( 'ss_podcasting_data_title_' . $series_id );
		}

		if ( empty( $title ) ) {
			$title = get_option( 'ss_podcasting_data_title' );
		}

		return $title;
	}


	/**
	 * Get the latest episode ID for a player
	 *
	 * @return int
	 */
	public function get_latest_episode_id() {
		if ( is_admin() ) {
			$post_status = array( 'publish', 'draft', 'future' );
		} else {
			$post_status = array( 'publish' );
		}
		$args     = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => $post_status,
		);
		$episodes = get_posts( $args );
		if ( empty( $episodes ) ) {
			return 0;
		}
		$episode = $episodes[0];

		return $episode->ID;
	}

	/**
	 * Sets up the template data for the HTML5 player, based on the episode id passed.
	 *
	 * @param int $id Episode id, 0 for current, -1 for latest
	 * @param \WP_Post $current_post Current post
	 *
	 * @return array
	 */
	public function get_player_data( $id, $current_post = null, $skip_empty_audio = true ) {
		try {
			$player_mode           = get_option( 'ss_podcasting_player_mode', 'dark' );
			$show_subscribe_button = 'on' === get_option( 'ss_podcasting_subscribe_button_enabled', 'on' );
			$show_share_button     = 'on' === get_option( 'ss_podcasting_share_button_enabled', 'on' );

			if ( '0' == $id || '' === $id ) {
				global $post;

				$allowed_post_types = array_merge( ssp_post_types(), array('auto_draft') );

				if ( empty( $post ) || ! in_array( $post->post_type, $allowed_post_types ) ) {
					// Possibly it's a page, or a Gutenberg template editor
					$id = $this->get_latest_episode_id();
				} else {
					$id = $post->ID;
				}

				if ( 'Auto Draft' === $post->post_title ) {
					$post->post_title = __( 'Current Episode', 'seriously-simple-podcasting' );
				}
			}

			if ( '-1' == $id ) {
				$id = $this->get_latest_episode_id();
			}

			$audio_file = get_post_meta( $id, 'audio_file', true );

			if ( $skip_empty_audio && empty( $audio_file ) ) {
				throw new \Exception();
			}

			$options_handler = new Options_Handler();

			/**
			 * Get the episode (post) object
			 * If the id passed is empty or 0, get_post will return the current post
			 */
			$episode               = isset( $post ) ? $post : get_post( $id );
			$current_post          = $current_post ?: $episode;
			$episode_duration      = get_post_meta( $id, 'duration', true );
			$current_url           = get_post_permalink( $current_post->ID );
			$audio_file            = $this->get_episode_player_link( $id );
			$album_art             = $this->get_album_art( $id, 'thumbnail' );
			$podcast_title         = $this->get_podcast_title( $id );
			$feed_url              = $this->get_feed_url( $id );
			$embed_code            = preg_replace( '/(\r?\n){2,}/', '\n\n', get_post_embed_html( 500, 350, $current_post ) );
			$subscribe_links       = $options_handler->get_subscribe_urls( $id, 'subscribe_buttons' );

			// set any other info
			$template_data = array(
				'episode'               => $episode,
				'episode_id'            => $episode->ID,
				'date'                  => $this->format_post_date( $episode->post_date ),
				'duration'              => $episode_duration,
				'current_url'           => $current_url,
				'audio_file'            => $audio_file,
				'album_art'             => $album_art,
				'podcast_title'         => $podcast_title,
				'feed_url'              => $feed_url,
				'subscribe_links'       => $subscribe_links,
				'embed_code'            => $embed_code,
				'player_mode'           => $player_mode,
				'show_subscribe_button' => $show_subscribe_button,
				'show_share_button'     => $show_share_button,
				'title'                 => $episode->post_title,
				'excerpt'               => ssp_get_episode_excerpt( $episode->ID ),
				'player_id'             => wp_rand(),
			);

			return apply_filters( 'ssp_html_player_data', $template_data );
		} catch( \Exception $e ){
			return apply_filters( 'ssp_html_player_data', array() );
		}


	}

	protected function format_post_date( $post_date, $format = 'M j, Y' ) {
		$timestamp = strtotime( $post_date );

		return date( $format, $timestamp );
	}

	/**
	 * Get player link for episode.
	 *
	 * @param int $episode_id
	 *
	 * @return string
	 */
	public function get_episode_player_link( $episode_id ) {
		$file = $this->get_episode_download_link( $episode_id );

		// Switch to podcast player URL
		$file = str_replace( 'podcast-download', 'podcast-player', $file );

		return $file;
	}

	/**
	 * Get download link for episode
	 *
	 * @param $episode_id
	 * @param string $referrer
	 *
	 * @return string
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {

		// Get file URL
		$file = $this->get_enclosure( $episode_id );

		if ( ! $file ) {
			return '';
		}

		// Get download link based on permalink structure
		if ( get_option( 'permalink_structure' ) ) {
			$episode = get_post( $episode_id );
			// Get file extension - default to MP3 to prevent empty extension strings
			$ext = pathinfo( $file, PATHINFO_EXTENSION );
			if ( ! $ext ) {
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

		// If there is a media file prefix, lets add it
		$series_id = ssp_get_episode_series_id( $episode_id );
		$media_prefix = ssp_get_media_prefix( $series_id );
		if ( $media_prefix ) {
			$link = parse_episode_url_with_media_prefix( $link, $media_prefix );
		}

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $episode_id, $file );
	}

	/**
	 * Get episode enclosure
	 *
	 * @param integer $episode_id ID of episode
	 *
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {

		if ( $episode_id ) {
			return apply_filters( 'ssp_episode_enclosure', get_post_meta( $episode_id, apply_filters( 'ssp_audio_file_meta_key', 'audio_file' ), true ), $episode_id );
		}

		return '';
	}

	/**
	 * Set episode enclosure
	 *
	 * @param integer $episode_id ID of episode.
	 *
	 * @return int|bool  Meta ID if the key didn't exist, true on successful update, false on failure
	 */
	public function set_enclosure( $episode_id, $enclosure ) {
		$meta_key = apply_filters( 'ssp_audio_file_meta_key', 'audio_file' );

		return update_post_meta( $episode_id, $meta_key, $enclosure );
	}


	/**
	 * Get Album Art for Player
	 *
	 * Iteratively tries to find the correct album art based on whether the desired image is of square aspect ratio.
	 * Falls back to default album art if it can not find the correct ones.
	 *
	 * @param int $episode_id ID of the episode being loaded into the player
	 *
	 * @return array [ $src, $width, $height ]
	 */
	public function get_album_art( $episode_id = false, $size = 'full' ) {

		/**
		 * In case the episode id is not passed
		 */
		if ( ! $episode_id ) {
			return $this->get_no_album_art_image_array();
		}

		/**
		 * Option 1: if the episode has a custom field image that is square, then use that
		 */
		$thumb_id = get_post_meta( $episode_id, 'cover_image_id', true );
		if ( ! empty( $thumb_id ) ) {
			$image_data_array = ssp_get_attachment_image_src( $thumb_id, $size );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return apply_filters( 'ssp_album_art', $image_data_array, $episode_id, $size, 'cover_image' );
			}
		}

		/**
		 * Option 2: if the episode belongs to a series, which has an image that is square, then use that
		 */
		$series_id  = $this->get_episode_series_id( $episode_id );

		if ( $series_id ) {
			$series_image_attachment_id = get_term_meta( $series_id, $this->token . '_series_image_settings', true );
		}

		if ( ! empty( $series_image_attachment_id ) ) {
			$image_data_array = ssp_get_attachment_image_src( $series_image_attachment_id, $size );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return apply_filters( 'ssp_album_art', $image_data_array, $episode_id, $size, 'series_image' );
			}
		}

		/**
		 * Option 3: if the series feed settings have an image that is square, then use that
		 */
		if ( $series_id ) {
			$feed_image = get_option( 'ss_podcasting_data_image_' . $series_id, false );
		}

		if ( ! empty( $feed_image ) ) {
			$feed_image_attachment_id = attachment_url_to_postid( $feed_image );
			$image_data_array         = ssp_get_attachment_image_src( $feed_image_attachment_id, $size );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return apply_filters( 'ssp_album_art', $image_data_array, $episode_id, $size, 'series_feed_image' );
			}
		}

		/**
		 * Option 4: if the default feed settings have an image that is square, then use that
		 */
		$feed_image = get_option( 'ss_podcasting_data_image', false );
		if ( $feed_image ) {
			$feed_image_attachment_id = attachment_url_to_postid( $feed_image );
			$image_data_array         = ssp_get_attachment_image_src( $feed_image_attachment_id, $size );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return apply_filters( 'ssp_album_art', $image_data_array, $episode_id, $size, 'default_feed_image' );
			}
		}

		/**
		 * Option 5: None of the above passed, return the no-album-art image
		 */
		$image_data_array = $this->get_no_album_art_image_array();
		return apply_filters( 'ssp_album_art', $image_data_array, $episode_id, $size, 'no_album_art_image' );

	}


	/**
	 * Returns the no album art image
	 *
	 * @return array
	 */
	public function get_no_album_art_image_array() {
		$src    = SSP_PLUGIN_URL . 'assets/images/no-album-art.png';
		$width  = 300;
		$height = 300;

		$img_data = compact( 'src', 'width', 'height' );

		return apply_filters( 'ssp_no_album_image', $img_data );
	}


	/**
	 * Gather a list of the last 3 episodes for the Elementor Recent Episodes Widget
	 *
	 * @param array $args {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type int    $episodes_number Number of episodes. Default: 3.
	 *     @type string $episode_types   Episode types. Variants: all_podcast_types, podcast. Default: podcast.
	 *     @type string $order_by        Order by field. Variants: published, recorded. Default: published.
	 *     @type string $podcast_term    Fetch episodes from the specified podcast.
	 *     @type int $paged              Page number.
	 * }
	 *
	 * @return WP_Query
	 */
	public function get_episodes_query( $args = array() ) {
		$defaults = array(
			'episodes_number' => 3,
			'episode_types'   => 'all_podcast_types',
			'order_by'        => 'published',
			'podcast_term'    => 0,
			'paged'           => 1,
			'order'           => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$post_types = ( 'all_podcast_types' === $args['episode_types'] ) ? ssp_post_types( true ) : SSP_CPT_PODCAST;

		$query = array(
			'posts_per_page' => $args['episodes_number'],
			'post_type'      => $post_types,
			'post_status'    => array( 'publish' ),
			'paged'          => $args['paged'],
			'order'          => $args['order'],
		);

		if ( 'recorded' === $args['order_by'] ) {
			$query['orderby']  = 'meta_value';
			$query['meta_key'] = 'date_recorded';
		}

		if ( $args['podcast_term'] ) {
			$query['tax_query'] = array(
				array(
					'taxonomy' => ssp_series_taxonomy(),
					'field'    => 'id',
					'terms'    => $args['podcast_term'],
				),
			);
		}

		return new WP_Query( $query );
	}

	/**
	 * Get the type of podcast episode (audio or video)
	 * @param  int $episode_id ID of episode
	 * @return string  The type of the episode (audio|video).
	 */
	public function get_episode_type( $episode_id = 0 ) {

		if( ! $episode_id ) {
			return false;
		}

		$type = get_post_meta( $episode_id , 'episode_type' , true );

		if( ! $type ) {
			$type = 'audio';
		}

		return $type;
	}


	/**
	 * Get size of media file
	 * @param  string  $file File name & path
	 * @return boolean       File size on success, boolean false on failure
	 */
	public function get_file_size( $file = '' ) {

		/**
		 * ssp_enable_get_file_size filter to allow this functionality to be disabled programmatically
		 */
		$enabled = apply_filters( 'ssp_enable_get_file_size', true );
		if ( ! $enabled ) {
			return false;
		}

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// translate file URL to local file path if possible
			$file = $this->get_local_file_path( $file );

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
		/**
		 * ssp_enable_get_file_duration filter to allow this functionality to be disabled programmatically
		 */
		$enabled = apply_filters( 'ssp_enable_get_file_duration', true );
		if ( ! $enabled ) {
			return false;
		}

		if ( $file ) {

			// Include media functions if necessary
			if ( ! function_exists( 'wp_read_audio_metadata' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			// translate file URL to local file path if possible
			$file = $this->get_local_file_path( $file );

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
	 * @param  int $size      Raw file size
	 * @param  int $precision Level of precision for formatting
	 * @return int|false          Formatted file size on success, false on failure
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
	 * Returns a local file path for the given file URL if it's local. Otherwise
	 * returns the original URL
	 *
	 * @param    string    file
	 * @return   string    file or local file path
	 */
	public function get_local_file_path( $url ) {

		// Identify file by root path and not URL (required for getID3 class)
		$site_root = trailingslashit( ABSPATH );

		// Remove common dirs from the ends of site_url and site_root, so that file can be outside of the WordPress installation
		$root_chunks = explode( '/', $site_root );
		$url_chunks  = explode( '/', $this->site_url );

		end( $root_chunks );
		end( $url_chunks );

		while ( ! is_null( key( $root_chunks ) ) && ! is_null( key( $url_chunks ) ) && ( current( $root_chunks ) == current( $url_chunks ) ) ) {
			array_pop( $root_chunks );
			array_pop( $url_chunks );
			end( $root_chunks );
			end( $url_chunks );
		}

		$site_root = implode('/', $root_chunks);
		$site_url  = implode('/', $url_chunks);

		// Make sure that $site_url and $url both use https
		if ( 'https:' === $url_chunks[0] ) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		$file = str_replace( $site_url, $site_root, $url );

		return $file;
	}
}
