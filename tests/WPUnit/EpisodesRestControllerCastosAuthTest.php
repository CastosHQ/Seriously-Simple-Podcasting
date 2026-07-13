<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

/**
 * Verifies Castos webhook signature authentication via the action-bound
 * signature scheme (CastosHQ/ssp-issues#859, #858).
 *
 * The action-bound signing format is mirrored from the Castos client
 * (App\Services\SSP\SspRequestSigner):
 * METHOD\nPATH\ncanonical_query\njson_body\ntimestamp\nnonce, where PATH is the
 * canonical `{home path}/{rest-prefix}{route}` and canonical_query is the ksort'd,
 * RFC3986-encoded query string ('' when empty).
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

	/** An authenticated GET carrying real query params authenticates when the query is signed. */
	public function test_action_bound_get_with_query_passes() {
		$query   = array( 'per_page' => 25, 'page' => 2, 'castos-nonce' => 1700000000 );
		$request = $this->action_bound_request( 'GET', '/ssp/v1/podcasts/42/episodes', array(), time(), $this->nonce(), $query );

		$this->assertTrue( Episodes_Rest_Controller::validate_castos_authentication( $request, array() ) );
	}

	/** Signature is bound to the query — mutating a signed query value is rejected. */
	public function test_tampered_query_value_rejected() {
		$timestamp = time();
		$nonce     = $this->nonce();
		$signature = $this->action_bound_signature( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $nonce, array( 'per_page' => 25, 'page' => 2 ) );
		// Signed page=2, sent page=99.
		$request   = $this->request( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $signature, $nonce, array( 'per_page' => 25, 'page' => 99 ) );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, array() ) );
	}

	/** An on-path attacker injecting an authority-bearing query param onto a signed GET is rejected. */
	public function test_injected_authority_query_param_rejected() {
		$timestamp = time();
		$nonce     = $this->nonce();
		$signed    = array( 'per_page' => 25, 'page' => 2 );
		$signature = $this->action_bound_signature( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $nonce, $signed );
		// Attacker appends filter[post_status]=private, which the signer never covered.
		$tampered  = $signed + array( 'filter' => array( 'post_status' => 'private' ) );
		$request   = $this->request( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $signature, $nonce, $tampered );

		$this->assertWPError( Episodes_Rest_Controller::validate_castos_authentication( $request, array() ) );
	}

	/** Query key order is irrelevant — both sides ksort before signing. */
	public function test_query_binding_is_order_independent() {
		$timestamp = time();
		$nonce     = $this->nonce();
		// Sign one key order...
		$signature = $this->action_bound_signature( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $nonce, array( 'page' => 2, 'per_page' => 25 ) );
		// ...send the reverse order; the ksort on both sides makes the signature still match.
		$request   = $this->request( 'GET', '/ssp/v1/podcasts/42/episodes', array(), $timestamp, $signature, $nonce, array( 'per_page' => 25, 'page' => 2 ) );

		$this->assertTrue( Episodes_Rest_Controller::validate_castos_authentication( $request, array() ) );
	}

	/**
	 * Cross-repo pin shared with App\Services\SSP\SspRequestSignerTest::test_golden_vector_matches_the_shared_canonical_form.
	 * Any drift in the canonical form (key sorting, RFC3986 encoding, segment order) on either
	 * side breaks this identical vector.
	 */
	public function test_golden_vector_matches_the_shared_canonical_form() {
		$expected = 'f8fea0761219ff3f6fd9b997ac0571612f5fa222b39dfa3d2e9130c794e3e238';

		$message = implode(
			"\n",
			array(
				'GET',
				'/wp-json/ssp/v1/podcasts/42/episodes',
				'castos-nonce=1700000000&page=2&per_page=25',
				json_encode( array() ),
				1700000000,
				'golden-nonce',
			)
		);

		$this->assertSame( $expected, hash_hmac( 'sha256', $message, self::TOKEN ) );
	}

	private function nonce() {
		return bin2hex( random_bytes( 32 ) );
	}

	/** Canonical `{home path}/{rest-prefix}{route}` — mirrors how the verifier and Castos signer derive PATH. */
	private function route_path( $route ) {
		$home_path = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );

		return $home_path . '/' . trim( rest_get_url_prefix(), '/' ) . $route;
	}

	/** Canonicalizes the query the way both the Castos signer and the verifier do (ksort + RFC3986, '' when empty). */
	private function canonical_query( $query ) {
		if ( empty( $query ) ) {
			return '';
		}

		ksort( $query );

		return http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}

	/** Builds the action-bound canonical message and HMAC, mirroring the Castos signer. */
	private function action_bound_signature( $method, $route, $body, $timestamp, $nonce, $query = array() ) {
		$message = implode( "\n", array( strtoupper( $method ), $this->route_path( $route ), $this->canonical_query( $query ), json_encode( $body ), $timestamp, $nonce ) );

		return hash_hmac( 'sha256', $message, self::TOKEN );
	}

	/** Builds a request carrying a valid action-bound signature for the given inputs. */
	private function action_bound_request( $method, $route, $body, $timestamp, $nonce, $query = array() ) {
		$signature = $this->action_bound_signature( $method, $route, $body, $timestamp, $nonce, $query );

		return $this->request( $method, $route, $body, $timestamp, $signature, $nonce, $query );
	}

	/** Builds a WP_REST_Request with the given Castos auth headers ($nonce = null omits the nonce header). */
	private function request( $method, $route, $body, $timestamp, $signature, $nonce, $query = array() ) {
		$request = new \WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( json_encode( $body ) );
		if ( ! empty( $query ) ) {
			$request->set_query_params( $query );
		}
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
