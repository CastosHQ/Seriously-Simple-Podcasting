<?php

namespace SeriouslySimplePodcasting\Controllers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Handlers\Feed_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed Controller Class
 *
 * @author      Jonathan Bossenger, Serhiy Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.20.7
 */
class Feed_Controller {

	use Useful_Variables;

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
	 * @var Renderer
	 * */
	protected $renderer;

	/**
	 * Admin_Controller constructor.
	 *
	 * @param Feed_Handler $feed_handler
	 * @param Renderer $renderer
	 */
	public function __construct( $feed_handler, $renderer ) {
		$this->init_useful_variables();

		$this->feed_handler = $feed_handler;
		$this->renderer     = $renderer;

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

		// Fix WP core bug - redirect canonical
		add_filter( 'redirect_canonical', array( $this, 'fix_canonical_feed_url' ) );
	}

	/**
	 * Fix WP core bug - canonical feed URLs.
	 * Examples: https://site.com/feed/podcast/feed/podcast/, https://site.com/feed/podcast/my-podcast/feed/podcast/
	 *
	 * @param string $redirect_url
	 *
	 * @return string
	 */
	public function fix_canonical_feed_url( $redirect_url ) {
		if ( ! is_feed() ) {
			return $redirect_url;
		}

		$feed_slug = $this->get_feed_slug();
		$search    = sprintf( '#\/feed\/%s\/(.*?)feed\/%s(.*)#', $feed_slug, $feed_slug );
		$replace   = sprintf( '/feed/%s/$1', $feed_slug );

		return preg_replace( $search, $replace, $redirect_url );
	}

	/**
	 * Register podcast feed
	 * @return void
	 */
	public function add_feed() {
		add_feed( $this->get_feed_slug(), array( $this, 'render_podcast_feed' ) );
	}

	/**
	 * @return string
	 */
	protected function get_feed_slug() {
		return apply_filters( 'ssp_feed_slug', $this->token );
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

		echo $this->get_podcast_feed();

		exit;
	}

	/**
	 * Loads the feed template.
	 *
	 * @return string
	 */
	public function get_podcast_feed( $series_id = null ) {

		do_action( 'ssp_before_feed' );

		$this->feed_handler->suppress_errors();

		if ( $series_id ) {
			$term = get_term_by( 'id', $series_id, ssp_series_taxonomy() );
			$series_slug = $term->slug;
		} else {
			$series_slug = $this->feed_handler->get_series_slug();
			$series_id = $this->feed_handler->get_series_id( $series_slug );
		}

		if ( ! $series_slug || ! $series_id ) {
			$this->feed_handler->redirect_default_feed();
		}

		$this->feed_handler->maybe_redirect_to_the_new_feed( $series_id );

		$this->feed_handler->maybe_protect_unauthorized_access( $series_id );

		$this->feed_handler->maybe_protect_private_feed( $series_id );

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

		$itunes_explicit = $is_explicit ? 'true' : 'false';

		$googleplay_explicit = $is_explicit ? 'Yes' : 'No';

		$complete = $this->feed_handler->get_complete( $series_id );

		$image = $this->feed_handler->get_feed_image( $series_id );

		// Podcast category and subcategory (all levels) - can be filtered with `ssp_feed_category_output`
		$category1 = ssp_get_feed_category_output( 1, $series_id );
		$category2 = ssp_get_feed_category_output( 2, $series_id );
		$category3 = ssp_get_feed_category_output( 3, $series_id );

		// Get iTunes Type
		$itunes_type = ssp_get_option( 'consume_order', '', $series_id );

		// Get turbo setting
		$turbo = $this->feed_handler->get_turbo( $series_id );

		// Get media prefix setting
		$media_prefix = $this->feed_handler->get_media_prefix( $series_id );

		$is_excerpt_mode = $this->feed_handler->is_excerpt_mode( $series_id );

		$locked = $this->feed_handler->get_locked( $series_id );

		$funding = $this->feed_handler->get_funding( $series_id );

		$podcast_value = $this->feed_handler->get_podcast_value( $series_id );

		$guid = $this->feed_handler->get_guid( $series_slug );

		$pub_date_type = $this->feed_handler->get_pub_date_type( $series_id );

		$stylesheet_url = $this->feed_handler->get_stylesheet_url();

		$qry = $this->feed_handler->get_feed_query( $series_slug, $exclude_series, $pub_date_type );

		$feed_link = $this->feed_handler->get_feed_link( $series_id );

		$home_url = $this->home_url;

		$this->send_feed_headers();

		// Load user feed template if it exists, otherwise use plugin template
		$user_template_file = apply_filters( 'ssp_feed_template_file', trailingslashit( get_stylesheet_directory() ) . $this->feed_file_name );

		$path = file_exists( $user_template_file ) ? $user_template_file : $this->template_path . $this->feed_file_name;

		$feed_controller = $this;

		$feed_data = apply_filters( 'ssp_feed_data', get_defined_vars() );

		$feed = $this->renderer->fetch( $path, $feed_data );

		do_action( 'ssp_after_feed' );

		return apply_filters( 'ssp_podcast_feed', $feed, $feed_data );
	}

	/**
	 * @param \WP_Query $qry
	 *
	 * @param array $args {
	 *     Array of the arguments for the feed item.
	 *
	 *  @type int $author Episode author.
	 *  @type bool $is_excerpt_mode Use excerpt mode or not.
	 *  @type string $pub_date_type Date type.
	 *  @type int|null $turbo_post_count Feed items counter.
	 *  @type string $media_prefix Prefix for Podtrac, Chartable, and other tracking services.
	 * }
	 *
	 * @return string
	 */
	public function fetch_feed_item( $qry, $args ) {

		$author           = isset( $args['author'] ) ? $args['author'] : '';
		$is_excerpt_mode  = isset( $args['is_excerpt_mode'] ) ? $args['is_excerpt_mode'] : '';
		$pub_date_type    = isset( $args['pub_date_type'] ) ? $args['pub_date_type'] : '';
		$turbo_post_count = isset( $args['turbo_post_count'] ) ? $args['turbo_post_count'] : 0;
		$media_prefix     = isset( $args['media_prefix'] ) ? $args['media_prefix'] : '';

		$qry->the_post();

		$ss_podcasting = ssp_frontend_controller();

		$post_id = get_the_ID();

		// Audio file
		$audio_file = $ss_podcasting->get_enclosure( $post_id );

		if ( get_option( 'permalink_structure' ) ) {
			$enclosure = $ss_podcasting->get_episode_download_link( $post_id );
		} else {
			$enclosure = $audio_file;
		}

		$enclosure = apply_filters( 'ssp_feed_item_enclosure', $enclosure, $post_id );

		if ( ! empty( $media_prefix ) ) {
			$enclosure = parse_episode_url_with_media_prefix( $enclosure, $media_prefix );
		}

		// If there is no enclosure then go no further
		if ( ! isset( $enclosure ) || ! $enclosure ) {
			return '';
		}

		$episode_image = $this->feed_handler->get_feed_item_image( $post_id );
		$duration      = $this->feed_handler->get_feed_item_duration( $post_id );
		$size          = $this->feed_handler->get_feed_item_file_size( $post_id );
		$mime_type     = $this->feed_handler->get_feed_item_mime_type( $audio_file, $post_id );
		$ep_explicit   = $this->feed_handler->get_feed_item_explicit_flag( $post_id );

		if ( $ep_explicit && $ep_explicit == 'on' ) {
			$itunes_explicit_flag     = 'true';
			$googleplay_explicit_flag = 'Yes';
		} else {
			$itunes_explicit_flag     = 'false';
			$googleplay_explicit_flag = 'No';
		}

		// Episode block flag
		$ep_block = get_post_meta( $post_id, 'block', true );
		$ep_block = apply_filters( 'ssp_feed_item_block', $ep_block, $post_id );
		$block_flag = ( $ep_block && $ep_block == 'on' ) ? 'yes' : 'no';

		// Episode author.
		$author = apply_filters( 'ssp_feed_item_author', $author, $post_id );

		$description = $this->feed_handler->get_feed_item_description( $post_id, $is_excerpt_mode, $turbo_post_count );

		// Clean up after shortcodes in content and excerpts.
		if ( $post_id !== get_the_ID() ) {
			$qry->reset_postdata();
		}

		$itunes_summary  = $this->feed_handler->get_feed_item_itunes_summary( $description, $post_id );
		$gp_description  = $this->feed_handler->get_feed_item_google_play_description( $description, $post_id );
		$itunes_subtitle = $this->feed_handler->get_feed_item_itunes_subtitle( $description, $post_id );
		$pub_date        = $this->feed_handler->get_feed_item_pub_date( $pub_date_type, $post_id );

		$itunes_enabled    = get_option( 'ss_podcasting_itunes_fields_enabled' );
		$is_itunes_enabled = $itunes_enabled && $itunes_enabled == 'on';
		// New iTunes WWDC 2017 Tags.
		$itunes_episode_type   = $is_itunes_enabled ? get_post_meta( $post_id, 'itunes_episode_type', true ) : '';
		$itunes_title          = $is_itunes_enabled ? get_post_meta( $post_id, 'itunes_title', true ) : '';
		$itunes_episode_number = $is_itunes_enabled ? get_post_meta( $post_id, 'itunes_episode_number', true ) : '';
		$itunes_season_number  = $is_itunes_enabled ? get_post_meta( $post_id, 'itunes_season_number', true ) : '';

		$title = esc_html( get_the_title_rss() );

		$feed_item_path = apply_filters( 'ssp_feed_item_path', '/feed/feed-item' );

		$args = apply_filters( 'ssp_feed_item_args', compact(
			'title', 'pub_date', 'author', 'description', 'itunes_subtitle',
			'itunes_episode_type', 'itunes_title', 'itunes_episode_number', 'itunes_season_number',
			'turbo_post_count', 'enclosure', 'size', 'mime_type', 'turbo_post_count', 'itunes_summary',
			'episode_image', 'itunes_explicit_flag', 'block_flag', 'duration', 'gp_description',
			'googleplay_explicit_flag'
		), $post_id );

		return $this->renderer->fetch( $feed_item_path, $args );
	}

	/**
	 * Sends RSS content type and charset headers
	 */
	public function send_feed_headers() {
		status_header( 200 );
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
