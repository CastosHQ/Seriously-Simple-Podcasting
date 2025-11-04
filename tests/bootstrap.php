<?php
/**
 * PHPUnit bootstrap file for Seriously Simple Podcasting plugin tests.
 *
 * @package Seriously_Simple_Podcasting
 */

// Load Composer autoloader
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WordPress test environment from wp-browser
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', dirname( __DIR__ ) . '/vendor/lucatume/wp-browser/includes/core-phpunit/' );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/' );
}

// Load WordPress test functions
if ( file_exists( WP_TESTS_DIR . 'includes/functions.php' ) ) {
	require_once WP_TESTS_DIR . 'includes/functions.php';
}

// Load the plugin before WordPress finishes bootstrapping.
if ( file_exists( dirname( __DIR__ ) . '/seriously-simple-podcasting.php' ) && function_exists( 'tests_add_filter' ) ) {
	tests_add_filter(
		'muplugins_loaded',
		static function () {
			require dirname( __DIR__ ) . '/seriously-simple-podcasting.php';
		}
	);
}

// Load WordPress test bootstrap
if ( file_exists( WP_TESTS_DIR . 'includes/bootstrap.php' ) ) {
	require_once WP_TESTS_DIR . 'includes/bootstrap.php';
}

// Load WordPress test case classes
if ( file_exists( WP_TESTS_DIR . 'includes/abstract-testcase.php' ) ) {
	require_once WP_TESTS_DIR . 'includes/abstract-testcase.php';
}

if ( file_exists( WP_TESTS_DIR . 'includes/testcase.php' ) ) {
	require_once WP_TESTS_DIR . 'includes/testcase.php';
}
