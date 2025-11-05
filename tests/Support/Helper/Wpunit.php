<?php
/**
 * Helper class for WPUnit tests.
 *
 * @package Seriously_Simple_Podcasting
 */

namespace Tests\Support\Helper;

/**
 * Class Wpunit
 *
 * @package Helper
 */
class Wpunit extends \Codeception\Module
{
	/**
	 * Initialize the helper and register autoloader for WordPress test framework.
	 *
	 * @return void
	 */
	public function _initialize(): void {
		// Register an autoloader that will load WordPress test framework classes
		// when wp-browser's WPTestCase tries to use WP_UnitTestCase
		spl_autoload_register( function( $class ) {
			if ( $class === 'WP_UnitTestCase' || $class === 'WP_UnitTestCase_Base' || $class === 'PHPUnit_Adapter_TestCase' ) {
				$base_path = dirname( dirname( dirname( __DIR__ ) ) );
				$includes_path = $base_path . '/vendor/lucatume/wp-browser/includes/core-phpunit/includes/';
				
				if ( $class === 'PHPUnit_Adapter_TestCase' && file_exists( $includes_path . 'phpunit-adapter-testcase.php' ) ) {
					require_once $includes_path . 'phpunit-adapter-testcase.php';
					return true;
				}
				
				if ( $class === 'WP_UnitTestCase_Base' && file_exists( $includes_path . 'abstract-testcase.php' ) ) {
					if ( ! class_exists( 'PHPUnit_Adapter_TestCase' ) ) {
						require_once $includes_path . 'phpunit-adapter-testcase.php';
					}
					require_once $includes_path . 'abstract-testcase.php';
					return true;
				}
				
				if ( $class === 'WP_UnitTestCase' && file_exists( $includes_path . 'testcase.php' ) ) {
					if ( ! class_exists( 'WP_UnitTestCase_Base' ) ) {
						if ( ! class_exists( 'PHPUnit_Adapter_TestCase' ) ) {
							require_once $includes_path . 'phpunit-adapter-testcase.php';
						}
						require_once $includes_path . 'abstract-testcase.php';
					}
					require_once $includes_path . 'testcase.php';
					return true;
				}
			}
			return false;
		}, true, true ); // prepend = true, so this autoloader runs first
	}
}
