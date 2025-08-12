<?php
/**
 * Log Helper Class
 *
 * A helper class that provides logging functionality using the Logger trait.
 *
 * @package Seriously Simple Podcasting
 * @since 1.19.20
 */

namespace SeriouslySimplePodcasting\Helpers;

// Exit if accessed directly.
use SeriouslySimplePodcasting\Traits\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Log_Helper
 *
 * Implements logging functionality using the Logger trait.
 *
 * @package SeriouslySimplePodcasting\Helpers
 * @since 1.19.20
 */
class Log_Helper {

	use Logger;
}
