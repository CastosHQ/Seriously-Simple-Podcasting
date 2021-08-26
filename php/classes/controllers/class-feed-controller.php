<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Feed_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed Controller Class
 *
 * @author      Jonathan Bossenger, Sergey Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.20.7
 */
class Feed_Controller extends Controller {

	/**
	 * File name of the feed template
	 * @var string
	 * */
	public $feed_file_name = 'feed-podcast.php';

	/**
	 * Feed handler
	 * @var Feed_Handler
	 * */
	protected $feed_handler;

	/**
	 * Admin_Controller constructor.
	 *
	 * @param string $file main plugin file
	 * @param string $version plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->feed_handler = new Feed_Handler();

		$this->bootstrap();
	}

	/**
	 * Set up all hooks and filters for this class
	 */
	public function bootstrap() {
		// Register podcast feed.
		add_action( 'init', array( $this, 'add_feed' ), 11 );

		// Handle v1.x feed URL as well as feed URLs for default permalinks.
		add_action( 'init', array( $this, 'redirect_old_feed' ), 12 );

		// Sanitize the podcast image
		add_filter( 'ssp_feed_image', array( $this, 'sanitize_image' ) );
	}

	/**
	 * Register podcast feed
	 * @return void
	 */
	public function add_feed() {
		$feed_slug = apply_filters( 'ssp_feed_slug', $this->token );
		add_feed( $feed_slug, array( $this, 'render_podcast_feed' ) );
	}

	/**
	 * Redirect feed URLs created prior to v1.8 to ensure backwards compatibility
	 * @return void
	 */
	public function redirect_old_feed() {
		if ( isset( $_GET['feed'] ) && in_array( $_GET['feed'], array( $this->token, 'itunes' ) ) ) {
			$this->render_podcast_feed();
			exit;
		}
	}

	/**
	 * Render the podcast feed
	 * @return void
	 */
	public function render_podcast_feed() {
		global $wp_query;

		// Prevent 404 on feed
		$wp_query->is_404 = false;

		/**
		 * Fix the is_feed attribute on the old feed url structure
		 */
		if ( ! $wp_query->is_feed ) {
			$wp_query->is_feed = true;
		}

		$this->load_feed_template();

		exit;

	}

	/**
	 * Loads the feed template file
	 * @todo: Further refactoring - use renderer, get_feed_data() function
	 */
	public function load_feed_template() {
		status_header( 200 );

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_before_feed' );

		$this->feed_handler->suppress_errors();

		$give_access = $this->feed_handler->has_access();

		$podcast_series = $this->feed_handler->get_podcast_series();

		$series_id = $this->feed_handler->get_series_id( $podcast_series );

		// Send 401 status and display no access message if access has been denied
		$this->feed_handler->protect_unauthorized_access( $give_access, $series_id );

		// If this is a series specific feed, then check if we need to redirect
		$this->feed_handler->maybe_redirect_series( $series_id );

		// If redirect is on, get new feed URL and redirect if setting was changed more than 48 hours ago
		$this->feed_handler->maybe_redirect_to_the_new_feed();

		$exclude_series = $this->feed_handler->get_excluded_series( $series_id );

		$title = $this->feed_handler->get_podcast_title( $series_id );

		$description = $this->feed_handler->get_podcast_description( $series_id );

		$language = $this->feed_handler->get_podcast_language( $series_id );

		$copyright = $this->feed_handler->get_podcast_copyright( $series_id );

		$subtitle = $this->feed_handler->get_podcast_subtitle( $series_id );

		$author = $this->feed_handler->get_podcast_author( $series_id );

		$owner_name = $this->feed_handler->get_podcast_owner_name( $series_id );

		$owner_email = $this->feed_handler->get_podcast_owner_email( $series_id );

		$is_explicit = $this->feed_handler->is_explicit( $series_id );

		$itunes_explicit = $is_explicit ? 'yes' : 'clean';

		$googleplay_explicit = $is_explicit ? 'Yes' : 'No';

		$complete = $this->feed_handler->get_complete( $series_id );

		$image = $this->feed_handler->get_feed_image( $series_id );

		// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
		$category1 = ssp_get_feed_category_output( 1, $series_id );
		$category2 = ssp_get_feed_category_output( 2, $series_id );
		$category3 = ssp_get_feed_category_output( 3, $series_id );

		// Get iTunes Type
		$itunes_type = get_option( 'ss_podcasting_consume_order' . ( $series_id > 0 ? '_' . $series_id : null ) );

		// Get turbo setting
		$turbo = $this->feed_handler->get_turbo( $series_id );

		// Get media prefix setting
		$media_prefix = $this->feed_handler->get_media_prefix( $series_id );

		$episode_description_uses_excerpt = $this->feed_handler->is_excerpt_mode( $series_id );

		$locked = $this->feed_handler->get_locked( $series_id );

		$funding = $this->feed_handler->get_funding( $series_id );

		$guid = $this->feed_handler->get_guid( $podcast_series );

		$stylesheet_url = $this->feed_handler->get_stylesheet_url();

		$this->send_feed_headers();

		// Load user feed template if it exists, otherwise use plugin template
		$user_template_file = apply_filters( 'ssp_feed_template_file', trailingslashit( get_stylesheet_directory() ) . $this->feed_file_name );

		$path = file_exists( $user_template_file ) ? $user_template_file : $this->template_path . $this->feed_file_name;

		global $ss_podcasting;

		require $path;

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_after_feed' );
	}

	/**
	 * Sends RSS content type and charset headers
	 */
	public function send_feed_headers(){
		header( 'Content-Type: ' . feed_content_type( SSP_CPT_PODCAST ) . '; charset=' . get_option( 'blog_charset' ), true );
	}


	/**
	 * Sanitizes the image, if it's not valid - change it to empty string
	 *
	 * @param string $image_url
	 *
	 * @return string
	 */
	public function sanitize_image( $image_url ) {
		if ( ! ssp_is_feed_image_valid( $image_url ) ) {
			$image_url = '';
		}

		return $image_url;
	}
}
