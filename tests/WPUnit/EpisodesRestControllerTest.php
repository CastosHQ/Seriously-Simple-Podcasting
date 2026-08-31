<?php

namespace Tests\WPUnit;

use SeriouslySimplePodcasting\Rest\Episodes_Rest_Controller;

class EpisodesRestControllerTest extends \Codeception\TestCase\WPTestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * The nonce gate grants cookie-derived privilege only for a valid wp_rest nonce.
	 */
	public function testHasValidRestNonceOnlyAcceptsValidNonce() {
		$editor = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $editor );

		$method = new \ReflectionMethod( Episodes_Rest_Controller::class, 'has_valid_rest_nonce' );
		$method->setAccessible( true );

		$missing = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$this->assertFalse( $method->invoke( null, $missing ), 'No nonce must not grant privilege' );

		$invalid = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$invalid->set_header( 'X-WP-Nonce', 'not-a-real-nonce' );
		$this->assertFalse( $method->invoke( null, $invalid ), 'An invalid nonce must not grant privilege' );

		$non_scalar = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$non_scalar->set_param( '_wpnonce', array( 'x' ) );
		$this->assertFalse( $method->invoke( null, $non_scalar ), 'A non-scalar _wpnonce must not grant privilege' );

		$valid = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$valid->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$this->assertTrue( $method->invoke( null, $valid ), 'A valid wp_rest nonce must grant privilege' );
	}

	/**
	 * A logged-in cookie without a REST nonce must not expose private episodes (the CSRF gap).
	 */
	public function testGetItemsWithoutNonceHidesPrivateEpisodes() {
		$user    = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$publish = $this->create_episode( 'publish', $user );
		$private = $this->create_episode( 'private', $user );

		// Logged-in browser cookie present, but no REST nonce accompanies the request.
		$_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $user, time() + HOUR_IN_SECONDS, 'logged_in' );
		wp_set_current_user( 0 );

		$ids = $this->get_items_ids();

		$this->assertContains( $publish, $ids, 'Published episode should be listed' );
		$this->assertNotContains( $private, $ids, 'Private episode must not leak without a valid REST nonce' );
	}

	/**
	 * A privileged user with a valid REST nonce still sees private episodes (no editor regression).
	 */
	public function testGetItemsWithValidNonceShowsPrivateEpisodes() {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		// A valid nonce is bound to the resolved user, exactly as core keeps the cookie user
		// when @wordpress/api-fetch sends X-WP-Nonce.
		wp_set_current_user( $user );
		$nonce = wp_create_nonce( 'wp_rest' );

		$publish = $this->create_episode( 'publish', $user );
		$private = $this->create_episode( 'private', $user );

		$ids = $this->get_items_ids( array( 'X-WP-Nonce' => $nonce ) );

		$this->assertContains( $publish, $ids );
		$this->assertContains( $private, $ids, 'A privileged user with a valid nonce should see private episodes' );
	}

	/**
	 * The episode GUID captured on import must be readable by Castos through
	 * the episode payload's meta; an episode without one is unaffected.
	 */
	public function testEpisodePayloadExposesOriginalGuid() {
		// The WP test case unregisters all meta keys in tearDown, so the plugin's
		// init-time registration is gone by this test — re-run it.
		ssp_get_service( 'cpt_podcast_handler' )->register_post_type();

		$user     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$imported = $this->create_episode( 'publish', $user );
		$manual   = $this->create_episode( 'publish', $user );

		update_post_meta( $imported, 'ssp_original_guid', 'https://example.com/?p=123' );

		$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );

		$items = array();
		foreach ( (array) $this->make_controller()->get_items( $request )->get_data() as $item ) {
			$items[ $item['id'] ] = $item;
		}

		$this->assertSame(
			'https://example.com/?p=123',
			isset( $items[ $imported ]['meta']['ssp_original_guid'] ) ? $items[ $imported ]['meta']['ssp_original_guid'] : null,
			'Imported episode GUID must be exposed in the REST meta Castos pulls'
		);
		$this->assertSame(
			'',
			isset( $items[ $manual ]['meta']['ssp_original_guid'] ) ? $items[ $manual ]['meta']['ssp_original_guid'] : null,
			'An episode without an imported GUID exposes the field as empty'
		);
	}

	private function create_episode( $status, $author ) {
		return self::factory()->post->create(
			array(
				'post_type'   => SSP_CPT_PODCAST,
				'post_status' => $status,
				'post_author' => $author,
			)
		);
	}

	/** Calls get_items() with the given headers and returns the listed episode IDs. */
	private function get_items_ids( $headers = array() ) {
		$request = new \WP_REST_Request( 'GET', '/ssp/v1/episodes' );
		$request->set_param( 'per_page', 10 );
		$request->set_param( 'page', 1 );

		foreach ( $headers as $key => $value ) {
			$request->set_header( $key, $value );
		}

		$response = $this->make_controller()->get_items( $request );

		$ids = array();
		foreach ( (array) $response->get_data() as $item ) {
			if ( isset( $item['id'] ) ) {
				$ids[] = $item['id'];
			}
		}

		return $ids;
	}

	/**
	 * Test that the filter parameter cannot override post_status for unauthenticated requests.
	 */
	public function testFilterCannotOverridePostStatusForUnauthenticated() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'post_status' => 'draft',
			'post_type'   => 'page',
			's'           => 'test',
		);

		$result = $method->invoke( $controller, $filter, false );

		$this->assertArrayNotHasKey( 'post_status', $result, 'filter[post_status] should be stripped for unauthenticated requests' );
		$this->assertArrayNotHasKey( 'post_type', $result, 'filter[post_type] should be stripped for unauthenticated requests' );
	}

	/**
	 * Test that authenticated users can use filter params.
	 */
	public function testFilterAllowedForAuthenticatedRequests() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'post_status' => 'draft',
			'post_type'   => 'page',
			's'           => 'test',
		);

		$result = $method->invoke( $controller, $filter, true );

		$this->assertArrayHasKey( 'post_status', $result );
		$this->assertArrayHasKey( 'post_type', $result );
	}

	/**
	 * Test that only allowlisted keys survive for unauthenticated users.
	 */
	public function testOnlyAllowlistedKeysPassForUnauthenticated() {
		$controller = $this->make_controller();

		$method = new \ReflectionMethod( $controller, 'sanitize_filter_args' );
		$method->setAccessible( true );

		$filter = array(
			'meta_query'       => array( array( 'key' => '_secret', 'value' => 'x' ) ),
			'meta_key'         => '_secret',
			'nopaging'         => true,
			'has_password'     => true,
			'suppress_filters' => true,
			's'                => 'test',
			'posts_per_page'   => 10,
		);

		$result = $method->invoke( $controller, $filter, false );

		$this->assertArrayHasKey( 's', $result );
		$this->assertArrayHasKey( 'posts_per_page', $result );
		$this->assertArrayNotHasKey( 'meta_query', $result );
		$this->assertArrayNotHasKey( 'meta_key', $result );
		$this->assertArrayNotHasKey( 'nopaging', $result );
		$this->assertArrayNotHasKey( 'has_password', $result );
		$this->assertArrayNotHasKey( 'suppress_filters', $result );
	}

	private function make_controller() {
		$episode_repo = ssp_episode_repository();

		return new Episodes_Rest_Controller( $episode_repo );
	}
}
