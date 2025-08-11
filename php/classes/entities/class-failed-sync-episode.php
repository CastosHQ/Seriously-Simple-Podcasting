<?php
/**
 * Failed_Sync_Episode Entity.
 *
 * @package SeriouslySimplePodcasting
 * @since   2.23.0
 */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Failed_Sync_Episode.
 *
 * @package SeriouslySimplePodcasting
 * @since   2.23.0
 */
class Failed_Sync_Episode extends Abstract_Entity {

	/**
	 * Post ID.
	 *
	 * @var int $post_id
	 */
	public $post_id;

	/**
	 * Podmotor file ID.
	 *
	 * @var int $podmotor_file_id
	 */
	public $podmotor_file_id;

	/**
	 * Podmotor episode ID.
	 *
	 * @var int $podmotor_episode_id
	 */
	public $podmotor_episode_id;

	/**
	 * Audio file path.
	 *
	 * @var string $audio_file
	 */
	public $audio_file;
}
