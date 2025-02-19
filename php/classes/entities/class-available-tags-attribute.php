<?php
/**
 * This class is for lazy loading Tag settings
 * for the 'seriously-simple-podcasting/playlist-player' attributes.
 * */

namespace SeriouslySimplePodcasting\Entities;

use JsonSerializable;

class Available_Tags_Attribute implements JsonSerializable {
	private $settings;

	/**
	 * Handles converting it to strings.
	 *
	 * @return false|string
	 */
	public function __toString() {
		return json_encode( $this->get_settings() );
	}

	/**
	 * Handles serialization.
	 *
	 * @return array[]
	 */
	public function jsonSerialize() {
		return $this->get_settings();
	}

	/**
	 * Returns the settings array.
	 *
	 * @return array[]
	 */
	protected function get_settings(){
		if ( $this->settings ) {
			return $this->settings;
		}

		$settings = [
			[
				'label' => __( '-- All --', 'seriously-simple-podcasting' ),
				'value' => '',
			],
		];

		$this->settings = array_merge(
			$settings,
			array_map( function ( $item ) {
				return [
					'label' => $item->name,
					'value' => $item->slug,
				];
			}, ssp_get_tags() ) );


		return $this->settings;
	}
}
