<?php
/**
 * Singleton Trait
 */

namespace SeriouslySimplePodcasting\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton Trait
 *
 * @author Sergiy Zakharchenko
 * @package SeriouslySimplePodcasting
 * @since 2.9.3
 */
trait Singleton {

	/**
	 * The single instance.
	 * @var $this
	 */
	protected static $_instance;

	/**
	 * Protected constructor.
	 */
	protected function __construct() {
		return $this;
	}

	/**
	 * Main SSP_Speakers Instance
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @return $this
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
