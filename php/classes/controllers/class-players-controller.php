<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Players_Controller class
 *
 * @author      Danilo Radovic
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       2.3
 */
class Players_Controller extends Controller {

	public $renderer = null;
	public $episode_controller;
	public $options_handler;
	public $episode_repository;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->renderer           = new Renderer();
		$this->episode_controller = new Episode_Controller( $file, $version );
		$this->options_handler    = new Options_Handler();
		$this->episode_repository = new Episode_Repository();
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
	 * @param int $id
	 *
	 * @return array
	 */
	public function get_html_player_data( $id ) {
		$audio_file = get_post_meta( $id, 'audio_file', true );
		if ( empty( $audio_file ) ) {
			return apply_filters( 'ssp_html_player_data', array() );
		}

		/**
		 * Get the episode (post) object
		 * If the id passed is empty or 0, get_post will return the current post
		 */
		$episode          = get_post( $id );
		$episode_duration = get_post_meta( $id, 'duration', true );
		$episode_url      = get_post_permalink( $id );
		$audio_file       = $this->episode_controller->get_episode_player_link( $id );
		$album_art        = $this->episode_controller->get_album_art( $id );
		$podcast_title    = get_option( 'ss_podcasting_data_title' );
		$feed_url         = $this->episode_repository->get_feed_url( $id );
		$embed_code       = preg_replace( '/(\r?\n){2,}/', '\n\n', get_post_embed_html( 500, 350, $episode ) );
		$player_mode      = get_option( 'ss_podcasting_player_mode', 'dark' );
		$show_subscribe_button  = 'on' === get_option( 'ss_podcasting_subscribe_button_enabled', 'on' );
		$show_share_button      = 'on' === get_option( 'ss_podcasting_share_button_enabled', 'on' );
		$subscribe_links  = $this->options_handler->get_subscribe_urls( $id, 'subscribe_buttons' );

		// set any other info
		$template_data = array(
			'episode'               => $episode,
			'episode_id'            => $episode->ID,
			'duration'              => $episode_duration,
			'episode_url'           => $episode_url,
			'audio_file'            => $audio_file,
			'album_art'             => $album_art,
			'podcast_title'         => $podcast_title,
			'feed_url'              => $feed_url,
			'subscribe_links'       => $subscribe_links,
			'embed_code'            => $embed_code,
			'player_mode'           => $player_mode,
			'show_subscribe_button' => $show_subscribe_button,
			'show_share_button'     => $show_share_button,
		);

		$template_data = apply_filters( 'ssp_html_player_data', $template_data );

		return $template_data;
	}

	/**
	 * Renders the HTML5 player, based on the attributes sent to the method
	 * If the player assets are registered but not already enqueued, this will enqueue them
	 *
	 * @param $episode_id
	 *
	 * @return string
	 */
	public function render_html_player( $episode_id ) {
		$template_data = $this->get_html_player_data( $episode_id );
		if ( ! array_key_exists( 'audio_file', $template_data ) ) {
			return '';
		}

		if ( wp_script_is( 'ssp-castos-player', 'registered' ) && ! wp_script_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_script( 'ssp-castos-player' );
		}
		if ( wp_style_is( 'ssp-castos-player', 'registered' ) && ! wp_style_is( 'ssp-castos-player', 'enqueued' ) ) {
			wp_enqueue_style( 'ssp-castos-player' );
		}

		return $this->renderer->render( $template_data, 'players/castos-player' );
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

		return $this->renderer->render( $template_data, 'players/subscribe-buttons' );
	}

	public function media_player( $id ) {
		/**
		 * Get the episode (post) object
		 * If the id passed is empty or 0, get_post will return the current post
		 */
		$episode  = get_post( $id );
		$src_file = $this->episode_controller->get_episode_player_link( $id );
		$params   = array(
			'src'     => $src_file,
			'preload' => 'none',
		);

		$audio_player = wp_audio_shortcode( $params );
		$template_data = array(
			'episode'      => $episode,
			'audio_player' => $audio_player,
		);

		return $template_data;
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

		return $this->renderer->render( $template_data, 'players/media-player' );
	}
}
