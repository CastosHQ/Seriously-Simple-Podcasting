<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

use WP_Term;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Controller
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Frontend_Controller {

	use Useful_Variables;

	/**
	 * @var Episode_Controller
	 */
	public $episode_controller;


	/**
	 * @var Players_Controller
	 * */
	public $players_controller;

	/**
	 * @var Episode_Repository
	 * */
	public $episode_repository;

	/**
	 * @var array
	 * */
	protected $removed_filters;


	/**
	 * Frontend_Controller constructor.
	 *
	 * @param Episode_Controller $episode_controller
	 * @param Players_Controller $players_controller
	 * @param Episode_Repository $episode_repository
	 */
	public function __construct( $episode_controller, $players_controller, $episode_repository ) {
		$this->init_useful_variables();

		$this->episode_controller = $episode_controller;
		$this->players_controller = $players_controller;
		$this->episode_repository = $episode_repository;

		$this->register_hooks_and_filters();
		$this->register_ajax_actions();
		$this->protect_private_podcast_episodes();
	}

	/**
	 * Register all relevant front end hooks and filters
	 */
	public function register_hooks_and_filters() {

		// Add meta data to start of podcast content
		$locations = get_option( 'ss_podcasting_player_locations', array( 'content' ) );

		if ( in_array( 'content', (array) $locations, true ) ) {
			add_filter( 'the_content', array( $this, 'content_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt', (array) $locations, true ) ) {
			add_filter( 'the_excerpt', array( $this, 'get_excerpt_meta_data' ), 10, 1 );
		}

		if ( in_array( 'excerpt_embed', (array) $locations, true ) ) {
			add_filter( 'the_excerpt_embed', array( $this, 'get_embed_meta_data' ), 10, 1 );
		}

		// Add SSP label and version to generator tags
		add_action( 'get_the_generator_html', array( $this, 'generator_tag' ), 10, 2 );
		add_action( 'get_the_generator_xhtml', array( $this, 'generator_tag' ), 10, 2 );

		// Add RSS meta tag to site header
		add_action( 'wp_head', array( $this, 'rss_meta_tag' ) );

		// Disable default RSS feed link for podcast archive page (/podcast)
		add_filter( 'post_type_archive_feed_link', array( $this, 'disable_default_podcast_rss_feed_link' ) );

		// Add podcast episode to main query loop if setting is activated
		add_action( 'pre_get_posts', array( $this, 'add_to_home_query' ) );

		// Make sure to fetch all relevant post types when viewing series archive
		add_action( 'pre_get_posts', array( $this, 'add_all_post_types' ) );

		// Make sure to fetch all relevant post types when viewing a tag archive
		add_action( 'pre_get_posts', array( $this, 'add_all_post_types_for_tag_archive' ) );

		// Download podcast episode
		add_action( 'wp', array( $this, 'download_file' ), 1 );

		add_filter( 'feed_content_type', array( $this, 'feed_content_type' ), 10, 2 );

		// Handle localisation
		add_action( 'plugins_loaded', array( $this, 'load_localisation' ) );

		add_filter( "archive_template_hierarchy", array( $this, 'fix_template_hierarchy' ) );
	}

	/**
	 * Remove class filters.
	 *
	 * @since 2.11.0
	 * @param $filter_name
	 * @param $function_name
	 * @param int $priority
	 *
	 * @return bool
	 */
	public function remove_filter( $filter_name, $function_name, $priority = 10 ) {
		$removed = remove_filter( $filter_name, array( $this, $function_name ), $priority );

		if ( $removed ) {
			$this->removed_filters[] = array(
				'filter_name'   => $filter_name,
				'function_name' => $function_name,
				'priority'      => $priority,
			);
		}

		return true;
	}

	/**
	 * Restore filters removed previously with $this->remove_filter().
	 * @since 2.11.0
	 * */
	public function restore_filters() {
		foreach ( $this->removed_filters as $filter ) {
			add_filter( $filter['filter_name'], array( $this, $filter['function_name'] ), $filter['priority'] );
		}
	}

	/**
	 * Disable default podcast RSS feed link. We add the correct feed link manually @see rss_meta_tag()
	 *
	 * @param string $link
	 *
	 * @return string|null
	 */
	public function disable_default_podcast_rss_feed_link( $link ) {
		if ( is_post_type_archive( SSP_CPT_PODCAST ) && strpos( $link, SSP_CPT_PODCAST . '/feed' ) ) {
			return null;
		}

		return $link;
	}

	/**
	 * Adds filter for private podcast episodes content.
	 */
	protected function protect_private_podcast_episodes() {
		$filter = array( $this, 'show_private_content_message' );
		add_filter( 'the_content', $filter, 20 );
		add_filter( 'the_content_rss', $filter, 20 );
		add_filter( 'comment_text_rss', $filter, 20 );
	}

	/**
	 * Show a message that episode belongs to private podcast.
	 *
	 * @param $content
	 *
	 * @return mixed|string|void
	 */
	public function show_private_content_message( $content ) {

		$post = get_post();

		$ssp_post_types = ssp_post_types();

		if ( empty( $post->post_type ) || ! in_array( $post->post_type, $ssp_post_types ) ) {
			return $content;
		}

		$terms = wp_get_post_terms( $post->ID, ssp_series_taxonomy() );

		if ( ! is_array( $terms ) ) {
			return $content;
		}

		$message =  __( 'This content is Private. To access this podcast, contact the site owner.', 'seriously-simple-podcasting' );

		// Protect default feed episodes.
		if ( empty( $terms ) && 'yes' === ssp_get_option( 'is_podcast_private' ) ) {
			return $message;
		}

		/**
		 * Protect episodes that belong to series.
		 *
		 * @var WP_Term[] $terms
		 * */
		foreach ( $terms as $term ) {
			if ( 'yes' === ssp_get_option( 'is_podcast_private', '', $term->term_id ) ) {
				return $message;
			}
		}

		return $content;
	}

	/**
	 * Unfortunately, WP core doesn't search for archive-podcast.php automatically (though it should).
	 * So add the template to search list manually.
	 *
	 * @param array $templates
	 *
	 * @return array
	 */
	public function fix_template_hierarchy( $templates ) {
		$use_post_tag = apply_filters( 'ssp_use_post_tags', true );

		// Use queried object because is_tax('post_tag') doesn't work ( is_tax is false ).
		$queried = get_queried_object();

		if ( is_tax( 'series' ) || ( $use_post_tag && isset( $queried->taxonomy ) && 'post_tag' === $queried->taxonomy ) ) {
			$templates = array_merge( array( 'archive-' . SSP_CPT_PODCAST . '.php' ), $templates );
		}

		return $templates;
	}

	public function register_ajax_actions() {
		add_action( 'wp_ajax_get_playlist_items', array( $this, 'get_ajax_playlist_items' ) );
		add_action( 'wp_ajax_nopriv_get_playlist_items', array( $this, 'get_ajax_playlist_items' ) );
	}

	public function get_ajax_playlist_items() {
		$items = $this->players_controller->get_ajax_playlist_items();
		wp_send_json_success( $items );
	}

	/**
	 * Add episode meta data to the full content
	 * @param  string $content Existing content
	 * @return string          Modified content
	 * @deprecated Use embed_player_in_content() instead
	 */
	public function content_meta_data( $content = '' ) {
		return $this->embed_player_in_content( $content );
	}

	/**
	 * Add episode meta data to the full content
	 * @param  string $content Existing content
	 * @return string          Modified content
	 */
	public function embed_player_in_content( $content = '' ) {

		global $post, $wp_current_filter, $episode_context;

		// Don't do anything if we don't have a valid post object
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $content;
		}

		// Don't output unformatted data on excerpts
		if ( in_array( 'get_the_excerpt', (array) $wp_current_filter, true ) ) {
			return $content;
		}

		// Don't output player if Elementor Player widget was embedded manually
		if ( false !== strpos( $content, 'data-widget_type="Castos Player.default"' ) ) {
			return $content;
		}

		// Don't output episode meta in shortcode or widget
		if ( isset( $episode_context ) && in_array( $episode_context, array( 'shortcode', 'widget' ), true ) ) {
			return $content;
		}

		if ( post_password_required( $post->ID ) ) {
			return $content;
		}

		// Don't output episode meta in a REST Request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}

		if ( ! apply_filters( 'ssp_show_media_player_in_content', true, $post, $content ) ) {
			return $content;
		}

		$podcast_post_types = ssp_post_types( true );

		$player_visibility = get_option( 'ss_podcasting_player_content_visibility', 'all' );

		switch ( $player_visibility ) {
			case 'membersonly':
				$show_player = is_user_logged_in();
				break;
			default:
				$show_player = true;
				break;
		}

		if ( $show_player && in_array( $post->post_type, $podcast_post_types ) && ! is_feed() && ! isset( $_GET['feed'] ) ) {

			// Get episode player
			$player = $this->get_episode_player( $post->ID, 'content' );

			// Get specified player position
			$player_position = get_option( 'ss_podcasting_player_content_location', 'above' );

			switch ( $player_position ) {
				case 'above':
					$content = $player . $content;
					break;
				case 'below':
					$content = $content . $player;
					break;
			}
		}

		return $content;
	}

	/**
	 * Runs checks to see if the player should be rendered or not
	 *
	 * @param $episode_id
	 *
	 * @param bool $show_player default true
	 *
	 * @return boolean
	 */
	public function validate_media_player( $episode_id, $show_player = true ) {
		/**
		 * If the show_player variable was already set false (specifically for the ss_podcast shortcode)
		 */
		if ( false === $show_player ) {
			return false;
		}
		/**
		 * Check if the user is using the ss_player shortcode anywhere in this post
		 */
		if ( ssp_check_if_podcast_has_shortcode( $episode_id, 'ss_player' ) ) {
			return false;
		}

		/**
		 * Check if this post is using the HTML5 player block
		 */
		if ( has_block( 'seriously-simple-podcasting/castos-player' ) || has_block( 'seriously-simple-podcasting/castos-html-player' ) ) {
			return false;
		}

		/**
		 * Check if the user is using an elementor widget version of the player.
		 */
		if ( ssp_is_elementor_ok() && ssp_check_if_podcast_has_elementor_player( $episode_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get episode meta data
	 *
	 * @param  integer $episode_id ID of episode post
	 * @param  string $context Context for display
	 *
	 * @return string               Episode meta
	 */
	public function get_episode_player( $episode_id = 0, $context = 'content' ) {

		$player = '';

		if ( ! $episode_id ) {
			return $player;
		}

		$file = $this->get_enclosure( $episode_id );

		if ( $file ) {

			if ( get_option( 'permalink_structure' ) ) {
				$file = $this->get_episode_download_link( $episode_id );
			}

			// Hide audio player in `ss_podcast` shortcode by default
			$show_player = true;
			if ( 'shortcode' === $context ) {
				$show_player = false;
			}

			// Show audio player if requested
			$player_style = get_option( 'ss_podcasting_player_style' );

			$show_player = $this->validate_media_player( $episode_id, $show_player );

			// Allow media player to be dynamically hidden/displayed
			$show_player = apply_filters( 'ssp_show_media_player', $show_player, $context );

			if ( $show_player ) {
				$player .= '<div class="podcast_player">' . $this->load_media_player( $file, $episode_id, $player_style, $context ) . '</div>';
			}
		}

		return apply_filters( 'ssp_episode_meta', $player, $episode_id, $context );
	}

	/**
	 * Get episode enclosure
	 * Wrapper for Episode_Controller get_enclosure method
	 *
	 * @param  integer $episode_id ID of episode
	 * @return string              URL of enclosure
	 */
	public function get_enclosure( $episode_id = 0 ) {
		return $this->episode_controller->get_enclosure( $episode_id );
	}

	/**
	 * Get download link for episode
	 * Wrapper for Episode_Controller get_episode_download_link method
	 *
	 * @param  integer $episode_id ID of episode
	 * @param  string $referrer Referrer
	 * @return string              Episode download link
	 */
	public function get_episode_download_link( $episode_id, $referrer = '' ) {
		return $this->episode_controller->get_episode_download_link( $episode_id, $referrer );
	}

	/**
	 * Return media player for a given file. Used to enable other checks or to prevent the player from loading
	 * @param string $src_file
	 * @param int $episode_id
	 * @param string $player_size
	 * @param string $context
	 *
	 * @return string
	 */
	public function media_player( $src_file = '', $episode_id = 0, $player_size = 'large', $context = 'block' ) {
		$media_player = '';
		$show_player  = $this->validate_media_player( $episode_id );
		if ( $show_player ) {
			$media_player = $this->load_media_player( $src_file, $episode_id, $player_size, $context );
		}
		return $media_player;
	}

	/**
	 * @param string $src_file
	 * @param int $episode_id
	 * @param string $player_size
	 * @param string $context
	 *
	 * @return mixed|void
	 */
	public function load_media_player( $src_file, $episode_id, $player_size, $context = 'block' ) {
		return $this->players_controller->load_media_player( $src_file, $episode_id, $player_size, $context );
	}

	/**
	 * Get the type of podcast episode (audio or video)
	 * @param  integer $episode_id ID of episode
	 * @return mixed              [description]
	 * @deprecated Use Episode_Repository::get_episode_type() instead
	 */
	public function get_episode_type( $episode_id = 0 ) {
		return $this->episode_repository->get_episode_type( $episode_id );
	}

	/**
	 * Fetch episode meta details
	 * @param  integer $episode_id ID of episode post
	 * @param  string  $context    Context for display
	 * @return string              Episode meta details
	 */
	public function episode_meta_details( $episode_id = 0, $context = 'content', $return = false ) {
		return $this->players_controller->episode_meta_details( $episode_id, $context, $return );
	}

	/**
	 * Add the meta data to the episode excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function get_excerpt_meta_data( $excerpt = '' ) {
		return $this->excerpt_meta_data( $excerpt, 'excerpt' );
	}

	/**
	 * Add episode meta data to the excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function excerpt_meta_data( $excerpt = '', $content = 'excerpt' ) {

		global $post;

		if( ! apply_filters( 'ssp_show_excerpt_player', true, $post, $excerpt, $content ) ){
			return $excerpt;
		}

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $excerpt;
		}

		if( post_password_required( $post->ID ) ) {
			return $excerpt;
		}

		// Don't output episode meta in a REST Request
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $content;
		}

		$podcast_post_types = ssp_post_types( true );

		$player_visibility = get_option( 'ss_podcasting_player_content_visibility', 'all' );

		switch( $player_visibility ) {
			case 'all': $show_player = true; break;
			case 'membersonly': $show_player = is_user_logged_in(); break;
			default: $show_player = true; break;
		}

		if ( $show_player && in_array( $post->post_type, $podcast_post_types ) && ! is_feed() && ! isset( $_GET['feed'] ) ) {

			$meta = $this->get_episode_player( $post->ID, $content );

			$excerpt = $meta . $excerpt;

		}

		return $excerpt;
	}

	/**
	 * Add the meta data to the embedded episode excerpt
	 * @param  string $excerpt Existing excerpt
	 * @return string          Modified excerpt
	 */
	public function get_embed_meta_data( $excerpt = '' ) {
		return $this->excerpt_meta_data( $excerpt, 'embed' );
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
				$query->set( 'post_type', array( 'post', SSP_CPT_PODCAST ) );
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

		if ( is_post_type_archive( SSP_CPT_PODCAST ) || is_tax( 'series' ) ) {

			$podcast_post_types = ssp_post_types( false );

			if ( empty( $podcast_post_types ) ) {
				$query->set( 'post_type', SSP_CPT_PODCAST );
				return;
			}

			$episode_ids = ssp_episode_ids();
			if ( ! empty( $episode_ids ) ) {

				$query->set( 'post__in', $episode_ids );

				$podcast_post_types = array_merge( array( SSP_CPT_PODCAST ), $podcast_post_types );
				$query->set( 'post_type', $podcast_post_types );
			}
		}

	}

	public function add_all_post_types_for_tag_archive( $query ) {

		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! is_tag() ) {
			return;
		}

		if ( ! apply_filters( 'ssp_use_post_tags', true ) ) {
			return;
		}

		$post_types             = $query->get( 'post_type' ) ?: array();
		$tag_archive_post_types = apply_filters( 'ssp_tag_archive_post_types', array( 'post', SSP_CPT_PODCAST ) );

		$query->set( 'post_type', array_merge( (array) $post_types, (array) $tag_archive_post_types ) );
	}

	/**
	 * Get duration of audio file
	 * @param  string $file File name & path
	 * @return mixed        File duration on success, boolean false on failure
	 */
	public function get_file_duration( $file ) {
		return $this->episode_repository->get_file_duration( $file );
	}

	/**
	 * Load audio player for given file - wrapper for `media_player` method to maintain backwards compatibility
	 * @param  string  $src 	   Source of audio file
	 * @param  integer $episode_id Episode ID for audio empty string
	 * @return string        	   Audio player HTML on success, false on failure
	 */
	public function audio_player( $src = '', $episode_id = 0 ) {
		$player = $this->media_player( $src, $episode_id );
		return apply_filters( 'ssp_audio_player', $player, $src, $episode_id );
	}

	/**
	 * Get episode image
	 * @param  integer $post_id   ID of episode
	 * @param  string  $size Image size
	 * @return string        Image HTML markup
	 */
	public function get_image( $post_id = 0, $size = 'full' ) {

		$image = '';

		if ( is_int( $size ) || ( 0 < intval( $size ) ) ) {
			$size = array( intval( $size ), intval( $size ) );
		} elseif ( ! is_string( $size ) && ! is_array( $size ) ) {
			$size = array( 200, 9999 );
		}

		$image_id = get_post_meta( $post_id, 'cover_image_id', true );

		if ( $image_id ) {
			$image = wp_get_attachment_image( $image_id, $size );
		}

		if ( empty( $image ) && has_post_thumbnail( $post_id ) ) {
			$image = get_the_post_thumbnail( intval( $post_id ), $size );
		}

		return apply_filters( 'ssp_episode_image', $image, $post_id );
	}

	/**
	 * Get episode image url
	 *
	 * @param integer $post_id ID of episode
	 * @param string $size Image size
	 *
	 * @return string        Image url
	 */
	public function get_episode_image_url( $post_id = 0, $size = 'full' ) {
		$image_url = '';
		$image_id  = get_post_meta( $post_id, 'cover_image_id', true );

		if ( ! $image_id ) {
			$image_id = get_post_thumbnail_id( $post_id );
		}

		if ( $image_id ) {
			$image_att = wp_get_attachment_image_src( $image_id, $size );
			$image_url = isset( $image_att[0] ) ? $image_att[0] : '';
		}

		return apply_filters( 'ssp_episode_image_url', $image_url, $post_id );
	}

	/**
	 * Download file from `podcast_episode` query variable
	 * @return void
	 */
	public function download_file() {

		if (  ! ssp_is_podcast_download() ) {
			return;
		}

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

			$file = $this->get_enclosure( $episode_id );
			if ( false !== strpos( $file, "\n" ) ) {
				$parts = explode( "\n", $file );
				$file  = $parts[0];
			}


			$this->validate_file( $file );

			// Get file referrer
			$referrer = '';
			if( isset( $wp_query->query_vars['podcast_ref'] ) && $wp_query->query_vars['podcast_ref'] ) {
				$referrer = $wp_query->query_vars['podcast_ref'];
			} else {
				if( isset( $_GET['ref'] ) ) {
					$referrer = esc_attr( $_GET['ref'] );
				}
			}

			if ( 'test-nginx' !== $referrer ) {
				// Allow other actions - functions hooked on here must not output any data
				do_action( 'ssp_file_download', $file, $episode, $referrer );
			}

			// Set necessary headers
			header( "Pragma: no-cache" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Robots: none" );

			$original_file = $file;

			// Dynamically change the file URL. Is used internally for Ads.
			$file = apply_filters( 'ssp_enclosure_url', $file, $episode_id, $referrer );
			$this->validate_file( $file );

			// Check file referrer
			if( 'download' == $referrer && $file == $original_file ) {

				// Set size of file
				// Do we have anything in Cache/DB?
				$size = wp_cache_get( $episode_id, 'filesize_raw' );

				// Nothing in the cache, let's see if we can figure it out.
				if ( false === $size ) {

					// Do we have anything in post_meta?
					$size = get_post_meta( $episode_id, 'filesize_raw', true );

					if ( empty( $size ) ) {

						// Let's see if we can figure out the path...
						$attachment_id = $this->get_attachment_id_from_url( $file );

						if ( ! empty( $attachment_id )  ) {
							$size = filesize( get_attached_file( $attachment_id ) );
							update_post_meta( $episode_id, 'filesize_raw', $size );
						}

					}

					// Update the cache
					wp_cache_set( $episode_id, $size, 'filesize_raw' );
				}

				// Send Content-Length header
				if ( ! empty( $size ) ) {
					header( "Content-Length: " . $size );
				}

				// Force file download
				header( "Content-Type: application/force-download" );

				// Set other relevant headers
				header( "Content-Description: File Transfer" );
				header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
				header( "Content-Transfer-Encoding: binary" );

				// Encode spaces in file names until this is fixed in core (https://core.trac.wordpress.org/ticket/36998)
				$file = str_replace( ' ', '%20', $file );
				$file = str_replace( PHP_EOL, '', $file );

				// Use ssp_readfile_chunked() if allowed on the server or simply access file directly
				@ssp_readfile_chunked( $file ) or header( 'Location: ' . $file );
			} else {

				// Encode spaces in file names until this is fixed in core (https://core.trac.wordpress.org/ticket/36998)
				$file = str_replace( ' ', '%20', $file );

				// For all other referrers redirect to the raw file
				wp_redirect( $file, 302 );
			}

			// Exit to prevent other processes running later on
			exit;

		}
	}

	/**
	 * @param string $file
	 *
	 * @return void
	 */
	protected function validate_file( $file ) {
		// Ensure that $file is a URL
		$is_url = is_string( $file ) && ( 0 === strpos( $file, 'http' ) );

		// Exit if file is not URL
		if ( ! $is_url ) {
			$this->send_404();
		}
	}

	/**
	 * Show the 404 not found page content.
	 */
	protected function send_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		get_template_part( 404 );
		exit();
	}

	/**
	 * Get the ID of an attachment from its image URL.
	 *
	 * @param   string      $url    The path to an image.
	 * @return  int|bool            ID of the attachment or 0 on failure.
	 */
	public function get_attachment_id_from_url( $url = '' ) {

		// Let's hash the URL to ensure that we don't get
		// any illegal chars that might break the cache.
		$key = md5( $url );

		// Do we have anything in the cache for this URL?
		$attachment_id = wp_cache_get( $key, 'attachment_id' );

		if ( $attachment_id === false ) {

			// Globalize
			global $wpdb;

			// If there is no url, return.
			if ( '' === $url ) {
				return false;
			}

			// Set the default
			$attachment_id = 0;


			// Function introduced in 4.0, let's try this first.
			if ( function_exists( 'attachment_url_to_postid' ) ) {
				$attachment_id = absint( attachment_url_to_postid( $url ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

			// Then this.
			if ( preg_match( '#\.[a-zA-Z0-9]+$#', $url ) ) {
				$sql = $wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s",
					esc_url_raw( $url )
				);
				$attachment_id = absint( $wpdb->get_var( $sql ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

			// And then try this
			$upload_dir_paths = wp_upload_dir();
			if ( false !== strpos( $url, $upload_dir_paths['baseurl'] ) ) {
				// Ensure that we have file extension that matches iTunes.
				$url = preg_replace( '/(?=\.(m4a|mp3|mov|mp4)$)/i', '', $url );
				// Remove the upload path base directory from the attachment URL
				$url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $url );
				// Finally, run a custom database query to get the attachment ID from the modified attachment URL
				$sql = $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $url );
				$attachment_id = absint( $wpdb->get_var( $sql ) );
				if ( 0 !== $attachment_id ) {
					wp_cache_set( $key, $attachment_id, 'attachment_id', DAY_IN_SECONDS );
					return $attachment_id;
				}
			}

		}

		return $attachment_id;
	}

	/**
	 * Get MIME type of attachment file
	 *
	 * @param  string $attachment URL of resource
	 *
	 * @return string|false MIME type on success, false on failure
	 */
	public function get_attachment_mimetype( $attachment = '' ) {
		$key = md5( $attachment );
		if ( ! $attachment ) {
			return false;
		}

		$mime = wp_cache_get( $key, 'mime-type' );
		if ( ! $mime ) {
			$filetype = wp_check_filetype( $attachment );
			$mime     = isset( $filetype['type'] ) ? $filetype['type'] : false;
			wp_cache_set( $key, $mime, 'mime-type', DAY_IN_SECONDS );
		}

		return $mime;
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

		$html = '';

		if( apply_filters( 'ssp_show_global_feed_tag', true ) ) {
			$html = '<link rel="alternate" type="application/rss+xml" title="' . __( 'Podcast RSS feed', 'seriously-simple-podcasting' ) . '" href="' . esc_url( $feed_url ) . '" />';
		}

		// Check if this is a series taxonomy archive and display series-specific RSS feed tag
		$current_obj = get_queried_object();
		if( isset( $current_obj->taxonomy ) && 'series' == $current_obj->taxonomy && isset( $current_obj->slug ) && $current_obj->slug ) {

			if( apply_filters( 'ssp_show_series_feed_tag', true, $current_obj->slug ) ) {

				if ( get_option( 'permalink_structure' ) ) {
					$series_feed_url = $feed_url . '/' . $current_obj->slug;
				} else {
					$series_feed_url = $feed_url . '&podcast_series=' . $current_obj->slug;
				}

				$html .= "\n" . '<link rel="alternate" type="application/rss+xml" title="' . sprintf( __( '%s RSS feed', 'seriously-simple-podcasting' ), $current_obj->name ) . '" href="' . esc_url( $series_feed_url ) . '" />';

			}

		}

		echo "\n" . apply_filters( 'ssp_rss_meta_tag', $html ) . "\n\n";
	}

	/**
	 * Set RSS content type for podcast feed
	 *
	 * @param  string $content_type Current content type
	 * @param  string $type         Type of feed
	 * @return string               Updated content type
	 */
	public function feed_content_type ( $content_type = '', $type = '' ) {

		if( SSP_CPT_PODCAST == $type ) {
			$content_type = 'text/xml';
		}

		return $content_type;
	}

	public function load_localisation () {
		load_plugin_textdomain( 'seriously-simple-podcasting', false, basename( $this->dir ) . '/languages/' );
	}

	/**
	 * Show single podcast episode with specified content items
	 * This is used in the SeriouslySimplePodcasting\Widgets\Single_Episode widget
	 * as well as the SeriouslySimplePodcasting\ShortCodes\Podcast_Episode shortcode
	 *
	 * @param  integer $episode_id    ID of episode post
	 * @param  array   $content_items Ordered array of content items to display
	 * @return string                 HTML of episode with specified content items
	 */
	public function podcast_episode ( $episode_id = 0, $content_items = array( 'title', 'player', 'details' ), $context = '', $style = 'standard' ) {
		global $post, $episode_context;

		if ( ! $episode_id || ! is_array( $content_items ) || empty( $content_items ) ) {
			return '';
		}

		// Get episode object
		$episode = get_post( $episode_id );

		if ( ! $episode || is_wp_error( $episode ) ) {
			return '';
		}

		$html = '<div class="podcast-episode episode-' . esc_attr( $episode_id ) . '">' . "\n";

		// Setup post data for episode post object
		$post = $episode;
		setup_postdata( $post );

		$episode_context = $context;

		foreach ( $content_items as $item ) {

			switch ( $item ) {

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

					$html .= '<div id="podcast_player_' . $episode_id . '" class="podcast_player">' . $this->media_player( $file, $episode_id, $style, 'podcast_episode' ) . '</div>' . "\n";
					break;

				case 'details':
				case 'meta':
					$html .= $this->episode_meta_details( $episode_id, $episode_context );
					break;

				case 'image':
					$html .= get_the_post_thumbnail( $episode_id, apply_filters( 'ssp_frontend_context_thumbnail_size', 'thumbnail' ) );
					break;

			}
		}

		// Reset post data after fetching episode details
		wp_reset_postdata();

		$html .= '</div>' . "\n";

		return $html;
	}
}
