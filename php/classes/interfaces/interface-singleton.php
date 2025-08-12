<?php
/**
 * Singleton interface.
 *
 * @package SeriouslySimplePodcasting
 * @since   2.0.0
 */

namespace SeriouslySimplePodcasting\Interfaces;

/**
 * Singleton interface.
 *
 * @package Seriously_Simple_Podcasting
 * @since   2.0.0
 */
interface Singleton {

	/**
	 * Get singleton instance.
	 *
	 * @return static
	 */
	public static function instance();
}
