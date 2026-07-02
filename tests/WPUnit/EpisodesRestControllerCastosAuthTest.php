<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

/**
 * Verifies Castos webhook signature authentication via the action-bound
 * signature scheme (CastosHQ/ssp-issues#859).
 *
 * The action-bound signing format is mirrored from the Castos client
 * (App\Services\SSP\SspRequestSigner): METHOD\nPATH\njson_body\ntimestamp\nnonce,
 * where PATH is the canonical `{home path}/{rest-prefix}{route}`.
 */
class EpisodesRestControllerCastosAuthTest extends \Codeception\TestCase\WPTestCase {

	const TOKEN = 'secret-api-token';

	const ROUTE = '/ssp/v1/episodes/123';

	protected function setUp(): void {
		parent::setUp();
		update_option( 'ss_podcasting_podmotor_account_api_token', self::TOKEN );
	}

	protected function tearDown(): void {
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
		parent::tearDown();
	}

	/** A valid action-bound request authenticates. */
	public function test_valid_action_bound_request_passes() {
		$body    = array( 'file' => array( 'id' => 1 ) );
		$request = $this->action_bound_request( 'PUT', self::ROUTE, $body, time(), $this->nonce() );

		$this->assertTrue( Episodes_Rest_Controller::validate_castos_authentication( $request, $body ) );
	}

	/** An authenticated GET collection request with an empty body authenticates. */
	public function test_action_bound_get_with_empty_body_passes() {
		$request = $this->action_bound_request( 'GET', '/ssp/v1/episodes', array(), time(), $this->nonce() );

		$this->assertTrue( Episodes_Rest_Controller::validate_castos_authentication( $request, array() ) );
	}

	/** Signature is bound to the HTTP method — a method mismatch is rejected. */
	public function test_tampered_method_rejected() {
		$body      = array( 'file' => array( 'id' => 1 ) );
		$timestamp = time();
		$nonce     = $this->nonce();
		// Signed as GET, sent as PUT.
		$signature = $this->action_bound_signature( 'GET', self::ROUTE, $body, $timestamp, $nonce );
		$request   = $this->request( 'PUT', self::ROUTE, $body, $timestamp, $signature, $nonce );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, $body ) );
	}

	/** Signature is bound to the request path — a path mismatch (retargeting) is rejected. */
	public function test_tampered_path_rejected() {
		$body      = array( 'file' => array( 'id' => 1 ) );
		$timestamp = time();
		$nonce     = $this->nonce();
		// Signed for episode 123, replayed against episode 456.
		$signature = $this->action_bound_signature( 'PUT', self::ROUTE, $body, $timestamp, $nonce );
		$request   = $this->request( 'PUT', '/ssp/v1/episodes/456', $body, $timestamp, $signature, $nonce );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, $body ) );
	}

	/** Signature is bound to the body — a body mismatch is rejected. */
	public function test_tampered_body_rejected() {
		$timestamp = time();
		$nonce     = $this->nonce();
		$signature = $this->action_bound_signature( 'PUT', self::ROUTE, array( 'file' => array( 'id' => 1 ) ), $timestamp, $nonce );
		$tampered  = array( 'file' => array( 'id' => 999 ) );
		$request   = $this->request( 'PUT', self::ROUTE, $tampered, $timestamp, $signature, $nonce );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, $tampered ) );
	}

	/** An expired timestamp is rejected regardless of signature validity. */
	public function test_expired_timestamp_rejected() {
		$body    = array( 'file' => array( 'id' => 1 ) );
		$request = $this->action_bound_request( 'PUT', self::ROUTE, $body, time() - 11 * MINUTE_IN_SECONDS, $this->nonce() );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, $body ) );
	}

	/** A replayed nonce is rejected on the second use within the freshness window. */
	public function test_replayed_nonce_rejected() {
		$body      = array( 'file' => array( 'id' => 1 ) );
		$timestamp = time();
		$nonce     = $this->nonce();

		$first = Episodes_Rest_Controller::validate_castos_authentication(
			$this->action_bound_request( 'PUT', self::ROUTE, $body, $timestamp, $nonce ),
			$body
		);
		$this->assertTrue( $first, 'First use of the nonce should authenticate' );

		$second = Episodes_Rest_Controller::validate_castos_authentication(
			$this->action_bound_request( 'PUT', self::ROUTE, $body, $timestamp, $nonce ),
			$body
		);
		$this->assertWPError( $second, 'Replayed nonce should be rejected' );
	}

	/** A request without the X-Castos-Nonce header is rejected, even with an otherwise-valid action-bound signature. */
	public function test_request_without_nonce_is_rejected() {
		$body      = array( 'file' => array( 'id' => 1 ) );
		$timestamp = time();
		$nonce     = $this->nonce();
		$signature = $this->action_bound_signature( 'PUT', self::ROUTE, $body, $timestamp, $nonce );
		// Valid action-bound signature, but the nonce header is withheld.
		$request = $this->request( 'PUT', self::ROUTE, $body, $timestamp, $signature, null );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, $body ) );
	}

	private function nonce() {
		return bin2hex( random_bytes( 32 ) );
	}

	/** Canonical `{home path}/{rest-prefix}{route}` — mirrors how the verifier and Castos signer derive PATH. */
	private function route_path( $route ) {
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

		return $home_path . '/' . trim( rest_get_url_prefix(), '/' ) . $route;
	}

	/** Builds the action-bound canonical message and HMAC, mirroring the Castos signer. */
	private function action_bound_signature( $method, $route, $body, $timestamp, $nonce ) {
		$message = implode( "\n", array( strtoupper( $method ), $this->route_path( $route ), json_encode( $body ), $timestamp, $nonce ) );

		return hash_hmac( 'sha256', $message, self::TOKEN );
	}

	/** Builds a request carrying a valid action-bound signature for the given inputs. */
	private function action_bound_request( $method, $route, $body, $timestamp, $nonce ) {
		$signature = $this->action_bound_signature( $method, $route, $body, $timestamp, $nonce );

		return $this->request( $method, $route, $body, $timestamp, $signature, $nonce );
	}

	/** Builds a WP_REST_Request with the given Castos auth headers ($nonce = null omits the nonce header). */
	private function request( $method, $route, $body, $timestamp, $signature, $nonce ) {
		$request = new \WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( json_encode( $body ) );
		$request->set_header( 'X-Castos-Signature', $signature );
		$request->set_header( 'X-Castos-Timestamp', $timestamp );
		if ( null !== $nonce ) {
			$request->set_header( 'X-Castos-Nonce', $nonce );
		}

		return $request;
	}

	private function assertWPError( $value, $message = '' ) {
		$this->assertInstanceOf( '\WP_Error', $value, $message );
	}
}
