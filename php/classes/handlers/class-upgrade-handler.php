<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class Upgrade_Handler implements Service {

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

		if ( version_compare( $previous_version, '2.2.4', '<' ) ) {
			$this->enable_distribution_upgrade_notice();
		}

		if ( version_compare( $previous_version, '2.2.4', '<' ) ) {
			$this->enable_elementor_template_notice();
		}

		if ( version_compare( $previous_version, '2.20.0', '<' ) ) {
			$this->update_enclosures();
		}
	}


	/**
	 * Update enclosures.
	 * Since version 2.20.0, we need to update enclosures to get rid of AWS files.
	 * */
	public function update_enclosures() {
		ignore_user_abort( true );
		$episode_ids = ssp_episode_ids();

		/**
		 * @var Episode_Repository $episode_repository
		 * */
		$episode_repository = ssp_get_service( 'episode_repository' );

		foreach ( $episode_ids as $episode_id ) {
			$enclosure = $episode_repository->get_enclosure( $episode_id );
			$updated   = $this->get_updated_enclosure_url( $enclosure );
			if ( $enclosure != $updated ) {
				$episode_repository->set_enclosure( $episode_id, $updated );

				// Also, update the old enclosure just for consistency
				update_post_meta( $episode_id, 'enclosure', $updated );
			}
		}
	}


	/**
	 * Variants:
	 * https://seriouslysimplepodcasting.s3.amazonaws.com/One-Sensitive/Intro.m4a -> https://episodes.castos.com/One-Sensitive/Intro.m4a
	 * https://s3.amazonaws.com/seriouslysimplepodcasting/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3 -> https://episodes.castos.com/spotfight/WWE-SmackDown-Review-ABSTURZ-18.10.19.mp3
	 * https://s3.us-west-001.backblazeb2.com/seriouslysimplepodcasting/thegatheringpodcast/In-suffering-take-2.mp3 -> https://episodes.castos.com/thegatheringpodcast/In-suffering-take-2.mp3
	 * https://episodes.seriouslysimplepodcasting.com/djreecepodcast/9PMCheckIn5-22-2017.mp3 -> https://episodes.castos.com/djreecepodcast/9PMCheckIn5-22-2017.mp3
	 * */
	public function get_updated_enclosure_url( $enclosure ) {

		$replacements = array(
			'seriouslysimplepodcasting.s3.amazonaws.com',
			's3.amazonaws.com/seriouslysimplepodcasting',
			's3.us-west-001.backblazeb2.com/seriouslysimplepodcasting',
			'episodes.seriouslysimplepodcasting.com',
		);

		foreach ( $replacements as $replacement ) {
			$pos = strpos( $enclosure, $replacement );
			if ( false !== $pos ) {
				return substr_replace( $enclosure, 'episodes.castos.com', $pos, strlen( $replacement ) );
			}
		}

		return $enclosure;
	}

	/**
	 * Adds the ss_podcasting_subscribe_options array to the options table
	 */
	public function upgrade_subscribe_links_options() {
		$subscribe_links_options = array(
			'apple_podcasts',
			'stitcher',
			'google_podcasts',
			'spotify',
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

	/**
	 * Update or create the ss_podcasting_elementor_templates_disabled option, to show the admin notice if it's off
	 */
	public function enable_elementor_template_notice() {
		if ( ! ssp_is_elementor_ok() ) {
			return;
		}
		$ss_podcasting_elementor_templates_disabled = get_option( 'ss_podcasting_elementor_templates_disabled', 'false' );
		if ( 'true' === $ss_podcasting_elementor_templates_disabled ) {
			return;
		}
		update_option( 'ss_podcasting_elementor_templates_disabled', 'false' );
	}

	/**
	 * Update or create the ss_podcasting_distribution_upgrade_disabled option, to show the admin notice if it's off
	 */
	public function enable_distribution_upgrade_notice() {
		$ss_podcasting_distribution_upgrade_disabled = get_option( 'ss_podcasting_distribution_upgrade_disabled', 'false' );
		if ( 'true' === $ss_podcasting_distribution_upgrade_disabled ) {
			return;
		}
		update_option( 'ss_podcasting_distribution_upgrade_disabled', 'false' );
	}
}
