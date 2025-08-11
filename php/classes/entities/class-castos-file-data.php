<?php
/**
 * Castos File Data entity class file.
 *
 * @package Seriously Simple Podcasting
 */

namespace SeriouslySimplePodcasting\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Castos File Data entity class.
 *
 * @since 2.25.0
 */
class Castos_File_Data extends Abstract_Entity {

	/**
	 * File path.
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * File name.
	 *
	 * @var string
	 */
	public $name = '';
}
