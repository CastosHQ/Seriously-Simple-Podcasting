<?php
/**
 * Abstract API Entity.
 *
 * @package SeriouslySimplePodcasting
 * */

namespace SeriouslySimplePodcasting\Entities;

/**
 * Class Abstract_API_Entity.
 * Abstract API entity class.
 * @since 2.23.0
 */
abstract class Abstract_API_Entity extends Abstract_Entity {

	/**
	 * @var int $id
	 * */
	public $code;

	/**
	 * @var string $message
	 * */
	public $message;
}

