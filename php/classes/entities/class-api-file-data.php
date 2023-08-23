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
class API_File_Data extends Abstract_API_Entity {

	/**
	 * @var int $id
	 * */
	public $id;

	/**
	 * @var int $id
	 * */
	public $episode_id;

	/**
	 * @var string $file_path
	 * */
	public $file_path;
}

