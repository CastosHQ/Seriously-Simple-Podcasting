<?php
/**
 * Block unstubbed WordPress HTTP requests throughout the WPUnit suite.
 *
 * WPLoader loads this configFile before WordPress, so add_filter() is not
 * available yet. wp-browser's preload API registers the filter for WordPress
 * to initialize; WPTestCase then includes it in its saved hook baseline.
 *
 * Tests opt in to a response by adding their own pre_http_request filter at
 * the usual priority (10), returning a response array or WP_Error for their
 * target URL and the incoming value for other URLs. Remove that specific
 * callback in tearDown(), not all pre_http_request filters.
 *
 * @package Seriously_Simple_Podcasting
 */

use lucatume\WPBrowser\WordPress\PreloadFilters;

PreloadFilters::addFilter(
	'pre_http_request',
	static function ( $preempt, $args, $url ) {
		// Run last, so URL-selective stubs still see false and their replies win.
		if ( false !== $preempt ) {
			return $preempt;
		}

		$message = sprintf( 'WPUnit blocked unstubbed HTTP request: %s %s', $args['method'], $url );
		codecept_debug( $message );

		return new WP_Error(
			'wpunit_http_request_blocked',
			$message,
			array(
				'url'    => $url,
				'method' => $args['method'],
			)
		);
	},
	PHP_INT_MAX,
	3
);
