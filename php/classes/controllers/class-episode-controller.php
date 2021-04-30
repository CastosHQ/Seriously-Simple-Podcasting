<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Controllers\Players_Controller;
use WP_Query;

/**
 * SSP Episode Controller
 *
 * @package Seriously Simple Podcasting
 */
class Episode_Controller extends Controller {


	public $renderer = null;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->renderer = new Renderer();
		$this->init();
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_recent_episodes_assets' ) );
	}

	public function load_recent_episodes_assets() {
		wp_register_style( 'ssp-recent-episodes', $this->assets_url . 'css/recent-episodes.css', array(), $this->version );
		wp_enqueue_style( 'ssp-recent-episodes' );
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

		return apply_filters( 'ssp_episode_download_link', esc_url( $link ), $episode_id, $file );
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
	 * Returns the no album art image
	 *
	 * @return array
	 */
	private function get_no_album_art_image_array() {
		$src    = SSP_PLUGIN_URL . 'assets/images/no-album-art.png';
		$width  = 300;
		$height = 300;

		return compact( 'src', 'width', 'height' );
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
	 *
	 * @since 1.19.4
	 */
	public function get_album_art( $episode_id = false ) {

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
			$image_data_array = ssp_get_attachment_image_src( $thumb_id );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * Option 2: if the episode has a featured image that is square, then use that
		 */
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( ! empty( $thumb_id ) ) {
			$image_data_array = ssp_get_attachment_image_src( $thumb_id );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * Option 3: if the episode belongs to a series, which has an image that is square, then use that
		 */
		$series_id    = false;
		$series = get_the_terms( $episode_id, 'series' );

		/**
		 * In some instances, this could return a WP_Error object
		 */
		if ( ! is_wp_error( $series ) && $series ) {
			$series_id = ( isset( $series[0] ) ) ? $series[0]->term_id : false;
		}

		if ( $series_id ) {
			$series_image_attachment_id = get_term_meta( $series_id, $this->token . '_series_image_settings', true );
		}

		if ( ! empty( $series_image_attachment_id ) ) {
			$image_data_array = ssp_get_attachment_image_src( $series_image_attachment_id );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * Option 4: if the feed settings have an image that is square, then use that
		 */
		$feed_image = get_option( 'ss_podcasting_data_image', false );
		if ( $feed_image ) {
			$feed_image_attachment_id = attachment_url_to_postid( $feed_image );
			$image_data_array         = ssp_get_attachment_image_src( $feed_image_attachment_id );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return $image_data_array;
			}
		}

		/**
		 * None of the above passed, return the no-album-art image
		 */
		return $this->get_no_album_art_image_array();
	}

	/**
	 * Get Episode List
	 *
	 * @param array $episode_ids , array of episode ids being loaded into the player
	 * @param $include_title
	 * @param $include_excerpt
	 * @param $include_player
	 * @param $include_subscribe_links
	 *
	 * @return array [ $src, $width, $height ]
	 *
	 * @since 2.2.3
	 */
	public function episode_list( $episode_ids, $include_title = false, $include_excerpt = false, $include_player = false, $include_subscribe_links = false ) {
		$episodes = null;

		if ( ! empty( $episode_ids ) ) {
			$args = array(
				'include'        => array_values( $episode_ids ),
				'post_type'      => SSP_CPT_PODCAST,
				'numberposts'    => -1
			);

			$episodes = get_posts( $args );
		}

		$episodes_template_data = array(
			'episodes'       => $episodes,
		);

		$episodes_template_data = apply_filters( 'episode_list_data', $episodes_template_data );

		return $this->renderer->render( $episodes_template_data, 'episodes/episode-list' );
	}

	/**
	 * Render a list of all episodes, based on settings sent
	 * @todo, currently used for Elementor, update to use for the Block editor as well.
	 *
	 * @param $settings
	 *
	 * @return mixed|void
	 */
	public function render_episodes($settings) {
		$player       = new Players_Controller( $this->file, $this->version );
		$args  = array(
			'post_type'      => SSP_CPT_PODCAST,
			'posts_per_page' => 10,
		);

		$episodes               = new WP_Query( $args );
		$episodes_template_data = array(
			'player' => $player,
			'episodes' => $episodes,
			'settings' => $settings,
		);

		$episodes_template_data = apply_filters( 'episode_list_data', $episodes_template_data );

		return $this->renderer->render( $episodes_template_data, 'episodes/all-episodes-list' );
	}

	/**
	 * Gather a list of the last 3 episodes for the Elementor Recent Episodes Widget
	 *
	 * @return mixed|void
	 */
	public function recent_episodes() {
		$args = array(
			'posts_per_page' => 3,
			'offset'         => 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish' ),
		);

		$episodes_query      = new WP_Query( $args );
		$template_data = array(
			'episodes' => $episodes_query->get_posts(),
		);

		return apply_filters( 'recent_episodes_template_data', $template_data );
	}

	/**
	 * Render the template for the Elementor Recent Episodes Widget
	 *
	 * @return mixed|void
	 */
	public function render_recent_episodes() {
		$template_data = $this->recent_episodes();

		return $this->renderer->render( $template_data, 'episodes/recent-episodes' );
	}

}
