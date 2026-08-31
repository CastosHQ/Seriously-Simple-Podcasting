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
	 * The series `guid` REST field reports only the podcast's own GUID, and the
	 * default podcast reports the same value through the REST field and the
	 * Castos push payload.
	 */
	public function testSeriesGuidFieldMatchesPushPayload() {
		$legacy  = '9b1e7c34-2f5a-5d8e-b6c1-4a7f0e3d92aa';
		$default = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$other   = $this->factory()->term->create( array( 'taxonomy' => ssp_series_taxonomy() ) );
		$castos  = ssp_get_service( 'castos_handler' );

		update_option( 'ss_podcasting_default_series', $default );
		update_option( 'ss_podcasting_data_guid', $legacy );

		$this->assertSame( '', $this->controller->series_get_field_value( array( 'id' => $other ), 'guid', null ) );
		$this->assertArrayNotHasKey( 'guid', $castos->generate_series_data_for_castos( $other ) );

		$this->assertSame( $legacy, $this->controller->series_get_field_value( array( 'id' => $default ), 'guid', null ) );
		$this->assertSame( $legacy, $castos->generate_series_data_for_castos( $default )['guid'] );

		delete_option( 'ss_podcasting_default_series' );
		delete_option( 'ss_podcasting_data_guid' );
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
		$this->assertSame( 'no_api_key', $result->get_error_code() );
	}

	/**
	 * Creates a WP_REST_Request with valid action-bound HMAC headers for the given token.
	 *
	 * Mirrors the Castos signer: signs METHOD\nPATH\ncanonical_query\njson_body\ntimestamp\nnonce,
	 * where PATH is the canonical `{home path}/{rest-prefix}{route}` the verifier reconstructs and
	 * canonical_query is '' here (the status request carries no query params).
	 *
	 * @param string $api_token API token to sign with.
	 *
	 * @return \WP_REST_Request
	 */
	private function make_hmac_request( $api_token ) {
		$route     = '/ssp/v1/status';
		$timestamp = (string) time();
		$nonce     = bin2hex( random_bytes( 32 ) );
		$path      = rtrim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' ) . '/' . trim( rest_get_url_prefix(), '/' ) . $route;
		$message   = implode( "\n", array( 'GET', $path, '', json_encode( array() ), $timestamp, $nonce ) );
		$signature = hash_hmac( 'sha256', $message, $api_token );

		$request = new \WP_REST_Request( 'GET', $route );
		$request->set_header( 'X-Castos-Timestamp', $timestamp );
		$request->set_header( 'X-Castos-Nonce', $nonce );
		$request->set_header( 'X-Castos-Signature', $signature );

		return $request;
	}
}
