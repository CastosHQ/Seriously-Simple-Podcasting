<?php
/**
 * Extending the WP REST API for Seriously Simple Podcasting
 *
 * @package Seriously Simple Podcasting
 * @since 1.19.12
 */

class SSP_WP_REST_API {

	/**
	 * @var $version string Plugin version (semvar)
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param    string $version Plugin version
	 */
	public function __construct( $version ) {
		$this->version = $version;

		// Register custom REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

	}

	/**
	 * Registers the custom REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'ssp/v1', '/podcast', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_rest_podcast' ),
		) );
	}

	/**
	 * Gets the default podcast data
	 *
	 * @return array Podcast
	 */
	public function get_rest_podcast() {

		$podcast = array();

		$podcast['title']               = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
		$description                    = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
		$podcast['podcast_description'] = mb_substr( strip_tags( $description ), 0, 3999 );
		$podcast['language']            = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
		$podcast['copyright']           = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		$podcast['subtitle']            = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
		$podcast['author']              = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
		$podcast['owner_name']          = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
		$podcast['owner_email']         = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
		$podcast['explicit_option']     = get_option( 'ss_podcasting_explicit', '' );
		$podcast['complete_option']     = get_option( 'ss_podcasting_complete', '' );
		$podcast['image']               = get_option( 'ss_podcasting_data_image', '' );
		$podcast['category1']           = ssp_get_feed_category_output( 1 );
		$podcast['category2']           = ssp_get_feed_category_output( 2 );
		$podcast['category3']           = ssp_get_feed_category_output( 3 );

		return $podcast;
	}

}