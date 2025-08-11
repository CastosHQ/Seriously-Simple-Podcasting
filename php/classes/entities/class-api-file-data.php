<?php
/**
 * API_File_Data Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class API_File_Data.
 *
 * @since 2.24.0
 */
class API_File_Data extends Abstract_API_Entity {

	/**
	 * File ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Episode ID associated with the file.
	 *
	 * @var int
	 */
	public $episode_id;

	/**
	 * Path to the file.
	 *
	 * @var string
	 */
	public $file_path;
}
