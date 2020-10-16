<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Options_Handler;
use SeriouslySimplePodcasting\Renderers\Renderer;

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

	/**
	 * Return feed url.
	 * @return string
	 * @todo we might need to take into account the series feed url here
	 *
	 */
	protected function get_feed_url() {
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

		return $feed_url;
	}

	/**
	 * Returns the subscribe links for a specific series by id
	 *
	 * @param $id
	 *
	 * @return array[]
	 * @todo see if this can be replaced by the Options_Handler::get_subscribe_urls method
	 *
	 */

	protected function get_subscribe_links( $id ) {

		$series_id = $this->get_series_id( $id );

		if ( $series_id ) {
			$itunes      = get_option( 'ss_podcasting_itunes_url_' . $series_id );
			$stitcher    = get_option( 'ss_podcasting_stitcher_url_' . $series_id );
			$spotify     = get_option( 'ss_podcasting_spotify_url_' . $series_id );
			$google_play = get_option( 'ss_podcasting_google_play_url_' . $series_id );
		} else {
			$itunes      = get_option( 'ss_podcasting_itunes_url' );
			$stitcher    = get_option( 'ss_podcasting_stitcher_url' );
			$spotify     = get_option( 'ss_podcasting_spotify_url' );
			$google_play = get_option( 'ss_podcasting_google_play_url' );
		}

		$subscribe_links = array(
			'itunes'      => [ 'title' => 'iTunes', 'link' => $itunes ],
			'stitcher'    => [ 'title' => 'Stitcher', 'link' => $stitcher ],
			'spotify'     => [ 'title' => 'Spotify', 'link' => $spotify ],
			'google_play' => [ 'title' => 'Google Play', 'link' => $google_play ],
		);

		return $subscribe_links;
	}

	/**
	 * Return a series id for an episode
	 *
	 * @param $episode_id
	 *
	 * @return int
	 * @todo check if there is a global function for this, and use it.
	 *
	 */
	protected function get_series_id( $episode_id ) {
		$series_id = 0;
		$series    = get_the_terms( $episode_id, 'series' );

		if ( $series ) {
			$series_id = ( ! empty( $series ) && isset( $series[0] ) ) ? $series[0]->term_id : 0;
		}

		return $series_id;
	}

	/**
	 * Get the latest episode ID for a player
	 *
	 * @return int
	 */
	public function get_latest_episode_id() {
		$args     = array(
			'fields'         => array( 'post_title, id' ),
			'posts_per_page' => 1,
			'post_type'      => ssp_post_types( true ),
			'post_status'    => array( 'publish', 'draft', 'future' ),
		);
		$episodes = get_posts( $args );
		if ( empty( $episodes ) ) {
			return 0;
		}
		$episode = $episodes[0];

		return $episode->ID;
	}

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->renderer           = new Renderer();
		$this->episode_controller = new Episode_Controller( $file, $version );
		$this->options_handler    = new Options_Handler();
		$this->init();
	}

	public function init() {
		/**
		 * Only load player assets once the wp_enqueue_scripts hook is triggered
		 * @todo ideally only when the player is loaded...
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'load_player_assets' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_subscribe_buttons_assets' ) );

	}

	/**
	 * Loads the HTML5 player CSS and JavaScript
	 */
	public function load_player_assets() {
		wp_register_style( 'castos-player-v1', $this->assets_url . 'css/castos-player-v1.css', array(), $this->version );
		wp_enqueue_style( 'castos-player-v1' );
		wp_register_script( 'castos-player-v1', $this->assets_url . 'js/castos-player-v1.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'castos-player-v1' );
	}

	/**
	 * Loads the Subscribe Buttons CSS
	 */
	public function load_subscribe_buttons_assets() {
		wp_register_style( 'ssp-subscribe-buttons', $this->assets_url . 'css/subscribe-buttons.css', array(), $this->version );
		wp_enqueue_style( 'ssp-subscribe-buttons' );
	}

	/**
	 * Sets up the template data for the HTML5 player, based on the episode id passed.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function html_player( $id ) {
		/**
		 * Get the episode (post) object
		 * If the id passed is empty or 0, get_post will return the current post
		 */
		$episode          = get_post( $id );
		$episode_duration = get_post_meta( $episode->ID, 'duration', true );
		$episode_url      = get_post_permalink( $id );
		$audio_file       = get_post_meta( $episode->ID, 'audio_file', true );
		$album_art        = $this->episode_controller->get_album_art( $episode->ID );
		$podcast_title    = get_option( 'ss_podcasting_data_title' );
		$feed_url         = $this->get_feed_url();
		$subscribe_links  = $this->get_subscribe_links( $episode->ID );
		$embed_code       = preg_replace( '/(\r?\n){2,}/', '\n\n', get_post_embed_html( 500, 350, $episode ) );
		$player_mode      = get_option( 'ss_podcasting_player_mode', 'dark' );

		// set any other info
		$templateData = array(
			'episode'      => $episode,
			'episode_id'   => $episode->ID,
			'duration'     => $episode_duration,
			'episodeUrl'   => $episode_url,
			'audioFile'    => $audio_file,
			'albumArt'     => $album_art,
			'podcastTitle' => $podcast_title,
			'feedUrl'      => $feed_url,
			'itunes'       => $subscribe_links['itunes'],
			'stitcher'     => $subscribe_links['stitcher'],
			'spotify'      => $subscribe_links['spotify'],
			'googlePlay'   => $subscribe_links['google_play'],
			'embed_code'   => $embed_code,
			'player_mode'  => $player_mode,
		);

		$template_data = apply_filters( 'ssp_html_player_data', $templateData );

		return $template_data;
	}

	/**
	 * Renders the HTML5 player, based on the attributes sent to the method
	 *
	 *
	 * @param $episode_id
	 *
	 * @return mixed|void
	 */
	public function render_html_player( $episode_id ) {
		$template_data = $this->html_player( $episode_id );

		return $this->renderer->render( $template_data, 'players/castos-player-v1' );
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
		$src_file = get_post_meta( $episode->ID, 'audio_file', true );
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
