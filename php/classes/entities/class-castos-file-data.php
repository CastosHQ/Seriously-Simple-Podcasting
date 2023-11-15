<?php

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract entity class.
 * @since 2.25.0
 */
class Castos_File_Data extends Abstract_Entity {

	/**
	 * @var string
	 * */
	public $path = '';

	/**
	 * @var string
	 * */
	public $name = '';
}
