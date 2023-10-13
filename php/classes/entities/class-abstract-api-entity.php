<?php

/**
 * Abstract API Entity.
 *
 * @package SeriouslySimplePodcasting
 * @since 2.23.0
 * */

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract API entity class.
 * @since 2.23.0
 * @author Serhiy Zakharchenko
 */
abstract class Abstract_API_Entity extends Abstract_Entity {

	/**
	 * @var int $code
	 * */
	public $code;

	/**
	 * @var string $message
	 * */
	public $message;

	/**
	 * @var bool $success
	 * */
	public $success = false;
}
