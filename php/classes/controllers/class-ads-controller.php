<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ads Controller
 *
 * @package Seriously Simple Podcasting
 * @author Serhiy Zakharchenko
 * @since 2.24.0
 */
class Ads_Controller {

	/**
	 * @var Castos_Handler $castos_handler
	 * */
	protected $castos_handler;

	public function __construct( $castos_handler ) {
		$this->castos_handler = $castos_handler;

		add_action( 'ssp_feed_fields', array( $this, 'maybe_show_ads_settings' ) );
		add_filter( 'ssp_episode_enclosure', array( $this, 'maybe_use_ads' ), 10, 2 );
	}

	/**
	 * Show ads settings if SSP is connected to Castos
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function maybe_show_ads_settings( $fields ) {
		if ( ! ssp_is_connected_to_castos() ) {
			return $fields;
		}

		$show_ads = array(
			'id'          => 'enable_ads',
			'label'       => 'Enable Ads',
			'description' => __( 'Enable Ads', 'seriously-simple-podcasting' ),
			'type'        => 'checkbox',
			'default'     => 'off',
			'callback'    => 'wp_strip_all_tags',
		);


		if ( ! $this->is_ads_enabled_in_castos() ) {
			$show_ads['type'] = 'info';
			$show_ads['description'] = __( 'Enable Ads in your Castos account first to get set up', 'seriously-simple-podcasting' );
		}

		$fields['show_ads'] = $show_ads;

		return $fields;
	}

	/**
	 * Checks if ads is enabled for this Castos podcast
	 *
	 * @return bool
	 */
	protected function is_ads_enabled_in_castos() {

		$podcasts = $this->castos_handler->get_podcasts();

		$series_id = $this->get_current_feed_series_id();

		if ( empty( $podcasts['data']['podcast_list'] ) ) {
			return false;
		}

		foreach ( $podcasts['data']['podcast_list'] as $podcast ) {
			if ( $series_id == $podcast['series_id'] ) {
				return ! empty( $podcast['ads_enabled'] );
			}
		}

		return false;
	}

	/**
	 * Gets current series ID for the feed details page ( Podcasting -> Settings -> Feed Details )
	 *
	 * @return int
	 */
	protected function get_current_feed_series_id() {
		$podcast_slug = filter_input( INPUT_GET, 'feed-series' );
		if ( ! $podcast_slug ) {
			return 0;
		}

		$podcast = get_term_by( 'slug', $podcast_slug, 'series' );

		return isset( $podcast->term_id ) ? $podcast->term_id : 0;
	}

	/**
	 * Replaces current enclosure with ads enclosure
	 *
	 * @param $enclosure
	 * @param $episode_id
	 *
	 * @return mixed|string
	 * @throws \Exception
	 */
	public function maybe_use_ads( $enclosure, $episode_id ) {

		// If SSP is not connected to Castos, return early.
		if ( ! ssp_is_connected_to_castos() ) {
			return $enclosure;
		}

		// Check that Ads are enabled for this podcast.
		if ( ! $this->is_ads_enabled( $episode_id ) ) {
			return $enclosure;
		}

		// Ensure that ads are enabled for the current episode in Castos.
		$file_data = $this->castos_handler->get_episode_file_data( $episode_id );

		if ( ! $file_data->success || ! $file_data->ads_enabled ) {
			return $enclosure;
		}

		return $file_data->url;
	}

	/**
	 * Checks if ads enabled for current episode podcast
	 *
	 * @param int $episode_id
	 *
	 * @return bool
	 */
	protected function is_ads_enabled( $episode_id ) {
		$podcast_id = $this->get_episode_podcast_id( $episode_id );

		return (bool) ssp_get_option( 'enable_ads', false, $podcast_id );
	}

	/**
	 * Gets episode podcast ID, 0 for default one
	 *
	 * @param $episode_id
	 *
	 * @return int
	 */
	protected function get_episode_podcast_id( $episode_id ){
		$episode_podcasts = ssp_get_episode_podcasts( $episode_id );
		return ! empty( $episode_podcasts[0] ) ? $episode_podcasts[0]->term_id : 0;
	}
}
