<?php
/**
 * Available Tags Attribute class file.
 *
 * This class is for lazy loading Tag settings
 * for the 'seriously-simple-podcasting/playlist-player' attributes.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Entities;

use JsonSerializable;

/**
 * Available Tags Attribute class.
 *
 * Handles lazy loading of tag settings for playlist player attributes.
 */
class Available_Tags_Attribute implements JsonSerializable {
	/**
	 * Cached tag settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Handles converting it to strings.
	 *
	 * @return false|string
	 */
	public function __toString() {
		return wp_json_encode( $this->get_settings() );
	}

	/**
	 * Handles serialization.
	 *
	 * @return array[]
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->get_settings();
	}

	/**
	 * Returns the settings array.
	 *
	 * @return array[]
	 */
	protected function get_settings() {
		if ( $this->settings ) {
			return $this->settings;
		}

		$settings = array(
			array(
				'label' => __( '-- All --', 'seriously-simple-podcasting' ),
				'value' => '',
			),
		);

		$this->settings = array_merge(
			$settings,
			array_map(
				function ( $item ) {
					return array(
						'label' => $item->name,
						'value' => $item->slug,
					);
				},
				ssp_get_tags()
			)
		);

		return $this->settings;
	}
}
