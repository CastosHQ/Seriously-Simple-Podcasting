<?php
/**
 * Passthrough controller class file.
 *
 * Manages dynamic file URL resolution via Castos API for features
 * that require passthrough URLs (ads, campaigns).
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
 * Passthrough Controller
 *
 * @package Seriously Simple Podcasting
 * @since 3.16.0
 */
class Passthrough_Controller {

	const ENABLE_ADS_OPTION       = 'enable_ads';
	const ENABLE_CAMPAIGNS_OPTION = 'enable_campaigns';

	/**
	 * Castos handler instance.
	 *
	 * @var Castos_Handler
	 */
	protected $castos_handler;

	/**
	 * Passthrough_Controller constructor.
	 *
	 * @param Castos_Handler $castos_handler Handler for Castos API interactions.
	 */
	public function __construct( $castos_handler ) {
		$this->castos_handler = $castos_handler;

		add_filter( 'ssp_enclosure_url', array( $this, 'maybe_use_dynamic_content' ), 10, 2 );
		add_action( 'ssp_cron_hook', array( $this, 'sync_passthrough_settings' ) );
	}

	/**
	 * Sync passthrough settings from Castos via WP Cron.
	 */
	public function sync_passthrough_settings() {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}

		$podcasts = $this->castos_handler->get_podcast_items();
		foreach ( $podcasts as $podcast ) {
			$this->sync_series_option( self::ENABLE_ADS_OPTION, $podcast->ads_enabled, $podcast->series_id );
			$this->sync_series_option( self::ENABLE_CAMPAIGNS_OPTION, $podcast->campaigns_enabled, $podcast->series_id );
		}
	}

	/**
	 * Replaces current enclosure with dynamic Castos content URL.
	 *
	 * Called when a listener hits a passthrough URL. Fetches the current
	 * file URL from Castos (which may include ads or campaign stitching).
	 *
	 * @param string $enclosure  Original enclosure URL.
	 * @param int    $episode_id Episode ID.
	 *
	 * @return string Modified or original enclosure URL.
	 * @throws \Exception When episode file data retrieval fails.
	 */
	public function maybe_use_dynamic_content( $enclosure, $episode_id ) {

		// If SSP is not connected to Castos, return early.
		if ( ! ssp_is_connected_to_castos() ) {
			return $enclosure;
		}

		// Check that passthrough is required for this episode (ads, campaigns, or SSStats).
		if ( ! ssp_episode_passthrough_required( $episode_id ) ) {
			return $enclosure;
		}

		$file_data = $this->get_episode_file_data( $episode_id );

		if ( ! $file_data->success ) {
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
		$transient = 'ssp_episode_file_data_' . $episode_id;

		$file_data = get_transient( $transient );

		if ( $file_data ) {
			return $file_data;
		}

		$castos_episode_id = get_post_meta( $episode_id, 'podmotor_episode_id', true );
		$file_data         = $this->castos_handler->get_episode_file_data( $castos_episode_id );
		$ttl               = $file_data->success ? WEEK_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
		set_transient( $transient, $file_data, $ttl );

		return $file_data;
	}

	/**
	 * Syncs a series option to match the Castos state.
	 *
	 * @param string $option    Option name (e.g. 'enable_ads', 'enable_campaigns').
	 * @param bool   $enabled   Whether the feature is enabled in Castos.
	 * @param int    $series_id Series ID.
	 */
	protected function sync_series_option( $option, $enabled, $series_id ) {
		$is_locally_enabled = 'on' === ssp_get_option( $option, 'off', $series_id );

		if ( $enabled !== $is_locally_enabled ) {
			ssp_update_option( $option, $enabled ? 'on' : '', $series_id );
		}
	}

}
