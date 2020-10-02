<?php

namespace SeriouslySimplePodcasting\Controllers;

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

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->renderer           = new Renderer();
		$this->episode_controller = new Episode_Controller( $file, $version );
		$this->init();
	}

	public function init() {
		/**
		 * Only register shortcodes once the init hook is triggered
		 */
		add_action( 'init', array( $this, 'register_shortcodes' ), 1 );
		/**
		 * Only load player assets once the wp_enqueue_scripts hook is triggered
		 * @todo ideally only when the player is loaded...
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'load_player_assets' ) );
	}

	public function load_player_assets() {
		wp_register_style( 'castos-player-v1', $this->assets_url . 'css/castos-player-v1.css', array(), $this->version );
		wp_enqueue_style( 'castos-player-v1' );
		wp_register_script( 'castos-player-v1', $this->assets_url . 'js/castos-player-v1.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'castos-player-v1' );
	}

	public function register_shortcodes() {
		add_shortcode( 'elementor_html_player', array( $this, 'elementor_html_player' ) );
		add_shortcode( 'elementor_subscribe_links', array( $this, 'elementor_subscribe_links' ) );
	}

	public function elementor_html_player( $attributes ) {
		$template_data = $this->html_player( $attributes['id'] );

		return $this->renderer->render( $template_data, 'players/castos-player-v1' );
	}

	public function elementor_subscribe_links( $attributes ) {
		$template_data = $this->get_subscribe_links( $attributes['id'] );

		return $this->renderer->render( $template_data, 'players/subscribe-links' );
	}

	/**
	 * Return feed url.
	 *
	 * @return string
	 */
	public function get_feed_url() {
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
	 * Return html player for a given podcast (episode) id.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function html_player( $id ) {
		$episode          = get_post( $id );
		$episode_duration = get_post_meta( $id, 'duration', true );
		$audio_file       = get_post_meta( $id, 'audio_file', true );
		$album_art        = $this->episode_controller->get_album_art( $id );
		$podcast_title    = get_option( 'ss_podcasting_data_title' );
		$episode_id       = $id;

		$subscribe_links = $this->get_subscribe_links( $id );

		$feed_url = $this->get_feed_url();
		// set any other info
		$templateData = array(
			'episode'      => $episode,
			'episode_id'   => $episode_id,
			'duration'     => $episode_duration,
			'audioFile'    => $audio_file,
			'albumArt'     => $album_art,
			'podcastTitle' => $podcast_title,
			'feedUrl'      => $feed_url,
			'itunes'       => $subscribe_links['itunes'],
			'stitcher'     => $subscribe_links['stitcher'],
			'spotify'      => $subscribe_links['spotify'],
			'googlePlay'   => $subscribe_links['googlePlay']
		);

		$template_data = apply_filters( 'ssp_html_player_data', $templateData );

		return $template_data;
	}

	/**
	 * Return media player for a given podcast (episode) id.
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function media_player( $id ) {
		// get src file
		$src_file = get_post_meta( $id, 'audio_file', true );
		$params   = array(
			'src'     => $src_file,
			'preload' => 'none'
		);

		$media_player = wp_audio_shortcode( $params );

		return $media_player;
	}

	public function get_subscribe_links( $id ) {

		$series_id = $this->get_series_id( $id );

		if ( $seriesId ) {
			$itunes     = get_option( "ss_podcasting_itunes_url_{$series_id}" );
			$stitcher   = get_option( "ss_podcasting_stitcher_url_{$series_id}" );
			$spotify    = get_option( "ss_podcasting_spotify_url_{$series_id}" );
			$googlePlay = get_option( "ss_podcasting_google_play_url_{$series_id}" );
		} else {
			$itunes      = get_option( "ss_podcasting_itunes_url" );
			$stitcher    = get_option( "ss_podcasting_stitcher_url" );
			$spotify     = get_option( "ss_podcasting_spotify_url" );
			$google_play = get_option( "ss_podcasting_google_play_url" );
		}

		$subscribe_links = array(
			'itunes'     => [ 'title' => 'iTunes', 'link' => $itunes ],
			'stitcher'   => [ 'title' => 'Stitcher', 'link' => $stitcher ],
			'spotify'    => [ 'title' => 'Spotify', 'link' => $spotify ],
			'googlePlay' => [ 'title' => 'GooglePlay', 'link' => $google_play ]
		);

		return $subscribe_links;
	}

	public function subscribe_links( $id ) {
		$template_data = $this->get_subscribe_links( $id );

		$template_data = apply_filters( 'ssp_subscribe_links_data', $template_data );

		return $this->renderer->render( $template_data, 'players/subscribe-links.php' );
	}

	public function get_series_id( $episode_id ) {
		$series_id = 0;
		$series    = get_the_terms( $episode_id, 'series' );

		if ( $series ) {
			$series_id = ( ! empty( $series ) && isset( $series[0] ) ) ? $series[0]->term_id : 0;
		}

		return $series_id;
	}

}
