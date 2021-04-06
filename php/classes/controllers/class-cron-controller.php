<?php

namespace SeriouslySimplePodcasting\Controllers;

use SeriouslySimplePodcasting\Handlers\Castos_Handler;
use SeriouslySimplePodcasting\Helpers\Log_Helper;

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
	public function __construct() {
		add_action( 'ssp_cron_hook', array( $this, 'upload_scheduled_episodes' ) );
		add_action( 'admin_init', array( $this, 'schedule_events' ) );
	}

	public function schedule_events() {
		if ( ! wp_next_scheduled( 'ssp_cron_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'ssp_cron_hook' );
		}
	}

	/**
	 * @return int Number of uploaded episodes
	 */
	public function upload_scheduled_episodes() {
		$castos_handler = new Castos_Handler();
		$uploaded = 0;
		foreach ( $this->get_scheduled_episodes() as $episode ) {
			$response = $castos_handler->upload_podcast_to_podmotor( $episode );

			if ( 'success' === $response['status'] ) {
				delete_post_meta( $episode->ID, 'podmotor_schedule_upload' );
				$uploaded++;
			}
		}

		if ( $uploaded ) {
			$logger = new Log_Helper();
			$logger->log( 'Cron: uploaded scheduled episodes', $uploaded );
		}

		return $uploaded;
	}


	/**
	 * @return array
	 */
	protected function get_scheduled_episodes() {
		$args = array(
			'post_type'  => ssp_post_types(),
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'   => 'podmotor_schedule_upload',
					'value' => 1,
				),
			),
		);

		$query = new \WP_Query( $args );

		return $query->get_posts();
	}
}
