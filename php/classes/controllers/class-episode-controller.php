<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;
use WP_Query;

/**
 * SSP Episode Controller
 *
 * @package Seriously Simple Podcasting
 *
 * @deprecated Almost all episode-related functions now in Episode_Repository or Frontend_Controller.
 * So lets just get rid of this class.
 * @todo: move functions to Episode_Repository, rest - to Frontend Controller
 */
class Episode_Controller {

	use Useful_Variables;

	/**
	 * @var Renderer
	 * */
	public $renderer;

	/**
	 * @var Episode_Repository
	 * */
	public $episode_repository;

	/**
	 * @param Renderer $renderer
	 */
	public function __construct( $renderer ) {
		$this->init_useful_variables();

		$this->renderer = $renderer;
		$this->episode_repository = new Episode_Repository();
		$this->init_assets();
	}

	protected function init_assets() {
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
	 *
	 * Todo: move it to Episode_Repository
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
	 *
	 * Todo: move it to Episode_Repository
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
				return $image_data_array;
			}
		}

		/**
		 * Option 2: if the episode belongs to a series, which has an image that is square, then use that
		 */
		$series_id  = $this->episode_repository->get_episode_series_id( $episode_id );

		if ( $series_id ) {
			$series_image_attachment_id = get_term_meta( $series_id, $this->token . '_series_image_settings', true );
		}

		if ( ! empty( $series_image_attachment_id ) ) {
			$image_data_array = ssp_get_attachment_image_src( $series_image_attachment_id, $size );
			if ( ssp_is_image_square( $image_data_array ) ) {
				return $image_data_array;
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
				return $image_data_array;
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
				return $image_data_array;
			}
		}

		/**
		 * Option 5: None of the above passed, return the no-album-art image
		 */
		return $this->get_no_album_art_image_array();
	}

	/**
	 * Get featured image src.
	 *
	 * @param int $episode_id ID of the episode.
	 *
	 * @return array|null [ $src, $width, $height ]
	 *
	 * @since 2.9.9
	 */
	public function get_featured_image_src( $episode_id, $size = 'full' ) {
		$thumb_id = get_post_thumbnail_id( $episode_id );
		if ( empty( $thumb_id ) ) {
			return null;
		}
		return ssp_get_attachment_image_src( $thumb_id, $size );
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

		return $this->renderer->render_deprecated( $episodes_template_data, 'episodes/episode-list' );
	}

	/**
	 * Render a list of all episodes, based on settings sent
	 * @todo, currently used for Elementor, update to use for the Block editor as well.
	 *
	 * @param $settings
	 *
	 * @return string
	 */
	public function render_episodes( $settings ) {
		global $ss_podcasting;
		$player = $ss_podcasting->players_controller;
		$paged  = get_query_var( 'paged' );

		$args = array(
			'post_type'      => SSP_CPT_PODCAST,
			'posts_per_page' => 10,
			'paged'          => $paged ?: 1,
		);

		$episodes               = new WP_Query( $args );
		$episodes_template_data = array(
			'player'   => $player,
			'episodes' => $episodes,
			'settings' => $settings,
		);

		$episodes_template_data = apply_filters( 'episode_list_data', $episodes_template_data );

		return $this->renderer->fetch( 'episodes/all-episodes-list', $episodes_template_data );
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
	 * }
	 *
	 * @return \WP_Post[]
	 */
	public function get_recent_episodes( $args = array() ) {
		$defaults = array(
			'episodes_number' => 3,
			'episode_types'   => 'all_podcast_types',
			'order_by'        => 'published',
		);

		$args = wp_parse_args( $args, $defaults );

		$post_types = ( 'all_podcast_types' === $args['episode_types'] ) ? ssp_post_types( true ) : SSP_CPT_PODCAST;

		$query = array(
			'posts_per_page' => $args['episodes_number'],
			'post_type'      => $post_types,
			'post_status'    => array( 'publish' ),
		);

		if ( 'recorded' === $args['order_by'] ) {
			$query['orderby']  = 'meta_value';
			$query['meta_key'] = 'date_recorded';
			$query['order']    = 'DESC';
		}

		$episodes_query = new WP_Query( $query );

		return $episodes_query->get_posts();
	}

	/**
	 * Render the template for the Elementor Recent Episodes Widget
	 *
	 * @return mixed|void
	 */
	public function render_recent_episodes( $template_data ) {
		$template_data['episodes'] = $this->get_recent_episodes( $template_data );
		$template_data             = apply_filters( 'recent_episodes_template_data', $template_data );

		return $this->renderer->fetch( 'episodes/recent-episodes', $template_data );
	}

}
