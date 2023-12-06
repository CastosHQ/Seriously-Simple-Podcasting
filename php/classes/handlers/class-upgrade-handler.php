<?php

namespace SeriouslySimplePodcasting\Handlers;

use SeriouslySimplePodcasting\Entities\Failed_Sync_Episode;
use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Interfaces\Service;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

class Upgrade_Handler implements Service {

	/**
	 * @var Episode_Repository $episode_repository
	 * */
	protected $episode_repository;

	/**
	 * @var Castos_Handler $castos_handler
	 * */
	protected $castos_handler;

	/**
	 * @var Series_Handler $series_handler
	 * */
	protected $series_handler;

	/**
	 * @param Episode_Repository $episode_repository
	 * @param Castos_Handler $castos_handler
	 */
	public function __construct( $episode_repository, $castos_handler, $series_handler ) {
		$this->episode_repository = $episode_repository;
		$this->castos_handler = $castos_handler;
		$this->series_handler = $series_handler;
	}

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

		if ( version_compare( $previous_version, '2.23.0', '<' ) ) {
			$this->schedule_fixing_episodes_sync();
		}

		if ( version_compare( $previous_version, '3.0.0', '<' ) ) {
			$this->enable_default_series();
		}
	}

	public function enable_default_series() {
		$this->series_handler->enable_default_series();
	}

	/**
	 * @return void
	 */
	public function run_upgrade_actions() {
		add_action( 'ssp_fix_episodes_sync', array( $this, 'fix_episodes_sync' ) );
	}

	/**
	 * Update enclosures.
	 * Since version 2.20.0, we need to update enclosures to get rid of AWS files.
	 * */
	public function schedule_fixing_episodes_sync() {
		ignore_user_abort( true );
		if ( ! ssp_is_connected_to_castos() ) {
			return;
		}
		$episodes = $this->episode_repository->get_failed_sync_episodes();

		if ( is_array( $episodes ) && $episodes ) {
			$this->episode_repository->update_failed_sync_episodes_option( $episodes );
			$this->set_episodes_status( $episodes, Sync_Status::SYNC_STATUS_SYNCING );
			$this->schedule_fix_episodes_sync_event();
		}
	}

	/**
	 * @param Failed_Sync_Episode[] $episodes
	 * @param string $status
	 *
	 * @return void
	 */
	protected function set_episodes_status( $episodes, $status ) {
		foreach ( $episodes as $episode ) {
			$this->episode_repository->update_episode_sync_status( $episode->post_id, $status );
		}
	}

	/**
	 * @return void
	 */
	protected function schedule_fix_episodes_sync_event() {
		if ( ! wp_next_scheduled( 'ssp_fix_episodes_sync' ) ) {
			wp_schedule_event( time(), 'ssp_five_minutes', 'ssp_fix_episodes_sync' );
		}
	}


	/**
	 * @see self::schedule_fixing_episodes_sync()
	 * */
	public function fix_episodes_sync() {
		if ( $episodes = $this->episode_repository->get_failed_sync_episodes_option() ) {
			$this->schedule_fix_episodes_sync_event();
		}

		$max_episodes = 20;

		for ( $i = 0; $episodes && $i < $max_episodes; $i ++ ) {
			$episode = $episodes[ $i ];
			unset( $episodes[ $i ] );

			$file_data = $this->castos_handler->get_file_data( $episode->audio_file );

			$file_data_esists    = $file_data->episode_id && $file_data->id;
			$episode_id_conflict = $episode->podmotor_episode_id && $episode->podmotor_episode_id != $file_data->episode_id;

			// Ensure episode data is full and episode does not have episode ID that is different from file data
			if ( ! $file_data_esists || $episode_id_conflict ) {
				$this->episode_repository->update_episode_sync_status( $episode->post_id, Sync_Status::SYNC_STATUS_FAILED );
				if ( ! $file_data_esists ) {
					$error = __( 'Could not get file data by the file URL. Please try to reupload the file.', 'serously-simple-podcasting' );
				} elseif ( $episode_id_conflict ) {
					$error = __( 'Current file does not belong to the provided Castos Episode. Please try to reupload the file.', 'serously-simple-podcasting' );
				}
				if ( ! empty( $error ) ) {
					$this->episode_repository->update_episode_sync_error( $episode->post_id, $error );
				}
				continue;
			}

			// Make sure no other episodes has such episode ID or file ID yet
			$by_episode_id = $this->episode_repository->get_by_podmotor_episode_id( $file_data->episode_id );
			if ( count( $by_episode_id ) > 1 || ( $by_episode_id && $episode->post_id != $by_episode_id[0] ) ) {
				$error = __( 'Current Castos Episode ID already exists! Please remove this episode and create the new one.', 'serously-simple-podcasting' );
				$this->episode_repository->update_episode_sync_error( $episode->post_id, $error );
				continue;
			}

			$by_file_id = $this->episode_repository->get_by_podmotor_file_id( $file_data->id );
			if ( count( $by_file_id ) > 1 || ( $by_file_id && $episode->post_id != $by_file_id[0] ) ) {
				$error = __( 'Current File ID already exists! Please try to reupload the file.', 'serously-simple-podcasting' );
				$this->episode_repository->update_episode_sync_error( $episode->post_id, $error );
				continue;
			}

			update_post_meta( $episode->post_id, 'podmotor_episode_id', $file_data->episode_id );
			update_post_meta( $episode->post_id, 'podmotor_file_id', $file_data->id );

			$this->episode_repository->update_episode_sync_status( $episode->post_id, Sync_Status::SYNC_STATUS_SYNCED );
			$this->episode_repository->delete_sync_error( $episode->post_id );
		}

		$this->episode_repository->update_failed_sync_episodes_option( array_values( $episodes ) );
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
