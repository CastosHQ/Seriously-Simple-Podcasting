<?php
/**
 * Verify the suite-wide HTTP guard and its compatibility with test stubs.
 *
 * @package Seriously_Simple_Podcasting
 */

namespace Tests\WPUnit;

class HttpIsolationTest extends \Codeception\TestCase\WPTestCase {

	private const URL = 'https://wpunit.example.invalid/feed.xml';

	public function testUnstubbedRequestsAreBlockedWithUrlDiagnostics() {
		foreach ( array( self::URL, home_url( '/?feed=podcast' ) ) as $url ) {
			foreach ( array( 'GET', 'POST' ) as $method ) {
				$response = wp_remote_request( $url, array( 'method' => $method ) );

				$this->assertInstanceOf( \WP_Error::class, $response );
				$this->assertSame( 'wpunit_http_request_blocked', $response->get_error_code() );
				$this->assertStringContainsString( $url, $response->get_error_message() );
				$this->assertSame( array( 'url' => $url, 'method' => $method ), $response->get_error_data() );
			}
		}
	}

	public function testUrlSelectiveStubWinsAtExistingTestPriorities() {
		$reply = array(
			'headers'  => array(),
			'body'     => 'Stubbed feed',
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
		);

		// Existing success stubs use 10; RSSImportHandlerTest's error override uses 20.
		foreach ( array( 10, 20 ) as $priority ) {
			$incoming = null;
			$stub     = static function ( $preempt, $args, $url ) use ( $reply, &$incoming ) {
				if ( self::URL !== $url ) {
					return $preempt;
				}

				$incoming = $preempt;
				return $reply;
			};

			add_filter( 'pre_http_request', $stub, $priority, 3 );
			try {
				$this->assertSame( $reply, wp_remote_get( self::URL ) );
				$this->assertFalse( $incoming, 'The guard must not preempt before URL-selective stubs run.' );
				$response = wp_remote_get( self::URL . '?unstubbed=1' );
				$this->assertInstanceOf( \WP_Error::class, $response );
				$this->assertSame( 'wpunit_http_request_blocked', $response->get_error_code() );
			} finally {
				remove_filter( 'pre_http_request', $stub, $priority );
			}
		}
	}

	public function testStubbedErrorOverridesEarlierSuccessResponse() {
		$error   = new \WP_Error( 'http_request_failed', 'Simulated feed failure' );
		$success = static function () {
			return array( 'body' => '', 'response' => array( 'code' => 200 ) );
		};
		$failure = static function () use ( $error ) {
			return $error;
		};

		add_filter( 'pre_http_request', $success, 10 );
		add_filter( 'pre_http_request', $failure, 20 );
		try {
			$this->assertSame( $error, wp_remote_get( self::URL ) );
		} finally {
			remove_filter( 'pre_http_request', $success, 10 );
			remove_filter( 'pre_http_request', $failure, 20 );
		}
	}

	public function testWordPressHookRestorationKeepsTheSuiteGuard() {
		// CastosHandlerImageTest removes all HTTP filters before parent tearDown().
		remove_all_filters( 'pre_http_request' );
		$this->_restore_hooks();

		$response = wp_remote_get( self::URL );
		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 'wpunit_http_request_blocked', $response->get_error_code() );
	}
}
