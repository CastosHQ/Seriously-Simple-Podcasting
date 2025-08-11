<?php
/**
 * Ads controller class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Entities\Episode_File_Data;
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

	const ENABLE_ADS_OPTION = 'enable_ads';

	/**
	 * Castos handler instance.
	 *
	 * @var Castos_Handler
	 */
	protected $castos_handler;

	/**
	 * Ads_Controller constructor.
	 *
	 * @param Castos_Handler $castos_handler Handler for Castos API interactions.
	 */
	public function __construct( $castos_handler ) {
		$this->castos_handler = $castos_handler;

		add_action( 'ssp_feed_fields', array( $this, 'maybe_show_ads_settings' ) );
		add_filter( 'ssp_enclosure_url', array( $this, 'maybe_use_ads' ), 10, 2 );
		add_action( 'ssp_check_ads', array( $this, 'check_ads_settings' ) );
	}

	/**
	 * Check ads settings by WP Cron once a day.
	 */
	public function check_ads_settings() {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		$podcasts = $this->castos_handler->get_podcast_items();
		foreach ( $podcasts as $podcast ) {
			if ( ! $podcast->ads_enabled && $this->is_series_ads_enabled( $podcast->series_id ) ) {
				$this->disable_series_ads( $podcast->series_id );
			}
		}
	}


	/**
	 * Show ads settings if SSP is connected to Castos.
	 *
	 * @param array $fields Feed settings fields.
	 *
	 * @return array
	 */
	public function maybe_show_ads_settings( $fields ) {

		$show_ads = array(
			'id'    => 'enable_ads',
			'label' => 'Enable Castos Ads',
			'type'  => 'info',
		);

		if ( ! ssp_is_connected_to_castos() ) {
			/* translators: %s is the URL to learn more about Castos advertising. */
			$show_ads['description'] = sprintf(
				__(
					'Monetize your podcast today when you partner with Castos for podcast hosting. <a target="_blank" href="%s">Learn more.</a>',
					'seriously-simple-podcasting'
				),
				'https://castos.com/advertising/'
			);

			$fields['show_ads'] = $show_ads;

			return $fields;
		}

		if ( ! $this->is_ads_enabled_in_castos() ) {
			/* translators: %s is the URL to learn more about enabling Castos ads. */
			$show_ads['description'] = sprintf(
				__(
					'Enable Ads in your Castos account first to get set up. <a target="_blank" href="%s">Learn more.</a>',
					'seriously-simple-podcasting'
				),
				'https://support.castos.com/article/300-enable-castos-ads'
			);

			$fields['show_ads'] = $show_ads;

			return $fields;
		}

		$show_ads = array_merge(
			$show_ads,
			array(
				'description' => __( 'Enable Castos Ads.', 'seriously-simple-podcasting' ),
				'type'        => 'checkbox',
				'default'     => 'off',
				'callback'    => 'wp_strip_all_tags',
			)
		);

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

		if ( ! is_array( $podcasts ) || empty( $podcasts['data']['podcast_list'] ) ) {
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

		$podcast = get_term_by( 'slug', $podcast_slug, ssp_series_taxonomy() );

		return isset( $podcast->term_id ) ? $podcast->term_id : 0;
	}

	/**
	 * Replaces current enclosure with ads enclosure.
	 *
	 * @param string $enclosure  Original enclosure URL.
	 * @param int    $episode_id Episode ID.
	 *
	 * @return string Modified or original enclosure URL.
	 * @throws \Exception When episode file data retrieval fails.
	 */
	public function maybe_use_ads( $enclosure, $episode_id ) {

		// If SSP is not connected to Castos, return early.
		if ( ! ssp_is_connected_to_castos() ) {
			return $enclosure;
		}

		// Check that Ads are enabled for this podcast.
		if ( ! $this->is_episode_ads_enabled( $episode_id ) ) {
			return $enclosure;
		}

		$file_data = $this->get_episode_file_data( $episode_id );

		if ( ! $file_data->success || ! $file_data->ads_enabled ) {
			return $enclosure;
		}

		return $file_data->url;
	}

	/**
	 * Gets episode file data from Castos API.
	 *
	 * @param int $episode_id Episode ID.
	 *
	 * @return Episode_File_Data Episode file data object.
	 * @throws \Exception When API call fails.
	 */
	protected function get_episode_file_data( $episode_id ) {
		$transient = 'ssp_episode_ads_file_' . $episode_id;

		$file_data = get_transient( $transient );

		if ( $file_data ) {
			return $file_data;
		}

		// Ensure that ads are enabled for the current episode in Castos.
		$castos_episode_id = get_post_meta( $episode_id, 'podmotor_episode_id', true );
		$file_data         = $this->castos_handler->get_episode_file_data( $castos_episode_id );
		set_transient( $transient, $file_data, DAY_IN_SECONDS );

		return $file_data;
	}

	/**
	 * Checks if ads enabled for current episode podcast.
	 *
	 * @param int $episode_id Episode ID.
	 *
	 * @return bool True if ads are enabled, false otherwise.
	 */
	protected function is_episode_ads_enabled( $episode_id ) {
		$series_id = $this->get_episode_series_id( $episode_id );

		return $this->is_series_ads_enabled( $series_id );
	}

	/**
	 * Checks if ads enabled for series.
	 *
	 * @param int $series_id Series ID.
	 *
	 * @return bool True if ads are enabled, false otherwise.
	 */
	protected function is_series_ads_enabled( $series_id ) {
		return (bool) ssp_get_option( self::ENABLE_ADS_OPTION, false, $series_id );
	}


	/**
	 * Disables ads for a series.
	 *
	 * @param int $series_id Series ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	protected function disable_series_ads( $series_id ) {
		return ssp_update_option( self::ENABLE_ADS_OPTION, '', $series_id );
	}

	/**
	 * Gets episode podcast ID, 0 for default one.
	 *
	 * @param int $episode_id Episode ID.
	 *
	 * @return int Series ID or 0 for default.
	 */
	protected function get_episode_series_id( $episode_id ) {
		$episode_podcasts = ssp_get_episode_podcasts( $episode_id );
		return ! empty( $episode_podcasts[0] ) ? $episode_podcasts[0]->term_id : 0;
	}
}
