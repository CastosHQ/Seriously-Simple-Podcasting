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
class Episode_Ads extends Abstract_API_Entity {

	/**
	 * @var string $ads_enabled
	 * */
	public $url;

	/**
	 * @var bool $ads_enabled
	 * */
	public $ads_enabled;

}
