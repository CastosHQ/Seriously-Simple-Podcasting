<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Handlers\Series_Handler;
use SeriouslySimplePodcasting\Rest\Rest_Api_Controller;

class RestApiControllerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Rest_Api_Controller
	 */
	private $controller;

	protected function setUp(): void {
		parent::setUp();
		$series_handler   = $this->createMock( Series_Handler::class );
		$this->controller = new Rest_Api_Controller( ssp_episode_repository(), $series_handler );
	}

	/**
	 * Test that deprecated podcast_update endpoint returns deprecation response.
	 */
	public function testPodcastUpdateReturnsDeprecationResponse() {
		$this->setExpectedDeprecated( 'SeriouslySimplePodcasting\Rest\Rest_Api_Controller::update_rest_podcast' );

		$response = $this->controller->update_rest_podcast();

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 410, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['updated'] );
		$this->assertStringContainsString( 'deprecated', strtolower( $data['message'] ) );
		$this->assertStringContainsString( 'PUT /ssp/v1/episodes/{id}', $data['message'] );
	}

	/**
	 * Test that deprecated endpoint no longer processes tokens or files.
	 */
	public function testPodcastUpdateIgnoresTokenAndFilePayload() {
		$this->setExpectedDeprecated( 'SeriouslySimplePodcasting\Rest\Rest_Api_Controller::update_rest_podcast' );

		// Set up a valid token in the database.
		update_option( 'ss_podcasting_podmotor_account_api_token', 'test-token-123' );

		// Simulate a POST with token and file — the old behavior would process these.
		$_POST['ssp_podcast_api_token'] = 'test-token-123';
		$_FILES['ssp_podcast_file']     = array(
			'tmp_name' => '/tmp/fake.csv',
		);

		$response = $this->controller->update_rest_podcast();

		$data = $response->get_data();
		$this->assertFalse( $data['updated'], 'Deprecated endpoint must never process uploads' );
		$this->assertStringContainsString( 'deprecated', strtolower( $data['message'] ) );

		// Clean up superglobals.
		unset( $_POST['ssp_podcast_api_token'], $_FILES['ssp_podcast_file'] );
		delete_option( 'ss_podcasting_podmotor_account_api_token' );
	}

	/**
	 * Test that connection endpoint returns success for valid HMAC authentication.
	 */
	public function testConnectionEndpointReturnsSuccessForValidHmac() {
		$api_token = 'test-castos-api-token';
		update_option( 'ss_podcasting_podmotor_account_api_token', $api_token );

		$request = $this->make_hmac_request( $api_token );

		$result = $this->controller->validate_connection_request( $request );
		$this->assertTrue( $result, 'Valid HMAC should return true' );

		$response = $this->controller->get_connection_status();
		$this->assertInstanceOf( \WP_REST_Response::class, $response );

		$data = $response->get_data();
		$this->assertTrue( $data['connected'] );
		$this->assertSame( ssp_version(), $data['ssp_version'] );

		delete_option( 'ss_podcasting_podmotor_account_api_token' );
	}

	/**
	 * Test that connection endpoint rejects requests with missing HMAC headers.
	 */
	public function testConnectionEndpointRejectsMissingHeaders() {
		update_option( 'ss_podcasting_podmotor_account_api_token', 'some-token' );

		$request = new \WP_REST_Request( 'GET', '/ssp/v1/status' );

		$result = $this->controller->validate_connection_request( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'missing_signature', $result->get_error_code() );

		delete_option( 'ss_podcasting_podmotor_account_api_token' );
	}

	/**
	 * Test that connection endpoint rejects requests with wrong signature.
	 */
	public function testConnectionEndpointRejectsInvalidSignature() {
		update_option( 'ss_podcasting_podmotor_account_api_token', 'real-token' );

		$request = $this->make_hmac_request( 'wrong-token' );

		$result = $this->controller->validate_connection_request( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'invalid_signature', $result->get_error_code() );

		delete_option( 'ss_podcasting_podmotor_account_api_token' );
	}

	/**
	 * Test that connection endpoint rejects requests with expired timestamp.
	 */
	public function testConnectionEndpointRejectsExpiredTimestamp() {
		$api_token = 'test-token';
		update_option( 'ss_podcasting_podmotor_account_api_token', $api_token );

		$expired_timestamp = time() - ( 11 * MINUTE_IN_SECONDS );
		$signature         = hash_hmac( 'sha256', json_encode( array() ) . $expired_timestamp, $api_token );

		$request = new \WP_REST_Request( 'GET', '/ssp/v1/status' );
		$request->set_header( 'X-Castos-Timestamp', (string) $expired_timestamp );
		$request->set_header( 'X-Castos-Signature', $signature );

		$result = $this->controller->validate_connection_request( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'invalid_timestamp', $result->get_error_code() );

		delete_option( 'ss_podcasting_podmotor_account_api_token' );
	}

	/**
	 * Test that connection endpoint rejects when no API token is stored.
	 */
	public function testConnectionEndpointRejectsWhenNoTokenStored() {
		delete_option( 'ss_podcasting_podmotor_account_api_token' );

		$request = $this->make_hmac_request( 'any-token' );

		$result = $this->controller->validate_connection_request( $request );
		$this->assertWPError( $result );
		$this->assertSame( 'invalid_signature', $result->get_error_code() );
	}

	/**
	 * Creates a WP_REST_Request with valid HMAC headers for the given token.
	 *
	 * @param string $api_token API token to sign with.
	 *
	 * @return \WP_REST_Request
	 */
	private function make_hmac_request( $api_token ) {
		$timestamp = (string) time();
		$signature = hash_hmac( 'sha256', json_encode( array() ) . $timestamp, $api_token );

		$request = new \WP_REST_Request( 'GET', '/ssp/v1/status' );
		$request->set_header( 'X-Castos-Timestamp', $timestamp );
		$request->set_header( 'X-Castos-Signature', $signature );

		return $request;
	}
}
