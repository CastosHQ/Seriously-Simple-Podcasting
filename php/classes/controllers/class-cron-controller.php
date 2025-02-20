<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Entities\Sync_Status;
use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Handlers\Upgrade_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;
use SeriouslySimplePodcasting\Repositories\Episode_Repository;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSP Episode Controller
 *
 * @package Seriously Simple Podcasting
 */
class Cron_Controller {

	/**
	 * @var Castos_Handler $castos_handler
	 * */
	protected $castos_handler;

	/**
	 * @var Episode_Repository $episodes_respository
	 * */
	protected $episodes_respository;

	/**
	 * @var Upgrade_Handler $upgrade_handler
	 * */
	protected $upgrade_handler;

	const SYNC_SCHEDULE_META = 'podmotor_schedule_upload';

	const ATTEMPTS_META = 'castos_sync_attempts';

	const MAX_ATTEMPTS = 3;

	/**
	 * @param Castos_Handler $castos_handler
	 * @param Episode_Repository $episodes_respository
	 * @param Upgrade_Handler $upgrade_handler
	 */
	public function __construct( $castos_handler, $episodes_respository, $upgrade_handler ) {

		$this->castos_handler       = $castos_handler;
		$this->episodes_respository = $episodes_respository;
		$this->upgrade_handler      = $upgrade_handler;

		add_action( 'admin_init', array( $this, 'schedule_events' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );

		$this->run_actions();
	}

	/**
	 * @return void
	 */
	protected function run_actions() {
		add_action( 'ssp_cron_hook', array( $this, 'upload_scheduled_episodes' ) );
		$this->upgrade_handler->run_upgrade_actions();
	}

	/**
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_cron_intervals( $schedules ) {
		if ( empty( $schedules['ssp_five_minutes'] ) ) {
			$schedules['ssp_five_minutes'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'SSP every five minutes' ),
			);
		}

		return $schedules;
	}

	public function schedule_events() {
		if ( ! wp_next_scheduled( 'ssp_cron_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'ssp_cron_hook' );
		}
		if ( ! wp_next_scheduled( 'ssp_check_ads' ) ) {
			wp_schedule_event( time(), 'daily', 'ssp_check_ads' );
		}
	}

	/**
	 * @return int Number of uploaded episodes
	 */
	public function upload_scheduled_episodes() {
		$uploaded = 0;
		$logger   = new Log_Helper();

		foreach ( $this->episodes_respository->get_scheduled_episodes() as $episode ) {
			$response = $this->castos_handler->upload_episode_to_castos( $episode );

			if ( $response->success ) {
				$this->unschedule_episode( $episode->ID );
				$this->episodes_respository->update_episode_sync_status( $episode->ID, Sync_Status::SYNC_STATUS_SYNCED );
				$this->episodes_respository->delete_episode_sync_error( $episode->ID );
				$uploaded ++;
			} else {
				$attempts = get_post_meta( $episode->ID, self::ATTEMPTS_META, true );
				if ( $attempts < self::MAX_ATTEMPTS ) {
					update_post_meta( $episode->ID, self::ATTEMPTS_META, ++ $attempts );
				} else {
					$this->unschedule_episode( $episode->ID );
				}
			}

			if ( 404 == $response->code ) {
				$castos_episode_id = get_post_meta( $episode->ID, 'podmotor_episode_id', true );

				// File does not exist anymore, remove connection
				if ( $castos_episode_id ) {
					delete_post_meta( $episode->ID, 'podmotor_episode_id' );
					delete_post_meta( $episode->ID, 'podmotor_file_id' );
				}

				$this->unschedule_episode( $episode->ID );
				$logger->log( sprintf( 'Cron: file does not exists on Castos, stop syncing: %d', $episode->ID ) );
			}
		}

		if ( $uploaded ) {
			$logger->log( 'Cron: uploaded scheduled episodes', $uploaded );
		}

		return $uploaded;
	}

	protected function unschedule_episode( $episode_id ) {
		delete_post_meta( $episode_id, self::SYNC_SCHEDULE_META );
	}
}
