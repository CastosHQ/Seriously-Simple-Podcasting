<?php
/**
 * Shortcode interface.
 *
 * @package SeriouslySimplePodcasting
 */

namespace SeriouslySimplePodcasting\ShortCodes;

interface Shortcode {
	/**
	 * Shortcode method.
	 *
	 * @param array $params Shortcode parameters.
	 *
	 * @return string
	 */
	public function shortcode( $params );
}
