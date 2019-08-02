<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed Controller Class
 *
 * @author      Jonathan Bossenger
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.20.7
 */
class Feed_Controller extends Controller {

	public $feed_file_name = 'feed-podcast.php';

	/**
	 * Admin_Controller constructor.
	 *
	 * @param $file main plugin file
	 * @param $version plugin version
	 */
	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );

		$this->bootstrap();
	}

	/**
	 * Set up all hooks and filters for this class
	 */
	public function bootstrap() {
		// Register podcast feed.
		add_action( 'init', array( $this, 'add_feed' ), 11 );

		// Handle v1.x feed URL as well as feed URLs for default permalinks.
		add_action( 'init', array( $this, 'redirect_old_feed' ), 11 );
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
	 * // @todo move all logic from feed template file to this method, at the very least
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
	 */
	public function load_feed_template() {
		status_header( 200 );

		$user_template_file = apply_filters( 'ssp_feed_template_file', trailingslashit( get_stylesheet_directory() ) . $this->feed_file_name );

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_before_feed' );

		// Load user feed template if it exists, otherwise use plugin template
		if ( file_exists( $user_template_file ) ) {
			require $user_template_file;
		} else {
			require $this->template_path . $this->feed_file_name;
		}

		// Any functions hooked in here must NOT output any data or else feed will break
		do_action( 'ssp_after_feed' );
	}
}
