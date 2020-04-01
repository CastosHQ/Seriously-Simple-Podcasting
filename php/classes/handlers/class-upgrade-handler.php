<?php

namespace SeriouslySimplePodcasting\Handlers;

class Upgrade_Handler {

	/**
	 * Main upgrade method, called from admin controller
	 *
	 * @param $previous_version
	 */
	public function run_upgrades( $previous_version ) {
		if ( version_compare( $previous_version, '1.13.1', '<' ) ) {
			flush_rewrite_rules();
		}

		if ( version_compare( $previous_version, '1.19.20', '<=' ) ) {
			$this->upgrade_subscribe_links_options();
		}

		if ( version_compare( $previous_version, '1.20.3', '<=' ) ) {
			$this->upgrade_stitcher_subscribe_link_option();
		}

		if ( version_compare( $previous_version, '1.20.6', '<' ) ) {
			$this->add_default_episode_description_option();
		}

		if ( version_compare( $previous_version, '2.0', '<' ) ) {
			$this->clear_castos_api_credentials();
		}

	}

	/**
	 * Adds the ss_podcasting_subscribe_options array to the options table
	 */
	public function upgrade_subscribe_links_options() {
		$subscribe_links_options = array(
			'itunes_url'      => 'iTunes',
			'stitcher_url'    => 'Stitcher',
			'google_play_url' => 'Google Play',
			'spotify_url'     => 'Spotify',
		);
		update_option( 'ss_podcasting_subscribe_options', $subscribe_links_options );
	}

	/**
	 * Fixes an incorrectly spelled subscribe option
	 */
	public function upgrade_stitcher_subscribe_link_option() {
		$subscribe_links_options = get_option( 'ss_podcasting_subscribe_options', array() );
		if ( isset( $subscribe_links_options['stitcher_url'] ) ) {
			$subscribe_links_options['stitcher_url'] = 'Stitcher';
		}
		update_option( 'ss_podcasting_subscribe_options', $subscribe_links_options );
	}

	/**
	 * Adds the default episode_description option
	 */
	public function add_default_episode_description_option() {
		update_option( 'ss_podcasting_episode_description', 'excerpt' );
	}

	/**
	 * Update the ss_podcasting_podmotor_account_id value to trigger the API credential validation notification
	 */
	public function clear_castos_api_credentials() {
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}
		$podmotor_account_id = get_option( 'ss_podcasting_podmotor_account_id', '' );
		if ( empty( $podmotor_account_id ) ) {
			return;
		}
		update_option( 'ss_podcasting_podmotor_account_id', '2.0' );
	}
}
