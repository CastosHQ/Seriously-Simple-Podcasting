<?php
/**
 * Available Podcasts Attribute class file.
 *
 * This class is for lazy loading Podcast settings
 * for the 'seriously-simple-podcasting/playlist-player' attributes.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Entities;

use JsonSerializable;

/**
 * Available Podcasts Attribute class.
 *
 * Handles lazy loading of podcast settings for playlist player attributes.
 */
class Available_Podcasts_Attribute implements JsonSerializable {
	/**
	 * Cached podcast settings.
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

		$default_series_id = ssp_get_default_series_id();

		$settings = array(
			array(
				'label' => __( '-- All --', 'seriously-simple-podcasting' ),
				'value' => - 1,
			),
		);

		$this->settings = array_merge(
			$settings,
			array_map(
				function ( $item ) use ( $default_series_id ) {
					$label = $default_series_id === $item->term_id ?
					ssp_get_default_series_name( $item->name ) :
					$item->name;

					return array(
						'label' => $label,
						'value' => $item->term_id,
					);
				},
				ssp_get_podcasts()
			)
		);

		return $this->settings;
	}
}
