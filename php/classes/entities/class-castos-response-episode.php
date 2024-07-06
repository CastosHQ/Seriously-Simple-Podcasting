<?php
/**
 * Castos_Response_Episode Entity.
 *
 * @package SeriouslySimplePodcasting
 * */
namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Castos_Response_Episode. Is used instead of response array in Castos_Handler class.
 * @since 3.5.0
 */
class Castos_Response_Episode extends Castos_Response {

	public $castos_episode_id;

	public function update( $raw_response ) {
		parent::update( $raw_response );
		if( $this->body && isset( $this->body['episode']['id'] ) ){
			$this->castos_episode_id = $this->body['episode']['id'];
		}
	}
}
