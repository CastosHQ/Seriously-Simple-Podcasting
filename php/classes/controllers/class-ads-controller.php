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

	public function maybe_show_ads_settings( $fields ) {
		$show_ads = array(
			'id'          => 'enable_ads',
			'label'       => 'Enable Ads',
			'description' => __( 'Enable Ads', 'seriously-simple-podcasting' ),
			'type'        => 'checkbox',
			'default'     => 'off',
			'callback'    => 'wp_strip_all_tags',
		);

		// $podcasts = $this->castos_handler->get_podcasts();

		$fields['show_ads'] = $show_ads;

		return $fields;
	}

	public function maybe_use_ads( $enclosure, $episode_id ) {

		$episode_ads = $this->castos_handler->get_episode_ads( $episode_id );

		if ( ! $episode_ads->success ) {
			return $enclosure;
		}

		return $episode_ads->url;
	}
}
