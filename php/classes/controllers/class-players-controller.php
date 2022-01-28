<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;
use SeriouslySimplePodcasting\Traits\Useful_Variables;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Players_Controller class
 *
 * @author      Danilo Radovic, Sergey Zakharchenko
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.3
 */
class Players_Controller {

	use Useful_Variables;

	public $renderer = null;
	public $options_handler;

	public function __construct() {
		$this->renderer           = new Renderer();
		$this->options_handler    = new Options_Handler();
	}

	/**
	 * @return Episode_Controller
	 */
	protected function episode_controller(){
		return ssp_episode_controller();
	}


	/**
	 * Todo: move it to Episode_Repository
	 * */
	public function get_ajax_playlist_items() {
		$atts      = json_decode( filter_input( INPUT_GET, 'atts' ), ARRAY_A );
		$page      = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT );
		$nonce     = filter_input( INPUT_GET, 'nonce' );
		$player_id = filter_input( INPUT_GET, 'player_id' );

		if ( ! $atts || ! $page || ! wp_verify_nonce($nonce, 'ssp_castos_player_' . $player_id) ) {
			wp_send_json_error();
		}

		$episode_repository = ssp_episode_controller()->episode_repository;

		$episodes = $episode_repository->get_playlist_episodes( array_merge( $atts, compact( 'page' ) ) );
		$items    = array();

		$allowed_keys = array(
			'episode_id',
			'album_art',
			'podcast_title',
			'title',
			'date',
			'duration',
			'excerpt',
			'audio_file'
		);

		foreach ( $episodes as $episode ) {
			$player_data = $this->get_player_data( $episode->ID );
			$items[] = array_intersect_key( $player_data, array_flip( $allowed_keys ) );
		}

		return $items;
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
		return $this->episode_controller()->episode_repository->get_player_data( $id, $current_post, $skip_empty_audio );
	}

	/**
	 * Renders the HTML5 player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param int $episode_id Post ID, -1 for current, 0 for latest
	 *
	 * @return string
	 */
	public function render_html_player( $episode_id, $skip_empty_audio = true ) {
		$template_data = $this->get_player_data( $episode_id, null, false );
		if ( $skip_empty_audio && ! array_key_exists( 'audio_file', $template_data ) ) {
			return '';
		}

		$this->enqueue_player_assets();

		return $this->renderer->render_deprecated( $template_data, 'players/castos-player' );
	}


	/**
	 * Renders the Playlist player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param $episodes
	 * @param $atts
	 *
	 * @return string
	 */
	public function render_playlist_player( $episodes, $atts ) {
		if ( empty( $episodes ) ) {
			return '';
		}

		// For the case if multiple players are rendered on the same page, we need to generate the player id;
		$player_id = wp_rand();

		wp_localize_script( 'ssp-castos-player', 'ssp_castos_player_' . $player_id, array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'atts'     => $atts,
			'nonce'    => wp_create_nonce( 'ssp_castos_player_' . $player_id ),
		) );
		$this->enqueue_player_assets();

		$template_data = $this->get_player_data( $episodes[0]->ID, get_post() );

		global $wp;
		$template_data['current_url'] = home_url( $wp->request );
		$template_data['player_id']   = $player_id;

		$template_data['player_mode'] = $atts['style'];

		foreach ( $episodes as $episode ) {
			$template_data['playlist'][] = $this->get_player_data( $episode->ID );
		}

		return $this->renderer->render_deprecated( $template_data, 'players/castos-player' );
	}

	/**
	 * Renders the Playlist player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param array $tracks
	 * @param array $atts
	 *
	 * @return string
	 */
	public function render_playlist_compact_player( $tracks, $atts, $width, $height ) {

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '';
		}

		$this->enqueue_player_assets();

		$data = array(
			'type'         => $atts['type'],
			// don't pass strings to JSON, will be truthy in JS
			'tracklist'    => wp_validate_boolean( $atts['tracklist'] ),
			'tracknumbers' => wp_validate_boolean( $atts['tracknumbers'] ),
			'images'       => wp_validate_boolean( $atts['images'] ),
			'artists'      => false,
			'tracks'       => $tracks,
		);

		$safe_type  = esc_attr( $atts['type'] );
		$safe_style = esc_attr( $atts['style'] );

		static $instance = 0;
		$instance ++;

		if ( 1 === $instance ) {
			/* This hook is defined in wp-includes/media.php */
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		}

		return $this->renderer->render_deprecated(
			compact('safe_style', 'safe_type', 'data', 'width', 'height'),
			'players/playlist-compact-player'
		);
	}


	public function enqueue_player_assets(){
		if ( wp_script_is( 'ssp-castos-player', 'registered' ) && ! wp_script_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_script( 'ssp-castos-player' );
		}
		if ( wp_style_is( 'ssp-castos-player', 'registered' ) && ! wp_style_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_style( 'ssp-castos-player' );
		}
	}


	/**
	 * Renders the Subscribe Buttons, based on the attributes sent to the method
	 *
	 * @param $episode_id
	 *
	 * @return mixed|void
	 */
	public function render_subscribe_buttons( $episode_id ) {
		$subscribe_urls                  = $this->options_handler->get_subscribe_urls( $episode_id, 'subscribe_buttons' );
		$template_data['subscribe_urls'] = $subscribe_urls;

		if ( isset( $template_data['subscribe_urls']['itunes_url'] ) ) {
			$template_data['subscribe_urls']['itunes_url']['label'] = 'Apple Podcast';
			$template_data['subscribe_urls']['itunes_url']['icon']  = 'apple-podcasts.png';
		}

		if ( isset( $template_data['subscribe_urls']['google_play_url'] ) ) {
			$template_data['subscribe_urls']['google_play_url']['label'] = 'Google Podcast';
			$template_data['subscribe_urls']['google_play_url']['icon']  = 'google-podcasts.png';
		}

		$template_data = apply_filters( 'ssp_subscribe_buttons_data', $template_data );

		return $this->renderer->render_deprecated( $template_data, 'players/subscribe-buttons' );
	}

	public function media_player( $id ) {
		/**
		 * Get the episode (post) object
		 * If the id passed is empty or 0, get_post will return the current post
		 */
		$episode  = get_post( $id );
		$src_file = $this->episode_controller()->get_episode_player_link( $id );
		$params   = array(
			'src'     => $src_file,
			'preload' => 'none',
		);

		$audio_player = wp_audio_shortcode( $params );
		return array(
			'episode'      => $episode,
			'audio_player' => $audio_player,
		);
	}

	/**
	 * Return media player for a given podcast (episode) id.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function render_media_player( $id ) {
		$template_data = $this->media_player( $id );

		return $this->renderer->render_deprecated( $template_data, 'players/media-player' );
	}

	/**
	 * @param array $atts
	 *
	 * @return int[]|\WP_Post[]
	 * @deprecated Use Episode_Repository::get_playlist_episodes()
	 */
	public function get_playlist_episodes( $atts ) {
		return $this->episode_controller()->episode_repository->get_playlist_episodes( $atts );
	}

	/**
	 * Get the latest episode ID for a player
	 *
	 * @return int
	 * @deprecated Use Episode_Repository::get_latest_episode_id()
	 */
	public function get_latest_episode_id() {
		return $this->episode_controller()->episode_repository->get_latest_episode_id();
	}
}
