<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Renderers\Renderer;
use SeriouslySimplePodcasting\Controllers\Episode_Controller;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 *
 * @author      Hugh Lashbrooke
 * @category    Class
 * @package     SeriouslySimplePodcasting/Controllers
 * @since       1.0
 */
class Players_Controller extends Controller {

	public $renderer = null;
	public $episode_controller;

	public function __construct( $file, $version ) {
		parent::__construct( $file, $version );
		$this->render = new Renderer();
		$this->episode_controller = new Episode_Controller($file, $version );
		add_action( 'init', array( $this, 'regsiter_shortcodes' ), 1 );
	}

	public function regsiter_shortcodes() {
		add_shortcode('elementor_html_player', array($this, 'elementor_html_player'));
	}

	public function elementor_html_player($attributes) {
		return $this->html_player($attributes['id']);
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
	 * @param int $id
	 *
	 * @return string
	 */
	public function html_player($id) {
		$episode = get_post($id);
		$episodeDuration = get_post_meta($id, 'duration', true);
		$audioFile = get_post_meta($id, 'audio_file', true);
		$albumArt = $this->episode_controller->get_album_art($id);
		$podcastTitle = get_option('ss_podcasting_data_title');

		$subscribeLinks = $this->subscribe_links($id);

		$feedUrl = $this->get_feed_url();
		// set any other info
		$templateData = array(
			'episode' => $episode,
			'duration' => $episodeDuration,
			'audioFile' => $audioFile,
			'albumArt' => $albumArt,
			'podcastTitle' => $podcastTitle,
			'feedUrl' => $feedUrl,
			'itunes' => $subscribeLinks['itunes'],
			'stitcher' => $subscribeLinks['stitcher'],
			'spotify' => $subscribeLinks['spotify'],
			'googlePlay' => $subscribeLinks['googlePlay']
		);

		$templateData = apply_filters( 'html_player_data', $templateData );

		// fix this later (return part) according to elementor integration demands
		return $this->render->render($templateData, 'players/html-player');
	}

	/**
	 * Return media player for a given podcast (episode) id.
	 * @param int $id
	 *
	 * @return string
	 */
	public function media_player($id) {
		// get src file
		$srcFile = get_post_meta($id, 'audio_file', true);
		$params = array(
			'src' => $srcFile,
			'preload' => 'none'
		);

		$mediaPlayer = wp_audio_shortcode($params);

		return $mediaPlayer;
	}

	public function subscribe_links($id) {

		$seriesId = $this->get_series_id($id);

		if($seriesId) {
			$itunes = get_option("ss_podcasting_itunes_url_{$seriesId}");
			$stitcher = get_option("ss_podcasting_stitcher_url_{$seriesId}");
			$spotify = get_option("ss_podcasting_spotify_url_{$seriesId}");
			$googlePlay = get_option("ss_podcasting_google_play_url_{$seriesId}");
		} else {
			$itunes = get_option("ss_podcasting_itunes_url");
			$stitcher = get_option("ss_podcasting_stitcher_url");
			$spotify = get_option("ss_podcasting_spotify_url");
			$googlePlay = get_option("ss_podcasting_google_play_url");
		}

		$subscribeLinks = array(
			'itunes' => $itunes,
			'stitcher' => $stitcher,
			'spotify' => $spotify,
			'googlePlay' => $googlePlay
		);

		return $subscribeLinks;
	}

	public function get_series_id($episode_id) {
		$series_id = false;
		$series = get_the_terms( $episode_id, 'series' );

		if ( $series ) {
			$series_id = ( ! empty( $series ) && isset( $series[0] ) ) ? $series[0]->term_id : false;
		}

		return $series_id;
	}

}
