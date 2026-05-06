<?php
/**
 * Episode File Data entity class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Episode File Data entity class.
 *
 * @since 2.24.0
 */
class Episode_File_Data extends Abstract_API_Entity {

	/**
	 * File URL.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Whether ads are enabled.
	 *
	 * @var bool
	 */
	public $ads_enabled;

	/**
	 * Whether campaigns are enabled.
	 *
	 * @var bool
	 */
	public $campaigns_enabled;

	/**
	 * Constructor.
	 *
	 * @param array $properties Entity properties.
	 */
	public function __construct( $properties ) {
		parent::__construct( $properties );

		if ( isset( $properties['podcast.ads_enabled'] ) ) {
			$this->ads_enabled = $properties['podcast.ads_enabled'];
		}

		if ( isset( $properties['podcast.campaigns_enabled'] ) ) {
			$this->campaigns_enabled = $properties['podcast.campaigns_enabled'];
		}

		$this->success = isset( $properties['code'] ) && ( 200 === $properties['code'] );
	}
}
