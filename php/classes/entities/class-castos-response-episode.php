<?php
/**
 * Castos Response Episode entity class file.
 *
 * @package SeriouslySimplePodcasting
 */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Castos_Response_Episode. Is used instead of response array in Castos_Handler class.
 *
 * @since 3.5.0
 */
class Castos_Response_Episode extends Castos_Response {

	/**
	 * Castos episode ID.
	 *
	 * @var int|null
	 */
	public $castos_episode_id;

	/**
	 * Updates the response with raw data.
	 *
	 * @param array $raw_response Raw response data.
	 * @return void
	 */
	public function update( $raw_response ) {
		parent::update( $raw_response );
		$this->castos_episode_id = null;
		if ( is_array( $this->body ) && isset( $this->body['episode']['id'] ) ) {
			$this->castos_episode_id = (int) $this->body['episode']['id'];
		}
	}
}
