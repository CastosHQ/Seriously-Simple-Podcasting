<?php

namespace SeriouslySimplePodcasting\Rest;

use SeriouslySimplePodcasting\Controllers\Episode_Controller;
use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

/**
 * Extending the WP REST API for Seriously Simple Podcasting
 *
 * @package Seriously Simple Podcasting
 * @since 1.19.12
 */

class Rest_Api_Controller {

	/**
	 * @var $version string Plugin version (semvar)
	 */
	private $version;

	/**
	 * @var $file plugin file
	 */
	private $file;

	/**
	 * Gets the default podcast data
	 *
	 * @return array Podcast
	 */
	private function get_default_podcast_settings() {
		$series_id = 0;
		$podcast   = array();

		$podcast['title']           = get_option( 'ss_podcasting_data_title', get_bloginfo( 'name' ) );
		$description                = get_option( 'ss_podcasting_data_description', get_bloginfo( 'description' ) );
		$podcast['description']     = mb_substr( wp_strip_all_tags( $description ), 0, 3999 );
		$podcast['language']        = get_option( 'ss_podcasting_data_language', get_bloginfo( 'language' ) );
		$podcast['copyright']       = get_option( 'ss_podcasting_data_copyright', '&#xA9; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) );
		$podcast['subtitle']        = get_option( 'ss_podcasting_data_subtitle', get_bloginfo( 'description' ) );
		$podcast['author']          = get_option( 'ss_podcasting_data_author', get_bloginfo( 'name' ) );
		$podcast['owner_name']      = get_option( 'ss_podcasting_data_owner_name', get_bloginfo( 'name' ) );
		$podcast['owner_email']     = get_option( 'ss_podcasting_data_owner_email', get_bloginfo( 'admin_email' ) );
		$podcast['explicit_option'] = get_option( 'ss_podcasting_explicit', '' );
		$podcast['complete_option'] = get_option( 'ss_podcasting_complete', '' );
		$podcast['image']           = get_option( 'ss_podcasting_data_image', '' );
		$podcast['category1']       = ssp_get_feed_category_output( 1, $series_id );
		$podcast['category2']       = ssp_get_feed_category_output( 2, $series_id );
		$podcast['category3']       = ssp_get_feed_category_output( 3, $series_id );

		return $podcast;
	}

	/**
	 * Constructor
	 *
	 * @param    string $file Plugin file
	 * @param    string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		$this->file    = $file;
		$this->version = $version;

		// Register custom REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		add_action( 'rest_api_init', array( $this, 'create_api_series_fields' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_episode_images' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_player_images' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_audio_download_link' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_audio_player_link' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_audio_player' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_episode_data' ) );

		$post_types = ssp_post_types( true, false );
		foreach ( $post_types as $post_type ) {
			add_filter( 'rest_prepare_' . $post_type, array( $this, 'rest_prepare_excerpt' ), 10, 3 );
		}

	}

	/**
	 * Prepares the Post excerpt for any podcast post types in the REST API
	 *
	 * @param $response
	 * @param $post
	 * @param $request
	 *
	 * @return mixed
	 */
	public function rest_prepare_excerpt( $response, $post, $request ) {
		if ( 'excerpt' === $response->data['excerpt']['rendered'] ) {
			$response->data['excerpt']['rendered'] = get_the_excerpt();
		}

		return $response;
	}

	/**
	 * Registers the custom REST API routes
	 */
	public function register_rest_routes() {
		/**
		 * Setting up custom route for podcast
		 */
		register_rest_route(
			'ssp/v1',
			'/podcast',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rest_podcast' ),
				'permission_callback' => '__return_true',
			)
		);

		/**
		 * Setting up custom route for podcast
		 */
		register_rest_route(
			'ssp/v1',
			'/podcast_update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_rest_podcast' ),
				'permission_callback' => '__return_true',
			)
		);

		/**
		 * Setting up custom route for episodes
		 */
		$controller = new Episodes_Controller();
		$controller->register_routes();

		/**
		 * Setting up custom route for the wp_audio_shortcode for a podcast
		 */
		register_rest_route(
			'ssp/v1',
			'/audio_player',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_episode_audio_player' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	/**
	 * Gets the podcast data for the podcast route
	 *
	 * @return array $podcast Podcast data
	 */
	public function get_rest_podcast() {
		$podcast = $this->get_default_podcast_settings();

		return $podcast;
	}

	/**
	 * Updates a podcast after a Castos import
	 *
	 * @return array
	 */
	public function update_rest_podcast() {
		$response = array(
			'updated' => 'false',
			'message' => '',
		);

		$ssp_podcast_api_token = ( isset( $_POST['ssp_podcast_api_token'] ) ? filter_var( $_POST['ssp_podcast_api_token'], FILTER_SANITIZE_STRING ) : '' );
		if ( empty( $ssp_podcast_api_token ) ) {
			$response['message'] = 'No Castos API token set';
			return $response;
		}

		$podmotor_api_token = get_option( 'ss_podcasting_podmotor_account_api_token', '' );
		if ( $ssp_podcast_api_token !== $podmotor_api_token ) {
			$response['message'] = 'Castos API invalid';
			return $response;
		}

		if ( ! isset( $_FILES['ssp_podcast_file'] ) ) {
			$response['message'] = 'No podcast file exists';
			return $response;
		}

		$episode_data_array = array_map( 'str_getcsv', file( $_FILES['ssp_podcast_file']['tmp_name'] ) );
		foreach ( $episode_data_array as $episode_data ) {
			// add check to make sure url being added is valid first
			update_post_meta( $episode_data[0], 'podmotor_episode_id', $episode_data[1] );
			update_post_meta( $episode_data[0], 'audio_file', $episode_data[2] );
		}
		ssp_email_podcasts_imported();

		$response['updated'] = 'true';
		$response['message'] = 'Podcast updated successfully';

		return $response;
	}

	/**
	 * Gets the podcast audio player code from wp_audio_shortcode, or null if ssp_podcast_id is not a valid podcast
	 *
	 * @return array $podcast Podcast data
	 */
	public function get_episode_audio_player() {
		$podcast_id = ( isset( $_GET['ssp_podcast_id'] ) ? filter_var( $_GET['ssp_podcast_id'], FILTER_SANITIZE_STRING ) : '' );
		global $ss_podcasting;
		$file   = $ss_podcasting->episode_controller->get_episode_player_link( $podcast_id );
		$params = array( 'src' => $file, 'preload' => 'none' );

		return array(
			'id'           => $podcast_id,
			'file'         => $file,
			'audio_player' => wp_audio_shortcode( $params )
		);
	}

	/**
	 * Add additional fields to series taxonomy
	 */
	public function create_api_series_fields() {
		$podcast_fields = array_keys( $this->get_default_podcast_settings() );

		foreach ( $podcast_fields as $podcast_field ) {
			register_rest_field(
				'series',
				$podcast_field,
				array(
					'get_callback' => array( $this, 'series_get_field_value' ),
				)
			);
		}
	}

	/**
	 * Get series settings data to add to series fields added above
	 *
	 * @param $data
	 * @param $field_name
	 * @param $request
	 *
	 * @return mixed|void
	 */
	public function series_get_field_value( $data, $field_name, $request ) {
		$podcast            = $this->get_default_podcast_settings();
		$field_value        = $podcast[ $field_name ];
		$series_id          = $data['id'];
		$series_field_value = get_option( 'ss_podcasting_data_' . $field_name . '_' . $series_id, '' );
		if ( $series_field_value ) {
			$field_value = $series_field_value;
		}

		return $field_value;
	}

	/**
	 * Add the featured image field to all Podcast post types
	 */
	public function register_rest_episode_images() {
		register_rest_field(
			ssp_post_types(),
			'episode_featured_image',
			array(
				'get_callback'    => array( $this, 'get_rest_featured_image' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Add the player image field to all Podcast post types
	 */
	public function register_rest_player_images() {
		register_rest_field(
			ssp_post_types(),
			'episode_player_image',
			array(
				'get_callback'    => array( $this, 'get_rest_player_image' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Add the audio file tracking url field to all Podcast post types
	 */
	public function register_rest_audio_download_link() {
		register_rest_field(
			ssp_post_types(),
			'download_link',
			array(
				'get_callback'    => array( $this, 'get_rest_audio_download_link' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Add the audio file tracking url field to all Podcast post types
	 */
	public function register_rest_audio_player_link() {
		register_rest_field(
			ssp_post_types(),
			'player_link',
			array(
				'get_callback'    => array( $this, 'get_rest_audio_player_link' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Add the audio player code to all Podcast post types
	 */
	public function register_rest_audio_player() {
		register_rest_field(
			ssp_post_types(),
			'audio_player',
			array(
				'get_callback'    => array( $this, 'get_rest_audio_player' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Add the audio player code to all Podcast post types
	 */
	public function register_rest_episode_data() {
		register_rest_field(
			ssp_post_types(),
			'episode_data',
			array(
				'get_callback'    => array( $this, 'get_episode_player_data' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Get the featured image for valid Podcast post types
	 * Call back for the register_rest_episode_images method
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 *
	 * @return bool
	 */
	public function get_rest_featured_image( $object, $field_name, $request ) {
		if ( ! empty( $object['featured_media'] ) ) {
			$img = wp_get_attachment_image_src( $object['featured_media'], 'app-thumb' );

			return $img[0];
		}

		return false;
	}

	/**
	 * Get the player image for valid Podcast post types
	 * Call back for the register_rest_episode_images method
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 *
	 * @return bool
	 */
	public function get_rest_player_image( $object, $field_name, $request ) {
		if ( ! empty( $object['id'] ) ) {
			$episode_id         = $object['id'];
			global $ss_podcasting;
			$album_art          = $ss_podcasting->episode_controller->get_album_art( $episode_id );

			return $album_art['src'];
		}

		return false;
	}

	/**
	 * Get the audio_file for valid Podcast post types
	 * Call back for the register_rest_episode_audio_file method
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 *
	 * return bool|string
	 */
	public function get_rest_audio_download_link( $object, $field_name, $request ) {
		if ( ! empty( $object['meta']['audio_file'] ) ) {
			global $ss_podcasting;
			$download_link      = $ss_podcasting->episode_controller->get_episode_download_link( $object['id'] );

			return $download_link;
		}

		return false;
	}

	/**
	 * Get the audio_file for valid Podcast post types
	 * Call back for the register_rest_episode_player_file method
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 *
	 * @return bool
	 */
	public function get_rest_audio_player_link( $object, $field_name, $request ) {
		if ( ! empty( $object['meta']['audio_file'] ) ) {
			global $ss_podcasting;
			$player_link      = $ss_podcasting->episode_controller->get_episode_player_link( $object['id'] );

			return $player_link;
		}

		return false;
	}

	/**
	 * Return the Audio Player html
	 *
	 * @param $object
	 * @param $field_name
	 * @param $request
	 *
	 * @return bool|string|void
	 */
	public function get_rest_audio_player( $object, $field_name, $request ) {
		if ( ! empty( $object['meta']['audio_file'] ) ) {
			$player_style = get_option( 'ss_podcasting_player_style', 'standard' );
			if ( empty( $player_style ) ) {
				$player_style = 'standard';
			}

			if ( 'standard' !== $player_style ) {
				return;
			}
			global $ss_podcasting;
			$file   = $ss_podcasting->episode_controller->get_episode_player_link( $object['id'] );
			$params = array( 'src' => $file, 'preload' => 'none' );
			return wp_audio_shortcode( $params );
		}

		return false;
	}

	/**
	 * Adds the Episode player data to the episodes route for the Castos Player block
	 *
	 * @return array
	 */
	public function get_episode_player_data( $object, $field_name, $request ) {
		if ( ! empty( $object['id'] ) ) {
			$options_handler    = new Options_Handler();
			$episode_repository = new Episode_Repository();
			$episode_id         = $object['id'];
			$player_data        = array(
				'playerMode'    => get_option( 'ss_podcasting_player_mode', 'dark' ),
				'subscribeUrls' => $options_handler->get_subscribe_urls( $episode_id, 'rest_api' ),
				'rssFeedUrl'    => $episode_repository->get_feed_url( $episode_id ),
				'embedCode'     => preg_replace( '/(\r?\n){2,}/', '\n\n', get_post_embed_html( 500, 350, $episode_id ) ),
			);

			return $player_data;
		}

		return false;
	}

}
