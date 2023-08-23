<?php
/**
 * Abstract Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Entity.
 * Abstract entity class.
 * @since 2.23.0
 */
class Failed_Sync_Episode extends Abstract_Entity {

	/**
	 * @var int $post_id
	 * */
	public $post_id;

	/**
	 * @var int $podmotor_file_id
	 * */
	public $podmotor_file_id;


	/**
	 * @var int $podmotor_episode_id
	 * */
	public $podmotor_episode_id;

	/**
	 * @var string $audio_file
	 * */
	public $audio_file;
}
