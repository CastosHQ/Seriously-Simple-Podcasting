<?php

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract entity class.
 * @since 2.24.0
 */
class Episode_File_Data extends Abstract_API_Entity {

	/**
	 * @var string $ads_enabled
	 * */
	public $url;

	/**
	 * @var bool $ads_enabled
	 * */
	public $ads_enabled;

	/**
	 * @param array $properties
	 */
	public function __construct( $properties ) {
		parent::__construct( $properties );

		if ( isset( $properties['podcast.ads_enabled'] ) ) {
			$this->ads_enabled = $properties['podcast.ads_enabled'];
		}

		$this->success = isset( $properties['code'] ) && ( 200 === $properties['code'] );
	}

}
